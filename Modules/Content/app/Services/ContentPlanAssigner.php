<?php

namespace Modules\Content\app\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Writes `tier_required` across a set of content rows.
 *
 * Exists so the three bulk endpoints (movies, series, a season's episodes)
 * share one behaviour, and so the choice between "save each model" and "one
 * mass UPDATE" is made deliberately rather than per controller:
 *
 *   • assign()      — for the rows the admin actually selected. Saves each
 *                     model so the `updating`/`updated` events fire, which is
 *                     what stamps `updated_by` and appends the
 *                     content_activity_log row. Paywalling a title is a
 *                     commercial decision; it should be attributable.
 *                     Bounded by the page size, so the per-row cost is fine.
 *
 *   • cascade()     — for rows dragged along by a parent (a series' episodes),
 *                     where the count is unbounded. One UPDATE, no per-row
 *                     log; the parent's own activity row is the audit record
 *                     for the change.
 *
 * Neither path touches the announcer: a plan change isn't a publish event and
 * must not re-notify an audience.
 */
final class ContentPlanAssigner
{
    /**
     * Apply $tier to each loaded model, returning how many were acted on.
     *
     * Rows already on $tier are skipped by Eloquent's dirty check, so no
     * spurious activity rows — but they still count as "acted on", because
     * the admin selected them and the reported total should match what they
     * ticked.
     *
     * @param  Collection<int, Model>  $models
     */
    public static function assign(Collection $models, ?string $tier): int
    {
        foreach ($models as $model) {
            $model->tier_required = $tier;
            $model->save();
        }

        return $models->count();
    }

    /**
     * Mass-apply $tier to everything matching $query, returning the number of
     * rows in scope.
     *
     * The count is taken before the write rather than from the UPDATE's
     * affected-rows: MySQL reports only rows whose value actually changed, so
     * re-applying a plan a run already carries would report "0 episodes" for
     * work the admin can plainly see was in scope.
     */
    public static function cascade(Builder $query, ?string $tier): int
    {
        $total = (clone $query)->count();

        if ($total > 0) {
            // Eloquent stamps updated_at for us; updated_by is set explicitly
            // because no model events fire on a mass update.
            $query->update([
                'tier_required' => $tier,
                'updated_by' => auth()->id(),
            ]);
        }

        return $total;
    }
}
