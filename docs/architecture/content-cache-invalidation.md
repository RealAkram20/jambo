# Content cache invalidation

This doc explains how Jambo keeps the personalised home/browse rails fresh
when admins publish, edit or delete movies, series and episodes. Read this
**before** modifying anything that mutates `Movie` / `Show` / `Episode`
publication state, and especially before adding new caches that hold
content lists.

## TL;DR for contributors

When you mutate publication state (`status` or `published_at`) on a
`Movie`, `Show` or `Episode`, **use Eloquent**:

```php
$movie->update(['status' => 'published', 'published_at' => now()]); // ✓
$movie->fill([...])->save();                                         // ✓
$movie->save();                                                       // ✓

Movie::query()->where('id', $id)->update(['status' => 'published']); // ✗ skips events
DB::table('movies')->where('id', $id)->update([...]);                // ✗ skips events
```

Eloquent fires the `created` / `updated` / `deleted` model events that
[`CatalogCacheObserver`](../../Modules/Frontend/app/Observers/CatalogCacheObserver.php)
listens to. Query-builder and raw-DB updates **silently bypass them**.
The cache then keeps serving yesterday's content list to every user
until TTL expiry, and admins waste an hour wondering why their shiny
new release isn't on the home page.

The model docblocks for [Movie](../../Modules/Content/app/Models/Movie.php),
[Show](../../Modules/Content/app/Models/Show.php) and
[Episode](../../Modules/Content/app/Models/Episode.php) carry a copy of
this rule too.

## Why caching is here at all

Personalised rails are expensive to compute. The
[`TopPicksRecommender`](../../Modules/Frontend/app/Services/TopPicksRecommender.php)
service runs per-user genre/cast affinity scoring against the entire
movie + show pool. At 100k catalog rows × every page load × every user,
that would crush the database. So results are cached **per user** with a
TTL of 30–60 minutes (configurable via `frontend.recommendations.cache_ttl_user`).

Without invalidation, this trade-off bites every time admin content
changes — TTL expiry is the slowest possible path to freshness, and
"publish a series, can't see it on home for an hour" is a real perception
problem (it caused real users to think the catalog was empty during the
1.7.x debugging window).

## The cache layers

These are every persistent cache that holds content lists. New caches
should be added to this list.

| Key | Owner | Holds | TTL | Invalidation hook |
|---|---|---|---|---|
| `user:{id}:top_picks:v1` | `TopPicksRecommender::forUser` | per-user Top Picks rail | `cache_ttl_user` (3600s default) | `CatalogCacheObserver` (admin write) + `PersonalisationCacheObserver` (user signal) |
| `user:{id}:smart_shuffle:v1` | `TopPicksRecommender::smartShuffle` | per-user Smart Shuffle | `smart_shuffle.cache_ttl_user` (1800s) | same |
| `user:{id}:fresh_picks:v1` | `TopPicksRecommender::freshPicks` | per-user Fresh Picks | configured | same |
| `user:{id}:upcoming:v1` | `TopPicksRecommender::upcoming` | per-user Upcoming rail | configured | same |
| `topPicks:guest:v1` | `TopPicksRecommender::forGuest` | anonymous-visitor Top Picks | `cache_ttl_guest` (1800s) | `CatalogCacheObserver` only |
| `smart_shuffle:guest:v1` | `TopPicksRecommender::smartShuffle(null)` | anon Smart Shuffle | configured | same |
| `fresh_picks:guest:v1` | `TopPicksRecommender::freshPicks(null)` | anon Fresh Picks | configured | same |
| `upcoming:guest:v1` | `TopPicksRecommender::upcoming(null)` | anon Upcoming | configured | same |
| `tab_series_of_the_day:{date}:v1` | `TopPicksRecommender` daily highlight | "series of the day" featured slot | end of day | `CatalogCacheObserver` |
| `seo.sitemap.xml` | [`SitemapController`](../../Modules/Seo/app/Http/Controllers/Public/SitemapController.php) | rendered sitemap XML | 6h | `CatalogCacheObserver` |

What is NOT a persistent cache:

- [`SectionDataComposer`](../../Modules/Frontend/app/View/Composers/SectionDataComposer.php)
  has a `private static $cache` — but that's a per-PHP-process memo to
  dedupe queries within one request. It dies with the request, never
  goes stale.
- Direct `Show::published()` / `Movie::published()` queries in
  controllers and other composers run fresh every request — no cache,
  no staleness.

## The invalidation hooks

There are two observers that flush content caches, scoped to different
trigger surfaces:

### `CatalogCacheObserver` (admin-write surface)

[Modules/Frontend/app/Observers/CatalogCacheObserver.php](../../Modules/Frontend/app/Observers/CatalogCacheObserver.php).
Attached to `Show`, `Movie`, `Episode` in
[`FrontendServiceProvider::registerCatalogCacheObservers`](../../Modules/Frontend/app/Providers/FrontendServiceProvider.php).

Triggers:

- `created` — flushes (a brand-new published row should be visible).
- `updated` — flushes **only when** `status` or `published_at` actually
  changed (`$model->wasChanged([...])`). Avoids cache thrash when admin
  edits unrelated fields like description, runtime, or cast.
- `deleted` — flushes (a removed row should disappear from rails).

Implementation: calls `Cache::flush()`. This is intentional, see
"Why Cache::flush() and not surgical key forgetting" below.

### `PersonalisationCacheObserver` (user-signal surface)

