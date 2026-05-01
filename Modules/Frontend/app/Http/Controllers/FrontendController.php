<?php

namespace Modules\Frontend\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Show;
use Modules\Content\app\Models\Category;
use Modules\Content\app\Models\Genre;
use Modules\Content\app\Models\Tag;
use Modules\Content\app\Models\Person;
use Modules\Content\app\Models\Vj;
use Modules\Streaming\app\Models\WatchHistoryItem;
use Modules\Streaming\app\Models\WatchlistItem;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Comment;
use Modules\Content\app\Models\Review;
use Modules\Payments\app\Models\PaymentOrder;
use Modules\Subscriptions\app\Models\SubscriptionTier;
use Modules\Subscriptions\app\Models\UserSubscription;
use Modules\Frontend\app\Services\TopPicksRecommender;
use Modules\Pages\app\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FrontendController extends Controller
{
    /**
     * Full-page search results — the form's submit target. Renders a
     * grid of matching movies + shows using the same card-style
     * component used elsewhere on the frontend, so the page feels like
     * a natural extension of /movie or /series rather than a one-off.
     *
     * Returns even when $q is empty / too short — the view shows an
     * "enter at least 2 characters" hint in that case so users who
     * land on /search by mistake get something coherent instead of a
     * blank page.
     */
    public function searchPage(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $movies = collect();
        $shows = collect();

        if (strlen($q) >= 2) {
            $like = '%' . $q . '%';

            $movies = Movie::published()
                ->with('genres')
                ->where(fn ($query) => $query->where('title', 'like', $like)->orWhere('synopsis', 'like', $like))
                ->orderByDesc('published_at')
                ->take(48)
                ->get();

            $shows = Show::published()
                ->with('genres')
                ->where(fn ($query) => $query->where('title', 'like', $like)->orWhere('synopsis', 'like', $like))
                ->orderByDesc('published_at')
                ->take(48)
                ->get();
        }

        return view('frontend::Pages.MainPages.search-page', compact('q', 'movies', 'shows'));
    }

    /**
     * Live-search suggestions for the header dropdown. Same shape as
     * before — kept stable so the dropdown JS doesn't need to know
     * about the new full-page route.
     */
    public function searchSuggest(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q'));
        if (strlen($q) < 2) {
            return response()->json(['movies' => [], 'shows' => []]);
        }

        $like = '%' . $q . '%';

        $movies = Movie::published()
            ->where(fn ($query) => $query->where('title', 'like', $like)->orWhere('synopsis', 'like', $like))
            ->take(5)
            ->get(['id', 'title', 'slug', 'poster_url', 'year'])
            ->map(fn ($m) => [
                'id' => $m->id,
                'title' => $m->title,
                'poster' => $m->poster_url,
                'year' => $m->year,
                'type' => 'Movie',
                'url' => route('frontend.movie_detail', $m->slug),
            ]);

        $shows = Show::published()
            ->where(fn ($query) => $query->where('title', 'like', $like)->orWhere('synopsis', 'like', $like))
            ->take(5)
            ->get(['id', 'title', 'slug', 'poster_url', 'year'])
            ->map(fn ($s) => [
                'id' => $s->id,
                'title' => $s->title,
                'poster' => $s->poster_url,
                'year' => $s->year,
                'type' => 'Series',
                'url' => route('frontend.series_detail', $s->slug),
            ]);

        return response()->json(['movies' => $movies, 'shows' => $shows]);
    }

    //main pages
    public function index()
    {
        $featuredMovies = Movie::published()
            ->orderByDesc('published_at')
            ->take(3)
            ->get();

        $latestMovies = Movie::published()
            ->with('genres')
            ->orderByDesc('published_at')
            ->take(12)
            ->get();

        $popularShows = Show::published()
            ->with('genres')
            ->orderByDesc('published_at')
            ->take(12)
            ->get();

        // Strict upcoming feed (movies + shows mixed, soonest-first).
        // Passes through even when empty — the section partial hides
        // itself so we don't render a slider with no content or, worse,
        // fall back to published titles (which was the prior bug: the
        // "Upcoming" slider silently served $latestMovies when no titles
        // had status=upcoming).
        $upcomingItems = app(TopPicksRecommender::class)
            ->upcomingListing(0, 12)['items'];

        return view('frontend::Pages.MainPages.index-page', compact(
            'featuredMovies',
            'latestMovies',
            'popularShows',
            'upcomingItems',
        ));
    }

    public function ott()
    {
        $featuredMovies = Movie::published()
            ->orderByDesc('published_at')
            ->take(3)
            ->get();

        $latestMovies = Movie::published()
            ->with('genres')
            ->orderByDesc('published_at')
            ->take(12)
            ->get();

        $popularShows = Show::published()
            ->with('genres')
            ->orderByDesc('published_at')
            ->take(12)
            ->get();

        $upcomingItems = app(TopPicksRecommender::class)
            ->upcomingListing(0, 12)['items'];

        return view('frontend::Pages.MainPages.ott-page', compact(
            'featuredMovies',
            'latestMovies',
            'popularShows',
            'upcomingItems',
        ));
    }

    public function movie()
    {
        $featuredMovies = Movie::published()
            ->orderByDesc('published_at')
            ->take(3)
            ->get();

        // Top 5 VJs by catalogue size — most active narrators first.
        // Additional VJs are fetched via the load-more endpoint below
        // so the initial payload stays small.
        $vjs = $this->topVjsForPage(0, 5);
        $vjsTotal = Vj::whereHas('movies', fn ($q) => $q->published())->count();

        return view('frontend::Pages.MainPages.movies-page', compact('featuredMovies', 'vjs', 'vjsTotal'));
    }

    /**
     * Dedicated listing for upcoming titles (movies + shows combined).
     * Initial render shows the first page; "Load More" pulls the rest
     * via the AJAX endpoint below.
     */
    public function upcomingPage(): \Illuminate\Contracts\View\View
    {
        $pageSize = 20;
        $listing = app(TopPicksRecommender::class)->upcomingListing(0, $pageSize);

        // Hero mirrors the /movie banner — top 3 soonest releases
        // drawn from the listing we already fetched so we don't hit
        // the DB twice. Banner hides itself when the catalog is empty.
        $featured = $listing['items']->take(3);

        return view('frontend::Pages.MainPages.upcoming-page', [
            'items' => $listing['items'],
            'total' => $listing['total'],
            'hasMore' => $listing['hasMore'],
            'pageSize' => $pageSize,
            'featured' => $featured,
        ]);
    }

    /**
     * AJAX endpoint — returns the next slice of upcoming cards as
     * rendered HTML plus an X-Has-More header so the client knows
     * when to hide the Load More button.
     */
    public function upcomingLoadMore(Request $request): \Illuminate\Http\Response
    {
        $offset = max(0, (int) $request->query('offset', 0));
        $limit = min(50, max(1, (int) $request->query('limit', 20)));

        $listing = app(TopPicksRecommender::class)->upcomingListing($offset, $limit);

        $html = view('frontend::components.partials.upcoming-cards', [
            'items' => $listing['items'],
        ])->render();

        return response($html)->header('X-Has-More', $listing['hasMore'] ? '1' : '0');
    }

    /**
     * AJAX endpoint — returns the next slice of VJ carousels as HTML
     * so the JS "Load More" button on /movie can append without
     * duplicating the card template in JavaScript.
     */
    public function moreVjsForMoviesPage(Request $request): \Illuminate\Http\Response
    {
        $offset = max(0, (int) $request->query('offset', 0));
        $limit  = min(10, max(1, (int) $request->query('limit', 5)));

        $vjs = $this->topVjsForPage($offset, $limit);

        $total = Vj::whereHas('movies', fn ($q) => $q->published())->count();
        $hasMore = ($offset + $vjs->count()) < $total;

        $html = '';
        foreach ($vjs as $vj) {
            // The view variable is `items`, not `movies` — mismatch
            // here was the root of an "Undefined variable $items"
            // 500 every time someone clicked Load More on /movie.
            // The three sibling load-more endpoints (genreVjs,
            // genreVjsShows, moreVjsForShowsPage) already do this
            // correctly; this one had drifted.
            $html .= view('frontend::components.sections.vj-carousel', [
                'vj' => $vj,
                'items' => $vj->movies,
                'contentKind' => 'movie',
            ])->render();
        }

        return response($html)->header('X-Has-More', $hasMore ? '1' : '0');
    }

    /**
     * Shared loader so /movie's initial render and the load-more
     * endpoint walk the exact same ordering.
     */
    private function topVjsForPage(int $offset, int $limit)
    {
        return Vj::whereHas('movies', fn ($q) => $q->published())
            ->withCount(['movies as movies_count' => fn ($q) => $q->published()])
            ->with(['movies' => fn ($q) => $q->published()->with('genres')->orderByDesc('published_at')->limit(10)])
            ->orderByDesc('movies_count')
            ->orderBy('id')
            ->skip($offset)
            ->take($limit)
            ->get();
    }

    /**
     * Genre-scoped variant of topVjsForPage(). Same ordering rule
     * ("most productive VJ first") but both the inclusion check and
     * the eager-loaded movies are filtered to the given genre so
     * each carousel contains only titles from that genre.
     */
    private function topVjsForGenre(int $genreId, int $offset, int $limit)
    {
        $inGenre = fn ($q) => $q->published()
            ->whereHas('genres', fn ($gq) => $gq->where('genres.id', $genreId));

        return Vj::whereHas('movies', $inGenre)
            ->withCount(['movies as movies_count' => $inGenre])
            ->with(['movies' => function ($q) use ($inGenre) {
                $inGenre($q);
                $q->with('genres')->orderByDesc('published_at')->limit(10);
            }])
            ->orderByDesc('movies_count')
            ->orderBy('id')
            ->skip($offset)
            ->take($limit)
            ->get();
    }

    /**
     * Shows/series twin of topVjsForGenre(). Orders VJs by how many
     * shows they have in the genre, and eager-loads only those shows.
     */
    private function topVjsForShowsByGenre(int $genreId, int $offset, int $limit)
    {
        $inGenre = fn ($q) => $q->published()
            ->whereHas('genres', fn ($gq) => $gq->where('genres.id', $genreId));

        return Vj::whereHas('shows', $inGenre)
            ->withCount(['shows as shows_count' => $inGenre])
            ->with(['shows' => function ($q) use ($inGenre) {
                $inGenre($q);
                $q->with('genres')->orderByDesc('published_at')->limit(10);
            }])
            ->orderByDesc('shows_count')
            ->orderBy('id')
            ->skip($offset)
            ->take($limit)
            ->get();
    }

    /**
     * /geners/{slug}/vjs — genre-scoped VJ view for movies. Mirrors
     * /movie: banner of 3 featured titles + VJ carousels filtered to
     * this genre, each VJ's carousel showing their most-recent titles
     * in the genre only. Load More pulls additional VJs via AJAX.
     */
    public function genreVjs(string $slug): \Illuminate\Contracts\View\View
    {
        $genre = Genre::where('slug', $slug)->firstOrFail();

        $featured = Movie::published()
            ->whereHas('genres', fn ($q) => $q->where('genres.id', $genre->id))
            ->orderByDesc('published_at')
            ->take(3)
            ->get();

        $vjs = $this->topVjsForGenre($genre->id, 0, 5);
        $vjsTotal = Vj::whereHas('movies', fn ($q) => $q->published()
                ->whereHas('genres', fn ($gq) => $gq->where('genres.id', $genre->id)))
            ->count();

        return view('frontend::Pages.geners-vjs-page', [
            'genre' => $genre,
            'featured' => $featured,
            'vjs' => $vjs,
            'vjsTotal' => $vjsTotal,
            'contentKind' => 'movie',
        ]);
    }

    /**
     * AJAX twin of genreVjs() — returns rendered VJ-carousel HTML
     * for the next page plus an X-Has-More header.
     */
    public function genreVjsLoadMore(string $slug, Request $request): \Illuminate\Http\Response
    {
        $genre = Genre::where('slug', $slug)->firstOrFail();
        $offset = max(0, (int) $request->query('offset', 0));
        $limit  = min(10, max(1, (int) $request->query('limit', 5)));

        $vjs = $this->topVjsForGenre($genre->id, $offset, $limit);

        $total = Vj::whereHas('movies', fn ($q) => $q->published()
                ->whereHas('genres', fn ($gq) => $gq->where('genres.id', $genre->id)))
            ->count();
        $hasMore = ($offset + $vjs->count()) < $total;

        $html = '';
        foreach ($vjs as $vj) {
            $html .= view('frontend::components.sections.vj-carousel', [
                'vj' => $vj,
                'items' => $vj->movies,
                'contentKind' => 'movie',
            ])->render();
        }

        return response($html)->header('X-Has-More', $hasMore ? '1' : '0');
    }

    /**
     * Shows/series twin of genreVjs(). Same page layout, but scoped
     * to series: hero drawn from shows in the genre, VJ carousels
     * show each VJ's shows in the genre (not their movies).
     */
    public function genreVjsShows(string $slug): \Illuminate\Contracts\View\View
    {
        $genre = Genre::where('slug', $slug)->firstOrFail();

        $featured = Show::published()
            ->whereHas('genres', fn ($q) => $q->where('genres.id', $genre->id))
            ->with(['seasons'])
            ->orderByDesc('published_at')
            ->take(3)
            ->get();

        $vjs = $this->topVjsForShowsByGenre($genre->id, 0, 5);
        $vjsTotal = Vj::whereHas('shows', fn ($q) => $q->published()
                ->whereHas('genres', fn ($gq) => $gq->where('genres.id', $genre->id)))
            ->count();

        return view('frontend::Pages.geners-vjs-page', [
            'genre' => $genre,
            'featured' => $featured,
            'vjs' => $vjs,
            'vjsTotal' => $vjsTotal,
            'contentKind' => 'show',
        ]);
    }

    /**
     * AJAX twin of genreVjsShows() — returns rendered VJ-carousel
     * HTML for the next page plus an X-Has-More header.
     */
    public function genreVjsShowsLoadMore(string $slug, Request $request): \Illuminate\Http\Response
    {
        $genre = Genre::where('slug', $slug)->firstOrFail();
        $offset = max(0, (int) $request->query('offset', 0));
        $limit  = min(10, max(1, (int) $request->query('limit', 5)));

        $vjs = $this->topVjsForShowsByGenre($genre->id, $offset, $limit);

        $total = Vj::whereHas('shows', fn ($q) => $q->published()
                ->whereHas('genres', fn ($gq) => $gq->where('genres.id', $genre->id)))
            ->count();
        $hasMore = ($offset + $vjs->count()) < $total;

        $html = '';
        foreach ($vjs as $vj) {
            $html .= view('frontend::components.sections.vj-carousel', [
                'vj' => $vj,
                'items' => $vj->shows,
                'contentKind' => 'show',
            ])->render();
        }

        return response($html)->header('X-Has-More', $hasMore ? '1' : '0');
    }

    /**
     * Shows-side twin of topVjsForPage(). Keeps the two endpoints
     * (/series initial render and /series/more-vjs) in lockstep on
     * ordering.
     */
    private function topVjsForShowsPage(int $offset, int $limit)
    {
        return Vj::whereHas('shows', fn ($q) => $q->published())
            ->withCount(['shows as shows_count' => fn ($q) => $q->published()])
            ->with(['shows' => fn ($q) => $q->published()->with('genres')->orderByDesc('published_at')->limit(10)])
            ->orderByDesc('shows_count')
            ->orderBy('id')
            ->skip($offset)
            ->take($limit)
            ->get();
    }

    /**
     * VJ detail page — organises that VJ's catalogue by genre. Each
     * genre section gets an initial slice of movies; Load More in the
     * UI pulls additional pages via vjGenreLoadMore() below.
     */
    public function vjDetail(string $slug)
    {
        $vj = Vj::where('slug', $slug)->firstOrFail();

        // Featured banner — top 3 movies by this VJ, newest first.
        $featuredMovies = $vj->movies()->published()
            ->with('genres')
            ->orderByDesc('published_at')
            ->take(3)
            ->get();

        // Group the VJ's catalogue by genre. Each genre bucket keeps
        // its first 15 movies plus a total-count + has-more flag so
        // the load-more button knows whether to show.
        $genres = Genre::whereHas('movies', fn ($q) =>
            $q->published()->whereHas('vjs', fn ($v) => $v->where('vjs.id', $vj->id))
        )->orderBy('name')->get();

        $buckets = $genres->map(function ($genre) use ($vj) {
            $query = Movie::published()
                ->with('genres')
                ->whereHas('genres', fn ($q) => $q->where('genres.id', $genre->id))
                ->whereHas('vjs', fn ($q) => $q->where('vjs.id', $vj->id))
                ->orderByDesc('published_at');

            $total = (clone $query)->count();
            $initial = $query->take(15)->get();

            return (object) [
                'genre' => $genre,
                'movies' => $initial,
                'total' => $total,
                'hasMore' => $total > $initial->count(),
            ];
        });

        return view('frontend::Pages.Vjs.detail-page', compact('vj', 'featuredMovies', 'buckets'));
    }

    /**
     * Load-more endpoint for a single genre within a VJ's catalogue.
     * Appends to an existing grid — returns rendered movie-card HTML
     * so the client JS just needs to insertAdjacentHTML.
     */
    public function vjGenreLoadMore(string $slug, Request $request): \Illuminate\Http\Response
    {
        $vj = Vj::where('slug', $slug)->firstOrFail();
        $genreSlug = (string) $request->query('genre', '');
        $offset = max(0, (int) $request->query('offset', 0));
        $limit  = min(60, max(1, (int) $request->query('limit', 15)));

        $genre = Genre::where('slug', $genreSlug)->firstOrFail();

        $query = Movie::published()
            ->with('genres')
            ->whereHas('genres', fn ($q) => $q->where('genres.id', $genre->id))
            ->whereHas('vjs', fn ($q) => $q->where('vjs.id', $vj->id))
            ->orderByDesc('published_at');

        $total  = (clone $query)->count();
        $movies = $query->skip($offset)->take($limit)->get();
        $hasMore = ($offset + $movies->count()) < $total;

        $html = '';
        foreach ($movies as $movie) {
            $html .= view('frontend::components.partials.vj-grid-card', [
                'item' => $movie,
                'contentKind' => 'movie',
            ])->render();
        }

        return response($html)->header('X-Has-More', $hasMore ? '1' : '0');
    }

    public function tv_show()
    {
        $featuredShows = Show::published()
            ->orderByDesc('published_at')
            ->take(3)
            ->get();

        // Mirrors /movie: top 5 VJs (by published show count), then a
        // Load More button fetches the rest so the first render stays
        // lean.
        $vjs = $this->topVjsForShowsPage(0, 5);
        $vjsTotal = Vj::whereHas('shows', fn ($q) => $q->published())->count();

        return view('frontend::Pages.MainPages.tv-shows-page', compact('featuredShows', 'vjs', 'vjsTotal'));
    }

    /**
     * AJAX endpoint for /series — returns the next slice of VJ
     * carousels as rendered HTML so the client JS just appends.
     * Mirrors moreVjsForMoviesPage() scoped to shows.
     */
    public function moreVjsForSeriesPage(Request $request): \Illuminate\Http\Response
    {
        $offset = max(0, (int) $request->query('offset', 0));
        $limit  = min(10, max(1, (int) $request->query('limit', 5)));

        $vjs = $this->topVjsForShowsPage($offset, $limit);

        $total = Vj::whereHas('shows', fn ($q) => $q->published())->count();
        $hasMore = ($offset + $vjs->count()) < $total;

        $html = '';
        foreach ($vjs as $vj) {
            $html .= view('frontend::components.sections.vj-carousel', [
                'vj' => $vj,
                'items' => $vj->shows,
                'contentKind' => 'show',
            ])->render();
        }

        return response($html)->header('X-Has-More', $hasMore ? '1' : '0');
    }

    /**
     * VJ series detail page — that VJ's shows grouped by genre,
     * mirroring vjDetail() for movies.
     */
    public function vjSeriesDetail(string $slug)
    {
        $vj = Vj::where('slug', $slug)->firstOrFail();

        $featuredShows = $vj->shows()->published()
            ->with('genres')
            ->orderByDesc('published_at')
            ->take(3)
            ->get();

        $genres = Genre::whereHas('shows', fn ($q) =>
            $q->published()->whereHas('vjs', fn ($v) => $v->where('vjs.id', $vj->id))
        )->orderBy('name')->get();

        $buckets = $genres->map(function ($genre) use ($vj) {
            $query = Show::published()
                ->with('genres')
                ->whereHas('genres', fn ($q) => $q->where('genres.id', $genre->id))
                ->whereHas('vjs', fn ($q) => $q->where('vjs.id', $vj->id))
                ->orderByDesc('published_at');

            $total = (clone $query)->count();
            $initial = $query->take(15)->get();

            return (object) [
                'genre' => $genre,
                'shows' => $initial,
                'total' => $total,
                'hasMore' => $total > $initial->count(),
            ];
        });

        return view('frontend::Pages.Vjs.series-detail-page', compact('vj', 'featuredShows', 'buckets'));
    }

    /**
     * Load-more endpoint for a single genre within a VJ's series
     * catalogue. Returns server-rendered grid cells so the JS just
     * insertAdjacentHTMLs.
     */
    public function vjSeriesGenreLoadMore(string $slug, Request $request): \Illuminate\Http\Response
    {
        $vj = Vj::where('slug', $slug)->firstOrFail();
        $genreSlug = (string) $request->query('genre', '');
        $offset = max(0, (int) $request->query('offset', 0));
        $limit  = min(60, max(1, (int) $request->query('limit', 15)));

        $genre = Genre::where('slug', $genreSlug)->firstOrFail();

        $query = Show::published()
            ->with('genres')
            ->whereHas('genres', fn ($q) => $q->where('genres.id', $genre->id))
            ->whereHas('vjs', fn ($q) => $q->where('vjs.id', $vj->id))
            ->orderByDesc('published_at');

        $total  = (clone $query)->count();
        $shows  = $query->skip($offset)->take($limit)->get();
        $hasMore = ($offset + $shows->count()) < $total;

        $html = '';
        foreach ($shows as $show) {
            $html .= view('frontend::components.partials.vj-grid-card', [
                'item' => $show,
                'contentKind' => 'show',
            ])->render();
        }

        return response($html)->header('X-Has-More', $hasMore ? '1' : '0');
    }

    //deatil pages
    public function movie_detail(?string $slug = null)
    {
        // `detailVisible` (published ∪ upcoming) so clicks from the
        // Upcoming rail on the home page land here instead of 404ing.
        // Watch + stream endpoints still gate on published().
        $movie = $slug
            ? Movie::where('slug', $slug)->detailVisible()->with(['genres', 'tags', 'categories', 'cast'])->firstOrFail()
            : Movie::published()->with(['genres', 'tags', 'categories', 'cast'])->orderByDesc('published_at')->firstOrFail();

        $recommended = Movie::published()
            ->where('id', '!=', $movie->id)
            ->inRandomOrder()
            ->take(6)
            ->get();

        $isUpcoming = $movie->status === Movie::STATUS_UPCOMING;

        // Upcoming titles have no stream source yet and shouldn't render
        // a Watch CTA regardless of the user's subscription. Hard-force
        // both instead of threading a new `isUpcoming` branch through
        // the existing `canWatch` / `source` flow.
        $source   = $isUpcoming ? null  : $movie->streamSource();
        $canWatch = $isUpcoming ? false : $this->userCanWatch($movie);

        [$reviews, $reviewStats, $myReview] = $this->loadReviewData($movie);

        return view('frontend::Pages.Movies.detail-page', compact(
            'movie', 'recommended', 'source', 'canWatch', 'isUpcoming',
            'reviews', 'reviewStats', 'myReview'
        ));
    }

    /**
     * Full watch page for a movie — player at the top, movie details
     * below, then Recommended + Similar rails so the user can keep
     * browsing while the player mini-docks on scroll.
     *
     * "Similar" is genre-overlap for v1 (intersect on genre IDs,
     * exclude the current movie, cap at 6). Refining the similarity
     * signal is a separate conversation.
     */
    public function movie_watch(?string $slug = null)
    {
        // Widen the lookup to `detailVisible` (published ∪ upcoming) so
        // an upcoming title returns a clean redirect to the detail page
        // instead of 404ing. Watching an upcoming movie is nonsensical
        // (there's nothing to stream yet), so we bounce to detail with
        // a flash instead of trying to render the player.
        $movie = $slug
            ? Movie::where('slug', $slug)->detailVisible()->with(['genres', 'tags', 'categories', 'cast'])->firstOrFail()
            : Movie::published()->with(['genres', 'tags', 'categories', 'cast'])->orderByDesc('published_at')->firstOrFail();

        if ($movie->status === Movie::STATUS_UPCOMING) {
            $when = $movie->published_at?->format('M j, Y');
            $msg = $when
                ? "\"{$movie->title}\" is coming soon — check back on {$when}."
                : "\"{$movie->title}\" is coming soon.";
            return redirect()->route('frontend.movie_detail', $movie->slug)->with('info', $msg);
        }

        $source = $movie->streamSource();
        $canWatch = $this->userCanWatch($movie);

        if (!$canWatch) {
            // Guests get prompted to sign in and bounce back here;
            // authed users without the right tier go to pricing.
            if (!auth()->check()) {
                return redirect()->guest(route('login'));
            }
            return redirect()->route('frontend.pricing-page')
                ->with('info', "A subscription is required to watch \"{$movie->title}\".");
        }

        if ($this->concurrencyExceeded($movie)) {
            return redirect()->route('streams.limit');
        }

        $recommended = Movie::published()
            ->where('id', '!=', $movie->id)
            ->inRandomOrder()
            ->take(6)
            ->get();

        $genreIds = $movie->genres->pluck('id')->all();
        $castIds = $movie->cast->pluck('id')->all();

        // Similarity score: shared cast counts twice as much as shared
        // genre — a movie with the same lead actor feels more "similar"
        // than one that just happens to share a genre bucket.
        $similar = (!empty($genreIds) || !empty($castIds))
            ? Movie::published()
                ->where('id', '!=', $movie->id)
                ->where(function ($q) use ($genreIds, $castIds) {
                    if (!empty($genreIds)) {
                        $q->whereHas('genres', fn ($gq) => $gq->whereIn('genres.id', $genreIds));
                    }
                    if (!empty($castIds)) {
                        $q->orWhereHas('cast', fn ($cq) => $cq->whereIn('persons.id', $castIds));
                    }
                })
                ->with('genres')
                ->withCount([
                    'genres as shared_genres' => fn ($q) => !empty($genreIds) ? $q->whereIn('genres.id', $genreIds) : $q->whereRaw('0=1'),
                    'cast as shared_cast' => fn ($q) => !empty($castIds) ? $q->whereIn('persons.id', $castIds) : $q->whereRaw('0=1'),
                ])
                ->orderByRaw('(shared_cast * 2 + shared_genres) DESC, published_at DESC')
                ->take(8)
                ->get()
            : collect();

        $resumePosition = 0;
        if (auth()->check()) {
            $history = WatchHistoryItem::where('user_id', auth()->id())
                ->where('watchable_type', $movie->getMorphClass())
                ->where('watchable_id', $movie->id)
                ->first();
            $resumePosition = ($history && !$history->completed) ? $history->position_seconds : 0;
        }

        return view('frontend::Pages.Movies.watch-page', compact('movie', 'source', 'recommended', 'similar', 'resumePosition'));
    }

    /**
     * Mirrors the server-side TierGate check so the detail page can
     * decide whether to play inline (allowed) or redirect to pricing.
     * The middleware is still the source of truth — this is just a
     * read-only gate for rendering the right button/state.
     */
    private function userCanWatch(Movie|Episode $content): bool
    {
        $requiredSlug = $content->tier_required ?? null;
        if (!$requiredSlug) {
            return true;
        }

        $user = auth()->user();
        if (!$user) {
            return false;
        }

        // Admins bypass all tier gating — they're the ones curating the
        // content and need to be able to verify playback regardless of
        // what subscription they happen to have.
        if ($user->hasRole('admin')) {
            return true;
        }

        $requiredTier = SubscriptionTier::where('slug', $requiredSlug)->first();
        if (!$requiredTier) {
            return true;
        }

        $sub = UserSubscription::with('tier')
            ->where('user_id', $user->id)
            ->current()
            ->orderByDesc('ends_at')
            ->first();

        $userLevel = $sub?->tier?->access_level ?? SubscriptionTier::ACCESS_FREE;

        return $userLevel >= $requiredTier->access_level;
    }

    /**
     * True when starting to play premium-gated `$content` on this
     * device would exceed the user's tier's max_concurrent_streams.
     * Free/ungated content and admins bypass; free-tier users bypass
     * too (they can't play premium content at all, so tier_gate blocks
     * them earlier with a 403).
     *
     * Only counts OTHER devices' active streams — the current session
     * doesn't count against itself.
     */
    private function concurrencyExceeded(Movie|Episode $content): bool
    {
        if (!$content->tier_required) {
            return false;
        }

        $user = auth()->user();
        if (!$user || $user->hasRole('admin')) {
            return false;
        }

        $activeSub = UserSubscription::with('tier')
            ->where('user_id', $user->id)
            ->current()
            ->orderByDesc('ends_at')
            ->first();

        $cap = $activeSub?->tier?->max_concurrent_streams;
        if ($cap === null || $cap <= 0) {
            return false;
        }

        $others = \Modules\Streaming\app\Models\ActiveStream::activeCount($user->id, session()->getId());
        return $others >= $cap;
    }

    public function tvshow_detail(?string $slug = null)
    {
        // `detailVisible` (published ∪ upcoming) so upcoming series
        // detail pages load for Upcoming-rail clicks. Episodes stay
        // unwatchable for upcoming series (no source), same rationale
        // as Movie::detail.
        $show = $slug
            ? Show::where('slug', $slug)->detailVisible()
                ->with(['genres', 'tags', 'categories', 'cast', 'seasons.episodes'])
                ->firstOrFail()
            : Show::published()
                ->with(['genres', 'tags', 'categories', 'cast', 'seasons.episodes'])
                ->orderByDesc('published_at')
                ->firstOrFail();

        $recommended = Show::published()
            ->where('id', '!=', $show->id)
            ->inRandomOrder()
            ->take(6)
            ->get();

        $isUpcoming = $show->status === Show::STATUS_UPCOMING;

        [$reviews, $reviewStats, $myReview] = $this->loadReviewData($show);

        return view('frontend::Pages.TvShows.detail-page', compact(
            'show', 'recommended', 'isUpcoming', 'reviews', 'reviewStats', 'myReview'
        ));
    }

    /**
     * Shared loader — returns the latest 10 published reviews with
     * user names, aggregate stats (count + rounded avg), and the
     * current user's review (if any) so the view can branch between
     * "write a review" and "your review" states without guessing.
     *
     * @return array{0:\Illuminate\Support\Collection,1:array{count:int,avg:float},2:?Review}
     */
    private function loadReviewData(Movie|Show $content): array
    {
        $base = Review::where('reviewable_type', $content->getMorphClass())
            ->where('reviewable_id', $content->id);

        $reviews = (clone $base)
            ->where('is_published', true)
            ->with('user:id,username,first_name,last_name')
            ->latest()
            ->take(10)
            ->get();

        $stats = [
            'count' => (clone $base)->where('is_published', true)->count(),
            'avg'   => round((float) (clone $base)->where('is_published', true)->avg('stars'), 1),
        ];

        $myReview = auth()->check()
            ? (clone $base)->where('user_id', auth()->id())->first()
            : null;

        return [$reviews, $stats, $myReview];
    }

    /**
     * Legacy numeric-id episode URL → 301 to the pretty form.
     * Preserves old bookmarks and any links minted before the route rename.
     */
    public function episodeLegacyRedirect(int $id)
    {
        $ep = Episode::with('season.show')->find($id);
        abort_unless($ep && $ep->season && $ep->season->show, 404);
        return redirect($ep->frontendUrl(), 301);
    }

    public function episode(string $show, int $season, int $episode)
    {
        // Match (show slug, season number, episode number) → one episode row.
        // Fails to 404 if any segment doesn't resolve, so hand-crafted URLs
        // with a typo'd slug or deleted episode return a proper not-found
        // rather than silently serving whatever Episode::first() found.
        $episodeModel = Episode::whereHas('season', function ($q) use ($season, $show) {
                $q->where('number', $season)
                  ->whereHas('show', fn ($s) => $s->where('slug', $show));
            })
            ->where('number', $episode)
            ->first();

        abort_unless($episodeModel, 404);
        $episodeModel->load(['season.show.genres', 'season.show.cast', 'season.show.seasons.episodes']);
        $episode = $episodeModel; // rest of the method reads $episode
        $show = $episode->season->show;

        // Upcoming series: episodes aren't streamable yet. Bounce to
        // the series detail page with a flash instead of trying to
        // render a player pointing at nothing.
        if ($show->status === Show::STATUS_UPCOMING) {
            $when = $show->published_at?->format('M j, Y');
            $msg = $when
                ? "\"{$show->title}\" is coming soon — check back on {$when}."
                : "\"{$show->title}\" is coming soon.";
            return redirect()->route('frontend.series_detail', $show->slug)->with('info', $msg);
        }

        $source = $episode->streamSource();
        $canWatch = $this->userCanWatch($episode);

        if (!$canWatch) {
            if (!auth()->check()) {
                return redirect()->guest(route('login'));
            }
            return redirect()->route('frontend.pricing-page')
                ->with('info', "A subscription is required to watch episodes of \"{$show->title}\".");
        }

        if ($this->concurrencyExceeded($episode)) {
            return redirect()->route('streams.limit');
        }

        // Next episode: try the next number in the same season; fall
        // back to the first published episode of the next season. The
        // autoplay-next toggle on the page wires onto this.
        $nextEpisode = Episode::where('season_id', $episode->season_id)
            ->where('number', '>', $episode->number)
            ->orderBy('number')
            ->first();

        if (! $nextEpisode) {
            $nextSeason = $show->seasons
                ->sortBy('number')
                ->firstWhere(fn ($s) => $s->number > $episode->season->number);
            if ($nextSeason) {
                $nextEpisode = $nextSeason->episodes->sortBy('number')->first();
            }
        }

        // Previous episode: mirror the next-episode logic in reverse —
        // try the previous number in the same season, else the last
        // episode of the previous season.
        $previousEpisode = Episode::where('season_id', $episode->season_id)
            ->where('number', '<', $episode->number)
            ->orderByDesc('number')
            ->first();

        if (! $previousEpisode) {
            $prevSeason = $show->seasons
                ->sortByDesc('number')
                ->firstWhere(fn ($s) => $s->number < $episode->season->number);
            if ($prevSeason) {
                $previousEpisode = $prevSeason->episodes->sortByDesc('number')->first();
            }
        }

        $resumePosition = 0;
        if (auth()->check()) {
            $history = WatchHistoryItem::where('user_id', auth()->id())
                ->where('watchable_type', $episode->getMorphClass())
                ->where('watchable_id', $episode->id)
                ->first();
            $resumePosition = ($history && !$history->completed) ? $history->position_seconds : 0;
        }

        // Similar series: mirrors the movie_watch scoring — shared cast
        // counts twice as much as shared genre. Excludes the current
        // show from the result set.
        $genreIds = $show->genres->pluck('id')->all();
        $castIds  = $show->cast->pluck('id')->all();

        $similarShows = (!empty($genreIds) || !empty($castIds))
            ? Show::published()
                ->where('id', '!=', $show->id)
                ->where(function ($q) use ($genreIds, $castIds) {
                    if (!empty($genreIds)) {
                        $q->whereHas('genres', fn ($gq) => $gq->whereIn('genres.id', $genreIds));
                    }
                    if (!empty($castIds)) {
                        $q->orWhereHas('cast', fn ($cq) => $cq->whereIn('persons.id', $castIds));
                    }
                })
                ->with('genres')
                ->withCount([
                    'genres as shared_genres' => fn ($q) => !empty($genreIds) ? $q->whereIn('genres.id', $genreIds) : $q->whereRaw('0=1'),
                    'cast as shared_cast'     => fn ($q) => !empty($castIds)  ? $q->whereIn('persons.id', $castIds) : $q->whereRaw('0=1'),
                ])
                ->orderByRaw('(shared_cast * 2 + shared_genres) DESC, published_at DESC')
                ->take(8)
                ->get()
            : collect();

        // Fallback recommendation bucket — random other published
        // series, used so the page always has something below the
        // similar row even when the current show has no taxonomy.
        $recommendedShows = Show::published()
            ->where('id', '!=', $show->id)
            ->inRandomOrder()
            ->take(6)
            ->get();

        // Comments thread — approved comments only, newest first,
        // top-level only (replies not rendered yet).
        $comments = Comment::where('commentable_type', $episode->getMorphClass())
            ->where('commentable_id', $episode->id)
            ->where('is_approved', true)
            ->whereNull('parent_id')
            ->with('user:id,username,first_name,last_name')
            ->latest()
            ->take(30)
            ->get();

        return view('frontend::Pages.TvShows.episode-page', compact('episode', 'show', 'source', 'canWatch', 'nextEpisode', 'previousEpisode', 'resumePosition', 'similarShows', 'recommendedShows', 'comments'));
    }

    /**
     * JSON player-data for a single episode. Used by the fullscreen
     * in-place swap — the client calls this when the user hits
     * prev/next while fullscreen so we can change episodes without
     * the browser exiting fullscreen mode (a full page navigation
     * always drops fullscreen, per browser security rules).
     *
     * Same tier / auth gating as the HTML view so we don't leak a
     * privileged stream URL via JSON.
     */
    public function episodePlayerData(Episode $episode): JsonResponse
    {
        if (! $this->userCanWatch($episode)) {
            return response()->json(['error' => 'subscription_required'], 403);
        }

        $episode->load(['season.show.seasons.episodes']);
        $show = $episode->season->show;
        $source = $episode->streamSource();

        $next = Episode::where('season_id', $episode->season_id)
            ->where('number', '>', $episode->number)
            ->orderBy('number')
            ->first();
        if (! $next) {
            $nextSeason = $show->seasons->sortBy('number')
                ->firstWhere(fn ($s) => $s->number > $episode->season->number);
            if ($nextSeason) {
                $next = $nextSeason->episodes->sortBy('number')->first();
            }
        }

        $prev = Episode::where('season_id', $episode->season_id)
            ->where('number', '<', $episode->number)
            ->orderByDesc('number')
            ->first();
        if (! $prev) {
            $prevSeason = $show->seasons->sortByDesc('number')
                ->firstWhere(fn ($s) => $s->number < $episode->season->number);
            if ($prevSeason) {
                $prev = $prevSeason->episodes->sortByDesc('number')->first();
            }
        }

        $resume = 0;
        if (auth()->check()) {
            $history = WatchHistoryItem::where('user_id', auth()->id())
                ->where('watchable_type', $episode->getMorphClass())
                ->where('watchable_id', $episode->id)
                ->first();
            $resume = ($history && !$history->completed) ? $history->position_seconds : 0;
        }

        $label = fn ($ep) => $ep
            ? 'S' . str_pad($ep->season->number ?? 0, 2, '0', STR_PAD_LEFT)
                . 'E' . str_pad($ep->number, 2, '0', STR_PAD_LEFT)
                . ' — ' . $ep->title
            : null;

        return response()->json([
            'id'              => $episode->id,
            'detailUrl'       => $episode->frontendUrl(),
            'title'           => $label($episode),
            'showTitle'       => $show->title,
            'videoUrl'        => $source['url'] ?? null,
            'videoUrlLow'     => $episode->streamSourceLow()['url'] ?? null,
            'poster'          => $episode->still_url ?: ($show->backdrop_url ?: $show->poster_url),
            'resumePosition'  => $resume,
            'nextEpisode'     => $next ? [
                'id'        => $next->id,
                'url'       => $next->frontendUrl(),
                'label'     => $label($next),
            ] : null,
            'previousEpisode' => $prev ? [
                'id'        => $prev->id,
                'url'       => $prev->frontendUrl(),
                'label'     => $label($prev),
            ] : null,
        ]);
    }

    /**
     * Drop an item from the authenticated user's Continue Watching row.
     *
     * `type` is either 'movie' (removes that one movie's history) or
     * 'show' (removes every episode of that show — otherwise a single
     * kept row would immediately repopulate the card on refresh since
     * the composer dedupes show cards by show_id).
     */
    public function removeFromContinueWatching(string $type, int $id): JsonResponse
    {
        $userId = auth()->id();
        if (!$userId) {
            return response()->json(['error' => 'unauthenticated'], 401);
        }

        if ($type === 'movie') {
            $deleted = WatchHistoryItem::where('user_id', $userId)
                ->where('watchable_type', (new Movie)->getMorphClass())
                ->where('watchable_id', $id)
                ->delete();
        } elseif ($type === 'show') {
            // All episode-rows whose episode belongs to this show.
            $episodeIds = Episode::whereHas('season', fn ($q) => $q->where('show_id', $id))
                ->pluck('id');
            $deleted = WatchHistoryItem::where('user_id', $userId)
                ->where('watchable_type', (new Episode)->getMorphClass())
                ->whereIn('watchable_id', $episodeIds)
                ->delete();
        } else {
            return response()->json(['error' => 'invalid_type'], 422);
        }

        return response()->json(['ok' => true, 'removed' => $deleted]);
    }

    /* ---------------------------------------------------------------
     | Reviews (movies + shows)
     | --------------------------------------------------------------- */

    public function storeMovieReview(Request $request, string $slug)
    {
        $movie = Movie::where('slug', $slug)->firstOrFail();
        $this->persistReview($request, $movie);
        return back()->with('success', 'Thanks — your review was saved.');
    }

    public function storeShowReview(Request $request, string $slug)
    {
        $show = Show::where('slug', $slug)->firstOrFail();
        $this->persistReview($request, $show);
        return back()->with('success', 'Thanks — your review was saved.');
    }

    public function destroyMovieReview(string $slug)
    {
        $movie = Movie::where('slug', $slug)->firstOrFail();
        $this->deleteOwnReview($movie);
        return back()->with('success', 'Your review was removed.');
    }

    public function destroyShowReview(string $slug)
    {
        $show = Show::where('slug', $slug)->firstOrFail();
        $this->deleteOwnReview($show);
        return back()->with('success', 'Your review was removed.');
    }

    /**
     * One review per (user, content). `updateOrCreate` makes the form
     * idempotent — editing an existing review silently overwrites the
     * previous one instead of stacking duplicates.
     */
    private function persistReview(Request $request, Movie|Show $content): void
    {
        $data = $request->validate([
            'stars' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:200',
            'body'  => 'required|string|min:3|max:4000',
        ]);

        $review = Review::updateOrCreate(
            [
                'user_id'         => auth()->id(),
                'reviewable_type' => $content->getMorphClass(),
                'reviewable_id'   => $content->id,
            ],
            [
                'stars'        => $data['stars'],
                'title'        => $data['title'] ?? null,
                'body'         => $data['body'],
                'is_published' => true,
            ],
        );

        // Only notify on genuinely new reviews (updateOrCreate returns
        // the same row for edits). wasRecentlyCreated is the canonical
        // Eloquent flag for "this was an INSERT, not an UPDATE".
        if ($review->wasRecentlyCreated) {
            event(new \Modules\Notifications\app\Events\NewReviewPosted(
                $review->id,
                auth()->user()->username,
                $content->title,
                (int) $data['stars'],
            ));
        }
    }

    private function deleteOwnReview(Movie|Show $content): void
    {
        Review::where('user_id', auth()->id())
            ->where('reviewable_type', $content->getMorphClass())
            ->where('reviewable_id', $content->id)
            ->delete();
    }

    /* ---------------------------------------------------------------
     | Comments (episodes)
     | --------------------------------------------------------------- */

    public function storeEpisodeComment(Request $request, Episode $episode)
    {
        $data = $request->validate([
            'body' => 'required|string|min:2|max:2000',
            'parent_id' => 'nullable|integer|exists:comments,id',
        ]);

        $comment = Comment::create([
            'user_id'          => auth()->id(),
            'commentable_type' => $episode->getMorphClass(),
            'commentable_id'   => $episode->id,
            'parent_id'        => $data['parent_id'] ?? null,
            'body'             => $data['body'],
            'is_approved'      => true,
        ]);

        $contentTitle = $episode->season?->show?->title
            ? "{$episode->season->show->title} — {$episode->title}"
            : ($episode->title ?? 'episode');

        event(new \Modules\Notifications\app\Events\NewCommentPosted(
            $comment->id,
            auth()->user()->username,
            $contentTitle,
            $data['body'],
        ));

        return back()->with('success', 'Comment posted.');
    }

    /**
     * Users can delete their own comments. Admins can delete any.
     */
    public function destroyComment(Comment $comment)
    {
        $user = auth()->user();
        abort_if(
            $comment->user_id !== $user->id && !$user->hasRole('admin'),
            403
        );

        $comment->delete();

        return back()->with('success', 'Comment removed.');
    }

    public function watchlist_detail()
    {
        // Two buckets surfaced in the UI: Movies and Series. Episodes
        // are intentionally excluded from this list view — they reach
        // the watchlist via the toggle endpoint but play through the
        // episode page directly, not this grid.
        $movieClass = (new Movie)->getMorphClass();
        $showClass  = (new Show)->getMorphClass();

        $items = WatchlistItem::where('user_id', auth()->id())
            ->whereIn('watchable_type', [$movieClass, $showClass])
            ->with(['watchable.genres'])
            ->latest('added_at')
            ->get();

        $movies = $items->where('watchable_type', $movieClass)->values();
        $shows  = $items->where('watchable_type', $showClass)->values();

        return view('frontend::Pages.watchlist-detail', compact('items', 'movies', 'shows'));
    }

    /**
     * Toggle membership in the authenticated user's watchlist.
     * JSON response — `inList` reflects the post-toggle state so the
     * client can update the button icon.
     */
    public function toggleWatchlist(string $type, int $id): JsonResponse
    {
        $userId = auth()->id();
        if (!$userId) {
            return response()->json(['error' => 'unauthenticated'], 401);
        }

        $model = match ($type) {
            'movie'   => Movie::find($id),
            'show'    => Show::find($id),
            'episode' => Episode::find($id),
            default   => null,
        };

        if (!$model) {
            return response()->json(['error' => 'not_found'], 404);
        }

        $morphType = $model->getMorphClass();
        $existing  = WatchlistItem::where('user_id', $userId)
            ->where('watchable_type', $morphType)
            ->where('watchable_id', $model->getKey())
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['ok' => true, 'inList' => false]);
        }

        WatchlistItem::addFor($userId, $model);
        return response()->json(['ok' => true, 'inList' => true]);
    }

    /**
     * Watchlist player — pretty URL `/watchlist/{movie-slug}`.
     *
     * Only Movies are served here. Episodes in the queue link to
     * `/episode/{id}` directly (existing page has its own prev/next
     * wiring). Shows link to `/series/{slug}` so the user can pick an
     * episode before playing.
     *
     * The slug identifies the Movie. We then look up the user's
     * WatchlistItem pointing at it — if missing we fall back to the
     * watch page behaviour (plays anyway, with the rest of the
     * watchlist on the side if there is one).
     */
    public function watchlistPlay(string $slug)
    {
        $movie = Movie::where('slug', $slug)->first();
        if (!$movie) {
            return redirect()->route('frontend.watchlist_detail')
                ->with('info', 'That movie is no longer available.');
        }

        // If an admin flipped the movie to upcoming after the user
        // added it to their watchlist, the queue item still resolves
        // but there's nothing to stream. Bounce to the detail page
        // with a release-date note instead of a dead player.
        if ($movie->status === Movie::STATUS_UPCOMING) {
            $when = $movie->published_at?->format('M j, Y');
            $msg = $when
                ? "\"{$movie->title}\" is coming soon — check back on {$when}."
                : "\"{$movie->title}\" is coming soon.";
            return redirect()->route('frontend.movie_detail', $movie->slug)->with('info', $msg);
        }

        $watchable = $movie;

        // The matching watchlist row (if any) gives us the "current"
        // anchor used to compute prev/next in the queue and highlight
        // the active tile.
        $current = WatchlistItem::where('user_id', auth()->id())
            ->where('watchable_type', $movie->getMorphClass())
            ->where('watchable_id', $movie->id)
            ->first();

        if (!$this->userCanWatch($watchable)) {
            return redirect()->route('frontend.pricing-page')
                ->with('info', "A subscription is required to watch \"{$watchable->title}\".");
        }

        if ($this->concurrencyExceeded($watchable)) {
            return redirect()->route('streams.limit');
        }

        $source = $watchable->streamSource();
        $sourceLow = method_exists($watchable, 'streamSourceLow')
            ? ($watchable->streamSourceLow()['url'] ?? null)
            : null;

        $poster = $watchable->backdrop_url ?: $watchable->poster_url;
        $title = $watchable->title;
        $detailUrl = route('frontend.movie_detail', $watchable->slug);

        $resumePosition = 0;
        $history = WatchHistoryItem::where('user_id', auth()->id())
            ->where('watchable_type', $watchable->getMorphClass())
            ->where('watchable_id', $watchable->id)
            ->first();
        if ($history && !$history->completed) {
            $resumePosition = $history->position_seconds;
        }

        $items = WatchlistItem::where('user_id', auth()->id())
            ->with(['watchable.genres'])
            ->latest('added_at')
            ->get();

        // Prev/next navigation — limited to Movies because only those
        // play through this queue. We find the current movie in the
        // movie-only slice; the adjacent Movies become prev/next.
        $movieClass = (new Movie)->getMorphClass();
        $movieItems = $items->where('watchable_type', $movieClass)->values();
        $currentMovieIndex = $movieItems->search(fn ($i) => $i->watchable_id === $watchable->id);
        $prevMovie = $currentMovieIndex !== false && $currentMovieIndex > 0
            ? $movieItems[$currentMovieIndex - 1]->watchable
            : null;
        $nextMovie = $currentMovieIndex !== false && $currentMovieIndex < $movieItems->count() - 1
            ? $movieItems[$currentMovieIndex + 1]->watchable
            : null;

        $currentIndex = $current
            ? $items->search(fn ($i) => $i->id === $current->id)
            : false;

        return view('frontend::Pages.watchlist-play-page', [
            'current'        => $current,
            'watchable'      => $watchable,
            'items'          => $items,
            'currentIndex'   => $currentIndex === false ? 0 : $currentIndex,
            'source'         => $source,
            'sourceLow'      => $sourceLow,
            'poster'         => $poster,
            'title'          => $title,
            'detailUrl'      => $detailUrl,
            'resumePosition' => $resumePosition,
            'prevMovie'      => $prevMovie,
            'nextMovie'      => $nextMovie,
        ]);
    }

    /**
     * Watchlist entry point for a series: resolve to the episode the
     * user should land on, then redirect to /episode/{id} so the real
     * episode player takes over (prev/next across seasons, autoplay,
     * comments, etc.).
     *
     * Resolution order:
     *   1. The most-recently-watched incomplete episode of this show
     *      (so the user resumes exactly where they left off).
     *   2. The first published episode of the first published season
     *      (so a fresh click on a never-watched show still plays).
     */
    public function watchlistSeriesPlay(string $slug)
    {
        $show = Show::where('slug', $slug)->first();
        if (!$show) {
            return redirect()->route('frontend.watchlist_detail')
                ->with('info', 'That series is no longer available.');
        }

        // Upcoming series: episodes aren't streamable yet. Same pattern
        // as watchlistPlay — bounce to the detail page so the user sees
        // the release date instead of a 404 resolving to a missing
        // episode.
        if ($show->status === Show::STATUS_UPCOMING) {
            $when = $show->published_at?->format('M j, Y');
            $msg = $when
                ? "\"{$show->title}\" is coming soon — check back on {$when}."
                : "\"{$show->title}\" is coming soon.";
            return redirect()->route('frontend.series_detail', $show->slug)->with('info', $msg);
        }

        $episodeClass = (new Episode)->getMorphClass();

        $resume = WatchHistoryItem::where('user_id', auth()->id())
            ->where('watchable_type', $episodeClass)
            ->where('completed', false)
            ->whereIn('watchable_id', function ($q) use ($show) {
                $q->select('episodes.id')
                    ->from('episodes')
                    ->join('seasons', 'seasons.id', '=', 'episodes.season_id')
                    ->where('seasons.show_id', $show->id);
            })
            ->orderByDesc('watched_at')
            ->first();

        if ($resume) {
            $resumeEp = Episode::with('season.show')->find($resume->watchable_id);
            if ($resumeEp) {
                return redirect($resumeEp->frontendUrl());
            }
        }

        $firstEpisode = Episode::whereHas('season', fn ($q) => $q->where('show_id', $show->id))
            ->orderBy('season_id')
            ->orderBy('number')
            ->first();

        if (!$firstEpisode) {
            return redirect()->route('frontend.series_detail', $show->slug)
                ->with('info', 'This series has no episodes yet.');
        }

        return redirect($firstEpisode->frontendUrl($show));
    }

    /**
     * JSON player-data for a movie in the watchlist queue. Used by
     * the in-place fullscreen swap on `/watchlist/{slug}` — clicking
     * prev/next (or autoplay on end) while fullscreen fetches this
     * and swaps <video>.src without a page navigation, so the browser
     * doesn't drop us out of fullscreen.
     *
     * Prev/next are computed against the user's current watchlist
     * ordering, NOT the global movie catalogue. Same tier/auth gating
     * as the HTML view so we don't leak a privileged URL via JSON.
     */
    public function watchlistMoviePlayerData(string $slug): JsonResponse
    {
        $movie = Movie::where('slug', $slug)->first();
        if (!$movie) {
            return response()->json(['error' => 'not_found'], 404);
        }
        if (!$this->userCanWatch($movie)) {
            return response()->json(['error' => 'subscription_required'], 403);
        }

        $source    = $movie->streamSource();
        $sourceLow = method_exists($movie, 'streamSourceLow')
            ? ($movie->streamSourceLow()['url'] ?? null)
            : null;
        $poster = $movie->backdrop_url ?: $movie->poster_url;

        $movieClass = (new Movie)->getMorphClass();
        $items = WatchlistItem::where('user_id', auth()->id())
            ->where('watchable_type', $movieClass)
            ->with(['watchable'])
            ->latest('added_at')
            ->get();

        $idx = $items->search(fn ($i) => $i->watchable_id === $movie->id);
        $prev = ($idx !== false && $idx > 0) ? $items[$idx - 1]->watchable : null;
        $next = ($idx !== false && $idx < $items->count() - 1) ? $items[$idx + 1]->watchable : null;

        $resume = 0;
        $history = WatchHistoryItem::where('user_id', auth()->id())
            ->where('watchable_type', $movie->getMorphClass())
            ->where('watchable_id', $movie->id)
            ->first();
        if ($history && !$history->completed) {
            $resume = $history->position_seconds;
        }

        return response()->json([
            'id'             => $movie->id,
            'detailUrl'      => route('frontend.watchlist_play', $movie->slug),
            'title'          => $movie->title,
            'videoUrl'       => $source['url'] ?? null,
            'videoUrlLow'    => $sourceLow,
            'poster'         => $poster,
            'resumePosition' => $resume,
            'nextContent'    => $next ? [
                'slug'  => $next->slug,
                'url'   => route('frontend.watchlist_play', $next->slug),
                'label' => $next->title,
            ] : null,
            'previousContent' => $prev ? [
                'slug'  => $prev->slug,
                'url'   => route('frontend.watchlist_play', $prev->slug),
                'label' => $prev->title,
            ] : null,
        ]);
    }

    /**
     * Remove a specific watchlist row. Used by the delete button on
     * the /watchlist-detail page — the row's ID is known, so we don't
     * need to resolve by morph class.
     */
    public function removeFromWatchlist(int $id): JsonResponse
    {
        $userId = auth()->id();
        if (!$userId) {
            return response()->json(['error' => 'unauthenticated'], 401);
        }

        $deleted = WatchlistItem::where('id', $id)
            ->where('user_id', $userId)
            ->delete();

        return response()->json(['ok' => (bool) $deleted]);
    }

    public function view_all()
    {
        return view('frontend::Pages.view-all');
    }

    // Genres Pages Routes
    public function genres(?string $slug = null)
    {
        if ($slug) {
            $genre = Genre::where('slug', $slug)->firstOrFail();
            $movies = $genre->movies()->published()->with('genres')->get();
            $shows = $genre->shows()->published()->with('genres')->get();
            $featured = $this->featuredForGenre($genre->id);
            return view('frontend::Pages.geners-page', compact('genre', 'movies', 'shows', 'featured'));
        }

        $genres = Genre::withCount(['movies', 'shows'])->orderBy('name')->get();
        return view('frontend::Pages.geners-page', compact('genres'));
    }

    /**
     * Up to 3 movies + 3 shows in the given genre, interleaved (movie,
     * show, movie, show, …) for the hero carousel. Mirrors the
     * mixed-hero pattern SectionDataComposer::buildHero() uses on the
     * home page so the visual rhythm is consistent. Each item is
     * tagged `_isShow` so the blade can pick the right detail route
     * and relations without a separate instance check.
     */
    private function featuredForGenre(int $genreId)
    {
        $inGenre = fn ($q) => $q->where('genres.id', $genreId);

        $movies = Movie::published()
            ->whereHas('genres', $inGenre)
            ->with('genres')
            ->orderByDesc('published_at')
            ->take(3)
            ->get()
            ->each(fn ($m) => $m->_isShow = false);

        $shows = Show::published()
            ->whereHas('genres', $inGenre)
            ->with(['genres', 'seasons'])
            ->orderByDesc('published_at')
            ->take(3)
            ->get()
            ->each(fn ($s) => $s->_isShow = true);

        $items = collect();
        $max = max($movies->count(), $shows->count());
        for ($i = 0; $i < $max; $i++) {
            if (isset($movies[$i])) $items->push($movies[$i]);
            if (isset($shows[$i]))  $items->push($shows[$i]);
        }

        return $items;
    }

    /* ---------------------------------------------------------------
     | Categories (mirror of genres — curated shelves like Trending,
     | Editor's Picks, etc. admins assign via the admin UI)
     | --------------------------------------------------------------- */

    public function all_categories()
    {
        $categories = Category::withCount(['movies', 'shows'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('frontend::Pages.categories-page', compact('categories'));
    }

    public function category(string $slug)
    {
        $category = Category::where('slug', $slug)->firstOrFail();
        $movies = $category->movies()->published()->with('genres')->get();
        $shows  = $category->shows()->published()->with('genres')->get();

        return view('frontend::Pages.categories-page', compact('category', 'movies', 'shows'));
    }

    public function all_genres()
    {
        $genres = Genre::withCount(['movies', 'shows'])->orderBy('name')->get();
        return view('frontend::Pages.all-geners-page', compact('genres'));
    }

    // tag Pages Routes
    public function tag(?string $slug = null)
    {
        if ($slug) {
            $tag = Tag::where('slug', $slug)->firstOrFail();
            $movies = $tag->movies()->published()->with('genres')->get();
            $shows = $tag->shows()->published()->with('genres')->get();
            return view('frontend::Pages.tags-page', compact('tag', 'movies', 'shows'));
        }

        $tags = Tag::withCount(['movies', 'shows'])->orderBy('name')->get();
        return view('frontend::Pages.tags-page', compact('tags'));
    }

    public function view_all_tags()
    {
        $tags = Tag::withCount(['movies', 'shows'])
            ->orderBy('name')
            ->get();

        return view('frontend::Pages.view-all-tags', compact('tags'));
    }

    // cast Pages Routes
    public function cast_details(?string $slug = null)
    {
        // No slug → no specific person to show. Bouncing to the cast
        // list is sane behaviour; the previous Person::firstOrFail()
        // returned the alphabetical first person regardless of which
        // card the user clicked, which is the source of the
        // "everyone opens Idris Elba" bug.
        if (!$slug) {
            return redirect()->route('frontend.cast_list');
        }

        $person = Person::where('slug', $slug)->firstOrFail();

        $person->load(['movies' => fn ($q) => $q->published(), 'shows' => fn ($q) => $q->published()]);

        return view('frontend::Pages.Cast.detail-page', compact('person'));
    }

    public function cast_list()
    {
        $persons = Person::withCount(['movies', 'shows'])->orderBy('last_name')->get();
        return view('frontend::Pages.Cast.list-page', compact('persons'));
    }

    public function all_personality()
    {
        $persons = Person::withCount(['movies', 'shows'])->orderBy('last_name')->get();
        return view('frontend::Pages.Cast.all-personality', compact('persons'));
    }

    // playlist Pages Routes
    // Extra Pages
    public function about_us()
    {
        return $this->renderManagedPage('about-us');
    }

    public function contact_us()
    {
        $page = Page::published()->where('slug', 'contact-us')->firstOrFail();

        return view('frontend::Pages.ExtraPages.managed-contact-page', ['page' => $page]);
    }

    /**
     * Handle the public contact-form submission. Recipient address is
     * read from the Contact page's admin-managed meta so it can be
     * changed without touching code or env. Falls back to the app's
     * mail-from address when admin hasn't set one.
     */
    public function contact_us_submit(Request $request)
    {
        // Honeypot — same field name as the auth forms (see
        // resources/views/components/auth/bot-defence.blade.php).
        // Bots fill the hidden `website` input; we silently flash the
        // success state without sending mail so the bot moves on.
        if (filled($request->input('website'))) {
            \Illuminate\Support\Facades\Log::info('[contact-form] honeypot triggered', ['ip' => $request->ip()]);
            return back()->with('contact_success', 'Thanks — your message has been sent. We\'ll be in touch shortly.');
        }

        // Optional reCAPTCHA, configurable from /admin/settings.
        // Pass-through when admin hasn't wired keys.
        if (!\App\Services\RecaptchaService::verify($request->input('g-recaptcha-response'), 'contact')) {
            return back()
                ->withInput()
                ->withErrors(['g-recaptcha-response' => 'reCAPTCHA verification failed. Please try again.']);
        }

        $data = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:50',
            'message' => 'required|string|max:5000',
        ]);

        $page = Page::where('slug', 'contact-us')->first();
        $recipient = $page?->metaValue('form_recipient_email') ?: config('mail.from.address');

        if (! $recipient) {
            return back()
                ->withInput()
                ->with('contact_error', 'Sorry — the contact form is not configured to send email yet.');
        }

        try {
            \Illuminate\Support\Facades\Mail::raw(
                "New contact-form submission\n\n"
                . "Name: {$data['first_name']} {$data['last_name']}\n"
                . "Email: {$data['email']}\n"
                . "Phone: {$data['phone']}\n\n"
                . "Message:\n{$data['message']}",
                function ($mail) use ($recipient, $data) {
                    $mail->to($recipient)
                        ->replyTo($data['email'], $data['first_name'] . ' ' . $data['last_name'])
                        ->subject('Contact form: ' . $data['first_name'] . ' ' . $data['last_name']);
                }
            );

            return back()->with('contact_success', 'Thanks — your message has been sent. We\'ll be in touch shortly.');
        } catch (\Throwable $e) {
            report($e);
            return back()
                ->withInput()
                ->with('contact_error', 'Sorry — we couldn\'t send your message right now. Please try again later.');
        }
    }

    public function faq_page()
    {
        $page = Page::published()->where('slug', 'faqs')->firstOrFail();

        return view('frontend::Pages.ExtraPages.managed-faq-page', ['page' => $page]);
    }

    public function privacy()
    {
        return $this->renderManagedPage('privacy-policy');
    }

    public function terms_and_policy()
    {
        return $this->renderManagedPage('terms-of-use');
    }

    /**
     * Every static page renders from the admin Pages CMS — title, body,
     * and optional featured image are entirely admin-controlled. A draft
     * page or one that doesn't exist 404s; the seeded system pages always
     * exist + are published, so this only fires for pages an admin
     * deliberately removed.
     */
    private function renderManagedPage(string $slug)
    {
        $page = Page::published()->where('slug', $slug)->firstOrFail();

        return view('frontend::Pages.ExtraPages.managed-page', ['page' => $page]);
    }

    public function comming_soon_page()
    {
        return view('frontend::Pages.ExtraPages.comming-soon-page');
    }

    public function pricing_page()
    {
        $tiers = \Modules\Subscriptions\app\Models\SubscriptionTier::active()->ordered()->get();
        return view('frontend::Pages.pricing-page', compact('tiers'));
    }

    public function error_page1()
    {
        return view('frontend::Pages.ExtraPages.error-page1');
    }

    public function error_page2()
    {
        return view('frontend::Pages.ExtraPages.error-page2');
    }

    /**
     * Account dashboard — profile summary, active subscription, and
     * the five most-recent payment orders. The routes group already
     * forces auth, so `auth()->user()` is always set here.
     */
    public function membership_account()
    {
        $user = auth()->user();

        $activeSub = UserSubscription::with('tier')
            ->where('user_id', $user->id)
            ->current()
            ->orderByDesc('ends_at')
            ->first();

        $recentOrders = PaymentOrder::where('user_id', $user->id)
            ->with('payable.tier')
            ->latest()
            ->take(5)
            ->get();

        return view('frontend::Pages.Profile.membership-account', compact('user', 'activeSub', 'recentOrders'));
    }

    /**
     * Full payment-order history, paginated.
     */
    public function membership_orders()
    {
        $orders = PaymentOrder::where('user_id', auth()->id())
            ->with('payable.tier')
            ->latest()
            ->paginate(15);

        return view('frontend::Pages.Profile.membership-orders', compact('orders'));
    }

    /**
     * Single-order invoice. Accepts an order id (`{order?}`); falls
     * back to the most recent one for the user when not provided so
     * bookmarking `/membership-invoice` still shows something useful.
     * Ownership check via route-model binding implicit scope.
     */
    public function membership_invoice(?PaymentOrder $order = null)
    {
        $user = auth()->user();

        if ($order && $order->exists) {
            abort_if($order->user_id !== $user->id, 404);
        } else {
            $order = PaymentOrder::where('user_id', $user->id)
                ->with('payable.tier')
                ->latest()
                ->first();
        }

        if ($order) {
            $order->loadMissing('payable.tier');
        }

        return view('frontend::Pages.Profile.membership-invoice', compact('user', 'order'));
    }

    /**
     * All active tiers with the user's current tier flagged so the
     * "current plan" card can highlight itself.
     */
    public function membership_level()
    {
        $user = auth()->user();

        $currentSub = UserSubscription::with('tier')
            ->where('user_id', $user->id)
            ->current()
            ->orderByDesc('ends_at')
            ->first();

        $currentTierId = $currentSub?->subscription_tier_id;

        $tiers = SubscriptionTier::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('access_level')
            ->get();

        return view('frontend::Pages.Profile.membership-level', compact('tiers', 'currentTierId', 'currentSub'));
    }

    /**
     * Post-checkout landing — renders the most recent successful
     * payment for the user. If the user lands here without any
     * completed orders (fresh signup, direct link), the view shows
     * an empty-state with a link back to /pricing.
     */
    public function membership_comfirmation()
    {
        $order = PaymentOrder::where('user_id', auth()->id())
            ->where('status', PaymentOrder::STATUS_COMPLETED)
            ->with('payable.tier')
            ->latest()
            ->first();

        return view('frontend::Pages.Profile.membership-comfirmation', compact('order'));
    }

    public function your_profile()
    {
        return view('frontend::Pages.Profile.your-profile', [
            'user' => auth()->user(),
        ]);
    }

    /**
     * Update the authenticated user's profile. Only the fields
     * rendered on the your-profile page are mass-assignable here;
     * password changes have a dedicated flow at /change-password.
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['required', 'string', 'max:100'],
            'email'      => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
        ]);

        $user->update($data);

        return redirect()->route('frontend.your-profile')
            ->with('status', __('streamAccount.profile_updated') ?? 'Profile updated.');
    }

    public function change_password()
    {
        return view('frontend::Pages.Profile.change-password');
    }
}
