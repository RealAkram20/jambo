# Jambo — Mobile, TV and Offline App Plan

**Status:** accepted (2026-09-08). ADR-0002/3/4 accepted unamended; Rio
chose to start with Phase 1 (the API) rather than the app shell, because the
app feeds off the live webapp. Preconditions in §10 are still open and are
not blockers until Phase 3 (downloads).
**Owner:** Jambo
**Scope:** one app for Android phones, tablets and Android TV, keeping the
Streamit design exactly, adding offline downloads that never hand the viewer a
playable file
**Decisions this plan proposes:** [ADR-0002](../adr/0002-mobile-and-tv-app-stack.md)
(app stack), [ADR-0003](../adr/0003-offline-content-protection.md) (content
protection), [ADR-0004](../adr/0004-app-distribution-and-billing.md)
(distribution and billing)

Status legend, as in `frontend-wiring.md`: `shipped` · `in progress` · `next` ·
`deferred` · `todo`. Everything in this document is `todo` unless marked.

---

## 0. The answer in one page

**What gets built.** One Expo (React Native) application from one codebase,
built twice: a phone/tablet build and an Android TV build via
`react-native-tvos`. It reproduces the Streamit OTT design from the live site's
own tokens and rails. It talks to a new versioned API (`/api/v1`) added to the
existing Laravel application, inside the module `routes/api.php` files that are
already scaffolded and empty. Nothing vendor-owned is touched.

**How offline works without giving anyone the MP4.** The app downloads the
same progressive MP4 the website streams today (via the same entitlement check
and the same token-signed Bunny URL) into an **encrypted, app-private Media3
cache**. There is no file a viewer can find, share over Xender or WhatsApp, or
open in another player: the bytes live under the app's internal storage,
encrypted with a key held in the Android Keystore, excluded from backup, and
readable only by the app's own player. Every download carries a **server-issued
licence** (expiry, device binding, subscription-bound) that the app enforces
offline and the server can revoke online. Watching offline still counts for
partner earnings through a batched, fraud-bounded ingest that reuses the rules in
`WatchAccrualService`.

**What this deliberately does not need.** No transcoding, no HLS, no DRM
vendor, no new server. The current pipeline (MP4 on Backblaze B2 behind a
token-signed Bunny pull zone, no byte through the VPS) is sufficient for v1.
HLS (adaptive bitrate, quality choice) and Widevine (hardware DRM) are designed
as additive later steps on the same download engine (§11).

**Sequence and size.** Six phases, ~70–86 working days of focused solo work
(§7). Phases 0–1 are backend-only and low-risk to production; they can be
interleaved with client work. Phases 2–4 (the app) need contiguous focus.

**What Rio decides before code starts** (§3, §10):

1. App stack: Expo + one Kotlin media module (recommended) — ADR-0002.
2. Protection level: encrypted private cache now, Widevine later on trigger
   (recommended) — ADR-0003.
3. Play Store model: consumption-only Play build + direct APK with in-app
   PesaPal (recommended) — ADR-0004.
4. Precondition: Bunny **Token Authentication ON** for the pull zone (open
   question 9 in `D:\OS\jambo-os\index.md`). Downloads must not ship on
   unsigned URLs.
5. Precondition: Streamit licence tier (open question 1). Unrelated to the app
   technically; blocks taking money through it.

---

## 1. Where Jambo is today — what the plan builds on

Read from the code on 2026-09-07 at v1.8.12. `asserted`: read, not executed.

| Capability | State today | Where |
|---|---|---|
| Mobile client | **None.** No APK, no Expo or Flutter project in this repo or anywhere under `D:\xampp\htdocs`. The PWA (`/manifest.webmanifest`, `public/sw.js`) is a home-screen wrapper with push; it cannot store protected video and has no Play or TV presence | `routes/web.php:50`, `public/sw.js` |
| API | **Scaffold only.** Every module's `routes/api.php` has one stub returning `$request->user()` under `auth:sanctum`. `Monetization` says "a token API can hang here later if a mobile partner app ships". Sanctum ^3.3 installed, `expiration => null`, `api` group = `throttle:api` + `localization` | `Modules/*/routes/api.php`, `config/sanctum.php`, `app/Http/Kernel.php:73` |
| Entitlement | `TierGate` middleware and `FrontendController::userCanWatch()` are **two hand-kept copies** of the same rules (tier slug → access level, episode inherits show tier, `require_signup_to_watch`, admin bypass). The comment in `TierGate` says "same lookup as userCanWatch() so the two can't drift apart" — by discipline, not by code | `Modules/Streaming/app/Http/Middleware/TierGate.php`, `Modules/Frontend/app/Http/Controllers/FrontendController.php` |
| Delivery | `/watch/src/{movie|episode}[/low]` → auth + tier gate → 302 to `CdnUrlResolver` output: B2 URL rewritten to the Bunny host and **token-signed, TTL 28 800 s** (8 h). `Cache-Control: private, max-age=300` | `StreamProxyController.php`, `CdnUrlResolver.php`, `config/config.php` |
| Renditions | Two fixed files per title: `video_url` and `video_url_low` ("Data Saver"). Progressive MP4/WebM. Some titles are YouTube embeds (`HasStreamSource` type `youtube`) | `Modules/Content/app/Models/Concerns/HasStreamSource.php` |
| Transcoding | **Removed in 1.4.0** — on-server ffmpeg HLS was "unreliable mid-stream" on the KVM 2. Rule: upload browser-playable MP4/WebM only | `2026_04_28_200000_drop_transcode_columns_*.php` |
| Heartbeat | `POST /api/v1/streaming/heartbeat` is a **web-session** route (CSRF + session cookie), every 15 s. Writes `watch_history`, `active_streams`, fires `PlaybackBeat` | `Modules/Streaming/routes/web.php:52`, `StreamingController::heartbeat()` |
| Concurrency | `active_streams` keyed on (user, **Laravel session id**, title); `TierGate` counts other sessions against `subscription_tiers.max_concurrent_streams`. `EnforceDeviceLimit` counts rows in the `sessions` table (needs `SESSION_DRIVER=database`) and **skips `api/*`** | `ActiveStream.php`, `EnforceDeviceLimit.php` |
| Partner money | `WatchAccrualService`: runtime from the content row, ≤45 s credit per beat bounded by wall clock, paused earns nothing, 70 % completion tops up, paid sub required, self-farming denied, daily cap, unique key on `qualified_views` | `Modules/Monetization/app/Services/WatchAccrualService.php` |
| Subscriptions | Tiers with `access_level`, `max_concurrent_streams`, `price` (decimal, UGX), periods daily/weekly/monthly/yearly. Activation from `payment.completed` | `Modules/Subscriptions/` |
| Payments | PesaPal v3 hosted checkout (redirect) + IPN re-poll | `Modules/Payments/app/Services/PesapalGateway.php`, `docs/modules/pesapal-integration.md` |
| Design | Streamit dark skin. Live skin: primary `#1A98FF`, background `#0b0d17` (manifest), body text `#D1D0CF`, Roboto 300–900, Phosphor icons. The built bundle carries four Streamit skins; the live one is what the browser renders, so tokens are exported from the rendered site, not from SCSS | `routes/web.php:66`, `public/frontend/css/jambo-header.css`, `Modules/Frontend/resources/assets/sass/streamit-design-system/_variables.scss` |
| Home rails | Composed server-side in `SectionDataComposer` (hero, Top 10 movies of the day, Top 10 series, Continue Watching, Top Picks, Smart Shuffle, Upcoming, Specials, Exclusives, genres, VJs, personalities, category rails). Cached; flushed by `CatalogCacheObserver` | `Modules/Frontend/app/View/Composers/SectionDataComposer.php` |
| Hosting | One Hostinger KVM 2, nginx, MariaDB, queue drained by the scheduler every minute, `CACHE_DRIVER=file` | `docs/deploy/hostinger-vps.md`, `app/Console/Kernel.php` |
| Tests | 20 feature/unit test files across modules (PHPUnit) | `Modules/*/tests` |
| Estate reference apps | **Kugawana** (`Kugawana/KugawanaApp`): Expo 57, expo-router, EAS profiles that ship APKs, PesaPal top-up polling. **Kangaru** (`Kangaru/mobile`): Expo 57, TanStack Query with persistence, `expo-sqlite`, Sentry, an `offline/` folder, the background-push pipeline | `D:\OS\references\standard-stack.md`, `reusable-features.md` |

