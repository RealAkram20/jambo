<?php

namespace Modules\Frontend\app\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Modules\Frontend\app\Services\TopPicksRecommender;

/**
 * Flushes the per-user Top Picks cache whenever a signal row the
 * algorithm reads (watch_history, ratings, watchlist_items) is
 * written or deleted. Attached generically so the same class works
 * for every signal model — each one has a `user_id` column.
 */
class PersonalisationCacheObserver
{
    public function saved(Model $model): void
    {
        $this->forget($model);
    }

    public function deleted(Model $model): void
    {
        $this->forget($model);
    }

    private function forget(Model $model): void
    {
        $userId = $model->user_id ?? null;
        if (!$userId) {
            return;
        }

        // Every personalised shelf is ranked off the same signal tables
        // (watch_history, ratings, watchlist), so one write should
        // refresh all of them. Cheaper to flush four keys than to let
        // any shelf sit with a stale ranking until its TTL.
        $prefix = TopPicksRecommender::CACHE_KEY_USER_PREFIX . $userId;
        Cache::forget($prefix . TopPicksRecommender::CACHE_KEY_USER_SUFFIX);
        Cache::forget($prefix . TopPicksRecommender::CACHE_KEY_SMART_SHUFFLE_USER_SUFFIX);
        Cache::forget($prefix . TopPicksRecommender::CACHE_KEY_FRESH_PICKS_USER_SUFFIX);
        Cache::forget($prefix . TopPicksRecommender::CACHE_KEY_UPCOMING_USER_SUFFIX);
    }
}
