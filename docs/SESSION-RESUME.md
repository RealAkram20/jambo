# Jambo — Session Resume Context

**Last session:** 2026-04-15
**Last commit:** Phase 4d — subscriptions:expire command scheduled hourly
**Branch:** `main` — pushed to https://github.com/RealAkram20/jambo
**Working tree:** clean

---

## What's been built (commit history, newest first)

```
(pending)  Phase 3c: Preserve full template home, wire every section to real data via SectionDataComposer
a34cb83  Phase 3b: OTT + episode + genres/tags + cast detail/list wired to real data
b465088  Phase 3a: Public frontend wired — home, movies, tv-show, movie-detail/{slug}, tv-show-detail/{slug}
2e9978e  Phase 2f: Dashboard home wired to real data; removed redundant second dashboard
63e66fb  Phase 2e: Reviews, Ratings, Comments moderation UI
e33e96c  Phase 2d: Wire template pages to real data, add taxonomy CRUD, remove blog
2eacf45  Phase 2c: Persons admin CRUD
eacd5ea  Phase 2b: Shows + Seasons + Episodes admin CRUD
53f445a  Phase 6a + planning: Notifications module (system channel live)
3c867c4  Remove the shop / merchandise feature
ecd85d4  Phase 2a: Movies admin CRUD end-to-end
f6280d3  Phase 1: Content, Subscriptions, Streaming domain schemas
a3cdeca  Build Payments module: PesaPal gateway + admin UI
5c4a69a  Build SystemUpdate module: admin-gated in-app updater
c95b80d  Build Installer module: 8-step web setup wizard
3ccacd7  Add docs for installer, system-update, and PesaPal integration
9647f90  Scaffold six feature modules for upcoming phases
6f0c433  Initial Jambo prototype on Streamit Laravel template
```

---

## Modules (8 enabled, in modules_statuses.json)

| Module | State | Key files |
|---|---|---|
| **Frontend** | Template shell — 50+ public routes, all static Blade, zero DB queries | `Modules/Frontend/` |
| **Content** | Phase 1 schema + Phase 2 admin CRUD for **Movies**, **Shows/Seasons/Episodes**, **Persons**, **Genres/Categories/Tags** done. **Reviews/Ratings/Comments** moderation UI live (Phase 2e). | `Modules/Content/` |
| **Subscriptions** | Schema done. 7 tiers seeded (daily/weekly/monthly/yearly). No admin CRUD, no activation listener yet. | `Modules/Subscriptions/` |
| **Payments** | Fully built. PesapalGateway, PaymentOrder model, PaymentController (create/callback/ipn/complete), admin settings page. `payment.completed` event fires. | `Modules/Payments/` |
| **Streaming** | Schema only (watchlist_items, watch_history). No controllers/views. | `Modules/Streaming/` |
| **Installer** | Fully built. 8-step wizard, EnsureInstalled middleware in Kernel, `storage/installed` flag, `jambo:reset-install` CLI. | `Modules/Installer/` |
| **SystemUpdate** | Fully built. UpdateManager, ZipExtractor, admin page at `/admin/updates`, `version.txt` at root. | `Modules/SystemUpdate/` |
| **Notifications** | System (in-app) channel done. `notifications` table, bell dropdown in admin header (polls every 60s), `/notifications` index page, `payment.completed` listener. Email + push channels NOT yet done. | `Modules/Notifications/` |

---

## Database state

**Tables that exist (30+):**

Core Laravel: `users`, `password_reset_tokens`, `failed_jobs`, `personal_access_tokens`, `migrations`, `media`, `settings`, `permissions`, `roles`, `role_has_permissions`, `model_has_roles`, `model_has_permissions`

Content: `categories`, `genres`, `tags`, `persons`, `movies`, `shows`, `seasons`, `episodes`, `category_movie`, `category_show`, `genre_movie`, `genre_show`, `movie_tag`, `show_tag`, `movie_person`, `show_person`, `ratings`, `reviews`, `comments`

