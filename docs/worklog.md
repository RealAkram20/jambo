# Worklog

Append-only record of work in this repository. Newest at the bottom.

Read it before touching anything. Add your entry **before** you write code.
Release notes with the *why* of each change stay in `CHANGELOG.md`; this file
holds session state, claims, gotchas and what was deliberately not built.

## The rules

1. Claim your files before you edit them.
2. Re-read the tree before you write; `git status` from an hour ago is stale.
3. Never re-implement a shared module. Extend it.
4. Say what you did not build.

## Entries

### 2026-09-07 — Mobile, TV and offline app: plan and proposed ADRs

**Status:** complete (planning only; no application code)
**Owns:** docs/plans/mobile-offline-app.md, docs/adr/0002-mobile-and-tv-app-stack.md,
docs/adr/0003-offline-content-protection.md, docs/adr/0004-app-distribution-and-billing.md,
docs/worklog.md (created)
**Shares:** none

**What this is:** the plan for one Expo app (phone, tablet, Android TV) that
keeps the Streamit design and adds offline downloads that never expose a
playable file, plus the API and backend changes it needs. Three decisions are
written as Proposed ADRs for Rio. Facts about the codebase were read from the
code at v1.8.12; external facts (Expo TV, Play policy, Bunny DRM, DRM
vendors) were read from the web the same day and are cited in the plan.

**Verified:** nothing executed. Codebase facts are `asserted` (read, not run):
no API beyond stubs, heartbeat is web-session, concurrency keyed on Laravel
session id, entitlement logic duplicated in TierGate and userCanWatch, MP4
two-rendition delivery via signed Bunny 302, no mobile client anywhere on the
machine.

**Not verified:** the Media3 cipher sink/source class names in the current
release; whether Bunny MediaCage Enterprise issues offline licences; whether
the Play developer account can register as a merchant from Uganda; which
Bunny pricing tier the pull zone is on; whether Bunny Token Authentication is
ON. Each is listed as a precondition or spike item in the plan.

**Deliberately not built:**
- No code, migrations, API routes or Expo project — the plan needs Rio's
  three decisions first (ADR-0002/3/4), and Phase 0 is a spike.
- No OpenAPI spec yet — Phase 1 deliverable.
- No wiki-side cost commitments — every price is marked approximate.

**For whoever is next:** start with `docs/plans/mobile-offline-app.md` §0 and
§10. Phase 0 is a 5-day spike; the `PlaybackAuthorizer` extraction in Phase 1
must land with behaviour-pinning tests before any API controller calls it.
The Streamit skin in the built CSS bundle carries four `--bs-primary` values;
the live one is `#1a98ff` (manifest `theme_color`), so export tokens from the
rendered site, not from SCSS.

### 2026-09-08 — Featured hero: admin-picked, drag-ordered homepage banner

