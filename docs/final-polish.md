# Jambo — Session Log & Active Rules

Working log of what's shipped and the design rules established along
the way. A new chat should read this first before auditing other areas.

**Last updated:** 2026-04-19
**Branch / tip:** `main` / `b0bf07f`

---

## Shipped in this session run (newest first)

### Guest browsing end-to-end — `b0bf07f`
Full public browsing for marketing. Guests can visit every page and
play every free (`tier_required = null`) title without signing in.
Auth only kicks in on premium playback or user-bound actions.

- `TierGate` middleware: guest on premium content → `redirect()->guest(route('login'))` (so intended() bounces them back after sign-in); JSON still 401s.
- Frontend `/watch/{slug}` and `/episode/{slug}` lost their `auth` middleware. Controller methods add a guest branch (go to login) when tier check fails.
- Streaming routes split: `/streams/limit` and `/api/v1/streaming/heartbeat` stay auth-only; `/player/*`, `/stream/*`, `/stream/proxy/*` are tier-gate-only so free bytes flow to guests.
- Player blades (`watch.blade.php`, `Pages/Movies/watch-page.blade.php`, `Pages/TvShows/episode-page.blade.php`) gate heartbeat setup behind `isAuthed` — no 401 spam for guests.

### Mobile bottom nav — `4fd56c3`
- Watchlist button points directly at `profile.watchlist` for authed users (no redirect hop), to `/login` for guests.
- Group-based active state: Home lights up on genre/category/tag/search pages; Movies on detail/watch/VJ pages; Series on detail/episode/VJ-series; Watchlist on legacy route + profile tab.

### Profile hub sidebar order — `19f602d`
Watchlist pinned to the top of the tab list.

### Concurrent-stream limit — `0012e27`
Phase B of device management. Premium content is capped per tier; free content is always unlimited.

- `subscription_tiers.max_concurrent_streams` (nullable int; NULL = unlimited). Seeded: day-pass 1, basic 2, premium 4, free NULL.
- `watch_history.session_id` + `last_beat_at`, indexed `(user_id, last_beat_at)`. 90-second idle window counts as "active".
- `WatchHistoryItem::activeStreamCount($userId, $excludeSessionId)` counts distinct live sessions on tier-gated content.
- `TierGate` enforces the cap after the tier check; admins bypass; free content skipped. Blocked users land on `/streams/limit`.
- FrontendController `movie_watch`, `episode`, `watchlistPlay` all call `concurrencyExceeded()` before rendering the player.
- Admin tier form gains a "Max concurrent streams" field.

### Devices tab — `804dcc7`
Phase A of device management. Session driver moved file→database; sessions migration added (everyone had to re-login once).

- `ProfileHubController::devices()` lists every active session with a tiny UA parser (Chrome/Firefox/Safari/Edge/Opera × Windows/macOS/iOS/Android/Linux — iOS check runs before macOS because iPhone UAs contain "like Mac OS X").
- Routes: `GET /{username}/devices`, `DELETE /{username}/devices/{session_id}`, `POST /{username}/devices/logout-others`.
- View styled to match the notifications inbox: hub-card, coloured icon tile, "This device" badge on current row, danger-subtle Sign out button per row.

### Notifications system — `ad5893d`, `07bf088`, `3bdeb47`, `53c50e3`, `9cd79b7`, `9dd7ea5`, `516b237`, `e389813`, `9e4ff40`, `d6c522f`
Three channels + user preferences + admin SMTP + follow-up polish.

