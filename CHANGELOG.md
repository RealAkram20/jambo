# Release Notes

## Jambo

### 1.5.23 — Cast: add "Actress" alongside "Actor" everywhere

Admin cast rows only had a single "Actor" option for performers,
which forced female performers to be miscredited. Added "Actress"
as a second performer role and made every cast/Starring section
treat the two as one group.

**Admin** (`admin/movies/partials/cast-row.blade.php`):
- New `<option value="actress">Actress</option>` in the role
  dropdown, placed right after Actor.
- Character-name placeholder updated to "actors / actresses".

**Validation** (`StoreMovieRequest`, `StoreShowRequest`):
- `cast.*.role` `in:` rule now accepts `actress` in addition to
  the previous five roles.

**Frontend — Starring sections include both:**
- `Pages/Movies/detail-page.blade.php` and
  `Pages/TvShows/detail-page.blade.php`: cast filter changed
  from `role === 'actor'` to
  `in_array(role, ['actor', 'actress'])`. Per-card fallback
  label (when no `character_name` is set) now reflects the
  actual role — an actress without a character name shows
  "Actress" instead of being mislabeled "Actor".
- `Pages/TvShows/episode-page.blade.php`: same filter change in
  the Read-more modal.
- `Pages/MainPages/index-page.blade.php`: hero banner's
  `$heroCast` now picks up actresses too.

**Recommendations** (`TopPicksRecommender`,
`SectionDataComposer`):
- All four `wherePivotIn('role', […])` eager-loads now include
  `actress`, so personalised rails and the homepage hero pull
  actress credits the same way they pull actor credits.

No DB migration — `role` is a free-form string column, so
existing rows are unaffected and "actress" works the moment the
admin saves a cast row with that role.

### 1.5.22 — VJ rails: fix "Nothing here yet" on every VJ except the first

Follow-up to 1.5.21 — that release exposed the underlying bug
rather than fixing it.

**Symptom:** on `/movie` (and the genre/series twins), only the
first VJ row rendered cards; every other VJ row showed
"Nothing here yet." even when those VJs had published movies
visible on their own `/vj/{slug}` detail page.

**Root cause:** classic Eloquent eager-load gotcha. The four
`topVjsFor*()` loaders did:

```php
->with(['movies' => fn ($q) => $q->published()->limit(20)])
```

Eloquent doesn't loop per parent — it issues a single
`SELECT … WHERE vj_id IN (1,2,3,…) LIMIT 20`. So the top-ranked
VJ in the result set hogs all 20 rows; every subsequent VJ's
relation collection comes back empty, and the carousel partial
renders its `@empty` branch.

**Fix:**
- Removed `->limit(20)` from all four `topVjsFor*()` eager-loads.
- Moved the per-VJ cap into `vj-carousel.blade.php` via
  `$items = $items->take(20)`, where it actually applies per VJ.

The eager-load now fetches every published title for every
selected VJ in one query, which is fine at this scale (worst
case: 100 VJs × ~30 titles = 3 000 small rows).

### 1.5.21 — VJ rails: render every VJ + bump per-VJ items 10 → 20

Two related bugs on the VJ-rail pages (`/movie`, `/series`,
`/geners/{slug}/vjs`):

1. **Some VJs never appeared.** The four entry points
   (`movie()`, `tv_show()`, `genreVjs()`, `genreVjsShows()`)
   only rendered the **top 5 VJs by catalogue size** server-side,
   leaving smaller VJs (1–3 titles) reachable only via the Load
   More button. Content team's expectation is "every VJ I've
   published for is visible on first load". Bumped the initial
   render cap to **100 VJs** — covers the foreseeable catalogue
   while keeping a hard ceiling. The Load More button stays as
   a safety net for anything beyond.

2. **The carousels that did appear couldn't scroll.** Each VJ
   row is a swiper-card that surfaces 7 cards per view at
   desktop. The eager-load on the `topVjsFor*()` loaders capped
   each VJ at 10 latest titles — which left only 3 hidden cards
   to swipe to (sometimes zero, since `watchOverflow` disables
   nav when `slidesPerView >= slides`). Bumped the per-VJ
   eager-load to **20** in all four sibling loaders so the rails
   behave consistently and have meaningful slide depth.

