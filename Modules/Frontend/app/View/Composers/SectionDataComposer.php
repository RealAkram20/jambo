<?php

namespace Modules\Frontend\app\View\Composers;

use Illuminate\View\View;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Show;

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

    public function compose(View $view): void
    {
        if (self::$cache === null) {
            self::$cache = $this->build();
        }

        foreach (self::$cache as $key => $value) {
            $view->with($key, $value);
        }
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

            'continueWatching' => $this->continueWatchingFallback(),
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
     * Until Phase 5 (Streaming) wires real watch history, surface the most
     * recent published movies as a stand-in. When the history table is live,
     * swap this for a per-user watch_history query.
     */
    private function continueWatchingFallback()
    {
        return Movie::published()->with('genres')->orderByDesc('updated_at')->take(6)->get();
    }
}
