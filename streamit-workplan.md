# Streamit Laravel Streaming Platform — Full Work Plan

**Stack:** Laravel 10 + Blade + Streamit Template (Iqonic Design)
**Server:** Hostinger VPS — Ubuntu 22.04
**Storage:** Dropbox (proxied, URL never exposed)
**Payments:** PesaPal v3 (Basic / Premium tiers)
**Cache:** Redis (temp links, sessions, queues)
**Queue Worker:** Supervisor

---

## Project Overview

A Netflix-style streaming platform built on the Streamit Laravel template.
Users subscribe to one of two tiers, pay via PesaPal, and watch movies
streamed securely through a Laravel proxy. The Dropbox URL is never sent
to the browser. Tier-gated content is enforced at both the route and
controller level on every request.

---

## Tier Structure

| Feature                  | Basic (SD)        | Premium (HD)       |
|--------------------------|-------------------|--------------------|
| Video quality            | 480p / 720p SD    | 1080p HD           |
| Access to all movies     | Yes               | Yes                |
| New releases (first week)| No                | Yes                |
| Price (monthly)          | UGX 15,000        | UGX 30,000         |
| Simultaneous streams     | 1                 | 2                  |
| Watchlist                | Yes               | Yes                |
| Download to cache        | No                | No (no one can)    |

> Prices are placeholders. Adjust before launch.

---

## Phase 0 — Server Setup

**Goal:** A clean, production-ready Ubuntu 22.04 environment on Hostinger VPS.

### Tasks

- [ ] Provision Hostinger VPS (Ubuntu 22.04, minimum 2 vCPU / 4GB RAM / 80GB SSD)
- [ ] Create a non-root sudo user, disable root SSH login
- [ ] Set up UFW firewall — allow ports 22, 80, 443 only
- [ ] Install Nginx
- [ ] Install PHP 8.2 with extensions: fpm, mysql, redis, gd, zip, mbstring, xml, curl, bcmath, tokenizer
- [ ] Install MySQL 8
- [ ] Install Redis
- [ ] Install Composer
- [ ] Install Node.js 20 + npm (for Vite asset compilation)
- [ ] Install Certbot + obtain SSL certificate for your domain
- [ ] Configure Nginx server block for Laravel (root to `/public`, handle PHP-FPM, add streaming headers)
- [ ] Install Supervisor
- [ ] Set `/bootstrap` permissions to 775, `/storage` to 775 recursively
- [ ] Configure PHP.INI: `upload_max_filesize`, `post_max_size`, `memory_limit` (at minimum 256M each)

### Nginx Streaming Headers to Add

```nginx
add_header Cache-Control "no-store, no-cache, must-revalidate";
add_header X-Content-Type-Options nosniff;
add_header X-Frame-Options SAMEORIGIN;
proxy_buffering off; # critical for smooth video proxy streaming
```

**Deliverable:** A running Nginx + PHP-FPM server with SSL, Redis active,
and Supervisor ready for queue workers.

---

## Phase 1 — Laravel Project Setup

**Goal:** Install Laravel 10, install the Streamit template, configure
the database, and establish base structure.

### Tasks

#### 1.1 Install Laravel + Streamit Template

- [ ] Clone or upload the Streamit Laravel template to `/var/www/yoursite`
- [ ] Run `composer install`
- [ ] Copy `.env.example` to `.env`, generate app key with `php artisan key:generate`
- [ ] Configure `.env`:
  - `APP_URL=https://yourdomain.com`
  - `APP_ENV=production`
  - `DB_*` credentials
  - `CACHE_DRIVER=redis`
  - `SESSION_DRIVER=redis`
  - `QUEUE_CONNECTION=redis`
  - `REDIS_HOST=127.0.0.1`
- [ ] Run `npm install` and `npm run build` (compiles both dashboard and frontend assets via Vite)
- [ ] Confirm the Streamit frontend module loads at `/` and the dashboard at `/dashboard`

#### 1.2 Database Schema (Custom Tables)

These tables extend Streamit's existing movie / show / category structure.

