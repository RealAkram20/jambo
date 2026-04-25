<?php

namespace Modules\Content\app\Models\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Wipe every polymorphic reference to a content row before/while
 * it's being deleted, so the rows that point at it (watchlist,
 * watch_history, ratings, reviews, comments) don't get left behind
 * as orphans. Shared between Movie, Show, and Episode.
 *
 * Cascading FK deletes (e.g. episodes vanishing because their
 * parent season was deleted) do NOT fire model events, so the
 * Show booted() handler has to clean its descendant episodes' rows
 * explicitly before the show row goes away.
 */
trait CleansContentMorphsOnDelete
{
    /**
     * @param  array<int>|int  $ids
     */
    protected static function cleanContentMorphsFor(string $type, $ids): void
    {
        $ids = is_array($ids) ? array_values($ids) : [$ids];
        if (empty($ids)) {
            return;
        }

        $morphTables = [
            'watchlist_items' => ['watchable_type',   'watchable_id'],
            'watch_history'   => ['watchable_type',   'watchable_id'],
            'ratings'         => ['ratable_type',     'ratable_id'],
            'reviews'         => ['reviewable_type',  'reviewable_id'],
            'comments'        => ['commentable_type', 'commentable_id'],
        ];

        foreach ($morphTables as $table => [$typeCol, $idCol]) {
            DB::table($table)
                ->where($typeCol, $type)
                ->whereIn($idCol, $ids)
                ->delete();
        }
    }
}