Files: `Modules/Frontend/app/Http/Controllers/FrontendController.php`
(`topVjsForPage`, `topVjsForGenre`, `topVjsForShowsByGenre`,
`topVjsForShowsPage`, plus the four call sites in `movie()`,
`tv_show()`, `genreVjs()`, `genreVjsShows()`).

Note for content team: a VJ still only appears when at least one
of their movies is `status = Published` AND `published_at <= now()`.
A VJ with assigned-but-draft movies is correctly hidden — publish
the movies and the VJ row appears.

### 1.5.20 — fix "Undefined variable $items" on /movie Load More

`FrontendController::moreVjsForMoviesPage()` (the AJAX endpoint
behind the Load More button on /movie) was passing the wrong
variable name to the `vj-carousel` partial — `'movies'` instead
of `'items'`. The partial uses `@forelse ($items as $item)`, so
every Load More click 500'd with "Undefined variable $items"
deep in `storage/framework/views/...`.

Three sibling endpoints (genreVjs, genreVjsShows,
moreVjsForShowsPage) were already correct; only this one had
drifted. Fixed; also added `contentKind => 'movie'` for parity
with the others, even though the partial defaults to that.

### 1.5.19 — spam-folder notices during sender-reputation warm-up

We're sending mail from a brand-new VPS IP, so Gmail / Outlook / etc.
spam-fold our verification + reset-link emails until reputation
builds (typically 2–4 weeks of users marking "Not spam"). SPF, DKIM,
DMARC and rDNS are all correctly configured — this is purely IP
reputation cold-start, not a misconfiguration.

To stop new signups bouncing off "I never got the email":

- `auth/verify-email` shows a prominent yellow notice: *"Don't see it
  within a minute? Check your Spam or Junk folder. Marking it 'Not
  spam' tells your provider to deliver future emails to your inbox."*
- `auth/forgot-password` shows the same notice immediately after a
  reset-link is requested (only when the success flash is present so
  the empty initial state stays clean).
- The post-registration welcome flash now names the email address and
  prompts the user to check spam if it doesn't arrive in a couple of
  minutes.

These notices have an inline comment with a removal hint: *"once
we've been live ~6 weeks and the bounce-to-spam rate has dropped"*.

### 1.5.18 — password reset 405 fix + queue worker for verify-email

Two pre-existing production bugs (both predate the recent security
work; just only got noticed now).

- **Password reset returned 405 Method Not Allowed.** The
  `auth/reset-password.blade.php` form posted to `password.update`
  (a PUT route used for in-app password change from the security
  tab) instead of `password.store` (the public reset POST handler).
  Fixed.
- **Email verification mail wasn't reaching new signups.** The
  `QueuedVerifyEmail` notification implements `ShouldQueue` so the
  email goes onto the `jobs` table; without a queue worker draining
  it, the row sits forever. Forgot-password and the other
  ChannelGated notifications send synchronously which is why those
  worked. Added a scheduled `queue:work --stop-when-empty
  --max-time=55` running every minute via the existing scheduler
  cron (no supervisord setup needed).

After deploy: drain the existing backlog once with
`php artisan queue:work --stop-when-empty` so any verify-emails
queued while the worker was missing actually go out, then the
new scheduler line keeps it draining automatically.

### 1.5.17 — WhatsApp / Telegram social-preview reliability

WhatsApp + Telegram are stricter than LinkedIn / Facebook on the
Open Graph image tag set. Without the supplementary tags they
sometimes silently drop the image. Added:

- `og:image:secure_url` — pins the HTTPS variant
- `og:image:type` — derived from the URL's file extension
  (jpg/jpeg/png/webp/gif → standard MIME)
- `og:image:alt` — defaults to the page title