```
subscriptions
─────────────
id | user_id | tier (basic|premium) | starts_at | expires_at
pesapal_ref | status (active|expired|cancelled) | amount | currency

pesapal_transactions
─────────────────────
id | user_id | order_tracking_id | merchant_reference
tier | amount | status (pending|completed|failed) | created_at

watchlist
──────────
id | user_id | movie_id | added_at

watch_history
──────────────
id | user_id | movie_id | watched_at | progress_seconds
```

Streamit's existing tables to keep and extend:
- `movies` — add columns: `dropbox_sd_path`, `dropbox_hd_path`, `min_tier` (basic|premium)
- `shows` — add: `min_tier`
- `episodes` — add: `dropbox_sd_path`, `dropbox_hd_path`
- `categories` — no changes needed
- `users` — no changes needed (subscription linked via foreign key)

- [ ] Write and run all migrations
- [ ] Seed database with test movies and one test user per tier

#### 1.3 Models + Relationships

- [ ] `User` hasOne `Subscription`, hasMany `WatchHistory`, hasMany `Watchlist`
- [ ] `Subscription` belongsTo `User`
- [ ] `Movie` — add accessors for `dropbox_sd_path`, `dropbox_hd_path`, `min_tier`
- [ ] `Episode` — same as Movie

#### 1.4 Middleware

- [ ] `EnsureActiveSubscription` — redirects to `/pricing` if no active subscription
- [ ] `EnsureTierAccess` — checks `min_tier` on the movie/episode against user tier, returns 403 with upgrade prompt if too low
- [ ] Apply both to all `/watch/*` and `/stream/*` routes

**Deliverable:** A running Streamit install with custom tables, models,
and middleware in place.

---

## Phase 2 — PesaPal Integration

**Goal:** Accept payments via PesaPal, activate subscriptions automatically
on IPN callback.

### How PesaPal v3 Works

```
1. User selects a tier on /pricing
2. Laravel calls PesaPal API to create an order → gets redirect URL
3. User is redirected to PesaPal hosted payment page
4. User pays via mobile money, card, or bank
5. PesaPal sends IPN (POST) to your /pesapal/ipn route
6. Laravel verifies the transaction status with PesaPal API
7. If confirmed → create/renew subscription record
8. User is redirected to /dashboard with subscription active
```

### Tasks

#### 2.1 PesaPal API Service

- [ ] Create `app/Services/PesapalService.php`
- [ ] Implement methods:
  - `authenticate()` — gets bearer token from PesaPal OAuth endpoint
  - `registerIPN($url)` — registers your IPN URL with PesaPal (done once)
  - `submitOrder($user, $tier, $amount)` — creates order, returns redirect URL
  - `getTransactionStatus($orderTrackingId)` — verifies payment status

#### 2.2 Routes + Controllers

```
GET  /pricing                     → PricingController@index
POST /pesapal/initiate            → PesapalController@initiate
GET  /pesapal/callback            → PesapalController@callback
POST /pesapal/ipn                 → PesapalController@ipn   (no auth middleware)
```

- [ ] `PricingController@index` — renders tier comparison page using Streamit's pricing layout
- [ ] `PesapalController@initiate` — validates tier, calls `PesapalService::submitOrder()`, stores pending transaction, redirects user to PesaPal
- [ ] `PesapalController@callback` — PesaPal redirects user here after payment (for UX only, not trusted for activation)
- [ ] `PesapalController@ipn` — receives PesaPal IPN, dispatches `ProcessPesapalPayment` queue job

#### 2.3 Queue Job

- [ ] Create `app/Jobs/ProcessPesapalPayment.php`
- [ ] Job calls `PesapalService::getTransactionStatus()` with the order tracking ID
- [ ] If `COMPLETED` → find matching `pesapal_transactions` record → create or extend `Subscription` for the user
- [ ] If `FAILED` → mark transaction as failed, send failure email
- [ ] Retry logic: 3 attempts with exponential backoff

#### 2.4 Supervisor Config for Queue Worker

