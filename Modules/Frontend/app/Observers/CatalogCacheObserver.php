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
 * Full architecture, including the rule that mutations MUST go
 * through Eloquent (not query-builder mass updates) for this to
 * fire, is in docs/architecture/content-cache-invalidation.md.
 * The Show / Movie / Episode model docblocks carry a copy of that
 * rule so anyone editing the models sees it immediately.
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
