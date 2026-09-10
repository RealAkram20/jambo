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

**A rule for anyone adding a screen, and it is the reason this slice crashed on
every episode: TWO PLACES THAT SHARE A REACT QUERY KEY MUST SHARE A RESULT
SHAPE.** TypeScript cannot enforce it — a cached value is typed by whichever
`queryFn` the compiler is looking at, and the key is not part of the type — so
it fails at runtime on whichever screen reads the cache second. If two screens
want the same data, share the call as well as the key; if they want different
data, they need different keys. jambo-51 audited the rest of the tree after
this and found no other collisions: `['profile']` appears three times,
`['me']`, `['preferences']`, `['notifications']`, `['app-config']` and
`['devices']` twice each, all pairing with the same `queryFn` at every site.

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

**Verified on an Android emulator (API 35, 1280x2856 @ 480dpi), against
`php artisan serve --port=8095` — rendered, not reasoned:**
- Home: hero, Continue Watching, two numbered Top 10 rails, ten poster rails,
  genres, VJs and personalities. Movies and Series grids. The Watchlist empty
  state. The website's four-tab bar, Home filled and white, the rest dimmed.
- **Portrait and landscape.** At 952x427 the rails reflow to 7.5 cards rather
  than stretching. That needed a real fix, not just a check: the site's
  breakpoint ladder is width-only, and on its own it hands a landscape phone
  the tablet layout — four cards 189dp wide and 265dp tall on a screen 390dp
  tall, so one rail plus a heading plus the tab bar is the entire viewport.
  A poster is now capped against the viewport *height* as well, and the rail
  adds whole cards until it fits. Same failure as slice 2a's auth card,
  different shape.
- Detail pages with the real catalogue, search, taxonomy archives.
- **Register and Forgot Password rendered for the first time.** Both were
  built and typechecked in slice 2a and had never been on a screen.
- 40 app tests, typecheck, lint, api:check and tokens:check green. 542 PHP
  tests pass with the same two pre-existing PricingPageCurrentPlanTest
  failures — no regression, and no PHP was changed.

🔴 **The sign-in form could not be typed into, and that was shipped state.**
Rio hit it first ("on the signin screen i can not enter anything") and it
reproduced from a clean launch every time. `AuthField` drew its focus glow by
adding `elevation` to the View *containing* the TextInput; adding elevation on
Android makes the platform rebuild that view, which destroyed the focused
input inside it. The keyboard opened, focus was gone in the same frame, one
character sometimes landed in the race and then nothing.

It predates this slice — slice 2a's successful sign-in was against the plain
form, before the glassy rebuild on 2026-09-09 introduced `AuthField`, and text
entry was never exercised on the new one. The glow now lives on a sibling
layer with constant elevation and only `shadowColor` toggling, so focus
rebuilds nothing. **The guard is a source test on purpose:** the failure is
native view recycling that Jest's host tree does not reproduce, so a render
test passes with the bug present. It was proved by reintroducing the bug and
watching it fail, and the mutation was restored.

**Two contract faults found against real data:**
- 🔴 The catalogue API returns `media_url()`, which emits app-absolute paths
  with no host. Checked against production: the live site serves
  `/storage/gallery/...` for exactly these, so **no catalogue image would have
  rendered in the app**, invisible locally because the dev seed is absolute
  picsum URLs. Fixed client-side in `src/ui/media.ts`, which also routes
  through the site's own `/img` proxy — the API hands out originals at upload
  size while the website's own cards ask for 640px WebP.
- `rating` is a content certification (G, PG, NC-17), not a number. The spec
  typed it `number`, which would have drawn "NC-17" stars. Spec corrected,
  types regenerated, `OpenApiSpecTest` still green.

**Not verified, and why:**
- Nothing has run on a real handset. Emulator only, so the phase exit's
  "Tecno-class phone and a 10" tablet" is still untested.
- Nothing against production: `jambofilms.com/api/v1` still 404s.
- No EAS build. `eas init` / `eas build` are Rio's account.
- The `imageUrl` helper's proxy path is now exercised locally, but never
  against production's own `/img` route.

**Still to build in this slice** (named, not missed):
- Continue Watching and History as their own screens (the rail exists).
- Profile and settings beyond Account and Devices: profile edit, security,
  notification preferences, plans, referrals and wallet, all read-only.
- Notifications, and the header bell that would open them.

**For whoever is next:**
- **`php artisan serve` is single-threaded.** Fifteen posters requested at
  once queue behind each other and time out, and the home screen renders as
  grey cards. It is not an app bug. Warm Glide's cache at the widths
  `src/ui/media.ts` asks for — `tools/dev-catalogue/README.md` has the loop —
  and the same screen goes from 12fps to 60.
- **Port 8092 had a Metro from the previous session still running**, serving
  this project without the `EXPO_PUBLIC_API_BASE_URL` this one needs. That
  variable is inlined at bundle time, so a stale Metro silently points the app
  at production. Check `Get-CimInstance Win32_Process` before assuming a
  listening port is yours.
- The dev-client launch URL scheme is `jambo://`, not `jambofilms://`
  (`app.config.ts` sets `scheme: 'jambo'`); the applicationId is
  `com.jambofilms.app` and the two are easy to confuse.
- `adb shell input text` mangles long strings on this emulator even when the
  field is healthy. `uiautomator dump` is the reliable way to read what
  actually landed, and it is how the focus bug was diagnosed.
- The Expo dev-client performance overlay was left on by an earlier session
  and shows in screenshots. It is the dev client, not the app.
- `tools/dev-catalogue/` re-dresses the local database with the real
  catalogue. It downloads ~31MB into `public/storage`, which is git-ignored.
  Local database only.
- **The test account is `testuser@jambo.test` / `Jambo@2026`**, named by Rio on
  2026-09-09 and created by `tools/dev-catalogue/6-test-user.php` on the
  highest tier, with three seeded watch-history rows (one finished) so
  Continue Watching and History have real and *different* state. Use this one.
  `app-slice-check@jambo.local` from slice 2a is still in the database and is
  now redundant — left rather than deleted, because dropping a user with watch
  history is a destructive change nobody asked for. Say the word and it goes.
- ⚠️ **Android autofill will silently replace what you type into the sign-in
  email field.** A scripted sign-in that typed the test address ended up
  authenticated as `admin@demo.com`, because tapping the field triggered a
  credential saved during an earlier session and the account screen then
  showed a different person entirely. It looked like an app bug and was not.
  Read the field back with `uiautomator dump` before submitting, and check
  which account you actually got.
- The Expo dev-client **performance overlay** was left on by an earlier
  session. Turn it off from the Tools bubble → "Toggle performance monitor";
  it is the dev client and never appears in a release build.

### Added after the first close — the account, viewing and settings screens

Built after Rio asked for the rest of §6.2: Continue Watching and History as
their own screens, Notifications, Security, Plans, and the Account screen
extended into the profile hub the website has (identity, subscription, then
Your viewing and Settings). All rendered against the real catalogue.

**Three more gaps found by building against the real contract, each named
rather than papered over:**

- 🔴 **A Continue Watching card has no honest action in this slice, so it is
  not interactive at all.** Resume needs the player. It cannot fall back to
  opening the title's page either: `ContinueWatchingCard` carries
  `resume: {type, id}` and no `slug`, and the detail endpoint is slug-only —
  `/movies/1` returns 404, checked against the running API. The first cut
  passed the id through the navigation helper, which returns early without a
  slug, so **every tap silently did nothing**. `ProgressCard` now renders a
  plain announced View when it has no action, and hides the play mark with
  it. **Adding `slug` to the resource is a one-line server change** and would
  make the card openable.
- **A History row can be an episode, not only a movie.** The contract says
  `item` is `MovieCard | Episode`, and an Episode has `still_url`, no
  `poster_url` and no slug. Rendering every row as a movie would have shown a
  blank poster and a link to nowhere; episode rows now show their still and
  are not pressable.
- `SecurityState` is the whole `{two_factor, google_enabled, email_verified}`
  envelope, not the two-factor block. The first cut typed it as the block and
  read `enabled` off the wrong level.

**Deliberately not built, with the reason in each file:**
- **Two-factor enrolment.** The endpoints exist, but enrolment is a flow —
  secret, authenticator, confirm, then recovery codes shown exactly once. A
  switch that turns 2FA on and never shows the recovery codes locks people out
  of their own accounts. Status is shown; the flow earns its own slice.
- **Changing a password**, for the same reason: it is a form with its own
  validation and its own "this signs you out everywhere" question.
- **Subscribe.** ADR-0004 forbids it on the Play build, and the `direct`
  build's PesaPal checkout does not exist server-side. Two independent
  reasons, either sufficient.
- **Notification `action_url` as a link.** It is a website URL and the app has
  no map from site URLs to its own screens. A deep-link resolver is its own
  work; guessing would send viewers to the wrong screen or out to a browser.
- **Notification preferences.** `/notifications/preferences` returns an empty
  array on this database, so there is nothing to render and no way to tell a
  real empty from a broken one without a server-side default set.

### 2026-09-09 — The profile drawer (session jambo-42)

**Status:** in progress
**Owns:** mobile/src/ui/ProfileDrawer.tsx (new),
mobile/src/ui/ProfileDrawer.test.tsx (new)
**Shares — exact edits, nothing else in these files:**
- `mobile/src/ui/theme.ts` — one appended `drawer` token block. No existing
  value changed.
- `mobile/src/api/endpoints.ts` — one appended `profile()` method on the client
  class. `GET /profile` is already in the OpenAPI spec and the `Profile` type is
  already generated; only the call was missing.
- `mobile/src/navigation/types.ts` — one line: `ProfileMenu: undefined` in
  `AppStackParams`.
- `mobile/src/navigation/RootNavigator.tsx` — one `AppStack.Screen` for
  `ProfileMenu`, presented as a transparent modal.
- `mobile/src/screens/HomeScreen.tsx` — one line: the header's `onAccount`
  navigates to `ProfileMenu` instead of `Account`.

**NOT touched:** `mobile/src/screens/AccountScreen.tsx`. Session jambo-fb has it
open and uncommitted. The drawer is a new surface beside it, not a replacement
for it, and the Account screen keeps its own route.

**What this is:** Rio gave a mockup (2026-09-09) of a slide-in profile menu —
avatar, name, handle, tier pill, then Watchlist / Profile / Security / Devices /
Notifications / Membership / Wallet / Refer & Earn, and Sign Out under an
ACCOUNT heading. It opens from the header's account icon. Rio said the pages
behind the new rows follow later, so this slice is the drawer only.

