<?php

namespace Modules\Frontend\app\Services;

use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Show;

/**
 * The "see all" pages behind the homepage rails.
 *
 * Each rail names a query, an ordering, and — for the personalised ones — the
 * items to pin at the front so the archive opens with what the viewer just
 * tapped past.
 *
 * Extracted from `FrontendController::railArchives()` on 2026-09-08 so
 * `GET /api/v1/collections/{rail}` answers with the same list the website
 * shows. A second definition here would mean the app's "see all" and the
 * site's "see all" quietly diverged.
 *
 * **The pinned rails are MySQL-only.** They order with `FIELD()`, which
 * MariaDB has and SQLite does not, so they cannot execute under the test
 * suite. Production is MariaDB, so this is a testing limitation rather than a
 * live defect — but a move to portable SQL would change a live ranking and is
 * a decision, not a cleanup.
 */
class RailArchiveCatalog
{
    /** Rails whose ordering uses FIELD() and therefore needs MySQL/MariaDB. */
    public const PINNED_RAILS = ['top-picks', 'smart-shuffle', 'fresh-picks'];

    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        $recommender = fn () => app(TopPicksRecommender::class);

        return [
            'smart-shuffle' => [
                'title' => __('sectionTitle.smart_shuffle'),
                'pinned' => fn () => $recommender()->smartShuffle(auth()->id()),
                'query' => fn () => Movie::published()->with('genres'),
                'order' => fn ($q) => $q->orderByDesc('views_count')->orderByDesc('created_at'),
            ],
            'only-on-streamit' => [
                'title' => __('sectionTitle.only_on_streamit'),
                'query' => fn () => Movie::published()->with('genres')->whereNotNull('tier_required'),
                'order' => fn ($q) => $q->orderByDesc('created_at'),
            ],
            'latest-movies' => [
                'title' => __('sectionTitle.latest_movies'),
                'query' => fn () => Movie::published()->with('genres'),
                'order' => fn ($q) => $q->orderByDesc('created_at'),
            ],
            'popular-series' => [
                'title' => __('sectionTitle.popular_show'),
                'type' => 'show',
                'query' => fn () => Show::published()->with('genres'),
                'order' => fn ($q) => $q->orderByDesc('views_count')->orderByDesc('created_at'),
            ],
            'latest-series' => [
                'title' => __('sectionTitle.latest_series'),
                'type' => 'show',
                'query' => fn () => Show::published()->with('genres'),
                'order' => fn ($q) => $q->orderByDesc('created_at'),
            ],
        ];
    }

    public function has(string $rail): bool
    {
        return array_key_exists($rail, $this->all());
    }

    /** @return array<string, mixed>|null */
    public function definition(string $rail): ?array
    {
        return $this->all()[$rail] ?? null;
    }

    public function title(string $rail): ?string
    {
        return $this->definition($rail)['title'] ?? null;
    }

    /** 'movie' or 'show' — what the archive is a list of. */
    public function type(string $rail): string
    {
        return $this->definition($rail)['type'] ?? 'movie';
    }

    /** The keys, for a client that wants to know which archives exist. */
    public function keys(): array
    {
        return array_keys($this->all());
    }

    /**
     * Build the query for a rail, with its pinned items ordered to the front.
     *
     * FIELD() returns 0 for ids not in the list, so "FIELD(id, …) = 0" sorts
     * pinned rows (false → 0) ahead of everything else, and the second FIELD()
     * preserves the recommender's ranking among them.
     */
    public function query(string $rail)
    {
        $definition = $this->definition($rail);

        if (! $definition) {
            return null;
        }

        $pinned = isset($definition['pinned']) ? ($definition['pinned'])() : collect();

        $pinnedIds = $pinned->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $query = ($definition['query'])();

        if ($pinnedIds) {
            $list = implode(',', $pinnedIds);
            $query->orderByRaw("FIELD(id, {$list}) = 0")
                ->orderByRaw("FIELD(id, {$list})");
        }

        ($definition['order'])($query);

        return $query;
    }
}