Payments: `payment_orders`

Subscriptions: `subscription_tiers` (7 rows seeded), `user_subscriptions`

Streaming: `watchlist_items`, `watch_history`

Notifications: `notifications` (Laravel UUID-keyed), plus 3 boolean columns on `users` (`in_app_notifications_enabled`, `email_notifications_enabled`, `push_notifications_enabled`)

**Seeded data:**
- 1 admin user: `admin@demo.com` / `12345678` (role: `admin`)
- 8 genres, 6 categories, 10 tags, 20 persons
- 20 movies with genre/category/tag/cast attachments
- 5 shows × 2 seasons × 6 episodes = 60 episodes
- 7 subscription tiers: free, day-pass, weekly-basic, basic, premium, basic-yearly, premium-yearly
- 20 ratings (10 movie + 5 show + 5 episode), 11 reviews, 12 comments
- 0 payment orders, 0 user subscriptions, 0 watchlist/history (user-generated)

---

## Admin login

- URL: `http://localhost/Jambo/login`
- Email: `admin@demo.com`
- Password: `12345678`

---

## Admin sidebar links (what the admin sees)

- Movies → `/admin/movies` (live CRUD)
- Shows → `/admin/shows` (live CRUD, nested seasons/episodes)
- Persons → `/admin/persons` (live CRUD)
- Ratings → `/rating` (live moderation, filter by type)
- Comments → `/comment` (live moderation, approve/unapprove, filter by type/status)
- Reviews → `/review` (live moderation, publish/unpublish, filter by type/status)
- Notifications → `/notifications` (system channel, bell in header)
- System Updates → `/admin/updates` (version check + updater)
- Payments → `/admin/payments` (PesaPal settings + order ledger)
- Access Control → `/permission-role` (spatie roles/permissions)

---

## What's next (in suggested order)

### Immediate next slice: Phase 5 — Streaming

Dropbox proxy controller (or equivalent storage driver), a TierGate
middleware that reads the caller's current UserSubscription and checks
`tier.access_level` against the movie/episode's required tier, and a
watch history heartbeat API. Phases 4b-4d are all shipped —
subscriptions activate on payment, renew correctly, and expire
automatically via the hourly `subscriptions:expire` schedule.

Remaining frontend polish that can come later (no rush):
- `/watchlist-detail`, `/archive-playlist` — need user auth + watchlist schema (Streaming module)
- `/playlist`, `/view-more`, `/view-all`, `/view-all-tags` — minor browse pages
- `/profile-marvin`, `/membership-*`, `/your-profile`, `/change-password` — user profile area

### Then:

| Slice | Description |
|---|---|
| **Phase 3** | Public Frontend wiring — replace FrontendController static views with Eloquent queries. Start with `/home`, `/movie`, `/movie-detail/{slug}`, `/tv-show`, `/tv-show-detail/{slug}` |
| **Phase 4b** | Tier admin CRUD at `/admin/subscription-tiers` + public pricing page at `/pricing` wired to real tiers |
| **Phase 4c** | Subscription activation listener — `Modules\Subscriptions\Listeners\ActivateSubscriptionFromPayment` listens on `payment.completed`, creates a `UserSubscription` row |
| **Phase 4d** | User subscription lifecycle — scheduled command expires subs, fires `subscription.expired` event |
| **Phase 5** | Streaming — Dropbox proxy controller, TierGate middleware, watch history heartbeat API |
| **Phase 6b** | Email notification channel — add `'mail'` to `via()` + `toMail()` on existing notification classes |
| **Phase 6c** | Push notification channel — `laravel-notification-channels/webpush`, VAPID keys, service worker |
| **Phase 0 cleanup** | Fix ProfileController stub, seed `settings.app_name`, remove stale shop lang keys |

---

## Key technical notes for the next developer (or the next Claude session)

