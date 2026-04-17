<?php

namespace Modules\Frontend\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Show;
use Modules\Content\app\Models\Genre;
use Modules\Content\app\Models\Tag;
use Modules\Content\app\Models\Person;
use Modules\Streaming\app\Models\WatchHistoryItem;
use Modules\Content\app\Models\Episode;
use Modules\Subscriptions\app\Models\SubscriptionTier;
use Modules\Subscriptions\app\Models\UserSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FrontendController extends Controller
{
    public function search(Request $request): JsonResponse
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
                'url' => route('frontend.tvshow_detail', $s->slug),
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

        return view('frontend::Pages.MainPages.index-page', compact(
            'featuredMovies',
            'latestMovies',
            'popularShows',
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

        return view('frontend::Pages.MainPages.ott-page', compact(
            'featuredMovies',
            'latestMovies',
            'popularShows',
        ));
    }

    public function movie()
    {
        $featuredMovies = Movie::published()
            ->orderByDesc('published_at')
            ->take(3)
            ->get();

        $movies = Movie::published()
            ->with('genres')
            ->orderByDesc('published_at')
            ->get();

        return view('frontend::Pages.MainPages.movies-page', compact('featuredMovies', 'movies'));
    }

    public function tv_show()
    {
        $featuredShows = Show::published()
            ->orderByDesc('published_at')
            ->take(3)
            ->get();

        $shows = Show::published()
            ->with('genres')
            ->orderByDesc('published_at')
            ->get();

        return view('frontend::Pages.MainPages.tv-shows-page', compact('featuredShows', 'shows'));
    }

    //movies pages
    public function download()
    {
        return view('frontend::Pages.Movies.download-page');
    }

    public function view_more()
    {
        return view('frontend::Pages.view-more');
    }

    public function resticted()
    {
        return view('frontend::Pages.Movies.resticted-page');
    }

    //deatil pages
    public function movie_detail(?string $slug = null)
    {
        $movie = $slug
            ? Movie::where('slug', $slug)->published()->with(['genres', 'tags', 'categories', 'cast'])->firstOrFail()
            : Movie::published()->with(['genres', 'tags', 'categories', 'cast'])->orderByDesc('published_at')->firstOrFail();

        $recommended = Movie::published()
            ->where('id', '!=', $movie->id)
            ->inRandomOrder()
            ->take(6)
            ->get();

        $source = $movie->streamSource();
        $canWatch = $this->userCanWatch($movie);

        return view('frontend::Pages.Movies.detail-page', compact('movie', 'recommended', 'source', 'canWatch'));
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
        $movie = $slug
            ? Movie::where('slug', $slug)->published()->with(['genres', 'tags', 'categories', 'cast'])->firstOrFail()
            : Movie::published()->with(['genres', 'tags', 'categories', 'cast'])->orderByDesc('published_at')->firstOrFail();

        $source = $movie->streamSource();
        $canWatch = $this->userCanWatch($movie);

        if (!$canWatch) {
            return redirect()->route('frontend.pricing-page')
                ->with('info', "A subscription is required to watch \"{$movie->title}\".");
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

    public function movie_player()
    {
        return view('frontend::Pages.Movies.movie-player');
    }

    public function tvshow_detail(?string $slug = null)
    {
        $show = $slug
            ? Show::where('slug', $slug)->published()
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

        return view('frontend::Pages.TvShows.detail-page', compact('show', 'recommended'));
    }

    public function episode(?string $slug = null)
    {
        $episode = null;
        if ($slug) {
            $episode = Episode::where('id', $slug)->first();
        }
        if (! $episode) {
            $episode = Episode::published()->first() ?? Episode::first();
        }

        abort_unless($episode, 404);
        $episode->load(['season.show.genres', 'season.show.seasons.episodes']);
        $show = $episode->season->show;

        $source = $episode->streamSource();
        $canWatch = $this->userCanWatch($episode);

        if (!$canWatch) {
            return redirect()->route('frontend.pricing-page')
                ->with('info', "A subscription is required to watch episodes of \"{$show->title}\".");
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

        $resumePosition = 0;
        if (auth()->check()) {
            $history = WatchHistoryItem::where('user_id', auth()->id())
                ->where('watchable_type', $episode->getMorphClass())
                ->where('watchable_id', $episode->id)
                ->first();
            $resumePosition = ($history && !$history->completed) ? $history->position_seconds : 0;
        }

        return view('frontend::Pages.TvShows.episode-page', compact('episode', 'show', 'source', 'canWatch', 'nextEpisode', 'resumePosition'));
    }

    public function watchlist_detail()
    {
        return view('frontend::Pages.watchlist-detail');
    }

    public function playlist_detail()
    {
        return view('frontend::Pages.playlist-detail');
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
            return view('frontend::Pages.geners-page', compact('genre', 'movies', 'shows'));
        }

        $genres = Genre::withCount(['movies', 'shows'])->orderBy('name')->get();
        return view('frontend::Pages.geners-page', compact('genres'));
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
        return view('frontend::Pages.view-all-tags');
    }

    // cast Pages Routes
    public function cast_details(?string $slug = null)
    {
        $person = $slug
            ? Person::where('slug', $slug)->firstOrFail()
            : Person::firstOrFail();

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
    public function play_list()
    {
        return view('frontend::Pages.playlist');
    }

    // Extra Pages
    public function about_us()
    {
        return view('frontend::Pages.ExtraPages.about-page');
    }

    public function contact_us()
    {
        return view('frontend::Pages.ExtraPages.contact-page');
    }

    public function faq_page()
    {
        return view('frontend::Pages.ExtraPages.faq-page');
    }

    public function privacy()
    {
        return view('frontend::Pages.ExtraPages.privacy-policy-page');
    }

    public function terms_and_policy()
    {
        return view('frontend::Pages.ExtraPages.terms-of-use-page');
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

    // new development
    public function profile_marvin()
    {
        return view('frontend::Pages.profile-marvin');
    }

    public function archive_playlist()
    {
        return view('frontend::Pages.archive-playlist');
    }

    public function membership_invoice()
    {
        return view('frontend::Pages.Profile.membership-invoice');
    }

    public function membership_orders()
    {
        return view('frontend::Pages.Profile.membership-orders');
    }

    public function membership_account()
    {
        return view('frontend::Pages.Profile.membership-account');
    }

    public function membership_level()
    {
        return view('frontend::Pages.Profile.membership-level');
    }

    public function membership_comfirmation()
    {
        return view('frontend::Pages.Profile.membership-comfirmation');
    }

    public function your_profile()
    {
        return view('frontend::Pages.Profile.your-profile');
    }

    public function change_password()
    {
        return view('frontend::Pages.Profile.change-password');
    }
}