Three consequences for the plan:

- **The API is the first real work, and it is all Rio's code.** The stubs
  live in module route files the template never touches, so the ground rules
  in `jambo-setup.md` are respected by construction.
- **The entitlement and heartbeat logic must be extracted into services**
  before a third copy appears. This is the "second time" rule from `CLAUDE.md`:
  two copies is a pattern, and the API would be the third.
- **Offline can ship on the current MP4 pipeline.** Media3's progressive
  downloader handles exactly this shape. HLS is not a prerequisite.

---

## 2. Requirements, restated

Rio's words: *"works well on both android tvs, phone and tablets … keeping the
exact ui design … offline mode where clients can download movies or series and
watch them over offline … we don't want to give them the file like the mp4,
they have to watch it from the app."*

**Functional**

- R1. Phone, tablet and Android TV from one codebase; TV navigable with a
  remote (D-pad), listed in the Play Store TV catalogue.
- R2. The Streamit look: same colours, type, rails, cards, hero, detail and
  watch page structure as the website, adapted to touch and to the 10-foot UI.
- R3. Everything the site does for a viewer: browse, search, VJ pages, genres,
  categories, detail pages, trailers, watch with resume, watchlist, continue
  watching, ratings/reviews/comments, profile, plan status, devices,
  notifications.
- R4. Download a movie, an episode or a whole season; manage downloads
  (progress, pause, resume, delete, storage used, expiry); watch with no
  connectivity; the app opens usefully offline.

**Protection**

- R5. No playable file is ever exposed: not in any file manager, not via USB or
  adb backup, not shareable, not playable by another app.
- R6. Downloads are entitlements, not copies: bound to the account, the device
  and the subscription; they expire; they can be revoked; they obey per-tier
  limits; a title can be marked non-downloadable.

**Business**

- R7. Partner earnings remain honest: offline viewing credits minutes under the
  same fraud posture as online, or not at all, by a setting.
- R8. Google Play policy compliance for a subscription-content app in Uganda
  (§3.3).
- R9. Nothing in the app depends on the Streamit template's update path.

**Non-functional**

- R10. Works on entry-level Android (2–3 GB RAM, Android 10+) on MTN/Airtel
  data: small bundle, low-rendition default on cellular, resumable downloads.
- R11. Observable: crash reporting, download/playback failure analytics.

---

## 3. Decisions and recommendations

Each is written up as an ADR in `docs/adr/` with status **Proposed**. Rio
accepts, amends or rejects; the ADR then records the ruling.

### 3.1 App stack — Expo single codebase + one Kotlin media module (ADR-0002)

**Recommendation.** Expo (current SDK, matching Kangaru/Kugawana) with
`react-native-tvos` and `@react-native-tvos/config-tv`, so one project builds
for phone/tablet and for Android TV by toggling `EXPO_TV=1` in EAS profiles.
The UI, navigation, state, auth, API client and design tokens are React
Native. **One custom Expo Module in Kotlin, `expo-jambo-media`, owns the
player and the download engine** on Android Media3 (ExoPlayer): a native
`VideoView`, a `DownloadService`/`DownloadManager`, the encrypted
`SimpleCache`, and licence storage. That module is where R5 is enforced and it
is the only native code in the app.

