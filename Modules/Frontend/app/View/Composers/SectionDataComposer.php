<?php

namespace Modules\Frontend\app\View\Composers;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Genre;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Person;
use Modules\Content\app\Models\Show;
use Modules\Streaming\app\Models\WatchHistoryItem;
use Modules\Streaming\app\Models\WatchlistItem;

/**
 * Shares the collections that every section-template on the public frontend
 * needs, so the home pages, movie pages and show pages can all include the
 * same section partials without wiring data at every call site.
 *
 * The collections here are intentionally cheap: small takes, eager-loaded
 * genres, and a single query per slot. We cache per-request so including
 * multiple sections on one page only hits the DB once.
 */
class SectionDataComposer
{
    private static ?array $cache = null;
    private static ?array $perUserCache = null;

    public function compose(View $view): void
    {
        if (self::$cache === null) {
            self::$cache = $this->build();
        }

        foreach (self::$cache as $key => $value) {
            $view->with($key, $value);
        }

        // Per-user data that must NOT live in the static public cache —
        // otherwise cards on every page would show the first logged-in
        // user's watchlist state to everyone. Rebuilt once per request.
        if (self::$perUserCache === null) {
            self::$perUserCache = $this->buildPerUser();
        }
        foreach (self::$perUserCache as $key => $value) {
            $view->with($key, $value);
        }
    }

    /**
     * Lightweight lookup set so every card on the page can render its
     * "in watchlist / not in watchlist" state without N extra queries.
     * Keys are "<type>:<id>" where type is 'movie' | 'show' | 'episode'.
     * Empty array for guests.
     */
    private function buildPerUser(): array
    {
        $userId = auth()->id();
        if (!$userId) {
            return ['userWatchlistIndex' => []];
        }

        $rows = WatchlistItem::where('user_id', $userId)
            ->get(['watchable_type', 'watchable_id']);

        $typeMap = [
            (new Movie)->getMorphClass()   => 'movie',
            (new Show)->getMorphClass()    => 'show',
            (new Episode)->getMorphClass() => 'episode',
        ];

        $index = [];
        foreach ($rows as $row) {
            $kind = $typeMap[$row->watchable_type] ?? null;
            if ($kind) {
                $index[$kind . ':' . $row->watchable_id] = true;
            }
        }
        return ['userWatchlistIndex' => $index];
    }

    private function build(): array
    {
        $movieBase = fn () => Movie::published()->with('genres');
        $showBase = fn () => Show::published()->with('genres');

        return [
            // Movies
            'latestMovies'   => $movieBase()->orderByDesc('published_at')->take(10)->get(),
            'popularMovies'  => $movieBase()->orderByDesc('views_count')->take(10)->get(),
            'topMovies'      => $movieBase()->orderByDesc('rating')->orderByDesc('views_count')->take(10)->get(),
            'upcomingMovies' => $movieBase()->where('published_at', '>', now())->orderBy('published_at')->take(10)->get(),
            'recommendedMovies' => $movieBase()->inRandomOrder()->take(10)->get(),
            'specialsMovies' => $movieBase()->orderByDesc('published_at')->take(10)->get(),
            'freshMovies'    => $movieBase()->inRandomOrder()->take(10)->get(),

            // Shows
            'latestShows'    => $showBase()->orderByDesc('published_at')->take(10)->get(),
            'popularShows'   => $showBase()->orderByDesc('views_count')->take(10)->get(),
            'topShows'       => $showBase()->orderByDesc('rating')->orderByDesc('views_count')->take(10)->get(),
            'recommendedShows' => $showBase()->inRandomOrder()->take(10)->get(),
            'internationalShows' => $showBase()->inRandomOrder()->take(10)->get(),

            // Hero
            'heroMovies'     => $movieBase()->orderByDesc('published_at')->take(3)->get(),
            'heroItems'      => $this->buildHero(),

            // Vertical slider (editorial feature, movies only)
            'verticalFeatured' => Movie::published()
                ->with('genres')
                ->orderByDesc('views_count')
                ->orderByDesc('rating')
                ->take(5)
                ->get(),

            // Tab slider — Top 10 Series of the Day (shows + all seasons + episodes)
            'tabSeries' => Show::published()
                ->with(['seasons.episodes'])
                ->orderByDesc('views_count')
                ->orderByDesc('published_at')
                ->take(10)
                ->get(),

            // Only on Streamit — premium/exclusive (movies with tier_required set)
            'exclusiveMovies' => Movie::published()
                ->with('genres')
                ->whereNotNull('tier_required')
                ->orderByDesc('published_at')
                ->take(8)
                ->get(),

            // Top Picks — separate from topMovies, curated-feel random draw
            'topPicks' => Movie::published()
                ->with('genres')
                ->inRandomOrder()
                ->take(8)
                ->get(),

            // Home Genres rail — genres with poster fallback via picsum seed
            'homeGenres' => Genre::withCount(['movies', 'shows'])
                ->orderByDesc('movies_count')
                ->take(10)
                ->get(),

            // Your Favourite Personality — top cast by appearance count
            'favoritePersonalities' => Person::withCount(['movies', 'shows'])
                ->orderByDesc('movies_count')
                ->orderByDesc('shows_count')
                ->take(12)
                ->get(),

            'continueWatching' => $this->continueWatchingForUser(),
        ];
    }

