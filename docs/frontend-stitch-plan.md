# Frontend Stitch Plan — Missing-Pieces Audit

**Scope:** Additive recommendations only. Every item here wires existing models or template pages to each other without changing schema, renaming routes, or refactoring existing working features.

**Last audit:** 2026-04-18

---

## Inventory snapshot

### Working (wired to real data)

- `/search` JSON endpoint (movies + shows live-match)
- `/movie`, `/series` — VJ-grouped carousels with Load More
- `/vj/{slug}`, `/vj-series/{slug}` — VJ catalogue by genre with grid Load More
- `/movie-detail/{slug}`, `/series/{slug}` — full metadata, taxonomy, cast
- `/watch/{slug}`, `/episode/{slug}` — real player, resume position, next/prev episode, similar rails
- `/cast-details/{slug}`, `/cast-list`, `/all-personality` — real Person records
- `/geners/{slug}`, `/all-genres`, `/tag/{slug}`, `/tags` — taxonomy listings with counts
- Continue-watching section + remove endpoint
- Tier gating on all watch routes (`userCanWatch`)

### Models that exist but aren't surfaced in the UI

| Model | Module | Used in frontend? |
|---|---|---|
| `WatchlistItem` | Streaming | **No** — model + migration exist, zero wiring |
| `WatchHistoryItem` | Streaming | Yes (continue watching) |
| `Review` | Content | **No** — no review form, no review list on detail pages |
| `Rating` | Content | **No** — detail pages show hardcoded star rating |
| `Comment` | Content | **No** — no comment thread on any page |
| `UserSubscription` | Subscriptions | Partial — gating uses it; membership pages don't display it |
| `PaymentOrder` | Payments | **No** — no order history UI |
| `Category` | Content | **No** — no `/categories` browse route |

---

## 1. High-impact quick wins (non-breaking, < 30 min each)

### 1.1 Wire Watchlist Detail page to the existing `WatchlistItem` model

- **Where it's broken:** [FrontendController.php:772](../Modules/Frontend/app/Http/Controllers/FrontendController.php#L772) returns the view with no data. [watchlist-detail.blade.php](../Modules/Frontend/resources/views/Pages/watchlist-detail.blade.php) renders hardcoded card placeholders.
- **What already exists:** `Modules\Streaming\app\Models\WatchlistItem` with `user_id`, polymorphic `watchable` (Movie | Show), `added_at`, and an `addFor()` static helper.
- **Wire-up:**
  1. Controller: `$items = WatchlistItem::where('user_id', auth()->id())->with('watchable.genres')->latest('added_at')->get();` → pass to view.
  2. View: replace hardcoded `@include('frontend::components.cards.card-style', [...])` calls with `@foreach ($items as $item)` over real data, deriving `cardPath` from `$item->watchable_type` (Movie → `movie_detail`, Show → `series_detail`).
  3. Add `auth` middleware to the route.
- **Non-breaking:** no schema change, no existing data touched.

### 1.2 Add watchlist add/remove AJAX endpoints (so the existing "+" buttons on cards work)