**Status:** complete (shipped as 1.8.19)
**Owns:** Modules/Content/database/migrations/*_create_featured_items_table.php,
Modules/Content/app/Models/FeaturedItem.php,
Modules/Content/app/Http/Controllers/Admin/FeaturedController.php,
Modules/Content/resources/views/admin/featured/index.blade.php,
Modules/Content/tests/Feature/FeaturedHeroTest.php
**Shares:** Modules/Content/routes/web.php — exact edit: one route group block for
featured index/store/destroy/reorder;
resources/views/components/partials/vertical-nav.blade.php — exact edit: one
nav-item between Categories and Vjs;
Modules/Frontend/app/View/Composers/SectionDataComposer.php — exact edit:
buildHero() reads featured rows first, existing auto-pick becomes the fallback.

**What this is:** Rio wants the OTT homepage hero (the big banner + poster rail
on `/`) chosen by hand instead of by views_count, with drag ordering copied
from the categories page. Scope is the homepage hero ONLY — Rio said explicitly
not to touch the /movie, /series or VJ page banners, which are a different
partial (`movie-slider`) fed by FrontendController's own `$featured`.

**For whoever is next:** the other active session owns docs/plans/ and
docs/adr/0002-4 (mobile app planning, no code) — no overlap.

**Verified:** 14 feature tests pass (fallback, drag order, mixed types, drafts
and episode-less series held back, CRUD, reorder, orphan sweep, admin-only,
and a scope guard proving /movie did not change). Mutation: removing the
`published()` filter failed the draft test, restored. Rendered on a local
server as an admin: empty state, four rows with both badge states, and the
homepage hero following the chosen order.

**Not verified:** production; drag-and-drop was not exercised by a real mouse
(the reorder endpoint is tested directly, the SortableJS wiring is copied
verbatim from the categories page); no mobile-width render of the admin table.

**Gotcha worth remembering:** a Blade view shares its variables with its
layout. The row loop used `$title`, and the admin header partial renders
`$title`; an Eloquent model stringifies to JSON, so the entire movie record
printed across the top of the page. Tests did not catch it because they
asserted on content, not on absence of junk. Rendering caught it. There is now
a test asserting no model is stringified into the page.

**Deliberately not built:**
- No "featured until" scheduling — Rio asked for manual control only.
- No per-page featured lists; /movie and /series banners are explicitly out of
  scope and still use FrontendController's own queries.
- No search in the two pickers; they list the 300 most recent titles each.
  Worth revisiting once the catalogue outgrows that.

### 2026-09-08 — Featured screen: catalogue search + craft pass (1.8.20)

**Status:** complete
**Owns:** same files as the 1.8.19 entry above.
**Shares:** none new.

**What this is:** Rio asked for AJAX search and a more considered screen,
functionality unchanged. The two 300-row dropdowns became one debounced
type-ahead over movies and series (`admin.featured.search`); the list was
reshaped so slot 1 reads as the 16:9 banner and the rest as the 2:3 rail.

**Verified:** 20 tests (6 new for search). Rendered at 1500px and 390px:
empty, search open, four rows including a draft. Impeccable detector clean.

**Not verified:** real-pointer drag; the endpoint is tested directly and the
SortableJS wiring is untouched from 1.8.19.

**Gotchas for whoever is next:**
- Local seed posters are `picsum.photos` URLs. `media_img()` passes external
  URLs through untouched, so a blank poster locally is external latency, not
  a layout bug. Do not chase it.
- `tools/cdp-shots.mjs`: never inject JS that awaits every image. Images with
  `loading="lazy"` below the fold never fire load/error, so the promise never
  settles and the run hangs. Force `loading='eager'` and wait a fixed time.
- A hung run leaves headless Edge holding the profile directory, so the next
  run reuses a logged-in session, finds no login form, and throws. Kill stray
  `msedge` processes before re-running.

### 2026-09-08 — Featured on the house shell + banner auto-rotation (1.8.21)

**Status:** complete
**Owns:** public/frontend/js/jambo-banner-rotate.js (new)
**Shares:** Modules/Content/resources/views/admin/featured/index.blade.php —
rebuilt on the Categories shell; components/partials/scripts/script.blade.php —
one script tag after swiper.js; components/sections/tab-slider.blade.php —
one data-jambo-rotate attribute.

**What this is:** Rio pushed back that the Featured screen invented its own
layout instead of using the Categories two-column shell, then asked for the
missing autoscroll. Both done: screen rebuilt on the house shell with the
craft kept in details rather than structure, and the banner sliders now
rotate.

**Verified:** in-browser probes on the homepage — hero advanced 0→1 across
nine slides; hover held it and mouseleave resumed it; a poster rail stayed
still over the same window. 20 Featured tests pass, detector clean, admin
screen rendered.

**Not verified:** prefers-reduced-motion (read of the code path, not run);
real-pointer drag.

**Deliberately not built:**
- Poster rails still do not auto-advance. Moving a rail under a reaching
  cursor is hostile and 39 at once is unreadable. Any single rail can opt in
  with one `data-jambo-rotate` attribute.
- No admin setting for rotation speed; the delays are constants in the file.

**Gotcha:** the admin theme's `--bs-primary` is RED, not the frontend blue.
`bg-primary-subtle` on an admin badge reads as an error next to green/orange
status pills. Use `bg-info-subtle` for informational badges there.

**Tool note:** `D:\OS	ools\cdp-shots.mjs` now logs the return value of an
injected `js` step, which is how the rotation was actually confirmed rather
than assumed.

### 2026-09-08 — Mobile app Phase 1: PlaybackAuthorizer + PlaybackBeatRecorder

**Status:** complete (services + refactor; no API routes yet)
**Owns:** Modules/Streaming/app/Services/PlaybackAuthorizer.php,
Modules/Streaming/app/Services/PlaybackBeatRecorder.php,
Modules/Streaming/app/Playback/{PlaybackDecision,PlaybackDenial,BeatOutcome}.php,
Modules/Streaming/tests/Feature/PlaybackAuthorizationPinTest.php,
Modules/Streaming/tests/Feature/PlaybackAuthorizerTest.php
**Shares:**
- Modules/Streaming/app/Http/Middleware/TierGate.php - body replaced by one
  authorize() call; the three HTTP responses are unchanged
- Modules/Frontend/app/Http/Controllers/FrontendController.php - userCanWatch(),
  concurrencyExceeded() and contentReleased() are now delegates; all 8 call
  sites and both signatures unchanged. -95 lines, +39
- Modules/Streaming/app/Http/Controllers/StreamProxyController.php -
  streamable() delegates to isReleased(); authorizer added to the constructor
- Modules/Streaming/app/Http/Controllers/StreamingController.php - heartbeat()
  keeps its validation and its browser-specific logout, and hands the rest to
  the recorder. Response bodies identical
- Modules/Frontend/tests/Feature/WatchPagePreviewBotTest.php - setUp() now
  seeds the "premium" tier its fixtures already referenced. No assertion
  changed; see R1 below for why it was needed
- CHANGELOG.md 1.8.22; docs/adr/0002,0003,0004 Proposed -> Accepted
- docs/plans/mobile-offline-app.md - Phase 0/1 status lines

**What this is:** Phase 1 of the mobile app plan. Rio accepted ADR-0002/3/4
unamended and chose to start with the API rather than the app shell, because
the app feeds off the live webapp and has nothing to talk to otherwise. This
commit is the part the API cannot be built without: one entitlement service
and one heartbeat service, extracted from three hand-kept copies.

**Verified:**
- 337 tests, 963 assertions. 2 failures, both pre-existing: run the suite on a
  stash of Modules/ and PricingPageCurrentPlanTest fails identically. They are
  about a "Current Plan" badge on /pricing and are unrelated.
- PlaybackAuthorizationPinTest (16 tests) was written and run GREEN against
  the old code before the extraction, and passes unchanged after it. It pins
  the player, the watch page and the stream URL through HTTP, which is what a
  viewer actually experiences. Run three times to rule out fixture flake.
- Rendered against the real dev MySQL through the HTTP kernel: /, /movie,
  /series, /pricing all 200 through the refactored path; a day-pass movie 302s
  a guest to /login from both /watch/{slug} and /player/movie/{slug}.
- R1 blast radius measured on real data: 0 movies and 0 shows carry a
  tier_required slug that matches no subscription_tiers row.

**Not verified:**
- No browser session. The heartbeat, the device-picker boot and the kicked-
  device overlay are covered by DeviceLimitTest, not by a real player.
- Production data was not checked for orphan tier slugs; the 0/0 count above
  is the dev database.
- Laravel Pint is NOT clean on this repo (14 issues across 18 files, mostly in
  files this change never touched). It is not a gate here, so the new files
  follow the surrounding house style (`!$x`, not Pint's `! $x`) rather than
  Pint's preset. Do not run `pint` repo-wide without deciding that separately.

**Two deliberate reconciliations.** Where the copies disagreed the extraction
had to pick, and it picked TierGate both times, because TierGate is the
security boundary and was already serving the bytes:
- **R1 - unknown tier slug.** TierGate treated a slug matching no tier row as
  free and streamed it; userCanWatch() refused guests one branch earlier, so
  the watch page and the player disagreed. Now uniformly free. This is why
  WatchPagePreviewBotTest needed its tier seeded: it was passing on the
  accident, not on premium gating. Reverse in PlaybackAuthorizer::authorize()
  (marked R1) if the ruling should instead be fail-closed.
- **R2 - cap on inherited episode plans.** concurrencyExceeded() read the
  episode's own tier_required with no fallback to the show, so the cap was
  skipped for nearly every episode. TierGate still capped /watch/src/, so an
  over-cap viewer got a player that silently would not load. Same people
  refused; they now reach the device picker instead.

**Deliberately not built:**
- No /api/v1 routes, no Sanctum device tokens, no `devices` table. Next in
  Phase 1, and the services here are shaped for them: authorize() already
  takes a client session key rather than a Laravel session id, so an app
  device UUID drops straight into the cap, the picker and the boot flow.
- PlaybackDecision carries SUBSCRIPTION_REQUIRED and UPGRADE_REQUIRED as
  separate codes, but the web still renders both as one 403 with the sentence
  it had before. The split is for the app's screens; do not "fix" the web to
  use it without deciding that is a change worth making.
- No OpenAPI spec yet; it belongs with the first real endpoint.

**For whoever is next:**
- The site is LIVE. Anything touching this path needs the pin test green
  before and after, not just after.
- Local MySQL was down at the start of this session; XAMPP's mariadb was
  started by hand (`/d/xampp/mysql/bin/mysqld.exe --defaults-file=...
  --standalone`) and left running. `php artisan serve` on 8090 accepted TCP
  but never logged a request while the DB was down - the hang was PDO, not
  the server. Booting the HTTP kernel from a script and dispatching
  Request::create() is faster than curl for checking a route anyway, and it
  works with no networking at all.
- MovieFactory rolls `published_at` from its own random published/draft
  decision. Overriding only `status` leaves the date null about half the time
  and isPubliclyVisible() then fails at random. Set both in fixtures.

### 2026-09-08 — Mobile app Phase 1b: API v1 foundation, auth and devices

**Status:** complete
**Owns:** app/Http/Api/{ApiErrorCode,ApiResponse}.php,
app/Http/Controllers/Api/V1/{AuthController,AppConfigController}.php,
Modules/Streaming/app/Models/Device.php,
Modules/Streaming/app/Http/Controllers/Api/V1/DeviceController.php,
Modules/Streaming/app/Http/Middleware/EnsureDeviceIsActive.php,
Modules/Streaming/database/migrations/2026_09_08_120000_create_devices_table.php,
tests/Feature/Api/V1/{AuthAndDevicesTest,ApiErrorCodeTest}.php
**Shares:**
- app/Http/Kernel.php — removed the dead `localization` middleware from the
  `api` group and its alias (see below); added the `device.active` alias
- routes/api.php — /v1 group added below the existing /user stub
- Modules/Streaming/routes/api.php — scaffold stub replaced with device routes
- app/Providers/RouteServiceProvider.php — added the `auth` rate limiter
- app/Exceptions/Handler.php — three renderable() callbacks, all guarded to
  token API requests only
- CHANGELOG.md 1.8.23

**What this is:** the app's front door. Sign-in issuing a per-device Sanctum
token, the two-call two-factor flow, sign-out, /me, the device list and boot,
and /app/config. The envelope and the single ApiErrorCode enum every later
endpoint will use.

**🔴 Found and fixed: every `/api/*` route on this site 500'd, and had since
April.** The `api` middleware group referenced `'localization'`, whose class
was deleted in `9c8c192` (i18n/RTL removal) while the alias and the group
entry stayed. Any request under /api/ threw BindingResolutionException before
reaching a controller. It went unnoticed for five months because nothing used
those routes — the site's own JSON endpoints live in routes/web.php and run in
the `web` group despite their /api/v1/ paths, and every module routes/api.php
held unused scaffold. Removed rather than restored; there is no i18n here now.
**If anything ever reported "the API is broken", this was why.**

**Verified:**
- 360 tests, 1131 assertions. Same 2 pre-existing PricingPageCurrentPlanTest
  failures as before this session; nothing else fails.
- Driven end to end against the real dev MySQL through the HTTP kernel:
  app/config public, /me 401 without a token, wrong password
  INVALID_CREDENTIALS, sign in on a phone, sign in on a TV, list both with the
  caller marked is_current, boot the TV from the phone, TV token then 401 and
  phone token still 200, second boot DEVICE_NOT_FOUND, sign out then 401.
  Script cleans up after itself.

**Not verified:**
- No real handset or emulator; there is no app yet.
- 2FA was exercised with a generated TOTP in tests, not with a real
  authenticator app.
- The `auth` rate limiter's second (per-ip, 60/min) layer is not covered by a
  test; only the per-email+ip layer is.
- Nothing was run against production.

**🔴 A test trap, and it would have hidden a real bug.** Laravel's AuthManager
is a container singleton and RequestGuard caches the user it resolved, so
within a single test every later request reuses the first resolution — a
DELETED token keeps authenticating. Every revocation assertion in
AuthAndDevicesTest passed at first for that reason alone, and would have
passed however broken revocation was. `$this->app['auth']->forgetGuards()`
before any request that must be unauthenticated is what makes those tests
honest; the helper is `withFreshToken()`. Production is unaffected because
each request is its own process — verified, not assumed.

**Deliberately not built:**
- `POST auth/register`. The web form carries a honeypot, optional reCAPTCHA,
  SignupAttempt logging, referral-cookie attribution and a username that
  doubles as a referral code. None of that ports to a native form unchanged,
  and a half-ported signup is worse than sending people to the website. Own
  slice, own decisions.
- `auth/google` and `auth/forgot-password` — same reason, smaller.
- Device-code sign-in for TV (RFC 8628). Phase 1 item, but it needs a `/tv`
  page on the site; grouped with the TV work.
- No OpenAPI spec yet. It should land with the catalogue endpoints, where the
  response shapes get big enough that hand-written client types would drift.
- Nothing counts app devices against `EnforceDeviceLimit` yet. The plan wants
  web sessions plus app devices under one cap; the `devices` table now makes
  that possible but the middleware still counts sessions only.

**For whoever is next:**
- A device's `uuid` IS the client session key. Write it as `session_id` on
  active_streams for app playback and the existing cap, picker and boot flow
  work with no new code. That is why booting a device calls
  `ActiveStream::terminateSession($userId, $device->uuid)`.
- The exception handler's API rendering is scoped by
  `$request->is('api/*') && ! $request->hasSession()`. The site's own AJAX
  endpoints share the /api/v1/ prefix but have sessions, and their JS reads
  their existing JSON shapes. Do not widen that guard.
- Sanctum tokens do not expire (`config/sanctum.php` expiration => null). Any
  new token-issuing path must delete the one it supersedes.
- Bootstrapping the app in a standalone script: bind a request instance before
  `$kernel->bootstrap()` or the url generator throws. Script:
  scratchpad/api_smoke.php in this session's temp dir; worth recreating.

### 2026-09-08 — Mobile app Phase 1c: catalogue and playback endpoints

**Status:** complete
**Owns:** Modules/Streaming/app/Services/StreamSourceResolver.php,
Modules/Streaming/app/Http/Controllers/Api/V1/PlaybackController.php,
Modules/Content/app/Http/Controllers/Api/V1/CatalogueController.php,
Modules/Content/app/Http/Resources/{Movie,Show,Season,Episode}Resource.php,
docs/api/openapi.yaml,
tests/Feature/Api/V1/{CatalogueAndPlaybackTest,OpenApiSpecTest}.php
**Shares:**
- Modules/Streaming/app/Http/Controllers/StreamProxyController.php —
  getRawUrl() now delegates to StreamSourceResolver; the release check and the
  404 stay put. Behaviour unchanged, pins still green
- Modules/Content/routes/api.php, Modules/Streaming/routes/api.php
- phpunit.xml — added Modules/*/tests/Unit to the Unit suite
- CHANGELOG.md 1.8.24