1. **PHP:** use XAMPP's PHP 8.2 (`c:/xampp/php/php.exe`), NOT the system PHP 8.5 at `C:\tools\php85`. The locked composer dependencies require PHP ≤ 8.4. Composer invocation: `"c:/xampp/php/php.exe" "c:/ProgramData/ComposerSetup/bin/composer.phar" <cmd>`.

2. **URL routing:** the project root has a custom `.htaccess` + `index.php` that hides `/public/` from URLs dynamically. No hardcoded project folder name anywhere. Renaming the folder Just Works — only `.env`'s `APP_URL` needs updating.

3. **After every new module file:** run `composer dump-autoload` (with XAMPP PHP) so the new classes are discoverable, then `php artisan optimize:clear` so cached config/routes/views don't mask the change.

4. **Module views use namespaced references:** `view('content::admin.movies.index')`, `view('installer::steps.requirements')`, `view('notifications::index')`, etc. The namespace is registered by each module's ServiceProvider via `loadViewsFrom()`.

5. **The Installer module's EnsureInstalled middleware** is the FIRST entry in the `web` middleware group (`app/Http/Kernel.php`). It checks `storage/installed`. If you need to re-test the installer wizard: `"c:/xampp/php/php.exe" artisan jambo:reset-install`.

6. **Spatie role middleware aliases** are registered in `app/Http/Kernel.php`: `role`, `permission`, `role_or_permission`. Admin-only routes use `middleware('role:admin')`.

7. **The `payment.completed` event** is fired by `Modules/Payments/app/Http/Controllers/PaymentController.php` and listened to by `Modules/Notifications/app/Listeners/SendPaymentReceivedNotification.php`. When the Subscriptions activation listener ships, it will also listen on the same event.

8. **Subscription billing periods:** `daily`, `weekly`, `monthly`, `yearly` — constants on `SubscriptionTier` model, with `durationInDays()` helper returning 1/7/30/365.

9. **The bell dropdown in the admin header** polls `GET /notifications/dropdown` every 60 seconds and renders JSON into a Bootstrap dropdown. The partial is at `resources/views/components/partials/notifications-bell.blade.php`, included by `header.blade.php`.

10. **`ProfileController` is imported in `routes/web.php` line 3 but doesn't exist.** This crashes `php artisan route:list` but doesn't affect normal HTTP requests. Fixing it is a Phase 0 cleanup item — either create a stub or comment out the import + the 3 profile routes on lines 31-33.

---

## Docs in the project

- `docs/streamit-laravel.md` — upstream Streamit template documentation snapshot
- `docs/modules.md` — module decomposition map and "where to put what" rules
- `docs/modules/installer.md` — reusable installer pattern doc
- `docs/modules/system-update.md` — reusable updater pattern doc
- `docs/modules/pesapal-integration.md` — reusable PesaPal integration doc
- `docs/modules/notifications.md` — reusable three-channel notification pattern doc
- `docs/plans/frontend-wiring.md` — the rolling Phase 2-6 plan with slice statuses
- `jambo-setup.md` — the original local dev setup guide (from the user)

---

## How to resume

```bash
cd c:\xampp\htdocs\Jambo
git pull
"c:/xampp/php/php.exe" "c:/ProgramData/ComposerSetup/bin/composer.phar" install --no-interaction
npm install
npm run build
"c:/xampp/php/php.exe" artisan migrate
"c:/xampp/php/php.exe" artisan optimize:clear
```

Then open `http://localhost/Jambo/` in the browser. If you get a 403 on the bare URL, Apache needs DirectorySlash On (it's the default — only breaks if httpd.conf was reset). If you get redirected to `/install`, either the `storage/installed` file was lost (recreate it with any JSON content) or the Installer module's middleware is catching a fresh environment.

Tell the next Claude session: **"Read `docs/SESSION-RESUME.md` and continue from Phase 4b."**