[Modules/Frontend/app/Observers/PersonalisationCacheObserver.php](../../Modules/Frontend/app/Observers/PersonalisationCacheObserver.php).
Attached to `WatchHistoryItem`, `Rating`, `WatchlistItem` in
[`FrontendServiceProvider::registerPersonalisationObservers`](../../Modules/Frontend/app/Providers/FrontendServiceProvider.php).

Triggers when a user watches, rates, or adds to watchlist. The
recommender's per-user shelves are ranked off these signals — without
this hook a user could rate a horror movie 5★ and still see the same
genre-blind Top Picks rail until TTL expiry. Surgically forgets the
four `user:{id}:*` keys for the affected user only (no global flush —
one user's signal shouldn't cost everyone else a cache rebuild).

## Why `Cache::flush()` and not surgical key forgetting

Tempting to flush only the TopPicks-related keys. Three reasons we don't:

1. **Per-user iteration is O(N)**. Forgetting four keys per user means
   `users_count × 4` cache deletes. On the file driver that's tens of
   ms per delete — at 10k users, the admin save would block for several
   seconds, or we'd need a queued job. `Cache::flush()` is O(1).

2. **It's already a list of N+ keys today**, and it grows whenever
   someone adds a new cache. Surgical flushing means tracking every new
   cache that gets added (here, in PRs, by future contributors). Flush
   means we get future caches for free as long as they live in the
   default cache store.

3. **The "unintended side effects" are negligible.** Sessions live on
   the SESSION_DRIVER store, separate from CACHE_DRIVER (default Laravel
   config) — users do NOT log out. Rate-limiter counters reset, which
   gives users a clean slate on a rare admin action — harmless. Settings
   caches rebuild on the next read in microseconds.

When this trade-off would flip:

- If admin publishes ever exceed ~dozens-per-minute (e.g., a bulk import
  flow), the broad flush starts to hurt next-request latency. Switch to
  cache versioning (one increment-on-write key the recommender appends
  to its keys) for O(1) surgical invalidation. The TopPicksRecommender
  already has `:v1` suffixes — they were designed with this migration
  in mind.

- If the cache store grows to hold something that's expensive to
  rebuild AND should survive a publish (none today). Add it to the
  exclusion list explicitly.

## Operator escape hatches

Run any of these on the VPS if cached content lists ever look stale and
you can't wait for the observer to do its job:

```bash
# Targeted — drops the application cache only.
sudo -u jambo2820 php artisan cache:clear

# Heavier — drops application + config + route + view + event caches.
sudo -u jambo2820 php artisan optimize:clear
```

`optimize:clear` is what fixed yesterday's "new series not showing"
incident before 1.7.7 shipped the automated invalidation.

## Diagnosing future "new content not appearing" reports

Walk this checklist:

1. **Is the content actually published?** Check `status='published'`
   AND `published_at <= now()` in the DB. UI status pickers translate to
   these columns; if `published_at` is null the row never passes
   `scopePublished`.

2. **Does the show have a published episode?** `Show::scopePublished`
   requires at least one. A series with only draft/upcoming episodes
   correctly stays invisible on rails — that's not a cache bug, that's
   the dead-end-click guard.

3. **Did the publish go through Eloquent?** Check the controller /
   command that did the write. If it used `Model::query()->update()`,
   the observer never fired — that's the bug; refactor to Eloquent.

4. **Is the observer registered?** `tinker` →
   `Modules\Content\app\Models\Show::getEventDispatcher()->getListeners('eloquent.saved: Modules\Content\app\Models\Show')`
   should include `CatalogCacheObserver@saved`. If empty,
   `FrontendServiceProvider` isn't booting (check
   `modules_statuses.json`).

5. **Is `Cache::flush()` actually working?** `tinker` →
   `Cache::put('probe', 'x'); Cache::flush(); Cache::get('probe');` should
   return `null`. If it returns `'x'`, the cache driver is misconfigured
   (rare, but check `.env CACHE_DRIVER`).

## File map

| File | Role |
|---|---|
| [`Modules/Frontend/app/Observers/CatalogCacheObserver.php`](../../Modules/Frontend/app/Observers/CatalogCacheObserver.php) | The admin-write invalidation hook (this doc's main subject) |
| [`Modules/Frontend/app/Observers/PersonalisationCacheObserver.php`](../../Modules/Frontend/app/Observers/PersonalisationCacheObserver.php) | The user-signal invalidation hook |
| [`Modules/Frontend/app/Services/TopPicksRecommender.php`](../../Modules/Frontend/app/Services/TopPicksRecommender.php) | The cache producer — keys, TTLs, computation |
| [`Modules/Frontend/app/Providers/FrontendServiceProvider.php`](../../Modules/Frontend/app/Providers/FrontendServiceProvider.php) | Boots both observers |
| [`Modules/Content/app/Models/Movie.php`](../../Modules/Content/app/Models/Movie.php) | Carries the contributor warning in its docblock |
| [`Modules/Content/app/Models/Show.php`](../../Modules/Content/app/Models/Show.php) | Same |
| [`Modules/Content/app/Models/Episode.php`](../../Modules/Content/app/Models/Episode.php) | Same |
| [`Modules/Seo/app/Http/Controllers/Public/SitemapController.php`](../../Modules/Seo/app/Http/Controllers/Public/SitemapController.php) | Sitemap cache (also flushed by `CatalogCacheObserver` as a side benefit) |