**What this is:** browsing and watching from the app. Neither controller holds
an entitlement rule; both call the services extracted in 1.8.22.

**🔴 Ten tests had never run.** phpunit.xml globbed `Modules/*/tests/Feature`
but not `Modules/*/tests/Unit`, so `Modules/Streaming/tests/Unit/CdnUrlResolverTest`
— ten tests over the Bunny token-signing that every stream URL goes through —
was silently skipped on every suite run since it was written. It passes when
pointed at directly. Directory added. **If you add a module Unit test, it now
runs; before today it did not.**

**A third thing nearly got copied.** StreamProxyController::getRawUrl() held
the rendition choice, the dropbox_path fallback and the CDN call in a private
method. The API needs the same answer and would have been a second copy, so it
came out as StreamSourceResolver first. Same pattern as the entitlement rules;
worth watching for a fourth.

**Verified:**
- 394 tests, 1260 assertions. Same 2 pre-existing PricingPageCurrentPlanTest
  failures; nothing else fails. The count jumped by more than the 24 new tests
  because of the phpunit.xml fix above.
- The pinning tests from 1.8.22 still pass after StreamProxyController was
  re-pointed, which is what says the web stream path did not move.
- Driven against the real dev MySQL through the HTTP kernel: movies list with
  a working cursor, detail page, series, search, 404 on a bad slug; playback
  refused for a guest (401), refused with SUBSCRIPTION_REQUIRED once signed in
  with no plan, allowed on the matching plan, heartbeat at 90s, next session
  resumes at 90, and active_streams keyed on 'smoke2-device-0001' — the device
  uuid, which is what makes the existing cap and picker work for app installs.

**Not verified:**
- **Token signing was NOT confirmed end to end.** The dev seed gives titles
  placeholder paths like `/jambo/movies/x.mp4`, which no CDN zone claims, so
  the resolver passes them through untouched; and the local BUNNY_TOKEN_KEY is
  empty. The unit tests cover the signing logic and now actually run, but a
  real signed URL from real data has not been seen. Open question 9 (is Bunny
  Token Authentication ON?) is still one dashboard look, and it gates Phase 3.
- No YouTube-sourced title was exercised; the embed branch is covered by
  reading, not running.
- Search is a LIKE over title only. It is not the website's search and does not
  pretend to be.

**Deliberately not built:**
- `GET /home`. It needs SectionDataComposer::build() split into a HomeRailsService
  the composer and the API share, so the app home is the website home by
  construction rather than by imitation. That is a live-site refactor with the
  same risk profile as the entitlement one and deserves its own slice with its
  own pinning tests — not a tail-end addition to this one.
- Genres, categories, VJ hubs, cast pages, watchlist, continue-watching,
  ratings, reviews, comments, notifications. All straightforward reads; none
  blocking the app shell.
- Nothing counts app devices against EnforceDeviceLimit yet (still session-only).

**For whoever is next:**
- EpisodeResource asks PlaybackAuthorizer for the inherited tier per episode.
  Eager-load `season.show` or a season of 20 episodes is 20 queries. The series
  endpoint already does; anything new that returns episodes must too.
- Add an endpoint and OpenApiSpecTest fails until docs/api/openapi.yaml
  describes it. That is deliberate. Website AJAX routes that share the /api/v1
  prefix are listed in the test's WEBSITE_AJAX_ROUTES and excluded.
- Symfony's YAML parser rejects an unquoted comma inside an inline `{ }` map.
  Quote any description containing one, or the whole spec fails to parse.

### 2026-09-08 — Mobile app Phase 1d: HomeRailsService and GET /api/v1/home

**Status:** complete
**Owns:** Modules/Frontend/app/Services/HomeRailsService.php,
Modules/Frontend/app/Http/Controllers/Api/V1/HomeController.php,
Modules/Frontend/tests/Feature/HomeRailsPinTest.php,
tests/Feature/Api/V1/HomeTest.php,
app/Http/Middleware/IdentifyApiViewer.php
**Shares:**
- Modules/Frontend/app/View/Composers/SectionDataComposer.php — build() and
  its six private helpers moved out; 437 lines to 87. compose() and
  buildPerUser() unchanged in behaviour
- Modules/Frontend/routes/api.php — scaffold stub replaced with the home route
- app/Http/Kernel.php — one added alias, `api.viewer`
- docs/api/openapi.yaml — /home path plus five card schemas
- CHANGELOG.md 1.8.25

**What this is:** the last live-site refactor in Phase 1. The composer and the
API now call one service, so the app home and the website home cannot drift.

**Verified:**
- HomeRailsPinTest (10 tests, 25 shared keys) written and GREEN against the
  old code before the extraction; green unchanged after.
- 414 tests, 1407 assertions. Same 2 pre-existing PricingPageCurrentPlanTest
  failures, nothing else.
- Real dev MySQL: the website homepage renders 200 at 317,818 bytes (317,842
  before the refactor — the difference is randomised rails, not lost markup).
  /api/v1/home gives a guest 6 hero items and 13 rails, no empty rail, no
  video_url anywhere in the body, and no continue_watching. Signed in, the
  card resolves to `resume movie#13 at 420s (8%, 83 min left)`.

**Not verified:**
- The dev database has no upcoming titles and no visible-home category with
  published content, so `upcoming` and `category:*` rails were covered by
  tests but never seen on real data.
- The homepage was checked by status code and byte count, not by eye. The
  rails are randomised per request, so a pixel comparison would not have been
  conclusive anyway, but nobody has LOOKED at the page since the refactor.