**Why.** It is the standard stack (`D:\OS\references\standard-stack.md`), so
the auth provider, API client, query persistence, push and EAS release pipeline
are copied from Kangaru and Kugawana rather than rebuilt. One codebase covers
TV and mobile, which is exactly the shape Expo documents for TV. Expo Updates
lets UI fixes ship without a Play review. And the hard part — protected offline
media — has to be native anyway; in every option below it is the same ~1,500
lines of Kotlin, so writing it as a module costs nothing extra and makes it a
reusable estate asset.

**Alternatives considered.**

| Option | Verdict | Why |
|---|---|---|
| Native Kotlin app (Compose + Compose for TV) | Rejected | Departs from the stack; two UI codebases (mobile, TV) or one Compose codebase nobody else in the estate can maintain; no OTA updates; none of the Kangaru/Kugawana app code reusable |
| Iqonic's Streamit Flutter app + TV add-on | Rejected | Flutter, so off-stack; it is built for Iqonic's own API contract, not this codebase's models (VJs, splits, tiers); its "exact UI" is Iqonic's app design, not Jambo's website; its offline feature is theirs, opaque, and not R5/R6 as specified. Buying it would mean rewriting most of it |
| PWA only (extend the current one) | Rejected | Cannot satisfy R1 (Play TV listing, D-pad), R5 (browser storage is not protected and is evictable), and Android TV browsers are not a distribution channel |
| react-native-video + The Widlarz Group "Offline Video SDK" (commercial) | **Fallback**, not first choice | Solves offline + Widevine for RN, supports Expo, priced per impression or per year on request. Worth a trial in Phase 0 as the time-box escape hatch. Not chosen first because v1 does not need Widevine, per-impression pricing does not fit a fixed-cost solo operation, and the encrypted-cache design below is not what it sells |
| expo-video as the player | Rejected for v1 | No DRM path, no cache injection, so the downloaded bytes could not be played from the protected cache |

**Android only in v1.** iOS needs `AVAssetDownloadTask` + FairPlay and a Mac
build pipeline; nothing in the ask requires it. The module is designed with an
iOS slot (§11).

**Where it lives.** `mobile/` in this repository, as Kangaru does. The
production deploy is `git pull` on the VPS and `mobile/` has no effect on it.

### 3.2 Content protection — encrypted private cache now, Widevine on trigger (ADR-0003)

The requirement is R5/R6. The honest way to choose a level is by the threat it
must stop.

| Threat | Who | Stopped by |
|---|---|---|
| T1. Viewer finds the video in a file manager and shares it (Xender, SHAREit, WhatsApp, Bluetooth, USB) — **the real East African channel** | Any user | App-internal storage (`filesDir`), no export path, `allowBackup=false` |
| T2. Viewer with adb or a rooted phone copies the app's private directory | Technical user | Encryption at rest with a per-install key wrapped by the Android Keystore; the copied bytes are ciphertext |
| T3. Screen recording / casting | Any user | `FLAG_SECURE` on the player window (blocks capture on unrooted devices and TVs); nothing stops a camera pointed at a screen, for anyone |
| T4. Rooted device with runtime hooking to dump the decrypted stream or the key | Determined attacker | Only hardware DRM (Widevine L1) with attestation. Even then L3 fallbacks leak; this is the level Netflix and Showmax buy from Google |

**Recommendation: Level 1 = T1–T3 now.** It satisfies the requirement as
stated ("they have to watch it from the app"), works on the current MP4
pipeline, has zero per-licence cost, and ships months earlier. **Level 2 =
Widevine** is added when one of these triggers fires: a distributor or partner
contract demands studio-grade DRM; evidence of T4-class extraction of Jambo
titles; or the catalogue moves to HLS/DASH for other reasons (§11), at which
point Widevine is a packaging flag plus a licence vendor.

**Level 1 mechanism (Android, Media3).**

1. `DownloadService` + `DownloadManager` write into a `SimpleCache` rooted at
   `context.filesDir/media`. Internal storage is invisible to the user and to
   other apps without root.
2. Cache writes and reads go through Media3's AES-CTR cipher sink/source
   (`AesCipherDataSink` / `AesCipherDataSource`) with a 16-byte secret generated
   at first run and stored wrapped by an `AndroidKeyStore` AES-GCM key. *(Verify
   the class names against the Media3 version chosen in Phase 0; if they have
   moved, the same effect is a custom `DataSink`/`DataSource` pair over
   `javax.crypto` CTR mode — half a day.)*
3. `android:allowBackup="false"` and `dataExtractionRules` excluding the media
   directory; `FLAG_SECURE` on the player activity.
4. The download's `MediaItem.uri` is an app URI (`jambo://download/{id}`)
   resolved at open time by a `ResolvingDataSource` that asks the API for a
   fresh signed CDN URL. The 8 h Bunny token TTL therefore never fails a
   long, paused, resumed download, and no signed URL is persisted.
5. Playback of a download uses the same `CacheDataSource` chain, so the player
   reads ciphertext through the cipher source and never materialises a file.
6. Licence data (see §4.3) is stored in the module's private SQLite,
   integrity-protected with an HMAC under the same Keystore key, and validated
   before every offline play.

**What Level 1 does not claim.** It does not stop a rooted, instrumented
device (T4), and the plan says so in the ADR so nobody sells it as DRM.

### 3.3 Distribution and billing — consumption-only on Play, in-app PesaPal on the direct APK (ADR-0004)

**The policy fact (checked 2026-09-07).** Google Play's Payments policy
requires Google Play Billing for subscription content (video named explicitly)
everywhere except India, South Korea, the EEA and the US; outside those,
developers "may not lead users to a payment method other than Google Play's
billing system". A **consumption-only** app — sign in, watch what you bought
elsewhere, buy nothing in the app — is explicitly allowed. Uganda has no
exception. Separately, whether a Ugandan Play developer account can register as
a merchant for Play Billing at all must be verified in the Play Console before
relying on it (historically it could not).

**Recommendation.** Two build variants of the same app, switched by an
environment flag:

- **`play`** — published on Google Play (phone, tablet, TV). Consumption-only:
  no plan purchase, no checkout, no link to a payment page. Shows the current
  plan and renewal date; a neutral sentence that subscriptions are managed on
  the account's website. This is the Netflix/Spotify pattern and passes review.
