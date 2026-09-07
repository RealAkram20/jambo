<?php

namespace Modules\Content\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

/**
 * One hand-picked title in the OTT homepage hero.
 *
 * The admin list at /admin/featured owns the order; the homepage reads it
 * through heroItems() and renders the rows in `sort_order`.
 *
 * Two rules make this safe to hand to a non-technical admin:
 *
 *   1. **Unpublished and deleted titles never reach the homepage.** The
 *      admin screen still shows them, flagged, so the reason a title is
 *      missing from the site is visible where it can be fixed.
 *   2. **An empty list is not an empty homepage.** When nothing is
 *      featured, or nothing featured is currently publishable, the caller
 *      falls back to the automatic view-count pick that shipped before.
 */
class FeaturedItem extends Model
{
    protected $fillable = ['featurable_type', 'featurable_id', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    public function featurable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * The featured rows an admin should see: every row in display order,
     * each with its title loaded, including ones that are unpublished or
     * whose title has been deleted. The screen labels those; it must not
     * hide them, or an admin cannot tell why the homepage looks wrong.
     */
    public static function forAdmin(): Collection
    {
        return static::ordered()->with('featurable')->get();
    }

    /**
     * The titles the homepage hero should actually render, in order.
     *
     * Returns an empty collection when nothing qualifies, which the caller
     * treats as "fall back to the automatic pick". Eager-loads exactly what
     * the hero partials read, so a ten-item rail is a fixed number of
     * queries rather than one per slide.
     */
    public static function heroItems(): Collection
    {
        $rows = static::ordered()->get();

        if ($rows->isEmpty()) {
            return collect();
        }

        $relations = [
            'genres',
            'tags',
            'cast' => fn ($q) => $q->wherePivotIn('role', ['actor', 'actress'])->limit(3),
        ];

        $movieIds = $rows->where('featurable_type', (new Movie)->getMorphClass())->pluck('featurable_id');
        $showIds = $rows->where('featurable_type', (new Show)->getMorphClass())->pluck('featurable_id');

        $movies = $movieIds->isEmpty()
            ? collect()
            : Movie::published()->with($relations)->whereIn('id', $movieIds)->get()->keyBy('id');

        // Shows also need seasons: the hero badge counts them, and the
        // published() scope on a show depends on having playable episodes.
        $shows = $showIds->isEmpty()
            ? collect()
            : Show::published()->with(array_merge($relations, ['seasons']))->whereIn('id', $showIds)->get()->keyBy('id');

        return $rows->map(function (self $row) use ($movies, $shows) {
            $isShow = $row->featurable_type === (new Show)->getMorphClass();
            $item = $isShow ? $shows->get($row->featurable_id) : $movies->get($row->featurable_id);

            if (!$item) {
                return null; // unpublished, or the title was deleted
            }

            // The hero partials branch on this flag rather than on class.
            $item->_isShow = $isShow;

            return $item;
        })->filter()->values();
    }

    /**
     * Drop rows whose title no longer exists. Called after the admin list
     * loads, so a deleted movie stops occupying a hero slot without anyone
     * having to notice and remove it by hand.
     */
    public static function prune(): int
    {
        $stale = static::ordered()->with('featurable')->get()
            ->filter(fn (self $row) => $row->featurable === null);

        if ($stale->isEmpty()) {
            return 0;
        }

        return static::whereKey($stale->pluck('id'))->delete();
    }
}