**🔴 A silent personalisation bug, found by a test.** /api/v1/home is public,
so `auth:sanctum` never runs and nothing resolves the bearer token. Every
`auth()->id()` inside HomeRailsService and TopPicksRecommender read null even
with a valid token, so a signed-in app would have received the guest home
screen — no Continue Watching, no personalisation, no error to notice. Fixed
with `api.viewer` (IdentifyApiViewer), which calls Auth::shouldUse('sanctum')
so the existing auth()->id() calls read the token and still answer null for a
guest. **Any future public endpoint that personalises needs this middleware;
`auth:sanctum` is wrong there because it fails closed on a guest.**

**Deliberately not built:**
- The `watchLink` on continue-watching cards is still a web route. The API
  ignores it and sends `resume` instead. Not removed, because the blades read
  it.
- No caching added. The expensive parts are already cached inside
  TopPicksRecommender on per-date keys, which is the right granularity; the
  rails as a whole are per-viewer and must not go in a shared cache.
- Genres, categories, VJ and cast *detail* endpoints. The home rails carry
  their cards, but tapping one has nowhere to go yet.

**For whoever is next:**
- HomeRailsService::forWeb() is per-viewer: topPicks, upcomingMovies,
  recommendedMovies, freshMovies and continueWatching all read auth()->id().
  Never put its whole result in a cross-request cache.
- SectionDataComposer memoises in private STATICS. In tests they survive
  between test methods in the same process; HomeRailsPinTest resets them by
  reflection in setUp and tearDown. Any new test touching the composer must
  do the same or it reads the previous test's catalogue.
- Show::published() requires at least one published episode — a series with
  no watchable episode is a dead end and is kept off public rails. Seeding a
  show without an episode gives silently empty series rails and assertions
  that pass for the wrong reason. It cost two fixture bugs here.

### 2026-09-08 — Mobile app Phase 1e: taxonomy pages and the watchlist

**Status:** complete
**Owns:** Modules/Content/app/Http/Controllers/Api/V1/TaxonomyController.php,
Modules/Streaming/app/Http/Controllers/Api/V1/WatchlistController.php,
tests/Feature/Api/V1/TaxonomyAndWatchlistTest.php
**Shares:**
- Modules/Content/routes/api.php, Modules/Streaming/routes/api.php — routes
  appended to the existing v1 groups; nothing existing altered
- docs/api/openapi.yaml — ten paths and five schemas
- CHANGELOG.md 1.8.26

**What this is:** genre / category / VJ / cast archive screens, and the
watchlist, Continue Watching and history screens.

**Entirely additive — no webapp file was modified.** Rio asked on 2026-09-08 to
be careful with the live site. The four live-site refactors Phase 1 required
(entitlement, heartbeat, stream source, home rails) are done and pinned; from
here Phase 1 is new files only.

**Verified:**
- 430 tests, 1493 assertions. Same 2 pre-existing PricingPageCurrentPlanTest
  failures.
- The homepage was rendered again and its structural fingerprint diffed
  against the pre-refactor render captured earlier: identical.
- Real dev MySQL: 8 genres, 6 categories, 1 VJ; /genres/action gives 9 movies
  and 1 series with no video_url in the body; /genres/not-a-real-genre is a
  clean 404; watchlist add twice leaves ONE row; remove twice succeeds twice;
  continue-watching and history both 200.

**Not verified:**
- No cast page was opened on real data (the smoke covered genres and VJs); the
  person path is covered by tests only.
- History pagination past the first cursor page was not exercised on real
  data — the dev database has too little history.

**Design notes worth keeping:**
- POST/DELETE rather than the website's toggle. A toggle retried over a flaky
  connection silently undoes itself; add and remove are safe to repeat. Both
  paths write through WatchlistItem::addFor, so the clients cannot disagree.
- Taxonomy `detail()` orders by QUALIFIED columns (movies.published_at,
  shows.published_at). These are BelongsToMany, and an unqualified
  published_at becomes ambiguous the moment a pivot gains that column — which
  fails as a SQL error in production, not as a test failure.
- A watchlist row whose title was deleted is dropped, not sent as null.

**For whoever is next:**
- `seed()` is taken by Laravel's TestCase. Naming a fixture helper that in a
  feature test is a fatal error, not a warning. Cost one run here.
- Four of the website's own AJAX routes share the /api/v1/watchlist and
  /api/v1/continue-watching prefixes. They do not collide (different segment
  counts and methods) but check `route:list` after adding anything there.

### 2026-09-08 — Mobile app Phase 1f: reviews, comments, and the coverage audit

**Status:** complete
**Owns:** Modules/Content/app/Http/Controllers/Api/V1/{Review,Comment}Controller.php,
tests/Feature/Api/V1/ReviewsAndCommentsTest.php, docs/api/coverage.md
**Shares:** Modules/Content/routes/api.php, docs/api/openapi.yaml,
CHANGELOG.md 1.8.27

**What this is:** reviews and comments on the website's own rules, plus the
answer to Rio's question "are we done with the API".

**We are NOT done.** docs/api/coverage.md is derived from the router: every
viewer-facing web route paired against every /api/v1 route. 34 endpoints
shipped, EIGHT capability areas open. In rough priority: account creation and
recovery (register, forgot/reset password, Google, verify email), profile and
security, notifications and push, subscription and billing, cross-device
concurrency (the app cannot see or boot a BROWSER session, and
EnforceDeviceLimit still counts sessions only), catalogue completeness
(search suggest, tags, rail archive, VJ sub-pages), static pages, referrals
and wallet.

**Verified:** 446 tests, 1565 assertions; same 2 pre-existing failures.
Entirely additive — no webapp file modified.

**Not verified:** reviews and comments were not exercised on real dev data,
only in tests. No notification listener was run end to end; the events are
asserted as dispatched, not as delivered.

**Decision raised, not taken:** ratings. The `ratings` table is written ONLY by
InteractionSeeder — the website gives a viewer no way to rate a title, and a
viewer's stars reach the table through a Review instead. POST /ratings is in
the plan's §5 but would be a new product feature, so it is not built. Rio to
rule.

**For whoever is next:**
- Symfony's YAML parser rejects an unquoted comma inside an inline `{ }` map.
  It has now bitten twice in this spec. Quote every description containing a
  comma, and do NOT write a regex to bulk-fix them — mine mangled an already
  quoted line because leading whitespace defeated its own guard.
- Public API routes that personalise need `api.viewer`. `auth:sanctum` is
  wrong there (fails closed on a guest) and no middleware at all is worse
  (silently null for a signed-in viewer). Three endpoints now depend on this.

### 2026-09-08 — Mobile app Phase 1g: account creation and recovery

**Status:** complete
**Owns:** app/Services/{SocialAccountResolver,DeviceTokenIssuer,TwoFactorChallengeStore}.php,
app/Http/Controllers/Api/V1/{Registration,Password,SocialAuth,EmailVerification}Controller.php,
tests/Feature/Api/V1/AccountAuthTest.php, tests/Feature/Auth/SocialAuthPinTest.php
**Shares:**
- app/Http/Controllers/Auth/SocialAuthController.php — account resolution
  extracted; 175 lines to 81. Redirect and session handling untouched
- app/Http/Controllers/Api/V1/AuthController.php — uses the two new services
  instead of its own private copies
- routes/api.php, docs/api/openapi.yaml, CHANGELOG.md 1.8.28

**What this is:** gap 1 of docs/api/coverage.md — register, Google, forgot,
reset, change password, verification status and resend.

**Verified:**
- SocialAuthPinTest (6 tests) written and GREEN against the old controller
  BEFORE the extraction, and green after. There were NO tests on that path
  before today.
- 468 tests, 1644 assertions; same 2 pre-existing failures.
- Homepage structural fingerprint still identical to the pre-refactor
  baseline; /login, /register, /forgot-password, /pricing all render 200.

**Not verified:**
- Google sign-in ran against Http::fake, never real Google. GOOGLE_CLIENT_ID is
  not set locally. **Before release, confirm the Android client id the app
  ships matches services.google.client_id, or every real sign-in fails the
  audience check.**
- No real mail sent; Notification::fake throughout.
- The reset LINK still lands on the website. /auth/reset-password exists so the
  app can complete it in-app later with no server change, but no deep link is
  wired.

**Deliberately not ported:** the honeypot (only works against a bot scraping a
DOM) and reCAPTCHA v3 (a browser token an Android app cannot produce). Play
Integrity is the real answer if app signup is abused; that is its own decision.

**For whoever is next:**
- Sanctum expiry is null here. ANY new token-issuing path must go through
  DeviceTokenIssuer, or it leaves working credentials that nothing can revoke.