- **`direct`** — the APK offered for download on jambofilms.com (the Kugawana
  EAS `preview` profile already produces exactly this). Includes in-app
  **Subscribe** with PesaPal hosted checkout in a Custom Tab, deep-link return
  (`jambo://payment/return`) and status polling — the Kugawana wallet top-up
  pattern. Mobile money is the audience's payment method; this is where it
  lives.

Play Billing is deliberately not integrated in v1: 15–30 % fee, merchant
eligibility unknown, and a second subscription source of truth to reconcile
with `user_subscriptions`. Revisit if Play's 2026 billing changes reach Uganda
or if Play distribution becomes the majority channel.

### 3.4 Offline viewing and partner money

`WatchAccrualService` turns live 15-second beats into "auditable earning
facts" with a wall-clock bound per beat. Offline, the server sees nothing until
the device reconnects. The rule set that keeps R7 true:

- The app records **offline sessions** locally: session UUID, download id,
  start and end (device wall clock **and** monotonic `elapsedRealtime`),
  and a position sample every 15 s.
- On reconnect it uploads them in a batch; the server ingests through
  `OfflineWatchIngestService`, which reuses `WatchAccrualService::credit()`
  with these bounds: runtime from the content row; credited seconds ≤ min(sum
  of monotone position deltas, monotonic elapsed time, runtime); a
  position jump larger than elapsed time + slack is clipped; the download's
  licence must have been valid at the session time; a subscription must have
  covered the session date (by dates, not by status, because
  `ExpireSubscriptionsCommand` rewrites status); the daily cap applies on the
  session date; unique on session UUID, so replays are no-ops.
- Sessions uploaded more than 7 days after they ended, or for a month
  settlement has already closed, are stored for stats and **not** credited.
- `qualified_views.source` (`online` | `offline`) so partner dashboards split
  the two, and a new setting `monetization.offline_minutes_earn` (default
  **off** until the first month of data has been read) so offline minutes can
  be watched before they are paid.

---

## 4. Architecture

### 4.1 Shape

```
┌──────────────────────────────┐        ┌──────────────────────────────────────┐
│  Jambo app (Expo, RN)        │        │  Laravel (existing VPS)              │
│  phone · tablet · Android TV │        │                                      │
│                              │ HTTPS  │  /api/v1  (new, Sanctum tokens)      │
│  UI · nav · auth · queries ──┼───────►│   auth/devices · catalogue · playback│
│  design tokens (Streamit)    │        │   downloads/licences · offline-beats │
│                              │        │                                      │
│  expo-jambo-media (Kotlin)   │        │  PlaybackAuthorizer (one entitlement │
│   VideoView (Media3)         │        │   service; TierGate + web + API)     │
│   DownloadManager            │        │  CdnUrlResolver (unchanged)          │
│   encrypted SimpleCache      │        │  WatchAccrualService (unchanged)     │
│   licence vault (SQLite+HMAC)│        │  OfflineWatchIngestService (new)     │
└──────────────┬───────────────┘        └───────────────┬──────────────────────┘
               │ GET signed URL (302 target, ≤8 h)       │ 302 / URL issue
               ▼                                         ▼
        ┌───────────────────────── Bunny pull zone (token auth ON) ────────────┐
        │                 origin: Backblaze B2 (MP4, two renditions)           │
        └──────────────────────────────────────────────────────────────────────┘
```

No video byte touches the VPS, as today. The app adds one hop the website
does not have: it asks the API for the resolved URL instead of following a 302,
so the URL never appears in a WebView or a log.

### 4.2 Backend additions (all Rio's code, in existing modules)

| Change | Module | Notes |
|---|---|---|
| `PlaybackAuthorizer` service: one tri-state answer (`allowed` / `login_required` / `subscription_required(tier)` / `over_cap` / `unavailable`) | Streaming | Replaces the duplicated logic in `TierGate` and `FrontendController::userCanWatch()`; both call it. API calls it too. Ships with tests that pin current behaviour before the refactor |
| `PlaybackBeatRecorder` service | Streaming | The body of `StreamingController::heartbeat()` minus the HTTP; web and API controllers both call it. `session_id` becomes "a client session key": Laravel session id for web, device UUID for the app |
| `devices` table + `Device` model | Streaming | `uuid`, `user_id`, `platform` (`android`, `android_tv`), `model`, `app_version`, `name`, `last_seen_at`, `revoked_at`, `personal_access_token_id`. The web device picker (`streams.limit`) lists devices too; "boot" revokes the token |
| Concurrency across web + app | Streaming | `EnforceDeviceLimit` counts web sessions **plus** devices seen within `session.lifetime`; `ActiveStream` already counts distinct session keys, so app streams that heartbeat are capped today once the key is the device UUID |
| `offline_downloads` table | Streaming | See §4.3 |
| `allow_download` on movies and shows (episodes inherit), `download_size_bytes` on movies/episodes (HEAD on save, queued) | Content | Admin toggle on the movie and show forms; bulk action next to the existing bulk plan assignment. YouTube-sourced titles are never downloadable |
| Tier fields: `max_download_devices`, `max_active_downloads`, `download_expiry_days`, `download_play_window_hours`, `download_quality` (`low`, `any`) | Subscriptions | Admin tier form. Defaults: streams cap, 25, 30, 48, `any` |
| `qualified_views.source`, `watch_progress_monthly.source`; `offline_minutes_earn` setting; partner dashboard split | Monetization | §3.4 |
| `OfflineWatchIngestService` + `POST playback/offline-sessions` | Monetization + Streaming | §3.4 |
| `HomeRailsService` extracted from `SectionDataComposer::build()` | Frontend | The composer and `GET /api/v1/home` call the same service and share the same cache, so the app home is the website home by construction |
| API resources (allow-listed fields), error codes enum, OpenAPI spec | app/ + modules | `docs/api/openapi.yaml`; the app's TypeScript types are generated from it |
| Sanctum: per-device tokens named by device UUID; abilities `viewer`; revocation via devices; `throttle:auth` keyed `ip|email` (Kugawana pattern) | app/ | Tokens do not expire on a timer (a TV re-login is a real cost); they are revocable and their last use is visible in Devices |
| Device-code sign-in for TV (`POST auth/device/code`, `POST auth/device/token`, web page `/tv`) | app/ | RFC 8628 shape: the TV shows a short code, the viewer approves it on their phone or the site. No typing passwords with a remote |