- **Where it's broken:** Every `card-style` include renders an add-to-wishlist `<a>` pointing to `route('frontend.watchlist_detail')` (i.e. it navigates to the page, never posts). Not a single card actually stores anything.
- **What already exists:** `WatchlistItem::addFor()` is ready; the continue-watching remove endpoint at `FrontendController::removeFromContinueWatching` is a perfect structural template.
- **Wire-up:**
  1. Add `POST /api/v1/watchlist/{type}/{id}` (toggle) and `DELETE /api/v1/watchlist/{type}/{id}`, mirroring the continue-watching endpoint style.
  2. Controller: accept `type ∈ {movie, show}`, resolve model class, call `WatchlistItem::addFor()` or delete matching row; return JSON `{ok, inList}`.
  3. Convert the wishlist button in [card-style.blade.php:53-59](../Modules/Frontend/resources/views/components/cards/card-style.blade.php#L53-L59) and [:122-128](../Modules/Frontend/resources/views/components/cards/card-style.blade.php#L122-L128) to a button-with-data-attrs (`data-type`, `data-id`); small shared JS toggles via `fetch`.
  4. When user is unauthenticated, intercept with a redirect to login.
- **Non-breaking:** introduces new routes + one JS file; existing card markup keeps working visually until users click the button.

### 1.3 Bind `/your-profile` form to the authenticated user

- **Where it's broken:** [your-profile.blade.php](../Modules/Frontend/resources/views/Pages/Profile/your-profile.blade.php) hardcodes `Marvin McKinney`, `marvin@demo.com`, `user_id="12"`. Form `action=""` posts nowhere. Controller at [FrontendController.php:939](../Modules/Frontend/app/Http/Controllers/FrontendController.php#L939) returns the view with no variables.
- **Wire-up:**
  1. Controller: pass `auth()->user()`.
  2. View: replace each hardcoded `value="…"` with `value="{{ old(field, $user->field) }}"`.
  3. Add a POST route + `updateProfile(Request $request)` handler validating `first_name`, `last_name`, `email`, `phone`; `auth()->user()->update(...)`.
- **Non-breaking:** existing profile form HTML stays; only the bindings change.

### 1.4 Replace hardcoded `/view-all-tags` list with real `Tag` records

- **Where it's broken:** [view-all-tags.blade.php](../Modules/Frontend/resources/views/Pages/view-all-tags.blade.php) lists hardcoded tag names. Controller at [FrontendController.php:821](../Modules/Frontend/app/Http/Controllers/FrontendController.php#L821) returns the view with no data.
- **Wire-up:** `$tags = Tag::withCount(['movies', 'shows'])->orderBy('name')->get();` → `@foreach` in the view. Each card links to `route('frontend.tag', $tag->slug)` (which is already wired).

### 1.5 Fix genre thumbnails on `/all-genres` and `/geners`

- **Where it's broken:** Template uses placeholder `https://picsum.photos/seed/...` URLs.
- **Wire-up:** Add a computed attribute to `Genre` (e.g. `featured_image_url`) that falls back to the backdrop of the most recent published movie/show in that genre:
  ```php
  public function getFeaturedImageUrlAttribute(): ?string {
      return $this->movies()->published()->latest('published_at')->value('backdrop_path')
          ?? $this->shows()->published()->latest('published_at')->value('backdrop_path');
  }
  ```
  Swap the `picsum` reference in the view for `$genre->featured_image_url ?: 'frontend/images/...fallback'`.
- **Non-breaking:** pure accessor; no DB change.

### 1.6 Wire `/playlist` profile dashboard to the auth user

- **Where it's broken:** [playlist.blade.php](../Modules/Frontend/resources/views/Pages/playlist.blade.php) hardcodes `admin@admin.com` + static cards. Controller at [FrontendController.php:851](../Modules/Frontend/app/Http/Controllers/FrontendController.php#L851) returns empty view.
- **Wire-up:** pass `auth()->user()`, plus the user's watchlist items and watch-history (repurpose this as a "My Library" page until a real Playlist model is designed). See §2.3 for the longer-term note on playlists.

### 1.7 Remove/repurpose dead template pages

- Pages that only render template HTML with no real feature behind them: [view-all.blade.php](../Modules/Frontend/resources/views/Pages/view-all.blade.php), [view-more.blade.php](../Modules/Frontend/resources/views/Pages/view-more.blade.php), [archive-playlist.blade.php](../Modules/Frontend/resources/views/Pages/archive-playlist.blade.php), [profile-marvin.blade.php](../Modules/Frontend/resources/views/Pages/profile-marvin.blade.php).
- **Recommendation:** either unlink from navigation (search the header/footer partials for `href`s pointing to those routes and drop the links), or repurpose `/view-all` into a real "All movies" paginated grid backed by `Movie::published()->paginate(30)` — the template already renders a card grid.
- **Non-breaking:** leaving the routes in place while de-linking is the lowest risk — anything still hitting the URL renders the existing template, but no user reaches it by clicking.

---

## 2. Structural gaps — design work, then wire

### 2.1 Reviews & ratings on movie / episode detail

- `Review` and `Rating` models exist but no UI reads or writes them.
- **Proposed stitch:**
  1. Add a collapsible "Write a review" form + list of existing reviews below the cast section on `Pages/Movies/detail-page.blade.php` and `Pages/TvShows/detail-page.blade.php`.
  2. Controller: on detail load, eager-load `$movie->reviews()->with('user')->latest()->take(10)` and `$movie->ratings()->avg('score')` (replacing the hardcoded 2.75 rating).
  3. Add POST routes for `reviews.store` and `ratings.store`, auth-gated, validated.
- **Non-breaking:** the current detail page structure is untouched above the new section.

### 2.2 Comments on episodes

- `Comment` model exists. Episode watch page already has space below the video.
- **Proposed stitch:** mirror the review UI, scoped to Episode polymorphic relation. Defer until 2.1 lands so the comment/review UI can share a partial.

### 2.3 Playlists — decide before building

- No `Playlist` or `PlaylistItem` model exists. Template pages (`playlist`, `playlist-detail`, `archive-playlist`) are pure UI.
- **Recommendation:** do **not** build a user-playlist feature yet. Repurpose the `/playlist` page as "My Library" (watchlist + continue-watching) for now (see §1.6). Mark `playlist-detail` and `archive-playlist` for removal or demo-only use. Revisit when product clarifies whether playlists are user-curated, admin-curated, or auto-generated.
- **Non-breaking:** no model created, no route removed; just a repurposed view.

### 2.4 Membership pages wired to `UserSubscription` + `PaymentOrder`

- Pages in [Modules/Frontend/resources/views/Pages/Profile/](../Modules/Frontend/resources/views/Pages/Profile/): `membership-account`, `membership-invoice`, `membership-orders`, `membership-level`, `membership-comfirmation` all render template placeholders.
- **Proposed stitch per page:**
  - `membership-account`: current tier, next renewal date, change-plan link → from `auth()->user()->activeSubscription()`.
  - `membership-invoice`, `membership-orders`: list `PaymentOrder::where('user_id', auth()->id())->latest()->paginate(10)`.
  - `membership-level`: list all `SubscriptionTier`s with the user's current tier highlighted (`/pricing` already does this — consider collapsing the two pages).
  - `membership-comfirmation`: render the most-recent successful `PaymentOrder` of the authenticated user (for the post-checkout redirect).
- **Non-breaking:** routes and templates stay; only controllers + bindings change.

### 2.5 Category browse route

- `Category` model exists but no `/categories` listing or `/categories/{slug}` detail page is wired.
- **Proposed stitch:** mirror the Genre routes/controller exactly — `Pages/category-page.blade.php` can reuse the same layout as `geners-page.blade.php`.
- **Non-breaking:** additive routes + one new view + one controller method.

### 2.6 Notification center

- No `Notification` model, no UI. Laravel ships with a notifications table — use it rather than inventing one.
- **Proposed stitch:**
  1. `php artisan notifications:table` migration.
  2. Add a bell icon in [header-default.blade.php](../Modules/Frontend/resources/views/components/partials/header-default.blade.php) with unread count from `auth()->user()->unreadNotifications->count()`.
  3. Route `/notifications` + page listing `auth()->user()->notifications()->paginate(20)`.
  4. Send a Notification on `PaymentReceived` (already emitted — Phase 6b).
- **Non-breaking:** self-contained; no existing features touched.

### 2.7 Language switcher

- 39 language files exist under [lang/](../lang/). No UI exposes them.
- **Proposed stitch:**
  1. Dropdown in header; options pulled from `array_keys(config('app.supported_locales'))` (add a config entry).
  2. `GET /locale/{locale}` controller stores the locale in session; middleware sets `App::setLocale()` on subsequent requests.
- **Non-breaking:** default locale continues unchanged for users who never open the dropdown.

---

## 3. Cleanup & hygiene

### 3.1 Dead links in the `card-style` "add to watchlist" button

Currently every card renders `href="{{ route('frontend.watchlist_detail') }}"` on the "+" icon — misleading, since clicking navigates away instead of saving. Even before §1.2 lands, change the `href` to `javascript:void(0)` and add a tooltip "Sign in to save" to remove the broken affordance.

### 3.2 Stop using `href="view-all"` and `href="#"` placeholders

Grep for `href="view-all"`, `href="#"`, and `href="javascript:void(0)"` inside `Modules/Frontend/resources/views/components/cards/*.blade.php` and the template landing pages — each is a navigation dead end. Pick one of:

- Wire to a real route (preferred for cast filmography counts, genre chips, etc.).
- Remove the link entirely (leave the label as plain text).

Don't leave hash anchors — they train users that click does nothing.

### 3.3 Uncommitted work — land what's done before starting more

Current `git status` shows ~48 modified files and new VJ/player work. Before taking on the items in §1, commit the working state so the "missing pieces" are isolated from in-flight changes. User memory already captures this rule ("Push after big changes").

---

## 4. Suggested execution order

| Order | Item | Why first |
|---|---|---|
| 1 | §3.3 commit + push current work | Isolates new work, matches project preference |
| 2 | §1.3 profile form binding | 10 min, stops showing "Marvin McKinney" to real users |
| 3 | §1.1 + §1.2 watchlist (view + add/remove) | Unlocks a whole class of cards' wishlist buttons |
| 4 | §1.4, §1.5, §1.6 tag/genre/library wiring | All < 20 min, all model data already queryable |
| 5 | §3.1 + §3.2 dead-link cleanup | Low effort, removes the worst "dummy UI" impression |
| 6 | §2.4 membership pages | User-visible subscription truth |
| 7 | §2.1 + §2.2 reviews/ratings/comments | Requires UI design work, bundle them |
| 8 | §2.5 categories, §2.7 language switcher | Completeness |
| 9 | §2.6 notifications | Larger; do last once the above land |
| — | §2.3 playlists | **Defer** pending product decision |

---

## 5. Non-goals / what to explicitly NOT do

- Don't redesign the card grid. Every page uses `components/cards/card-style.blade.php` — changes there ripple across the site.
- Don't rename existing routes. `/tv-show-detail/{slug}` already 301-redirects to `/series/{slug}`; adding more redirects compounds the maintenance cost.
- Don't migrate to a new CSS framework. The Streamit template is set in SCSS and documented in [docs/ui-guidelines.md](ui-guidelines.md); any redesign lives in a separate effort.
- Don't create new models when a Laravel built-in exists (notifications: use `notifications` table; watchlist: already a `WatchlistItem` model — reuse it).