**The mockup is magenta and the brand is not.** Rio asked for the layout with
Jambo's colours, so the active row's gradient is `auth.buttonFrom` →
`auth.buttonTo` (the site's own blue), not the mockup's crimson.


**Scope grew twice mid-session, both times from Rio, and the record matters
because the second reversed the first.** He asked for streaming preferences —
"for the mobile it should be watching not editing so all can edit and update
their streaming preferences", then "so the app is a pure streaming
experience". I read that as a cut and asked whether Profile editing, Wallet
and Refer & Earn should leave the app; he chose to drop them, and I told
jambo-fb to stop building all three. He then corrected the reading: "the thing
i am fighting is we don't want the admin level on the mobile app. profile
editing, referrals and wallet are all needed." **Pure streaming means no ADMIN
surface, not less viewer account management.** All three rows are back and
jambo-fb resumed. The question was mine and it was framed wrong; noting it so
the next session does not re-derive the same false choice.

**Owns, final:** mobile/src/ui/ProfileDrawer.tsx, mobile/src/ui/profileMenu.ts,
mobile/src/ui/profileMenu.test.ts,
mobile/src/screens/StreamingPreferencesScreen.tsx,
app/Http/Controllers/Api/V1/StreamingPreferencesController.php,
database/migrations/2026_09_09_120000_add_streaming_preferences_to_users_table.php,
tests/Feature/Api/V1/StreamingPreferencesTest.php
**Shares:** theme.ts (appended `drawer` block), endpoints.ts (appended
`profile`, `referrals`, `wallet`, `preferences`, `updatePreferences`),
types.ts + RootNavigator.tsx (`ProfileMenu`, `StreamingPreferences`, `Tabs`
retyped with nested params), HomeScreen.tsx (one line), User.php (one cast),
routes/api.php (two routes), AppConfigController.php (`features.referrals`),
docs/api/openapi.yaml.

**The API grew, because Rio said "let the api expose them all as they are
needed":**
- `features.referrals` on `/app/config`, wired to the same
  `ReferralSettings::active()` the website's own sidebar gates its Refer &
  Earn tab on. Without it the app had to guess, and would have offered a page
  the admin had switched off.
- `GET`/`PATCH /account/preferences` with a `streaming_preferences` JSON
  column on users. **On the account, not the handset** — that was Rio's
  explicit ask and it is what makes Phase 4's television inherit the same
  answers.
- Client methods for `/profile`, `/referrals` and `/wallet`, which were
  already specced and had no caller.

**`video_quality` is `auto | high | data_saver` and that is not a resolution
ladder.** Jambo has exactly two renditions and `/playback/sessions` picks
between them with `quality: default|low`. A menu reading 4K / 1080p / 720p /
480p would be three labels for two files. `high` means default, `data_saver`
means low, `auto` means the client chooses from the connection. Anyone adding
a resolution label here should add a rendition first.

**Subtitles were considered and deliberately left out.** There is no subtitle
or caption track anywhere in Streaming or Content, and a preference for
something the player cannot deliver makes a viewer believe they turned
something on.

**Verified:**
- 10 PHP feature tests on the preferences endpoint, 42 assertions, green.
  **Two guards proved by mutation and both restored:** replacing the merge
  with `array_merge(self::DEFAULTS, $data)` failed the partial-PATCH test, and
  deleting the unknown-quality fallback failed its own. A third mutation on
  the app side — letting a zero unread count draw a badge — failed two of the
  drawer's tests.
- `OpenApiSpecTest` green, which is what proves the new endpoint is documented
  and that the spec still documents nothing that does not exist.
- 15 app tests on the drawer's pure helpers; 65 app tests overall, typecheck
  and lint clean on every file this session owns.
- `/app/config` called directly, not just through its test, and it returns
  `{"downloads":false,"in_app_subscribe":false,"google_sign_in":false,"referrals":false}`.

🔴 **I shipped a broken `/app/config` for part of this session and jambo-fb
caught it.** A Python heredoc turned the `\a` of `Modules\Referrals\app\...`
into a literal BEL byte (0x07), so the file was a ParseError and the first
call the app makes on launch 500d. This is the *same trap already recorded
machine-wide as "quoted heredocs mangle PHP namespaces"* — I hit it through
Python rather than bash and did not connect the two. `grep -rlP '\x07'
--include=*.php` finds it and now returns nothing. **Write PHP with the Write
tool. Never through a shell or Python string.**

**Deliberately not built:**
- **The Profile, Wallet and Refer & Earn screens.** jambo-fb owns them and is
  building them now. The drawer's three rows render dimmed with a "Soon" tag
  rather than being hidden or left pressable-but-dead — hiding them makes the
  menu disagree with the website, and a dead row makes a viewer think the app
  is broken. Each becomes live with one line in `ProfileDrawer.tsx`.
- **The pencil on the avatar is decoration**, marked `pointerEvents="none"`.
  The whole identity block is the press target and goes where the pencil
  would. It becomes real with the avatar upload endpoint, which exists.
- **A Wi-Fi-only downloads switch is stored but not shown** unless
  `features.downloads` is true. Downloads are Phase 3; a switch governing a
  feature that does not exist appears to do nothing.

### Added after the second close — rail archives, and the account screens

**"View all" on the home rails.** 🔴 The rail and collection key spaces do not
match: `GET /home` is underscored, `GET /collections` is hyphenated, six
convert by swapping the separator, **one pair no rule derives** — the rail
`exclusives` is the collection `only-on-streamit` — three rails have no
archive at all (`top_movies`, `top_series`, `international_series`) and one
collection has no rail. Using a rail key as a collection key 404s.

`collectionKeyFor` converts and the home screen checks the result against the
list the server publishes before drawing a link, so a rail without an archive
gets no link rather than a link to a 404, and a collection added server-side
lights up its rail with no app release. Verified on the emulator: "Movies to
Watch" and "Best in Series This Week" have no link, "Top Picks for You" and
"AI Smart Shuffle" do. Category shelves route to their taxonomy archive
instead. Ten unit tests pin the mapping.

**ProfileEdit, Wallet and Refer & Earn**, after Rio clarified that "a pure
streaming experience" means no *admin* surface in the app, not no viewer
account management. Money is a string from the wire to the Text node — no
total, no sum, no "X more to withdraw", because each is arithmetic on
somebody's balance. Referrals treats a 404 as "off for you" with no Try again
button, and is gated on `features.referrals` rather than on the 404.

**AccountScreen was handed to jambo-42** on Rio's instruction, mid-session:
they are building a full-screen profile drawer and introducing more screens,
so the app has one account navigation rather than two. The three hub links
added to reach the new screens were reverted; those screens are reached from
the drawer.

**Working alongside another session.** jambo-42 held theme.ts, schema.d.ts,
ProfileDrawer, AppHeader and the streaming-preferences backend. Nothing of
theirs is in any of my commits, and their `StreamingPreferences` and
`ProfileMenu` routes were preserved when I took the window on the navigation
files.

🔴 **Their `/app/config` 500ed on a literal 0x07 byte.** The \a in
`use Modules\Referrals\app\Services\ReferralSettings;` was written through a
string interpreter, which reads \a as BEL - so the line became
`Modules\Referrals` + 0x07 + `pp\Services\...` and PHP reported
`ParseError: syntax error, unexpected character 0x07`. It broke the app's
first call and therefore every launch. Found by curl, located with
`grep -rlP '\x07' --include=*.php` (that file was the only hit in the repo),
reported rather than edited under them, and fixed by them.

This is the trap already in this machine's memory as "quoted heredocs mangle
PHP namespaces", now in a second interpreter - theirs was Python, the original
was bash. **Write PHP with the Write tool and run it by path.** This paragraph
was itself mangled twice while being written, because it contains the very
escape it describes; it took a quoted heredoc plus raw strings to survive.

**Still not built:** the player, two-factor enrolment, password change,
subscribe, notification deep links, avatar upload, and notification
preferences. Each is named with its reason in the file that would have held it.

**Rio corrected the shape mid-build: "this is not a drawer it's a screen of
it's own."** It had been a panel sliding in from the left over the home screen
behind a scrim. It is now a full screen presented as a modal, and the
correction deleted more code than it added — no scrim, no panel width, no
translateX, no `Animated`, no `BackHandler` interception, no
`accessibilityViewIsModal`. The navigator does all of that for a screen. The
file moved from `ui/ProfileDrawer.tsx` to `screens/ProfileMenuScreen.tsx` and
the token block from `drawer` to `profileMenu`.

**Then "please loop yourself until we get the exact design",** which is what
the rest of this entry is: rendered on the emulator, compared against the
mockup, changed, rendered again.

**What the loop actually caught, none of which a test would have:**
- 🔴 **The active-row highlight could never fire.** The menu is opened from the
  home header, so the route underneath is always the Home tab, which is in no
  row's map — the gradient, the filled icon and the heavier label were code
  that ran never. It is now driven by `openedRow()`, a module-level store,
  *because component state was not enough*: picking a row dismisses the menu,
  so `useState` was gone by the next open. Proved by seeding the store, seeing
  the pill render, and restoring.
- **The unread pill was blue on blue.** `badgeBg` is `activeFrom` and the
  active row's fill is a gradient starting at `activeFrom`, so on the lit row
  the count was a shape you had to look for. It now inverts to white with the
  row's blue as its text.
- **The avatar was a third too small.** Measured against the phone frame in
  Rio's mockup the circle is about a fifth of the screen's width; at 68 it was
  nearer an eighth and the identity block read as another list row. Now 84.

**Verified by rendering, on an Android emulator (API 35, 1280x2856 @ 480dpi),
against `php artisan serve` on 8095 and the real local catalogue:**
- The menu with all nine rows live, real name, `@handle` and the real plan
  name on the tier pill.
- The **99+ badge**, which needed 120 seeded unread rows to exist at all —
  `tools/dev-catalogue/8-test-user-notifications.php`, added for this.
- The active row rendering as the mockup's filled pill, in Jambo blue.
- The Streaming preferences screen, and a **round trip proved end to end**:
  tapping "Data saver" on the handset wrote
  `{"video_quality":"data_saver",...}` to the `streaming_preferences` column,
  read back out of the database rather than from the screen's own state.
- The Downloads section correctly absent, because `features.downloads` is
  false on this server.

**Three rows shipped dimmed and are now live.** Profile, Wallet and Refer &
Earn were rendered with a "Soon" tag while jambo-fb built their screens in
parallel; they point at `ProfileEdit`, `Wallet` and `Referrals` as of
`331b7ff`. **The identity block goes to `Account`, not to `ProfileEdit`, and
that is deliberate** — Account is the hub holding the subscription, Continue
Watching and History, and this menu is now the app's only navigation, so
pointing the block at the editor would leave Continue Watching unreachable.

**For whoever is next:**
- **`adb shell input tap` on the header account icon is unreliable while the
  home list is still settling posters.** It is not an app bug — the dev server
  is single-threaded, the list re-renders, and the tap lands mid-frame.
  `scratchpad/openmenu.sh` retries until `uiautomator dump` actually shows
  "Your account", and that is the only way scripted navigation was reliable.
- **A back press on Home exits the app.** Picking a row dismisses the menu, so
  the stack is one deep again; a scripted sequence that presses back twice
  ends up on the Android launcher and the next twenty taps do nothing.
- The 120 seeded notifications are local only and re-running the script
  replaces its own rows rather than adding to them. Pass `0` to clear them.


### 2026-09-09 — The Profile screen: a banner, real country, real avatar upload

**Status:** in progress
**Owns:** mobile/src/screens/ProfileScreen.tsx (new),
mobile/src/ui/profileFields.ts (new), mobile/src/ui/profileFields.test.ts (new),
app/Support/Countries.php (new),
database/migrations/*_add_country_to_users_table.php (new),
tests/Feature/Api/V1/ProfileCountryTest.php (new)
**Shares — exact edits:** ProfileEditScreen.tsx (a country picker, and the
avatar pencil), ProfileMenuScreen.tsx (the Profile row points at the new view),
navigation/types.ts + RootNavigator.tsx (one route), ui/theme.ts (a
`profileBanner` block), api/endpoints.ts (`uploadAvatar`), ProfileController,
openapi.yaml.

**What this is:** Rio's mockup of the profile page — a banner, the avatar
overlapping it, name, handle, a Premium pill, then a card of Email / Phone /
Country / Member Since, and an Edit Profile row. He asked for the loop:
render, compare, change, render again.

**Two things in the mockup had nothing behind them, and both were put to Rio
rather than faked:**

1. 🔴 **"Country: Uganda" was an invented value.** There is no country column
   on `users` and no country field anywhere in `/api/v1` — the mockup's
   "Uganda" is the only place it has ever existed. Rio chose to make it real
   rather than drop the row, so this slice adds the column, the API field and
   a picker on the edit form. **An account that has not set one renders an em
   dash, never a guess.** The rejected option worth recording: deriving it
   from the phone's dialling code, which is wrong for anyone using a SIM from
   a country they do not live in and offers them no way to correct it.
2. **"Change Cover" is gone, and that follows from Rio's own instruction.**
   He asked for "a gradient from our brand blue" instead of a cover image, so
   the banner is brand furniture rather than user content and there is nothing
   to change. There is no cover image anywhere in the API either.

**The avatar pencil is being wired for real**, Rio's choice over the cheaper
one: `POST /profile/avatar` already exists and is specced, so the gap was only
the client. That means `expo-image-picker`, a native module, and therefore a
dev-client rebuild.


**Rio, mid-slice: "we need the phone number even on the webapp, because we
want to be able to reach out to these people when needed."** Surveyed before
acting on it. `users.phone` exists (added for PesaPal prefill) and the
website's profile-hub form already offers it, but **registration never asks
for it at all** — web or app — so the column is empty for almost every
account and Rio cannot in fact reach anybody. That is the real gap, and it is
in registration rather than in the profile forms. Being handled after the
Profile screen, in the same slice.


🔴 **Gotcha that nearly produced a false green, worth knowing before the next
mutation test.** `opcache.enable_cli` is On in this XAMPP build. Restoring a
mutated PHP file with `mv backup.php original.php` gives the restored file the
*backup's* mtime, which is older than the mutant's — so opcache keeps serving
the mutant and the test that just failed goes on failing against code that
looks correct in the editor. Twenty minutes went into debugging a validator
that was already right. **`touch` the file after restoring a mutation**, or
restore by rewriting its contents rather than moving a file over it. A run
green under these conditions would have been meaningless in the other
direction too.


**Verified by rendering, on the emulator against the real local API:**
- The banner, the tagline, the overlapping avatar, the name, handle and plan
  pill, the detail card and the Edit Profile row.
- **Country end to end:** the picker opened with Uganda, Kenya, Tanzania,
  Rwanda, Burundi, South Sudan and Congo above the divider, choosing Uganda
  and saving wrote `UG` to the column, and the Profile row read "Uganda" back.
  Read out of the database, not off the screen.
- **Photo upload end to end**, after the bug below: a 461 KB PNG is stored in
  the `profile_image` collection against the test account.
- The Phone row correctly reading an em dash, because that account has none.

🔴 **The avatar upload failed first, and the cause is worth carrying to every
future upload in this app.** Every React Native example passes a file part as
`{ uri, name, type }`. That is what the old RN `FormData` wants, and this
runtime's is the **spec-compliant** one, which accepts only a real `Blob`: the
request never left the device and threw `Unsupported FormDataPart
implementation`, surfaced by the client as a plain network error. The fix is
`new File(uri)` from `expo-file-system`, which declares `implements Blob` and
streams from disk rather than reading the photo into JavaScript memory. Found
by temporarily rendering `error.cause` on the screen, because nothing reached
logcat and the server log was silent — the request genuinely never arrived.

`api/client.ts` also gained multipart support for this: a `FormData` body is
passed through untouched and **`Content-Type` is deliberately not set**,
because a multipart body is unparseable without the boundary that fetch
generates. Setting the header by hand sends the type without the boundary and
the server sees an empty `$request->file()`.

**Uniformity, on Rio's instruction — "keep the ui uniform".** Four screens had
each grown their own version of the same two shapes: the profile menu drew
52dp rows, the profile details 18dp of padding, the streaming settings 12dp
and the country picker 48dp, with three icon sizes and two card treatments
between them. Nothing was wrong alone; together they read as four apps. There
is now one `ListRow` and one `ListCard` in `ui/list.tsx` spending one `list`
token block, and all four use them. **A screen that wants a row uses `ListRow`;
widening that file is the honest move, not a fifth variant beside it.**

**A broken photo now falls back to the initial.** `avatar_url` being present
does not mean the image is reachable, and the empty circle was rendering as a
hole rather than as a placeholder. Which leads to a finding rather than a bug:

**`APP_URL` is `http://localhost/Jambo` while the app talks to
`10.0.2.2:8095`,** so every media URL the API builds is unreachable from the
handset even though the API itself is fine. Correct in production, where the
two are the same host. Nothing to fix in the app; worth knowing before anybody
spends an hour on "images do not load".

**Deliberately not built:**
- **"Change Cover" from the mockup.** Rio replaced the cover image with a
  brand gradient, so the banner is furniture rather than user content and
  there is nothing to change. There is no cover column, endpoint or field
  anywhere either.
- **Removing a photo.** `deleteAvatar()` is on the client and the endpoint
  exists, but no control calls it. Replacing covers the case people actually
  have; reverting to initials earns a confirmation and a place to put it.

### 2026-09-09 — Phase 2 slice 2c: the player (session jambo-69)

**Status:** in progress
**Owns:** mobile/modules/expo-jambo-media/** (new, the only native code in the
app — ADR-0002), mobile/src/screens/WatchScreen.tsx (new),
mobile/src/ui/player/** (new: the controls, the settings menu, the pure
helpers and their tests), tools/dev-catalogue/9-playable-video.php (new)
**Shares — exact edits, nothing else in these files:**
- `mobile/src/ui/theme.ts` — one appended `player` token block, from new
  probes in `design/export-tokens.mjs`. Appended, not reordered or
  reformatted: jambo-51 is in the same file and both of us rewriting it is how
  a token gets lost. The `auth`, `profileMenu` and `profileBanner` blocks are
  not touched.
- `mobile/src/api/endpoints.ts` — `playbackSession()` and `heartbeat()`,
  appended after jambo-51's `profile`, `uploadAvatar`, `deleteAvatar`,
  `countries`, `preferences`, `updatePreferences`, `referrals` and `wallet`.
- `mobile/src/navigation/{types,RootNavigator}.tsx` — one `Watch` route,
  presented fullscreen with no header.
- `mobile/src/screens/TitleDetailScreen.tsx` — the Play/Resume CTA, and
  episodes become pressable. **This deletes "Playback arrives in the next
  update."**
- `mobile/src/screens/ContinueWatchingScreen.tsx` and
  `mobile/src/ui/rails/ProgressCard.tsx` — the card resumes, and the play mark
  comes back. **This deletes "Resume arrives with the player in the next
  update."**
- `mobile/{package.json,app.config.ts}` — `expo-screen-orientation`, and the
  autolinked native module.
- `design/export-tokens.mjs` — a sign-in step, and the player probes.
  Additive; the four public pages are unchanged.

**version.txt is NOT bumped.** Same reason as every app slice: it is what
`UpdateManager` version_compares against the update manifest, and bumping it
advertises a webapp update containing nothing.

**No PHP in this slice.** `POST /playback/sessions` and `POST
/playback/heartbeat` have existed since 1.8.24 and are already in the spec and
in the generated types, so the contract needs nothing. If that changes it goes
to Rio before it is written.

**What this is:** the player. `expo-jambo-media` in Kotlin on Media3, a watch
screen, resume, next episode, the Data Saver toggle, and remote keys built now
rather than retrofitted in Phase 4. Online only: no downloads, no encrypted
cache, no licences.

**Working alongside jambo-51.** They hold theme.ts, schema.d.ts,
ProfileMenuScreen, AppHeader, AccountScreen and the streaming-preferences
backend, and they have a dev client from this session that
`expo prebuild --clean` would invalidate mid-loop. **The prebuild waits for
their window.** Everything that does not need a native rebuild is built first.

Two of their answers this slice depends on, confirmed by message rather than
assumed:
- The in-player quality menu **writes to `/account/preferences`** rather than
  keeping a local override, so the account stays the one source of truth and
  the answer follows the viewer to a television. PATCH, one key at a time: the
  endpoint merges, so resending the whole set would silently reset a field
  that did not move.
- **No subtitles menu.** There is no subtitle or caption track anywhere in
  Streaming or Content. This is a deviation from the brief, raised with Rio
  rather than taken quietly.

**Deliberately not built in this slice:**
- Everything in Phase 3: downloads, the encrypted cache, licences, the offline
  home. No Download CTA appears anywhere.
- Subtitles and captions, as above.
- Picture-in-picture, background audio, lock-screen / MediaSession controls,
  Chromecast.
- Guest playback and the guest view counter.
- TV chrome: leanback manifest, banner, the `EXPO_TV` profile, device-code
  sign-in. Phase 4. Only the remote keys and the focus states are built now,
  because those are the part that is expensive to retrofit.
- Checkout on a refusal. `SUBSCRIPTION_REQUIRED` routes to the informational
  Plans screen, per ADR-0004.
- `FLAG_SECURE` on by default. The prop exists and is off this phase: the
  website streams unprotected, and turning it on blacks out the screenshots
  this slice is verified with. Phase 3 turns it on for offline playback, which
  is the case ADR-0003 actually argues.

**Built so far, before the emulator (the prebuild waits on jambo-51's dev
client, which `expo prebuild --clean` would replace mid-loop):**

- `mobile/modules/expo-jambo-media/` — Kotlin on Media3. The view draws frames
  and reports state; every control is React Native. `useController = false`.
- `mobile/src/ui/player/` — the controls, the seek bar, the settings menu, the
  remote keys, and two pure modules with 34 tests: `playback.ts` (rendition
  choice, resume clamping, the clock, next-episode ordering, seek maths) and
  `resume.ts` (a Continue Watching card to a player target).
- `mobile/src/screens/WatchScreen.tsx` — session, heartbeat, resume, quality,
  next episode, and the refusal states.
- `tools/dev-catalogue/9-playable-video.php` and `video-server.mjs`.
- **Both apologies deleted.** `TitleDetailScreen` has a real Play control and
  its episodes are pressable; Continue Watching resumes on the home rail and
  on its own screen, and the play mark is back on the card.

**Verified against the running API, by calling it rather than reading it:**
- A session for a movie at both qualities; a `low` request for a title with no
  low rendition refused with CONTENT_UNAVAILABLE; episodes across two seasons;
  `/episodes/{id}` returning the series slug that next-episode needs.
- A heartbeat at 12s makes the next session resume at 12. A heartbeat at 42s of
  a 52s title comes back `completed: true` and the next session resumes at 0,
  which is the documented behaviour and not a bug.
- `npm run check` green: api:check, tokens:check, typecheck, lint, **127 app
  tests** (71 before this slice).
- **Eight guards proved by mutation, every mutation restored.** The harness
  found a weak test of my own: the "a `show` type is refused" case had no id,
  so the id guard rejected it and the type guard was never exercised —
  replacing it with `type === undefined` left the suite green. Fixed by giving
  that fixture a valid id.

**Three faults found in `design/export-tokens.mjs` while growing it a sign-in
step, all pre-existing or introduced-and-caught in the same session:**

1. 🔴 **A signed-in run silently deleted the sign-in screen's tokens.** The
   browser profile is wiped at startup inside a try/catch, and that wipe fails
   while the previous run's browser holds the files — which it usually does,
   because `browser.kill()` leaves Edge's renderer and GPU children alive.
   Twenty were counted after two runs. Harmless while every page was public;
   once the script could sign in, run two inherited run one's session, `/login`
   redirected, and every `authCard`/`authField` probe matched nothing. Caught
   only because `color.surface` is required and comes from that page. Fixed
   with a unique profile per run.
2. 🔴 **Every control-bar colour came back null.** `player.css` is written in
   `oklch()` and relative colour, which `getComputedStyle` returns verbatim and
   the exporter's parser dropped. Three conversions were measured against this
   browser: computed style preserves `oklch`, canvas `fillStyle` echoes it
   unchanged, and a one-pixel `fillRect` + `getImageData` converts it exactly
   (`oklch(0.7 0.15 250)` → `75,163,247`). The last is now used, which keeps
   the browser as the authority instead of putting colour-space maths in the
   repo.
3. The pill radius `calc(infinity * 1px)` computes to `3.35544e+07px` and the
   number parser rejected it, so the seek bar's radius read as null — which
   means "square" when the truth is the opposite.

Also: the capture order is now public pages first, THEN sign in. The obvious
order breaks the login page's own probes, which is how fault 1 surfaced.

**A design question settled by evidence rather than preference.** `apple-design`
(now a standard for `mobile/`, per Rio on 2026-09-09 — see this repo's
`CLAUDE.md` §3 and the plan's §6.4) asks for translucent chrome. The captured
tokens say the site does not use any: `color.playerControlsBg` is
`transparent`, `effect.playerControlsBackdrop` is `none`, and
`effect.playerOverlay` is a real gradient. So the controls sit on a
LinearGradient scrim and `expo-blur` was not added.

**A dependency avoided.** The player needs landscape. The first cut reached for
`expo-screen-orientation`; `@react-navigation/native-stack` already exposes
`orientation: 'landscape'` as a screen option through `react-native-screens`,
which is installed. No new package, no lock-file conflict with jambo-51, and
the navigator unwinds it on the way out with no cleanup to forget.

**`media.ts` gained `assetUrl`.** 🔴 A playback URL is not always absolute:
when no CDN zone claims the stored value, `CdnUrlResolver::resolve()` returns
it untouched, and this database stores bare paths — confirmed by calling the
endpoint, which returned `"url":"/jambo/movies/hidden-storm-29765.mp4"`. Same
class as slice 2b's image fault, so the existing origin logic was extended
rather than copied. It deliberately does not route through the `/img` proxy.

🔴 **The Android build was broken before this slice, and nobody knew, because
nobody had regenerated `android/` in months.**

`mobile/.gitignore` ignores `/android`, so that directory has never been in
git. Every build in this project has been incremental against a tree generated
by some earlier session. jambo-51's successful dev-client build the same
evening reported `456 actionable tasks: 35 executed, 421 up-to-date` —
`expo-modules-core` and `expo-manifests` were among the 421 that never re-ran.
So a clean `expo prebuild --clean` had not been exercised on this machine, and
the brief's instruction to run one is exactly what surfaced it.

The symptom: `:expo-modules-core:compileDebugKotlin` cannot resolve React
Native at all — `ReadableMap`, `ReactStylesDiffMap`, `SoLoader`, `HybridData`
— and `:expo-manifests:generateDebugBuildConfig` dies with
`NoClassDefFoundError: com/squareup/javawriter/JavaWriter$Scope`.

**It is not the media module, and that was proved rather than assumed.** The
module was moved out of the tree entirely and the `expo.autolinking` key
removed from `package.json`; autolinking confirmed it was unlinked; the
identical failure reproduced. Everything was restored afterwards. The module's
own Gradle tasks all pass.

**The dependency was ruled out properly before touching any cache.** The tvos
substitution resolves (`com.facebook.react:react-android ->
io.github.react-native-tvos:react-android:0.86.2-0`), the 279 MB AAR
downloaded, and both it and its transformed output were opened by hand:
`classes.jar`, 1,654 entries, `com/facebook/react/bridge/ReadableMap.class`
present. The artifact was never the problem.

⚠️ **One diagnostic of mine was inconclusive and is recorded so nobody repeats
it.** An init script printing `debugCompileClasspath` through
`incoming.artifactView { lenient = true }` showed react-android as a raw
`.aar` rather than an extracted `classes.jar`, which looks like the answer and
is not: that view requests the default variant, so the raw aar is what it
should return. It says nothing about what the Kotlin compiler received.

**Two things a local module needs that are easy to miss**, both found the slow
way: `expo-modules-autolinking` in SDK 57 does NOT default to `./modules` — the
app's `package.json` must declare `expo.autolinking.nativeModulesDir` — and the
module needs its own `package.json` or the scanner skips the directory. And
`android.defaultConfig.versionName` is mandatory in the module's
`build.gradle`: without it the build fails with
`'android.defaultConfig.versionName' is not defined` reported against
`node_modules/expo/android/build.gradle`, which points nowhere near the module
actually missing it.

**Verified by rendering on an Android emulator (API 35, 1280x2856), against
`php artisan serve` on 8095, the real local catalogue and the byte-range video
server — every one of these was watched on screen, not reasoned about:**

- **A film plays.** Video through the Kotlin Media3 module, controls over it,
  the scrim, the seek bar filling, the clock running, landscape and fullscreen.
- **Resume works end to end.** A heartbeat wrote position 8; the Continue
  Watching card then read "15% watched"; tapping it started the player at 8 and
  it ran on from there.
- **The heartbeat reaches the database.** After playing to the end the row read
  `position=51 completed=true`, and the next session correctly returned
  `resume_position: 0`, because a finished title starts again.
- **Auto quality chose the low rendition on its own**, because the emulator
  reported a cellular connection. That is the first end-to-end proof that
  `/account/preferences` actually drives playback rather than merely being
  stored. On wifi the same title played the default file.
- **The quality menu shows three rows for two files** — Auto, Default, Data
  saver — with a tick rather than colour alone, and no invented resolutions.
- **Episodes play, and next episode crosses the season boundary**: S01E04 to
  S01E05 to S01E06 to **S02E01**, which is the case the fixture's whole-series
  scope exists to reach.
- **Both apologies are gone.** The detail page leads with Play; Continue
  Watching resumes and the play mark is back on the card.
- **Refusals render honestly.** The real "This title is not available yet." was
  seen for an unreleased title, with Try again and Go back.
- **The orientation lock unwinds.** Back out of the player and the app is
  portrait again — the reason for using the navigator's `orientation` option
  rather than locking by hand and having to remember to release it.
- `npm run check` green: api:check, tokens:check, typecheck, lint, **127 tests**
  (71 before this slice). **Eight guards proved by mutation, all restored.**

**Three defects found by rendering that no test would have caught**, two of
them mine and one in the fixture:

1. 🔴 **The end card swallowed every touch.** It was a full-screen layer, so
   the moment a film finished the settings gear became untappable — a control
   that exists and cannot be used, and the kind of fault that gets reported for
   months as something else. It is now a centred card with
   `pointerEvents="box-none"`, the transport row hides rather than being
   overlapped, and it gained **Watch again**: without that the only way to
   rewatch was to leave and come back, which resumes at the end and finishes
   immediately.
2. 🔴 **A React Query key collision crashed the player on every episode.**
   `Cannot read property 'seasons' of undefined`. The series lookup used
   `['title', 'series', slug]` — the exact key `TitleDetailScreen` already
   writes, with a different shape: its queryFn returns `{ detail, isReleased }`
   and mine returned `{ series, isReleased }`. **TypeScript cannot see this**,
   because a cached value is typed by whichever queryFn the compiler is looking
   at and query keys are not part of the type. Fixed by sharing the call as well
   as the key, which also means opening the player from a series page reuses
   that page's fetch instead of issuing a second one.
3. **The fixture gave video to two draft movies.** `Goodbye Monster` and
   `Blades of the Guardians` are `status=draft`, so `isReleased()` refused them
   whatever video they carried. Found by tapping a Continue Watching card and
   getting the refusal. The episode half of the same script had already been
   fixed for exactly this; the two selections were written separately, so the
   mistake was made twice.

**Not verified, plainly:**

- **Nothing on a real handset.** Emulator only, so the phase exit's
  "Tecno-class phone and a 10-inch tablet" is still untested.
- **Nothing against production**, which still 404s.
- **No EAS build.** `eas init` and `eas build` are Rio's account.
- **The remote keys are written and never pressed.** `useTVEventHandler` is
  inert on a phone build and there is no TV emulator here, so play/pause, the
  ten-second seeks and the menu key have been reasoned about only. Every
  control is a `Focusable` and the focus ring was seen on screen, which is the
  part that would have been expensive to retrofit — but the key handling itself
  is unproven and should be treated that way in Phase 4.
- **No real signed CDN URL.** The dev seed has placeholder paths and the local
  Bunny key is empty, so the player has only ever fetched from a local file
  server. Open question 9 in the wiki still gates this.
- **Dragging the seek bar was not exercised on the device.** It renders, fills
  and reports the right position, and its maths has unit tests; no finger has
  been dragged across it.
- **Playback speed was never changed on the device**, only seen in the menu.

**For whoever is next:**

- 🔴 **`expo prebuild --clean` had never been run in this repo, and the build
  was broken.** `mobile/.gitignore` ignores `/android`, so that directory has
  never been in git and every build until today was incremental against a tree
  generated months ago. The fix was deleting `~/.gradle/caches/9.3.1/transforms`
  (2.0 GB) and `build-cache-1`, then building with `--no-build-cache`. Keep
  `modules-2`: it holds the 279 MB React Native AAR and re-downloading that is
  the slow part. **A build that only ever works incrementally is a build nobody
  can reproduce**, and it is worth Rio hearing as a launch risk rather than as
  one session's bad afternoon.
- **Two things a local Expo module needs that are easy to miss.** SDK 57's
  autolinking does not default to `./modules` — the app's `package.json` must
  declare `expo.autolinking.nativeModulesDir` — and the module needs its own
  `package.json` or the scanner walks straight past the directory. Also
  `android.defaultConfig.versionName` is mandatory, and its absence is reported
  against `node_modules/expo/android/build.gradle` rather than against the
  module actually missing it.
- **The dev client resolves to port 8081 by default**, which is the Precious
  project's Apache. Launch it explicitly:
  `adb shell am start -n com.jambofilms.app/.MainActivity -a android.intent.action.VIEW -d "jambo://expo-development-client/?url=http://10.0.2.2:8093"`.
  A `127.0.0.1` URL there works only with a matching `adb reverse`.
- **Never combine a trailing `&` with the Bash tool's background flag.** It
  detaches the process from the wrapper, which exits reporting success while
  leaving an orphan holding the port. That is how a second Metro hit
  `EADDRINUSE` against my own first one.
- **Grid cards on the Movies and Series tabs did not respond to
  `adb shell input tap` at all**, while the same cards reached through search
  opened immediately. Not investigated, and possibly a scripted-input artefact
  rather than an app fault — but use search to reach a title deterministically
  rather than losing time to it.
- **The video fixtures are two different films on purpose**, so a quality
  switch visibly changes the picture. The low one is only ten seconds, so a
  data-saver session ends almost immediately: set the account to `auto` on wifi
  if you want time to test anything.
- `tools/dev-catalogue/video-server.mjs` must be running in its own terminal.
  `php artisan serve` answers a Range request with the whole file and a `200`,
  measured, so seeking through it would re-download the film.

### The uploaded photo did not appear, and it was two faults stacked

Rio uploaded a profile photo, the upload succeeded, and the circle showed his
initial instead. Two separate causes, one in the API and one in the local
environment.

**Fault 1, in the API, now fixed.** `avatar_url` was the only image field in
`/api/v1` returning an ABSOLUTE url. Everything else returns a path —
`poster_url` is `/storage/gallery/movies/...` — and the app resolves those
against whatever host it is talking to. `getFirstMediaUrl()` instead builds
its url from `config('app.url')`, which is the *website's* address and need
not be the API's: here the app talks to `10.0.2.2:8095` while `APP_URL` is
`http://localhost/Jambo`, so the photo came back pointing at a host the phone
has no route to. **This is not only a dev-box quirk** — a deployment behind a
different hostname, a proxy, or a subdirectory reproduces it, and no client
can correct it because it cannot know which part was host and which was path.
`App\Support\MediaUrl::relative()` now normalises it, with the deliberate
exception that a url on somebody else's host is returned untouched: several
titles here are Dropbox and Backblaze links an admin pasted, and rewriting one
points the client at a file this server does not have. Nine unit tests, and
the external-url guard proved by mutation and restored.

🔴 **Fault 2, local only, NOT fixed because fixing it moves Rio's media.**
`public/storage` is a **real directory, not Laravel's symlink**. It holds 126
poster files under `gallery/`, plus `media/` and `dev-video/`. Spatie
media-library writes uploads to `storage/app/public/<media id>/`, which
nothing serves — so **an avatar upload has never displayed on this machine**,
and `/storage/3/...` 404s while `/storage/gallery/...` works.

The two trees are NOT copies of one another: `public/storage/gallery` has 126
files and `storage/app/public/gallery` has 4. **So `php artisan storage:link
--force` would delete 122 poster images.** Do not run it.

The correct repair is to merge `public/storage/*` into `storage/app/public/`,
remove the real directory, then `storage:link` — and that moves files, so it
is Rio's call rather than something to do unannounced. Proved the rest of the
chain is sound by copying the single uploaded file into `public/storage/3/`:
both `/storage/3/...` and the `/img` proxy then returned 200. That copy is
still there and is the only thing making Rio's current photo visible.


### 2026-09-09 — Survey: rearranging the homepage sections (no code)

Rio is planning drag-to-rearrange for the homepage and asked for a scan of
what exists first. **Most of it already does, on both sides.**

`Modules/Frontend/.../Api/V1/HomeController::show()` already returns ONE
ORDERED LIST of rails with stable keys — `continue_watching`, `top_movies`,
`top_series`, `top_picks`, `smart_shuffle`, `latest_movies`, `latest_series`,
`fresh_picks`, `exclusives`, `popular_movies`, `international_series`,
`upcoming`, then category rails, genres, VJs and personalities. Its docblock
says the shape was chosen so a new rail needs no app release, and empty rails
are dropped. `HomeScreen.tsx` honours it: `renderableRails(data.rails)` then
`rails.map(...)`. **So the app needs no change for reordering**, which is
worth stating before anyone proposes building arrangement into the client.

**The only gap is storage.** The order is a literal array in the controller.
No migration and no settings row holds a custom one.

**The admin pattern exists three times already** — `admin/featured/index`
(newest, closest, itself copied from categories), `persons/PersonCategoies`,
and `Pages` footer fields, all SortableJS. A fourth hand-rolled drag list
would be the duplication Rio has asked us to stop.

🔴 **The constraint that decides the design, from jambo-69 and verified in
`mobile/src/api/catalogue.ts`: the rail keys are already a published contract
the app depends on in two more places.** `isNumberedRail()` keys the Top 10
numeral treatment on `top_movies` and `top_series`. `collectionKeyFor()`
decides whether a rail gets a "View all" and where it points — and **the rail
and collection key spaces do not match**: `/home` is underscored,
`/collections` is hyphenated, most convert by swapping the separator, three
rails have no archive, and `exclusives` maps to `only-on-streamit` by an
exception table with no rule behind it, pinned by unit tests.

So if a stored order keys on rail keys — and it should — then
`home_sections.key` is **a foreign key into a published contract**. Adding a
rail server-side stays free; **renaming one becomes a breaking change** that
must move the app's mapping with it, and the symptom of getting it wrong is a
silently dead "View all" rather than a loud failure. That belongs in the
migration's docblock.

**Also confirmed by jambo-69: resume survives an admin moving or disabling
Continue Watching**, and by design rather than luck — `RailFor` switches on
`rail.kind === 'progress'` and never on the key, and the Continue Watching
screen is a separate route calling `GET /continue-watching` directly. Disabling
the rail hides a shelf; it does not remove the ability to resume.

**Shape this reduces to, when Rio wants it:** a `home_sections` table of key,
label, position and enabled; an admin screen copying the featured drag list;
`HomeController` reading its order from that table instead of the array.
Categories already arrive through `categoryRails()` and slot in. Nothing in
the app changes.


**Plan written at Rio's request: `docs/plans/homepage-section-arrangement.md`.**
He asked for a plan and for a different session to build it before anything
else proceeds, so this session wrote it and did not start the work.

Two findings in it are worth repeating here because they change the estimate:

🔴 **The website and the API describe the homepage with two different, non-
matching vocabularies.** `ott-page.blade.php` renders `continue-watching`,
`recommended`, `top-ten-block`, `top-ten-tvshow`, `category-rails`, `vjs`,
`only-on-streamit`, `random-category-rail` at three fixed slots, `upcomming`,
`verticle-slider`, `Your-Favourite-Personality`, `tab-slider` and `geners`.
The API serves `top_movies`, `top_series`, `top_picks`, `smart_shuffle`,
`latest_movies`, `latest_series`, `fresh_picks`, `exclusives`,
`popular_movies`, `international_series`, `upcoming` plus taxonomy rails. The
website's `verticle-slider` and `tab-slider` are the **day**-based Top 10s,
which the API does not expose at all — its `top_movies`/`top_series` are the
7-day rails. So "one arrangement governing both surfaces" is not a small
change, and the plan recommends staging it with an ADR before the website half.

🔴 **`home_sections.key` would be a foreign key into a published contract.**
`catalogue.ts` keys `isNumberedRail()` and `collectionKeyFor()` on the literal
rail strings, and the rail and collection key spaces do not match — one pair,
`exclusives` to `only-on-streamit`, follows no rule at all. Adding a rail stays
free; renaming one is breaking, and it fails silently as a dead "View all".



### 2026-09-09 — Homepage section arrangement, stage 1 (API + admin drag)

**Status:** complete
**Owns:** Modules/Frontend/database/migrations/2026_09_09_180000_create_home_sections_table.php,
Modules/Frontend/app/Models/HomeSection.php,
Modules/Frontend/app/Http/Controllers/Admin/HomeSectionController.php,
Modules/Frontend/resources/views/admin/home-sections/index.blade.php,
Modules/Frontend/tests/Feature/HomeSectionArrangementTest.php
**Shares:** Modules/Frontend/app/Http/Controllers/Api/V1/HomeController.php — exact edit:
the rails array is wrapped in HomeSection::arrange(), nothing else moves;
Modules/Frontend/routes/web.php — exact edit: one admin route group for
home-sections index / reorder / toggle;
resources/views/components/partials/vertical-nav.blade.php — exact edit: one
nav-item directly after Featured; CHANGELOG.md; docs/worklog.md

**What this is:** stage 1 of docs/plans/homepage-section-arrangement.md. A
home_sections table, a HomeSection model, HomeController ordering and filtering
its rails by that table, and an admin drag screen copied from
admin/featured/index.blade.php. **Nothing under mobile/ is touched** — the app
already renders whatever order the API hands it, and another session owns that
tree right now.

**Status at close:** complete. Files owned turned out to be exactly as claimed,
plus CHANGELOG.md (1.8.36) and version.txt.

**Verified by running, not by reading:**

- 10 feature tests pass in `HomeSectionArrangementTest`: the seeded order is
  byte-identical to what the controller built before the table existed; a drag
  through the real `reorder` endpoint is the order `/home` returns; a disabled
  section is absent and returns when switched back on; a rail whose row was
  deleted sorts last instead of vanishing; disabling `continue_watching` leaves
  `GET /continue-watching` returning the item; the screen and both writes are
  admin-only; the screen lists disabled sections rather than hiding them; and a
  section with no row gets one when the screen loads.
- **Rendered the admin screen on a real server** (artisan serve on 8096,
  logged in over HTTP as a throwaway admin, since the only existing admin's
  password is unknown). 200, 147 KB, 16 rows, 16 switches, every section named
  from its translation (`Only on Jambo`, `AI Smart Shuffle`, `Best in Series
  This Week` — not raw keys), keys in the seeded order, SortableJS loaded once,
  and no model stringified into the layout.
- **Drove the whole feature live against MySQL**, not just SQLite: toggled
  `exclusives` off over HTTP and watched it leave `/api/v1/home`; toggled it
  back; reversed all 16 positions through the `reorder` endpoint and watched
  `/home` come back reversed; restored. A PATCH without the CSRF token returns
  419, checked by accident and worth knowing.
- The throwaway admin was deleted, the order restored to 0..15, and
  `enabled=false` count is back to 0. The shared DB is as I found it.

**Three guards proved by mutation, all restored, each confirmed restored by
re-running the test and by grepping for leftover markers:**

1. Dropping a rail with no row instead of sorting it last — the "sorts last"
   test failed on the exact assertion.
2. Removing the missing-table fallback — the new fallback test failed with
   `no such table: home_sections`, which is the identical symptom the
   coordinator reported from the field.
3. Ignoring `enabled` — both disable tests failed.

`opcache.enable_cli` is On here, so every restore was followed by `touch`.

**Not verified:**

- **Drag-and-drop was never exercised by a real mouse.** The SortableJS wiring
  is copied verbatim from the Featured screen and the endpoint behind it is
  tested directly, but nobody has dragged a row.
- No mobile-width render of the admin table, and no screenshot of any kind.
- Nothing in production. Nothing on a real device — the app was not run,
  because the app does not change.
- The `label` override is honoured on read and covered by no test, because
  nothing writes it yet (see below).

**Deliberately not built:**

- **A rename control for `label`.** The column exists because the plan
  specifies it and `arrange()` honours it, but there is no UI to set it. The
  reason is not laziness: every heading today comes from a `sectionTitle.*`
  translation key, so an admin override is a decision about what happens in a
  second language, and that decision was not mine to make inside a stage-1
  slice. The screen shows the effective heading, so the column is visible even
  though it is not editable.
- **Keyboard reordering.** SortableJS drag is mouse and touch only, so a
  keyboard user cannot reorder. Up/down buttons would fix it, but they would
  make this the only one of the product's four drag lists that has them, and
  fixing one screen is worse than fixing the pattern. Flagged as a fork for
  Rio rather than decided here — it should be done to Featured, Categories and
  the footer editor in one pass.
- **Anything under `mobile/`.** Nothing. The app already renders whatever
  order it is handed. Checked at close: `git status` shows no file of mine
  under `mobile/`, and the hunks in `HomeScreen.tsx` are another session's
  `onResume`/`resumeTarget` player work.
- **The website's homepage.** Stage 2, and it needs an ADR first — the two
  surfaces use non-matching section vocabularies and `ott-page.blade.php` is
  Streamit template Blade.
- **A second control for category order.** Categories already drag on their
  own screen; the block moves as one row here.
- **Caching the sections query.** One 16-row read on `/home`. A cache would
  only make an admin's drag take a TTL to appear. The number is in the
  docblock so the next person can see when that stops being true.

**For whoever is next, three things that cost me time:**

1. 🔴 **Land the migration before the code that reads it, or land the code with
   a fallback.** I did neither, and `/api/v1/home` 500ed for every session on
   this machine until the coordinator ran the migration. `arrange()` now
   degrades to the controller's built-in order when the table is missing or
   empty, and there is a test that drops the table and expects a 200. Three
   sessions share this tree AND this database: a broken shared endpoint between
   two of your edits is not a private intermediate state.
2. **Calling `/api/v1/home` in a test leaves the default guard set to
   `sanctum`.** `IdentifyApiViewer` does `Auth::shouldUse('sanctum')` and it
   persists for the rest of the test process, so a later `actingAs($user)`
   against a web route dies with `RequestGuard::viaRemember does not exist`.
   Pass the guard explicitly: `actingAs($user, 'web')`.
3. **Do not switch users inside one test.** `AuthenticateSession` invalidates
   the session on the second `actingAs` and the request 302s to login, which
   looks exactly like an authorization failure and is not.

4. **Kill a dev server by port or PID, never by name pattern.** Cleaning up
   my `artisan serve` on 8096 I matched on the command line and killed the
   `artisan serve` wrappers of all three sessions running here, including
   8090 and 8095 which are not mine. No lasting harm: the wrapper is not the
   HTTP server, the `php -S` child is, and both of those kept listening --
   verified 200 on /api/v1/home for 8090 and 8095 afterwards. But those two
   sessions have lost their serve console output and auto-restart, so if a
   dev server seems to have stopped reporting, that is why: restart it.

Also worth knowing: the plan was accurate on every code fact I checked except
one omission — it does not mention that `Modules/Frontend` had no admin routes,
controllers or `app/Models/` directory at all, so all three were created here.


### 2026-09-09 — Phase 2 slice 2d: billing, invoice, membership, password, 2FA enrolment (session jambo-a6)

**Status:** in progress
**Owns:** mobile/src/features/billing/** (new), mobile/src/features/security/**
(new), tools/dev-catalogue/10-billing-orders.php (new),
Modules/Subscriptions/tests/Feature/SubscriptionOrdersApiTest.php (new),
tests/Feature/ProfileHubBillingTest.php (new)
**Shares — exact edits, nothing else in these files:**
- `mobile/src/navigation/types.ts` — four route entries: `Billing`,
  `Invoice { reference }`, `ChangePassword`, `TwoFactorSetup`.
- `mobile/src/navigation/RootNavigator.tsx` — the four matching
  `AppStack.Screen` lines, appended beside `Plans` and `Security`.
- `mobile/src/screens/PlansScreen.tsx` — the "Current plan" card from
  `membership.blade.php` added above the existing plan ladder, plus one
  "Order history" row. The ladder itself is not touched.
- `mobile/src/screens/SecurityScreen.tsx` — the password row, and the
  two-factor card gains its pending / enabled states, recovery codes,
  regenerate and disable. **This deletes "Turn this on from the Jambo
  website" and "Passwords and two-factor setup are changed on the Jambo
  website."**
- `mobile/src/screens/ProfileMenuScreen.tsx` — **jambo-51's file.** ONE row,
  `{ key: 'billing', label: 'Billing', icon: Receipt, to: 'Billing' }`,
  directly after `membership`. Messaged before editing.
- `mobile/src/ui/profileMenu.ts` — `activeRowFor` gains `Billing: 'billing'`
  and `Invoice: 'billing'`. One map, two lines.
- `mobile/package.json` + `package-lock.json` — `react-native-qrcode-svg`
  only. Pure JS on top of `react-native-svg`, which is already a dependency
  and already linked, **so no prebuild.**
- `Modules/Subscriptions/app/Http/Controllers/Api/V1/SubscriptionController.php`
  — `card()` only.
- `app/Http/Controllers/ProfileHubController.php` — `billing()` and
  `invoice()` eager-load only.
- `resources/views/profile-hub/billing.blade.php`,
  `resources/views/profile-hub/invoice.blade.php` — the `$tierName`
  expression only.
- `docs/api/openapi.yaml` (PaymentOrder schema), `CHANGELOG.md`,
  `docs/worklog.md`.

**NOT touched:** `mobile/src/ui/theme.ts`, `design/tokens.json`,
`design/export-tokens.mjs`, `mobile/src/ui/list.tsx`,
`mobile/src/api/endpoints.ts`, `mobile/src/ui/AppHeader.tsx`. The status badge
spends `colors.okBg/okText/okBorder` and `colors.warning`, which already exist
and already mean exactly this. No new probe, no raw hex, and no collision with
jambo-51 in the file three sessions keep meeting in.

**version.txt is NOT bumped for the app half.** The PHP half is a webapp
change and takes a CHANGELOG entry and a version bump on its own terms.

**What this is:** the five account screens the website has and the app does
not. Two of the five turned out to be completions rather than new screens:
`PlansScreen` already renders the "All plans" half of `membership.blade.php`
from the same `GET /subscription` call, and `SecurityScreen`'s own docblock
had already scoped enrolment and password change into a later slice. Building
a second screen beside either would have shown a viewer the same facts twice.

🔴 **A live-site defect found by reading the page I was told to port.** The
website's own Billing and Invoice pages throw a 500 for any account that has
ever paid:

    RelationNotFoundException: Call to undefined relationship [tier]
    on model [Modules\Subscriptions\app\Models\SubscriptionTier]

`ProfileHubController::billing()` eager-loads `payable.tier` and both blades
read `$order->payable?->tier?->name`, but `payable` **is** the
`SubscriptionTier` and that model has no `tier` relation. Reproduced against
the real MySQL database through the controller's own query, not inferred. An
account with no orders sees the empty state and is fine, which is why five
months of orders have not surfaced it. Rio agreed to the repair.

🔴 **`GET /subscription/orders` cannot say what was bought.** `card()` returns
a `description` field, and `payment_orders` has no such column, so it is null
on every order and always will be. No plan name, no billing period, no payment
method, no tracking id — all four of which the website's invoice prints. The
resource is extended and the dead field removed; nothing but the app reads it,
and Phase 1 is not deployed.

**Deliberately not built in this slice:**
- **The invoice Print button.** `window.print()` has no native equivalent and
  `expo-print` is a native module, which means a prebuild. Named, not faked.
- **The Deactivate account card** on `security.blade.php`. `DELETE /account`
  exists and is not in this slice; it is destructive and earns its own care.
- **Any Subscribe or Select control.** ADR-0004, and the `direct` PesaPal flow
  still does not exist server-side. The website's plan grid links to the
  pricing page; the app states where payment happens instead.
- **Everything in Phase 3**, and the Downloads screen, which has no website
  counterpart.

### 2026-09-09 — One shelf, one name: the app and the website disagreed

Rio sent a full-page capture of the live `jambofilms.com` and asked why the
app's home screen differs, "before we confuse the app devs". It did differ,
in three ways, and one of them was a straight defect.

**Verified against the live capture, not by reading Blade.** Live order:
hero, Continue Watching, AI Smart Shuffle, Top 10 Movies This Week, Top 10
Series This Week, New Releases, VJs, Only On Jambo, Indian Movies, the
"#4 in Movies Today" slider, Your Favourite Personality, Animation Movies, the
"#7 in Series Today" slider, Genres, Korean Movies.

🔴 **The same rail carried two different headings**, which is what made the
surfaces look like different products:

| Rail, identical data | Website | App |
|---|---|---|
| Top 10 movies, 7 day | `sectionTitle.top_ten` → "Top 10 Movies This Week" | `movies_to_watch` → "Movies to Watch" |
| Top 10 series, 7 day | `top_10_tvshow_to_watch` → "Top 10 Series This Week" | `best_in_tv` → "Best in Series This Week" |
| Upcoming | `sectionTitle.upcoming_title` | `widgets.Upcoming` |

**Fixed by making the website's wording win**, because it is what viewers
already read. Three heading keys changed on the API side; no app release
needed, since headings travel in the response.

🔴 **`sectionTitle.upcoming_title` did not exist at all**, so the live site
renders the literal string "sectionTitle.upcoming_title" as a heading the
moment that rail has anything in it. Invisible today only because it is empty.
Added to `lang/en/sectionTitle.php` rather than repointing the Blade — a
language file is a config point the template provides, and its layouts are not
ours to edit.

**The root cause was a duplicated list, and that is what was actually
cleaned.** `HomeSection::DEFAULTS` mapped every section key to a translation
key, and `HomeController` separately repeated the same headings as inline
`__()` calls. Two lists that had to agree and did not. `DEFAULTS` is now the
only place a heading key lives, `HomeSection::headingFor()` resolves it, and
the controller asks rather than repeats. **Adding a section means adding one
row to `DEFAULTS` and nothing else.**

**What caught it:** the agent's own test, "every rail carries the heading its
section row names", failed the moment the controller changed. It exists to
assert the admin screen and the API agree, and it earned its place — without
it the fix would have half-landed and the admin screen would still have read
"Movies to Watch".

**Verified:** 20 tests green across `HomeTest` and `HomeSectionArrangementTest`;
the live API now returns "Top 10 Movies This Week" and "Top 10 Series This
Week", matching the website exactly; nothing anywhere still references the old
strings.

**Not fixed, and still the open question for stage 2:** the two surfaces still
show a DIFFERENT SET of rails in a different order. The website interleaves
category rails at fixed slots and carries two full-width day-based sliders the
API does not expose; the app carries Latest Movies, Latest Series, Fresh Picks,
Popular Movies and Top Picks, none of which are on the live page. Naming is now
consistent; composition is not. `docs/plans/homepage-section-arrangement.md` §3
carries the evidence.


### 2026-09-09 — Broken images locally: an unfinished migration, not a new fault

Rio reported broken images on the hero thumbnails, the Top 10 vertical slider
and the Genres rail.

**Diagnosed, and it is local-only.** The image URLs are bare app-absolute
paths — `/storage/gallery/the%20plus%20one/download%20(9).png`. Proved by
request: `http://localhost/storage/...` returns **404** while
`http://localhost/Jambo/storage/...` returns the PNG. This install lives in a
subdirectory; the browser resolves a leading `/` against the host root and
loses the `/Jambo` segment. **jambofilms.com is at the domain root, so the same
markup is correct there** — Rio's own live capture shows every image loading.

**The real cause is an incomplete migration, visible in git.**
`4fa86a8` routed all media through `media_url()`. `6a938ff` ("1.6.0 — Perf:
on-the-fly image resize + WebP via /img proxy") then introduced `media_img()`,
which builds `url('img/' . $path)` and **strips the install's base path**, with
a comment in `app/helpers.php` naming this exact XAMPP subdirectory problem.
The rails were moved over; **roughly 24 call sites across 23 views were not**,
and those are precisely the ones that break: hero-thumb, genres-card,
top-ten-card, personality-card, cast, card-style, continue-watch-card,
episode-card, vertical-banner, vertical-thumb, tab-series-slide, and the
detail/watch pages.

🔴 **`media_url()` must NOT be "fixed" to return an absolute URL.** It is what
`CatalogueController` and `TaxonomyController` use to build `poster_url`,
`cover_url`, `image_url` and `photo_url` for the mobile app, which needs a
PATH so it can resolve against whatever host it is talking to. Making it
absolute would reintroduce, across the whole catalogue, exactly the fault
fixed for `avatar_url` earlier today. The bug is in the views assuming an
app-absolute path is browser-resolvable, and the fix is to finish the
migration to `media_img()`.

Finishing it also buys the resizing and WebP those 24 sites never got, which
is a real gain on production rather than only a local repair.

**"Popular on Jambo" is NOT a bug.** `vertical-banner.blade.php` shows
`#{rank} in Movies Today` when a title has a rank today and falls back to
"Popular on Jambo" when it does not — its own comment reads "no rank it did
not earn today". Live shows a rank because it has real traffic; the local
database has no view data to rank on, so the fallback is correct and is the
same never-invent-a-value discipline used everywhere else here. Confirmed live
still renders "in Movies Today".


### 2026-09-09 — Finishing the media_img migration (Rio: "fix them")

**Owns, for this slice:** the seven view files below plus six dead assignments.
**Not touching:** `app/helpers.php`, `media_url()` itself, or any
`@section('seo:image', ...)` — see below for why each is deliberate.

Survey first, because the count was smaller than the grep suggested. Of 25
`media_url()` calls in views:

- **11 are `@section('seo:image', ...)` and are already correct.** They feed
  `og_image_meta()`, which absolutises against `app.url` AND strips the
  subdirectory base segment itself. Open Graph needs an absolute URL and must
  not go through the `/img` proxy, so these stay.
- **6 are dead assignments** left behind when 1.6.0 moved the rendered tag to
  `media_img()` and forgot the variable above it: `card-style`,
  `continue-watch-card`, `episode-card`, `genres-card`, `personality-card`,
  `top-ten-card`. Deleted rather than converted — converting dead code would
  have doubled the work and left the same clutter.
- **7 are genuinely rendered and genuinely broken**, and are the ones Rio saw:
  `card-genres-grid` (the Genres rail), `hero-thumb` (the hero thumbnails),
  `vertical-banner` and `vertical-thumb` (the Top 10 slider), plus `cast`,
  `tab-series-slide` and `tranding-tab`.


**Done and verified.** Seven rendered call sites converted to `media_img()`
plus the Cast detail page found on a second pass, and six dead assignments
removed after proving each variable was unreferenced.

Widths follow the conventions already in these views rather than being
invented: **640** for a card or tile, **384** for a thumbnail or avatar,
**1280** for a large slide. Every conversion keeps its existing fallback image
and legacy directory argument.

Verified by request, not by reading:
- The hero thumbnails and Genres tiles now emit
  `/img/storage/...?w=384&fm=webp` and `?w=640&fm=webp`, and those URLs return
  **200 image/webp**.
- **Every image on the homepage checked on the URL Rio actually uses**,
  `http://localhost/Jambo/`: **14 of 14 return 200, none broken.**
- The homepage itself still returns 200 after `view:clear`.

Nothing calling `media_url()` remains in the views except the `seo:image`
sections and three comments that mention it by name.

**Side benefit worth stating:** these images never went through the resize
proxy, so this is not only a local repair — production now serves them as
sized WebP instead of full-resolution PNG. The Genres tile alone was a
full-size gallery JPEG.



### 2026-09-09 — The Watchlist screen, from Rio's mockup (session jambo-wl)

**Status:** complete
**Owns:** mobile/src/features/watchlist/** (new: the screen, the card, the pure
list helpers and their tests), mobile/src/ui/format.ts (new),
Modules/Streaming/app/Services/WatchlistPlayResolver.php (new),
tools/dev-catalogue/11-test-user-watchlist.php (new)
**Shares — exact edits, nothing else in these files:**
- `mobile/src/screens/WatchlistScreen.tsx` — deleted. Its replacement is
  feature-shaped under `features/watchlist/`, which is what CLAUDE.md asks new
  work to be.
- `mobile/src/navigation/TabNavigator.tsx` — one import path.
- `mobile/src/ui/theme.ts` — one appended `watchlist` token block. **Appended,
  not reordered:** jambo-51 and jambo-69 are both in this file and three of us
  rewriting it is how a token gets lost. The `auth`, `profileMenu`,
  `profileBanner` and `player` blocks are not touched.

**What this is:** Rio's mockup of My Watchlist — an Edit action, Movies /
TV Shows / All filter tabs, an item count, a sort control, and cards that
carry a play mark, a meta line and a per-item menu. He asked for the loop:
render, compare, change, render again.

**Three things in the mockup had nothing behind them. All three were put to
Rio rather than faked, and he chose the conservative option on each:**

1. **Wide landscape key art.** `GET /watchlist` sends `poster_url` only, which
   is the 5:7 portrait poster. Offered `backdrop_url` on the payload with the
   poster as fallback — the chain `HomeRailsService:341` already uses for the
   Continue Watching still. **Rio chose portrait posters**, so no PHP changes
   and the card art is the art the app already draws everywhere else.
2. **"2023 · Action · 2h 49m" and "1 Season · Drama".** Year and runtime are on
   `MovieResource::card()`; **genre and season count are on neither card
   shape.** Worth knowing: `WatchlistController::index()` already eager-loads
   `genres` for both Movie and Show and then throws them away, so the genre was
   one line. **Rio chose only what exists today** — year and runtime for a
   movie, year for a series. Nothing invented.
3. **A Home / Search / Downloads / Profile tab bar.** The app ships the
   website's own bar (Home / Movies / Series / Watchlist) and Downloads is
   Phase 3, not started. **Rio chose to leave the bar alone**, so a Downloads
   tab does not appear over an unbuilt screen and the navigation port stands.

**Decided here rather than asked, and why:**
- **No back arrow.** Watchlist is a tab root and the profile menu's Watchlist
  row navigates to the *tab* (`ProfileMenuScreen.tsx:310`), so nothing ever
  pushes this screen. An arrow with nothing behind it is a dead control.
- **The mockup's crimson becomes the brand blue.** Same call, same reasons, as
  `profileMenu` in `theme.ts`: the accent is `auth.buttonFrom`/`buttonTo`, the
  gradient the sign-in button and the menu's active row already draw, so there
  is one pair of values and the screen cannot drift away from the brand.
- **Two columns, not the grid's three.** `PosterGrid` floors cards-per-view,
  which is 3 on a phone. These cards carry a title, a meta line and a 44dp menu
  button; at 118dp a kebab and a meta line collide. The column count is derived
  from a minimum cell width instead, so a tablet still gets four.

**Status at close: complete.** Files owned turned out as claimed, plus
`tests/Feature/Api/V1/SubscriptionAndReferralsTest.php` (the API order tests
went into the existing file rather than a new one — one home per subject) and
`CHANGELOG.md` + `version.txt` for the webapp half. `npm run check` green:
api:check, tokens:check, typecheck, lint, **182 app tests** (161 after my
files, 127 before this slice; the remainder are jambo-51's).

**A convergence worth noting:** jambo-51 independently created
`mobile/src/features/watchlist/` while I was creating
`mobile/src/features/{billing,security}/`. Two sessions reached the same
structure from Rio's ruling without coordinating it. That is the ruling
working, and `src/features/` should now be treated as the settled home for new
work rather than as one session's idea.

**Verified by rendering on the emulator (API 35, 1280x2856) against the
`php artisan serve` on 8095 and the real dev database — every one of these was
watched on screen:**

- **Billing** lists four orders newest-first with the plan name, the date, the
  amount grouped as `UGX 150,000.00`, a green Completed or amber Pending badge
  and a chevron. Portrait AND landscape.
- **The empty state**, with the fixtures removed: "No orders yet" and a Browse
  plans control that was pressed and **landed on Membership**. Fixtures
  restored afterwards.
- **The invoice** for a completed order: reference, Billed to with the real
  name and email, date, status, method, tracking, the plan line item with
  "Yearly subscription" under it, and the total.
- **The invoice for a PENDING order omits Method and Tracking entirely.** The
  word "null" appears nowhere. That is the case fixture 0003 exists for.
- **Membership** shows the current plan card the website has: Premium Monthly,
  an Active badge, Started and the renewal date, above the existing ladder.
- **The two-factor flow end to end, with a real code.** The secret shown on
  screen was byte-identical to the one in the database; the QR rendered; a TOTP
  computed from that secret with the server's own Google2FA was typed in and
  accepted; eight recovery codes appeared behind an acknowledgement; the
  database then read `enabled: YES`.
- **Regenerating codes** replaced all eight, and the batch on screen matched
  the database exactly.
- **Disabling** demanded the password, took it, and left `enabled: no,
  secret: null`. **The account is as I found it: two-factor off.**
- **The change-password form takes typing in all three fields** (the elevation
  trap is not present) and a wrong current password renders "The password is
  incorrect." **under the Current password field**, not as a banner. The
  password itself was NOT changed — see below.

**Verified on the live-site half by HTTP, including by mutation:**

- The billing page returns **200** with the real plan names, three Completed
  and one Pending.
- Put the fault back and the same request returned **500** carrying
  `RelationNotFoundException` and `undefined relationship [tier]`. Restored,
  200 again.
- The invoice page renders the plan, "Yearly subscription" and the tracking id.
- 19 PHP tests green across the two files.

**Eight guards proved by mutation, every mutation restored and each restore
confirmed by re-running the test AND grepping for the marker:**

1. The `instanceof SubscriptionTier` guard in `card()`.
2. The dead `description` field staying removed.
3. The website eager-load repair — two populated-page tests fail and both
   empty-state ones pass, which is the exact signature of why nobody noticed.
4. `formatMoney` not doing arithmetic.
5. `optionalDetail` returning null for absent values.
6. `statusTone` treating only `completed` as a success.
7. `otpauthUri` escaping the holder.
8. `sanitiseCode` capping at six digits.

🔴 **The harness caught two of my own tests being green for the wrong reason,
and both are now real:**

- `test_an_order_that_is_not_a_plan_has_no_plan_block` used an order with NO
  payable, so `$order->payable` was null either way and removing the
  `instanceof` guard left it passing. It now also exercises an order whose
  payable is a real model that is not a tier, which is the only shape that
  fails without the guard.
- `never recomputes the digits` used `12345678901234.56`, which is inside
  `Number.MAX_SAFE_INTEGER`, so replacing the string grouping with
  `Number(digits).toLocaleString()` changed nothing. It now pins
  `99999999999999999.99`, which a float turns into `100,000,000,000,000,000`.

**This is the third slice running in which the mutation pass found a weak test
of the author's own** — 2c found one too. It is the step actually earning its
cost.

**Not verified, plainly:**

- **The password was never actually changed on the device.** Doing it revokes
  every device on the account except the calling one, and jambo-51 has a
  session on this same test account, so it would have signed them out
  mid-session. Only the refusal path was exercised on screen. The success path
  is pinned server-side in `AccountAuthTest`, which existed before this slice.
- **Nothing on a real handset**, and nothing against production, which still
  404s.
- **No real payment ever produced these orders.** They are fixtures from
  `tools/dev-catalogue/10-billing-orders.php`, so the shape of a genuine
  PesaPal order — what `payment_method` and `order_tracking_id` actually
  contain in the field — is still unseen.
- **The QR was never scanned by an authenticator app.** It renders and the URI
  is pinned character for character against the server's builder, but no phone
  camera has read it. The manual-key path WAS proved end to end, and that is
  the path a handset viewer actually uses.
- **`react-native-qrcode-svg` is new**, pure JS on top of the already-linked
  `react-native-svg`, so no prebuild was needed and none was run. It rendered
  on the emulator and has not been through a release build.
- **No screen-reader pass.** Labels and grouped announcements are written and
  were not heard.

**Left behind on purpose:** four `DEV-BILL-` orders on the test account, so the
next session can render Billing and the invoice without rebuilding fixtures.
Remove them by running `tools/dev-catalogue/10-billing-orders.php` with
`JAMBO_BILLING_RESET=1` set. Nothing outside that prefix is touched.

**For whoever is next:**

- 🔴 **A page that only renders for an empty list has not been tested.** The
  billing 500 survived because every test that touched it used an account with
  no orders. When pinning a page that lists things, the case that matters is
  the populated one. This is the second time in this repo a fault hid behind a
  path nothing exercised.
- **The BEL-byte trap bit again, in Markdown this time.** Writing a namespace
  through a Python string inside a shell heredoc turned the escape into 0x07
  and the CHANGELOG read `Subscriptionspp`. It is not only a PHP problem — it
  is any backslash in any file written through a shell string. Repaired by
  rewriting the byte; use the Write or Edit tool for anything with a namespace
  in it.
- **Never combine a trailing ampersand with the Bash tool's background flag.**
  Slice 2c wrote this down and I did it anyway. Metro on 8094 detached from its
  wrapper, which exited reporting success; I kept its PIDs so it could be
  killed by PID rather than by name pattern.
- **Metro ports 8081, 8092 and 8093 were all taken**, so this slice used 8094.
  Check with `Get-CimInstance Win32_Process` before choosing one.
- **`adb shell input tap` against a screen that re-renders on tap is a race.**
  Half a dozen taps landed on whatever had moved into those coordinates, twice
  navigating somewhere else entirely. Dump `uiautomator` and tap in the same
  command, and re-dump after anything that changes the layout.
- **The payment method renders exactly as the server sends it** — lowercase
  `card`, `mpesa`, `airtel` — because the website's invoice does the same and
  the app does not know the correct casing of a payment brand. "Mpesa" would be
  wrong for M-Pesa. If Rio wants them titled, that is a server-side display
  decision rather than a client one.

### Reopened the same day — Rio's asset ruling, and what it caught

Rio, 2026-09-09, after the screens above were built and verified:

> please tell all the other agent to use the orignal cards as they are in the
> webapp with the exack design and behavier and size for all screens we don't
> want to use foreign design here. we just have to keep things orignal, yes we
> will get inpirational Ui screen to improve on ui experiece but it does not
> overide our existing asets that reused like cards, banners, buttons etc

and then: *"we need this enforced even in the OS."*

**It landed on two components in this slice, and he was right about both.**

1. **The order status badge was approximated, not captured.** It drew a hollow
   outline built from the semantic `okBg/okText/okBorder` and `warning` tokens.
   The site paints a **solid fill**. It looked reasonable on the emulator and
   it was not the product.
2. **The invoice's line-item table was hand-built** beside the site's
   `table.table-dark` instead of wearing it — its own radius, its own divider
   colour, its own cell padding.

Both are now read from probes. `design/export-tokens.mjs` gained a second
signed-in page, the profile hub's billing page, discovered from a link the
signed-in header renders rather than hard-coded. It had to be a real hub page:
`.jambo-hub-card` is defined in a `<style>` block inside
`profile-hub/_layout.blade.php`, so it exists on no public page and cannot be
read by injecting a class into the home page. The badges themselves ARE
injected, because which badges a hub page renders depends on what the capture
account has bought, and a fresh account has none.

**Tokens went from 117 to 143.** Twenty-six new ones, all hub and badge values,
and no existing token moved.

🔴 **The capture found a live accessibility defect that faithful porting would
have shipped.** The site's `.badge.bg-warning` is white on `#ffd81c`:

| Badge | Contrast | WCAG AA needs |
|---|---|---|
| Completed, white on `#27ae60` | 2.87 | 4.5 |
| **Pending, white on `#ffd81c`** | **1.39** | 4.5 |
| Pending, black on the same yellow | 15.09 | 4.5 |

So the website's own Pending badge is effectively unreadable. Raised with Rio
rather than copied: **his call was keep the site's fill and darken the text**,
and report the contrast defect for a separate website fix so the two surfaces
converge instead of drifting. That is the ONLY value in the `badge` block that
deviates, and the deviation lives in `theme.ts` beside its reason so there is
one place to undo it when the site is fixed.

**A defect the typecheck caught in the exporter.** `font.weight` is unitless
and `toNumber()` requires a `px` suffix, so `put('font.weight.badge', ...)`
silently wrote nothing — exactly like the two existing weight mappings, which
already used `Number()` directly for this reason. It surfaced as a compile
error in `theme.ts` rather than as a badge rendering at the wrong weight,
which is the whole argument for naming every token in one typed file.

**Copy trimmed, per Rio's other ruling the same day** (relayed by jambo-e1, who
recorded it in `~/.claude/skills/screen/SKILL.md` §5). Gone: the sentence under
Billing's "Order history", the sentence under "Current plan", the one under
"All plans", the second line of the billing empty state, and the paragraph
explaining what an authenticator app is. **Kept, because each carries a fact a
viewer cannot see:** the password screen's warning that changing it signs out
every other device, the pending-2FA line explaining that a secret exists and
nothing has confirmed it, the recovery-code warning, and the two confirmations
before a destructive action. Accessibility labels untouched.

**Where the ruling is recorded**, since Rio asked for the OS specifically:
`~/.claude/skills/screen/SKILL.md` §4a, so it loads in every session in every
folder, and `D:\OS\decisions\log.md` as a dated entry beside the copy ruling.
Relayed to every live session on the machine.

**Re-rendered after the rewire**, portrait, on the emulator: Billing with the
real green and yellow fills and no blurb, and the invoice with the site's own
table ground, divider, cell padding and 16px type. `npm run check` green.

**Membership re-rendered too**, once jambo-e1 handed the emulator back: the
site's solid green Active badge, the header reading Membership rather than
Plans, and the trimmed copy. All three screens now wear captured assets and
all three have been seen.

**A note on sharing one emulator.** Two sessions drove `emulator-5554` at once
for a few minutes and both of us landed on the other's screens; one of my taps
toggled jambo-e1's Watchlist into edit mode, which I put back. If a second
session is rendering, either take a second AVD (`kadson_dev` and
`kangaru_perf` exist on this machine) or agree a handover. Coordinates dumped
from `uiautomator` are worthless the moment somebody else navigates.

### 2026-09-09 — The Notifications screen, from Rio's mockup (session jambo-nt)

**Status:** in progress
**Owns:** mobile/src/features/notifications/** (new: the screen, the settings
screen, the row, the Phosphor icon map, the pure helpers and their tests),
Modules/Notifications/app/Support/NotificationCategories.php (new),
Modules/Notifications/tests/Feature/NotificationCategoryApiTest.php (new),
tools/dev-catalogue/12-notifications.php (new)

**Shares — exact edits, nothing else in these files:**
- `mobile/src/screens/NotificationsScreen.tsx` — deleted. Its replacement is
  the feature folder above, per CLAUDE.md: new work lands feature-shaped.
- `Modules/Notifications/app/Http/Controllers/Api/V1/NotificationController.php`
  — `card()` gains `key` and `category`; `index()` gains a `category` filter.
  Nothing else in the controller moves.
- `mobile/src/navigation/RootNavigator.tsx` and `navigation/types.ts` — the
  Notifications import repointed, one `NotificationSettings` route added.
- `mobile/src/api/endpoints.ts` — the notifications block only: a category
  argument and a cursor, per-row read and delete, and the preferences pair.
- `design/export-tokens.mjs` — one new signed-in page (the hub's own inbox)
  and its probes. No existing probe touched.
- `design/tokens.json`, `mobile/src/ui/tokens.json` — regenerated.
- `mobile/src/ui/theme.ts` — one appended `notifications` block.
- `docs/api/openapi.yaml` — the Notification schema and the index parameters.
- `mobile/src/api/schema.d.ts` — regenerated from the spec.

**What this is:** Rio's mockup of 2026-09-09 — a filtered notification inbox
with day sections, an unread dot, a gear, and a row carrying either a poster
or a coloured icon tile.

**Three of its elements had nothing behind them, and all three went to Rio:**

1. **The "Offers" chip.** No promotional notification type exists; the module
   groups its 33 types as account, billing, content, monetization and admin.
   **His call: keep the chip and map it to admin broadcasts**, which is how a
   promotion actually reaches a viewer today.
2. **The gear.** No notification-settings screen existed. **His call: build
   one**, from the website's own "Delivery preferences" card, which is on the
   same hub page and whose API was already built.
3. **The thumbnail.** The mockup draws a large landscape image; the website's
   `.jambo-hub-inbox__image` is 40x40 at 10px radius. **His call: hold the
   site's 40x40 exactly** — the asset ruling wins over the drawing.

**The mockup's crimson becomes the brand blue.** Fourth time, and this time
the site settled it rather than the brand guide: `.jambo-hub-inbox__row.is-unread`
is already a blue tint, `rgba(26, 152, 255, 0.07)` on a `rgba(26, 152, 255, 0.18)`
border. The unread state was captured, not chosen.




**Three more shared files than the claim above listed, all named before the
edit and all minimal:**
- `Modules/Frontend/app/Http/Controllers/FrontendController.php` — the episode
  selection inside `watchlistSeriesPlay` only, replaced by a call to the new
  resolver. Redirects, flash messages and the prev/next queue logic untouched.
- `Modules/Streaming/app/Http/Controllers/Api/V1/WatchlistController.php` —
  `index()`'s eager loads, and one new private `watchlistCard()`.
- `mobile/src/ui/rails/Focusable.tsx`, `mobile/src/ui/components.tsx`,
  `mobile/src/api/{catalogue,endpoints}.ts`, `docs/api/openapi.yaml`,
  `mobile/src/api/schema.d.ts` (regenerated), `CHANGELOG.md`.
- `mobile/src/screens/TitleDetailScreen.tsx` — jambo-69's file. Its private
  `formatRuntime` was deleted and imported from `ui/format.ts` instead, because
  this screen needed the same five lines and a second copy is how two runtimes
  come to disagree. Nothing else in that file was touched.

---

## How this ended up different from where it started, which is the useful part

**The first version was wrong in a way that passed every check.** It had 21
green unit tests, clean types, clean lint and a rendered screenshot that
matched Rio's mockup closely. It was still wrong, and both corrections came
from him looking at the screen rather than from anything the tooling could
have caught.

**Correction 1 — the copy.** Every heading had a sentence under it explaining
the heading. Rio: *"we don't want too much wording, we only need three, two
word paragraphs... otherwise all these screens are self explanatory."* Now in
`~/.claude/skills/screen/SKILL.md` §5 and `D:\OS\decisions\log.md`.

**Correction 2 — the card.** The card carried a title, a meta line and a
three-dot menu, none of which exists in the web app. Rio: *"use the original
cards as they are in the webapp with the exact design and behaviour and size
for all screens, we don't want to use foreign design here."* Then, on the
first attempt at that: *"yes they are three cards but you have used big
spaces, which is nothing related to our webapp cards and design"* — the card
had been pinned to the rail's 3.5-across width and then laid out in three
whole columns, so the leftover became gutters three times the site's.

**What the second correction turned up is worth more than the screen.** Going
to `profile-hub/watchlist.blade.php` to copy the card showed that the website
has always rendered genre, runtime and season count on a watchlist card, and
that `ProfileHubController::watchlist` eager-loads a `seasons_count` precisely
so it can. Earlier the same day I had put a fork to Rio — "genre and season
count are on neither card shape, add them or show only what exists?" — and he
chose to show only what exists. **That fork was badly framed.** The question I
asked was about the API; the honest third option was parity with the page we
already ship, and I had not looked at the page. Filed as a standard in
`screen` §1: *the API not having it is not the same as the product not having
it.*

**Verified, by running it:**
- Rendered on emulator-5554 against the API on 8095 with six real titles
  seeded by the new `11-test-user-watchlist.php`. Movies tab, TV Shows tab,
  Edit mode with selection and the remove bar, and the sort control.
- The payload, by calling `WatchlistController::index` directly: two genres per
  row, `seasons_count` 2 on both series, and a resolved `play` target on all
  six — the two series resolving to episodes 31 and 37 rather than to their
  first.
- **Both new server guards proved by mutation.** Removing the resume branch
  fails `play_on_a_saved_series_resumes_the_unfinished_episode`; ignoring the
  release check fails `an_unreleased_movie_offers_nothing_to_play`. Both
  mutations restored and the file greps clean of the marker.
- Mobile: 191 Jest tests, `tsc --noEmit`, eslint, and `api:check` (schema
  matches the contract). PHP: 21 in `TaxonomyAndWatchlistTest`.
- Two contrast defects measured and fixed before shipping: white on the
  destructive fill was 2.78:1 against the 4.5:1 body text needs, and the
  white selection tick on `auth.buttonFrom` was 2.90:1 against the 3:1 a
  meaningful graphic needs. Now 5.67 and 5.30.

**NOT verified:**
- **The tablet and television column counts.** `columnsFor` returns 4 at 768dp
  and 8 at 1280dp by asking the site's ladder, and that is unit-tested, but
  nothing has been rendered at either width.
- **Play from a card, end to end.** The resolver's target is proven by test
  and by the live payload; nobody has pressed a watchlist poster and watched a
  video start. The player itself is jambo-69's slice and was verified there.
- **Removing a title through Edit mode.** The mutation is wired and
  `removalTarget`'s series → show translation is unit-tested, but no row was
  actually deleted on the device — I did not want to empty the fixture
  mid-loop. `JAMBO_WATCHLIST_RESET=1` on step 11 restores it if someone does.
- **The website's own `/watchlist/series/{slug}` after the refactor.** Its
  episode selection now comes from the resolver and the API tests cover that
  service, but the web route was not clicked in a browser.
- Anything on iOS. There is no iOS build in this project yet.

**Deliberately not built:**
- **A Downloads tab.** The mockup's bottom bar is Home / Search / Downloads /
  Profile; the app ships the website's Home / Movies / Series / Watchlist and
  offline is Phase 3. Put to Rio, who chose to leave the bar alone. A tab over
  an unbuilt screen is worse than no tab.
- **A back arrow.** The mockup has one. Watchlist is a tab root and the profile
  menu's Watchlist row navigates to the *tab*, so nothing ever pushes this
  screen and the arrow would have had nothing behind it.
- **The hover overlay.** `card-style.blade.php` hides its title, buttons and
  "Play now" behind `:hover`, which no handset can reach. Rather than invent a
  touch equivalent, the app follows what the site itself does on a phone: the
  whole poster is the link. If Rio wants a long-press overlay later, that is a
  design conversation and not a gap.
- **A landscape backdrop on the card.** Offered — `HomeRailsService:341`
  already falls back backdrop → poster for the Continue Watching still — and
  Rio chose portrait posters. `backdrop_url` stays off this payload.

**Live now but unused by this screen: `genres` and `seasons_count`.** They are
correct, contract-documented and tested, and they are what the website's card
shows — but after the card became poster-only they reach the app and are drawn
nowhere except the accessibility label. Left in deliberately rather than
ripped out and re-added, because the card design moved twice in one session.
If it settles as poster-only, they are the first thing to reconsider.

**For whoever is next:**
- 🔴 **Two sessions on one emulator is a race and it cost real time here.**
  jambo-a6 and I both drove `emulator-5554` with `adb shell input tap`, landing
  on each other's screens; one of my captures is of their Billing page and one
  of their taps put my screen into Edit mode. `kadson_dev` and `kangaru_perf`
  are both on this machine and unused. Boot a second AVD before you start.
- **The emulator died twice mid-session**, both times unprompted. `emulator
  -avd Test_Android -no-snapshot-save -no-boot-anim` brings it back, then
  connect through the dev launcher's Home tab — the deep link
  `jambo://expo-development-client/?url=...` did not resolve.
- **Metro is on 8093 and 8094, not 8081**, and Fast Refresh did not pick up a
  component rewrite once; the dev menu's Reload did.
- **`docs/worklog.md` and `CHANGELOG.md` cannot be edited with a quoted bash
  heredoc containing PHP namespaces.** It ate a backslash and turned `\app\`
  into a 0x07 byte inside `FrontendController`, exactly as the 2026-09-09 entry
  above warns. Write a script with the Write tool and run it by path.
- **The two PHP failures in `PricingPageCurrentPlanTest` are pre-existing.**
  Nothing in this slice touches the pricing page and the view still contains
  the string the test looks for. 590 pass.

### 2026-09-09 — Refer & Earn, from Rio's mockup (session jambo-e1)

**Status:** complete
**Owns:** mobile/src/features/referrals/** (new: the screen, the share row, the
pure helpers and their tests), tools/dev-catalogue/13-test-user-referrals.php
(new)
**Shares — exact edits, nothing else in these files:**
- `mobile/src/screens/ReferralsScreen.tsx` — deleted; replaced feature-shaped
  under `features/referrals/`, which is what CLAUDE.md asks new work to be.
- `mobile/src/navigation/RootNavigator.tsx` — one import path, and the title
  becomes "Refer & Earn" to match the website's own `pageTitle` and the mockup.
- `mobile/src/ui/theme.ts` — one appended `referrals` block. **Appended only.**
  jambo-a6 owns `hub` and `badge` and jambo-98 is appending `notifications`;
  nothing above my block is touched.

**What this is:** Rio's mockup of Refer & Earn — a hero, the referral code with
a copy control, a row of share destinations, a three-step explainer and a
rewards summary.

**Read the website first, which is the lesson from the Watchlist an hour ago.**
`resources/views/profile-hub/refer.blade.php` and
`Modules/Referrals/app/Http/Controllers/Api/V1/ReferralController.php` both go
through `ReferralDashboardService`, so unlike the watchlist the API is NOT
thinner than the page for the numbers this screen shows. The one thing the
page has and the endpoint does not is the referral *list* (masked friend name,
status, joined date, amount earned) — and the mockup does not show a list, so
it stays out of scope rather than becoming a silent gap.

🔴 **One figure in the mockup does not exist and will not be invented.**
"15 Days Free Premium Earned". Jambo's referral reward is a **percentage of
what the friend pays, credited as money to a wallet** — `rewardPercent`,
`balance`, `totalEarned` — and there is no free-days entitlement anywhere in
`Modules/Referrals`, the ledger, or the subscription tiers. A screen that
tells someone they have earned 15 days of Premium they cannot redeem is the
money case rule 1 of `screen` exists for. The tile shows what they actually
have, in currency, and this is flagged to Rio rather than quietly dropped.

**Decided here, not asked:**
- **The mockup's crimson becomes the brand blue**, as on the profile menu and
  the Watchlist. The accent is `auth.buttonFrom`/`buttonTo` so there is one
  gradient in the app.
- **Copy is cut to what carries a fact**, per Rio's ruling earlier today. The
  hero's "Share Jambo Films with friends and earn rewards when they join"
  restates its own headline and goes; what replaces it is the website's real
  sentence, which carries the two percentages — a fact the screen cannot
  otherwise show. "Get rewards automatically" becomes the actual reward.
- **`Clipboard` from react-native core, not a new dependency.** It is
  deprecated but still present and linked in react-native-tvos 0.86, and
  `expo-clipboard` would need a native rebuild — which would invalidate the
  dev client two other sessions are running on right now. Noted for the next
  rebuild.
- **The share row is five real destinations**, not decoration: WhatsApp,
  Facebook and X through their public share URLs, Copy through the clipboard,
  and More through the OS share sheet. Nothing draws a button that cannot act.

**Verified, by running it:**
- Rendered on emulator-5554 with the test account seeded by the new step 13:
  the hero and its real terms line, the code card, the five share buttons, How
  It Works, and Your Rewards reading **3 Subscribed referrals** and **UGX
  21,000 Earned in total** — the same figures `ReferralDashboardService`
  reports, checked directly before rendering.
- **Copy works and says so.** Tapped Copy and captured "Code copied" in the
  site's own green.
- The money format was wrong on the first render — "UGX 21000.00" against the
  website's "UGX 21,000" — and was fixed and re-rendered.
- 18 unit tests on the pure helpers, 101 across `src/features`, plus
  `tsc --noEmit` and eslint on every file this slice owns.

**NOT verified:**
- **The four share destinations.** Only Copy was exercised on the device.
  WhatsApp, Facebook, X and More are unit-tested at the URL level and the
  handlers are three lines each, but nobody has watched a share sheet open.
  jambo-98 took the emulator before I got to them, and one of my taps landed
  on their Notifications screen while they had it.
- **The 404 "programme is off" path**, carried over unchanged from the screen
  this replaces. `ReferralSettings::active()` is true on this database and I
  did not switch it off to see the other branch.
- **The empty state**, meaning an account with a code and no referrals. It was
  rendered *before* seeding — every figure showed 0 and `UGX 0`, correctly —
  but that was the previous build of the tile, before the wallet glyph and the
  grouping went in.
- Anything on a tablet, a television, or iOS.

**Deliberately not built:**
- **"15 Days Free Premium Earned".** See above and the CHANGELOG. There is no
  free-days entitlement anywhere in the product.
- **Editing the referral code.** The website lets a viewer set a custom code
  with a live availability check, and `PUT /api/v1/referrals/code` exists for
  it. The mockup shows a read-only code, Rio's copy ruling points away from
  adding a form nobody asked for, and an availability-checked input is a slice
  of its own. Named here so the next person does not think it was missed.
- **The referral list.** `refer.blade.php` has a table of masked friend names,
  statuses, join dates and amounts; `GET /api/v1/referrals` does not send it
  and the mockup does not show it. This is the one place the API is still
  thinner than the page — worth an endpoint field when a screen wants it.
- **Withdrawal.** Money leaving the business, read-only on this endpoint by
  design, and the website's own page sends people to the Wallet for it.

**For whoever is next:**
- **`theme.ts` did not typecheck while I was finishing**, at lines 977-1006,
  referencing `prefDetail`, `prefRow`, `prefRowY`, `prefRowBorder`, `prefTile`
  and `prefTitle`. That is jambo-98's `preferences` block written against
  probes not yet exported. **Left alone deliberately** — a file mid-mutation
  belongs to whoever is mutating it — and flagged to them. Nothing in
  `features/referrals` depends on it.
- **`Clipboard` from react-native core logs a deprecation warning** on every
  access. The swap to `expo-clipboard` is one function in
  `ReferralsScreen.tsx` and should happen at the next native rebuild.
- **Three sessions shared one emulator today** and all three of us lost time
  to it. `kadson_dev` and `kangaru_perf` are unused. Boot your own.

#### Closing the Notifications slice

**Status: complete.** Files as claimed, plus two not planned for:
`mobile/src/features/notifications/useNow.ts` and a one-line touch-target fix
in `RootNavigator`. Both are explained below.

**What the chips actually filter.** `notifications.type` already holds the
notification's class name, so `NotificationCategories` maps that and nothing
was migrated — **the chips work over notifications sent before they existed.**
Filtering happens on the server because the list is cursor-paginated; filtering
the thirty rows the app happens to hold would hide matching rows until the
viewer scrolled far enough to load them. An unknown category is a 422, never a
silently unfiltered list.

**The map is explicit rather than derived, and that is deliberate.** All 33
class names happen to snake_case into their own `settingKey()`, so a string
transformation would have worked today and failed silently the first time
somebody named one differently. `NotificationCategoryApiTest` walks the
directory instead and fails if a class is in neither the map nor
`UNCATEGORISED`, so notification 34 forces a decision rather than quietly
leaving its chip.

**A third signed-in page in the token exporter.** `.jambo-hub-inbox__row` and
its icon tile are declared in a `<style>` block inside
`profile-hub/notifications.blade.php`, so they exist on that one URL and
nowhere else — injecting them into the billing page would have read the page's
defaults and called them the row. The rows themselves are injected, like the
badges, because whether that page renders one depends on what the capture
account has been sent and a fresh account has none. **174 tokens, up from 143,
with zero existing values changed** — diffed, not assumed.

🔴 **All five of the site's icon-tile tones fail WCAG AA, and this is a website
defect, not an app one.** The tile fills are fine: each is 14.8:1 or better
against the black row. The `-emphasis` foreground Bootstrap pairs them with is
the broken half, in every tone:

| Tone | The site's pair | Contrast | AA needs |
|---|---|---|---|
| **Warning** | `#ffe877` on `#fff7d2` | **1.14** | 4.5 |
| Success | `#7dcea0` on `#d4efdf` | 1.54 | 4.5 |
| Info | `#66afff` on `#cce4ff` | 1.76 | 4.5 |
| Danger | `#989eac` on `#dddfe3` | 2.01 | 4.5 |
| Primary | `#ef6b72` on `#faced0` | 2.11 | 4.5 |

Same fix as the Pending badge, and the same standing ruling: keep the site's
fill, fix the foreground, record the deviation where it can be undone. The
replacement ink is `colors.background` — the row's own ground, so the glyph is
a knockout of the surface behind it and **no raw value was invented to fix a
raw value that was wrong.** That lands between 14.8:1 and 19.5:1.

🔴 **A second finding, put to Rio rather than decided.** On this build
Bootstrap's `--bs-primary` is a salmon `#ef6b72`, not the Jambo blue — the
Streamit theme left it and applies the brand through custom CSS instead. So
`bg-primary-subtle` is a **pale pink**, and primary is the commonest
notification colour, which means the most frequent tile in the app is pink on a
blue-branded product. Faithful to the site, and probably not what anybody
wants. The site is internally inconsistent here, not the app.

**Three numbers were guessed and then were not.** The first cut of the row set
a 15px title and a 12px timestamp by eye. The site's answers, probed, are 16 at
600 for the title and 14 for both the message and the time — and the timestamp
matching the message is a decision rather than an oversight, so it stands. The
600 weight is the one thing the app cannot draw: it ships four Roboto faces and
the nearest is 500. A fifth face for one line of one screen is a font file on
every handset, so the token records what the site does and the row draws the
nearest weight.

**What the mockup asked for that the data could not give, and Rio's answers:**

1. **The Offers chip** — no promotional notification type exists. **His call:
   keep the chip, map it to admin broadcasts**, which is how a promotion
   actually reaches a viewer today.
2. **The gear** — no settings screen existed. **His call: build one.**
3. **The thumbnail** — mockup draws it large, the site is 40x40 at 10px radius.
   **His call: hold the site's size exactly.**

**The unread tint needed no fourth ruling.** The mockup marks unread in crimson
and the app has translated that to brand blue three times on the strength of
the brand. This time the site answered directly:
`.jambo-hub-inbox__row.is-unread` is already `rgba(26, 152, 255, 0.07)`. It was
captured, not chosen.

**Two things moved rather than being dropped.** "Mark all read" existed on the
screen this replaced, and Rio's header has three controls and no room for a
fourth, so it is the last row of the settings screen — a regression avoided
without arguing with the drawing. And **push is deliberately absent from that
screen** although the site has a third switch: on the website that switch
drives a browser Web Push subscription, so in the app it would either silence
the viewer's *browser* notifications from their phone or toggle a flag for a
channel that cannot deliver. The app registers no FCM token and the registry
still has no sender. The endpoint carries `push` regardless, so the row is a
few lines away on the day push works.

**A defect the emulator caught that no test could.** The gear's touch target
was 30dp — `uiautomator` reported it at 90x90 physical pixels at density 3,
against the app's own `MIN_TOUCH_TARGET` of 48. `spacing.xs` looked right and
was not; it is `touchPadding(22)` now, re-measured at 144x144, exactly 48dp.
**The same pattern is in `AppHeader`** — `action: { padding: spacing.xs }`
around a `tabBar.iconSize` icon — and it is not this slice's file, so it is
reported rather than changed.

**Verified, by running it:**
- **The screen rendered on emulator-5554**, portrait, signed in, against a
  local API: chips, the Today heading, real posters in the 40x40 square, icon
  tiles in four tones, the blue unread tint and dot, relative timestamps, and
  the two-line message clamp.
- **Marking read works end to end** — the profile menu's unread badge went 5 to
  4 after a row press.
- **The settings screen rendered**, and the Email switch **wrote through**:
  toggled off, read back `"email": false` from the API, toggled on, read back
  `true`.
- **Infinite scroll fires** — the footer spinner appeared and page two loaded.
- **Every chip checked over HTTP** against real rows: movies 29, series 2,
  account 7, offers 1, and `?category=sponsors` is a 422.
- **Three guards proved by mutation and restored.** Unfiling
  `MovieAddedNotification` failed the drift test; dropping `Rule::in` failed
  the 422 test; making `groupByDay` skip an unparseable date failed the test
  that says it must not. All three restored and re-run green.
- `npm run check` green: 228 tests, lint, typecheck, `api:check`,
  `tokens:check`. Backend: 31 tests across the Notifications module, the
  notifications API and the spec.

**Not verified:**
- **Tablet and TV.** Neither layout was opened. The chips wrap by design but
  have only been seen on one width.
- **The Earlier heading, on a device.** `This Week` and `Earlier` are covered
  by unit tests and by the seeded dates, but a previous session left roughly
  fifteen identical fixtures dated 15h ago in the shared test account, and
  scrolling past them to reach my spread-out rows cost more emulator time than
  it was worth. The headings are proved by test, not by eye.
- **A chip pressed on the device.** The filters are proved over HTTP against
  the same endpoint the app calls, and the chip row renders, but scripted taps
  drifted repeatedly and I stopped spending time on them.
- **Anything about push actually arriving.** Unchanged by this slice.

**Deliberately not built:**
- **Per-row delete.** The endpoint exists and the website's row has the button.
  The mockup has no row actions at all, so adding a swipe or a long-press would
  have been inventing an interaction. A slice of its own.
- **Per-notification-type preferences.** `GET /notifications/preferences`
  returns them and neither the website nor the app has a UI for them.
  Surfacing it would mean designing a screen the product does not have.
- **Opening `action_url`.** Still a website URL with no route table mapping
  site URLs onto app screens. Pressing a row marks it read and navigates
  nowhere, which is honest; a deep-link resolver is its own work.
- **`WatchlistAvailableNotification` under Movies or TV Shows.** Its payload
  carries `kind`, so it could be routed, but that value lives in the `data`
  text column and reading it would mean a JSON path query against an unindexed
  column on every chip press. One type; it stays under All.

**For whoever is next:** `tools/dev-catalogue/12-notifications.php` seeds 40
rows covering every branch — poster and icon, all five tones, read and unread,
the title and message clamps, dates across all three headings, and enough rows
to cross the 30-row page. `JAMBO_NOTIF_RESET=1` removes them; every id starts
`dev-notif-`. The token exporter needs a local server and the test account:
`node design/export-tokens.mjs --base=http://127.0.0.1:8090
--login=testuser@jambo.test --password=Jambo@2026`. And `adb shell` paths need
`MSYS_NO_PATHCONV=1` in Git Bash, or `/sdcard/x.png` is rewritten to a Windows
path and `screencap` prints its usage instead of failing usefully.

### 2026-09-09 — The preloader plays once, at launch (session jambo-98)

**Status:** complete
**Owns:** nothing new
**Shares — exact edits, nothing else in these files:**
- `mobile/src/ui/components.tsx` — `Loading` split in two. `Preloader` keeps the
  GIF; `Loading` becomes a centred `ActivityIndicator` and no longer takes
  `source`.
- `mobile/src/navigation/RootNavigator.tsx` — one import and one line, the
  launch state now calls `Preloader`.
- `mobile/src/screens/AccountScreen.tsx` and `screens/DevicesScreen.tsx` — the
  dead `source` prop removed, plus the `branding` read and, in Devices, the
  `useAuth` import that had nothing left to do.

**What this is.** Rio, 2026-09-09: *"we only need the preloader when we are
opening the app, not every screen."*

**Why the component changed rather than the screens.** `<Loading>` has 24 call
sites across 24 files, most of them owned by other live sessions. Editing all
of them would have been 24 conflicts for one decision. Changing what `Loading`
draws is one file, and every existing call site gets the new behaviour without
being touched — which is the whole argument for the shared component existing.

**The reasoning is worth keeping, because it is not about taste.** The
preloader is the app *arriving*. That reads correctly exactly once. Played on
every tab, every list and every pull-to-refresh, a 200dp animated logo stops
saying "Jambo is starting" and starts saying "Jambo is slow" — a brand
animation arguing against the brand.

**`source` was deleted rather than ignored.** Keeping the parameter and
quietly dropping it on the floor would have left two screens passing the
admin's preloader URL into a component that no longer had any use for it: code
that looks deliberate and does nothing. A caller that genuinely wants the
animation now asks for `Preloader` by name.

**Verified:** force-stopped and relaunched on emulator-5554 — the animated
wordmark still plays at launch. Screens render with no GIF. `npm run check`
green: 228 tests, lint, typecheck, `api:check`, `tokens:check`.

**Not verified:** **the new spinner was never caught in a screenshot.** Screens
resolve faster than a scripted `screencap` could catch, and jambo-e1 needed the
device. The absence of the GIF on a loaded screen was seen; the spinner itself
was not.

**A false alarm, and worth recording as one.** A capture during the launch
sequence showed the preloader with the **S of "FILMS" cut off**, and it was
reported to Rio and to jambo-e1 as a possible defect in the asset. It is not.
A later capture caught the animation at rest and the lockup is complete: the
GIF animates from a stacked mark to a horizontal one, so a screenshot taken
part-way through catches a frame mid-reveal and reads as a crop.

**The lesson is about the method, not the asset.** One frame of an animation is
not evidence about the animation. The arithmetic that seemed to rule out the
container — 540x360 into a 200x133 box under `contentFit="contain"`, ratios
1.500 and 1.504 — was right and led to the wrong conclusion anyway, because it
answered "is the box cropping it" and not "is this frame the whole thing".
Nothing was changed, and both reports were corrected.

### 2026-09-09 — The Account hub removed, and two screens rehoused (session jambo-98)

**Status:** complete
**Owns:** nothing new
**Shares — exact edits, nothing else in these files:**
- `mobile/src/screens/AccountScreen.tsx` — **deleted.**
- `mobile/src/screens/ProfileMenuScreen.tsx` — identity block points at
  `Profile`; two rows added; `MenuRow['to']` loses `Account` and gains the two
  routes; two Phosphor icons imported.
- `mobile/src/ui/profileMenu.ts` — `activeRowFor` loses `Account`, gains
  `ContinueWatching` and `History`.
- `mobile/src/ui/profileMenu.test.ts` — three assertions added, one changed.
- `mobile/src/navigation/RootNavigator.tsx` and `navigation/types.ts` — the
  route, its import and its param entry removed.
- `mobile/src/screens/DevicesScreen.tsx` — a `['me']` query and one row.

**What this is.** Rio, 2026-09-09, with a screenshot of the old hub and of the
menu's identity block: *"we should remove this, and link this to our account
page we have designed, because it is linking to the older account page."*

**The hub predated the Profile screen and had been overtaken by it.** It was a
column of buttons — Notifications, Security, Plans, Devices — every one of
which is now a row in the profile menu, plus a subscription summary that
Membership renders more fully. Two things on it were not duplicated anywhere,
and finding them is the whole of this entry.

🔴 **Deleting it stranded two screens.** `ContinueWatching` and `History` were
reachable **only** from that hub: nothing else in the app navigates to either,
which a grep confirmed rather than an assumption. Both became rows in the menu,
between Watchlist and Profile.

**Rio reversed half of that within the hour, and he was right.** *"did you add
the on the menu the continue watching, remove it we have it on the homepage."*
The home screen's Continue Watching rail is the app's answer to that question
and a menu row was a second one. The row is gone.

**So `ContinueWatchingScreen` was deleted too, and that was checked rather than
assumed.** With the row gone it had zero callers, and it could not acquire one
by accident: `continue-watching` is **not** in `GET /collections`, verified
against the running API, so the home rail is offered no "View all" and cannot
route there. A screen nothing can reach is dead code, and this project's rule
is that the codebase does not carry any.

**The consequence, which is worth stating rather than discovering later:** the
home rail is now the only place a viewer sees in-progress titles, and it shows
only what fits the rail. There is no full list. That is a product decision, not
an oversight, and reversing it is one row and one route.

**History stays**, because it is not on the home page and nothing else reaches
it. The website's sidebar has no counterpart for it either, and does not need
one: a browser keeps history in the browser. **That makes it the row most
likely to fall out of `activeRowFor` later**, since every other row has a
website equivalent to remind someone it exists — so the test names it, and
names the two dead routes beside it.

**One fact was rehomed rather than lost.** The hub was the only place in the
app showing the concurrent-stream cap. The Devices screen already said devices
count towards "how many can watch at once" and could not say how many, which is
half a sentence. It now reads the cap from a `['me']` query on the key the rest
of the app already uses, so a warm cache costs nothing. **Its failure is
deliberately not that screen's error state:** the device list is what a viewer
came for, and losing one number must not replace it with an error page. `null`
from the plan still renders "Unlimited" rather than "none" — opposite claims,
and the wrong one tells a paying viewer they cannot watch on a second device.

**Verified:** the reloaded menu on emulator-5554 showed both new rows in place
with the right icons, which is also how Rio saw the Continue Watching one and
asked for it back out. `npm run check` green after the reversal: 230 tests,
lint, typecheck, `api:check`, `tokens:check`. The active-row assertion was
mutation-proved while both rows existed — removing `ContinueWatching` from the
map failed it — and restored; the map now asserts that same route lights
nothing.

**Not verified:**
- **The identity block's new destination was never pressed.** The emulator went
  down before it could be. The route is typechecked and the row is in the
  active-row map, but nobody has watched it open Profile.
- **The identity block itself, in the reloaded menu.** The capture that proved
  the two new rows showed the rows correctly with the identity block not
  visible above them. The code makes that hard to credit — the block is an
  unconditional first child of the ScrollView, its avatar has a fixed size so
  it cannot collapse, and that screen has no scroll-restore logic — so the
  capture is the more likely fault. Recorded rather than resolved, and it is
  the first thing to look at on the next device pass.

**Deliberately not built:** nothing was added to the Profile screen. It is
identity — banner, avatar, email, phone, country, member since — and Continue
Watching and History are viewing history, not identity. Putting them there
would have made it the hub that was just removed.

### 2026-09-09 — The Wallet screen, and withdrawal on the phone (session jambo-98)

**Status:** in progress
**Owns:** mobile/src/features/wallet/** (new: the screen, the withdrawal
sheet, the pure helpers and their tests),
Modules/Referrals/tests/Feature/WalletWithdrawalApiTest.php (new)

**Shares — exact edits, nothing else in these files:**
- `mobile/src/screens/WalletScreen.tsx` — deleted, replaced by the feature
  folder above.
- `Modules/Referrals/app/Http/Controllers/Api/V1/ReferralController.php` —
  `wallet()` gains the withdrawal history and an in-progress flag; one new
  method requests a withdrawal. **No money logic is written here**: it calls
  `ReferralWalletService::requestWithdrawal`, the same entry point the
  website's own form posts to.
- `Modules/Referrals/routes/api.php` — one POST route, and the comment above
  it that says withdrawal is no longer read-only.
- `mobile/src/api/endpoints.ts` — the wallet block only.
- `mobile/src/navigation/RootNavigator.tsx` — one import repointed.
- `mobile/src/ui/theme.ts` — one appended `wallet` block.
- `docs/api/openapi.yaml`, `mobile/src/api/schema.d.ts` — the new route and
  the widened wallet response.

**What this is:** Rio's mockup of 2026-09-09 — a balance card, two primary
actions, a row of quick actions and a transaction list.

**Six of its controls were checked against the product before anything was
built, and only two survived intact.** The skill's §1 was amended this same day
after a watchlist was cut to the endpoint when the website had the data all
along, so the website's own wallet page and its controller were read first.

| Mockup | Reality |
|---|---|
| Add Funds | **Nothing behind it.** No top-up exists: no ledger type, no deposit code. Money enters this wallet as referral rewards, refunds and statement credits |
| Withdraw | **Real on the website**, deliberately absent from the API |
| Buy/Rent Movies | **Nothing behind it.** Jambo is subscription only; no purchase or rental model exists |
| Upgrade Membership | Real |
| Gift Credits | Nothing behind it |
| Airtime Top Up | Nothing behind it |

Its three sample transactions — a movie purchase, a movie rental and a wallet
top-up — are all three kinds that cannot occur. The real ledger types are
referral reward, refund, statement credit, performance credit, spend,
withdrawal hold, hold release and adjustment.

**Rio's answers, 2026-09-09:**

1. **"Add Funds" becomes "Earn more", pointing at Refer & Earn** — which is
   what the website's own wallet card does, and the only way money actually
   enters the wallet.
2. **The four quick actions become the two real ones** — Upgrade Membership and
   Refer & Earn, again exactly the website's pair.
3. **Withdrawal gets built properly**, in the app and in the API.




### 2026-09-09 — Referral code editing, and the rule the API was missing (jambo-e1)

**Status:** complete
**Owns:** mobile/src/features/referrals/EditCodeSheet.tsx (new),
Modules/Referrals/app/Support/ReferralCodeRules.php (new)
**Shares — exact edits, nothing else in these files:**
- `Modules/Referrals/app/Http/Controllers/Api/V1/ReferralController.php` —
  `updateCode` now validates through the shared rules, plus a new `checkCode`.
- `Modules/Referrals/app/Http/Controllers/ReferralCodeController.php` —
  `check()` delegates; `apply()` untouched.
- `app/Http/Controllers/ProfileHubController.php` — `updateReferralCode`'s
  validate array only. **jambo-a6 also edits this file** (billing eager loads);
  different method, no overlap.
- `Modules/Referrals/routes/api.php` — one route.
- `docs/api/openapi.yaml`, `mobile/src/api/schema.d.ts` (regenerated),
  `mobile/src/api/endpoints.ts` (two client methods), `mobile/src/ui/theme.ts`
  (a shared `sheet` block; `watchlist` now references it instead of restating
  it), `tests/Feature/Api/V1/SubscriptionAndReferralsTest.php` (five tests).

**Two asks from Rio, and only one of them was work.**

1. **"The percentages should be dynamic according to what the admin sets."**
   They already were, and it is now verified rather than asserted:
   `ReferralSettings` reads the `settings` table, the endpoint forwards it, the
   screen trims decimal zeros as the Blade view does. Changed the admin values
   to 17.50 / 25, re-rendered, watched both the hero line and step 3 follow
   with "17.50" shown as "17.5%". Restored to 10 / 10.

   🔴 **The first run of that probe produced a false negative** and I nearly
   reported a defect that did not exist. `setting()` in `app/helpers.php` takes
   a POSITIONAL pair — `setting(['key', 'value'])`, because it does
   `Setting::set($key[0], $key[1])` — and the associative form I used wrote
   nothing while looking like it had. Recorded because a false red on a
   money-adjacent setting is exactly the sort of thing that gets acted on
   before anyone checks the probe.

2. **"We need to be able to edit the referral code."** This turned up a real
   defect, described in the CHANGELOG: the API's save was missing
   `ReservedUsername`, so the app could take a code the router owns. Fixed by
   extracting `ReferralCodeRules` and pointing all four call sites at it.

**Verified:**
- 19 tests in `SubscriptionAndReferralsTest`, five of them new, including one
  that asserts availability and the save agree on the same four codes — the
  promise the web comment made and the code did not keep.
- **Both new guards proved by mutation.** Removing `ReservedUsername` from the
  save fails the reserved-code test; removing the reserved check from
  availability fails the agreement test. Both restored, file greps clean.
- `npm run check` green: 230 tests, lint, typecheck, api:check, tokens:check.
- Full PHP suite: **603 passed, 2 failed.** The two are
  `PricingPageCurrentPlanTest`, pre-existing all session — baseline was 590
  passed with the same two red, so this session added 13 tests and no
  failures. The pricing view still contains the string the test looks for and
  nothing in this slice goes near it.
- On the device: the editor sheet opens seeded with the current code, shows
  its hint line and keeps Save disabled while the value is unchanged. The
  accessibility tree confirms the three controls and their labels — "Copy the
  code testuser", "Copy your referral link", "Change your referral code".

**NOT verified:**
- **The availability line changing as somebody types.** This is the one thing
  I could not capture. Three attempts each ended with the emulator in a
  different state — the sheet dismissed by a stray DEL, the element inspector
  toggled on, the app in the system dialer, and finally the AVD dead for the
  third time this session. The server half is proved by test and mutation, so
  what is missing is the visual confirmation of the debounce and the status
  line, not the correctness of the answer they show.
- **Saving a new code end to end from the app.** Same reason.
- The four share destinations beyond Copy, and the programme-off 404 path,
  both still open from the previous entry.

**Deliberately not built:**
- **A client-side copy of the code rules.** The app sends what was typed and
  reads the server's message. A fifth place for the same rules is exactly what
  this slice removed.
- **Changing the website's referral JS.** Its debounce and stale-guard were
  the model for the app's; the page works and touching it was not asked for.

**For whoever is next:**
- 🔴 **`setting()` takes a positional pair, not an associative array.** See
  above. `setting(['referrals.active', '1'])` is right;
  `setting(['referrals.active' => '1'])` silently does nothing.
- **`uiautomator dump /sdcard/ui.xml` needs `MSYS_NO_PATHCONV=1`** in this
  shell or the path becomes `/Files/Git/sdcard/ui.xml`. With it, the dump is
  by far the most reliable way to find a control — it gave exact bounds and
  accessibility labels when coordinate-guessing had failed four times.
- **A screenshot is one frame and some states are shorter than the capture.**
  A copy confirmation lives two seconds; a reloading bundle looks like a
  broken screen. jambo-98 hit the same thing on an animated preloader and
  concluded it was clipped when it was mid-animation. Capture immediately
  after the tap, and doubt the screenshot before the code.
- The emulator died three times unprompted. Two spare AVDs are unused.

### 2026-09-09 — History off the profile menu (jambo-e1)

**Status:** complete
**Shares — exact edits, and all four files are jambo-98's:** they were told
before the edit and replied "make the test edit yourself, in the same change
as the map entry, do not wait for me", with the reasoning that splitting it
would leave the suite red or the map untested for a window.
- `mobile/src/screens/ProfileMenuScreen.tsx` — the `history` row, its comment
  block, `'History'` from the `to` union, and the now-unused
  `ClockCounterClockwise` import.
- `mobile/src/ui/profileMenu.ts` — the `History: 'history'` map entry.
- `mobile/src/ui/profileMenu.test.ts` — one assertion added to the existing
  removed-routes case, and the case renamed to name all three.
- `mobile/src/navigation/{RootNavigator.tsx,types.ts}` — the route and its type.
- `mobile/src/screens/HistoryScreen.tsx` — deleted.

**The check that actually mattered, run before touching anything.** Rio's
second message — "history is used in background for training our ai system" —
made the question "does removing this screen starve the training data". It does
not: `PlaybackBeatRecorder::record` writes `WatchHistoryItem` from the playback
heartbeat, and the screen only read it back through `GET /history`, which is
still served and still documented. Had the screen been part of the write path
this would have been a very different change.

That sentence is now in three places on purpose — the changelog, the test
comment and here — because "we deleted the History screen" and "we stopped
collecting history" are one careless sentence apart and somebody will read one
of them in six months.

**Verified:** 249 tests, lint and typecheck clean on every file this touched.
The removed-routes guard mutation-proved: adding `History` back to the map
fails it, restored after.

**NOT verified:** the menu was not rendered after the change. Two other
sessions are on the emulator and the change is four deletions and one
assertion, all covered by a test that fails if the map disagrees.

**For whoever is next:** `mobile/src/features/wallet/money.test.ts` does not
typecheck as I write this — `Property 'reason' does not exist on type
'{ can: true; }'`, twice. That is jambo-98's in-flight Wallet slice, left alone
and flagged to them. `npm run check` will fail on it until they land.

### 2026-09-09 — Streaming preferences off the menu; the player left alone (jambo-68)

**Status:** complete
**Owns:** `mobile/src/screens/StreamingPreferencesScreen.tsx` (deleted),
`mobile/src/ui/profileMenu.ts`, `mobile/src/ui/profileMenu.test.ts`
**Shares — exact edits:**
- `mobile/src/screens/ProfileMenuScreen.tsx` — the `streaming` row, its comment
  block, `'StreamingPreferences'` from the `to` union, the now-unused
  `SlidersHorizontal` import, and two doc comments that named the row.
- `mobile/src/navigation/{RootNavigator.tsx,types.ts}` — the route, its import
  and its type.
- `docs/plans/mobile-offline-app.md` — new §6.5, and one row of the §6.2 table.

**Rio, 2026-09-09:** "remove the streaming menu, because all of these settings
are applied to the player itself." Then, mid-change: *"wait to build the player
we are going to build it fully. so i need you to have it in plan."*

**That second message arrived after I had already written the autoplay toggle
into `PlayerMenu` and `WatchScreen`, and both edits were reverted.** Reverted
surgically rather than by `git checkout`, because the player is jambo-69's
in-progress slice and a checkout would have taken their work with it. Both
files now diff clean against HEAD, which is the check that proves the revert
was exact. **The player build is a slice of its own and it is not this one.**

**What the deletion stranded, checked before it was made.** Three fields on
`streaming_preferences`, not one:
- `video_quality` — already written from `PlayerMenu`. The screen was a second
  door onto one account field, which is exactly what Rio was pointing at.
- `autoplay_next` — read by `WatchScreen`, settable **only** from the deleted
  screen. It is now frozen at its default of on, with no control anywhere in
  the app until the player is built. **This is a known regression, accepted by
  Rio's "wait", and it is written into the plan rather than left to be
  discovered.**
- `wifi_only_downloads` — Phase 3, gated on `features.downloads`, which is
  `false` by default in `AppConfigController`, so no viewer could see the row
  today. It belongs on the Downloads screen when that lands, not in a player
  overlay.

**The API is untouched.** `GET`/`PATCH /account/preferences`,
`StreamingPreferencesController`, the column, its migration and its ten feature
tests all stay. **"We deleted the preferences screen" and "we stopped saving
preferences" are one careless sentence apart** — jambo-98's phrasing, and the
same trap the History removal hit this morning. The field is still on the
account, and quality is still written to it from the player on every change.

**jambo-98 owned the one file I could not edit, and handled it themselves.** I
told them before touching `mobile/src/features/wallet/WalletScreen.tsx`, which
had a Streaming tile pointing at the route I was deleting. They replaced it
with a **Billing** tile rather than deleting it, on the reasoning that one tile
in a two-tile row reads as a rendering fault, and that Refer & Earn was already
a primary button on that screen. Their file, their call, and a better answer
than the deletion I had proposed. Asking first cost one message and avoided
landing an edit under a screen they had open.

**Verified:** 249 tests green across 17 suites, `tsc --noEmit` clean, `eslint .`
clean. The removed-routes guard mutation-proved — putting
`StreamingPreferences: 'streaming'` back into the map fails
`knows nothing about the four removed routes` with `Received: "streaming"`, and
the map was restored. `grep` for the route name across `mobile/src` returns
only the API type and the guard assertion.

**NOT verified:** the menu was not rendered on a device after the change. Two
other sessions are on the emulator, and the change is four deletions plus one
assertion, all covered by a test that fails if the map and the rows disagree.
Nothing was rendered for the player either, because nothing in the player
changed.

**Deliberately not built:**
- **The autoplay control.** Rio said wait. It is §6.5 of the plan instead.
- **Any replacement home for `wifi_only_downloads`.** The Downloads screen does
  not exist yet and inventing a settings screen to hold one switch for an
  invisible feature is how the deleted screen came to exist in the first place.
- **Any change to the preferences API.** An endpoint with one writer is not a
  dead endpoint.

**For whoever is next:** §6.5 of `docs/plans/mobile-offline-app.md` is the
player build's inheritance — the three fields, where each must end up, and the
three constraints on the settings menu. Read it before adding a row to
`PlayerMenu`, and close `autoplay_next` first.

#### Closing the Wallet slice

**Status: complete.** Files as claimed, plus `mobile/src/features/wallet/money.ts`
and its test, which the claim folded into "the pure helpers".

**Withdrawal is real, and it writes no money logic.** The API method validates a
request shape and calls `ReferralWalletService::requestWithdrawal` — the entry
point the website's own form posts to — and `Payouts::request` below that is
where the row lock, the one-open-request guard and the ledger hold already
lived, inside a transaction that rolls the request row back when the ledger
refuses. **The whole endpoint is about forty lines because none of the hard
parts are in it.**

**No idempotency key, and that is a decision rather than an omission.** The
standard says money paths are idempotent; this one is, by a mechanism that was
already there. `Payouts::request` locks the owner row and refuses a second open
request, so the retry a dropped connection produces cannot create two
withdrawals — it 422s, and the app refetches. A key would be a second mechanism
guaranteeing what the first already does. `test_a_second_request_while_one_is_
open_is_refused` is what makes that argument checkable: if it ever fails, the
missing key becomes a real gap.

**The API was thinner than the website, exactly as §1 warns.** The site has
shown withdrawal history all along — date, amount, status, reference — and
`GET /wallet` dropped every bit of it. That is not decoration: **the hold is
taken the moment a withdrawal is requested, so a balance that has dropped with
nothing paid out has no explanation without it.** Ten most recent, plus
`has_open_withdrawal` computed from the same `OPEN_STATUSES` the service guards
on, so the button and the rule cannot disagree.

🔴 **`Ledger::balanceFor` returns a string whose decimals follow the database
driver.** MySQL answers `"15000.00"`, SQLite answers `"15000"`. Four tests
failed on it before it was understood. Two consequences, and the second is the
larger:

1. Money is compared with `bccomp` in tests, never `assertSame`.
2. **The screen this replaced was wrong about the fix.** It carried an explicit
   rule — every amount stays a string from the wire to the Text node, never
   parsed — written to protect a balance from binary floating point. The
   instinct is right and the conclusion was not: the website itself does
   `number_format((float) $balance, 0)` on every amount its wallet prints, so
   string fidelity was never what the product shipped, and rendering the raw
   string makes the app's numbers depend on the database behind the API. Money
   is parsed once and formatted in `money.ts`, which is also the only place
   that can guarantee `UGX 12,500` rather than `12500.00`.

🔴 **A real defect found on the money path, reported and NOT fixed.**
`wallet_withdrawal_requests.requested_at` is a MySQL `timestamp` that the
schema left without an explicit default, so MySQL gave it the implicit
`DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`. `Payouts::request`
inserts the row with the correct UTC value and then immediately runs
`$withdrawal->update(['hold_entry_id' => ...])` — **and that update silently
overwrites `requested_at` with the database server's own clock**, in the
session timezone (`SYSTEM`, which is EAT here) rather than the app's UTC.

Measured, not inferred:

| | |
|---|---|
| `requested_at` stored | `2026-09-09 22:43:40` |
| `created_at` on the same row | `2026-09-09 19:43:40` |
| Ledger hold `created_at` | `2026-09-09 19:43:40` |
| PHP `now()` | `19:43:40 UTC` |
| MySQL `now()` | `22:43:40` |

So every withdrawal claims to have been requested three hours after it was, and
**the timestamp moves again on every later update** — approve, pay, reject — so
a withdrawal paid a week later will say it was requested a week later. It was
visible on the device as the withdrawal reading `Sep 10` above its own ledger
entry reading `Sep 9`.

**Pre-existing, and the website's form has it too** — this endpoint calls the
same service. Not fixed here on purpose: it is a schema change to a money
table, existing rows carry wrong values that need a decision rather than a
default, and the audit trail is the thing being repaired. It is Rio's call and
its own change.

**Rio's three answers, all recorded in the claim above, and one tightening.**
His two answers together would have put Refer & Earn in the primary row *and*
in the quick actions. Two routes to one screen on one screen is what §4 exists
to stop, so Refer & Earn is the "Earn more" primary and the tiles carry
Upgrade Membership and Billing. **The second tile was Streaming until
jambo-68's slice removed that screen an hour later**; Billing replaced it
because this is a money screen and payment history is money.

**Verified, on the device and against the running server:**
- **The screen renders with real data** — balance, the two actions, both tiles,
  and real referral rewards in the ledger.
- **A withdrawal was requested end to end against the live API** and every
  moving part moved: balance 21,000 → 6,000, a `withdrawal_hold` ledger entry
  at −15,000, `has_open_withdrawal` true, the row at status `requested`.
- **The device then showed all four consequences**: the balance at 6,000, "A
  withdrawal is on hold." on the card, the Withdraw button disabled under "A
  withdrawal is already in progress.", and the Withdrawals section.
- Two guards mutation-proved and restored: unscoping the withdrawal query fails
  the cross-viewer test, and returning `rejection_reason` on every status fails
  the withholding test.
- 69 PHP tests across Referrals and Wallet. `npm run check` green: 249 tests,
  lint, typecheck, `api:check`, `tokens:check`.

**Not verified:**
- 🔴 **The form was never submitted from the device.** The withdrawal above was
  posted over HTTP. Three sessions shared one Metro this evening and the bundle
  reloaded under the form four times mid-typing; the sheet's own field wiring —
  that the three inputs reach the request body — is covered by nothing but
  reading it. **This is the one thing worth re-running on a quiet device.**
- The 422 paths were never seen in the sheet's error Alert, only in tests.
- Tablet and TV. Neither layout was opened.

**Deliberately not built:**
- **Wallet top-up.** Rio's call: no ledger type, no deposit code, and ADR-0004
  forbids a payment flow on the Play build. "Add Funds" points at earning.
- **Buy/Rent, Gift Credits, Airtime Top Up.** None exist in the product.
- **Cancelling a withdrawal.** The website cannot either; a viewer with a
  regretted request waits for a clerk. Worth having, not worth inventing here.
- **Paginated withdrawal history.** Ten rows, because the ledger below already
  carries every hold and release.

### 2026-09-09 — The Devices screen, and where a device is (session jambo-98)

**Status:** in progress
**Owns:** mobile/src/features/devices/** (new: the screen, the row, the pure
helpers and their tests), app/Support/IpLocation.php (new),
database/migrations/*_add_last_ip_to_devices_table.php (new),
tests/Feature/Api/V1/DeviceLocationTest.php (new)

**Shares — exact edits, nothing else in these files:**
- `mobile/src/screens/DevicesScreen.tsx` — deleted, replaced by the feature.
- `Modules/Streaming/app/Services/AccountDeviceRegistry.php` — a `location`
  key on both row shapes. No existing key changes.
- `Modules/Streaming/app/Http/Middleware/EnsureDeviceIsActive.php` — the
  existing `last_seen_at` stamp also records `last_ip`.
- `Modules/Streaming/app/Models/Device.php` — one fillable entry.
- `mobile/src/api/endpoints.ts` — the devices block only.
- `mobile/src/navigation/RootNavigator.tsx` — one import repointed.
- `mobile/src/ui/theme.ts` — one appended `devices` block, after jambo-68's
  `plans`.
- `design/export-tokens.mjs` — device-row probes appended after jambo-68's
  pricing probes.
- `docs/api/openapi.yaml` and the regenerated `schema.d.ts`.

**What this is:** Rio's mockup of 2026-09-09 — a hero, a usage meter, an
Active/Inactive split, and a row per device.

**Three of its elements were checked against the product and all three needed
his call.** The website's own devices page was read first, per §1.

| Mockup | Reality |
|---|---|
| "Kampala, Uganda" per row | **No location is stored.** The website shows the raw IP. App installs store no IP at all — the `devices` table has no such column |
| "4 of 6 devices used" | **No device-registration cap exists.** The real limit is `max_concurrent_streams` from the tier, it counts browser sessions, and app installs are excluded from it by default |
| The `+` button | Nothing to add. A device registers by signing in; the nearest real thing is TV sign-in, which is Phase 4 |

**Rio's answers:** add geolocation; show the real limit labelled honestly; drop
the `+`.

**The Active/Inactive split was not put to him because it can be made real.**
`AccountDeviceRegistry::countAgainstCap` already only counts things seen inside
the session lifetime, so "counts against your limit" and "does not" is a
distinction the server already draws.

**Verified and not verified:** see the closing note below.

### 2026-09-09 — Membership, rebuilt to Rio's mockup (jambo-68)

**Status:** in progress — Rio is looping on this screen with me
**Owns:** `mobile/src/screens/PlansScreen.tsx`, the `plans` block at the end of
`theme.ts`, the pricing probes and their `put()` calls in
`design/export-tokens.mjs`, three new cases in
`tests/Feature/Api/V1/SubscriptionAndReferralsTest.php`
**Shares — exact edits:**
- `Modules/Subscriptions/.../Api/V1/SubscriptionController.php` — `plans()`
  only; three fields added to the payload.
- `Modules/Subscriptions/app/Models/SubscriptionTier.php` — one new static,
  `popularFrom`.
- `Modules/Frontend/resources/views/Pages/pricing-page.blade.php` — the
  inline highlight rule became a call to that static. Six lines to one.
- `docs/api/openapi.yaml` + the regenerated `schema.d.ts`.

**The endpoint was thinner than the page it came from, and that was the whole
blocker.** `subscription_tiers.features` has backed the website's pricing cards
since before this API existed and `GET /plans` never forwarded it, so the app
had a price ladder with no stated reason to climb it. The two options that
looked available were "show less than the website" and "invent bullets", and
the real answer was the third one the `screen` skill names: **parity with what
we already ship.** Same trap as the watchlist meta line on 2026-09-09, same
resolution. `period_label` went with it, so no client keeps its own copy of
daily/weekly/monthly/yearly.

**The "Most popular" rule now lives in one place.** It was inline in the blade;
it is `SubscriptionTier::popularFrom` and both the website and the API call it.
Two copies would have disagreed the first time an admin added a tier, and
nobody would have seen it until the two screens were put side by side.
Mutation-proved: `sortByDesc` → `sortBy` fails the new case, restored.

**The pricing card had never been probed.** Twelve new probes on `/pricing`
— `.pricing-plan-wrapper`, `.plan-main-price`, `.pricing-plan-discount`,
`.jambo-period-tabs`, `.jambo-strip` — took `tokens.json` from 174 to 217. §4a:
a component that exists on the web product takes its design, behaviour and
size from a measurement, not from an eye on a drawing.

🔴 **The ninth contrast failure on this palette, and the worst so far for what
it is.** The website's "Most popular" ribbon is `#d1d0cf` on `#1a98ff` —
**1.95:1**, on the one label whose entire job is to be read. Kept the fill,
moved the app's text to white (3.01:1), recorded both ratios in the theme
block. White-on-blue is still under AA, and it is deliberately not re-decided
here: it is the pair the whole app wears and `badge` already ruled it out of
scope for a single slice. **The website defect is Rio's to schedule.**

**Two traps that cost time and are worth knowing:**
- 🔴 **A quoted heredoc through the Bash tool still ate one layer of
  backslashes**, so `re.sub`'s `\1` and `\2` were written into
  `export-tokens.mjs` as the literal control bytes 0x01 and 0x02. `node
  --check` caught it; `grep` for the bytes cleaned it. Write the script to a
  file and run it by path, which is what the memory already says.
- 🔴 **`toNumber()` in the exporter demands a `px` suffix**, so every
  `font-weight` probe silently produced nothing and `put()` skipped it. Two
  earlier blocks already carry a comment about this. Third time.

**Fast Refresh is dead on this dev client.** The HMR websocket to
`ws://10.0.2.2:8093/message` never connects — the HTTP bundle fetch to the same
host and port succeeds, so it is the socket specifically. Every style change
therefore needs `am force-stop` and a relaunch before it is visible, which is
why Rio saw an unchanged screen for twenty minutes.
`scratchpad/render-membership.sh` does the restart-and-navigate.

**Verified on the device, all four states, by moving the test account rather
than by trusting the code:**
- **Monthly, populated.** Three cards abreast, equal height, buttons on one
  line, every price whole — the first cut clipped all three to "UGX 10,0…" at
  the site's type scale, which is why the scale came down.
- **The "Most popular" ribbon**, which was invisible until the account was
  moved off Premium. `popularFrom` marks exactly one tier and that tier was
  this account's own plan, and the ribbon is suppressed on the plan you are
  on — the website does the same. Switched to Basic, rendered the ribbon and
  the green current-plan card side by side, switched back.
- **Weekly and Yearly.** Yearly shows both yearly tiers with the admin's own
  "2 months free vs monthly plan" bullet, which is the sentence the mockup
  wanted invented. It was already in the data.
- **No membership at all.** Status set to cancelled, rendered "No active
  membership" with no date and no badge, restored.

A short ladder now centres, which is the website's `justify-content-center`
rather than a decision taken here. Rendered left-aligned first and a lone
Weekly card against the left margin read as a layout that had failed to load.

266 mobile tests, 22 PHP tests on this endpoint, typecheck, lint, `api:check`
and `tokens:check` all green.

**NOT verified:** the Daily tab, which is one card and the same code path as
Weekly. No tablet or TV width was rendered — `CARD_WIDTH` is a third of the
viewport, so a 1280dp tablet would draw three very wide cards.

🔴 **A mutation that stayed green found a real defect, and the lesson is
jambo-49's from the same evening.** `features` is published as
`is_array(...) ? array_values(...) : []`, with two tests on it, both green.
Replacing the whole expression with `$tier->features ?? []` left them green:
the null case was answered by the fallback and **nothing was asking what
`array_values` was for.** Several guards, one fallback, an assertion that
cannot say which guard answered.

The case it protects is not theoretical. Delete one entry from the middle of
that JSON — by hand, or by any write that does not reindex — and the column
holds `[0 => 'A', 2 => 'B']`, which `json_encode` emits as an **object**. The
generated client type says `string[]`, so the app would receive
`{"0":"A","2":"B"}` where it expects a list and every plan card would render
with no bullets while the endpoint looked healthy. There is now a case
asserting both the values and that the keys are `[0, 1]`; the mutation fails
it, and the guard is restored. 23 PHP tests green.

🔴 **A second untested branch, found by taking the lesson one step further.**
jambo-49's refinement — *when a mutation does not fail, suspect the fixture as
much as the assertion* — applied straight back to `popularFrom`. Deleting its
`?? highest paid tier` fallback failed **nothing**, because every fixture in
that file builds monthly tiers and the branch could not run. It is not a
decorative branch: the website's own comment says an all-yearly catalogue must
still get a visual winner, and an admin selling only weekly and yearly plans is
a real admin. There is now a case with no monthly tier at all; the mutation
fails it. 24 PHP tests green.

**Two mechanics for the next session, both cost time here:**
- 🔴 **MySQL was down when this session resumed** and every authenticated call
  500d. `mysqld.exe --defaults-file=D:/xampp/mysql/bin/my.ini` brought it back.
- 🔴 **A back press to recover from a missed tap exits the app to the Android
  launcher**, and the next twenty taps then land on Gmail. The memory already
  says this; a retry loop that presses back between attempts is how it bites
  anyway. Relaunch with `monkey -p com.jambofilms.app`, and wait for a rail
  card rather than for the header, which paints while the rails are still
  fetching.

**Open, and each is Rio's call rather than mine:**
1. **`test-premium` is an active tier** — UGX 10,000, no features, named "Test
   Premium". It is a card on this screen and a card on the website's pricing
   page. It should probably be deactivated.
2. **The hero image is a real catalogue poster**, not the mockup's studio
   photograph, because the product has no such asset and inventing one is not
   this screen's job.
3. **"Good Stories Live Forever" is not on the screen.** It is script
   lettering in the mockup and the app ships no script face; adding one is a
   font decision.
4. **The mockup's "up to 2 months free" is the real yearly price instead.**
   That figure is arithmetic across two prices and would keep being stated
   after an admin changed either.
5. **The cards say "On the website", not "Get Started"**, because neither
   build can take a payment. ADR-0004 for Play; no server-side checkout for
   direct.

#### Closing the Devices slice

**Status: complete.** Files as claimed, plus `tools/dev-catalogue/14-test-user-devices.php`
and two entries added to the notification icon map, both explained below.

**The mockup's three impossible elements, and what they became.** All three
were checked against the website and the schema before anything was built.

| Mockup | Shipped |
|---|---|
| "Kampala, Uganda" per row | A real resolver, and **an IP on every row until a database exists** — see the blocker below |
| "4 of 6 devices used" | "4 of 4 watching now", from the tier's concurrent-stream cap, which is the only limit Jambo enforces |
| A `+` in the header | Gone. A device registers by signing in |

🔴 **The geolocation is built and cannot resolve anything yet, and only Rio can
change that.** `IpLocation` reads a MaxMind GeoLite2 file locally: no
per-request cost, no rate limit, and no viewer's address leaves the server. The
file is free but needs **a MaxMind account and a licence key**, is ~60 MB and
updates weekly, so it is fetched on the server rather than committed. Until
`GEOIP_DATABASE` points at one, every call returns null and every row shows its
address — which is exactly what the website has always shown, so the screen is
correct rather than broken. The metered alternative costs per request and the
no-account free API forbids commercial use, so neither was available.

**Storing the address was a decision, not a detail.** Browser sessions have
always carried an IP; app installs carried nothing, so `devices.last_ip` is
new. It is **one column holding only the latest value** — a device that moves
overwrites, and nothing accumulates a trail — nullable with no backfill, and
the resolved city is never persisted. Deriving at read time from an address
that already exists is a different thing to hold about somebody than a
movement history.

**The icon came from the website, after the first cut guessed it.** `markFor`
originally chose a glyph from `kind` and `platform`, which meant every browser
row drew the same picture — a television and a laptop were identical. But
`UserAgent::parse` **already decides an icon**, and the hub's own device list
draws it; the API was simply dropping it. Same thinness as the watchlist meta
line. It is forwarded now, and the app resolves it through the notification
inbox's icon map rather than a second table, which is why that map gained
`ph-desktop` and `ph-globe`.

**A television still shows a desktop glyph**, and that is faithful rather than
missed: `UserAgent::parse` has no TV case, so the website draws a desktop for a
Tizen browser too. Fixing it is a change to the shared parser that improves
both surfaces, not an app-side special case.

**Two defects the emulator caught that no test had.**

1. 🔴 **Every app install with no reported version rendered "vnull".** The
   check asked whether `app_version` was `undefined`; the server sends null,
   and `` `v${null}` `` is the string "vnull". A regression test now names it,
   mutation-proved.
2. **The section heading carried its own margins and was also placed inside a
   row that carried the same margin**, stacking into a visible hole between
   the meter and the list. A shared text style used both inside a laid-out row
   and standalone must bring no margins of its own.

**Verified, by running it on emulator-5554 against a local API with 19 real
devices:**
- The hero, the meter at its cap in amber with its one-line note, both section
  headings, and rows for app installs and browser sessions.
- **Icons differ per row and come from the server** — phone for installs,
  desktop for Chrome on Windows and Safari on macOS.
- The location line falling back to the address, including a private address
  showing `192.168.1.64` and no city, which is the guard working.
- **Sign out end to end**: pressed a row, confirmed, and the account went from
  19 devices to 18 with the right one gone, checked against the API.
- `npm run check` green: 266 tests, lint, typecheck, `api:check`,
  `tokens:check`. Backend: 18 tests across the two device files.
- **Four guards proved by mutation and restored**: the private-address check,
  the zero-limit meter rule, the version-string rule, and — the useful one —
  see below.

🔴 **One mutation proved a test that could not fail, which is the finding worth
keeping.** Deleting the private-address guard left
`test_a_private_address_has_no_location` passing, because on a machine with no
geolocation database *every* path returns null and the assertion could not tell
which one had answered. The guard was lifted into `IpLocation::isPublic` and
asked directly, where removing it now fails. **A guard whose removal changes no
test is a guard nobody is checking**, and only mutation finds those.

**Not verified:**
- **The meter below its cap.** The test account sits at 4 of 4, so the partial
  bar has only ever been seen full. Covered by unit test, not by eye.
- **A resolved city.** Nothing has ever rendered one, and nothing can until the
  database exists.
- **Tablet and TV layouts.** Neither was opened.
- **"Sign out all".** The single-row path was driven end to end; the bulk one
  was not, and it is the riskier of the two because it is a loop of requests.

**For whoever is next:** `tools/dev-catalogue/14-test-user-devices.php` seeds
four browser sessions and three app installs covering both icons, a public and
a private address, the name clamp and the Inactive section.
`JAMBO_DEVICES_RESET=1` removes them. **Do not run a mutation while Metro is
watching** — a syntax error in a watched file leaves the running app on a black
screen that survives a reload, and it cost a relaunch to work out that the code
was already fine. `scratchpad/tap.sh` taps a control by its accessibility label
using real bounds, which is the only reliable way I found to drive this screen.

### 2026-09-10 — Security, rebuilt to Rio's mockup (jambo-68)

**Status:** in progress
**Owns:** `mobile/src/screens/SecurityScreen.tsx`, and whatever new pure module
the activity list needs under `mobile/src/features/security/`
**Reads but does not touch:** `mobile/src/features/devices/list.ts`
(`whereFrom`, `lastActive`) and `mobile/src/features/notifications/icons.ts`
(`iconFor`) — **both are jambo-49's and both are imported rather than
reimplemented.** The activity rows are the same device rows their Devices
screen draws, so a second formatter here would be two opinions about one fact.

**Checked what the product can back before drawing anything, and two things it
cannot:**

🔴 **"PIN for Purchases" does not exist anywhere in Jambo.** No column, no
endpoint, no website control, no migration — a repo-wide grep finds the phrase
only in the privacy policy's text about parental consent. And the app takes no
payments at all on either build (ADR-0004 for Play, no server-side checkout for
direct), so the toggle would guard a purchase flow the app does not have. A
switch that appears to protect money and does nothing is the §1 failure in its
worst form: somebody turns it on and believes their card is protected. **Not
built. Raised with Rio.**

🔴 **The phone number has no "Verified" state to report.** `users.phone` exists
and the website's profile page edits it, but there is no `phone_verified_at`,
no verification flow and no sender configured. The row is built; the green
badge beside it is not, because it would be a fact the system cannot support.
The email badge beside it IS real — `email_verified` is on the security
resource — which is exactly why drawing both would be so convincing.

**Two things in the mockup that have nowhere to go, and one already-built thing
it drops:**
- The header's **?** icon. There is no in-app page viewer and no pages
  endpoint; `faqs` exists only as a website page. Omitted rather than wired to
  a dead end.
- **Google sign-in** is on the current screen and not in the mockup. It reports
  `google_enabled`, which is whether the SERVER has Google configured — not
  whether this account is linked. It is server configuration on a personal
  security screen, and the mockup is right to drop it.

**Nothing that works today is lost.** The 2FA recovery codes and disable
controls, and the resend-verification action, are all live on the current
screen. The mockup is a row list and those are not rows, so each stays as a
block that appears only in the state it applies to — recovery codes only when
2FA is on, resend only when the email is unverified.

### 2026-09-10 — One overlay design, enforced (session jambo-49)

**Status:** complete
**Owns:** mobile/src/ui/overlay.tsx (new)
**Shares — exact edits, nothing else in these files:**
- `mobile/src/ui/theme.ts` — one appended `dialog` block, placed after
  `watchlist` because it reuses that block's measured red.
- `mobile/eslint.config.js` — one `no-restricted-imports` rule and two
  override blocks.
- `mobile/src/features/devices/DevicesScreen.tsx` — its two `Alert.alert`
  calls become `useConfirm()`.
- `CLAUDE.md` — one section, so every session reads the rule.

**What this is.** Rio saw a screenshot of the device sign-out confirmation and
it was an Android system dialog: *"we need a universal design for our system so
that we don't use these default generic design and make sure all the other
agents use it when it's needed it should be enforced."*

**The survey found the small problem and the large one.** `Alert.alert` was two
call sites, both mine, both from today. But **six screens had each hand-rolled
a bottom sheet from `Modal`** — the watchlist's sort sheet, the player's
settings menu, the country picker, the referral code editor and the withdrawal
form.

**Why nobody had noticed the six.** The *tokens* were already shared —
`theme.sheet` — so they looked alike and each file read reasonably on its own.
The *markup* was not, so `onRequestClose` (which is what makes the Android back
button dismiss a sheet rather than the screen behind it), the safe-area padding
and the scrim's press target were each solved five times and correctly a
different number of times. **Shared tokens hide unshared components**, which is
the thing worth remembering: looking alike is not the same as being one thing.

**What was built.** `Sheet` and `useConfirm()`. The confirm resolves a promise
rather than taking callbacks, so the action stays where it is written instead
of moving inside a closure; dismissing by scrim, cancel or back all resolve
`false`, so there is no fourth outcome a caller can forget. A second question
while one is open answers the first `false` rather than dropping its promise,
because an unresolved promise is an `await` that never returns and the code
after it silently never runs.

**Almost nothing in the `dialog` token block is new**, and that is deliberate.
The ground, radius and scrim are the existing `sheet` block, so a dialog and a
sheet are visibly one object. The confirm button is `colors.primary`. The
border is the sign-in card's hairline. **The only pair that needed deciding was
the destructive one, and it was not decided here**: `watchlist.dangerFill`
under `colors.onPrimary` was already measured at 5.67:1 after a first cut
failed at 2.78:1, so the app has one destructive red rather than two that
nearly match.

**The enforcement is the deliverable, not the component.** A
`no-restricted-imports` rule makes `Alert` and `Modal` from `react-native` a
lint error, with `ui/overlay.tsx` exempt and **a shrinking allowlist** of the
six files that predate it. Nothing new may be added to that list; a line is
deleted when its file converts. That way the rule lands now without turning
another session's build red mid-slice, and converting the six stays deliberate
work in its own commit per CLAUDE.md.

**Verified:**
- **The rule bites.** A probe file importing both `Alert` and `Modal` produced
  two errors with the guidance text; deleted after.
- **The dialog rendered on emulator-5554** and is unmistakably this app: navy
  panel, brand type, outlined Cancel, red destructive Sign out. No teal, no
  grey slab, no capitalisation.
- **Cancel resolves to no action** — checked against the API rather than by
  eye: the device was still signed in afterwards.
- `npm run check` green: 266 tests, lint, typecheck, `api:check`,
  `tokens:check`.

**Not verified:**
- **`Sheet` has no caller yet.** It is written and typechecked and nothing
  renders it, because converting the six belongs in its own commit. Until then
  it is unproven on a device.
- The dialog on a tablet, and with a long title.

🔴 **A stray tap emptied the test account's device list mid-session.** My
`tap.sh` helper was called with an empty label, which matched the first node
carrying `content-desc=""` — any decorative view — and tapped an arbitrary
point that landed on "Sign out all". The helper now refuses an empty label.
Worth knowing generally: **a lookup that falls back to "match anything" is
worse than one that fails**, and on a screen with a destructive control it is
actively dangerous. Re-seed with `14-test-user-devices.php`.

**Delete account, and the switch behind it.** Rio, mid-build: *"we will have a
delete button. but we can disable this button when we please, when enabled it
shows and when disabled it disappears from the app."* Asked him first whether
"delete" should erase or close, because the two are days apart in work and one
of them touches payment records; he answered that the switch was the point, so
the button uses the endpoint that already exists.

**It is enforced twice, and only one of those is the app.** `/app/config`
carries `features.account_deletion` so the row disappears, and
`DELETE /account` checks the same setting and refuses with FORBIDDEN. A flag
only the client reads is not a switch: a build already on a phone would keep
closing accounts after Rio turned it off. The refusal happens *before* the
password is checked, so a disabled feature cannot be used to test passwords.

**It defaults to ON**, unlike `downloads` and `in_app_subscribe` beside it.
Those gate features that are not finished; this gates one that is, and a
missing settings row must not quietly remove somebody's ability to close their
own account. There is a test asserting exactly that default.

🔴 **The copy says "closes", not "erases", and Rio should know why.** The
endpoint marks the account deactivated, revokes every device and deletes every
token — sign-in stops everywhere, immediately — and keeps the person's records.
The screen says so in as many words. Google Play's data-deletion policy is not
met by this, which is a known gap rather than a hidden one.

🔴 **I overwrote uncommitted work and had to recover it from a running
process.** `SecurityScreen.tsx` had an earlier session's `TwoFactorOn` — the
recovery-codes, regenerate and disable-with-password block — below the part of
the file I had read, and I replaced the whole file. It was not in git. Metro
was still serving the bundle built from it, so the exact logic and the exact
wording came back out of `index.bundle`. **Read a file to the end before
overwriting it**, especially one git already lists as modified; the Write tool
only requires that it was read, not that it was read entirely.

**Verified on the device, both states of the switch:**
- **On:** the rows render with real state — 2FA "Pending", the email address
  with an "Unverified" badge, "Not added" for the absent phone — the activity
  list shows three devices with "This device" on the current one, and the Close
  account block opens to its consequence line and password field.
- **Off:** the entire block is absent, heading included.
- The first render also found a defect no test would have: **two chevrons on
  every activity row**, because `ListRow` already draws one for a pressable row
  and I supplied another as an accessory.

266 mobile tests, 17 PHP tests on this controller, typecheck, lint and both
generated-file checks green. The server-side gate is mutation-proved: replacing
its condition with `false` fails the refusal test, restored.

**NOT verified:** the delete request was never actually completed on a device —
doing so would destroy the test account every other session is using. The
endpoint's own behaviour is covered by the tests that existed before this and
by the two added here.

**For whoever is next:** `php -S 0.0.0.0:8095` is what the app talks to and it
died mid-session; restart it from the repo root. MySQL had died earlier the
same way.

### 2026-09-10 — Phone numbers: one format in, one format stored (session jambo-49)

**Status:** in progress
**Owns:** mobile/src/ui/phone.ts, mobile/src/ui/phone.test.ts,
mobile/src/ui/PhoneField.tsx (all new), app/Support/PhoneNumber.php (new),
app/Console/Commands/NormalisePhoneNumbers.php (new),
tests/Feature/PhoneNumberTest.php (new)

**Shares — exact edits, nothing else in these files:**
- `mobile/src/screens/ProfileEditScreen.tsx` — the phone `Field` becomes a
  `PhoneField`; nothing else on that form moves.
- `mobile/src/features/wallet/WithdrawSheet.tsx` — the same swap for
  `payee_msisdn`.
- `app/Http/Controllers/Api/V1/ProfileController.php` — phone normalised on
  write and validated.
- `Modules/Referrals/app/Http/Controllers/Api/V1/ReferralController.php` — the
  payout number validated as a real mobile line.
- `mobile/package.json`, `composer.json` — one dependency each.

**What this is.** Rio, 2026-09-10, with fifteen worked examples: the app must
recognise a Ugandan number however it is typed — `+256742078673`,
`0742 078 673`, `00256 742 078 673`, `(0742) 078 673` and the rest — carry a
dial code from the selected country, and **format live as it is typed, the way
a card number does.**

**His two decisions.** Use libphonenumber rather than hand-rolling, everywhere.
And convert the numbers already stored, with a dry run that reports what it
cannot parse rather than guessing.

**Why the library was the right call and I had leaned the other way.** Every
format he listed differs only in separators and the international prefix, so
*recognising* them needs no library at all. What needs one is knowing that
`742078673` is a real Ugandan mobile line and `123456789` is not — and one of
the two fields is a **mobile money payout number**, where a plausible typo
sends money to somebody else. That is the argument that decides it, and it is
about the withdrawal field rather than the profile one.

**Verified:** pending.
**Not verified:** pending.

**Second pass, after Rio re-armed the loop on this screen.** Two changes, both
from putting the render beside the mockup rather than from reading the code:

- **Icon tiles.** The mockup sets every row's icon in a rounded square and the
  app had no way to say that. `ListRow` gained an optional `iconTile`, off by
  default, so no screen already written changes. **A second row component for
  one screen is exactly what §4 exists to stop** — and jambo-49's finding from
  the same evening is the sharper version of it: six hand-rolled sheets looked
  identical because they shared tokens, and looking alike is not being one
  thing. The tile's size and radius are the notification inbox's captured ones.
- **A glow behind the shield.** The mockup's is a crimson halo around a badge
  the product does not own; this is two translucent circles of `colors.primary`
  behind the glyph. No new colour, and no invented asset.

**Still deliberately different from the mockup, and each was reported:** no PIN
row, no phone "Verified" badge, no help icon, a left-aligned title because that
is what every other screen in this app has, and blue rather than crimson on
Rio's standing ruling.

**Verified:** rendered again after both changes and the rows carry their tiles;
303 mobile tests across 19 suites, typecheck, lint, `api:check` and
`tokens:check` all green.

🔴 **The emulator died again mid-pass** — third time this project has recorded
it. `emulator.exe -avd Test_Android -no-snapshot-save -no-boot-anim`, then wait
on `getprop sys.boot_completed`, then `adb reverse tcp:8093 tcp:8093` before
launching the app.

### 2026-09-10 — The account area: navigation fixed, and an audit (jambo-68)

**Status:** navigation complete; the rest is a plan awaiting Rio
**Owns:** `docs/plans/account-area-audit.md` (new)
**Shares — exact edits:**
- `mobile/src/screens/CatalogueScreen.tsx` — `AppHeader` on Movies and Series.
- `mobile/src/features/watchlist/WatchlistScreen.tsx` — the same, above its own
  title bar. Two lines and an import.
- `mobile/src/screens/HomeScreen.tsx` — unchanged in the end; the header was
  moved out and put back.
- `mobile/src/navigation/TabNavigator.tsx` — a comment recording why the header
  is not here.

**Rio's report is in the plan.** Three asks — navigation, Billing's design, too
many screens — and the audit answers all three with counts rather than
impressions: 14 account screens against the website's 8 sections, 5 screens on
the mockup design language and 8 not, and a recommendation that lands at 9.

🔴 **The obvious fix for the header black-screened the app, and the reason is
worth carrying.** `AppHeader` on the tab navigator's `screenOptions` is one
place instead of four and is what anyone would reach for. `TabNavigator` is
imported by the root navigator, so pulling `AppHeader` — and through it
`AuthProvider` — into it closed an import cycle: `Cannot read property
'EventEmitter' of undefined` at bundle load, a black screen that survived a
relaunch. **The screens are leaves of that graph and can import it; the
navigator cannot.** Reverted and done per screen. The cost is that a fifth tab
will need the header added by hand, and that is written in the file.

**Two environment faults cost most of the time, neither mine:**
- 🔴 **A dependency installed while Metro was running is invisible to it.**
  `libphonenumber-js` landed at 02:00 for another session's phone field; Metro
  had been up since earlier and resolved it as missing, which crashed the app
  with `UnableToResolveError` on a path that existed on disk. `npx expo start
  --clear` fixed it. Restarting Metro after any install is the rule.
- The 8095 API server had died again, and the app showed "The route
  api/v1/auth/login could not be found" — which reads like a routing bug and is
  a dead server. Both servers answered 422 the moment it was back up.

**Verified:** the Watchlist tab now reports `Jambo Films`, `Search` and
`Account, testuser` in its accessibility tree and renders the bar above its own
title. Typecheck and lint clean on every file touched.

**NOT verified:** Movies and Series were not opened after the change — same
component as each other, and the same two lines as Watchlist, which was
rendered. No test covers "every tab has an account entry point", and one
should exist before this can drift again.

**For whoever is next:** the audit's §6 is the cheapest change in it — the
profile menu grouped into Account / Viewing / Money with a Membership card on
top, no new screens and no API. §4.1 deletes `ProfileScreen` entirely, which is
385 lines and a redundant tap.

#### Closing the phone-number slice

**Status: complete.** Files as claimed, plus `mobile/metro.config.js` and
`mobile/.env`, both explained below.

**All nineteen shapes converge**, asserted literally in both languages rather
than summarised — Rio's list IS the contract, and a case dropped from a test is
a shape somebody can type that the system will refuse. 37 assertions in
TypeScript, 31 in PHP.

**Both surfaces normalise, and that is why there is a PHP twin.** The website's
profile form and the admin write this column too. Normalising only in the app
would have left the database holding exactly the mixture this removes.

**Rio's grouping is not libphonenumber's, and his wins.** `AsYouType('UG')`
produces `0742 078673` — four then six, the official convention in Google's
metadata. His examples are threes. **Uganda is overridden and every other
country keeps the library's own rules**, because there the library is the only
specification we have; forcing threes globally would render a US number as
`+1 555 123 456 7`.

**The library earns its place on one field only, and it is worth being precise
about which.** Recognising his formats needs no library — they differ only in
separators and prefix. What needs one is knowing `742078673` is a real mobile
line, `0123456789` is not, and **`0414230000` is a landline**: a valid Ugandan
number that cannot receive mobile money. That last case is the whole argument,
and it applies to the withdrawal field rather than the profile one.

🔴 **The convenient import does not bundle, and this cost an hour.**
`libphonenumber-js/max` reaches for `../../metadata.max.json.js` — a shim the
package ships because Node's ESM loader cannot import JSON — and Metro cannot
resolve that double extension. The app built, launched, and died on the dev
launcher's error screen. The package's exports map offers the same metadata
twice: `import` points at the shim, `require` at real JSON that Metro reads
natively. `metro.config.js` now redirects **that one specifier**, deliberately
rather than setting `unstable_enablePackageExports = false`, which is the fix
usually suggested and changes how every package in the tree resolves in order
to fix one file.

**A second trap behind the first.** After the resolver was fixed the app still
failed, this time with "route api/v1/auth/login could not be found" — because
`EXPO_PUBLIC_API_BASE_URL` had not reached the bundle and the app was calling
**production**, where this API branch is not deployed. The proof was grepping
the served bundle for the URL rather than guessing. It is now in
`mobile/.env`, which Expo loads reliably, instead of a shell prefix that a
restarted Metro forgets.

**Verified, on emulator-5554 against the local API:**
- The field renders the dial code from the **selected country** — `+256` beside
  `Uganda` — and typing `0742078673` produces **`0742 078 673`** live, which is
  Rio's format exactly.
- `+256742078673` typed in full is read as international and the prefix steps
  aside.
- The backfill's dry run was watched against six seeded rows: three converted,
  one already correct, two listed as unreadable and left alone. `--write`
  applied exactly those three.
- `npm run check` green: 303 tests, lint, typecheck, `api:check`,
  `tokens:check`. Backend: 58 tests across the three affected files.
- **Two guards proved by mutation and restored**: the dry-run default, and the
  formatter's digit-preservation rule.

**Not verified:**
- **The withdrawal field on a device.** Its `isMobile` check is covered by
  tests on both sides and the field itself was never typed into on the
  emulator, because reaching it needs a wallet balance above the minimum.
- **A country other than Uganda, on a device.** Kenya and Tanzania are asserted
  in tests; nobody has changed the country picker and watched the prefix follow.

**Two findings recorded rather than fixed:**
- **An extension is silently dropped.** `0742-078-673 ext 4` parses — the
  library understands `ext` — and E.164 formatting discards it. Left as is:
  meaningless on a mobile money number, and no gateway here would take E.164's
  `;ext=` syntax. Named in a test so a future "my extension vanished" has a
  findable answer.
- **An existing profile test asserted the phone came back exactly as sent.**
  Updated, with the reason in place, because normalising is the change working
  rather than a regression.

**Email verification, both halves (jambo-68).** Rio, pointing at the Email
field in the profile editor: *"this does not show or help to verify the email
address."* He was right twice.

🔴 **The server promised a link it never sent.** `ProfileController::update`
cleared `email_verified_at` when the address changed and answered *"Check your
new address for a verification link"* — and sent nothing. The single moment a
person is actively watching their inbox for that mail was the moment it did not
come. It now calls `sendEmailVerificationNotification()`, which is the same
call `EmailVerificationController::resend` makes, so there is one mechanism
rather than two that drift.

**The existing test passed the whole time**, because it asserted
`email_verified` was false and never asked what the clearing was *for*. That is
jambo-49's finding for the third time tonight. Two cases added: the link goes
to the NEW address, and a save that does not touch the email sends nothing.
Mutation-proved — `if (false)` on the send fails the first — and note the
assertion is against `App\Notifications\QueuedVerifyEmail`, not Laravel's
`VerifyEmail`, because `User` overrides the method to queue it.

**The field now has three states**, and the third is the one that was missing:
verified, not verified with a Send verification link button, and *edited but
not yet saved* — where neither of the first two is true of what is on screen
and offering to send a link would send it to the old address. It says what
saving will do instead.

**One stale sentence removed while there.** The editor said password and
two-factor "are changed on the Jambo website". That stopped being true when
`ChangePassword` and `TwoFactorSetup` shipped. It names the Security screen now.

**Collision, resolved in the other session's favour.** jambo-49 made
`AppHeader` propless and self-navigating while I was adding it to three
screens, and had already updated my call sites by the time I typechecked. Their
version is better and I said so: four call sites passing the same two callbacks
was four chances to pass a different pair. I deleted my now-unused
`navigation` from `CatalogueScreen` and touched nothing else of theirs.

**Verified on the device:** the editor renders "⚠ Not verified" with the button
beneath it, and the accessibility tree reports `Send verification link`.
303 mobile tests, 19 PHP tests on this controller, typecheck and lint green.

**NOT verified:** no mail was actually delivered — the test asserts the
notification was dispatched, not that SMTP is configured on this box.

### 2026-09-10 — The header, both rows, as the website has it (session jambo-49)

**Status:** complete
**Owns:** nothing new
**Shares — exact edits, nothing else in these files:**
- `mobile/src/ui/AppHeader.tsx` — rewritten: bell, avatar, genre bar, and it
  navigates itself.
- `mobile/src/ui/theme.ts` — one appended `header` block.
- `design/export-tokens.mjs` — header probes, appended after jambo-72's
  pricing block. No existing probe touched.
- `mobile/src/api/endpoints.ts` — one `genres()` method.
- `HomeScreen`, `CatalogueScreen`, `WatchlistScreen` — `<AppHeader />`, two
  lines deleted from each.

**What this is.** Rio: *"now let us fix the header"*, then *"let us use the
exact header as it is on our web app."*

**The mockup turned out to be the website's own header**, which is the easiest
kind of request to satisfy honestly. `header-default.blade.php` below 992px
renders the brand, a search toggle, a **notification bell with an unread
badge**, the **viewer's avatar**, and a **second row of genre chips**. The app
had the first two and a generic account circle.

**The bell's absence had an expiry date and it had passed.** The old docblock
said a bell would open a list always empty for reasons a viewer cannot see,
because the notifications screen did not exist. It does now, so the bell
arrives with it — exactly as that note said it would.

**Four call sites became none.** Every tab passed the same `onSearch` and
`onAccount` pair; adding the bell would have made it eight copies of one
decision. The header owns where its own controls go now.

🔴 **Two probe defects, both of which produced plausible values.**

1. `.jambo-genre-chip` matched the **first** chip in the bar, which is "All",
   which is *active* on the home page. The resting chip captured as white on
   black and the active state came back identical to it — **every genre chip
   would have been drawn as the selected one.** Same trap as the sign-in
   field's focused border, same fix: ask for `:not(.active)`.
2. `border-bottom-color` on the genres bar read `#d1d0cf` — the text colour,
   because CSS defaults a border colour to `currentColor` when no border is
   set. The widths were captured too and are **0**. **A colour alone cannot
   tell a hairline from a phantom.**

🔴 **The logo, and Rio was right for a reason I had backwards.** He said it
looked smaller than the website's. The captured site logo is 24 tall and the
app's box was `108x32`, which looked larger — so the measurement appeared to
contradict him. It did not: **the brand is a wide wordmark**, so `contain`
fitted it to that box by *width* and it landed around **16dp tall**. A fixed
box can only ever be right for one logo's aspect, and the logo is
admin-uploadable.

Height drives it now, as the site does — `height: Npx; width: auto` — with the
ratio learned from the image on load and the captured pair seeding it so the
header does not reflow from nothing on launch. **It went from ~16dp to 24dp.**

**Contrast measured before spending, four pairs:** the resting chip at
12.03:1, the active chip at 21:1, the header icon at 14.35:1, and 🔴 **the
unread badge at 3.01:1**, white on the brand blue. That is under AA and it is
the tenth entry in the tally — it is the pair the whole app already wears and
the `badge` block already ruled it not a slice's to re-decide alone. Kept,
measured, named.

**Verified:** rendered on emulator-5554. The bell shows a real unread count of
2, the avatar is the account's own photo, the chip bar scrolls with All active
and Action, Thriller, Horror, Comedy, Sci-Fi behind it, and the logo is
visibly taller than before. `npm run check` green: 303 tests, lint, typecheck,
`api:check`, `tokens:check`, 244 tokens with **zero existing values changed**.

**Not verified:**
- **No chip has been pressed.** The genre chips navigate to the Taxonomy
  screen and that route is typechecked, but nobody has watched one open.
- **The active chip only ever renders on Home.** Whether a genre chip lights
  up when its own archive is open is not wired — the bar does not read the
  current route yet, so "All" is always the active one. Named because it is a
  visible half-truth rather than a missing feature.
- Tablet.

**For whoever is next:** restart Metro after any `npm install` — a running
Metro does not see a new package and fails with an unresolvable path that
exists on disk.

#### The header crashed the Notifications screen, and the logo went up again

🔴 **Adding the bell made the Notifications screen unopenable, and Rio found
it.** The header cached its unread count at `['notifications', null]` — the
same key the inbox uses for its All tab. The inbox reads that key with
`useInfiniteQuery`, which stores `{ pages, pageParams }`; the header read it
with `useQuery`, which stores the response object. **Whichever mounted first
won the cache entry**, and when the header won, `getNextPageParam` found no
`pages` and threw `Cannot read property 'length' of undefined`.

**Two queries may share a key only if they share a shape.** The comment I had
written there argued the shared key saved a fetch, and it did — the cost was
that the screen could not be opened at all.

**The fix is `['notifications', 'unread']`, and the second element matters.** A
key outside the prefix, like `['notifications-unread']`, would be safe from the
collision **and would stop refreshing**: marking something read invalidates
`['notifications']`, which matches by prefix, so the badge would hold a stale
count for ever. Staying under the prefix keeps that working, and it cannot
collide because the second element is a *category* and the server's categories
are a closed, tested set that does not contain "unread". A test in
`list.test.ts` asserts exactly that.

**The shape of the bug is worth more than the fix.** It was a crash **on the
Notifications screen caused by an edit to the header** — two files with no
import between them, coupled only through a cache key. Nothing in a typecheck,
a lint or 303 unit tests saw it, and it needed the app open on the right screen
in the right order to appear.

**The logo went up again**, to 30 from the captured 24, at Rio's request after
seeing it on a device. **A deliberate deviation, recorded in `theme.ts` beside
the token it departs from**, so the site value is still what it is measured
against. It is inside the site's own range rather than invented: the CSS rule
is `height: 36px` and it only renders shorter because a browser's cramped
mobile header constrains it, and a native header is not a 390px browser window.

**Verified:** the bell opens the inbox again and the rows render; the logo is
visibly larger. `npm run check` green at 305 tests.

### 2026-09-10 — Password reveal, the Kangaru strength meter, and the copy that lied (jambo-68)

**Status:** complete
**Owns (new):** `mobile/src/ui/passwordStrength.ts`, `passwordStrength.test.ts`,
`PasswordMeter.tsx`, `PasswordField.tsx`, `useAvatarUpload.ts`
**Shares — exact edits:** `RegisterScreen` (one meter, one import);
`ChangePasswordScreen`, `TwoFactorSetupScreen`, `SecurityScreen` (`Field` →
`PasswordField` on password boxes only); `ProfileEditScreen` (avatar block,
email block, one stale line); `ProfileScreen` (60 lines of upload replaced by
the shared hook); `ProfileController` + its test.

**Rio's three asks, and what each turned out to be.**

🔴 **"Hide and unhide for all password fields including in the auth."** It
existed on two screens of eight. Sign in and Register each had their own
hand-positioned `RevealToggle` over their own field; the six behind them —
change password's three, two-factor's confirmation, Security's two prompts —
had none. **This is jambo-49's six-hand-rolled-sheets finding one layer down:**
two implementations that looked identical *because they shared a token and a
child component*, while the offset, the hit area and the accessibility state
were each solved separately. One `PasswordField` now, importing their toggle
rather than copying it. Its anchor is measured from `FIELD_HEIGHT` rather than
the literal 45 the sign-in screen hard-codes.

**"The strength helper as we did in Kangaru."** Found at
`Kangaru/mobile/src/auth/passwordStrength.ts` — he first said Forever Loved,
which has none, and corrected himself. **Ported, not rewritten**, with its 19
tests. Two changes only: the floor is 8 because that is what
`Password::defaults()` holds on this server (verified by reflection — min 8,
mixedCase/numbers/symbols/uncompromised all false), and the dictionary names
Jambo. The argument for porting is the tests: the reasoning about passphrases,
about naming obvious junk rather than scoring it, and about a visible scale was
already defended there. Rendered on the device, "Jambo2026" reads Weak with
three ticks and *"That contains one of the first passwords anyone would
guess"* — the dictionary change working.

🔴 **"Hardcoded texts with fake information."** He was right and the worst one
was not the example he gave. The profile editor's avatar caption read
*"Profile pictures are changed on the Jambo website"* while `ProfileScreen`,
one tap away, had a working picker and upload the whole time — **the app was
sending people to a browser for something it already did.** The editor uploads
now, and the flow moved to `useAvatarUpload` which both screens call rather
than the editor growing a second copy of a permission prompt, a MIME sniff and
four failure sentences. The second false line, about password and two-factor
being website-only, went earlier the same night.

**The pattern is worth more than the three lines:** a sentence saying "this
happens elsewhere" is a claim about capability, and capability moves. All three
were true when written. §9 of the audit says it as a rule — when a feature
lands, the sentence that apologised for its absence is part of that feature's
diff.

**Verified:** rendered on the device — all three change-password fields report
`Show password`, and the meter draws its bar, its four-rule checklist and its
hint. 333 mobile tests across 20 suites (up from 303; the port brought 19 and
two more came from the email work), typecheck and lint clean on every file I
touched.

**NOT verified:** the two-factor and Security password boxes were not opened on
the device — same component, same one-line swap as the three that were. The
avatar upload was not run end to end from the editor; the picker needs a real
photo and a permission grant, and the flow itself is unchanged code that
already worked on the other screen.

**Coordination:** jambo-49 claimed `features/notifications/**`,
`ui/SelectionBar.tsx`, one RootNavigator route and two appended theme blocks
while I was mid-edit. No overlap with anything above. Their Notifications
screen currently carries four unused-variable warnings because it is mid-edit;
I filtered it out of my typecheck rather than touching it. Emulator handed to
them.

**For whoever is next:** §9.4 of `docs/plans/account-area-audit.md`. Deleting
`ProfileScreen` is now more urgent than when the audit was written, not less —
the upload lived on the read-only screen while the editor denied the capability
existed, which is a split so bad that one half was lying about the other. The
shared hook is the first half of that merge already done.

**The meter's hidden fifth rule (jambo-68).** Rio, on a device: he typed
`Jambo2026@N`, watched all four boxes go green, and the bar still read Weak on
one segment. *"this says weaker but all the checks are checked how does the
person know"*.

🔴 **He was right and the score was not wrong — the explanation was missing.**
The cap came from `predictable()`, a real rule doing real work that was never
on the checklist. So the screen showed four reasons to be confident and one
verdict contradicting them, with nothing joining the two. That is exactly the
failure this module's own header warns about: a bar whose standard is invisible
grades against something nobody was told, which is a verdict rather than a
guide.

**The fix is to stop grading in secret.** `predictable` is now a fifth
requirement, "Not an obvious guess", rendered unticked beside the four that
passed, and its sentence is set in the site's alert red rather than the same
quiet grey as every other hint. **It caps but never credits** — a password does
not earn a segment for merely being absent from a dictionary — and there is a
mutation test on that specific point, because it is the easy thing to get wrong
when adding a rule to a scale.

🔴 **The bug is in Kangaru too**, where this was ported from. It needs a
dictionary hit to surface and nobody there had typed one. Told jambo-49 in case
it comes back the other way.

**A comment claimed 8.3:1 and the colour measures 11.12:1.** Caught by running
the numbers rather than trusting the sentence I had just written. A wrong
figure in a comment is worse than none, because the next person spends it.

**Verified:** 21 strength tests (up from 19), 335 mobile tests overall, lint
and typecheck clean. Mutation-proved twice: forcing the fifth rule to `met`
fails two cases, and counting it toward the score fails the segment test.

**NOT verified on the device yet** — jambo-49 has the emulator and I have asked
for it back for one screenshot.

### 2026-09-10 — Notifications: bulk actions, long-press selection, and one overflow instead of two pills

**Session:** jambo-49
**Status:** complete; verified on the emulator except the two states noted below
**Owns:** mobile/src/features/notifications/** (NotificationsScreen, NotificationRow,
NotificationSettingsScreen, list.ts, list.test.ts), mobile/src/ui/SelectionBar.tsx (new)
**Shares:** mobile/src/ui/theme.ts (appended `selection`; one key in `notifications`),
mobile/src/ui/list.tsx (one optional prop), mobile/src/navigation/RootNavigator.tsx
(the Notifications route only) — all three cleared with jambo-72 before editing

**What Rio asked for**, in his words, in this order:

1. *"we can click and view the notfication but we need the abillity to make all
   read and clear them"*
2. *"we can have the long press event like long press to select and we can
   select multiple"*
3. On seeing the first cut: *"i think we can hide these initially or we can
   upgrade this in the way the feels clean and modern simple creative"*

**What the third one was about, because it is the interesting one.** The first
cut put "Mark all read" and "Delete all" above the chips as a filled blue pill
and an outlined red one, copying the website's own two header buttons. On the
site they sit in a wide desktop header and read as chrome. On a 360dp handset
they are the loudest thing on a screen whose entire job is a list, and the
destructive one shouts hardest. They are housekeeping, so they now live behind
a single "⋮" in the stack header, in a `Sheet`, with Notification settings —
which is what the gear used to open. **One header control replaced two pills
and an icon**, and the inbox above the fold is nothing but the inbox.

**Long-press selection.** Rows take `selecting` / `selected` / `onLongPress`.
The tick replaces the 40x40 leading square rather than sitting beside it, so
entering selection does not reflow every row under the thumb that is pressing
one. Selection mode *is* the set being non-empty, so unpicking the last row and
cancelling are the same thing rather than two states that can disagree.

🔴 **A layout shift caused by my own fix, found on the device and worth
recording as a shape.** Removing the header action row on entering selection
moved the whole list 97dp up the screen — the long press moved the row it had
just picked, and the next tap would have landed on a different one. The row now
stays mounted and goes dim and disabled instead. **A gesture must never move the
thing it acted on**, and "hide the control that no longer applies" is the
instinct that breaks it.

**A shared component, and deliberately only half a conversion.**
`mobile/src/ui/SelectionBar.tsx` is new and the inbox uses it. **WatchlistScreen
still carries its own private copy of the same bar and I did not touch it** —
this project's rule is that existing flat code is converted in its own commit,
never as a side effect of a feature. Whoever does that conversion: the tokens
are already shared at `theme.selection`, which names the Watchlist's own values
rather than restating them, so the bars cannot drift apart in the meantime.

**Two small findings on the way through:**

- `ListRow` drew a chevron for every pressable row. A caret promises a screen,
  and "Mark all read" acts and closes. New optional `chevron` prop, default
  true, so nothing already written renders differently.
- The row's accessibility label joined its parts with ". " onto a message that
  already ended in one, so TalkBack read "catalogue.. 3m ago" out of the seeded
  inbox. Trailing terminators are stripped before the join now.
- The filter chips announced as bare "Account", "Movies" and so on, and
  "Account" collided with the header's own account control for anything
  searching by label — it cost jambo-72 three misnavigations. They announce as
  "Account filter" now. A chip that filters is not a place.

**Moved back where it belongs.** "Mark all read" was parked on
NotificationSettingsScreen when the inbox header had no room for it. Rio's
instruction supersedes that and it is gone from the settings screen. Two ways
to mark an inbox read is one too many.

**Tests:** `list.test.ts` is 30 green. The three new helpers — `toggle`,
`idsOf`, `unreadAmong` — each have a guard proved by mutation: mutating the Set
in place, driving `idsOf` off the Set instead of the list, and asking
`read === false` rather than `!== true`. All three mutants died.

**Verified on the emulator** (API 35, testuser@jambo.test, 40 seeded rows):
the overflow sheet with all three rows; the sheet with only Settings when the
inbox is empty; long-press entering selection; the bar showing "1", "Mark read"
and "Delete"; the tick and the primary border on the picked row. Rio emptied
the inbox himself through "Delete all" while I was working, which is the
end-to-end proof that the destructive path and its confirmation work.

**NOT verified on the device, because Rio took the emulator mid-session:** the
chevrons being absent from the two action rows after that fix, and the
confirmation dialog's own appearance for "Delete N notifications". Both
typecheck and both are the same `Sheet` and `useConfirm` every other screen
uses, but neither has been photographed. Look at those two first.

**Not built, deliberately:**

- No bulk endpoint for a subset. Deleting eleven selected rows is eleven
  idempotent `DELETE /notifications/{id}` calls, which is what the API offers.
  A partial failure leaves a state the refetch reports rather than one nobody
  can reason about. A real bulk endpoint would be a server slice.
- No "Select all". Long press plus taps covers what Rio asked for, and the
  overflow already has the whole-inbox versions of both actions.
- "Delete all" still means the whole inbox even while a chip is filtering,
  because `DELETE /notifications` takes no filter. The dialog says "all
  notifications" in so many words, and selection covers the subset case.
- WatchlistScreen's ActionBar, as above.

**Handed off:** Rio asked me mid-session to fix being unable to subscribe to a
membership plan. That is ADR-0004 territory — the Play build is consumption-only
on purpose and the `direct` build's PesaPal checkout has never been built, on
either side of the wire. jambo-72 has taken it and is putting the tradeoff to
Rio rather than either of us adding a Subscribe button that could cost the Play
listing. I touched nothing in Subscriptions or PlansScreen.

### 2026-09-10 — In-app checkout: ADR-0004's missing half (jambo-68)

**Status:** server and app complete; not yet run against a real gateway
**Owns:** `Modules/Payments/app/Services/SubscriptionCheckout.php` (new),
`tests/Feature/Api/V1/SubscriptionCheckoutTest.php` (new),
`docs/adr/0005-over-the-air-updates.md` (new, Proposed)
**Shares — exact edits:** `SubscriptionController` (one method plus a private
url helper), `Modules/Subscriptions/routes/api.php` (one route),
`RouteServiceProvider` (one limiter), `PaymentController` (its tier branch now
calls the service), `docs/api/openapi.yaml` (the POST, plus one unrelated line
repaired), `mobile/src/features/billing/api.ts` (one function),
`mobile/src/screens/PlansScreen.tsx`.

**Rio: "we can not subscribe to any membership plan fix this."** jambo-49
asked before touching it, which was right — the answer is that it was **both
deliberate and unbuilt**, and only reading ADR-0004 tells you which half is
which. Play is consumption-only because Google requires Play Billing for
subscription video outside IN/KR/EEA/US and forbids pointing elsewhere; the
direct APK was always meant to take PesaPal and never got it. Put the tradeoff
to Rio rather than making it work, because a Subscribe button on the Play
build is the one change here that could cost the listing. He chose the direct
APK.

**The extraction is the whole slice.** `coverage.md` §4 said in-app checkout
needed the website's order creation pulled into a shared service, and it did.
`SubscriptionCheckout` now owns the four money decisions — price read off the
tier, snapshot frozen into metadata, referral discount computed and never
accepted, order written before the gateway is called — and **the website's
pricing page calls it too**. The referral tests passing unchanged is what says
the extraction preserved behaviour rather than approximated it.

🔴 **The test that matters sends `amount: 1` and asserts the gateway was
handed 30000.** Mutation-proved by making the service trust a client amount;
it failed with "1.0 is not identical to 30000.0" and was restored. That test
fails the day somebody adds an `amount` field for convenience, which is the
only way this endpoint can go wrong.

**Rio, mid-slice: "we have the api that handle the payment/subscription
because we are yet to have more payment gateways and this is done on the
server side."** Already true and now verified: the request carries a slug and
nothing else, and the gateway is resolved from `payments.default_gateway`
through a contract with a registry. A second gateway is a config change the
app never sees.

**`Linking` rather than a Custom Tab, deliberately.** `expo-web-browser` is a
native module and adding it means rebuilding the dev client, which would have
stopped another session mid-verification. The payment completes identically
because the gateway confirms to the SERVER; the app polls the order on focus.
Swapping in a Custom Tab later is one call site.

🔴 **A broken line in `openapi.yaml` had been failing four spec tests.** In the
wallet block: `{ description: Refused, with a message written for a viewer }` —
an unquoted comma ends a value in a YAML flow mapping. The wallet endpoint and
its own tests were green, so nothing pointed at it; the only symptom was a
different test file failing to parse a file nobody was looking at. **It was the
guard that checks the spec documents every route**, so my new endpoint could
have shipped undocumented behind it. Quoted, and all four pass.

**Verified:** 243 API feature tests, 335 mobile tests, lint, typecheck,
`api:check` and the spec suite all green.

**NOT verified:** no payment has been run end to end. The gateway is faked in
tests and PesaPal credentials are not configured on this box, so the redirect
URL, the callback and the activation listener are unexercised from the app.
The Subscribe button was not rendered on the device either — the running build
is the `play` variant, where `CAN_SUBSCRIBE_IN_APP` is false and the button
correctly does not exist. **Seeing it requires a `direct` build**, which is the
next thing anyone touching this should do.

**Rio, also mid-slice: "we don't want to keep on pushing updates for the minor
updates to playstore."** That is over-the-air updates and it is genuinely
unbuilt — `expo-updates` is not installed and the only update mechanism today
is the `min_app_version` gate that sends people to the store.
**`docs/adr/0005-over-the-air-updates.md` is Proposed**, recommending
self-hosted `expo-updates` on Jambo's own server rather than EAS Update,
because "from our side" is the requirement. Not started: it is a native module,
so it needs one more store release and a dev-client rebuild, and doing that
while three sessions are working would stop all of them.

---

### 2026-09-10 — the home hero, at the website's design

**Status:** complete
**Owns:**
- `Modules/Content/app/Http/Resources/MovieResource.php` (new `hero()` shape)
- `Modules/Content/app/Http/Resources/ShowResource.php` (new `hero()` shape)
- `Modules/Content/app/Http/Resources/Concerns/RendersHeroFields.php` (new)
- `mobile/src/ui/rails/Hero.tsx`
- `mobile/src/ui/rails/TextureText.tsx` + `.test.tsx` (new)
- `mobile/src/ui/rails/BannerButton.tsx` (new)
- `mobile/src/ui/rails/banner.ts` + `banner.test.ts` (new)
- `docs/adr/0006-porting-the-websites-banners-to-the-app.md` (new)
- `mobile/assets/streamit/texture-text.webp` — **renamed** from
  `top-ten-texture.webp`. It is the site's own `texure.webp`, byte-identical,
  and it now fills the headline as well as the numerals, so the old name lied.

**Shares — the exact edit in each:**
- `Modules/Frontend/.../Api/V1/HomeController.php` — `hero` maps through
  `heroCards()` instead of `cards()`, plus that one new private method.
- `Modules/Frontend/app/Services/HomeRailsService.php` — `buildHero()` returns
  through a new `withHeroAggregates()`, which batches two `loadAvg` calls.
- `mobile/src/ui/rails/TopTenCard.tsx` — its private `TextureNumeral` now
  delegates to `TextureText` rather than holding a second copy of the SVG
  pattern trick. **If the Top 10 numerals ever render blank, look here first.**
- `mobile/src/ui/theme.ts` — one new `banner` block, nothing existing touched.
- `mobile/src/ui/format.ts` — one added `formatBannerRuntime`, beside its
  sibling and with a comment on why the site writes a runtime two ways. NOTE:
  this file is still untracked in the tree, so it belongs to an uncommitted
  slice of somebody else's; the addition is additive only.
- `mobile/src/api/catalogue.ts` — `MovieHero` / `SeriesHero` / `HeroCard`
  types beside the existing `TitleCard`.
- `mobile/src/screens/HomeScreen.tsx` — `openTitle` widened to take a
  `HeroCard`, and the cast at the `<Hero>` call site dropped.
- `mobile/src/api/schema.d.ts` — regenerated. **It also picked up jambo-72's
  `subscription/orders`, which had not been regenerated after their spec edit,
  so `npm run api:check` was red for an unrelated reason.**
- `lang/en/streamTag.php` — one word. `starrting` had the value "Starting", so
  the OTT home said "Starting:" over the cast line while the guest home said
  "Starring:" through a different key. The misspelled key stays; several
  blades reference it.
- `mobile/.env` — **restored** `EXPO_PUBLIC_API_BASE_URL`; see the trap below.
- `docs/api/openapi.yaml`, `CHANGELOG.md`.

**What this is:** Rio asked for the webapp home page's banners to exist in the
app at the website's own design. A scan of `/` (`ott-page.blade.php`, which is
the real home page — `/home` is the secondary one) found three banner-shaped
blocks and every ordinary rail already ported. The hero is first.

The app's hero draws a portrait poster, the title, and a year. The website's
draws a full-bleed backdrop, a texture-filled headline, a certification or
season badge, a five-star average with the IMDb mark, runtime, a three-line
synopsis, tags / genres / starring, Play Now, and a poster thumbnail strip
that doubles as the slider's navigation. The gap is data before it is styling:
`hero` is built from `MovieResource::card()`, which keeps `backdrop_url`,
`synopsis`, `genres`, `tags` and `cast` behind the `detail()` flag.

**Not mine, named so nobody waits on me:** the other two banners — the Top 10
Movies of the Day vertical slider and the Top 10 Series of the Day tab slider.
Neither has any API payload at all (`verticalFeatured` and `tabSeries` never
leave `HomeRailsService`), and neither has a `HomeSection` key, so on the
website they sit at fixed blade positions. They are the next two slices.

**What it actually took, and the parts worth knowing:**

- **A third resource shape**, `hero()`, on both MovieResource and ShowResource,
  sharing a `RendersHeroFields` trait. Not `detail()`: that carries
  `views_count`, `published_at`, `categories` and every season of a series
  onto the critical path of a first paint. ADR-0006 records the reasoning.
- **`TextureText` is now shared with the Top 10 numerals.** The SVG-pattern
  trick was private to `TopTenCard`; writing a second copy for the headline is
  the fork this repo's rules forbid, so it moved out and both use it. It
  gained a measuring pass: SVG has no ellipsis, so the string is laid out by an
  invisible `<Text numberOfLines>` and RN is asked what it would have drawn.
- **Two queries for the whole banner** instead of the blade's two N+1s.

**Verified — the design, by measurement rather than by eye.** Rendered `/` in
headless Edge at 390x844, rendered the app on emulator-5554, cropped both
banners to the same box and compared:

- **Luminance across the slide, eleven columns**: the largest difference is 7
  of 255, under 3%. The two-layer horizontal scrim is right, which a
  screenshot could not have told me — my own eye said the app was too bright
  and it was wrong, the two captures were just at different scales.
- **Vertical rhythm, nine text bands**: found the app's content block 41pt
  short of the site's, then re-measured after fixing and accounted for every
  remaining point. Three causes were real and are fixed: taxonomy lines at
  RN's default ~18 instead of Bootstrap's 21, the CTA label the same, and the
  headline's missing `mb-1`. Rendered three times and measured after each.
  The block went 41pt short → 35 → **32**, and every one of the 32 is now
  accounted for: **14 of it is the meta row**, which the site draws 48 tall
  only because its IMDb logo is 32px and that mark is deliberately not drawn;
  the other ~18 is font-metric drift between Roboto and the site's face,
  spread over six blocks at 1-6pt each. Below the meta row the two renderings
  agree to within 6pt everywhere and to within 3pt on the taxonomy lines.
  **That is where I stopped: the residual is under a few points per block and
  chasing it is over-fitting to one title string in one language.**

  The measuring scripts are `scratchpad/rows.py` (band profile) and
  `scratchpad/compare.py` (luminance profile). They are scratch, not committed
  — the method is in the memory, and re-deriving them is ten minutes.

**Verified — the payload.** `GET /api/v1/home` returns 6 hero slides carrying
backdrop, synopsis, three genres, three tags, cast, and for a series
`seasons_count` and `episode_runtime_minutes`. Checked against the site's own
first slide: same title, same NC-17, same three genres, same two tags.

**Verified — the tests bite.** Two API mutants killed (hero sent as `card()`;
`stars_avg` falling back to 5) and two app mutants killed (half-star boundary;
truthiness instead of a type check). A third app mutant — branching on
`seasons_count`'s presence instead of on `type` — **survived**, which meant a
missing test rather than a passing one. The case it exposed is reachable:
`seasons_count` is `whenLoaded`, `rating` is on every SeriesCard, so a series
whose seasons were not loaded would have been badged "PG". Test added, mutant
now dies.

**Not verified:**

- **Nothing on a real handset or a TV.** Emulator only, API 35, 1280x2856.
- **The half star has never been seen**, because no title in the database has
  a rating. `starRow` is tested; the glyph is not photographed.
- **A hero item with a real backdrop.** Every title in this database falls back
  to its poster, so the 5:7-into-16:9 top-anchored crop is what I saw. A true
  backdrop has never been through it.

**Deliberately not built:**

- **The thumbnail strip.** Not an omission: the site's own strip computes to
  `display: none` at 390pt and its dots take over. The app draws the dots.
- **The IMDb wordmark**, and **the site's five-star fallback**, and **its `?: 'PG'`
  certification fallback.** All three are in ADR-0006. The ratings table is
  empty — 0 rows against 79 movies — so those five gold stars are the only
  rating the live site has ever shown anyone.
- **A fix to the website's own `?? 5`.** It changes what every visitor sees,
  which is Rio's call.
- **The other two banners.** Both need a server payload first.

**For whoever is next:** jambo-72 holds the Subscriptions API controller,
`SubscriptionCheckout`, `PaymentController`'s tier branch, `PlansScreen`, the
billing `api.ts` and `CheckoutSheet.tsx`. No overlap with this.

🔴 **Two traps this session, both from a whole-file write where an edit
belonged.** `mobile/.env` held `EXPO_PUBLIC_API_BASE_URL=http://10.0.2.2:8090/api/v1`
and was replaced rather than appended to; `src/config/env.ts` defaults to
`https://jambofilms.com/api/v1`, so the next bundle would have pointed a dev
emulator at production — registering real devices, reading live data, and
creating real orders on a checkout under test — while looking like a normal
local run. Restored, with a comment. Separately, `mobile/src/api/schema.d.ts`
had not been regenerated after an openapi edit, so `npm run api:check` was
red for a reason that had nothing to do with the file being edited.

🔴 **`StyleSheet.absoluteFillObject` does not exist in this build's types.**
`react-native-tvos@0.86.2-0` ships `absoluteFill` but not the spreadable
object, so the idiom every RN codebase uses fails `tsc`. It cost two sessions
time in one night, which is this repo's own threshold for a rule. Write the
four sides out, or use `absoluteFill` as a whole style.

---

### 2026-09-10 — the banner's stars, removed from both surfaces

**Status:** complete
**Owns:** the same files as the entry above, plus
`mobile/src/ui/rails/ImdbMark.tsx` (new).
**Shares:** `hero-banner.blade.php`, `vertical-banner.blade.php`,
`Pages/MainPages/index-page.blade.php`, `components/cards/movie-slider.blade.php`,
`components/sections/parallax.blade.php` — exact edit in each: the star row
deleted, a comment left saying why, the IMDb mark untouched.

**What this is:** Rio looked at the rendered banner and said *"the featured
banner is not complete on mobile we are missing on some elements the stars
etc"*. He was right that they were missing, and it was my decision rather than
a bug — I had left them out and documented it instead of asking. That was the
mistake worth recording: **a decision that changes what a viewer sees is the
owner's, and documenting it is not the same as asking.**

Put to him with the facts, he went further than I had. Not "leave the site
alone", but **remove the invented rating from both surfaces**, and keep the
IMDb mark on both.

**What made the decision easy once it was found:** the `ratings` table is
empty — 0 rows against 120 titles — and **nothing in the product can write to
it.** No controller, no endpoint, no form, on the website or in the app; only
a seeder. So `?? 5` was never a fallback for a quiet edge case, it was the
only value jambofilms.com had ever shown. Grep for `Rating::` before arguing
about this: seven files read it, none write it.

**It was in five places, not one.** `hero-banner`, `vertical-banner`, the
guest home hero, the `parallax` placeholder, and `movie-slider` — the last one
hardcoding 3.5 stars on **eight listing pages** (`/movie`, `/series`,
`/upcoming`, `/genres/*`, the VJ pages, taxonomy archives) without reading the
database at all. `grep -rln "ratting-start"` finds them; there are none left.

🔴 **A real bug in the same block.** The guest home hero drove its star loop
from `$movie->rating`, which is a content **certification** — so
`$i > "NC-17"` compared an int against a string, and the certification was
printed next to the IMDb mark where a score belongs. Viewers read "NC-17" as a
rating out of ten. It is a badge now.

**Also removed:** `stars_avg` from both hero shapes, `starsAverage()` from the
trait, the `loadAvg('ratings', 'stars')` from `withHeroAggregates`, the field
from `openapi.yaml`, and `starRow`/`bannerStars`/`hasMeta` with their tests
from the app. A field nothing draws grows a consumer later.

**The IMDb mark in the app is `ui/rails/ImdbMark.tsx`** — the site's own SVG
ported to `react-native-svg` paths, not redrawn and not added to the asset
pipeline. Metro has no SVG transformer configured and adding one for a single
2KB file is a build change for nothing. It is 32x32 with the default `meet`
fit because `.imdb-img` is, and **that 32pt box is what makes the meta row 48
tall on both surfaces** — it was the single largest item in the 14pt gap the
previous entry could not close.

**Verified, both surfaces re-rendered and re-measured:**

- Site at 390pt and app on emulator-5554 both now read badge, IMDb mark,
  runtime. No stars anywhere.
- **Luminance across the slide: max difference 6 of 255, 2.4%.**
- **Vertical rhythm: within 2-4pt at every one of nine bands**, down from 32pt
  of accumulated drift. What is left is Roboto against the site's face and
  nothing else — the 14pt meta-row gap closed when the mark went in.
- 14 API home tests, 356 app tests, lint, typecheck, spec and generated types
  all green.

**Not verified:** the four listing-page banners and the parallax block were
changed but not re-rendered — the blade edits are deletions inside an existing
flex row and the Frontend suite (89 tests) passes, but nobody has looked at
`/movie` or `/genres/*` since. Look there first if something is off.

**Deliberately not built:** a real rating feature. It was offered to Rio as
option 2 — a rate control on both surfaces, an endpoint, the row driven by the
average — and he chose removal instead. If it is ever revived, ADR-0006 keeps
the layout and the arithmetic (`starRow`'s three cases) in its history.

**Left behind on purpose:** `$movieRating` is still passed by eight pages and
now gates nothing. Tidy-up for whoever is next in `movie-slider`.

**Verified against live PesaPal (jambo-68).** Rio supplied credentials and put
them in settings himself; `payments.pesapal_environment` is **live**, not
sandbox. One order was created through the new endpoint to prove the chain:

```
POST /api/v1/subscription/orders  {"tier_slug":"basic"}
→ checkout.mode  webview
  checkout.url   https://pay.pesapal.com/iframe/PesapalIframe3/Index?OrderTrackingId=…
  reference      JAM-48-5SSMAYDNZY
```

Order row: `pending`, `15000.00 UGX`, gateway `pesapal`, tracking id stored,
snapshot `{"tier_id":4,"price":"15000.00","currency":"UGX"}`. Unpaid and
harmless — an abandoned checkout, which a live merchant account sees
constantly — but it is a **real** order and worth knowing before anyone runs
the suite against these settings.

🔴 **The URL PesaPal returns is literally an iframe**, which is what Rio meant
when he corrected the design: *"the same way we are given iframe from
pesapal"*. The contract passes it through untouched as `mode: webview` plus a
URL, so the gateway's own presentation reaches the app without the app knowing
whose it is. That is the property that lets a future gateway ship without an
app release, and it is now demonstrated rather than argued.

🔴 **`return_url` and `cancel_url` came back as `127.0.0.1:8090`**, because
`payments.callback_base_url` is unset on this box. Harmless locally and wrong
in production: a phone cannot resolve the server's loopback. **That setting has
to be the public origin before any build ships**, and it is the same setting
the website's own callbacks already depend on.

**Still NOT verified:** no payment has been *completed*, so the callback, the
activation listener and the app's return-and-poll path are unexercised. The
Subscribe button has never been rendered either — it exists only in a `direct`
build and the emulator runs `play`.

🔴 **The credentials were pasted into a chat transcript and the account is
live.** They are in the settings table where they belong, and a scratch script
that briefly held them was deleted; no tracked file contains them. Rotating
them once testing is finished is Rio's call and worth making.

**The checkout became a popup (jambo-68).** Rio, three messages in a row, each
sharpening the same point: *"the same way we are given iframe from pesapal"*,
then *"we are supposed to use a popup, not a redirect"*, then *"we don't have
to send people outside the app, and also remember this controller via the api,
which is reusable by any other future payment gateway we integrate"*.

**The API was already right; the app was not.** The endpoint answers
`{ mode, url, return_url, cancel_url }` and the mode has said `webview` since
the contract was written. The app fell back to the system browser because
there was no WebView in the binary — a missing native module, not a decision.
`react-native-webview` is installed and the dev client rebuilt.

🔴 **`Sheet` grew two props rather than the app growing a seventh overlay.**
A payment needs full height, because a gateway's page cannot be measured, and
it must not close on a stray scrim tap while somebody is typing a mobile-money
PIN. Both are now `fullHeight` and `dismissOnScrim` on the shared component, so
**the lint rule banning a raw `Modal` needed no exemption** — which is the
better outcome than adding a seventh line to a list whose author wrote that it
only ever shrinks.

**Finishing is the server's word, never the sheet's.** Reaching `return_url`
means the payer got to the end of the gateway's flow, not that money arrived —
mobile money is a USSD prompt that can be approved after the page has moved on.
So the sheet reports *which* of Jambo's URLs ended it and the screen behind
asks the order. A cancel leaves the reference outstanding for the same reason.

**Redirect is still honoured, and that is the contract rather than a
leftover.** The server picks a mode per order, so a gateway that cannot be
framed still sells, and a build that meets a mode it does not know falls
through to a URL it can always open. That is precisely what stops a future
integration needing an app release, which was Rio's stated reason.

🔴 **`StyleSheet.absoluteFillObject` does not exist in this RN build's types**,
and `absoluteFill` is a registered style id rather than an object, so spreading
*that* is wrong in a second way that happens to typecheck. jambo-7d hit it an
hour before I did and told me rather than reaching into my file. **Second
occurrence in one night makes it a pattern**: write the four sides out.

**Verified:** 357 mobile tests across 21 suites, lint and typecheck clean.
**NOT verified:** the popup has never rendered — the dev client was rebuilding
as this was written, and the emulator goes to jambo-7d the moment it lands.

🔴 **A real payment cannot be completed on this box, and the reason is not the
code.** PesaPal confirms server-to-server to the URL it was handed, so a
completion needs `payments.callback_base_url` pointing at a **publicly
reachable** host. It is unset here, so callbacks come back as
`127.0.0.1:8090` — which the emulator resolves to its own loopback, not the
machine's, and which PesaPal cannot reach at all.

Worse for testing: on this box the website and the app need *different* bases —
a desktop browser wants `127.0.0.1`, the emulator's WebView wants `10.0.2.2` —
and there is one setting. **So the fix is not a value, it is a tunnel**:
cloudflared or ngrok in front of `php artisan serve`, with
`payments.callback_base_url` set to the tunnel origin. Then one value is right
for the browser, the emulator and PesaPal at once.

In production it is simply the public origin, and it must be set before any
build ships — the same setting the website's own callbacks already depend on.

**The popup verified, and two findings that matter more than it working
(jambo-68).** PesaPal's iframe renders inside the app and offers **MTN Mobile
Money, Airtel Money, Visa, Mastercard, Amex and EzeeMoney** with the phone
field and Proceed. Rio's figure — 96% of 80,000 signups pay by mobile money,
which Play Billing does not offer — is the whole argument, and that screen is
it rendered.

🔴 **The payer is asked to pay "ARM GENIUS DIGITAL MARKETING", not Jambo.**
The pay line reads *Pay "ARM GENIUS DIGITAL MARKETING" UGX 1,500.00*.
**It is not fixable in code.** Fetching the iframe HTML shows both strings
present: our `description` ("Jambo — Day Pass") is in the page, and the pay
line uses the **PesaPal account's registered business name**. So the lever is
the account registration or a PesaPal store/branch, not the payload — the
`branch` field PesaPal's SubmitOrderRequest accepts is a secondary label and
we do not send it.

jambo-49's successor put it better than I first did, and they were right to
push back on my framing: this is not a polish item, it is the checkout's
conversion rate. A mobile-money payer asked to send money to a marketing
company they have never heard of has no dispute path worth the name and will
simply stop. **Raised with Rio as its own item rather than as a footnote to
"the popup works", because good news is where bad news goes to die.**

Still unchecked: whether the same string reaches the **USSD prompt** on the
handset, which is the actual moment of truth for a mobile-money payer and may
carry a different name than the web view.

🔴 **Six live orders were created tonight, not two.** I told Rio two and was
wrong — taps I believed had missed had in fact hit Subscribe. All are
`pending` and unpaid on the live account:

| Reference | Amount | Plan | Tracking id |
|---|---|---|---|
| JAM-48-5SSMAYDNZY | 15,000 | Basic Monthly | daa4c17f-023d-4430-b19a-d9eb777bb49e |
| JAM-48-HTT6MP1WEV | 15,000 | Basic Monthly | 342504ff-ceff-43da-8ae1-d9ebfe68aae1 |
| JAM-48-GYW19TTSTV | 15,000 | Basic Monthly | be46540d-e3e4-40e4-8a13-d9ebc643d0af |
| JAM-48-6GIZ7DN1FJ | 15,000 | Basic Monthly | 2afb65ca-1286-4462-bf2e-d9eba26537cc |
| JAM-48-GIDXRUJPYZ | 1,500 | Day Pass | a71b3693-d366-455f-a2e4-d9eb68e047ad |
| JAM-48-U2C1L86TFA | 1,500 | Day Pass | d8309b04-be43-435b-a011-d9ebe2b79278 |

All on user 48 (`testuser@jambo.test`), 2026-09-10. Nothing was paid, so they
are abandoned checkouts — which a live merchant account sees constantly — but
they are real and whoever reconciles should not have to guess which are mine.

**The lesson underneath is the same one that cost me `.env` an hour earlier:**
a tap I could not confirm is not a tap that did nothing. I assumed the misses
were misses because the dump did not show what I expected, when the app had in
fact moved on.

**A real payment completed end to end, and I had said it could not
(jambo-68).** Rio paid a Day Pass from the app. `JAM-48-WSNOS6RQ5P`,
UGX 1,500, order `completed`, and `UserSubscription` for user 48 is **Day
Pass, active, ends 2026-09-11 03:03:01**. Order history returns all eleven
orders including that one.

🔴 **My earlier claim was wrong and worth correcting rather than quietly
dropping.** I told Rio a payment could not complete on this box because
PesaPal confirms server-to-server to a public URL and this machine has none.
The confirmation arrived anyway — the IPN registered under
`payments.pesapal_ipn_id` points somewhere reachable, which the callback URL
in the order payload does not change. **I reasoned from the callback URL I
could see and never checked the IPN registration.** The lesson is the same one
that has recurred all night: an assertion about a system nobody has exercised
is a hypothesis, and this one was testable in one query.

**Two defects in the screen Rio saw afterwards, both mine, both fixed:**

🔴 **"Confirming your payment…" rendered in the red error box**, beside a card
that already read Current plan. It was written to `checkoutError`, so a normal
step of a working payment was dressed as a fault, and **nothing ever cleared
it**, so it outlived what it described. There is now a separate
`checkoutNotice` rendered `tone="ok"`, cleared the moment the order settles.

🔴 **The screen did not refresh itself.** Rio: *"I thought on completing the
order the page reloads."* He was right to expect it. The refresh was wired to
`useFocusEffect`, which fires when a SCREEN regains navigation focus —
**closing a modal is not that**, because the screen never lost focus. So the
poll never ran and the card changed only when he touched something that
happened to refetch.

Closing the sheet now starts an actual poll: every three seconds for a minute,
clearing on `completed` or `failed` and saying so if it runs out. **A poll
rather than one request because mobile money is a USSD prompt** — the payer
approves seconds after the sheet closes, so the answer at close is almost
always still `pending`. Asking once is how a paid subscription sits looking
unpaid. A cancel polls too, for the same reason: backing out of the page is not
the same as declining on the handset.

**Verified:** typecheck and lint clean. **NOT verified on a device** — the fix
was written after Rio's payment and the emulator is with jambo-7d.

### 2026-09-10 — the two daily Top 10 banners, at the website's design (jambo-cf)

**Status:** complete
**Owns:** mobile/src/ui/rails/RankedBanner.tsx, mobile/src/ui/rails/BannerPager.tsx,
mobile/src/ui/rails/DottedList.tsx,
Modules/Frontend/tests/Feature/HomeBannerRailsTest.php,
Modules/Frontend/database/migrations/2026_09_10_210000_place_daily_banner_home_sections.php,
tools/dev-catalogue/15-daily-top-ten-views.php
**Shares:**
Modules/Frontend/app/Http/Controllers/Api/V1/HomeController.php — two
`bannerRail()` entries in show(), one new private builder, `style` on titleRail;
Modules/Frontend/app/Services/HomeRailsService.php — the `verticalFeatured`
line only, a dead `loadAvg` removed;
Modules/Frontend/app/Models/HomeSection.php — two DEFAULTS rows, and arrange()
now renames a heading rather than adding one;
Modules/Content/app/Http/Resources/{MovieResource,ShowResource}.php and
Concerns/RendersHeroFields.php — a fourth shape, `banner()`;
Modules/Frontend/tests/Feature/HomeSectionArrangementTest.php — the heading
pin now asserts banners send none;
Modules/Frontend/database/migrations/2026_09_09_180000_create_home_sections_table.php
— docblock only, the `isNumberedRail(key)` warning it carried is obsolete;
lang/en/sectionTitle.php — two keys;
public/frontend/css/jambo-header.css — the scrim block at the end of the file;
mobile/src/ui/rails/{Hero,BannerButton,banner}.tsx|ts,
mobile/src/ui/rails/banner.test.ts, mobile/src/ui/theme.ts,
mobile/src/api/catalogue.ts, mobile/src/api/catalogue.test.ts,
mobile/src/screens/HomeScreen.tsx, mobile/src/api/schema.d.ts,
mobile/assets/streamit/trending-label.webp, docs/api/openapi.yaml,
CHANGELOG.md, docs/worklog.md.

**What this is:** Rio reported the Top 10 Movies of the Day banner missing from
the app, then asked for the series one too. Neither was a regression — neither
had ever been built. jambo-7d named both above as the next two slices when they
shipped the hero: `verticalFeatured` and `tabSeries` were composed by
`HomeRailsService` and never left it, so the app's home screen was the
website's minus its two biggest blocks.

**The design is measured, not eyeballed.** `/` rendered in headless Edge at
390x844 and again at 430x932, read with `getComputedStyle`, the method
`design/export-tokens.mjs` and the hero slice use. The second viewport is what
earned its keep: **every size is identical at both widths**, so Streamit fixes
these in pixels inside the phone breakpoint and none of them is a ratio. A
`width * 0.16` headline would have been wrong on every phone that is not 390.

**Three things the measurement found that no screenshot would have.**

1. **The movies banner had no scrim at all.** Streamit declares one on
   `.slider--image` — the element that *contains* the slide's `<img>` — and a
   child image paints over its own parent's background. That gradient has
   never reached a pixel since the section was built. Rio saw the result
   independently, mid-slice: "the texts here are not visible enough work
   around the opacity to have it clear."
2. **`.text-gold` matches no rule.** Grepped every `.css` and `.scss` in the
   repository, built bundles included. The rank line renders in inherited body
   grey on the website, so it renders in body grey in the app. **If Rio wants
   it gold that is one rule on the website and one token in the app, in that
   order** — the app must not be the only surface that is gold.
3. **The two banners are not a pair.** The movies one centres every line in
   32pt gutters; the series one is left-aligned in 16pt. Its headline is
   `.texture-text` and the movies one is plain white at 62.46. Its slide is a
   fixed 480; the movies slide is whatever its content adds up to. They are
   two components, and building one parameterised component would have meant
   a prop for every row.

**The scrim number was solved, not chosen.** Each of the ten movie backdrops
and three series backdrops was composited the way its slide composites it, the
luminance of the band the text occupies was read, and the veil solved for the
smallest alpha that still clears WCAG AA — 4.5:1 — for the 16px synopsis. The
brightest movie needed 0.586, the brightest series 0.607, so 0.6 on both. Flat
rather than a gradient because this banner's content is centred and spans 326
of its 390: there is no edge for a gradient to hide in. The website's copy is
in `jambo-header.css` per ADR-0001; the series slider gets it as a
`background-color` on the pseudo-element that already carries its three
gradients, so this repo holds no second copy of those to drift.

**`style` on a rail, and a hardcoded list retired.** The Top 10 numerals were
decided by an app-side set of two literal rail keys, with a comment in
`catalogue.ts` saying the field belonged on the server. It does now:
`top_movies` and `top_series` send `style: "numbered"`, the banners send
`movies_today` / `series_today`, and `isNumberedRail` reads the rail rather
than the key. A third ranked shelf would look right with no app release.

**`BannerPager` is jambo-7d's pager, moved rather than forked.** The hero's
paging FlatList and dot strip came out of `Hero.tsx` verbatim so three banners
share one. `dynamicDots` exists because the site is not consistent: the tab
slider sets swiper's `dynamicBullets` and the hero does not, and both were
measured on the same page before the flag was written.

**Verified:**
- `Modules/Frontend/tests/Feature/HomeBannerRailsTest.php`, 9 tests, 51
  assertions. **Mutation-checked:** forcing `ranked_today` to `true` fails two
  of them, so the honesty assertion bites rather than passing vacuously.
- `npm run check` in `mobile/` — api:check, tokens:check, typecheck, lint and
  372 jest tests, all green.
- **Both banners rendered on emulator-5554** and compared against the same two
  sections rendered on `/` at 390. Movies: plate, rank line, blue-dotted genre
  chips, two-line clamped headline, IMDb + runtime, three-line synopsis, Play
  Now, and the two 24pt edge arrows. Series: plate, rank line, texture
  headline, synopsis, "May 2024 · 2 Seasons" with its white dot, Stream Now,
  and swiper's shrinking dots.
- **The scrim was verified numerically, not by eye.** The emulator capture was
  decoded, a glyph-free strip of the banner sampled, and the alpha fitted
  against the source artwork: 0.55 measured against 0.60 declared. The strip
  composites to L=0.052, which is 6.8:1 for body text — comfortably past AA.
  The 0.05 gap is measurement, not a bug: the app fetches its backdrop through
  the `/img` WebP proxy at slide width while the reference decoded the
  original file.
- The website's two sections re-captured with artwork loaded: the scrim paints
  on both, and the rank lines read "#4 in Movies Today" and "#2 in Series
  Today".

**Not verified:**
- **No iOS and no tablet.** Both banners are phone-width renderings only.
- **The 430pt capture proved the site's numbers do not scale; the app was not
  re-rendered at 430.** A tablet or a rotated phone will stretch the movies
  banner's centred block across a much wider column, and nobody has looked at
  that.
- **The arrows' 44pt hit slop was not tested with a finger**, only reasoned
  about. On a remote they are reachable; on a thumb they are 24pt of visible
  target with slop around them.
- **Reduced motion.** Nothing here animates beyond the pager's scroll, but
  `apple-design`'s reduced-motion rule was not exercised.

**Deliberately not built:**
- **The series banner's episode list.** The blade carries season tabs and four
  episodes per season, in a `d-none d-lg-block` column no phone ever draws. It
  is real work for the TV build in Phase 4 and it needs an endpoint shape that
  does not exist.
- **Auto-rotation.** All three of the site's banners run a 7-second timer.
  Inherited jambo-7d's decision not to, for their reasons: it takes what
  somebody is reading away from them, and on a remote it moves the focus
  target out from under the d-pad.
- **Gold.** See finding 2 above. The app matches the site's grey; making the
  app gold alone would put the two surfaces out of step over a dead class.
- **A "1 Season" fix on the website.** `streamEpisode.season` is the plural
  "Seasons", so a one-season show reads "1 Seasons" there. The app says "1
  Season" because `seasonCount()` already handled it for the hero's badge and
  copying a typo onto a second surface is worse than the two differing by a
  letter. One lang key and a blade would fix the site, in somebody's own slice.
- **Nothing was done about `Modules/Frontend/app/Http/Controllers/FrontendController.php`
  or the pricing page.**

**Two failures in the suite are not mine, and they belong to nobody.**
`tests/Feature/PricingPageCurrentPlanTest.php` fails twice. Committed in
`e981b71` on **2026-07-18**, and red with `pricing-page.blade.php` AND
`SubscriptionTier.php` both stashed back to HEAD — so this is committed
behaviour, not anyone's working tree, and **the suite has not been run green in
about seven weeks.** My first reading blamed the uncommitted
`SubscriptionTier::popularFrom()`; jambo-7d corrected it and they were right,
that refactor is behaviour-preserving.

The two failures are not the same kind of thing, which matters for whoever
takes them:

- `free_user_sees_current_plan_on_the_free_tier` is a **product question**.
  The free strip at line 279 of the blade is inside `@guest`, so a signed-in
  viewer never sees a free card to put a badge on. The information is on the
  page already — line 102 renders `Current plan: Free` in the `@auth` strip.
  So the decision is "should a signed-in viewer get a free-tier card at all",
  not "restore something that broke".
- `subscribed_user_does_not_see_current_plan_on_the_free_tier` is a **broken
  assertion**. The string it forbids is rendered at line 754 of the response,
  on the subscriber's own Premium card, which is the feature working. The test
  name says "on the free tier" and the assertion says `assertDontSee` over the
  whole page. **A one-line blade fix does not make this one green**; the test
  needs narrowing.

**jambo-72 is gone from `ListAgents`, so the membership slice has no owner.**
Reported to Rio by both jambo-7d and me rather than patched unasked: it is
money-adjacent and the first half is a product call. Everything else is green:
693 passing.

**For whoever is next:**
- **`tools/dev-catalogue/15-daily-top-ten-views.php` is on disk and it has
  been RUN on this machine.** It landed in `tools/dev-catalogue/` rather than
  a new `scripts/` folder because that is where this repository already keeps
  its dev-data steps, numbered and documented in one README — a second place
  for the same kind of thing is how two of them start disagreeing. It is step
  15, and the README says why it is not step 7. Rio asked why the local site showed "Popular on Jambo" instead of
  "#2 in Movies Today"; the answer was one movie and one series' worth of
  viewing in the 24-hour window, and everything else padded in from all-time
  popularity, which is the designed behaviour. The script makes the ranking
  visible on a dev box. **It wrote 108 rows tagged `session_id =
  seed-daily-views`, and `JAMBO_DAILY_VIEWS_RESET=1` removes exactly those.** It refuses to run
  outside `local`, because on production it would be fabricated engagement in
  the same table partner earnings read.
- **Both daily shelves cache on a per-date key.** Changing watch history
  without clearing them looks exactly like a broken feature until midnight.
  The constants are public on `TopPicksRecommender`; the script uses them.
- **The CSS trap in finding 1 is worth a rule, not just an entry.** A
  background declared on an element that contains an image never shows,
  because the child paints over its parent — and it survives review precisely
  because the declaration reads correctly. jambo-7d checked their genre tiles
  against it independently and they are fine (artwork and gradient are
  siblings, gradient second). Filed on the wiki's Frontend Overrides page as a
  fifth Streamit trap; if it appears a third time it belongs in a skill.
### 2026-09-10 — the Genres rail, at the website's design

**Status:** complete
**Owns:**
- `Modules/Content/app/Models/Genre.php` (`attachFeaturedImages`)
- `Modules/Content/tests/Feature/GenreFeaturedImageTest.php` (new)
- `Modules/Content/app/Http/Controllers/Api/V1/TaxonomyController.php` (`genres()`)
- `mobile/src/ui/rails/TaxonomyCards.tsx` (`GenreCard`)
- `mobile/src/screens/GenresScreen.tsx` (new)

**Shares — all four also held by jambo-cf for the two Top 10 banners, and each
edit is one isolated block that does not touch theirs. They have been told:**
- `Modules/Frontend/.../Api/V1/HomeController.php` — `genreRail()` only, one
  added `image_url` line.
- `Modules/Frontend/app/Services/HomeRailsService.php` — the `homeGenres`
  entry only, wrapped in `tap()` to batch the artwork.
- `docs/api/openapi.yaml` — the `GenreCard` schema only.
- `mobile/src/ui/theme.ts` — one new `genreTile` block.
- `mobile/src/screens/HomeScreen.tsx` — the `case 'genres'` arm and one clause
  in `seeAllFor`.
- Also `FrontendController::all_genres`, `navigation/types.ts`,
  `RootNavigator.tsx`.

**What this is:** Rio, with the two rails side by side: *"the genere on teh
webapp are showing images and we have the view, fix it as well"*, then *"learn
from the orignal from the webapp to get the exact work on design"*. The site
draws a 5:3 still with the genre name centred over it and a "View All" beside
the heading. The app drew a bordered chip with a title count and no link.

**Measured, not eyeballed** — `/` and `/all-genres` in headless Edge at 390pt:

| | |
|---|---|
| Tile | 164 x 98.4, so **5:3**, radius 8 |
| Artwork | `object-fit: cover`, `object-position: 50% 0%` |
| Scrim | `linear-gradient(90deg, rgba(0,0,0,.8), rgba(0,0,0,.4) 50%, transparent)` — **left to right, not vertical** |
| Label | absolute inset 0, `display:flex` **centred in both axes**, 16/500 white |
| View All | 12/500, `#1a98ff`, → `/all-genres` |
| The archive | one full-width column of the same tile, 358 x 215 |

Two traps in that: reading only `position:absolute; top:0` off the label puts
it at the top of the tile, and a vertical scrim is the obvious guess and wrong
— the label is centred, so a vertical one darkens the artwork above and below
it and leaves the text on the brightest band.

**The data gap, and an N+1 that came with it.** A genre owns no image; the site
borrows one through `Genre::featured_image_url`, an accessor that walks up to
**four queries per genre** — up to forty on the home rail, on every request,
for a decoration. `Genre::attachFeaturedImages($genres)` answers the whole set
in **two**, with the accessor's exact precedence (movie over show, backdrop
over poster, newest first). Verified against the accessor across all eight
genres: identical answers, 8 queries → 2. Both surfaces use it now, so the
website's home page and its `/all-genres` got faster for free.

**"View all" needed a screen, not a collection.** `RailArchiveCatalog` has no
`genres` key and should not — a collection is a list of TITLES behind one rail,
and this is a list of genres. The website makes the same distinction, linking
to `/all-genres` rather than `/collection/genres`. So `seeAllFor` names
`genres` explicitly and a new `GenresScreen` draws the archive.

**Two more faults found only by rendering it.**

1. **The rail had no "View all" even after `seeAllFor` learned about genres**,
   because **only the `titles` arm forwards `onSeeAll` to `<Rail>`**. The
   genres, vjs and people arms never did. Genres passes it now; the other two
   still do not, and the website has "View All" on both — see below.
2. **Two tiles per view, not 2.4.** The genres rail was using the shared
   `stillWidth`, which is derived from the poster rail's card count. The site
   is `data-mobile="2"`: a 358pt content width holds two 179pt slides. It is
   computed from the width now.

**Verified:**

- 7 new PHP tests, **two mutants killed** — swapping movie/show precedence, and
  swapping backdrop/poster precedence. The second one first *survived* a
  mutation run that never applied, because the shell ate the `$` in my sed. The
  mutation is a file now (`scratchpad/mutate.py`); a mutation you cannot prove
  ran is not a mutation test.
- **Rendered and measured from the view tree, not by eye.** The tile comes out
  **547 x 327, aspect 1.673** against the site's 164 x 98.4 = 1.667 — 0.4%, a
  rounding artefact of `Math.round(width / aspect)`. Two whole tiles per view
  with the third peeking, as the site's swiper does.
- **"View all" opens the archive**, confirmed by tapping it and asserting the
  destination rather than assuming — the first tap missed and scrolled instead,
  which is this emulator's known behaviour.
- `GenresScreen` renders one full-width column of the same tile, which is what
  `/all-genres` resolves to at 390pt.
- 372 app tests, 410 PHP tests, lint, typecheck, spec and generated types green.

**Not verified:** the website's own `/all-genres` and Genres rail were
re-rendered and look right, but only the home rail was measured; the eight
listing pages that also lost N+1 queries were not re-rendered at all.

**Found, not mine, reported to Rio:**

- 🔴 **`tests/Feature/PricingPageCurrentPlanTest.php` has two failures that are
  nobody's uncommitted work, and they have DIFFERENT causes.** Verified against
  HEAD: the committed blade carries the same logic, and jambo-cf stashed both
  modified files back and reproduced. **The test dates to `e981b71`,
  2026-07-18**, so the full suite has not been run green in weeks.

  1. **`free_user_sees_current_plan_on_the_free_tier`** — a viewer with no
     paid subscription has no `UserSubscription`, so `$currentTierId` is null
     and no card is marked current, Free included. The free-tier strip that
     would carry the badge is inside `@guest`. **But this is less clear-cut
     than a missing feature**: the `@auth` strip at the top of the page already
     renders `Current plan: Free` for that viewer, so the real question is
     whether a signed-in viewer should see a free *card* at all. That is a
     product decision about a pricing page.
  2. **`subscribed_user_does_not_see_current_plan_on_the_free_tier`** — **the
     test is wrong, not the page.** The subscriber's own Premium card renders
     "Current Plan", which is the feature working; the test's name says "on the
     free tier" and its assertion is a page-wide `assertDontSee`. Narrowing the
     assertion to the free card is a change to the test.

  🔴 **My first read of (2) was wrong and jambo-cf corrected it.** I had said
  nothing renders "Current Plan" — but if that were true `assertDontSee` would
  PASS. A failing `assertDontSee` is positive evidence the string IS present,
  and I reasoned past it. **Read what a failing assertion proves, not what the
  suite's headline says.**

  Not fixed here: money-adjacent, a product decision on one half, a test fix on
  the other, and the membership slice lost its owner when jambo-72 ended. Two
  sessions have now declined it deliberately rather than by oversight.

- **The VJs rail's "View All" goes to `/movie`** on the website — the movie
  listing, not any VJ index. `vjs.blade.php:11`.
- **The `/all-genres` page's heading reads "Geners"**, a typo in
  `frontendheader.geners`.
- **The app's VJs and Personalities rails have no "View all"** where the site
  has both. Each needs an index screen; genres now has the pattern to copy.

🔴 **A second orphaned pre-computation, and mine.** `HomeRailsService` was
still calling `loadAvg('ratings', 'stars')` on the daily movies set, feeding
the five-star row I had removed from `vertical-banner.blade.php`. jambo-cf
found and dropped it. **Twice tonight I removed a display and left the query
that fed it** — the hero's one I caught, this one I did not. When a rendered
thing goes, grep for what computed it.

🔴 **A CSS trap worth knowing, found by jambo-cf on the Top 10 movie banner
and checked against mine.** That banner declares its scrim on
`.slider--image`, the element that CONTAINS the slide's `<img>` — and a child
image paints over its own parent's background, so the gradient has never
reached a pixel and every word on it has been sitting on raw artwork. The
genre tile draws artwork and gradient as siblings, gradient second, so it is
not the same shape; the render confirms the left of every tile is darkened.
**A background on an element that contains an image never shows.**

**For whoever is next:** jambo-cf holds the two Top 10 banners and four of the
files above. Read their entry before touching `HomeController::show`,
`titleRail`, or the banner half of `theme.ts`.

### 2026-09-10 — two days of finished work, committed (session jambo-5b)

**Status:** complete
**Owns:** `mobile/.gitignore` (one rule appended). Nothing else was edited.

**What this was.** Rio asked what was left on the plan, and the answer turned
out to be that 194 paths of finished, documented work had never been
committed. Twenty-three `### Unreleased` entries in `CHANGELOG.md`, every one
of them with a worklog entry marked complete, sitting in the working tree
across two days and several sessions.

**Two commits.**

- `0f1cb35` — `CLAUDE.md`, `.graphifyignore`, and the graphify lines in
  `.gitignore` and `.gitattributes`. **`CLAUDE.md` was untracked**, so every
  session has been reading the project's standing instructions out of the
  working tree and no clean checkout has ever had them.
- `6f91859` — the other 241 paths, all twenty-three slices together.

**Why one commit and not twenty-three, since the house style is small
commits.** `docs/api/openapi.yaml` and `mobile/src/api/schema.d.ts` are
generated one from the other and `npm run api:check` fails when they are split;
`OpenApiSpecTest` fails when a route lands without its spec. So a
backend/app split produces two commits that each fail their own suite. The
slices also share `theme.ts`, `endpoints.ts` and `HomeScreen.tsx` far too
heavily to cut by feature. **A commit that does not build is worse than a
commit that is large**, and the changelog already narrates the slices
individually, which is where that detail belongs.

🔴 **The trap, and it is worth a rule.** `mobile/modules/` was untracked as a
whole and `git add -A` would have committed **144 Gradle build artefacts** —
`.dex`, `.jar`, `.bin`, merged manifests — alongside the ten real sources of
`expo-jambo-media`. The cause is that `mobile/.gitignore` ignores `/android`,
**anchored to `mobile/`**, so it never reached
`modules/expo-jambo-media/android/build/`. The rule reads correctly and covers
nothing. Appended `modules/*/android/build/`; staging went from 154 paths to
10.

**The general shape:** a leading-slash ignore is anchored, and a second copy of
that directory name deeper in the tree is not covered. Any local native module
reintroduces the directory the anchored rule was written for. Worth checking
the first time a module is added, not the first time a `.dex` reaches history.

**Verified before committing:** 372 app tests in 22 suites, `tsc --noEmit`
clean, eslint clean, 693 PHP tests passing. Also confirmed `.env` is ignored on
both sides and that nothing matching a keystore, credential or service account
was staged.

**Coordination.** jambo-78 was live and answered: nothing of theirs was
mid-edit, and the `features/` restructure was not theirs. They named jambo-cf
as live, but **jambo-cf does not appear in `ListAgents`** and their Top 10
banner entry says complete, so nothing was in flight. Both were told the tree
is now clean.

**Deliberately not done:**
- **Not pushed.** Rio asked for a commit.
- **`tests/Feature/PricingPageCurrentPlanTest.php` still fails twice.** Red
  since `e981b71`, 2026-07-18, reproduced by three sessions now. Named in the
  commit message as not from this work. Still unowned, still half a product
  question.
- **No slice of the account-area audit was implemented.** Merging
  `ProfileScreen` into the editor, folding notification settings in, turning
  the invoice into a sheet and the billing summary are all still open.

### 2026-09-10 — the account area: grouping, the merged profile, the folded settings, the billing summary (jambo-6b)

**Status:** complete
**Owns:**
- `mobile/src/ui/profileMenu.ts` and `mobile/src/ui/profileMenu.test.ts`
- `mobile/src/screens/ProfileMenuScreen.tsx`
- `mobile/src/screens/ProfileScreen.tsx` — to be **deleted** (§4.1)
- `mobile/src/screens/ProfileEditScreen.tsx`
- `mobile/src/features/notifications/NotificationsScreen.tsx`
- `mobile/src/features/notifications/NotificationSettingsScreen.tsx` — to be **deleted** (§4.2)
- `mobile/src/features/billing/BillingScreen.tsx`
- `mobile/src/features/billing/InvoiceScreen.tsx` — becomes a `Sheet` (§4.3)

**Shares — minimal diffs, each named:**
- `mobile/src/ui/theme.ts` — the `list` and `profileMenu` token blocks only.
- `mobile/src/ui/format.ts` — one added formatter, shared with `PlansScreen`.
- `mobile/src/ui/list.tsx` — the section heading reads its two numbers from
  `list` instead of holding them as literals.
- `mobile/src/screens/PlansScreen.tsx` — the `CurrentStrip` date line only,
  which moves to the shared formatter.
- `mobile/src/navigation/types.ts`, `mobile/src/navigation/RootNavigator.tsx` —
  the routes the deleted screens leave behind.
- `docs/api/openapi.yaml` + `mobile/src/api/schema.d.ts` — only if §3 needs a
  field, and always in the same commit.

**What this is:** the four numbered slices of
`docs/plans/account-area-audit.md` that were still open after `6f91859`, in the
order the plan gives them and one commit each. §7's step 1 (navigation) and
§9.4's step 0 (the copy sweep) are already done and are not touched.

**Coordination:** jambo-5b was live and has been told which files are claimed.

**For whoever is next:** §2.2 — the account icon kept present on account
screens, so the area is a hub rather than a tree — is deliberately NOT in this
work. It changes navigation Rio has already signed off, so it is a question for
him rather than a fifth commit.

---

#### Step 1 of 4 — §6, the groups and the promoted card. Complete.

**Verified, on a booted emulator and read from the view tree rather than from
pixels.** Density 480, so 3.0, and every number below is px/3:

| | |
|---|---|
| Membership card | 1208 x 194 px = **402.7 x 64.7 dp** |
| Row | 168 px = **56 dp**, which is `list.rowMinHeight` and unchanged |
| Between rows | 6 px = **2 dp**, `profileMenu.rowGap`, unchanged |
| Between one group and the next | 48 px = **16 dp**, `spacing.lg` |
| Whole menu | ends at y=2775 of 2856 — **it no longer scrolls** |

Read off the tree in order: identity, the card, ACCOUNT / Profile, Security,
Devices, VIEWING / Watchlist, Notifications, MONEY / Billing, Wallet, Refer &
Earn, then Sign out under a rule with no heading.

- The card opens Membership, and the Membership screen's own strip reads
  **"Active until Sep 11, 2026"** — the same word and the same date as the
  card, from the one shared formatter, against the real dev database.
- Six mutants killed on the two pure modules (grouping x2, the card title x2,
  the renewal word x2), applied from a file rather than a shell one-liner so
  each one is provably applied. All restored.
- `npm run check` green: 23 suites, 390 tests, plus api:check, tokens:check,
  typecheck and lint.

**Not verified:** no PHP was touched, so `php artisan test` was not run for
this step. The grouping was rendered only with referrals switched ON, so the
"MONEY loses a row" path exists in a test and has not been seen on a screen.
Nothing was checked on a tablet or on the TV build.

**🔴 Two defects found by rendering, both older than this change and both
fixed here** — the active row was invisible to a screen reader
(`accessibilityState` was never set, on any row), and the highlight did not
follow you back from a destination (`useNavigationState` does not re-render
when its selected slice is unchanged, so the menu kept the previous visit's
row lit). The second is the one worth remembering: **the view tree could not
answer a question about state, and that was itself the finding.**

**§5's placement of the next charge — stated, not slipped in.** The audit
argues the next charge belongs on Membership rather than Billing, and putting
the renewal date on the Membership card takes that recommendation. I agree
with it: "when am I next charged" is a membership question, and it is one line
on a card people already open. Rio has not seen that argued, and it is the one
genuine judgment call in this slice — flagged rather than assumed. Step 4's
Billing summary will still carry what you have SPENT; only the next charge
sits on Membership.

**🔴 An environment trap that cost half an hour, and it is the XAMPP bleed one
port over.** Apache serves the *Precious* Laravel app on **8081**, which is
Metro's default, and it wins the bind — so `adb reverse tcp:8081` sends the
dev client to a different project's homepage with no error anywhere. Metro was
moved to **8099**. Check what answers on a port before trusting it:
`curl -s http://127.0.0.1:8081/status` returned a page titled PRECIOUS.

**🔴 A self-inflicted one worth a rule.** `io.open(path, 'w')` truncates the
file *before* the write, so a `UnicodeEncodeError` raised while encoding leaves
an empty file — which is how `ProfileMenuScreen.tsx` went to 0 bytes with an
hour of uncommitted edits in it. Recovered from `git show HEAD:` plus a replay
script. **Encode first, then open for writing**, and put a multi-edit replay in
a file rather than a heredoc so it can be re-run.

---

#### Step 2 of 4 — §4.1, ProfileScreen absorbed into the editor. Complete.

**Deleted:** `screens/ProfileScreen.tsx` (327 lines), the `Profile` route in
`RootNavigator` and `types.ts`, the `profileBanner` token block (83 lines of
theme), and `detail()` plus its four tests in `profileFields`. **Added:** one
caption.

**Verified on the device, read from the view tree:**

- The menu's **Profile row** opens the editor directly. Header title reads
  **Profile**; the screen carries First name, Last name, Username, Email with
  its verification state, Phone, Country, Save changes.
- The **identity block** opens the same screen — both doors, one destination.
- The footer reads **"Member since September 2026"**, above the existing
  security line.
- Coming back from it, the Profile row reads `selected="true"`, so the
  highlight fix from step 1 holds for a row as well as for the card.

**Nothing to measure against the website here.** The profile hub's blades have
no "Member since" anywhere — grep found only a `Joined` column on the referrals
table — so that caption is Rio's mockup's wording, carried over from the screen
that was deleted rather than newly invented. No component was added, so there
is no captured value to compare.

**Not verified:** the avatar picker was not exercised again in this step; it
moved into `useAvatarUpload` yesterday and both callers became one. No PHP was
touched.

**Deliberately not done:** the editor did not get a hero, per §8.

---

#### Step 3 of 4 — §4.2 and §4.3, folded and sheeted. Complete.

**Deleted:** `NotificationSettingsScreen.tsx` (174 lines), `InvoiceScreen.tsx`
(258), and both routes with their types. **Added:** `DeliverySection.tsx`,
`InvoiceSheet.tsx`, and `ui/motion.ts`.

**Verified on the device, read from the view tree:**

- **Closed**, the inbox header is one row — `Delivery settings`, 168 px = 56 dp,
  the shared row height — and **neither switch appears in the tree at all**,
  which is the accessibility half of "collapsed" working.
- **Open**, the tree carries In-app and Email with the unverified-address note,
  and the caret has turned.
- **The switch round-trips against the real server**: In-app toggled to
  `checked="false"`, then back to `checked="true"`.
- **Collapsing removes both from the tree again.**
- The invoice opens as a sheet over the order list, dismisses on the scrim, and
  the whole document — heading, Billed to, four detail rows, the line-item
  table, Total, footnote — fits above the fold at 0.72 of the display.

**Nothing was re-measured against the website, and nothing needed to be.** The
invoice's table numbers were probed off the rendered billing page when it was
built and were copied across unchanged; the delivery rows are `ListRow` at the
app's shared metrics. No component's colour, weight, radius or size was
re-decided.

**🔴 Two faults found only by rendering.**

1. **The section opened to nothing.** The caret turned and the state flipped,
   but the body stayed shut — which on a device is indistinguishable from a
   press that did not register, and cost twenty minutes of tapping. A child
   laid out normally inside a parent whose height is animated to 0 reports **0**
   from `onLayout`, so the animation had nothing to travel to. The measuring
   child is absolutely positioned now: it takes its width from left/right and
   its height from its own content, so it measures at full size however short
   the clipped parent currently is.
2. **`Sheet` had no height cap.** Found by the same habit that found the first
   one — reading bounds rather than looking. Fixed in the shared component,
   with the ratio as a token.

**Not verified:** the error state inside the section (the query has not been
made to fail on the device), and the empty-inbox error branch, which renders
the same section above `ErrorState` but was not exercised. Nothing on a tablet
or the TV build. No PHP was touched.

**Deliberately not built:** the third delivery switch. The plan's §4.2 says
"three switches" from the website; the app has two, and **push is absent on
purpose** — the website's third drives a browser Web Push subscription, the app
registers no FCM token, and `docs/api/coverage.md` records that the registry
has no sender. A third switch would either silence the viewer's browser from
their phone or toggle a flag for a channel that cannot deliver. The endpoint
carries `push` either way, so it is a few lines on the day push works.

---

#### Step 4 of 4 — §3, the Billing summary and the tiles. Complete.

**Server:** `GET /subscription/orders` gained `totals` — `SUM(amount)` over the
decimal column, completed orders only, scoped to the viewer, null on mixed
currencies. Spec and generated types landed in the same commit, as they must.

**Verified against the real dev database, not a fixture.** The screen renders
**UGX 164,000.00 across 4 charges**; the same query run directly against
`payment_orders` returns `{"currency":"UGX","s":"164000.00","c":4}`. The
accessibility label reads "You have paid UGX 164,000.00 across 4 charges".

**Nine mutants killed across the two halves.**

| Mutation | Result |
|---|---|
| count pending and failed as spent | 1 failed |
| add two currencies together | 2 failed |
| stop scoping the sum to the viewer | 1 failed |
| return a bare float instead of a two-place string | 3 failed |
| render a zero count | **survived — see below** |
| accept a null amount | 2 failed |
| always say "charges" | 1 failed |
| print raw digits instead of `formatMoney` | 3 failed |
| render a zero count, after the new test | 1 failed |

🔴 **The survivor is the finding.** `spendSummary`'s `orders === 0` check
could be deleted with the suite still green, because the case covering it
passed a *null amount* — so the guard below it answered and the count check was
correct and unobserved. Exactly the shape `screen` describes: the assertion was
right about data that could never have shown the fault. Forced the real state
(an amount present, a count of zero) into its own test, and asserted that the
state actually happened so a later fixture edit cannot quietly disarm it.

**§5 taken, and stated rather than slipped in.** The next charge sits on the
Membership card from step 1, not on Billing. I agree with the audit: it is a
membership question and it is one line on a card people already open. Rio has
not seen that argued, so it is flagged here and in the changelog rather than
left to be discovered in a diff.

**Rendered and read from the view tree:** the summary line above the card, the
receipt tile on every order row, and the gear/bell/envelope tiles on the
Delivery rows. Row height is unchanged at 223 px — the accessory's two lines
drive it, not the tile.

**Checks:** `npm run check` green (23 suites, 394 tests, api:check, tokens:check,
typecheck, lint). `php artisan test`: **698 passed, 2 failed** — the two
`PricingPageCurrentPlanTest` failures that have been red since 2026-07-18 and
nothing else. 693 before this work, so all five new PHP tests pass.

**Not verified:** the mixed-currency path exists only in a test; no account on
this box has paid in two currencies, so it has not been seen on a screen. The
"more than one page" path is likewise tested and not rendered — this account
has six orders.

---

### Closing this session's entry

**Built:** the four numbered steps of `docs/plans/account-area-audit.md` that
were still open, one commit each — §6 (grouping and the Membership card), §4.1
(the profile merged), §4.2 and §4.3 (delivery folded, invoice sheeted), and §3
(the spend summary and the tiles). **14 account screens are now 10**, against
the audit's target of 9; the difference is Membership, which the audit counts
as a destination and which is still its own screen behind the card.

**Deliberately NOT built, each named so nobody rebuilds it badly:**

- **~~§2.2, the account icon on account screens.~~** Raised with Rio as
  planned, and **he chose to build it** — so it became a fifth commit rather
  than an open question. See the entry below.
- **The third delivery switch.** Push has no sender; see step 3.
- **A Print button on the invoice.** Needs `expo-print`, a native module and
  therefore a prebuild.
- **`tests/Feature/PricingPageCurrentPlanTest.php`.** Still two failures, still
  red since `e981b71`, still half a product question. Five sessions have now
  declined it deliberately rather than by oversight.

**Left for whoever is next, and each of these is cheap:**

- **`ui/player/PlayerControls.tsx` still has its own reduced-motion effect.**
  `ui/motion.ts` is the canonical one now and says so. Four lines, next time
  somebody opens the player.
- **The eslint allowlist is down to five and one of the five may be free.**
  `ProfileMenuScreen` left it without being converted, because it never
  imported `Modal` or `Alert` — it was added from a list of screens rather than
  from its imports. Worth reading the other five the same way.

---

#### Step 5 — §2.2, raised and then built (jambo-6b)

**Asked rather than assumed, which was the instruction.** §2.2 was put to Rio
with the two-tap path drawn out against the four-tap one; he chose to build it.
It is the last open item in `docs/plans/account-area-audit.md`.

**Owns:** `mobile/src/ui/AccountButton.tsx` (new).
**Shares — minimal diffs:** `mobile/src/ui/AppHeader.tsx` (the avatar block
becomes `<AccountButton />` and its `/profile` query goes with it),
`mobile/src/navigation/RootNavigator.tsx` (one `accountScreenOptions` const,
spread into seven screens).

**Verified on the device, read from the view tree:**

- The account button is present beside the back arrow on **Profile, Security,
  Devices, Membership, Billing, Wallet and Refer & Earn** — all seven checked
  individually, each reporting exactly one `content-desc="Account, testuser"`.
- **Wallet to Security is two taps**, driven by label rather than coordinates:
  tap the account button, tap Security. It was four.
- **The negative case holds.** The Search screen shows `Navigate up` and
  `Search films and series` and **no account button**, which is what says the
  catalogue screens were not swept up in a stack-wide option.

**Not verified:** Notifications was left out by design and was not re-checked
after this change; its overflow menu is unaffected because nothing was added to
its options. Nothing on a tablet or the TV build, where a persistent account
control on a remote-driven header may want a different answer.

**A note for whoever adds the eighth account screen:** the button is opt-in per
screen, spread from `accountScreenOptions`. That is deliberate — a stack-wide
default would put an account avatar on a film's detail page — but it does mean
a new account screen will not get it unless somebody remembers. There is no
test that would catch that, and it would be worth one if a third account screen
is ever added at once.

### 2026-09-10 — the VJs and Personality rails, at the website's design (jambo-6b)

**Status:** complete
**Owns:**
- `mobile/src/ui/rails/TaxonomyCards.tsx` — `VjCard` and `PersonCard`

**Shares — minimal diffs, each named:**
- `mobile/src/screens/HomeScreen.tsx` — the `vjs` and `people` arms only.
- `mobile/src/ui/theme.ts` — one added `personTile` block; `genreTile`
  untouched.

**What this is:** Rio, over a screenshot of the two rails: *"fix these
sections"*. Both are drawn at the app's own numbers rather than the site's, and
both are missing the "View All" the site's heading carries. It is the same
finding the Genres rail closed on 2026-09-10 and explicitly left open for these
two — see that entry's "Found, not mine" list.

**Measured before touching anything**, at 390x844 in headless Edge against
`127.0.0.1:8090`:

| | Site | App now |
|---|---|---|
| VJ card | **164.03 x 98.41, aspect 5:3**, radius 8, name centred OVER the art | 16:9 still, name and "N titles" below it |
| VJ card partial | `card-genres-grid` — **the same partial the genres rail uses** | a hand-built card |
| Personality image | **165 x 214.5, aspect 1/1.3**, radius 8, 16pt below it | a circle |
| Personality caption | `.cast-title`, 14px/500, centred, below the image | 13px caption |
| Per view, both | **2** (`data-mobile="2"`, a 179pt slide in a 358pt rail) | VJs from `stillWidth`; people 4 |
| View All, both | present, 12px/500 `#1a98ff` | absent |

**No PHP and no API change in this first commit.** Both rails already send
everything the cards need.

**Verified on the device, measured from the view tree and the pixels:**

| | App | Site | Delta |
|---|---|---|---|
| VJ tile | 182.3 x 109 dp, **aspect 1.673** | 164.03 x 98.41, **1.667** | 0.36% |
| VJ label | centred in BOTH axes over the art — tile centre 1408.5, label centre 1408.5 | `.blog-description`, absolute, inset 0, centred | matches |
| Personality card | 183.3 x 238 dp, **aspect 0.7703** | 165 x 214.5, **0.7692** | 0.14% |
| Per view, both | 2 whole cards, third peeking | 2 | matches |

Both deltas are the rounding artefact of `Math.round(width / aspect)` on a
426.7dp emulator against a 390dp probe, and they are the same size as the one
the genres tile already carries.

- **"View all" is on the VJs heading** and tapping it selects the Movies tab —
  asserted from the tab bar's `selected` state, not assumed.
- **The tile no longer draws "60 titles"**, and `VJ Test, 60 titles` is still
  the accessibility label, so the count moved rather than being dropped.
- **Two measurements were wrong before they were right**, and both times the
  cause was the same: `uiautomator` clips bounds to the visible window and a
  pixel scan sees only what is painted, so a rail sitting under the sticky
  header measured 6dp short in one reading and 100dp short in another. The
  fix is to scroll the thing fully clear before believing either.

**Corrected a note from the Genres entry.** It recorded the VJs rail's
"View All" going to `/movie` as an oddity on the website. It is not:
`FrontendController::moreVjsForMoviesPage` pages a VJ-per-row carousel on that
page, so `/movie` is the VJ index. The app now goes there too.

**Deliberately not built:** the Personality rail's "View All". Its destination
is `/all-personality`, which has no app screen and no endpoint, so `seeAllFor`
answers undefined and no control is drawn — a control that leads nowhere is
worse than a heading without one. It is the shape `GenresScreen` already has,
plus one endpoint.

### 2026-09-10 — one home arrangement, governing the website as well as the app

**Status:** in progress
**Owns:**
- `Modules/Frontend/app/Models/HomeSection.php` — the `WEB_VIEWS` map and `webPlan()`
- `Modules/Frontend/resources/views/components/sections/arranged.blade.php` (new)
- `Modules/Frontend/resources/views/components/sections/latest-series.blade.php` (new)
- `docs/adr/0007-one-home-arrangement-for-both-surfaces.md` (new)

**Shares — minimal diffs, each named:**
- `Modules/Frontend/resources/views/Pages/MainPages/ott-page.blade.php` — the
  section block between the hero and the mobile footer becomes one include.
- The home section partials — the `<h4>` heading line only, so an admin's
  label reaches the website the way it already reaches the app.
- `Modules/Frontend/resources/views/components/sections/category-rails.blade.php`
  — renders the whole Visible Home pool rather than the first shelf.
- `Modules/Frontend/app/View/Composers/SectionDataComposer.php` — one public
  accessor for the cache it already builds.
- `Modules/Frontend/resources/views/admin/home-sections/index.blade.php`
- `CHANGELOG.md`, `docs/worklog.md`

**Not touching:** anything under `mobile/`, `Modules/Content`, or the API
controllers. Another session holds `mobile/src/api/*` and
`Modules/Content/.../TaxonomyController.php`.

**Status: complete, shipped as 1.8.38.**

**What was built.** The website's home page renders from `home_sections`, the
same rows that order `/api/v1/home`. `HomeSection::WEB_VIEWS` maps each section
to its partial, its collection, and whether it is full-width;
`HomeSection::webPlan()` turns the stored rows into runs of includes, grouped
so the page container opens once per run and a full-width banner breaks the
run; `components/sections/arranged.blade.php` renders that.
`ott-page.blade.php` is one include. Every section partial takes
`$sectionHeading`. ADR-0007 has the decision and why the plan's estimate was
wrong.

**Verified, by rendering and by mutation.**
- `http://127.0.0.1:8090/` as a guest: thirteen headings in exactly the stored
  order, both daily banners present, Continue Watching and Upcoming correctly
  absent because both collections are empty for a guest here.
- Disabling `top_movies` removed it from the page. Renaming `popular_movies` to
  "Uganda Loves These" and moving it to position 0 did both. **The table was
  restored to its original 18 rows and order.**
- `/admin/home-sections` rendered as an admin and screenshotted: 18 rows, four
  columns, no blurb, no duplicate badge.
- Four new tests in `HomeSectionArrangementTest`. Two mutants — dropping the
  `enabled` filter, and ignoring the label — each failed their own test and
  nothing else. Both restored; `grep MUTANT` is clean.
- Full suite: **705 passing, 2 failing.** The two are
  `PricingPageCurrentPlanTest`, "free user sees current plan on the free tier"
  and "subscribed user does not see current plan on the free tier". **They fail
  identically on a stashed clean tree, so they pre-date this work.**

**Not verified.**
- **A real-pointer drag.** The reorder endpoint is tested directly and the
  SortableJS wiring is unchanged from 1.8.19, but nobody has dragged a row.
- **The admin screen's icons.** The screenshot was taken from a saved file over
  `file://`, so the Phosphor font did not resolve and every icon, including the
  sidebar's, drew as an empty box. Almost certainly a capture artefact, not a
  defect — but it is unproven, and the drag handle is an icon.
- **Production.** Nothing was deployed. The homepage composition change is
  visible to viewers on the next deploy.
- **Category shelves on a populated catalogue.** This machine has no Visible
  Home category with published content, so `homeCategories` is empty and the
  block was correctly skipped rather than correctly drawn. The suite covers it;
  the render did not.

**Deliberately not built.**
- **No app change, and nothing under `mobile/` touched.** The app already
  renders whatever ordered list it is given.
- **No rail key added, renamed or reordered.** `DEFAULTS` is byte-identical.
- **`FrontendController::ott()` still computes `$featuredMovies`,
  `$latestMovies` and `$popularShows` that `SectionDataComposer` then
  overwrites.** Three wasted queries on every home page. Found while doing
  this, left alone: restructuring a route action inside a feature slice is the
  failure mode, not the fix. It is a five-line commit for whoever takes it.
- **No second control for category order.** It is on the Categories screen and
  two controls for one order is how two orders start disagreeing.

**Gotcha, and it cost the other session a warning.** I ran `git stash
--include-untracked` to check whether the pricing failures pre-dated me, on a
tree jambo-6b had eleven uncommitted files in. It popped cleanly and all eleven
were verified back, but **do not stash a shared tree** — use a worktree.

**For whoever is next.** Adding a home section is two rows in one class:
`HomeSection::DEFAULTS` for the heading key, `HomeSection::WEB_VIEWS` for the
partial. A test asserts the two key sets match in the same order, so forgetting
one fails rather than silently missing a surface.

**Addendum, same session — the deploy-readiness pass, and it found something.**

Rio asked whether this is ready for the live server and reminded that hardcoded
work is not wanted. Two things changed as a result.

1. **The four-category cap is gone.** `HomeRailsService` returned
   `homeCategories` (1) and `randomHomeCategories` (3) because the page had a
   fixed slot and three rotating ones. Collapsing the block into one movable
   section left that split describing nothing, and the cap was overruling the
   admin — six categories flagged Visible Home on this box, four on the page.
   It was applied to the result, not the query, so it saved nothing and only
   hid rows. One collection now, at every call site: the service, the API
   controller, the blade, and `HomeRailsPinTest`. Verified: six flagged
   produces six shelves on both surfaces, four before.
2. **The number of home category shelves is now unbounded in code**, and the
   real cost is written beside it. `shapeCategoryRails` eager-loads every
   published title in each Visible Home category to keep twelve. Bounding that
   per parent needs a window function or a query per category — Eloquent cannot
   express it in a `with()` closure. **Do that before the list grows.** Not
   done here: it is a service-level change and this was a feature slice.

**Two items left the "Not verified" list above.**

- **A real pointer drag is verified, and not by us.** Somebody reordered the
  rows in a browser at 20:26 UTC: all eighteen positions written in one
  request, both daily banners moved into the middle of the page. The rendered
  home page matched exactly. **That arrangement is still in the local table and
  was deliberately left there** — it is somebody's, not a fixture.
- **The category block is verified with content**, which the earlier render
  could not do because this box had no Visible Home category.

**Full suite after the change: 705 passing, 2 failing**, the same
`PricingPageCurrentPlanTest` pair, red since `e981b71` on 2026-07-18 and
reproduced by seven sessions.

**Deploy note.** No new migration — `home_sections` shipped in 1.8.36 and the
daily-banner placement in 1.8.37. But `random-category-rail.blade.php` is
DELETED, and a stale compiled view still calls it at runtime, so
`php artisan view:clear` before `view:cache` is not optional on this deploy.
The runbook already does both in that order at `docs/deploy/hostinger-vps.md`
§2.5. Follow it rather than a partial pull.

**The tree is shared again.** jambo-6b's `/cast-list` rename and round-avatar
work is uncommitted in `Your-Favourite-Personality.blade.php` alongside mine.
They preserved the `$sectionHeading ?? …` line. Do not push the tree as a
whole without their sign-off.

### 2026-09-10 — `/cast-list` replaces `/all-personality`, and the people go round (jambo-6b)

**Status:** complete
**Owns:** `mobile/src/screens/CastListScreen.tsx` (renamed from
`PersonalitiesScreen.tsx`), `public/frontend/css/jambo-header.css` (one added
block), `Modules/Frontend/routes/web.php`, `app/Rules/ReservedUsername.php`.

**Shares — named, and one of them is jambo-9c's:**
- `Modules/Frontend/resources/views/components/sections/Your-Favourite-Personality.blade.php`
  — the `View All` href, the slider's `data-mobile`, and one added wrapper
  class. **jambo-9c's `$sectionHeading ?? …` line is in this file and rode
  along in my commit**; they were told so it is not committed twice.
- `Modules/Frontend/app/Http/Controllers/FrontendController.php` — the deleted
  `all_personality()` and a docblock on `cast_list()`.
- `mobile/src/ui/theme.ts`, `TaxonomyCards.tsx`, `HomeScreen.tsx`,
  `endpoints.ts`, `navigation/types.ts`, `RootNavigator.tsx`.
- `docs/frontend-guide.md`, `docs/api/coverage.md`.

**What this is:** Rio spotted that `/all-personality` duplicated `/cast-list`,
then asked for the home rail's people to be round, smaller and four across on
both surfaces.

**Verified by measuring both surfaces, not by looking at them:**

| | Site | App | Delta |
|---|---|---|---|
| Home avatar | 75.5 x 75.5, radius 50%, 4 per view | 84.7 x 85, circle, 4 per view + a fifth peeking | 2.5% of viewport width |
| Home name | 12px | 12px, wrapping to two lines exactly as the site does | — |
| Cast list card | 97.98 x 127.38, aspect 1/1.3, radius 8, **3 per row** | 0.7694 against 0.7693, 3 per row | 0.01% |
| Detail-page cast row | 105.33 x 136.92, radius **8px** — unchanged | n/a | — |

- `/cast-list` returns 200, `/all-personality` returns 404, and the rail's
  `View All` href now reads `/cast-list` — all three read back from the server
  rather than assumed.

**🔴 Three faults, two of them mine and caught by measuring:**

1. **The CSS override was not scoped.** `.favourite-person-block` is worn by
   the home rail AND four cast rows on the detail pages. Probing a movie page
   showed it had gone round too. Fixed with a `--home` modifier class.
2. **`border-radius: 50%` silently lost** to Bootstrap's `rounded-3`, which
   ships `!important`. The square applied and the circle did not, which reads
   as "the rule did not load" rather than "the rule was outranked".
3. **A probe keyed on `.movie-geners-block` matched two different sections.**
   Genres and VJs share that class, so `querySelector` returned whichever the
   admin's arrangement ordered first — and jambo-9c had just made that order
   admin-controlled. The re-probe reads each section by its heading. My earlier
   VJs numbers were re-checked against it and were correct.

**Not verified:** the cast list's second page. The dev database holds 20 people
and the endpoint pages at 40, so `onEndReached` has never fired. Nothing on a
tablet or the TV build.

**Deliberately not changed, and then confirmed:** the movie and TV detail
pages' cast and crew rows. Rio said "on home"; I took him literally and then
put it back to him on 2026-09-11 as an open choice rather than leaving my own
reading standing — shown that the mismatch reads as an oversight, and that it
was one selector either way. **He chose to keep them as they are.** So the two
surfaces differ on purpose: circles on the home rail, the rounded 1/1.3
portrait on a detail page's cast row. It is written into the CSS comment too,
because the next person to see it will assume it was missed.

**Coordination.** jambo-9c held `Modules/Frontend` for the home-section
arrangement throughout. They stashed the shared tree once, which briefly took
this work out of it; it popped cleanly and all eleven files were verified
present by grepping for eight distinct markers before committing. Their
`randomHomeCategories` removal was checked against my rail-key test and does
not touch it.

**Next action created by this work, named rather than left implicit.**

**`HomeScreen`'s outer list must become a `FlatList`.** It renders its rails in
a plain `ScrollView` with `rails.map(...)`, so **every rail mounts on first
paint**. Each rail's items are a horizontal `FlatList`, so cards virtualise
sideways, but the outer list does not virtualise at all.

That was survivable while the home payload was bounded. It no longer is: this
session removed a cap of four category shelves, deliberately, so the number of
rails is now whatever an admin flags Visible Home. Measured by jambo-6b on the
current payload — 37,321 bytes, 15 rails, 109 items, about 340 bytes an item —
six extra shelves of ten is roughly another 20 KB of JSON and about fifteen
more image requests before the viewer has scrolled anything. On MTN data that
is real.

**Neither of us measured the growth**, because this database has no category
flagged Visible Home, so the cap removal changes nothing locally. The shape is
sound; the number is not evidence.

It is one line of structure and it belongs to whoever next opens
`mobile/src/screens/HomeScreen.tsx`, not inside a backend slice. Doing it there
is why this is written down instead of done.

### 2026-09-11 — seven sections removed, because the product does not have them

**Status:** complete, 1.8.39. Same session as the entry above.

**What happened, and it is worth reading before the next arrangement change.**
1.8.38 seeded `home_sections` from `HomeSection::DEFAULTS`, which listed every
rail `GET /api/v1/home` could build. That is not the same list as the shelves
the product actually has. Three of them — Top Picks, Popular Movies, Fresh
Picks — had been **deliberately retired from the website months earlier** and
replaced by category shelves. The evidence was in the file I deleted:
`random-category-rail.blade.php` was included three times and each call site
carried a comment naming the rail it replaced. I read those comments, quoted
them in the ADR as proof the stand-ins could go, and did not draw the other
conclusion — that the rails they stood in for were not wanted back.

Rio switched all seven off and said to remove them.

**Removed:** the rows (migration `2026_09_11_090000`), both registry constants,
the seven `titleRail` calls, five orphaned partials, three orphaned rail
archives, and four shared collections (`topPicks`, `latestShows`,
`internationalShows`, `upcomingMovies`) plus `resolveTopPicks()`.

**Kept, and each for a checked reason:** `latest-movies` and `upcomming`
partials, because `/home` includes them; `freshMovies` and `popularMovies`
collections, because `suggested` and `tranding-tab` on that same page read
them; the `latest-movies`, `latest-series` and `popular-series` archives,
which were already unlinked before today and are not dirt this change created.

**Verified.** Eleven rows; both constants still agree by key set; the website
renders seven headings and the API nine rails, consistent with each other and
with the stored positions. 102 Frontend tests pass. Four tests drove off
retired keys and now drive off `top_movies` and `exclusives`.

🔴 **Left for Rio, not decided here.** `TopPicksRecommender::forUser()` and
`forGuest()` have no caller now. They are the engine from
`docs/plans/top-picks-personalization.md` — roughly two hundred tested lines
plus the unreferenced `frontend.recommendations.enabled` flag — and the shelf
they ranked is gone. Deleting a personalisation engine because a shelf was
retired is a product decision. It should not sit unanswered for long.

**Deploy note.** Production is on 1.8.38 as of tonight and already runs the
arrangement. This adds one migration that deletes rows, and deletes Blade
files, so `view:clear` before `view:cache` matters again.

**For whoever is next.** When seeding a registry that an admin will manage,
seed it from **what the product renders**, not from what the code can produce.
The two lists look identical right up until one of them contains something
somebody deliberately removed.

### 2026-09-11 — category shelves dealt through the page, and a switch that lied

**Status:** complete, 1.8.40.

**Two reports from Rio, and only one was a bug.**

**1. Stacking.** Making the shelves one movable block in 1.8.38 was right for
the admin screen and wrong for the page. `HomeSection::spreadCategories()` now
deals them: the Categories row's position is where the first goes, the rest
follow one per `frontend.home.category_gap` (default two) other sections. It
runs at the end of `arrange()` and inside `webPlan()`, so both surfaces put the
same shelves in the same gaps — verified by rendering both and comparing the
sequences, not by reading the code.

Two edges are deliberate and tested: categories outlasting the page fall
consecutively at the end rather than being dropped, and sections outlasting the
categories simply continue.

**2. "More than 5 active, about 4 shown" was NOT a cap.** The cap of four went
in 1.8.38 and is live — checked against the deployed commit rather than
assumed. A Visible Home category is dropped for one reason now: no PUBLISHED
titles. `shapeCategoryRails` filters on `railItems->isNotEmpty()` and railItems
is built from published movies and shows only.

**That filter is correct and was invisible, which is the actual defect.** The
Categories screen showed the switch on and a Count column summing ALL titles
including drafts, so a category with five drafts read as active with content
and produced nothing. The row now says "Nothing published, so no shelf" when
the switch is on and the published count is zero. Rendering an empty rail
instead would have been the wrong fix.

**Verified.**
- Six categories activated locally: both surfaces returned the identical
  interleaved sequence — shelf, two sections, shelf, one section (the page ran
  out), then the remainder at the end.
- The gap set to zero restacks them, and that mutation fails the new test and
  nothing else. Restored; `grep MUTANT` is clean.
- The admin warning was proved by creating a Visible Home category with no
  published titles: the row showed the warning and `forWeb()` produced zero
  shelves in the same breath. The probe category was force-deleted.
- 103 Frontend tests pass.

**Not verified.** Production. This box has six categories and a small
catalogue; Rio's has more of both. The count that matters — how many of HIS
Visible Home categories have no published titles — is answerable only on his
server, and the screen now answers it for him.

**Deploy note.** No migration. Deletes `category-rails.blade.php`, so
`view:clear` before `view:cache` again.

**For whoever is next.** The gap lives in `Modules/Frontend/config/config.php`
under `home.category_gap`. If Rio ever wants the shelves somewhere other than
"from the Categories row onward", that is a second position, not a bigger gap,
and it wants a second row rather than a cleverer algorithm.