### 4.3 Download licence — the data and the state machine

`offline_downloads` (server) mirrored by the licence vault (app):

| Column | Meaning |
|---|---|
| `id`, `uuid` | Licence id; the app's download id |
| `user_id`, `device_id` | Binding |
| `watchable_type`, `watchable_id`, `rendition` (`default`/`low`) | What |
| `status` | `issued` → `active` (bytes complete) → `played` (first play stamped) → `expired` / `revoked` / `deleted` |
| `issued_at`, `expires_at` | `expires_at = min(issued_at + tier.download_expiry_days, subscription.ends_at + 3 days)` — a day pass gets day-pass downloads |
| `first_played_at`, `play_expires_at` | `first_played_at + tier.download_play_window_hours` (Netflix's 48 h rule) |
| `renewals`, `last_synced_at` | Renewal is online only and re-checks entitlement; unlimited while the subscription holds |
| `bytes_expected`, `bytes_done` | Reported by the app for the admin report |
| `signature` | HMAC-SHA256 over the licence fields with an `APP_KEY`-derived secret; the app stores it and re-verifies before offline play, so a tampered vault row fails closed |

Transitions the app enforces offline: play allowed iff `status ∈ {active,
played}` and `now < expires_at` and (`first_played_at` null or `now <
play_expires_at`). Transitions the server enforces online (on every sync):
subscription lapsed → `expired`; device revoked → `revoked`; title
un-downloadable or unpublished → `revoked`; over tier caps → new requests
refused with `DOWNLOAD_LIMIT` (code, not prose).

**Time.** The app anchors on the last server timestamp plus monotonic elapsed
time. A wall clock earlier than the last known server time is treated as
rollback: offline play is refused until the next online sync. This is the same
compromise every offline video app makes; it is written into the ADR.

### 4.4 Flows

**Download.** Detail page → "Download" (visible only if `allow_download`,
entitled, under caps, not YouTube) → `POST /downloads` → server checks
`PlaybackAuthorizer` + caps → returns licence + `jambo://download/{uuid}` +
`size_bytes` → app enqueues in `DownloadManager` (foreground service with
notification, Wi-Fi-only preference honoured, cellular default = `low`) →
`ResolvingDataSource` fetches a fresh signed URL per open → progress events to
JS → on completion `PATCH /downloads/{uuid}` with bytes → `active`.

**Offline play.** Downloads screen → play → vault check (§4.3) → `VideoView`
over the encrypted cache → offline session recorded every 15 s to SQLite →
first play stamps `first_played_at` locally (and on the server at next sync).

**Sync (any time the app is online).** `GET /downloads` returns server status
for every licence the device holds; the app reconciles (revoke → delete bytes);
`POST /playback/offline-sessions` drains the local session queue; watch
history positions merge so Continue Watching is the same on the website.

**Online play.** `POST /playback/sessions` (authorizer + cap) → signed URL +
resume position → heartbeat via `POST /playback/heartbeat` with the token —
same rules as the website, same `ActiveStream` cap.

---

## 5. API v1 — contract summary

Conventions from `~/.claude/skills/engineering-standards/` §API: `/api/v1`,
envelope `{success, message, data}` / `{success:false, code, message,
errors}`, codes in one enum, cursor pagination on lists, whitelisted
`?include=`, allow-listed resources, OpenAPI in `docs/api/openapi.yaml`
checked by a test.

| Area | Endpoints | Auth |
|---|---|---|
| Auth | `POST auth/login`, `auth/register`, `auth/google`, `auth/2fa/challenge`, `auth/forgot-password`, `auth/logout`; `POST auth/device/code`, `auth/device/token` (TV) | throttle `ip|email` |
| Devices | `GET devices`, `POST devices`, `DELETE devices/{uuid}` | token |
| Catalogue | `GET home` (rails, same composition as the site), `movies`, `movies/{slug}`, `series`, `series/{slug}` (seasons, episodes), `episodes/{id}`, `search?q=`, `search/suggest`, `genres`, `genres/{slug}`, `categories`, `categories/{slug}`, `vjs`, `vjs/{slug}`, `cast/{slug}`, `upcoming`, `collections/{rail}`, `pricing` | public (respects `require_signup_to_watch` for playback only) |
| Viewer | `GET me` (profile, plan, caps, settings), `PATCH me`, `GET/POST/DELETE watchlist`, `GET continue-watching`, `GET history`, `POST ratings`, `POST reviews`, `POST comments`, `GET notifications`, `POST notifications/read` | token |
| Playback | `POST playback/sessions`, `POST playback/heartbeat`, `POST playback/guest-view` | token / public |
| Downloads | `GET downloads/policy`, `GET downloads`, `POST downloads`, `PATCH downloads/{uuid}`, `POST downloads/{uuid}/renew`, `POST downloads/{uuid}/played`, `DELETE downloads/{uuid}`, `POST playback/offline-sessions` | token |
| Subscription (`direct` build only) | `GET subscription`, `POST subscription/checkout` (PesaPal redirect URL), `GET subscription/orders/{id}` (poll) | token |
| Ops | `GET app/config` (min app version, feature flags, server time) | public |

Error codes the app branches on (excerpt): `LOGIN_REQUIRED`,
`SUBSCRIPTION_REQUIRED`, `UPGRADE_REQUIRED`, `STREAM_LIMIT`, `DEVICE_REVOKED`,
`DOWNLOAD_NOT_ALLOWED`, `DOWNLOAD_LIMIT`, `DOWNLOAD_DEVICE_LIMIT`,
`LICENCE_EXPIRED`, `CONTENT_UNAVAILABLE`, `APP_UPDATE_REQUIRED`.

---

## 6. Keeping the Streamit design

"Exact" means the same visual system, honestly adapted to each form factor:
Streamit's own mobile breakpoints already define the phone layout; the desktop
layout is the TV layout with focus navigation.

### 6.1 Tokens, exported once and checked in twice

`design/tokens.json` in this repo, generated by a small script that reads the
rendered site's computed styles (the live skin), consumed by the app's theme.
The values known today:

| Token | Value | Source |
|---|---|---|
| `color.primary` | `#1A98FF` | manifest `theme_color`, `jambo-header.css` |
| `color.bg` | `#0b0d17` | manifest `background_color` |
| `color.text` | `#D1D0CF` | Streamit dark body colour |
| `color.success` / `warning` | `#14e788` / `#FFD81C` | Streamit skin |
| `font.family` | Roboto 300/400/500/700/900 | `master.blade.php` Google Fonts link |
| `radius.sm/md/lg` | `.25em / .5em / 1em` | Streamit `_variables.scss` |
| icons | Phosphor (`phosphor-react-native`) | site-wide |
| rail rhythm | heading→cards 24 px, cards→next heading 60 px; detail pages `--jambo-rail-gap` | CHANGELOG 1.8.9/1.8.10 |
| poster grid | 8-across desktop standard (TV), 2–3 on phone per Streamit breakpoints | commit `6c033a3` |

### 6.2 Screen inventory

| App screen | Website source | Notes |
|---|---|---|
| Home | `/` (`frontend.ott`) via `HomeRailsService` | Hero slider, Top 10 Movies/Series of the day with numbered badges, Continue Watching with progress, Top Picks, Smart Shuffle, Upcoming, category rails, genres, VJs, personalities |
| Movies / Series | `/movie`, `/series` | Banner + rails + VJ rails; Load More = infinite list |
| Movie detail | `/movie-detail/{slug}` | 21:9 backdrop (1.8.9), trailer, CTA row: Play / Trailer / Watchlist / **Download** |
| Series detail | `/series/{slug}` | Episode grid/scroller toggle, per-episode **Download**, **Download season** |
| Watch / Episode | `/watch/{slug}`, `/episode/…` | Player + related rails; Data Saver toggle; next episode |
| Search | `/search` + suggest | Voice search on TV (Android TV search intent) |
| Genres, categories, VJ hub, VJ movies/series, cast | matching routes | Archive layouts as on site |
| Watchlist, Continue Watching, History | `/watchlist`, profile hub | |
| Profile & settings | `/{username}`, `/change-password`, 2FA | Plus: Devices, Downloads settings (quality, Wi-Fi only, storage), Notifications |
| **Downloads** (new) | — | Grouped by title/season; progress, size, "expires in 12 days", "48 h left once started"; storage meter; Delete all |
| **Offline home** (new) | — | When offline at launch: Downloads first, cached rails greyed, a bar "You're offline — showing your downloads" |
| Plans | `/pricing` | `play`: informational. `direct`: Subscribe → PesaPal |
| TV sign-in (TV only) | new `/tv` page on the site | Code on screen, approve on phone |

### 6.3 TV adaptations

- Leanback launcher intent and 320×180 banner (set by the TV config plugin).
- Focus-driven navigation: `TVFocusGuideView`, visible focus ring in the
  primary colour, rails as horizontal lists with focus memory.
- Remote keys in the player: play/pause, ±10 s, menu for quality/subtitles.
- Downloads **off by default on TV** (typical box storage is 8 GB); enabled
  by a setting for boxes with an SD card or USB storage.
- TV builds skip the `direct` subscribe flow (no checkout on a remote).

---

## 7. Phases

Estimates are focused working days for one developer who knows this codebase.
"Exit" is what must be true to close the phase.

### Phase 0 — Decisions and spike (5 days) `in progress`

- ADR-0002/0003/0004 accepted or amended.
- Preconditions: Bunny Token Authentication confirmed ON; Streamit licence
  tier answered; Play Console merchant eligibility checked.
- Spike, in a throwaway Expo project with a bare Kotlin module: download one
  Jambo MP4 from a signed URL into the encrypted cache on a phone and on an
  Android TV emulator; play it in aeroplane mode; confirm nothing is visible in
  a file manager and `adb backup` yields nothing; confirm `FLAG_SECURE` blocks
  a screen recording. Time-box: 3 days. If the cipher path costs more, run the
  TWG SDK trial in the remaining 2 days and decide.
- **Exit:** the spike video plays offline from ciphertext; ADRs Accepted.

### Phase 1 — API v1, backend refactors (12–15 days) `in progress`

- `shipped 2026-09-08` — `PlaybackAuthorizer` and `PlaybackBeatRecorder`
  extracted, with `PlaybackAuthorizationPinTest` written and green against
  the old code first. Three copies collapsed into one, two disagreements
  between them reconciled in TierGate's favour (R1, R2 — see CHANGELOG
  1.8.22 and the worklog). 337 tests green bar two pre-existing failures.