- Both sign-in paths must reach the same TwoFactorChallengeStore. A second
  challenge implementation would let a 2FA account in through whichever path
  forgot it.

### 2026-09-08 — Mobile app Phase 1h: profile and security

**Status:** complete
**Owns:** app/Http/Controllers/Api/V1/{Profile,AccountSecurity}Controller.php,
tests/Feature/Api/V1/ProfileAndSecurityTest.php
**Shares:** routes/api.php, docs/api/openapi.yaml, docs/api/coverage.md,
CHANGELOG.md 1.8.29

**What this is:** gap 2 of the coverage audit. Profile read/edit, avatar,
two-factor lifecycle, deactivation. Additive — no webapp file modified.

**Verified:** 482 tests, 1717 assertions; same 2 pre-existing failures.

**Not verified:** avatar upload used Storage::fake, never a real disk; no
Spatie MediaLibrary conversion ran.

**A bug the tests found, and one deliberately left alone.** `phone` is
nullable, and Laravel's validator omits an absent nullable key entirely, so an
app sending only changed fields hit an undefined index. Fixed here with
isset(). **The same shape exists in ProfileHubController::updateProfile and was
NOT touched** — the web form always posts the input so it cannot fire, and Rio
asked for care with live code. If that form ever becomes partial, fix it there.

**For whoever is next:**
- A test helper cannot sign in a user who already has 2FA enabled: login
  correctly answers TWO_FACTOR_REQUIRED. Sign in FIRST, then enable.
### 2026-09-08 — Mobile app Phase 1i: catalogue completeness and CMS pages

**Status:** complete
**Owns:** Modules/Frontend/app/Services/RailArchiveCatalog.php,
Modules/Pages/app/Http/Controllers/Api/V1/PageController.php,
Modules/Pages/routes/api.php,
Modules/Frontend/tests/Feature/RailArchivePinTest.php,
tests/Feature/Api/V1/CatalogueExtrasTest.php
**Shares:**
- Modules/Frontend/app/Http/Controllers/FrontendController.php —
  railArchives() extracted to the catalog; railArchive() now delegates
- Modules/Pages/app/Providers/RouteServiceProvider.php — gained mapApiRoutes()
- Modules/Content Catalogue/Taxonomy controllers + routes; Frontend HomeController
- docs/api/openapi.yaml, docs/api/coverage.md, CHANGELOG.md 1.8.30

**What this is:** gaps 6 and 7. Search suggest, tags, cast grid, rail
archives, CMS pages.

**A behaviour fix, not just an addition.** `/search` had been a plain title
LIKE while the website searches title OR synopsis and ranks by title-match
quality. The app would have put the wrong film first. Now identical.

**🔴 A limitation found by the pin, and left as-is deliberately.** The three
personalised rail archives (top-picks, smart-shuffle, fresh-picks) order with
MySQL's `FIELD()`. SQLite has no equivalent, so **those rails have never been
covered by the suite and cannot be while tests run on SQLite.** Production is
MariaDB and they were verified rendering there. Rewriting to portable SQL
would change a live ranking — a decision, not a cleanup. Recorded in
RailArchiveCatalog, RailArchivePinTest and the API docs so nobody reads the
coverage gap as an oversight.

**Verified:** 501 tests, 1797 assertions; same 2 pre-existing failures.
Homepage fingerprint still identical. /collection/latest-movies,
/collection/top-picks and /about-us all render on real MariaDB.

**Not verified:** the CMS page content is passed through as admin-authored
HTML and was not rendered in any client.

**Still open in gap 6:** VJ sub-pages (a genre-filtered slice within one VJ)
and the guest view counter.

**For whoever is next:**
- Modules/Pages had NO api route mapping until today. If another module's
  routes/api.php seems to be ignored, check its RouteServiceProvider::map()
  actually calls mapApiRoutes() — several were scaffolded without it.
### 2026-09-08 — Mobile app Phase 1j: one account, one device list

**Status:** complete
**Owns:** Modules/Streaming/app/Services/AccountDeviceRegistry.php,
tests/Feature/Api/V1/AccountDevicesTest.php
**Shares:**
- Modules/Streaming/app/Http/Middleware/EnforceDeviceLimit.php — counts
  through the registry instead of querying sessions inline. **Behaviour
  unchanged while the setting is off, which is the default.**
- Modules/Streaming/app/Http/Controllers/Api/V1/DeviceController.php
- docs/api/openapi.yaml, docs/api/coverage.md, CHANGELOG.md 1.8.31

**What this is:** gap 5. The device list is now the whole account.

**⚠️ The cap change is BUILT BUT OFF.** `streams.count_app_devices` defaults
to false. With it off, EnforceDeviceLimit counts exactly what it counted
before — browser sessions — so the live site is unchanged. With it on, app
installs count too, which is what the plan asks for and which would start
sending viewers who have both a browser and the app to the picker.
**Rio decides when to flip it.** Both states are asserted in
AccountDevicesTest.

**Contract change:** the device list's `uuid` is now `id`, and each row has a
`kind` of app|browser. A session has an id where a device has a uuid, and one
field is what lets DELETE /devices/{id} accept either. Nothing has shipped
against the old shape.

**Verified:** 508 tests, 1821 assertions; same 2 pre-existing failures.
Homepage fingerprint identical.

**Not verified:** no real browser session was booted from a real device; the
sessions rows are seeded in tests.

### 2026-09-08 — Mobile app Phase 1k: notifications and the FCM registry

