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
use Modules\Content\app\Models\Vj;
use Modules\Frontend\app\Services\TopPicksRecommender;
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

        // Compute Top 10 Movies once and reuse: the top-ten rail uses the
        // full set, the vertical hero slider uses the top 5 (so its
        // "#X in Movies Today" rank badge is honest without doubling the
        // per-slide work the right banner does).
        $topMovies = app(TopPicksRecommender::class)->globalTopPicks(Movie::class, 10);

        return [
            // Movies
            'latestMovies'   => $movieBase()->orderByDesc('published_at')->take(10)->get(),
            'popularMovies'  => $movieBase()->orderByDesc('views_count')->take(10)->get(),
            'topMovies'      => $topMovies,
            // Upcoming — driven by the STATUS_UPCOMING flag, not a future
            // published_at (the old query was unsatisfiable because the
            // published() scope already forces published_at <= now).
            'upcomingMovies' => app(TopPicksRecommender::class)->upcoming(auth()->id(), 10),
            'recommendedMovies' => app(TopPicksRecommender::class)->smartShuffle(auth()->id(), 10),
            'specialsMovies' => $movieBase()->orderByDesc('published_at')->take(10)->get(),
            'freshMovies'    => app(TopPicksRecommender::class)->freshPicks(auth()->id(), 10),

            // Shows
            'latestShows'    => $showBase()->orderByDesc('published_at')->take(10)->get(),
            'popularShows'   => $showBase()->orderByDesc('views_count')->take(10)->get(),
            'topShows'       => app(TopPicksRecommender::class)->globalTopPicks(Show::class, 10),
            'recommendedShows' => $showBase()->inRandomOrder()->take(10)->get(),
            'internationalShows' => $showBase()->inRandomOrder()->take(10)->get(),

            // Hero
            'heroMovies'     => $movieBase()->orderByDesc('published_at')->take(3)->get(),
            'heroItems'      => $this->buildHero(),

            // Vertical slider — top 5 of the Top 10 Movies of the Day so
            // the "#X in Movies Today" badge on each slide is accurate.
            // loadAvg() pre-computes ratings_avg_stars in a single batch
            // query so vertical-banner doesn't N+1 a ratings()->avg() call
            // per slide.
            'verticalFeatured' => $topMovies->take(5)->loadAvg('ratings', 'stars'),

            // Tab slider — Top 10 Series of the Day: ranked by distinct 24h
            // viewers, cached on a per-date key so the shelf is stable within
            // the day and flips at midnight. Falls back to all-time popularity
            // when daily activity is thin. See TopPicksRecommender.
            'tabSeries' => app(TopPicksRecommender::class)->topSeriesOfTheDay(10),

            // Only on Streamit — premium/exclusive (movies with tier_required set)
            'exclusiveMovies' => Movie::published()
                ->with('genres')
                ->whereNotNull('tier_required')
                ->orderByDesc('published_at')
                ->take(8)
                ->get(),

            // Top Picks — personalised per viewer. Warm users get a genre/cast
            // affinity ranking; cold users and guests fall back to the global
            // weighted blend. See docs/plans/top-picks-personalization.md.
            'topPicks' => $this->resolveTopPicks(8),

            // Home Genres rail — genres with poster fallback via picsum seed
            'homeGenres' => Genre::withCount(['movies', 'shows'])
                ->orderByDesc('movies_count')
                ->take(10)
                ->get(),

            // Home VJs rail — narrators ranked by combined catalogue
            // size, but filtered to only VJs with at least one
            // *published* movie or show (otherwise the slider would
            // surface VJs whose entire catalogue is still draft).
            // Card thumbnails use Vj::featured_image_url, which falls
            // back through the VJ's most recent published title.
            'homeVjs' => Vj::query()
                ->where(function ($q) {
                    $q->whereHas('movies', fn ($mq) => $mq->published())
                      ->orWhereHas('shows',  fn ($sq) => $sq->published());
                })
                ->withCount([
                    'movies as movies_count' => fn ($q) => $q->published(),
                    'shows as shows_count' => fn ($q) => $q->published(),
                ])
                ->orderByRaw('(movies_count + shows_count) DESC')
                ->orderBy('id')
                ->take(12)
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
            'cast' => fn ($q) => $q->wherePivotIn('role', ['actor', 'actress'])->limit(3),
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

    /**
     * Route the Top Picks shelf through the personal recommender.
     * Rollout flag lets us fall back to the old random draw live if
     * the algorithm ships with a bug — flip without a redeploy.
     */
    private function resolveTopPicks(int $limit): Collection
    {
        if (!config('frontend.recommendations.enabled', true)) {
            return Movie::published()->with('genres')->inRandomOrder()->take($limit)->get();
        }

        $recommender = app(TopPicksRecommender::class);
        $uid = auth()->id();

        return $uid
            ? $recommender->forUser($uid, $limit)
            : $recommender->forGuest($limit);
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
            'watchLink'       => $e->frontendUrl($show),
            // For shows, remove-by-show_id wipes every episode's history,
            // otherwise a kept row would re-surface the show card on the
            // next render (composer dedupes by show).
            'removeType'      => 'show',
            'removeId'        => $show->id,
        ];
    }
}