```ini
[program:streamit-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/yoursite/artisan queue:work redis --sleep=3 --tries=3 --timeout=90
autostart=true
autorestart=true
numprocs=1
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/streamit-worker.log
```

- [ ] Add this config to Supervisor
- [ ] Run `supervisorctl reread && supervisorctl update && supervisorctl start streamit-worker:*`

#### 2.5 Subscription Expiry

- [ ] Create scheduled command `CheckExpiredSubscriptions` — runs daily at midnight
- [ ] Sets `status = expired` on subscriptions where `expires_at < now()`
- [ ] Register in `app/Console/Kernel.php` as `->daily()`
- [ ] Add cron entry on the VPS: `* * * * * php /var/www/yoursite/artisan schedule:run >> /dev/null 2>&1`

**Deliverable:** End-to-end PesaPal payment flow, IPN-activated subscriptions,
and automatic expiry handling.

---

## Phase 3 — Dropbox Proxy Streaming

**Goal:** Stream video from Dropbox through Laravel. The Dropbox URL never
reaches the browser.

### Architecture

```
Browser: GET /stream/{movie:slug}?quality=sd
  ↓
StreamController (checks auth, subscription, tier)
  ↓
Redis: check for cached Dropbox temp link
  ↓ (miss)
Dropbox API: generate temp link for the file path
  ↓
Redis: cache the temp link for 3.5 hours (keyed: stream:{movie_id}:{tier})
  ↓
Laravel: forward Range header to Dropbox, pipe bytes back to browser
  ↓
Browser: plays video from yoursite.com/stream/movie-slug — no Dropbox URL visible
```

### Tasks

#### 3.1 Dropbox API Service

- [ ] Create `app/Services/DropboxService.php`
- [ ] Store `DROPBOX_APP_KEY`, `DROPBOX_APP_SECRET`, `DROPBOX_REFRESH_TOKEN` in `.env`
- [ ] Implement:
  - `getAccessToken()` — exchanges refresh token for short-lived access token, caches in Redis for 3.5 hours
  - `getTemporaryLink($path)` — calls Dropbox `/files/get_temporary_link` endpoint, returns URL
  - `getCachedStreamUrl($movieId, $tier, $path)` — checks Redis first, calls `getTemporaryLink()` on miss, caches result

#### 3.2 Streaming Controller

- [ ] Create `app/Http/Controllers/StreamController.php`
- [ ] Route: `GET /stream/{movie:slug}` with middleware `[auth, EnsureActiveSubscription, EnsureTierAccess]`
- [ ] Logic:
  1. Resolve movie from slug
  2. Determine quality: user is Premium → check `?quality` param (sd or hd), Basic → force SD
  3. Get Dropbox path from movie model based on resolved quality
  4. Call `DropboxService::getCachedStreamUrl()`
  5. Read `Range` header from the incoming request
  6. Open a Guzzle stream to the Dropbox temp URL, forwarding the Range header
  7. Return a `StreamedResponse` with correct headers:

```php
return response()->stream(function () use ($dropboxUrl, $range) {
    $client = new \GuzzleHttp\Client();
    $response = $client->get($dropboxUrl, [
        'stream' => true,
        'headers' => ['Range' => $range ?? 'bytes=0-'],
    ]);
    $body = $response->getBody();
    while (!$body->eof()) {
        echo $body->read(1024 * 64); // 64KB chunks
        flush();
    }
}, $response->getStatusCode(), [
    'Content-Type'    => 'video/mp4',
    'Accept-Ranges'   => 'bytes',
    'Content-Range'   => $response->getHeader('Content-Range')[0] ?? '',
    'Content-Length'  => $response->getHeader('Content-Length')[0] ?? '',
    'Cache-Control'   => 'no-store, no-cache',
    'X-Accel-Buffering' => 'no', // disables Nginx buffering for streaming
]);
```

- [ ] Handle `Range` header missing (full file request) and malformed range gracefully

#### 3.3 Watch Page Route

- [ ] Route: `GET /watch/{movie:slug}` — renders Streamit's movie detail / player Blade view
- [ ] The `<video>` tag src is set to `/stream/{slug}` — never a Dropbox URL
- [ ] Add to the player Blade:

```html
<video
  id="streamit-player"
  controls
  controlslist="nodownload nofullscreen"
  oncontextmenu="return false;"
  preload="metadata"
>
  <source src="{{ route('stream', $movie->slug) }}" type="video/mp4">
</video>
```

- [ ] Add a small JS snippet that removes the download attribute and disables right-click on the video element (not bulletproof, but adds friction)

#### 3.4 Episode Streaming

- [ ] Same StreamController handles episodes via route `GET /stream/episode/{episode:id}`
- [ ] Middleware checks the parent show's `min_tier`

**Deliverable:** Fully proxied video streaming. Dropbox is invisible to the client.
Seeking and scrubbing work via Range request forwarding.

---

## Phase 4 — Admin Panel

**Goal:** Manage movies, shows, categories, users, and subscriptions from the
Streamit dashboard.

### Tasks

#### 4.1 Movie Management

The Streamit template ships with `MovieListPage.blade.php` and related views.
Extend them:

- [ ] Add movie upload form fields: `dropbox_sd_path`, `dropbox_hd_path`, `min_tier`
- [ ] Admin pastes the Dropbox file path (not a URL) — e.g. `/movies/avengers-hd.mp4`
- [ ] `MovieController@store` validates and saves these fields
- [ ] `MovieController@index` uses DataTables plugin (already in Streamit) for paginated movie list

#### 4.2 Show + Season + Episode Management

Streamit ships with `ShowListPage.blade.php`, `SeasonsPage.blade.php`, `EpisodesPage.blade.php`.

- [ ] Extend episode form with `dropbox_sd_path`, `dropbox_hd_path`
- [ ] Extend show form with `min_tier`

#### 4.3 Subscription Management

- [ ] New admin view: `/dashboard/subscriptions` — lists all active/expired subscriptions with user, tier, expiry, PesaPal reference
- [ ] Admin can manually extend or cancel a subscription (for support cases)
- [ ] Use Streamit's DataTable component for this view

#### 4.4 Analytics Dashboard

Streamit ships with `AnalyticsPage.blade.php` and ApexCharts.

- [ ] Wire up real data:
  - Total active subscribers (Basic vs Premium split) — donut chart
  - New subscriptions per day (last 30 days) — line chart
  - Most-watched movies (by `watch_history` count) — bar chart
  - Revenue by month (from `pesapal_transactions`) — area chart
- [ ] Use ApexCharts (already included in template) for all four

#### 4.5 User Management

Streamit ships with user views.

- [ ] Admin view: `/dashboard/users` — list users, show subscription status inline
- [ ] Admin can view a user's watch history and subscription history

**Deliverable:** A fully functional admin dashboard for content and subscription management.

---

## Phase 5 — Frontend (User-Facing)

**Goal:** Use the Streamit frontend module to deliver a polished, functional
streaming experience.

### Tasks

#### 5.1 Homepage

Streamit ships with a full homepage layout. Wire it up:

- [ ] Hero banner (Swiper Slider) — pull from featured movies in DB
- [ ] "Trending Now" row — movies sorted by `watch_history` count
- [ ] "New Releases" row — movies sorted by `created_at DESC`
- [ ] "Premium Picks" row — movies where `min_tier = premium` (blurred thumbnails for Basic users)
- [ ] Category rows — pulled dynamically from `categories` table

#### 5.2 Movie Detail Page

Streamit ships with a movie detail layout.

- [ ] Render poster, title, year, genre, description, rating, cast
- [ ] For Basic users visiting a Premium movie: show a blur overlay with an upgrade CTA
- [ ] For subscribed users with correct tier: show the video player wired to `/stream/{slug}`
- [ ] Related movies row at the bottom (same genre)

#### 5.3 Show / Season / Episode Pages

Streamit ships with `ShowListPage`, `SeasonsPage`, `EpisodesPage`.

- [ ] Wire seasons list and episode list to real DB data
- [ ] Episode player wired to `/stream/episode/{id}`
- [ ] Episode progress saved to `watch_history` via AJAX every 30 seconds