- `shipped 2026-09-08` — auth (login, two-call 2FA, logout, /me), the
  `devices` table with per-device Sanctum tokens, device list and boot,
  `/app/config`, the response envelope and the `ApiErrorCode` enum. Also
  fixed a pre-existing 500 on every `/api/*` route (a `localization`
  middleware deleted in `9c8c192` but still referenced by the `api`
  group). **Still todo in this bullet:** `auth/register`, `auth/google`,
  forgot-password, and device-code sign-in with the `/tv` page.
- `shipped 2026-09-08` — catalogue (movies, series, episodes, search,
  cursor-paginated, allow-listed resources) and playback (sessions,
  heartbeat) on the 1.8.22 services. `StreamSourceResolver` extracted so
  the API does not re-derive the rendition choice.
- `shipped 2026-09-08` — `HomeRailsService` extracted from
  `SectionDataComposer::build()` (437 lines to 87) with `HomeRailsPinTest`
  green before and after, and `GET /home` calling it, so the app home is
  the website home by construction. Section headings resolve from the same
  `sectionTitle` keys the blades use.
- `shipped 2026-09-08` — genre, category, VJ and cast archive screens, plus
  watchlist (idempotent add/remove rather than the web's toggle),
  continue-watching and history. Entirely additive; no webapp file touched.
  **Still todo:** ratings, reviews, comments and notifications.
- `shipped 2026-09-08` — `docs/api/openapi.yaml` plus `OpenApiSpecTest`,
  which fails when a route is undocumented, when the spec describes a
  route that does not exist, or when an error code is missing from its
  enum. **Still todo:** generating the app's TypeScript types from it.
- Deploy: additive migrations, no change to web behaviour. Verify the
  website's watch, heartbeat and device picker after the refactor.
- **Exit:** a Postman/Bruno collection walks login → home → play session →
  heartbeat on production; web regression tests green.

### Phase 2 — The app, online (18–22 days) `todo`

- `mobile/` bootstrapped from Kangaru's structure (`api/`, `auth/`,
  `navigation/`, `ui/`), Kugawana's EAS profiles, Sentry.
