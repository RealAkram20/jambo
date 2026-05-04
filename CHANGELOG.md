# Release Notes

## Jambo

### 1.6.3 — Notifications: bulk "Delete all" button

Pairs with the existing "Mark all read" so users with months of
piled-up notifications don't have to click 200 trash icons one by
one. Hard delete (no soft-delete column on the notifications
table), so the click goes through a `confirm()` first.

- New `destroyAll()` method on `NotificationController`.
- New route `DELETE /notifications/all`, named
  `notifications.destroy-all`. Registered BEFORE the
  `DELETE /notifications/{id}` wildcard so Laravel doesn't match
  `id=all` against the single-row destroy and 404.
- "Delete all" button on the user inbox
  ([resources/views/profile-hub/notifications.blade.php](resources/views/profile-hub/notifications.blade.php))
  next to "Mark all read" — visible whenever the inbox has any
  notifications (read or unread).
- Same button on the admin notifications page
  ([Modules/Notifications/resources/views/index.blade.php](Modules/Notifications/resources/views/index.blade.php))
  on the Inbox tab. Both reload the page on success so the
  empty-state placeholder + bell badge re-render cleanly.

### 1.6.2 — Define the missing `.btn-ghost` class

User report: "Mark all as read" button missing from the user
notifications page — they have to click each notification one by
one. Investigation showed the button DOES exist in
[resources/views/profile-hub/notifications.blade.php](resources/views/profile-hub/notifications.blade.php),
the click handler IS wired to the existing
`notifications.mark-all-read` endpoint, and the unread-count
visibility logic IS in place. The issue was purely visual: the
button uses `class="btn btn-ghost btn-sm"`, but `.btn-ghost` is
**referenced 30+ times across the codebase and documented in
docs/ui-guidelines.md as "Ghost = back/cancel only"** —
yet never actually defined as a CSS rule anywhere. Bootstrap
doesn't ship it. The button was rendering as un-styled text
against the dark inbox card, invisible to anyone who wasn't
hovering over it.

Fix: added a `.btn-ghost` rule at the bottom of
[public/frontend/css/jambo-header.css](public/frontend/css/jambo-header.css)
(loaded site-wide by the frontend master layout). Neutral
transparent → grey-on-hover styling that works on both dark and
light surfaces. This makes ALL frontend `btn-ghost` usages
visible at once, not just the notifications button.

Cache-busts via `versioned_asset()` automatically.

Note: admin-side `btn-ghost` buttons (back/cancel in admin
forms) still render unstyled because admin pages load their
CSS from `dashboard/*` instead of `frontend/css/`. We'll mirror
the rule into the admin stylesheet when an admin user reports
the same issue, or proactively if you want me to do it now.

### 1.6.1 — Player: fullscreen actually fills the screen on Android TV

User report: clicking fullscreen on the watch page on Android TV
left ~160px of black on each side instead of filling the panel.
Root cause was two design constraints leaking into fullscreen
mode in [public/frontend/css/player.css](public/frontend/css/player.css):

- `.jambo-player-frame { max-width: 1600px; aspect-ratio: 16/9 }`
- `.jambo-watch-hero  { max-width: 1600px }`

Both kept applying when the element entered `:fullscreen`, so on
a 1920×1080 TV the player was capped at 1600px wide (black bars
on either side). Added explicit `:fullscreen` /
`:-webkit-full-screen` overrides on the frame, the hero wrapper,
the Media Chrome skin, and the underlying `<video>` element so
fullscreen really fills the viewport regardless of which element
the fullscreen API targets. `object-fit: contain` is preserved
on the video so 21:9 movies still letterboxes cleanly instead
of stretching.

Browser cache is auto-busted via the existing
`versioned_asset()` helper that all three watch pages use — the
mtime change appends a fresh `?v=` to the player.css URL.

### 1.6.0 — Perf: on-the-fly image resize + WebP via /img proxy

Network-panel diagnostics on the home page showed **35.4 MB
transferred / 47s to finish** with cache disabled — almost all of
it was raw uploaded images served at full source resolution to
every device. Even with the 1.5.33 browser-cache headers in place,
first visits paid the full bill. This release ships a real
responsive-image pipeline.

