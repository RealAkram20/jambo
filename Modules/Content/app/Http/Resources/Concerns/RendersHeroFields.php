<?php

namespace Modules\Content\app\Http\Resources\Concerns;

/**
 * The fields a full-width banner draws that a poster card does not.
 *
 * Shared by MovieResource and ShowResource because the banner draws the same
 * things for both — `components/partials/hero-banner.blade.php` renders one
 * slide and branches on `_isShow` only for the badge and the runtime line.
 * Two copies of this logic would drift the first time the banner changed on
 * one of them.
 *
 * The home page has three banners, not one, and this trait now serves all of
 * them: the hero, the Top 10 Movies of the Day vertical slider, and the Top 10
 * Series of the Day tab slider. `hero()` and `banner()` are separate shapes
 * because the daily two draw strictly less — no Tags line, no Starring line —
 * and one more genre.
 */
trait RendersHeroFields
{
    /**
     * How many taxonomy terms the banner draws. Three, because the blade
     * slices every one of its three lists with `->take(3)`; sending more would
     * be bytes the app throws away on every first paint.
     */
    private const HERO_TAXONOMY_LIMIT = 3;

    /**
     * Four, and it is the daily movies banner's own number rather than a
     * rounder version of the hero's three: `components/partials/vertical-banner`
     * slices its one genre list with `->take(4)`. A shared "3" would have been
     * one chip short of the website on any movie carrying four genres, which
     * is a difference nobody would ever trace back to a constant.
     */
    private const BANNER_TAXONOMY_LIMIT = 4;

    /**
     * Where this slide came in the ranking. Set by `banner()`; meaningless,
     * and never emitted, in any other shape.
     */
    private int $rank = 0;

    /**
     * A genre or tag list, or an absent key when the relation was not loaded.
     *
     * `whenLoaded` rather than a lazy read: a banner that silently issues a
     * query per slide is the bug this shape was written to avoid, and an
     * absent key tells the app the truth — "not sent" — instead of an empty
     * array that reads as "this title has no genres".
     */
    private function taxonomy(string $relation, ?int $limit = null): mixed
    {
        $limit ??= self::HERO_TAXONOMY_LIMIT;

        return $this->whenLoaded($relation, fn () => $this->{$relation}
            ->take($limit)
            ->map(fn ($term) => [
                'slug' => $term->slug,
                'name' => $term->name,
            ])->values());
    }

    /**
     * The two fields that make a banner slide a *ranked* banner slide.
     *
     * `rank` is positional, so it is handed in rather than read off the model:
     * a title is not "number three", it is third in this list on this day.
     *
     * `ranked_today` is the honest half. Both daily shelves backfill from
     * all-time popularity when a day is quiet (TopPicksRecommender), and the
     * website will not print a rank a title did not earn — it says "Popular on
     * Jambo" instead. The app cannot tell the two apart from position alone,
     * so the server says which it is. The viewer count itself is deliberately
     * not sent: the app draws a label, not a number, and the count is business
     * data a catalogue response has no reason to publish.
     */
    private function bannerFields(): array
    {
        return [
            'rank' => $this->rank,
            'ranked_today' => ((int) ($this->resource->recent_viewers ?? 0)) > 0,
        ];
    }

    /**
     * The "Starring" line.
     *
     * Deduplicated by id before slicing, which is the blade's own fix and the
     * reason it is worth repeating here: a pivot row exists per role, so a
     * person credited as both actor and director appeared in the website's
     * hero twice before `unique('id')` was added to it.
     */
    private function heroCast(): mixed
    {
        return $this->whenLoaded('cast', fn () => $this->cast
            ->unique('id')
            ->take(self::HERO_TAXONOMY_LIMIT)
            ->map(fn ($person) => [
                'slug' => $person->slug,
                'name' => $person->full_name,
            ])->values());
    }

    /**
     * The mean episode length, in whole minutes, or null.
     *
     * Reads only the attribute `loadAvg('episodes', 'runtime_minutes')` puts
     * on the model, and never falls back to a query: `Show::episodes()` is a
     * hasManyThrough over every episode of the series, so a lazy read here
     * would be the exact N+1 this shape was written to remove. A caller that
     * did not load it gets null, and the banner draws no clock line.
     *
     * Rounded to a whole minute because the line reads "45m"; a mean of 44.5
     * is not a runtime anybody recognises.
     */
    private function meanEpisodeRuntime(): ?int
    {
        $avg = $this->resource->getAttributes()['episodes_avg_runtime_minutes'] ?? null;

        return $avg === null ? null : (int) round((float) $avg);
    }
}