**Status:** complete (server side only)
**Owns:** Modules/Notifications/app/Http/Controllers/Api/V1/NotificationController.php,
Modules/Notifications/routes/api.php,
Modules/Streaming/database/migrations/*_add_push_token_to_devices_table.php,
tests/Feature/Api/V1/NotificationsAndPushTest.php
**Shares:** Modules/Streaming/app/Models/Device.php - fcm_token/push_enabled
columns, registerPushToken(), forgetPushToken(), pushable() scope, and
revoke() now clears the token; docs/api/*, CHANGELOG.md 1.8.32

**What this is:** gap 3, server side. The polled list (the fallback that makes
push safe) and the token registry.

**Why the token lives on the devices row.** The push standard's rule 1 wants a
registry that is per user, per install, idempotent, and deleted on sign-out.
devices is already one row per (account, install) and every session-ending
path already goes through revoke(). Putting the token anywhere else would mean
remembering to clear it in three places.

**Verified:** 522 tests, 1889 assertions; same 2 pre-existing failures.
Asserted specifically: logout clears the token, being booted clears it, a
token can belong to only one install, and pushable() is what a sender queries.

**NOT verified, and nobody should read this as "push works":**
- **No sender exists.** This is registration and the fallback list only.
- Nothing has been delivered to a real device. The standard's checklist -
  backgrounded, killed (adb shell am kill, never force-stop), locked,
  Android 13+ notification permission, Android 14+ full-screen intent, and the
  fallback poll with push disabled entirely - is Phase 2 work with a handset.
- The second-press-in-the-same-process case is not covered either.

**For whoever builds the sender:**
- Query Device::pushable(). It is the only definition of "reachable".
- Batch receipts come back BY INDEX. Build the parallel token list as the
  envelopes are built, or a mismatch deletes the wrong viewer's token.
- Never put viewer identity in the payload; a lock screen is readable by
  whoever holds the phone.

### 2026-09-08 — Mobile app Phase 1l: subscription, billing, referrals, wallet

**Status:** complete (read side)
**Owns:** Modules/Subscriptions/app/Http/Controllers/Api/V1/SubscriptionController.php,
Modules/Referrals/app/Http/Controllers/Api/V1/ReferralController.php,
Modules/{Subscriptions,Referrals}/routes/api.php,
tests/Feature/Api/V1/SubscriptionAndReferralsTest.php
**Shares:** Modules/Referrals/app/Providers/RouteServiceProvider.php — gained
mapApiRoutes(); docs/api/*, CHANGELOG.md 1.8.33

**What this is:** gaps 4 and 8, read side. Plans, current plan, payment
history, refer and earn, wallet.

**DELIBERATELY NOT BUILT, and both are money:**
- **In-app checkout.** PaymentController::createOrder carries server-authored
  pricing, a frozen price snapshot (so an admin editing a price mid-checkout
  cannot void a genuine payment), a server-computed referral discount, and an
  explicit guard against pairing an arbitrary `amount` with a SubscriptionTier
  payable — the attack that buys Premium for one shilling. Reusing it means
  extracting it into a shared service. **That is a money-code slice of its
  own.** The Play build needs no checkout at all under ADR-0004.
- **Wallet withdrawal.** Money leaving the business.

**Verified:** 533 tests, 1945 assertions; same 2 pre-existing failures.
Homepage fingerprint identical.

**Not verified:** no real PesaPal order was created or polled; orders in tests
are seeded rows. The referral dashboard numbers come from the website's own
service and were not cross-checked against a real referral chain.

**For whoever is next:**
- ReferralSettings caches per request and compares the STRING '1'. In a test,
  `setting(['referrals.active', '1'])` alone does nothing — call
  `ReferralSettings::flush()` after it.
- Modules/Referrals was the SECOND module today found with no api route
  mapping (Pages was the first). If a module's routes/api.php seems ignored,
  check its RouteServiceProvider::map() actually calls mapApiRoutes().

### 2026-09-08 — Review pass over the finished API

**Status:** complete
**Owns:** tests/Feature/Api/V1/{ApiFailureEnvelopeTest,PaginationAndLimitsTest}.php
**Shares:** app/Exceptions/Handler.php (catch-all renderer, 401 mapping),
app/Providers/RouteServiceProvider.php (both limiters re-keyed),
app/Http/Controllers/Api/V1/PasswordController.php (forgot() swallows a
transport failure), eight API controllers (paging), docs/api/openapi.yaml,
CHANGELOG.md 1.8.34

**What this is:** Rio asked to look at what had just been finished. Every
endpoint walked against real MySQL (scratchpad/full_smoke.php, 75 calls) and
the diff read for defects. Four found and fixed; see CHANGELOG 1.8.34.

**The lesson worth keeping, because it will recur.** All four were invisible
to a green suite of 533 tests. The suite fakes mail, creates one row per test,
and runs on SQLite. Real data has tied timestamps, real mail can be down, and
real viewers sit behind CGNAT. **A green suite is necessary and not
sufficient; walk the surface against the real database before calling
anything finished.** full_smoke.php is the shape of that walk - recreate it.

**Verified:** 544 tests, 2049 assertions, 2 pre-existing failures. 75/75
smoke. Homepage fingerprint identical.

**Not verified:** no handset has sent X-Device-Id; the pinned FIELD() rails
were confirmed on MariaDB only.

**For whoever is next:**
- Never cursorPaginate on a timestamp alone. Add ->orderByDesc('id').
- Never cursorPaginate over orderByRaw. Use offset paginate().
- A public API route is IP-keyed for guests unless it reads X-Device-Id.
- Any endpoint that promises a neutral answer (forgot-password, register
  collision messages) must swallow downstream failures, or the failure itself
  becomes the oracle.

### 2026-09-08 — Mobile app Phase 2a: the shell, the tokens and the front door

**Status:** in progress
**Owns:** design/export-tokens.mjs, design/tokens.json, the whole of mobile/
(package.json, app.json, eas.json, tsconfig.json, index.ts, App.tsx,
metro.config.js, eslint.config.js, jest.setup.ts, scripts/,
src/{api,auth,device,navigation,ui,screens,config}/)
**Shares:**
- .gitignore — exact edit: one appended block ignoring mobile/node_modules,
  mobile/android, mobile/ios, mobile/.expo and mobile/dist. `/node_modules` in
  this file is root-anchored, so without it the app's node_modules commits.
- CHANGELOG.md — a new `## Jambo App` section with its own version line.
  **version.txt is deliberately NOT bumped** (see below).

**What this is:** the first slice of Phase 2 — the Expo app bootstrapped in
`mobile/`, the Streamit design tokens exported from the rendered site, the
typed API client, the device identity every request carries, and the sign-in
path. Five real screens: launch/min-version gate, sign in, two-factor, account,
devices.

**No PHP is touched in this slice, and the API needs nothing new for it.**
Checked before starting rather than assumed: `POST /auth/login` already takes
the `device` object, and `/app/config`, `/me`, `/devices` all exist in
docs/api/openapi.yaml at v1.8.34.

**version.txt is not bumped, on purpose.** It is the input to
`UpdateManager`'s `version_compare` against the update manifest
(Modules/SystemUpdate/app/Services/UpdateManager.php:45). Bumping it for a
change that ships no PHP would advertise a webapp update containing nothing.
The app carries its own version in mobile/app.json, starting at 0.1.0.

**Decisions made at bootstrap, each permanent enough to name:**
- `react-native-tvos@0.86.2-0` is the RN core from day one (matches Kangaru's
  RN 0.86.2 / Expo SDK 57 exactly). ADR-0002 wants one codebase for phone and
  TV; swapping the core RN package in Phase 4, after every native dependency
  is pinned, is the expensive version of this decision. It builds an ordinary
  phone APK when EXPO_TV is unset.
- applicationId `com.jambofilms.app` for both ADR-0004 variants — Rio's call,
  2026-09-08. Consequence accepted: the Play build and the direct APK are
  signed with different keys, so switching channel needs an uninstall. The
  website's install page must say so.
- Launcher label "Jambo" (fits under an icon); the Play listing title can be
  "Jambo Films" at Phase 5, where listing copy is decided anyway.
- Generated types, hand-written client. `openapi-typescript` emits
  src/api/schema.d.ts from docs/api/openapi.yaml; the client is Kangaru's,
  extended with the X-Device-Id header. A generated client would be worse at
  the envelope, the 401 hook and the device header than the one that exists.

**Deliberately not built in this slice** (named so nobody rebuilds them badly):
- Home, rails, hero and poster cards — slice 2b, where the token file gets its
  real test.
- Any player, and no Kotlin at all: `expo-jambo-media` is not created here.
- Everything in Phase 3 (downloads, licences, vault, offline home).
- TV beyond installing the tvos core and config plugin: no leanback manifest,
  no banner, no focus ring, no EXPO_TV profile, no device-code sign-in.
- Register, Google sign-in, forgot/reset password, email verification — all
  exist in the API; Google needs google-services.json, a SHA-1 and EAS
  credentials, which is Rio's accounts, not code.
- Push: the token registry exists but docs/api/coverage.md records that there
  is no sender, so registering a token would produce nothing.
- The `direct` subscribe flow (PesaPal). The variant flag ships; the flow does
  not.
- iOS. ADR-0002: a slot, not a build.

**Verified (2026-09-08, on an Android emulator, API 36):**
- `BUILD SUCCESSFUL in 12m 55s`. **The `react-native-tvos` fork builds an
  ordinary phone APK on Expo SDK 57**, which is the ADR-0002 bet and it holds.
  It needed one override — `@react-native/jest-preset` pinned to 0.86.2 —
  because jest-expo wants 0.86.3 and the fork is built on 0.86.2.
- `Android Bundled 25487ms index.ts (1737 modules)`. The sign-in screen renders
  in the site's colours: `#000000` ground, the translucent charcoal card at
  12px, labelled fields on the resting hairline, the primary pill. Sign in is
  correctly disabled while the fields are empty.
- A real request to `https://jambofilms.com/api/v1/auth/login` left the handset,
  was refused, and the refusal rendered in the site's own alert colours. The
  whole pipe works: device id resolved, header attached, envelope parsed, code
  branched, banner drawn.
- 19 tests, typecheck, lint, `api:check` and `tokens:check` all green.
- **Both guards proved by mutation and restored.** Making `clearSession()`
  delete the device id fails two tests; removing the `X-Device-Id` line fails
  three. The first mutation also caught a defect in the test itself — see the
  CHANGELOG note about the constant uuid mock.
- The token export was run four times against the live site as its probes were
  corrected; the final run captures 47 tokens with no missed probes.

**Corrected later the same session — the two claims below were true when first
written and are not any more.** A real sign-in did succeed once the API was
served locally, and all five screens have now rendered with real data:
Account shows Premium Monthly, a formatted renewal date and the 4-device cap
from `limits`; Devices lists the emulator as "This device · In use" alongside a
second install, with a working boot button. The update gate was exercised for
real too — the server's `min_app_version` is `1.0.0`, so a `0.1.0` build was
refused with "You have 0.1.0. Jambo needs 1.0.0 or later." The app version was
raised to 1.0.0 as a result: the server already declares that as the floor, so
that, not an arbitrary 0.x, is the app's first version.

**Not verified, and the reason:**
- 🔴 **No sign-in has succeeded against production, and cannot yet.**
  `https://jambofilms.com/api/v1/app/config` returns **404**: Phase 1 is on
  `feat/api-v1-mobile-app` and is not deployed. The phase exit — a real
  subscriber, on production — is blocked on a deploy, not on app code.
- 🔴 **When it is deployed, every authenticated call will 401 unless the
  docroot points at `public/`.** The root `.htaccess` — the front controller
  used when the site is served from a subdirectory — has no `Authorization`
  pass-through, while `public/.htaccess` does. Since the root one handles the
  rewrite, the bearer token never reaches PHP. Proved with curl on the same
  token: `/me` answers **200 through `php artisan serve`** and **401 through
  Apache at `localhost/Jambo/`**. Not changed here — it is a live-site file and
  Rio's call. Whoever deploys must check how the VPS vhost is rooted first.
- Nothing has run on a real handset — emulator only. No Tecno-class device and
  no 10" tablet has been seen, so the phone/tablet layout claim in the phase
  exit is untested.
- No EAS build has been run. `eas init` and `eas build` touch Rio's Expo
  account and generate an upload keystore on Expo's servers; that waits for
  him to say go.
- Sentry is wired and inert: no DSN, and the connector in this session is not
  authorised, so nothing has been sent or seen.

**For whoever is next:**
- **Port 8081 on this machine is taken by the `Precious` project's dev server**,
  which answers Metro's own status endpoint with an HTML page. Expo prints
  "Waiting on http://localhost:8081" and looks healthy while the dev client
  fails with "Failed to open app". Start Metro on another port
  (`npx expo start --dev-client --port 8092`) and `adb reverse tcp:8092 tcp:8092`.
  This is the same class of problem as [[jambo-local-render]]'s port 8000 clash.
- XAMPP MySQL would not start when asked, so the local API could not be brought
  up as the alternative to a deploy. Not investigated further.
- Prettier reads the repository root `.editorconfig`, which is Laravel's
  4-space PHP style. `mobile/.prettierrc` and `mobile/.editorconfig` exist to
  stop the app formatting itself to the webapp's conventions.
- Generated files are in `mobile/.prettierignore` for a reason: formatting
  `src/api/schema.d.ts` or `src/ui/tokens.json` puts them out of step with
  their generators and turns `npm run check` red for no change in meaning.
- `adb shell` paths need `MSYS_NO_PATHCONV=1` in Git Bash, or `/sdcard/x.png`
  becomes `C:/Program Files/Git/sdcard/x.png`.
- The OpenAPI spec declares `required` only on its envelopes, so every payload
  field generates as optional. `required()` in `src/api/errors.ts` handles the
  two that are load-bearing. Adding `required` arrays to the spec would be a
  real improvement and is a docs-only change — but it asserts server behaviour
  that should be checked endpoint by endpoint first.
- **A throwaway local account was left in place, deliberately:**
  `app-slice-check@jambo.local` (user 47, password `AppSliceCheck2026`,
  Premium Monthly to 2026-10-08). Local database only. It is kept rather than
  deleted because slice 2b needs a signed-in viewer on the first run; delete it
  when the app can create its own account, or sooner if this database is ever
  used for anything but development.
- **`public/storage` on this machine is a real directory, not a symlink** —
  what `artisan storage:link` leaves on Windows without symlink permission. It
  broke the admin file manager: `fm-guard.php` computed the project root as
  `dirname(__DIR__, 4)`, correct only from `storage/app/public/media`, so from
  the served copy it climbed to the web root, found no `.env`, and failed
  closed with "File manager unavailable." on a correct install. The guard now
  walks up for `.env`/`artisan` instead of counting levels. The environment
  fix — replacing the copy with a junction — is still outstanding and needs a
  command this session was not permitted to run.

### 2026-09-08 — Phase 2a addendum: the app wears the admin's branding

**Status:** complete
**Owns:** design/export-branding.mjs, design/build-app-icons.php, design/branding.json,
mobile/assets/branding/{logo.png,favicon.png,preloader.gif},
mobile/assets/{icon.png,adaptive-icon.png}, mobile/src/ui/branding.ts
**Shares:** mobile/app.config.ts (icon, adaptiveIcon, splash plugin),
mobile/src/ui/components.tsx (Loading renders the preloader), 
mobile/src/screens/SignInScreen.tsx (wordmark above the card)

**What this is:** Rio's instruction — the app uses the logo, favicon and
preloader uploaded and set in the admin, not invented placeholders, and the
wiring should allow changing them dynamically later.

**Where they come from.** `design/export-branding.mjs` reads the three admin
settings off a *running* site by scraping the markup that renders them — the
auth header's brand image, the favicon `<link>`, and the loader component's
`<img alt="loader">`. Same reasoning as the token export: no database
credentials, works against any environment by `--base`, and it picks up the
template fallback when a setting is empty, which is genuinely what a viewer
sees. Provenance lands in `design/branding.json`.

**What is dynamic and what cannot be.** `src/ui/branding.ts` prefers a URL
from `GET /app/config` and falls back to the bundled file, so the in-app logo
and loading animation are already server-driven the day the API carries them.
The launcher icon and the native splash are **not** and cannot be: Android
reads both out of the APK before any JavaScript runs. Written down in the
config and in branding.ts so nobody tries.

**The one server-side change still needed**, and it is small and additive:
`GET /app/config` should return `branding: { logo_url, preloader_url }` from
the same `branding_asset()` / `branded_logo()` helpers the site uses. That is
one resource, one spec entry and one OpenApiSpecTest update. Not done here —
Phase 2 was scoped to touch no PHP, and this needs Rio's nod first. The app
consumes it already, so the switch-on is server-only.

**Icons.** `design/build-app-icons.php` builds `icon.png` (opaque, on the
site's black) and `adaptive-icon.png` (transparent, inset to 55%) from the
favicon. Two Android rules the source does not meet: a transparent legacy icon
is composited by the launcher against anything it likes, and an adaptive icon
is masked to an OEM-chosen shape where only the centre ~66% survives — the
mark fills ~80% of the favicon, so pointing Expo straight at it clips the J on
any round mask. GD rather than an npm image library, because this repo already
runs PHP with GD.

**Verified:** typecheck, lint and 19 tests green; `expo config` resolves icon,
adaptiveIcon and the splash plugin with no deprecation warnings; the exported
logo and favicon were opened and are the real Jambo marks.

**Not verified at the time of writing:** nothing rendered yet — `expo-image`
and `expo-splash-screen` are native modules, so the installed dev client
cannot load them and a full rebuild was required.

**Deliberately not done:**
- The 192px favicon is upscaled ~5x to 1024. Rio's call, 2026-09-08: fine for
  development and the preview APK, replace with a 1024px master before the
  Play listing. The script prints that warning on every run.
- The preloader is a 5.2 MB GIF and is bundled whole. It is what the admin
  uploaded and Rio asked for it; worth compressing before release, and worth
  serving from `/app/config` instead so it is not in the APK at all.
- Production's branding differs from local — the live favicon points at
  `/storage/gallery/site/Jambo-films-logo.png`, which is none of the three set
  locally. The app ships local branding by Rio's choice; re-export against
  production before release.

**Amended after Rio's offline point — the design changed because of it.** The
first cut rendered branding straight from the remote URL and leaned on
`expo-image`'s disk cache. Rio pointed out that offline viewing is the whole
point of this app, and that cache is an evictable LRU: the OS clears it when
storage gets tight, which on a handset holding a few downloaded films is not
rare. A viewer days into an offline trip would open a de-branded app with no
network to fix it. Branding is now genuinely downloaded and owned —
`src/ui/brandingStore.ts` writes to `Paths.document`, the directory Expo
documents as safe from system deletion, and the UI reads `file://` only. The
network appears in `syncBranding()` alone, which runs on the launch path and
is allowed to fail.

Change detection is the `?v=<mtime>` fingerprint the API now puts on each URL:
a replaced logo is a different URL, so it downloads once and never again. No
polling and no version protocol. Downloads are staged in the cache directory
and moved into place, because Expo documents that on Android a failed download
leaves a partial file at the destination — writing straight over the live logo
would replace it with a truncated image.

**The PHP change, made deliberately and named.** `AppConfigController` now
returns `branding: { logo_url, preloader_url }`, absolute and fingerprinted,
resolved by locating the setting's file under `public/` rather than trusting
the stored path — the file manager records the URL as the *browser* saw it, so
on a subdirectory install the setting carries that install's prefix, which is
wrong for a handset and wrong for `artisan serve`. Phase 2 was scoped to touch
no PHP; Rio asked twice for dynamic branding, which cannot work without it.

**Verified on the emulator, 2026-09-09:**
- The wordmark renders above the sign-in card, from the downloaded file, as the
  website's auth header shows it.
- `run-as` confirms both files in app-private storage at the exact source byte
  counts: logo 57,599 and preloader 5,196,049.
- 🟢 **The offline case, which is the one that matters.** Airplane mode on,
  API confirmed unreachable from the device, app force-stopped and relaunched:
  still fully branded from the local copy.
- 542 PHP tests pass; the 2 failures are the pre-existing
  PricingPageCurrentPlanTest pair, unchanged.
- App side: typecheck, lint, 19 tests, api:check and tokens:check all green.

**Still not verified:** a branding *change* has not been round-tripped — no
asset has been replaced in the admin and re-downloaded on the device. The
fingerprint logic is reasoned and unit-untested. Worth doing before release.

### 2026-09-09 — Phase 2a: the sign-in screen rebuilt to Rio's design

**Status:** in progress
**Owns:** mobile/src/ui/AuthBackground.tsx, mobile/src/ui/authComponents.tsx,
mobile/src/screens/{SignInScreen,ForgotPasswordScreen,RegisterScreen}.tsx
**Shares:** mobile/src/ui/theme.ts (an `auth` token block and a `greeting`
type style), mobile/src/ui/fonts.ts (Roboto 900), mobile/src/auth/AuthProvider.tsx
(`remember` on signIn, `register`), mobile/src/navigation/{types,RootNavigator}.tsx
(two routes), mobile/src/api/endpoints.ts (forgotPassword, register),
app/Http/Controllers/Api/V1/AppConfigController.php + docs/api/openapi.yaml
(`features.google_sign_in`)

**What this is:** Rio supplied a mockup for the sign-in screen and asked for it
built identically, with Google shown only when it is actually available.

**Four things in the mockup the API cannot honour, raised rather than
silently resolved** (the `screen` skill is explicit that a mockup does not
outrank the rules, and that neither quietly dropping an element nor building a
dead one is acceptable):

- 🔴 **Apple sign-in does not exist.** No endpoint, no config, nothing in the
  spec — `grep -i apple` over the API and the spec returns nothing. The button
  is omitted. A control that can only fail is the one thing the rules forbid
  outright, and on Android it would also need a web OAuth flow and an Apple
  developer account that ADR-0002 does not contemplate.
- **Google is conditional, not absent.** `POST /auth/google` exists but
  `services.google.client_id` is unset, so the server cannot complete it.
  `GET /app/config` now carries `features.google_sign_in`, and the block —
  divider and button — renders only when it is true. It is currently false, so
  the screen ships without it. The flag is on `/app/config` rather than
  `/account/security` because the sign-in screen needs it *before* anyone is
  authenticated.
- **"Remember me" was going to be decorative** — the app's token has no timer
  expiry, so the session always persisted regardless. It is now real: unchecked
  means the session is held in memory only and never written to the keystore,
  so a viewer on a shared handset is signed out when the app is killed.
- **"Forgot password?" and "Create one" needed somewhere to go.** Both
  endpoints existed and neither screen did. Rather than link to nothing or drop
  the links from the design, both screens were built against the real
  endpoints. Forgot-password deliberately shows the same confirmation whatever
  the server says, because the endpoint is written not to reveal whether an
  address has an account and branching in the client would give that away
  instead.

**A trap worth keeping, and this is the second time it has cost a rebuild:**
🔴 **`expo prebuild` without `--clean` leaves a native project that is
inconsistent with app.config and with newly added native modules.** First
occurrence: after adding expo-image and expo-splash-screen, autolinking listed
both and the build succeeded, but the installed APK still reported
`versionName=0.1.0` and the app threw *Cannot find native module 'ExpoImage'*.
Second: after adding expo-linear-gradient, a plain `prebuild` produced an APK
where `expo.modules.image.ExpoImageViewWrapper` could not be constructed at
all — `InvocationTargetException` — even though expo-image had worked minutes
earlier. Both were fixed by `expo prebuild --clean`. **After adding or removing
any native module, or changing a native field in app.config.ts, run
`--clean`.** `android/` is gitignored, so it costs a rebuild and nothing else.

**Rotation, found by Rio asking what happens when the screen turns.** It was
bad, and worth writing down because it would have been just as bad on the
tablet the phase exit names. The auth card had no width cap, so in landscape it
stretched to the full 2856px: inputs a single line across the screen, and the
Sign in button pushed off the bottom because the card grew sideways while the
viewport lost height. The button was not reachable.

Fixed with one token — `auth.cardMaxWidth` (440) — applied to the card and the
footer on all three auth screens, plus `alignItems: 'center'` on the scroll
container so the capped card centres instead of sitting against the left edge.
A form is read and filled in a column; the column's width should not change
because the device turned. The same cap is what makes a 10" tablet correct
without a second layout.

**Verified by rotating the emulator**, not by reasoning: landscape now centres,
keeps the portrait proportions, and scrolls to the button and the footer;
portrait is unchanged. Auto-rotate was restored afterwards.

### 2026-09-09 — Mobile app Phase 2b: Home, the rail kit, and the rest of §6.2

**Status:** in progress
**Owns:** design/export-tokens.mjs (new probes), design/tokens.json,
mobile/src/ui/tokens.json (both generated), mobile/src/ui/rails/*,
mobile/src/ui/media.ts, mobile/src/ui/metrics.ts,
mobile/src/screens/{Home,Movies,Series,MovieDetail,SeriesDetail,Search,
Genre,Category,Vj,Person,Watchlist,History,Profile}Screen.tsx,
mobile/src/navigation/TabNavigator.tsx
**Shares:**
- mobile/src/ui/theme.ts — new semantic blocks for the rail kit. Additive; the
  `auth` block is not touched.
- mobile/src/api/endpoints.ts — the catalogue calls, appended.
- mobile/src/navigation/{types,RootNavigator}.tsx — the tab navigator replaces
  the two-screen app stack.
- CHANGELOG.md — the `## Jambo App` section.

**version.txt is NOT bumped.** Same reason as every app slice: it is what
`UpdateManager` version_compares against the update manifest, and bumping it
advertises a webapp update containing nothing.

**What this is:** slice 2b of Phase 2 — the Home screen, the rail UI kit the
whole app is built from, and then the rest of the §6.2 screen inventory except
Downloads. The 35 tokens slice 2a exported and never used get their first real
exercise here, and the ones the site has that the export never probed get
added.

**Three findings from reading, before any code:**

1. **The app's chrome is already designed on the website.**
   `components/widgets/mobile-footer.blade.php` renders a fixed four-tab bottom
   nav below 992px — Home, Movies, Series, Watchlist, Phosphor icons in
   regular/fill — and `partials/header-default.blade.php` puts search, the bell
   and the avatar in the phone header. The app ports those rather than
   inventing a navigation shape.

2. **`space.railGap` (24) is the detail-page rhythm, not Home's.**
   `jambo-header.css:840` scopes `--jambo-rail-gap` to `.jambo-detail-rails`,
   which the home page does not carry; home rails keep the vendor 3.75em. The
   heading-to-cards gap on a phone is `mb-2 pb-1` = 12px, not the 24px §6.1
   records from desktop. New probes, rather than reusing a token that is honest
   about a different page.

3. 🔴 **The catalogue API hands the app full-size posters.**
   `MovieResource::card()` uses `media_url()`, which returns the original
   upload; the website's own cards use `media_img($src, 640)` and a srcset
   through the `/img` proxy. Thirteen rails of originals into 100dp cards is
   the `ui-performance` skill's item 2 on a Ugandan connection. Fixed in the
   app for now (see below), with the server-side version named.

**Deliberately not built in this slice** (named so nobody rebuilds them badly):
- The player, and no Kotlin: `expo-jambo-media` is still not created. Play
  controls route to the detail screen rather than to a control that cannot
  play.
- Everything in Phase 3: downloads, licences, the vault, the offline home.
- TV beyond keeping the structure ready: no leanback manifest, no banner, no
  EXPO_TV profile, no device-code sign-in, no TVFocusGuideView.
- In-app checkout / PesaPal. The variant flag ships; the flow does not, and it
  does not exist server-side either.
- Standalone star ratings — the website has no viewer-facing way to rate a
  title, so building one is a product decision.
- VJ genre-filtered sub-pages and the guest view counter: `docs/api/coverage.md`
  records both as still open server-side.
- Push delivery. The registry exists, the sender does not.
- Guest browsing. The app stays sign-in-first; raised with Rio as a fork rather
  than decided quietly.

**For whoever is next:** see the closing notes at the end of this entry.
