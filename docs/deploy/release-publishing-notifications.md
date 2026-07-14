# Release note — publishing, timezones & notifications

**Fixes:** movies/episodes showing as Published but invisible to users;
"new movie added" notifications linking to a 404; the admin panel hanging
on Save.

This is a **release note, not a runbook.** It only lists what is *different*
about this deploy. Follow your normal deploy runbook
([hostinger-vps.md](hostinger-vps.md) or [cyberpanel.md](cyberpanel.md))
and fold the extra steps below into it.

---

## Why the extra steps

Three root causes, three deploy requirements:

| Root cause | Requires |
|---|---|
| App clock was UTC, admins are UTC+3 — a title published at 8pm went live at 11pm | `APP_DISPLAY_TIMEZONE` |
| Notifications fired on `status = published` without checking the release date | `php artisan migrate` (adds `announced_at`) |
| Push/email fan-out ran inline inside the admin's save request | `QUEUE_CONNECTION=database` + a live worker + cron |

---

## 1. `.env` changes

Add / change these two lines in the **production** `.env`:

```dotenv
# Wall clock the admin panel reads and writes. Storage stays UTC.
APP_DISPLAY_TIMEZONE=Africa/Kampala

# Was `sync`. Notification fan-out now runs on the queue.
QUEUE_CONNECTION=database
```

`QUEUE_CONNECTION=database` is what the deploy runbooks already assume —
if yours is currently `sync`, this is the change.

## 2. Migration (do not skip)

```bash
php artisan migrate --pretend   # review
php artisan migrate --force
```

Adds `announced_at` to `movies`, `shows`, `seasons`, `episodes` **and
backfills it** for everything already published.

> **The backfill is load-bearing.** It marks existing content as
> "already announced". Without it, the first scheduler tick after deploy
> would broadcast your **entire back catalogue** to every verified user.
> Verified locally: post-migration the first tick announces nothing.

## 3. Rebuild caches

Standard step, but **required** this time — `config/app.php` and the admin
Blade forms both changed:

```bash
php artisan config:clear && php artisan view:clear
php artisan config:cache && php artisan view:cache
```

## 4. Restart the queue worker (required)

Workers cache code in memory. There is a **new job class**
(`BroadcastNotificationToAll`) and notifications are now `ShouldQueue` —
a stale worker will not know about either.

```bash
sudo supervisorctl restart jambo-worker:*
```

## 5. Confirm cron is alive (required)

Already documented in both runbooks, but it is now **load-bearing**:

```cron
* * * * * cd /var/www/jambo && php artisan schedule:run >> /dev/null 2>&1
```

It drives two things now:

- `queue:work` — sends the notifications
- `content:announce-due` — announces titles the moment their release
  time arrives (this is how a scheduled release gets its notification)

**If this cron dies, notifications and scheduled releases stop silently.**
They do not error — jobs just pile up unsent. This is the one real
tradeoff of moving off `sync`.

---

## Post-deploy checks

```bash
# The release announcer is registered and scheduled
php artisan schedule:list | grep announce-due

# Nothing stuck: this should stay near 0 and keep draining
php artisan tinker --execute="echo DB::table('jobs')->count();"
php artisan queue:failed
```

Then in the admin panel:

1. Publish a movie with the date field left **blank** → it should appear on
   the site **immediately** (this was the 3-hour blackout).
2. Publish a movie dated **a few days out** → no notification yet, and its
   detail page shows **"Coming soon"** rather than a 404.
3. Hit Save on a series → the page should return **instantly** (the push
   fan-out now happens on the worker, not in your request).

The publish date field now reads **"Published at (EAT)"**. That label is
the fix for admins working from other countries — the field always means
Kampala time, so an admin in Riyadh or London schedules the same real
release moment. It shows their own local equivalent underneath.

---

## Rollback

Standard code rollback per the runbook. The migration is reversible
(`php artisan migrate:rollback`), but you generally **don't need to** —
`announced_at` is additive and ignored by the old code.

If the queue turns out not to be running and you need notifications back
immediately, set `QUEUE_CONNECTION=sync` and rebuild the config cache.
Sends go back to being slow and inline, but nothing is lost.

---

## Known, deliberately not included

- **Old timestamps are still ~3h off.** Publish dates typed before this
  fix were stored on the wrong clock. They're all in the past, so nothing
  is hidden by it and no user sees a difference. Not rewritten — correcting
  historical data is a separate, deliberate decision.
- **Bunny CDN token key is empty** (`BUNNY_CDN_TOKEN_KEY`), so CDN video
  URLs are unsigned and hotlinkable. Unrelated to this release.
- **Series buffering on playback** is a *content* issue, not a code one:
  all 60 episodes stream from `dropbox_path` (no CDN), while the one movie
  on Backblaze gets Bunny CDN acceleration. Fix is to move episode files to
  the B2 bucket and populate `episodes.video_url`.