- **Profile hub Notifications tab** — inbox (mark-read, delete, open action_url, empty state) + delivery preferences (in-app/email/push toggles). Unread badge on sidebar tab.
- **Admin SMTP settings** at `/admin/settings` — host/port/user/password/encryption/from-address/from-name. Password Crypt-encrypted; empty password field = keep existing. `AppServiceProvider::boot` overrides `config('mail.*')` at runtime from the settings table. Send-test button catches transport errors.
- **Push notifications** — `laravel-notification-channels/webpush` installed, VAPID keys generated (note: XAMPP's default openssl.cnf lacks EC curve config, generate keys with `OPENSSL_CONF=c:/xampp/php/extras/openssl/openssl.cnf artisan webpush:vapid`), service worker at `public/sw.js`, `POST /notifications/push/{subscribe,unsubscribe}` endpoints, `PaymentReceivedNotification` gets `toWebPush()`.
- **Admin settings split** into three per-section forms (General / Branding / SMTP), each with its own Save button and flash key.
- **Bell icon for users** routes directly to profile notifications tab (not through the redirect).
- **Live unread counter** sync across header bell, sidebar tab badge, inbox card subtitle via a `syncUnread(count)` helper driven by the JSON `unread_count` field.

### Watchlist system — `271466b`
- Cards show year (new optional `cardYear` slot on `card-style.blade.php`), runtime cleanup, "N seasons" for shows (eager-loaded).
- Movie click → `/watchlist/{slug}` (watchlist-play-page with prev/next queue). Show click → new `/watchlist/series/{slug}` resolver (resume-or-first episode, then `/episode/{id}`).
- Toggle button starts in "in-list" (ph-check) state on this page; empty states have proper CTAs.

### Profile hub tabs rendering — `fe779c6`
One-line fix: every tab view used `@include('profile-hub._layout')` + `@section('hub-content')` which runs the layout before the section exists. Switched to `@extends`.

### Admin/user separation — `ee03791`
Strict split: different layouts, different access, different landing pages.

- Login, registration, email verification, password confirm, 2FA challenge all branch on role (admin → `/app`, user → `/`).
- `/app`, `dashboard.*` group, `backend.*` group all require `role:admin` (were auth-only).
- `ProfileHubController::resolveOwn` bounces admins to `/app` before any `/{username}` tab renders.
- Frontend header hides the Subscribe badge for admins and shows "Admin Dashboard" shortcut instead of the user dropdown.

### Orphan/stub cleanup + i18n/RTL removal — `9c8c192`
Bundled checkpoint. Deleted: `/download` (no-downloads policy), `/movie-player`, `/view-more`, `/resticted`, `/person-detail`, `/profile-marvin`, plus orphan widgets and `BlogPaginationStyle/`. Also dropped all non-en lang dirs, RTL SCSS, `SetLocale` middleware, `LanguageController`.

---

## Active design rules (for the next Claude session)

### Admin vs user separation
Admin and user sides are strictly separate — different layouts, different access, different chrome. When a single route serves both, branch on `$user->hasRole('admin')` and render two distinct views. **Never** render `layouts.app` (admin) for regular users; **never** render `master.blade.php` / profile-hub chrome for admins. Admin-only routes always use `middleware(['auth', 'role:admin'])`.

### Guest browsing
Guests can browse the entire app and play any title where `tier_required = null`. Auth only gates:
- User-bound actions (watchlist, reviews, comments, watch history)
- Playback of premium content (tier_required != null)
- Profile hub, notifications, devices, billing

When a guest hits a premium gate, redirect via `redirect()->guest(route('login'))` so `intended()` returns them to the same URL after sign-in.

### Free vs premium content
`tier_required` is the single source of truth:
- `null` → free, open to guests, no concurrent-stream limit
- any tier slug → premium, requires a subscription with sufficient `access_level`, counts against the tier's `max_concurrent_streams`

### URLs on subdirectory installs
The app runs under `/Jambo/` locally. **Never** hardcode `/path` in client JS — browsers resolve those against the origin root. Always generate URLs via `url()`, `route()`, or `asset()` so the subdirectory is included. This has bitten:
- Service worker registration
- Notification API calls
- Heartbeat URLs

### Admin settings split by card
Each card in admin settings has its own form + Save button + flash key. One card's validation errors must never block saving another.

### UI guidelines (from [docs/ui-guidelines.md](ui-guidelines.md))
- Visible row actions → `btn-*-subtle` variants (not `btn-ghost`; ghost is reserved for back/cancel).
- No `mb-*` on `.nav-item`; template CSS handles spacing.
- Phosphor icons, Streamit design system, Bootstrap utility classes only — no new CSS.
- Card patterns: `jambo-hub-card` for profile-side panels.

### Watchlist semantics
Watchlist IS the library. There is no separate Playlist model. Movies click to `/watchlist/{slug}` (queue player), shows click to `/watchlist/series/{slug}` (resolves to resume-or-first episode).

---

## Known gaps / candidates for the next audit

### Player / streaming
- [ ] **Resume position for guests on free content** — we skip `WatchHistoryItem` writes for guests entirely. If we ever want "resume where you left off" for signed-out visitors, we'd need localStorage-based tracking.
- [ ] **Guest banner on free content player** — a dismissible "Sign in to save your progress and unlock premium titles" strip would be useful marketing.
- [ ] **Player heartbeats inside the watchlist queue** (`watchlist-play-page.blade.php`) are already auth-only (the route requires auth), but the in-page fullscreen swap fetches `/api/v1/watchlist/{slug}/player-data` which is also auth-only — verify the queue flow cleanly after our session/heartbeat changes.

### Notifications
- [ ] **Subscription-activated / subscription-expired notifications** — events fire, no listener yet. Same shape as `PaymentReceivedNotification`.
- [ ] **Admin broadcast form** (`/admin/notifications/broadcast`) — write-to-all flow.
- [ ] **VAPID_SUBJECT seeded** via .env but `config/webpush.php` reads it — confirm the default fallback is sensible for production.

### Device management
- [ ] **Admin account surface** — admins are bounced out of `/{username}/security`, so they have no self-service password/2FA UI. Build `/admin/account` for this.
- [ ] **Automatic stream-session cleanup** — `watch_history.last_beat_at` rows never expire; a scheduled `php artisan streams:cleanup-stale` would tidy rows past the 90s idle window.

### Auth
- [ ] **Guest checkout for the Day Pass tier** — could let guests buy a one-day pass without creating an account (email-only purchase, magic-link access).
- [ ] **Password confirmation for destructive account actions** (delete account, logout-everywhere). Currently none.

### Catalog / UI
- [ ] **Free badge on cards** — distinguish free content visually (counterpart to the existing premium `ph-crown`).
- [ ] **Continue-watching rail for guests** — currently empty; could show "Trending" or "Popular free titles".
- [ ] **Error pages** (`/error-page1`, `/error-page2`) — still just routable URLs, not wired to Laravel's exception handler.

### Phase 0 debt (from the original wiring plan)
- [ ] Remove stale `shop` / `cart_page` / `wishlist_page` keys from `lang/en/frontendheader.php`.
- [ ] Audit `BackendController` for stale references to non-existent `Modules\Booking` / `Modules\Product`.
- [ ] Seed `settings.app_name = 'Jambo'` explicitly.

### Doc refresh (still outdated)
- [ ] `docs/frontend-guide.md` still lists 21 blog pages, `/download`, `/movie-player`, `/profile-marvin`.
- [ ] `docs/admin-panel-guide.md` still flags unified sidebar entries as duplicates.
- [ ] `docs/modules.md` calls Content/Subscriptions/Payments/Streaming "empty skeleton" — all shipped.
- [ ] `docs/SESSION-RESUME.md` lists Phase 4b/4c/4d as "next" — all shipped.

---

## Non-goals — do NOT do these

- **Don't reintroduce `/download`.** Product decision: no local downloads.
- **Don't reintroduce playlists or the language switcher.** Removed deliberately; re-adding means reviving middleware, RTL SCSS, model etc.
- **Don't touch the card/section component library.** Every page uses `components/cards/card-style.blade.php` and 20+ section blades — changes there ripple. Fix data, not markup. The `cardYear` slot was an *additive* change (non-breaking); follow that pattern.
- **Don't add new CSS.** Streamit design system is set; use existing utility classes.
- **Don't reuse `btn-ghost` for visible actions.** Ghost = back/cancel only.
- **Don't hardcode `/foo` URLs in client JS.** Always go through `url()` / `route()`.
- **Don't render admin chrome for users or user chrome for admins.** See the admin/user separation rule.