**New `/img/{path}` route.** Backed by `league/glide` and a thin
`App\Http\Controllers\ImageProxyController`. Accepts `?w=`, `?h=`,
`?q=`, `?fm=` query params (clamped to safe ranges so a bad bot
can't ask for gigapixels). Sources files from `public/`; caches
resized variants to `storage/app/glide-cache/` so subsequent
requests for the same size+format are served straight from disk.
Imagick is used when available, GD as the universal fallback.

**Two new helpers in `app/helpers.php`:**
- `media_img($value, $width, $fallback?, $legacyDir?)` — returns a
  proxy URL with `?w=N&fm=webp`. External URLs pass through
  unchanged because Glide can't proxy remote sources.
- `media_srcset($value, [320, 640, ...], ...)` — builds a real
  `srcset` string so the browser picks the right size per
  viewport / DPR.

**Card components updated to use both, with `loading="lazy"
decoding="async"` and a `sizes` attribute** so phones get 320w
and desktops/TVs get 640w (or 384w for cast headshots):

- `card-style.blade.php`, `genres-card.blade.php`,
  `top-ten-card.blade.php`, `continue-watch-card.blade.php`,
  `personality-card.blade.php`, `episode-card.blade.php`

**Hero backdrop now routed through the proxy at 1920w WebP**
(`Pages/MainPages/index-page.blade.php`). Previously the heaviest
assets on the page — typically 4K source files at 3-5MB each
across multiple slides. Cuts ~90% per backdrop with no visible
difference at TV / desktop resolutions.

**Expected impact** based on the 35.4MB baseline:
- Hero: ~15MB → ~1MB
- Posters: ~36MB across all carousels → ~2-3MB
- **Total page weight: ~35MB → ~3-4MB on first load**
- Subsequent visits hit the 1y browser cache from 1.5.33

**Operator notes for deploy:**
- `composer require league/glide` adds the dependency.
- PHP must have GD or Imagick. CyberPanel default PHP includes
  GD; verify with `php -m | grep -i gd`.
- `storage/app/glide-cache/` will be created on first request and
  needs to be writable by the web user (`jambo2820`).
- Cache populates lazily as users visit; first request to a
  given size resizes (~150-300ms), subsequent are instant.

### 1.5.33 — Perf: browser caching, lazy-load posters, drop dead deps

Targeted fixes for users on weak networks (Android TV being the
loudest complaint) where every navigation was re-downloading every
asset.

1. **Browser-cache static assets via `public/.htaccess`.** Hashed
   Vite output (`/build/assets/*.js`, `*.css`, fonts) now ships with
   a 1-year `Cache-Control: public, max-age=31536000, immutable` —
   safe because the filename hash changes on every release, so it
   self-invalidates. Static images get a 7-day window. HTML/Laravel
   responses are deliberately untouched so per-user session state
   keeps working. Wrapped in `<IfModule>` blocks so the file no-ops
   if `mod_expires` / `mod_headers` aren't loaded.

2. **Lazy-load below-the-fold poster images.** `card-style` (the
   most-used poster card on the home page) and `genres-card` now
   carry `loading="lazy" decoding="async"`. Existing `top-ten-card`,
   `continue-watch-card`, `personality-card`, and `episode-card`
   already had it, so the home page is now consistent.

3. **Dropped two unused dependencies from package.json.**
   `popper.js@1.16` (legacy v1, superseded by `@popperjs/core@2`
   which `app.js` actually imports) and the typo `i@0.3.7`. Run
   `npm install` to update lockfile.

### 1.5.32 — SEO: live diagnostic for "tag not detected on website"

The Google Tag field already accepted `G-XXXXXXXXXX` IDs and the
controller already extracted the ID when an admin pasted the
entire `<!-- Google tag (gtag.js) -->` snippet — so storage was
fine. The recurring "Google says my tag isn't detected" issue
was almost always one of two silent gates:

1. **Master switch is OFF** (the default). Saving a tag ID alone
   doesn't render anything; you also have to flip "Enable
   analytics tracking" on.
2. **You're testing it logged in as admin** while "Don't track
   logged-in admins" is ON (also default). The tag IS being
   rendered for anonymous visitors, but suppressed in your own
   browser, so View Source comes up empty.

Added a live diagnostic block at the top of the Analytics card
that shows, at a glance:

- ✓/✗ Tag ID saved (and the value)
- ✓/✗ Master switch ON/OFF
- ✓/✗ Whether an anonymous visitor would see the tag right now
- ✓/✗ Whether you (the logged-in admin) would see it, with an
  explicit incognito hint when admin exclusion is hiding it
- A collapsible "Show the exact snippet being injected" panel
  with the rendered gtag.js snippet for copy/paste verification

Also relabelled the field from "Google Analytics 4 — Measurement
ID" to "Google Tag (gtag.js) — Measurement ID" and clarified in
the help text that pasting the entire snippet works too.

### 1.5.31 — Notifications: use "series" not "shows" + fix dead URLs

The notification copy still said "show" / "shows" in places
that reach end users, even though every other surface of the
app calls them "series". Fixed:

- **ShowAddedNotification** — title "New **show** added" →
  "New **series** added"; action button label "Open show" →
  "Open series".
- **NotificationSetting::definitions()** (admin Settings tab) —
  the toggle labelled "TV **show** added" → "TV **series**
  added"; description "...existing **show**" → "...existing
  **series**".
- **WelcomeUserNotification** — "movies, **shows**, and add
  favourites" → "movies, **series**, and add favourites".

While editing the same notification classes, fixed a related
URL bug: action_url on Show/Episode/Season-added notifications
pointed at `/tv-show-detail/{slug}` (which 301-redirects, an
extra hop) and fell back to `/tv-shows` (which doesn't exist —
404). Rewrote both to `/series/{slug}` and `/series` so the
"Open series" button lands cleanly.

Note for ops: existing notification rows in the database have
the OLD title/message baked in at the time they were sent —
this release only affects notifications dispatched from now on.
The admin Settings tab labels update immediately because they
render live from PHP rather than storage.

### 1.5.30 — VJ pages: use "series" instead of "shows"

The VJ overview (`/vj/{slug}`) and the VJ series-detail
(`/vj-series/{slug}`) pages were rendering "shows" via
`__('streamTag.shows')`. Site-wide convention is "series" — the
matching key `streamTag.series` already exists in
`lang/en/streamTag.php`. Switched both call sites.

### 1.5.29 — Share-link previews on the watch + watchlist-play pages

Follow-up to 1.5.28: extended the per-page SEO metadata to the
two player pages. The realistic share moment isn't from the
detail page (where the user is just browsing) — it's from the
player itself, when someone is enjoying a title and wants to
send it to a friend.

- `/watch/{slug}` (movie player): now sets the same `<title>`,
  og:image, og:description as the movie detail page, scoped to
  the movie being watched.
- `/watchlist/{slug}` (in-playlist movie player): same pattern,
  reading from `$watchable` (the resolved Movie) and `$poster`
  (the controller already computes both for the player chrome).

Episode pages (`/episode/...`) were already covered in 1.5.28.

### 1.5.28 — Share-link previews for movies, series, episodes

Until now, sharing a link to a movie/series/episode page produced
a generic preview ("Jambo" + the site-wide default image +
default description) — the per-page SEO meta tags weren't being
filled in correctly. After this release, a link to e.g.
`/movie-detail/some-movie` previews on WhatsApp / Telegram /
Twitter / Facebook with:

- **Title:** "Movie Title - Jambo"
- **Description:** the movie's synopsis (truncated to 200 chars)
- **Image:** the movie's backdrop (or poster fallback)

Three concrete fixes:

1. **Movies + Series detail pages had a wrong field name.** They
   were reading `$movie->description` / `$show->description`,
   neither of which exists on the model. The actual prose field
   is `synopsis`. Result: every detail page was silently falling
   back to the global default description. Now uses `synopsis`.

2. **No `<title>` was set on detail pages.** The browser tab
   read just "Jambo" and the og:title fallback inherited that.
   Now passes `'title' => $movie->title` (and `$show->title`)
   into the layout's `@extends`.

3. **Episode pages had zero SEO metadata.** Added title (formatted
   as "Show — S01E03: Episode Title"), description (episode
   synopsis with show synopsis fallback), and image (episode still
   with show backdrop / poster fallback).

Image URLs now run through `media_url()` before reaching head-tags
so legacy bare filenames (Streamit-template-era values like
`media/foo.webp`) get resolved to public asset paths instead of
becoming broken absolute URLs at the root.

### 1.5.27 — VJ overview hero showed wrong titles (composer collision)

The combined VJ overview at `/vj/{slug}` (added in 1.5.24) was
rendering a hero of titles that didn't belong to the visited VJ.
Cause: the controller passed `$heroItems` scoped to the VJ, but
`SectionDataComposer` registers a global `heroItems` for every
`frontend::Pages.*` view (the homepage's mixed movies+series
banner). View composer data is merged in **after** controller
data, so the global feed silently overwrote the VJ's hero.

Renamed the controller variable to `$vjHeroItems` (and updated
the overview view to consume the new name) so there's no key
collision. Left a comment at both ends explaining why the
unusual name matters, since the trap is invisible — you only
notice when the hero shows wrong content.

### 1.5.26 — Add missing streamTag.vjs translation key

The new homepage VJs slider header rendered the literal string
"streamTag.vjs" because the translation key was missing. The
`?? 'VJs'` fallback in the template was dead code — Laravel's
`__()` returns the key itself (a truthy string) when a
translation is missing, so the null-coalesce never fires.
Added `'vjs' => 'VJs'` next to `'genre' => 'Genres'` in
`lang/en/streamTag.php`.

### 1.5.25 — Hotfix: homepage 500 from misaligned withCount key

The homeVjs query in 1.5.24 had two spaces between "shows" and
"as" in the withCount alias key (`'shows  as shows_count'`),
visually aligning it with the `'movies as movies_count'` line
above. Production Laravel parsed the literal string as the
relation method name, throwing:

> Call to undefined method
> Modules\\Content\\app\\Models\\Vj::shows  as shows_count()

Collapsed to a single space. No other functional change.

### 1.5.24 — VJs: homepage slider + combined overview at /vj/{slug}

Two related changes that together promote VJs from a hidden
secondary axis (only reachable from the per-row "View All" links
on /movie and /series) to a first-class browse axis with its own
landing page.

**1. New homepage VJs slider.** Inserted into `ott-page` (the
homepage) between **Top 10 Series to Watch** and **Only on Jambo**.
Mirrors the existing genres slider pixel-for-pixel — same
`card-genres-grid` partial, same swiper config, same View All
behaviour. Cards link to the new combined VJ overview page below.
Data wired by `SectionDataComposer` as `$homeVjs` (top 12 VJs
ranked by combined published movies + shows count). Each card uses
the new `Vj::featured_image_url` accessor, which falls back through
the VJ's most recent published movie / show backdrop / poster —
mirrors the `Genre::featured_image_url` accessor exactly.

**2. Route restructure — `/vj/{slug}` is now the combined overview.**

- `/vj/{slug}` (NEW) → `vjOverview()` → hero rotating through the
  VJ's newest titles regardless of type (movies + series mixed),
  then a Movies rail and a Series rail below it. Each rail caps at
  20 with View All linking to the type-scoped catalogue page.
- `/vj-movie/{slug}` (formerly `/vj/{slug}`) → `vjMovieDetail()`
  → unchanged movies-only catalogue, organised by genre with
  Load More.
- `/vj-movie/{slug}/more` (formerly `/vj/{slug}/more`) →
  `vjMovieGenreLoadMore()`.
- `/vj-series/{slug}` → unchanged.
- `/vj/{slug}/more` 301-redirects to `/vj-movie/{slug}/more` so
  any external bookmarks pointing at the old movies-only Load
  More endpoint keep working.

The route name `frontend.vj_detail` was kept on the new combined
overview, so the ~5 internal callers that already used it (homepage
sitemap, mobile-footer, vj-carousel) all naturally land on the
combined page now — which is what every caller actually wanted
("show me this VJ"). The one place that genuinely needed
movies-only — vj-carousel's "View All" link on a movies row — was
explicitly switched to `frontend.vj_movie_detail`.

No DB migration; no data backfill; existing bookmarks at the old
`/vj/{slug}` URL now land on the more-useful combined overview
instead of the movies-only page.

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