#### 5.4 Search + Filters

- [ ] Search bar in the Streamit megamenu — queries `movies.title` and `shows.title`
- [ ] Filter by genre, year, rating, tier (on movie list page)
- [ ] NoUi Slider (already in template) — use for year range filter

#### 5.5 Watchlist

- [ ] Add / remove from watchlist via AJAX (heart icon on movie cards)
- [ ] Watchlist page at `/watchlist` — lists saved movies and shows
- [ ] Works for both tiers

#### 5.6 Pricing Page

- [ ] `/pricing` — renders two tier cards side by side using Streamit's card components
- [ ] Each card has a PesaPal "Subscribe Now" button that POSTs to `/pesapal/initiate`
- [ ] Show current plan status if user is already subscribed (with expiry date)

#### 5.7 User Profile + Settings

Streamit ships with `user-profile.blade.php` and `user-privacy-setting.blade.php`.

- [ ] Wire profile page to real user data
- [ ] Add subscription status section: tier badge, expiry date, renewal button
- [ ] Watch history tab — lists last 20 watched movies

#### 5.8 Auth Pages

Streamit ships with auth views including Two Factor.

- [ ] Use Laravel Breeze as the auth backend (already compatible with Streamit's auth views)
- [ ] Two Factor Authentication — use Streamit's `TwoFactor.blade.php` view
- [ ] After login, redirect based on subscription status:
  - No subscription → `/pricing`
  - Active subscription → `/` (homepage)

**Deliverable:** A complete, functional frontend that matches the Streamit design
and is wired to real data, payments, and streaming.

---

## Phase 6 — Design Customization (Streamit Template)

**Goal:** Brand the template to your platform's identity.

### Streamit Customization Points (from documentation)

- [ ] **Logo** — replace in `/public/frontend/images/` and `/public/dashboard/images/`
- [ ] **Favicon** — replace in `/public/`
- [ ] **Colors** — edit the primary color variable in `/public/frontend/scss/` and `/public/dashboard/scss/`. Streamit uses CSS custom properties
- [ ] **Fonts** — update font imports in the SCSS files (Streamit uses a Google Fonts import by default)
- [ ] **Theme mode** — Streamit supports dark/light mode toggle. Default to dark (standard for streaming platforms)
- [ ] **Loader** — customize the page loading transition in the loader config
- [ ] **Sidebar appearance** — configure via Streamit's sidebar settings (dashboard only)
- [ ] **Header style** — choose from available header variants in the template
- [ ] **Footer style** — customize links, copyright text, social icons

**Deliverable:** A fully branded platform that still uses Streamit's component system.

---

## Phase 7 — Security + Hardening

**Goal:** Lock down the platform before going live.

### Tasks

- [ ] Set all `/stream/*` routes behind `auth` + `EnsureActiveSubscription` + `EnsureTierAccess`
- [ ] Rate limit the stream route to prevent abuse: `throttle:60,1` (60 requests per minute per user)
- [ ] Add `X-Robots-Tag: noindex` header to `/stream/*` routes so video URLs never appear in search
- [ ] Set `SESSION_SECURE_COOKIE=true` and `SESSION_SAME_SITE=strict` in `.env`
- [ ] Disable `APP_DEBUG` in production
- [ ] Add CSRF protection to all PesaPal-facing forms
- [ ] Exclude `/pesapal/ipn` from CSRF middleware (PesaPal sends POST without a CSRF token)
- [ ] Validate PesaPal IPN authenticity by calling their status API instead of trusting the POST body
- [ ] Configure Nginx to block direct access to `.env`, `.git`, and `storage/` paths
- [ ] Set up daily database backups to a separate Dropbox folder using a cron job

---

## Phase 8 — Testing + Launch

### Pre-launch Checklist

- [ ] Test full PesaPal payment flow end-to-end in sandbox mode
- [ ] Switch PesaPal to live credentials, test one real transaction
- [ ] Test subscription activation, expiry, and renewal
- [ ] Test stream proxy with a real movie — confirm seek/scrub works
- [ ] Test tier enforcement: Basic user cannot watch Premium content
- [ ] Test that DevTools shows `/stream/slug` with no Dropbox URL anywhere in requests
- [ ] Test on mobile (iOS Safari, Android Chrome) — confirm HTML5 player works
- [ ] Test episode streaming and progress saving
- [ ] Load test the stream proxy with at least 5 concurrent streams
- [ ] Confirm Redis is caching temp links (check with `redis-cli KEYS "stream:*"`)
- [ ] Confirm Supervisor keeps the queue worker alive after a server reboot
- [ ] Confirm SSL renews automatically via Certbot timer
- [ ] Confirm the daily subscription expiry cron is running

### Launch

- [ ] Point domain DNS to Hostinger VPS IP
- [ ] Enable Nginx site, disable default
- [ ] Set `APP_ENV=production`, `APP_DEBUG=false`
- [ ] Run `php artisan config:cache && php artisan route:cache && php artisan view:cache`
- [ ] Monitor error log: `tail -f /var/log/nginx/error.log`
- [ ] Monitor queue worker log: `tail -f /var/log/streamit-worker.log`

---

## File Reference (Streamit Template Pages)

| Template File                    | Purpose in Our Build                          |
|----------------------------------|-----------------------------------------------|
| `IndexPage.blade.php`            | Homepage with movie rows                      |
| `MovieListPage.blade.php`        | Browse all movies, filter by genre/year       |
| `ShowListPage.blade.php`         | Browse all TV shows                           |
| `SeasonsPage.blade.php`          | Show seasons list                             |
| `EpisodesPage.blade.php`         | Season episodes list                          |
| `CategoryListPage.blade.php`     | Movies by category                            |
| `CommentPage.blade.php`          | Movie comments / ratings                      |
| `AnalyticsPage.blade.php`        | Admin analytics (wired to real data)          |
| `user-profile.blade.php`         | User profile + subscription status            |
| `user-privacy-setting.blade.php` | Account security settings                     |
| `auth/TwoFactor.blade.php`       | 2FA login screen                              |

---

## Plugins in Use (From Streamit Template)

| Plugin         | Where Used                                      |
|----------------|-------------------------------------------------|
| Swiper Slider  | Homepage hero banner, movie rows                |
| ApexCharts     | Admin analytics (revenue, subscriptions, views) |
| DataTables     | Admin movie list, user list, subscriptions list |
| SweetAlert     | Confirm dialogs (cancel subscription, delete)   |
| Flatpickr      | Admin date pickers (subscription management)    |
| NoUi Slider    | Year range filter on movie browse page          |
| Circle Progress| User profile — subscription days remaining      |

---

## Environment Variables Reference

```env
APP_NAME=YourStreamingPlatform
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=streamit_db
DB_USERNAME=streamit_user
DB_PASSWORD=your_db_password

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=strict

DROPBOX_APP_KEY=your_app_key
DROPBOX_APP_SECRET=your_app_secret
DROPBOX_REFRESH_TOKEN=your_long_lived_refresh_token

PESAPAL_CONSUMER_KEY=your_consumer_key
PESAPAL_CONSUMER_SECRET=your_consumer_secret
PESAPAL_IPN_URL=https://yourdomain.com/pesapal/ipn
PESAPAL_ENV=live  # or sandbox during testing
```

---

## Build Sequence Summary

| Phase | Focus                                | Est. Time |
|-------|--------------------------------------|-----------|
| 0     | VPS server setup                     | 1 day     |
| 1     | Laravel + Streamit install + schema  | 1–2 days  |
| 2     | PesaPal integration                  | 2–3 days  |
| 3     | Dropbox proxy streaming              | 2–3 days  |
| 4     | Admin panel wiring                   | 2 days    |
| 5     | Frontend wiring                      | 3–4 days  |
| 6     | Design customization                 | 1 day     |
| 7     | Security hardening                   | 1 day     |
| 8     | Testing + launch                     | 1–2 days  |
| **Total** |                                  | **~16–19 days** |

---

*Start with Phase 0. Say "go" and the full VPS setup bash script is next.*
