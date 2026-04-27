# Working on Jambo — Dev Workflow & Guardrails

A short field guide for fixing bugs and shipping features on the Jambo
streaming platform without breaking anything that already works.

> **Read this before any non-trivial change**, then read the linked
> module/area-specific doc for the part you're touching. Most of the
> system is already documented; the rules below stop us from
> rediscovering the same gotchas every time.

---

## 1. Before you start — read these

These describe how the system actually fits together. Skim before
making *any* design decision; deep-read the one closest to your task.

- [`docs/streamit-laravel.md`](streamit-laravel.md) — the template
  Jambo is built on. Knowing what's vendor vs. ours saves rewrites.
- [`docs/modules.md`](modules.md) — module map. What each module owns,
  which depends on which.
- [`docs/admin-panel-guide.md`](admin-panel-guide.md) — admin UI flows,
  RBAC, content management, payments admin.
- [`docs/frontend-guide.md`](frontend-guide.md) — public site routes,
  layouts, the home/detail/watch flow.
- [`docs/ui-guidelines.md`](ui-guidelines.md) — visual conventions:
  colours, spacing, mobile breakpoints.
- [`docs/deploy/hostinger-vps.md`](deploy/hostinger-vps.md) and
  [`docs/deploy/cyberpanel.md`](deploy/cyberpanel.md) — production env.
- [`docs/modules/<name>.md`](modules/) — per-module specifics
  (notifications, file-manager, system-update, pesapal-integration).
- [`docs/SESSION-RESUME.md`](SESSION-RESUME.md) — running log of major
  shipped phases, useful for "what was the previous work in this area?"
- [`docs/debug.md`](debug.md) — known-issue notes, recovery procedures.

If a change touches a module without an existing doc, add one rather
than letting the next person guess.

---

## 2. Standard change loop

Apply this every time. It's the difference between fixes that stick
and fixes that silently regress.

1. **Identify the file(s) actually affected.** Use `Grep` for symbols,
   not vibes. Don't assume — verify.
2. **Read the relevant doc(s) above** for context the code alone
   doesn't tell you (e.g., why a route is auth-only, why a column is
   nullable).
3. **Make the smallest change that resolves the issue.** Don't refactor
   adjacent code. Don't add abstractions for hypothetical future needs.
4. **Lint** with `php -l <file>` for any PHP touched.
5. **Bump `version.txt`** following the rules in §4.
6. **Commit with a clear message** stating the *why* (see §5).
7. **Push** to `main`. CI is not yet present — verification is manual.
8. **Deploy** following §3.
9. **Verify on production** before declaring it shipped.

---

## 3. Production deploy — the working sequence

The VPS runs as user `jambo2820`, not root. Git refuses to operate on
the repo as the wrong user; use the patterns below.

### 3a. Standard pull-only deploy (Blade / JS / CSS / config-only changes)

```bash
cd /home/jambofilms.com/public_html
git pull
php artisan view:clear
```

That's it. No queue restart needed.

### 3b. With a migration

```bash
cd /home/jambofilms.com/public_html
git pull
php artisan migrate
php artisan view:clear && php artisan route:clear && php artisan cache:clear
```

### 3c. With code that runs in the queue worker (jobs, listeners, etc.)

The systemd worker holds compiled PHP in memory. Without a restart it
keeps running the **old code** even after the pull.

```bash
cd /home/jambofilms.com/public_html
git pull
php artisan migrate
php artisan view:clear && php artisan route:clear && php artisan cache:clear
sudo systemctl restart jambo-queue.service
```

### 3d. If the queue currently has bad jobs (e.g., wrong serialised payload)

Stop worker → flush queue → restart → re-dispatch fresh.

```bash
sudo systemctl stop jambo-queue.service
php artisan queue:clear database --queue=default --force
sudo systemctl start jambo-queue.service
# then re-dispatch via tinker, one job at a time:
php artisan tinker --execute='\Modules\Content\app\Jobs\TranscodeVideoJob::dispatch("movie", 25);'
```

### 3e. If `git pull` errors with "dubious ownership"

You're SSH'd as root and the repo is owned by `jambo2820`. Either:

```bash
# Option A — pull as the file owner (preferred, keeps perms clean):
sudo -u jambo2820 -i bash -c 'cd /home/jambofilms.com/public_html && git pull'

# Option B — register an exception for root:
git config --global --add safe.directory /home/jambofilms.com/public_html
```

### 3f. After deploy — sanity checks

```bash
git log --oneline -1                            # verify expected SHA
grep -A1 'public int $timeout' Modules/Content/app/Jobs/TranscodeVideoJob.php   # spot-check expected file content
sudo -u jambo2820 crontab -l | grep schedule    # verify Laravel scheduler cron is alive
php artisan subscriptions:expire --dry-run      # verify scheduled commands run
```

---

## 4. Versioning (`version.txt`)

Already-recorded user preference: **never reuse a version**, and the
size of the bump matches the change.

| Change kind | Bump |
|---|---|
| Bundled bug fixes | patch (`1.2.3 → 1.2.4`) |
| New feature, backwards-compatible | minor (`1.2.4 → 1.3.0`) |
| Schema changes that aren't safely reversible / breaking | major (`1.3.x → 2.0.0`) |

Always bump in the same commit as the change.

---

## 5. Commit messages

Short subject line stating *what* changed. Body explains *why* — what
problem this commit solves and what the trade-off is, not what lines
moved (the diff already shows that).

Always include the Anthropic co-author trailer:

```
Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
```

---

## 6. Pitfalls already paid for — don't re-pay them

These are landmines we've already stepped on. The fixes are in the
codebase; don't undo them.

### 6a. Browser / PWA cache for static assets

`asset('frontend/css/foo.css')` produces a URL with no version query.
Browsers and the installed PWA cache it indefinitely, so CSS / JS
edits don't reach users until they manually hard-refresh.

**Always use `versioned_asset()`** for files in `public/` that change
over time. It appends `?v=<filemtime>`, so each `git pull` invalidates
the cache automatically.

```blade
<link rel="stylesheet" href="{{ versioned_asset('frontend/css/jambo-header.css') }}">
<script src="{{ versioned_asset('frontend/js/swiper.js') }}" defer></script>
```

### 6b. Vite-compiled CSS lives in `build-frontend/`

Editing `Modules/Frontend/resources/assets/sass/**/*.scss` requires a
`npm run build` step before changes ship. We don't have a Vite build on
the VPS. **Patch in `public/frontend/css/jambo-header.css`** (loaded
after the Vite bundle, so it wins by source order) and update the SCSS
source for parity — but the runtime fix is the CSS file in `public/`.

### 6c. `overflow-x: clip` belongs on `<body>`, not `<html>`

Putting overflow-x clip/hidden on `<html>` interferes with the
fullscreen API on mobile WebKit (video can't enter fullscreen cleanly).
The mobile-overflow guard in `jambo-header.css` is scoped to `body`
**and** to `<992px` only — keep it that way.

### 6d. Queue worker runs old code until restarted

If a deploy touches a job class, listener, or anything the worker
loads, you **must** `systemctl restart jambo-queue.service`. View
caches and route caches are unrelated; the worker is its own
long-lived PHP process.

### 6e. Job timeouts are baked into the queued payload

`queue:retry` re-pushes the **original serialised payload**, including
the timeout that was in effect when the job was first dispatched. If
you bump `$timeout` on a job class, **don't retry** — clear the queue
and dispatch fresh, otherwise old payloads still hit the old limit.

### 6f. Laravel scheduler needs a cron entry

The `subscriptions:expire`, `notifications:cleanup`, and any other
`->hourly()` / `->daily()` schedules in service providers do **nothing**
unless this is in `jambo2820`'s crontab:

```
* * * * * cd /home/jambofilms.com/public_html && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

Verify with `sudo -u jambo2820 crontab -l | grep schedule`.

### 6g. Transcoding is CPU-heavy on a 2-core box

ffmpeg saturates available cores. The fix in `TranscodeVideoJob` uses
`-preset veryfast` (≈4× faster than libx264 default) and a 6-hour
timeout. Don't lower either casually — the previous values caused
production failures.

### 6h. Banner / hero CSS classes diverge between pages

`.banner-home-swiper-image` (home), `.slider--image` (detail), and
`.tab-slider-banner-images` already have `background-size: cover`.
`.movie-banner-image` did not — that bug ate a session day. If you
add a new banner partial, **check it has cover/center/no-repeat** so a
1920px backdrop doesn't blow out mobile.

### 6i. Mobile rotation requires both manifest *and* JS

The PWA manifest's `orientation` field gates the OS — set to `any`
(not `portrait`). Then a JS handler in
`jambo-minimal-player.blade.php` enters fullscreen on rotate to
landscape. Both pieces are needed; one without the other does nothing.

### 6j. View counts already follow a "unique-device-or-user" model

`watch_history` (authed) + `guest_views` (cookie-based, free content
only) → both increment `views_count` on first sight per device/user.
Don't add another counter path; extend these.

---

## 7. When something breaks in production

1. **Check `storage/logs/laravel.log`** — `tail -n 200`. Most issues
   surface here.
2. **Check `failed_jobs` table** — for queue failures, the original
   exception is in the payload, not the `MaxAttempts` wrapper.
3. **Check systemd journal** — `journalctl -u jambo-queue.service
   --since "2 hours ago"` for OOM kills, restarts, signals.
4. **Check the PWA install** — installed PWAs cache aggressively. A
   browser hard-refresh doesn't clear the PWA. Uninstall + reinstall
   for definitive testing of manifest / asset changes.

---

## 8. Useful one-liners

```bash
# Show recent commits with one-line summary
git log --oneline -10

# Spot-check a file matches expectation post-deploy
grep -A2 '<some-anchor>' <path/to/file>

# How many failed jobs do we have?
php artisan tinker --execute='echo \DB::table("failed_jobs")->count();'

# What's the latest job class to fail?
php artisan tinker --execute='echo \DB::table("failed_jobs")->latest("failed_at")->first()?->payload;'

# Re-trigger a transcode without queue:retry (uses fresh payload)
php artisan tinker --execute='\Modules\Content\app\Jobs\TranscodeVideoJob::dispatch("movie", 42);'

# Show pending subscriptions that the cron should expire next sweep
php artisan subscriptions:expire --dry-run

# Confirm scheduler cron is wired
sudo -u jambo2820 crontab -l | grep schedule
```

---

## 9. Out of scope for this guide

- Module-specific contracts (see `docs/modules/<name>.md`).
- Streamit template internals (see `docs/streamit-laravel.md`).
- Admin panel route inventory (see `docs/admin-panel-guide.md`).
- Frontend route inventory (see `docs/frontend-guide.md`).

This guide is intentionally **process**-focused. The *what* lives in
the docs above; this is the *how* we change things safely.
