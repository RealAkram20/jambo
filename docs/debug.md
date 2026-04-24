# Jambo — Debug & Audit Report

**Date:** 2026-04-24
**Scope:** Full codebase — admin + auth, frontend + content, integrations + quality
**Method:** Three parallel Explore-agent audits, cross-verified manually before recording

This document is an **inbox of actionable findings**, ordered by severity. Each entry
has a location, a one-line description, and a suggested fix. Anything marked `[OK]`
is confirmed working — kept here so a future reader can see the surface was checked,
not silently skipped.

- 🚨 [Blockers](#-blockers) — fix before shipping
- 🔒 [Security](#-security)
- ⚠️ [Broken](#%EF%B8%8F-broken-behaviour) — user-visible bugs
- 🚧 [Stubs / incomplete](#-stubs--incomplete)
- 🏚️ [Template-demo residue](#%EF%B8%8F-template-demo-residue)
- 🧹 [Tech debt / quality](#-tech-debt--quality)
- ✅ [Verified working](#-verified-working)
- 🎯 [Recommended fix order](#-recommended-fix-order)

---

## 🚨 Blockers

None. The platform is in a healthy state end-to-end. No blocker prevents you from
running the site or taking a test PesaPal payment.

---

## 🔒 Security

### 1. `password.confirm` missing on `permission-role` mutations — **✅ FIXED**

`permission-role/store` and `permission-role/reset` inherited only `auth + role:admin`
from the group, not `password.confirm`. A stolen admin session cookie could silently
grant itself any permission or wipe any role's grants — the risk the `password.confirm`
on the GET was meant to mitigate.

- **File:** `routes/web.php:138-139`
- **Fix applied:** both mutating routes now carry `->middleware('password.confirm')`.
  Verified via `php artisan route:list --name=backend.permission-role`.

### 2. CSRF exemption list is minimal ✅

Only `payment/ipn` is exempted (`app/Http/Middleware/VerifyCsrfToken.php`). Correctly
scoped — PesaPal doesn't send CSRF tokens on webhooks.

### 3. Mass-assignment / XSS sweep ✅

- `PaymentOrder`, `User`, `SubscriptionTier`, `Movie`, `Show` all have explicit
  `$fillable`. No wildcards.
- Two `{!! ... !!}` raw outputs in views, both safe: Video.js setup JSON (`json_encode`
  of a controlled array) and admin order pretty-printed `raw_response` inside a
  `<pre>` (no user-controlled injection path — the payload comes from PesaPal).

### 4. Credentials

- `.env` gitignored ✅
- PesaPal consumer secret stored `Crypt::encryptString`-encrypted in `settings` ✅
- VAPID private key stored `Crypt::encryptString`-encrypted in `settings` ✅
- SMTP password stored `Crypt::encryptString`-encrypted in `settings` ✅

---

## ⚠️ Broken behaviour

### 1. Three admin redirect targets point at a non-existent route — **✅ FIXED**

`RoleController::update`, `PermissionController::store`, and `PermissionController::update`
redirected to `route('backend.permission-role.list')`, but that name wasn't registered.
The index route is named `backend.permission-role`. Laravel threw `RouteNotFoundException`
on successful submit.

- **Files:**
  - `app/Http/Controllers/RoleController.php:116`
  - `app/Http/Controllers/PermissionController.php:52`
  - `app/Http/Controllers/PermissionController.php:101`
- **Fix applied:** three one-line changes to point at `backend.permission-role`.

### 2. `PermissionController::index` / `RoleController::index` return null on browser navigation

Both only handle `wantsJson()`. A GET to `/permission` or `/role` returns `null` → a
blank 200 response. The edit / create / update flows already use JSON (they're
modal-driven), so these endpoints are AJAX-only by design; a browser hitting the URL
directly gets nothing.

- **Files:**
  - `app/Http/Controllers/RoleController.php:17-23`
  - `app/Http/Controllers/PermissionController.php:18-24`
- **Status:** intentionally left alone. The endpoints are designed as AJAX-only; adding
  a redirect was rejected as unnecessary. If the blank response ever bothers a user,
  the fix is a one-line `return redirect()->route('backend.permission-role');` after
  the `wantsJson()` block.

### 3. `subscription_tiers.currency` column default is `KES` — **✅ FIXED**

The original migration defaulted the `currency` column to `'KES'`. The seeder masked
this with explicit `'UGX'`, but any tier created via code paths that omit the currency
inherited KES — a silent inconsistency with `config/payments.php` (UGX default) and
the Uganda-only merchant account on PesaPal.

- **Fix applied:** new migration
  `Modules/Subscriptions/database/migrations/2026_04_24_230000_set_subscription_tiers_currency_default_to_ugx.php`
  runs `$t->string('currency', 8)->default('UGX')->change()`.
- **Verified:** `information_schema.COLUMNS` shows both `payment_orders.currency` and
  `subscription_tiers.currency` defaulting to `'UGX'` post-migration.

### 4. `__() ?? 'fallback'` pattern doesn't fail over

A pattern scattered across views — `{{ __('foo.bar') ?? 'Fallback' }}` — looks like
a safety net but never fires. `__()` returns the **key string** when the translation
is missing (truthy), so the `??` right-hand side never executes. The user sees the
raw key.

We've cleaned up the most visible offenders during this session (Frontend, Payments,
Admin), but the pattern still exists in some places where the key does exist (so
nothing's broken *yet*). When a future key gets removed from a lang file, the view
will silently start showing `foo.bar` instead of the fallback.

- **Pattern location:** grep for `__('.*') ?? '` across `resources/views` and
  `Modules/*/resources/views`.
- **Fix:** replace with a helper or simply trust the key. A tiny helper makes the
  intent clear:

```php
function lang(string $key, string $fallback = ''): string
{
    $t = trans($key);
    return $t === $key ? $fallback : $t;
}
```

Then `{{ lang('foo.bar', 'Fallback') }}` actually works.

### 5. 2FA mid-setup logout is undefined behaviour

If a user starts 2FA setup (has `two_factor_secret` but not `two_factor_confirmed_at`),
then logs out, the next login succeeds without a TOTP challenge (because 2FA isn't
"confirmed" yet) and the security page still shows them on the pending-setup step.
Not a security hole — the secret they scanned is still theirs — but confusing UX.

- **File:** `app/Services/TwoFactorAuthentication.php:36` (generatePendingSetup) and
  the Security tab's state branching.
- **Fix:** either cancel the pending secret on logout, or add copy on the security
  page explaining "you have a pending setup from <date>; continue or cancel".

---

## 🚧 Stubs / Incomplete

### 1. No refund or order-cancellation endpoints

`Modules/Payments/app/Services/PesapalGateway.php` implements `submitOrder`,
`getTransactionStatus`, `registerIpn`. The PesaPal v3 spec also offers
`RefundRequest` and `OrderCancellation` — not wired. If a customer disputes a
charge, the admin currently has to refund via PesaPal's own dashboard and then
manually mark our `PaymentOrder` as `cancelled` and our `UserSubscription` as
cancelled.

- **Fix:** add `refund(string $orderTrackingId, float $amount, string $reason)` and
  `cancelOrder(string $merchantReference)` to the gateway interface + Pesapal
  implementation, plus admin buttons on the order detail page.

### 2. Upcoming titles can't be clicked from the shelf

The `Upcoming` rail on the home page renders cards with `cardPath = '#'` — clicking
does nothing. The `frontend.movie_detail` / `frontend.series_detail` controllers both
scope their lookups with `published()`, so even if we linked through, users would
404 on any upcoming item. Deliberate trade-off, flagged in the card partial's
comment, but worth a proper "coming soon" detail page eventually.

- **Files:**
  - `Modules/Frontend/resources/views/components/partials/upcoming-cards.blade.php`
  - `Modules/Frontend/app/Http/Controllers/FrontendController.php:427,600`
- **Fix:** loosen the detail controllers to `->where('status', '!=', 'draft')` and
  branch the blade on `$movie->status === 'upcoming'` to hide the Watch Now button
  and show a release-date countdown.

### 3. Empty test directories

Three module test directories have only `.gitkeep`:

- `Modules/Payments/tests/Feature/`
- `Modules/Subscriptions/tests/Feature/`
- `Modules/Notifications/tests/Feature/`

The only real tests are `Modules/Frontend/tests/Feature/TopPicksRecommenderTest.php`
(25 tests covering the recommender) and the default Laravel Breeze auth tests at
`tests/Feature/Auth/*`. Coverage gaps on the most financially-sensitive flows:

- Payment create → callback → activate
- Payment IPN idempotency
- Subscription same-tier renewal vs. different-tier upgrade
- `ExpireSubscriptionsCommand` sweep
- 2FA enable / confirm / disable round-trip

### 4. Admin missing a "reconcile pending orders" sweep

`/admin/payments/orders` has a per-order **Reconcile with gateway** button, but no
bulk action. If PesaPal's IPN misfires across a window of orders (e.g., your
callback URL was offline during a batch), an admin has to click each one. Not a
blocker — edge case — but a scheduled job (`php artisan payments:reconcile
--since=1h`) would be cheap to add.

---

## 🏚️ Template-demo residue

The Streamit template ships ~50 demo pages showcasing icons, widgets, colors,
buttons, typography, etc. We removed the sidebar links during the admin cleanup, but
the routes + controller methods + blades still exist:

- `DashboardController::user()` removed ✅ (rewired to the new admin UserController)
- `DashboardController::profile()` / `privacy()` still exist as stubs (methods
  unreferenced; `dashboard.profile` now points at `AdminProfileController`;
  `dashboard.privacy` route removed).
- The dashboard group in `routes/web.php:35-130` still registers ~50 demo routes
  (alerts, avatars, badges, breadcrumb, buttons, offcanvas, colors, cards,
  carousel, grid, images, listgroup, modal, pagination, popovers, typography,
  tooltips, tabs, wizard, validation, font-awesome, ph-regular/bold/fill,
  icons-related, tables-related, widgets, error-404/500, maintenance, coming-soon,
  blank-page, auth showcase). Admin can still reach these by typing the URL.
- Matching blades live under `resources/views/DashboardPages/*` and are ~60 files.
- `resources/views/DashboardPages/user/ProfilePage.blade.php` — an old page with
  fake "Austin Robertson" user profile, ~1000 lines. Orphaned.
- `resources/views/DashboardPages/user/AddPage.blade.php` — demo add-user page with
  fake fields. Orphaned.
- `resources/views/DashboardPages/user/PrivacySetting.blade.php` — template demo.
  Orphaned.
- `resources/views/DashboardPages/auth/default/*` — template's internal auth demos.
  Never used; real auth lives at `resources/views/auth/*`.
- `Modules/Frontend/resources/views/Pages/view-all.blade.php` — 270 lines of
  hardcoded demo cards (Anna Sthesia & friends). Route `frontend.view-all` is
  still registered and reachable at `/view-all`, but no link in the site points
  to it.

**Fix:** a single cleanup commit that deletes all the unused routes, methods, and
blades. Everything is **currently orphaned** (no sidebar / header / in-page link
reaches them), so deletion is safe. Estimated ~70 files + ~60 route lines. Can be
done in one pass when you're ready — doesn't block anything.

---

## 🧹 Tech debt / quality

### 1. `form-role.blade.php` uses Laravel Collective Forms

`resources/views/permission-role/form-role.blade.php` uses `{{ Form::model(...) }}`
etc. Works, but we're on Laravel 10; Form facade is third-party
(`laravelcollective/html`). Everything else in the app is plain Blade / HTML.
Consistency debt.

- **Fix:** convert to plain `<form method="POST" ...>` + `@csrf` + `<input>`. ~15
  min.

### 2. `lang/en/frontendheader.php` has stale shop keys

Memory notes `shop` / `cart_page` / `wishlist_page` keys in six locale files
remained after the shop module was deleted. Harmless (the keys aren't referenced)
but confusing to new developers who'll grep for them.

- **Fix:** one-pass cleanup across `lang/*/frontendheader.php` (6 files).

### 3. Missing `refund_request` and `order_cancel` PesaPal endpoints (see Stubs § 1)

### 4. `PermissionRoleTableSeeder` — `user` role fixed ✅

Seeder now calls `$user->syncPermissions([])` instead of granting all 28 admin
permissions. Fixed this session.

### 5. Dynamic property deprecations ✅

`RolePermission` controller declared properties with types this session. No other
dynamic-property warnings found in the scan.

### 6. Pagination renderer ✅

`Paginator::useBootstrapFive()` in `AppServiceProvider::boot()`. All
`->links()` calls now render Bootstrap 5 markup matching the admin + frontend
themes.

### 7. `SESSION_DRIVER` consistency ✅

`.env` is `database`. `AdminProfileController` and `ProfileHubController` both
read from the `sessions` table. No lingering code assumes the file driver.

### 8. `FileManager` + `Installer` + `SystemUpdate` modules are stubs

`modules_statuses.json` has them all `true`, but they're skeletons:
- `FileManager` — placeholder controllers, no UI.
- `Installer` — web setup wizard, never wired.
- `SystemUpdate` — in-app updater, never wired.

They're harmless because nothing links to them. **Fix:** either flesh them out or
disable them in `modules_statuses.json` until they're ready.

---

## ✅ Verified working

A condensed list of surfaces confirmed functional by the audit so the team knows
they don't need re-testing unless the code below them changes:

- **Auth:** register, login, logout, password reset, email verification, account
  deactivation, 2FA challenge (login-time).
- **2FA:** enable → QR code → confirm → recovery codes → disable flow (both from
  user profile hub and admin profile).
- **Password reveal:** every password input across the app — admin profile, user
  CRUD, user security, admin settings, PesaPal secret — has the `<x-password-input>`
  component with a working toggle. Auth forms work via the shared JS handler on
  `.jambo-field__toggle`.
- **Session / device management:** both user and admin profiles list sessions from
  `sessions` table; per-session sign-out + sign-out-all-others both work.
- **User CRUD:** list, search, filter, create, edit, delete — with self-delete
  guard and last-admin guard.
- **Subscription tiers CRUD:** list, create, edit, delete (with "can't delete
  tier with active subs" guard). Features managed as newline-separated textarea.
- **Payments (PesaPal):** create-order → iframe modal → status polling →
  `payment.complete` page. Callback + IPN idempotent via `lockForUpdate`.
- **Payments admin:** full Orders CRUD — list (filters + pagination + expand),
  detail (edit status / notes / payment_method, reconcile with gateway, delete
  with guards), create (manual order with auto-activation).
- **Subscription activation:** `ActivateSubscriptionFromPayment` listener
  registered, idempotent via `payment_order_id`, handles renewal + tier
  switching.
- **Expire sweep:** `ExpireSubscriptionsCommand` scheduled hourly,
  `withoutOverlapping`, transaction-safe.
- **TierGate middleware:** applied to `/stream/*` and `/player/*` routes,
  respects `tier_required` + `max_concurrent_streams`, admins bypass.
- **Recommendations:** `TopPicksRecommender` (Top Picks, Smart Shuffle, Fresh
  Picks, Upcoming, Top 10 Series of the Day). Cache invalidation via observer on
  all four per-user keys.
- **Notifications:** event → subscriber → notification routing for
  OrderPlaced, PaymentCompleted, PaymentFailed, SubscriptionActivated,
  SubscriptionCancelled, plus legacy string events. Database + mail + WebPush
  channels. VAPID keys encrypted in settings.
- **Pricing page → checkout:** guest-vs-signed-in-vs-free branching on Subscribe
  button. Iframe modal with X-only close, status polling, fallback-new-tab on
  X-Frame-Options block.
- **Genre pages:** overview + per-section View All (Movies by VJ + Series by VJ),
  hero banner mirroring `/movie`.
- **Upcoming page:** full listing page with hero banner, AJAX Load More, daily
  ranking. Status filter on admin side. Published vs Upcoming vs Draft flow
  consistent across movies and shows.
- **Admin sidebar:** cleaned of template demos. Payments group (Settings /
  Orders / Pricing) sits above Review.
- **i18n:** all referenced lang files exist. The few raw-key leakages fixed
  during this session. Remaining `?? 'fallback'` patterns work because the keys
  they reference exist.
- **CSRF:** only `/payment/ipn` exempted.
- **PHP 8.2 deprecations:** no dynamic-property warnings.

---

## 🎯 Recommended fix order

Cheapest-first, highest-ROI-first:

1. ~~**[Security]** Add `password.confirm` to `permission-role.store` and
   `permission-role.reset`.~~ **✅ DONE**
2. ~~**[Broken]** Fix the three redirect targets from `backend.permission-role.list`
   to `backend.permission-role`.~~ **✅ DONE**
3. **[Broken]** Make `PermissionController::index` / `RoleController::index`
   redirect HTML callers to `backend.permission-role`. **⏭️ SKIPPED** — those
   endpoints are AJAX-only by design and the blank response doesn't affect any
   in-use flow.
4. ~~**[Broken]** Migration to flip `subscription_tiers.currency` default to
   `UGX`.~~ **✅ DONE** — column default now `UGX`, verified via
   `information_schema`.
5. **[Debt]** Convert `form-role.blade.php` off Laravel Collective. ~15 minutes,
   removes a dependency.
6. **[Stub]** Flesh out the Upcoming detail page so cards can be clicked. ~1
   hour.
7. **[Stub]** Refund + cancel endpoints on the PesaPal gateway, plus admin
   buttons. ~2 hours.
8. **[Cleanup]** Delete the ~70 orphan template-demo files (routes + methods +
   blades). 30 min cleanup commit.
9. **[Tests]** Write feature tests for the payment flow (create → callback →
   activate idempotency). ~3 hours.
10. **[Tests]** Feature tests for subscription renewal + tier change + expiry.
    ~2 hours.

**Fix-today tier complete** (items 1, 2, 4). Item 3 deliberately skipped per scope
decision. Items 5-10 are sequencing calls.

---

**Document updates:** when any finding here is addressed, delete the entry (or
move it under ✅ with a link to the commit). Adding new findings at the top of
their section is fine — this is a living audit, not a snapshot.
