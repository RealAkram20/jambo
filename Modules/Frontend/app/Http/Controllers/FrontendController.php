<?php

namespace Modules\Frontend\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Show;

class FrontendController extends Controller
{
    /**
     * Display a listing of the resource.
     */
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
        return view('frontend::Pages.MainPages.ott-page');
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

        return view('frontend::Pages.Movies.detail-page', compact('movie', 'recommended'));
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

    public function episode()
    {
        return view('frontend::Pages.TvShows.episode-page');
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
    public function genres()
    {
        return view('frontend::Pages.geners-page');
    }

    public function all_genres()
    {
        return view('frontend::Pages.all-geners-page');
    }

    // tag Pages Routes
    public function tag()
    {
        return view('frontend::Pages.tags-page');
    }

    public function view_all_tags()
    {
        return view('frontend::Pages.view-all-tags');
    }

    // cast Pages Routes
    public function cast_details()
    {
        return view('frontend::Pages.Cast.detail-page');
    }

    public function cast_list()
    {
        return view('frontend::Pages.Cast.list-page');
    }

    public function all_personality()
    {
        return view('frontend::Pages.Cast.all-personality');
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
        return view('frontend::Pages.pricing-page');
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