Width/height are intentionally not emitted — admin-pasted URLs
(Dropbox / Contabo / external CDNs) make accurate dimensions
impossible without an extra HEAD request per pageview. Pages that
need them can `@push('seo:head', ...)` per-template.

Also: WhatsApp caches link previews for ~7 days. To force a
re-scrape, paste the URL through Facebook's Sharing Debugger
(developers.facebook.com/tools/debug) and click "Scrape Again",
or share with a `?v=N` query param to fool the cache.

### 1.5.16 — three quality fixes

- Subscription-expired notification (and any other ChannelGated
  notification) now addresses the recipient by their first name —
  "Hi Akram," rather than "Hi there," — and the admin-facing
  message names the affected user ("Jane Smith's subscription has
  expired") instead of the legacy "User #11" label. Falls back
  through username and email-local-part if first/last aren't set.
- Open Graph / Twitter Card image now renders correctly on shared
  links. Previously even with a featured image set, the meta tag
  was empty: managed Pages, Movie detail, and TV-show detail views
  weren't yielding `seo:image`. They do now (movies + shows prefer
  the wider backdrop_url for `summary_large_image`). The head-tags
  partial also force-converts relative paths to absolute URLs since
  Facebook silently drops relative `og:image` values.
- Contact form now ships the same honeypot + reCAPTCHA defence as
  register/login/forgot-password. Throttle was already in place; the
  bot-defence component is now rendered in the form and the controller
  silently flashes success on honeypot trigger.

### 1.5.15 — anti-bot signup defences + admin reCAPTCHA toggle

Production was seeing low-volume but persistent automated signups
(~1/hr) that never confirmed their email. None ever exploited
anything — they just polluted the users table. Layered defences,
no schema changes, no module changes.

Defences (all unconditional, free, zero UX friction)
- **Honeypot field** in register / forgot-password / login forms.
  Hidden from humans (CSS off-screen + tabindex=-1 + autocomplete=off
  + aria-hidden); bots fill it. Server silently accepts the request
  but creates no user / sends no reset link.
- **Throttle** on `POST /register` and `POST /forgot-password` —
  5 attempts per IP per 10 minutes.
- **Nightly cleanup** `jambo:purge-unverified --days=7` runs at
  03:10 UTC and deletes accounts with `email_verified_at IS NULL`
  older than 7 days. Skips anyone with the `admin` role for safety.
  `--dry-run` lists candidates without deleting.

Optional reCAPTCHA (v2 + v3, configurable from admin)
- New "Google reCAPTCHA" card in `/admin/settings`: enable toggle,
  v2/v3 selector, site key, secret key (encrypted at rest), v3
  score threshold.
- Site key + secret are stored in the settings table. Secret is
  Crypt-encrypted on save like the SMTP password. Blank secret on
  re-save = "keep existing".
- Verifies on register / login / forgot-password when enabled. When
  disabled (the default), pass-through — only honeypot + throttle
  run. No code changes needed to enable, just paste keys in admin.
- New `App\Services\RecaptchaService`, new
  `<x-auth.bot-defence />` blade component shared across the three
  forms.

### 1.5.14 — security pass + diagnostics

Pre-launch hardening sweep. No data migrations, no module changes — all
changes are code/config/views; existing movies, episodes, payment
orders, users, settings, branding, and gallery files are untouched.

Security
- Pesapal IPN/callback no longer trust the `OrderTrackingId` from the
  request — re-poll always uses the tracking ID stored at order
  creation. Closes the order-forging path where an attacker paired
  their own pending merchant_reference with someone else's completed
  tracking ID. `payment/ipn` is POST-only.
- `db:seed` no longer creates `admin@demo.com / 12345678` unless
  `IS_DUMMY_DATA=true`.
- `confirm-password` is rate-limited (`throttle:6,1`).
- Notifications Guest model uses an explicit `$fillable = ['id']`.
- `SystemUpdate.allow_users_id` reads from `JAMBO_UPDATER_USER_IDS`
  env so production can pin updater access to specific user IDs.
