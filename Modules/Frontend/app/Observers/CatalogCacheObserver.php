<?php

namespace Modules\Frontend\app\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Flushes content-list caches whenever a Show, Movie or Episode is
 * written or deleted, so newly-published content appears on home and
 * browse rails immediately instead of after the
 * `TopPicksRecommender` per-user TTL expires.
 *
 * The companion `PersonalisationCacheObserver` only flushes when a
 * user produces a watch / rating / watchlist signal — it doesn't
 * know about admin-side publishes. Without this class, an admin can
 * publish a series, see it linked from the notification (which
 * doesn't go through any cache), and still have it absent from the
 * personalised home rails for up to an hour. That's the
 * "underperformance" gap.
 *
 * Implementation note: we call `Cache::flush()` rather than
 * iterating user-by-user TopPicks keys. The flush is O(1) regardless
 * of user count, and since admin publishes are rare events (handful
 * per day, not per request), the marginal cost of re-warming
 * unrelated caches on the next page load is negligible. Iterating
 * per-user keys would scale linearly with users and need queueing
 * to avoid blocking the admin save response under file cache.
 *
 * Sessions are NOT affected — Laravel's default config puts sessions
 * on the SESSION_DRIVER store (typically file), distinct from the
 * CACHE_DRIVER store this method targets, so users stay logged in.
 * Rate-limiter counters do reset, which on this rare-event surface
 * is harmless.
 */
class CatalogCacheObserver
{
    public function created(Model $model): void
    {
        $this->flush();
    }

    public function updated(Model $model): void
    {
        // Only flush when the publication state actually changed —
        // updating an episode's description, runtime, or media URL
        // without flipping its status doesn't change what appears on
        // public lists, so cache stays fresh.
        if ($model->wasChanged(['status', 'published_at'])) {
            $this->flush();
        }
    }

    public function deleted(Model $model): void
    {
        $this->flush();
    }

    private function flush(): void
    {
        // Wrap so a transient cache outage during admin save can't
        // produce a 500 — content still saved successfully, lists
        // just stay stale until the next manual `optimize:clear`.
        try {
            Cache::flush();
        } catch (\Throwable $e) {
            // Intentionally swallow — see comment above.
        }
    }
}