- Tokens exported; UI kit: rail, poster card, Top 10 card, hero, CTA row,
  episode grid, focus ring.
- All screens in §6.2 except Downloads; player via `expo-jambo-media` (online
  path only) with heartbeat, resume, Data Saver, next episode.
- `play` / `direct` variant flag; `direct` subscribe flow.
- **Exit:** a real subscriber account can do everything the website does on a
  Tecno-class phone and a 10" tablet; APK from EAS `preview`.

### Phase 3 — Offline (18–22 days) `todo`

- `expo-jambo-media` download engine + encrypted cache + licence vault.
- Backend: `offline_downloads`, tier caps, `allow_download`, sizes, licence
  endpoints, sync, `OfflineWatchIngestService`, `source` split, setting,
  admin toggles and a Downloads report.
- App: Download CTA and states, Downloads screen, offline home, session
  recorder and sync, storage meter, Wi-Fi-only, quality preference.
- **Exit:** the acceptance script in §7.1 passes on a phone with the SIM
  removed; an offline session shows up on the partner dashboard as offline
  minutes; a revoked device loses its downloads at next launch.

### Phase 4 — Android TV (10–14 days) `todo`

- `EXPO_TV=1` EAS profile; leanback, banner, focus navigation, remote keys,
  device-code sign-in end to end, TV search intent.
- Downloads gated by the TV setting.
- **Exit:** the TV build passes Google's TV app quality checklist on a real
  Android TV box; a viewer signs in with the code flow and watches a premium
  title.

### Phase 5 — Release (5–8 days) `todo`

- Play Console: internal → closed testing → production for phone/tablet and
  TV (consumption-only build). Direct APK on the site with an install page.
- `GET app/config` min-version gate; Expo Updates channel per variant.
- Docs: `docs/modules/mobile.md`, `docs/api/`, deploy runbook additions,
  CHANGELOG entries in the house style (why, not just what).
- **Exit:** both channels live; crash-free sessions ≥ 99 % over the first two
  weeks of closed testing.

**Total: ~70–86 focused days.** At the capacity `D:\OS\context\priorities.md`
describes (one person, many workstreams, Priority 1 still open) that is four to
six calendar months. Phases 0–1 fit between client work; Phases 2–4 do not.

### 7.1 Offline acceptance script (Phase 3 exit)

1. Subscribe (Premium), download a premium movie on Wi-Fi at full quality and
   an episode on cellular (must default to `low`).
2. Remove the SIM, disable Wi-Fi, kill the app, relaunch: Downloads first,
   both titles play, resume works, progress bar updates.
3. Connect a USB cable and browse the device from a PC; open every file
   manager on the phone; run `adb backup`: no video file exists anywhere.
4. Screen-record during playback: black frames.
5. Set the phone clock back a week while offline: playback refused with the
   "check your connection" message; go online: playback restored.
6. Let the subscription lapse (admin sets `ends_at` in the past); sync:
   downloads expire; renew subscription; renew downloads without re-downloading
   bytes.
7. Boot the device from the website's device picker; relaunch: token revoked,
   bytes gone.
8. Partner dashboard: the offline minutes appear with `source = offline` and
   do not pay while the setting is off; turn it on; next month's settlement
   includes them.

---

## 8. Costs

Approximate, as of 2026-09-07; verify each in the vendor dashboard before
budgeting.