- New `App\Http\Middleware\SecurityHeaders` adds X-Frame-Options,
  X-Content-Type-Options, Referrer-Policy, Permissions-Policy, and
  HSTS to every response.
- CORS narrowed to the production origin (and localhost dev hosts),
  configurable via `CORS_ALLOWED_ORIGINS` env. No more wildcard.
- SVG dropped from branding (logo/favicon/preloader) and Files Gallery
  upload allowlists. Existing SVG files keep displaying — only new
  uploads are blocked.
- SEO verification file upload rejects bodies containing
  script/iframe/event-handler patterns or `javascript:` URIs.
- New `App\Support\HtmlSanitizer` cleans Quill-authored Pages content
  on save (allow-list of safe tags + attributes, drops `on*=`,
  `javascript:`, etc.). No new composer dependency.
- `axios` bumped to `^1.7.7+` (resolved at `^1.15.x`) to clear the
  CVE-2023-45857 / CVE-2024-39338 class.
- SystemUpdate verifies the downloaded archive's SHA-256 against the
  manifest when present, and the zip extractor enforces a realpath /
  no-`..` containment check (zip-slip guard).
- Socialite `same-email` merge now auto-sets `email_verified_at` when
  matching an unverified local row, since Google has already verified
  the address — closes the email-squatting takeover path.

Admin
- New "Error Log" admin page (after SEO menu): tail any file under
  `storage/logs/` with a per-file Clear button.
- New "System Status" admin page: app/Laravel/PHP versions, debug
  flag, environment, runtime drivers, DB connectivity, free disk,
  storage symlink presence, PHP extensions, and module enable map.

## [Unreleased](https://github.com/laravel/laravel/compare/v10.2.7...10.x)

## [v10.2.7](https://github.com/laravel/laravel/compare/v10.2.6...v10.2.7) - 2023-10-31