    /**
     * Mixed featured collection for the OTT hero (3 movies + 3 shows).
     * Each item is tagged with `_isShow` so the blade can branch without
     * instance checks, and comes with genres/tags/cast already loaded.
     */
    private function buildHero()
    {
        $relations = [
            'genres',
            'tags',
            'cast' => fn ($q) => $q->wherePivot('role', 'actor')->limit(3),
        ];

        $movies = Movie::published()->with($relations)
            ->orderByDesc('views_count')
            ->orderByDesc('published_at')
            ->take(3)
            ->get()
            ->each(fn ($m) => $m->_isShow = false);

        $shows = Show::published()->with(array_merge($relations, ['seasons']))
            ->orderByDesc('views_count')
            ->orderByDesc('published_at')
            ->take(3)
            ->get()
            ->each(fn ($s) => $s->_isShow = true);

        // Interleave so the rail shows movie, show, movie, show, movie, show.
        $items = collect();
        $max = max($movies->count(), $shows->count());
        for ($i = 0; $i < $max; $i++) {
            if (isset($movies[$i])) $items->push($movies[$i]);
            if (isset($shows[$i]))  $items->push($shows[$i]);
        }

        return $items;
    }

    /**
     * Real Continue Watching row, per authenticated user.
     *
     * Contract:
     *   • Anonymous viewers get an empty collection — the section blade
     *     hides itself entirely in that case.
     *   • For signed-in users: up to 6 cards, ordered by most recent
     *     heartbeat. Completed rows are excluded.
     *   • Series are deduplicated: one card per show, and the card
     *     points at the latest episode the user was watching (most
     *     recent heartbeat wins).
     *
     * Each returned element is a normalised stdClass so the card
     * template doesn't need to branch on movie-vs-episode.
     */
    private function continueWatchingForUser(): Collection
    {
        $userId = auth()->id();
        if (!$userId) {
            return collect();
        }

        // Pull enough rows to dedupe across shows. 24 is generous —
        // unlikely a real user has more in-progress items than that
        // between heartbeats, but it gives room for a bingewatcher
        // with multiple episodes of several shows still open.
        $rows = WatchHistoryItem::where('user_id', $userId)
            ->where('completed', false)
            ->orderByDesc('watched_at')
            ->with(['watchable' => function (MorphTo $morphTo) {
                $morphTo->morphWith([
                    Movie::class   => ['genres'],
                    Episode::class => ['season.show'],
                ]);
            }])
            ->take(24)
            ->get();

        $cards = collect();
        $seenShows = [];

        foreach ($rows as $row) {
            $w = $row->watchable;
            if (!$w) continue;

            if ($w instanceof Movie) {
                $cards->push($this->buildMovieCard($row, $w));
            } elseif ($w instanceof Episode) {
                $showId = $w->season?->show_id;
                if (!$showId || in_array($showId, $seenShows, true)) {
                    continue;
                }
                $seenShows[] = $showId;
                $cards->push($this->buildEpisodeCard($row, $w));
            }

            if ($cards->count() >= 6) break;
        }

        return $cards;
    }

    /**
     * Minutes remaining. Prefers the heartbeat-reported duration
     * (real), falls back to `runtime_minutes` (admin-set). The fallback
     * assumes the user's position splits evenly — inaccurate, but
     * better than showing nothing.
     */
    private function minutesLeft(WatchHistoryItem $h, ?int $runtimeMinutes): int
    {
        if ($h->duration_seconds && $h->duration_seconds > $h->position_seconds) {
            return max(1, (int) ceil(($h->duration_seconds - $h->position_seconds) / 60));
        }
        if ($runtimeMinutes) {
            $pct = max(0, 100 - $h->progressPercent());
            return max(1, (int) ceil($runtimeMinutes * $pct / 100));
        }
        return 10;
    }

    private function buildMovieCard(WatchHistoryItem $h, Movie $m): object
    {
        return (object) [
            'imagePath'       => $m->backdrop_url ?: $m->poster_url ?: 'gameofhero.webp',
            'title'           => $m->title,
            'subtitle'        => $m->published_at?->format('M Y') ?? ($m->year ? (string) $m->year : ''),
            'progressPercent' => $h->progressPercent(),
            'minutesLeft'     => $this->minutesLeft($h, $m->runtime_minutes),
            'watchLink'       => route('frontend.watch', $m->slug),
            'removeType'      => 'movie',
            'removeId'        => $m->id,
        ];
    }

    private function buildEpisodeCard(WatchHistoryItem $h, Episode $e): object
    {
        $show = $e->season->show;
        $ep = 'S' . str_pad($e->season->number, 2, '0', STR_PAD_LEFT)
            . 'E' . str_pad($e->number, 2, '0', STR_PAD_LEFT);
        return (object) [
            'imagePath'       => $e->still_url ?: ($show->backdrop_url ?: $show->poster_url ?: 'vikings-portrait.webp'),
            'title'           => $show->title,
            'subtitle'        => $ep . ' · ' . $e->title,
            'progressPercent' => $h->progressPercent(),
            'minutesLeft'     => $this->minutesLeft($h, $e->runtime_minutes),
            'watchLink'       => route('frontend.episode', $e->id),
            // For shows, remove-by-show_id wipes every episode's history,
            // otherwise a kept row would re-surface the show card on the
            // next render (composer dedupes by show).
            'removeType'      => 'show',
            'removeId'        => $show->id,
        ];
    }
}