| Item | One-off | Monthly | Note |
|---|---|---|---|
| Bunny egress for downloads | — | $0.005/GB (Volume tier) **or** ~$0.06/GB (Standard tier, Africa) | A 1.5 GB download costs $0.0075 or $0.09. 1,000 downloads/month: $7.50 or $90. **Check which tier the pull zone is on**; Africa on Standard is 12× Volume |
| Google Play developer account | $25 | — | |
| EAS Build | — | $0–19 | Free tier has a monthly build quota; Kugawana already uses EAS |
| Sentry | — | $0 | Free tier |
| Test hardware | ~$100–150 | — | One Android TV box, one entry-level Android phone (the audience's device) |
| **Later — Bunny Stream** (HLS, ABR) | — | $0.01/GB storage, $0.005/GB delivery, encoding free | §11 |
| **Later — Widevine** | — | Bunny MediaCage Enterprise reported at $99/mo + $0.005/licence (sales-activated, offline support undocumented); Axinom pay-as-you-go with a 60-day trial; EZDRM documents persistent (offline) licences | §11. Offline persistent licences must be confirmed with the vendor in writing |
| **Fallback — TWG Offline Video SDK** | — | on request (per impression or per year) | Only if the Phase 0 spike fails its time-box |

No new server. The KVM 2 carries API JSON, not video.

---

## 9. Risks

| Risk | Likelihood | Mitigation |
|---|---|---|
| Solo capacity: Priority 1 (B&D, Cert Perts) still open; this competes | High | Phases 0–1 are backend, small and interleavable. Do not start Phase 2 until Priority 1 is invoiced or Rio decides otherwise and logs it |
| Bunny token auth is OFF in the dashboard (config says signed URLs are only safe when it is ON) | Unknown | Precondition; one dashboard look. Downloads never ship on unsigned URLs |
| Media3 cipher API names differ from this plan | Medium | Spike; custom sink/source fallback is half a day |
| Play merchant registration unavailable in Uganda | Medium | ADR-0004 does not depend on it; `direct` carries payments |
| Play review flags the consumption-only build for mentioning the website | Low | Copy reviewed against the policy wording; no URL, no CTA |
| Users on entry-level phones run out of storage | High | Storage meter, `low` default on cellular, per-device cap, oldest-expired auto-clean |
| Offline minutes gamed (clock, replays) | Medium | §3.4 bounds; `offline_minutes_earn` off by default; partner-visible split |
| Downloaded titles later found to be YouTube embeds or unpublished | Low | Never downloadable; revoke on unpublish |
| Template update overwrites something | Low | The app and API are entirely outside vendor files; the `/tv` page uses a Streamit page template through the normal channel |
| Data cost to viewers of re-downloading after expiry | Medium | Renewal keeps bytes; only the licence refreshes |
| Two entitlement copies drift during the refactor | Medium | Pin tests first; both callers switch in one commit |

---

## 10. Preconditions and questions for Rio

1. **Bunny Token Authentication ON?** (`jambo-os/index.md` open question 9.)
2. **Streamit licence tier?** (open question 1.) Independent of the app;
   blocks money.
3. **Which Bunny tier is the pull zone on** (Volume vs Standard)? Sets the
   egress line in §8.
4. **Play Console merchant eligibility** for the Jambo developer account.
5. **Accept, amend or reject ADR-0002, 0003, 0004.**
6. **Defaults to confirm:** 30-day download expiry, 48 h play window, 25
   downloads per device, download devices = streams cap, `low` on cellular,
   downloads off on TV, `offline_minutes_earn` off at launch.
7. **Does Priority 1 close before Phase 2 starts?** The plan assumes yes.

---

## 11. Deliberately out of scope, and when it comes back

| Not in v1 | Why | Trigger to add |
|---|---|---|
| HLS / adaptive bitrate via Bunny Stream (managed transcoding) | Not needed for offline; a separate improvement recorded in `D:\OS\references\video-streaming.md` | When viewer complaints on MTN data or a quality picker are wanted. The download engine already speaks HLS (Media3 `HlsDownloader`); the API's `rendition` becomes a variant selection |
| Widevine (Level 2) | §3.2 | Distributor demand, evidence of T4 extraction, or the HLS/DASH move |
| iOS / tvOS | Not asked; needs a Mac pipeline and FairPlay | When the audience data shows iOS share worth it |
| Google Play Billing | §3.3 | Uganda gains an alternative-billing programme, or Play becomes the main channel |
| Chromecast / AirPlay | Cast of protected downloads is a DRM problem; cast of online streams is easy | After v1, online only |
| Multiple profiles per account, kids mode | Not in the website's model | When the website gets it |
| Live TV / events | Not in the catalogue model | — |
| A partner mobile app | `Monetization/routes/api.php` reserves the slot | When partners ask |

---

## Appendix A — Files this plan will create or change

```
docs/plans/mobile-offline-app.md            this plan
docs/adr/0002-mobile-and-tv-app-stack.md    proposed
docs/adr/0003-offline-content-protection.md proposed
docs/adr/0004-app-distribution-and-billing.md proposed
docs/api/openapi.yaml                       Phase 1
docs/modules/mobile.md                      Phase 5
design/tokens.json                          Phase 2
mobile/                                     Phase 2 (Expo app; expo-jambo-media in mobile/modules/)
Modules/Streaming/app/Services/PlaybackAuthorizer.php        Phase 1
Modules/Streaming/app/Services/PlaybackBeatRecorder.php      Phase 1
Modules/Streaming/app/Models/{Device,OfflineDownload}.php    Phase 1 / 3
Modules/Monetization/app/Services/OfflineWatchIngestService.php  Phase 3
Modules/Frontend/app/Services/HomeRailsService.php           Phase 1
Modules/*/routes/api.php                    Phase 1 onward (replacing the stubs)
```

## Appendix B — Sources consulted (2026-09-07)

- Expo, *Build Expo apps for TV* — single project targets mobile and TV via
  `react-native-tvos` and `EXPO_TV`; Video, Image, Reanimated, SQLite,
  Updates supported on TV. https://docs.expo.dev/guides/building-for-tv/
- react-native-tvos wiki and `@react-native-tvos/config-tv`.
  https://github.com/react-native-tvos/react-native-tvos/wiki
- Google Play, *Understanding Google Play's Payments policy* — subscription
  content must use Play Billing outside India, South Korea, EEA, US;
  consumption-only apps allowed.
  https://support.google.com/googleplay/android-developer/answer/10281818
- Bunny, *MediaCage Enterprise DRM* (Widevine + FairPlay, sales-activated;
  offline not documented) and Stream pricing.
  https://bunny.net/docs/stream-mediacage-enterprise-drm ·
  https://bunny.net/pricing/stream/
- The Widlarz Group, *Offline Video SDK* (react-native-video add-on; Widevine
  persistent licences; DRM on Android requires DASH).
  https://www.thewidlarzgroup.com/offline-video-sdk
- EZDRM universal licence delivery (persistent licences); Axinom DRM pricing
  (pay-as-you-go, 60-day evaluation).
- Estate: `D:\OS\references\video-streaming.md`, `standard-stack.md`,
  `reusable-features.md`, `rate-limiting.md`, `background-push.md`;
  `D:\OS\jambo-os\`.