- Postmark mailer configuration update by [@ninjaparade](https://github.com/ninjaparade) in https://github.com/laravel/laravel/pull/6228
- [10.x] Update sanctum config file by [@ahmed-aliraqi](https://github.com/ahmed-aliraqi) in https://github.com/laravel/laravel/pull/6234
- [10.x] Let database handle default collation by [@Jubeki](https://github.com/Jubeki) in https://github.com/laravel/laravel/pull/6241
- [10.x] Increase bcrypt rounds to 12 by [@valorin](https://github.com/valorin) in https://github.com/laravel/laravel/pull/6245
- [10.x] Use 12 bcrypt rounds for password in UserFactory by [@Jubeki](https://github.com/Jubeki) in https://github.com/laravel/laravel/pull/6247
- [10.x] Fix typo in the comment for token prefix (sanctum config) by [@yuters](https://github.com/yuters) in https://github.com/laravel/laravel/pull/6248
- [10.x] Update fixture hash to match testing cost by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/laravel/pull/6259
- [10.x] Update minimum `laravel/sanctum` by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/laravel/pull/6261
- [10.x] Hash improvements by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/laravel/pull/6258
- Redis maintenance store config example contains an excess space by [@hedge-freek](https://github.com/hedge-freek) in https://github.com/laravel/laravel/pull/6264

## [v10.2.6](https://github.com/laravel/laravel/compare/v10.2.5...v10.2.6) - 2023-08-10

- Bump `laravel-vite-plugin` to latest version by [@adevade](https://github.com/adevade) in https://github.com/laravel/laravel/pull/6224

## [v10.2.5](https://github.com/laravel/laravel/compare/v10.2.4...v10.2.5) - 2023-06-30

- Allow accessing APP_NAME in Vite scope by [@domnantas](https://github.com/domnantas) in https://github.com/laravel/laravel/pull/6204
- Omit default values for suffix in phpunit.xml by [@spawnia](https://github.com/spawnia) in https://github.com/laravel/laravel/pull/6210

## [v10.2.4](https://github.com/laravel/laravel/compare/v10.2.3...v10.2.4) - 2023-06-07

- Add `precognitive` key to $middlewareAliases by @emargareten in https://github.com/laravel/laravel/pull/6193

## [v10.2.3](https://github.com/laravel/laravel/compare/v10.2.2...v10.2.3) - 2023-06-01

- Update description by @taylorotwell in https://github.com/laravel/laravel/commit/85203d687ebba72b2805b89bba7d18dfae8f95c8

## [v10.2.2](https://github.com/laravel/laravel/compare/v10.2.1...v10.2.2) - 2023-05-23

- Add lock path by @taylorotwell in https://github.com/laravel/laravel/commit/a6bfbc7f90e33fd6cae3cb23f106c9689858c3b5

## [v10.2.1](https://github.com/laravel/laravel/compare/v10.2.0...v10.2.1) - 2023-05-12

- Add hashed cast to user password by @emargareten in https://github.com/laravel/laravel/pull/6171
- Bring back pusher cluster config option by @jesseleite in https://github.com/laravel/laravel/pull/6174

## [v10.2.0](https://github.com/laravel/laravel/compare/v10.1.1...v10.2.0) - 2023-05-05

- Update welcome.blade.php by @aymanatmeh in https://github.com/laravel/laravel/pull/6163
- Sets package.json type to module by @timacdonald in https://github.com/laravel/laravel/pull/6090
- Add url support for mail config by @chu121su12 in https://github.com/laravel/laravel/pull/6170

## [v10.1.1](https://github.com/laravel/laravel/compare/v10.0.7...v10.1.1) - 2023-04-18

- Fix laravel/framework constraints for Default Service Providers by @Jubeki in https://github.com/laravel/laravel/pull/6160

## [v10.0.7](https://github.com/laravel/laravel/compare/v10.1.0...v10.0.7) - 2023-04-14

- Adds `phpunit/phpunit@10.1` support by @nunomaduro in https://github.com/laravel/laravel/pull/6155

## [v10.1.0](https://github.com/laravel/laravel/compare/v10.0.6...v10.1.0) - 2023-04-15

- Minor skeleton slimming by @taylorotwell in https://github.com/laravel/laravel/pull/6159

## [v10.0.6](https://github.com/laravel/laravel/compare/v10.0.5...v10.0.6) - 2023-04-05

- Add job batching options to Queue configuration file by @AnOlsen in https://github.com/laravel/laravel/pull/6149

## [v10.0.5](https://github.com/laravel/laravel/compare/v10.0.4...v10.0.5) - 2023-03-08

- Add replace_placeholders to log channels by @alanpoulain in https://github.com/laravel/laravel/pull/6139

## [v10.0.4](https://github.com/laravel/laravel/compare/v10.0.3...v10.0.4) - 2023-02-27

- Fix typo by @izzudin96 in https://github.com/laravel/laravel/pull/6128
- Specify facility in the syslog driver config by @nicolus in https://github.com/laravel/laravel/pull/6130

## [v10.0.3](https://github.com/laravel/laravel/compare/v10.0.2...v10.0.3) - 2023-02-21

- Remove redundant `@return` docblock in UserFactory by @datlechin in https://github.com/laravel/laravel/pull/6119
- Reverts change in asset helper by @timacdonald in https://github.com/laravel/laravel/pull/6122

## [v10.0.2](https://github.com/laravel/laravel/compare/v10.0.1...v10.0.2) - 2023-02-16

- Remove unneeded call by @taylorotwell in https://github.com/laravel/laravel/commit/3986d4c54041fd27af36f96cf11bd79ce7b1ee4e

## [v10.0.1](https://github.com/laravel/laravel/compare/v10.0.0...v10.0.1) - 2023-02-15

- Add PHPUnit result cache to gitignore by @itxshakil in https://github.com/laravel/laravel/pull/6105
- Allow php-http/discovery as a composer plugin by @nicolas-grekas in https://github.com/laravel/laravel/pull/6106

## [v10.0.0 (2022-02-14)](https://github.com/laravel/laravel/compare/v9.5.2...v10.0.0)

Laravel 10 includes a variety of changes to the application skeleton. Please consult the diff to see what's new.
