# Jambo — Frontend ↔ Admin Wiring Plan

This doc captures the full plan for connecting the Jambo admin panel to
the public-facing Streamit frontend, with real database queries
replacing the template's hardcoded data, and the notification system
(system / email / push) layered on top. It is a living plan — update as
slices ship.

Status legend:

- `shipped` — in main, smoke-tested
- `in progress` — currently being built
- `next` — queued for the next working session
- `deferred` — planned but explicitly postponed
- `todo` — in the backlog, no session assigned

---

## Where we are today

| Layer | State |
|---|---|
| Admin routes backed by real queries | `/admin/movies`, `/admin/payments`, `/admin/updates` only |
| Static admin routes (all 70+ `dashboard.*` group) | Still hardcoded template Blade — no Eloquent queries yet |
| Frontend public routes | All 50+ `FrontendController` methods return static views — zero DB queries |
| Domain schema | **Phase 1 complete** — movies, shows, seasons, episodes, genres, categories, tags, persons, ratings, reviews, comments, subscription_tiers, user_subscriptions, watchlist_items, watch_history all exist and are seeded |
| Payments | Fully functional gateway layer. `payment.completed` event fires on successful charge but **nothing listens yet** |
| Notifications | Not built. User model already has the `Notifiable` trait; admin header has a placeholder bell icon ready to wire |
| Subscription billing period | Column is a plain string; seeder only writes `'monthly'`. User wants daily + weekly + monthly + yearly |
| Shop | Removed entirely in commit `3c867c4` |

---

## Phase 2 — Admin CRUD (in progress)

Replace every static `DashboardController` route in the `dashboard.*` group with a real resource controller in the matching module, feeding the existing Streamit Blade views with live data.

### Slices (in suggested order)

| # | Slice | Shipped? |
|---|---|---|
| 2a | **Movies** — full resource under `Modules/Content/app/Http/Controllers/Admin/MovieController.php`, index/create/store/edit/update/destroy, search + status filter + pagination, dynamic cast repeater | ✅ commit `ecd85d4` |
| 2b | **Shows + Seasons + Episodes** — nested editor. ShowController + SeasonController + EpisodeController, with seasons managed inline on the show edit page and episodes managed inline on the season edit page | next |
| 2c | **Persons** — flat CRUD; single page list + add/edit form | next |
| 2d | **Genres, Categories, Tags** — three lookup-table CRUDs sharing a pattern; smallest slice | next |
| 2e | **Ratings, Reviews, Comments** — moderation UI: bulk-approve, bulk-delete, filter by target (movie/episode) | next |
| 2f | **Dashboard home** — replace `DashboardController@index` with a real dashboard showing counts (movies / users / subscribers / active subscriptions / recent payment orders) | next |

### Pattern every slice follows

Established by slice 2a. Every entity gets:

1. A resource controller under `Modules/<Module>/app/Http/Controllers/Admin/` with DB-transaction-wrapped writes and unique-slug generation
2. `StoreXRequest` + `UpdateXRequest` form request classes
3. A `Route::resource(...)` line in the module's `routes/web.php` inside the existing `web + auth + role:admin` group
4. Views under `Modules/<Module>/resources/views/admin/<entity>/` — `index.blade.php`, `create.blade.php`, `edit.blade.php`, `form.blade.php` (shared partial), any sub-partials
5. A conditional sidebar link (`@if (Route::has('admin.x.index'))`) in [resources/views/components/partials/vertical-nav.blade.php](../../resources/views/components/partials/vertical-nav.blade.php) so disabling the module hides the nav automatically

---

## Phase 3 — Public frontend wiring

Every method in [Modules/Frontend/app/Http/Controllers/FrontendController.php](../../Modules/Frontend/app/Http/Controllers/FrontendController.php) that currently returns a static view gets replaced with real queries against the Phase 1 schema.

### Slices

| # | Slice | Notes |
|---|---|---|
| 3a | **Home** — `/home` lists trending movies, new releases, featured genres, top picks. Wire `FrontendController@index` to `Movie::published()->latest()->take(N)->get()`. | next |
| 3b | **Movie list** — `/movie` paginated + filterable by genre/year. | |
| 3c | **Movie detail** — `/movie-detail/{slug}` via route-model binding. Show title, synopsis, poster, trailer, cast, related-by-genre. Log a view to `watch_history` on play (later, once the player is wired). | |
| 3d | **Show / Season / Episode** — three nested detail pages. | |
| 3e | **Person detail** — `/person-detail/{slug}` with filmography via `movie_person` + `show_person` pivots. | |
| 3f | **Genre / Category / Tag listing pages** — one view per taxonomy. | |
| 3g | **User profile** — `/your-profile` backed by the logged-in user's subscriptions, watchlist, and watch history. | |
| 3h | **Watchlist + continue watching** — `/watchlist-detail` reads from `watchlist_items` and `watch_history`. | |

### Pattern every slice follows

- Keep the Streamit Blade templates. They're fully styled in the Prime Video theme.
- Replace the template's hardcoded `@include('components.datatable.DataTable', [...])`-style calls with `@foreach ($movies as $movie) ... @endforeach` and Blade binding.
- Where the template uses a specific image filename (e.g. `{{ asset('frontend/images/movie/01.webp') }}`), fall back to the model's `poster_url` field with a "?" tile when missing.
- No new CSS. If markup needs a new class, use an existing Bootstrap utility.

---

## Phase 4 — Subscriptions + Payments wiring

### 4a. Billing period expansion (this session)

The `subscription_tiers.billing_period` column is currently a plain string with seeded value `'monthly'`. The user wants **daily, weekly, monthly, yearly**. Because the column is already a string, no schema migration is needed — we just need to:

1. Add constants to [SubscriptionTier](../../Modules/Subscriptions/app/Models/SubscriptionTier.php): `PERIOD_DAILY`, `PERIOD_WEEKLY`, `PERIOD_MONTHLY`, `PERIOD_YEARLY`
2. Add a helper `durationInDays(int $count = 1): int` that returns the right day count for the current period
3. Update the seeder to create sample tiers in each period so admins see the full range
4. Add an `interval_count` column (optional) later if "every 3 weeks" semantics become necessary

### 4b. Tier management admin CRUD

- `/admin/subscription-tiers` — list, create, edit, archive
- Form includes: name, slug, price, currency, `billing_period` select (Daily/Weekly/Monthly/Yearly), `access_level` picker, features list, sort order, active toggle
- On save, validates that the period is one of the four enum values

### 4c. Public pricing page

- `/pricing` wired to `SubscriptionTier::active()->ordered()->get()`
- Each card shows features JSON as a bulleted list
- "Subscribe" button POSTs to `/payment/create-order` with the tier id

### 4d. Payment → subscription activation listener

Currently the Payments module fires `payment.completed` into the void. Register a listener:

```
Modules\Subscriptions\app\Listeners\ActivateSubscriptionFromPayment
```

On each `payment.completed`, if `payable_type` is `SubscriptionTier`, the listener creates (or extends) a `UserSubscription` with:

- `starts_at = now()`
- `ends_at = now()->addDays($tier->durationInDays())`
- `status = active`
- `payment_order_id = $order->id`

And expires any previous active subscription for the same user+tier.

### 4e. User subscription lifecycle

- Background job / scheduled command runs daily, flips `status` from `active` to `expired` for any `UserSubscription` where `ends_at < now()`
- Fires a `subscription.expired` event so notifications can pick it up

### 4f. "My subscription" user page

- `/my-subscription` shows current active subscription, next renewal date, cancel button
- Cancel flips `status` to `cancelled`, sets `cancelled_at`, keeps `ends_at` so access continues to the end of the paid period

---

## Phase 5 — Streaming

| # | Slice | Notes |
|---|---|---|
| 5a | **Dropbox proxy controller** — `/stream/{episode_or_movie}` returns either a signed Dropbox short link or a proxied ranged passthrough | depends on Dropbox API integration |
| 5b | **TierGate middleware** — checks the user's current subscription access_level vs the movie/episode `tier_required` | |
| 5c | **Watch history heartbeat** — `POST /api/watch-history` from the player every 15 s, upserts a row in `watch_history_items` | |
| 5d | **Continue watching rail** — reads `watch_history_items` where `completed = false` ordered by `watched_at` desc | |

---

## Phase 6 — Notifications (this session + next)

See [docs/modules/notifications.md](../modules/notifications.md) for the full design. Summary:

| # | Slice | Status |
|---|---|---|
| 6a | **System (in-app) channel** — Laravel `DatabaseChannel`, `notifications` table, admin header bell dropdown, `/notifications` index page, mark-as-read endpoints, one example notification class | this session |
| 6b | **Event listeners** — `payment.completed` → `PaymentReceivedNotification` dispatched to the paying user and every admin | this session |
| 6c | **Email channel** — add `MailChannel` to existing notification classes via `via()`. No new classes. | next session |
| 6d | **Broadcast channel** — add `BroadcastChannel` + Laravel Echo front end so unread count updates in real time without refresh | deferred |
| 6e | **Push channel** — `laravel-notification-channels/webpush`, VAPID keys in `.env`, service worker at `public/sw.js`, subscription UI in user settings | deferred |
| 6f | **Admin broadcast** — form at `/admin/notifications/broadcast` lets an admin write a message with title/body/target audience/channels and fan it out | deferred |
| 6g | **Scheduled reminders** — "subscription expires in 3 days" via `php artisan schedule:work` + a `SendExpiryReminders` command | deferred |

---

## Phase 0 followups (technical debt)

Small things from earlier sessions that haven't been cleaned up. Each one is a half-hour slice and can be grabbed opportunistically.

- Remove stale `shop`/`cart_page`/`wishlist_page`/etc. keys from `lang/*/frontendheader.php` (6 locales) — left in place when the shop was removed because the keys are unreferenced but touching 6 files for zero functional gain wasn't worth it then
- Stub or delete the missing `ProfileController` import in [routes/web.php](../../routes/web.php) — currently causes `php artisan route:list` to crash with a `ReflectionException`
- Stub or delete `BackendController` references to non-existent `Modules\Booking\Models\Booking` and `Modules\Product\Models\Order`
- Seed `settings.app_name = 'Jambo'` so the footer's `setting('app_name')` call returns the right value instead of the `?: 'Jambo'` fallback
- Wire the [blog-detail.blade.php](../../Modules/Frontend/resources/views/components/widgets/blog-detail.blade.php) "Edit profile" link to a real user profile route (currently `#`)

---

## Working rhythm

Each slice above is roughly one session. A session follows this shape:

1. Ensure the plan above is still accurate; update any row that has changed
2. Add concrete todos for the slice
3. Build the backend first (migration if needed → model → service/controller → routes)
4. Wire the UI (Blade views, form partials, sidebar link)
5. Smoke test the new feature + every URL that used to work (`/`, `/login`, `/app`, `/admin/movies`, etc.)
6. Commit with a descriptive body; push to main
7. Cross the slice off this plan in the next session

No slice is allowed to break an existing URL. No slice is allowed to add a new dependency without noting it in the commit message.
