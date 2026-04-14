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

            // Mixed
            'heroMovies'     => $movieBase()->orderByDesc('published_at')->take(3)->get(),
            'continueWatching' => $this->continueWatchingFallback(),
        ];
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
