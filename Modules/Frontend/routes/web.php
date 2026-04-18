<?php

use Illuminate\Support\Facades\Route;
use Modules\Frontend\app\Http\Controllers\FrontendController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group([], function () {
    //main pages
    Route::get('/', [FrontendController::class, 'ott'])->name('frontend.ott');
    Route::get('/home', [FrontendController::class, 'index'])->name('frontend.index');
    Route::get('/search', [FrontendController::class, 'search'])->name('frontend.search');
    Route::get('/movie', [FrontendController::class, 'movie'])->name('frontend.movie');
    Route::get('/movie/more-vjs', [FrontendController::class, 'moreVjsForMoviesPage'])->name('frontend.movie_more_vjs');
    Route::get('/vj/{slug}', [FrontendController::class, 'vjDetail'])->name('frontend.vj_detail');
    Route::get('/vj/{slug}/more', [FrontendController::class, 'vjGenreLoadMore'])->name('frontend.vj_genre_more');
    Route::get('/series', [FrontendController::class, 'tv_show'])->name('frontend.series');
    // NOTE: /series/more-vjs must be registered before /series/{slug}
    // below, otherwise Laravel matches it as a show slug.
    Route::get('/series/more-vjs', [FrontendController::class, 'moreVjsForSeriesPage'])->name('frontend.series_more_vjs');
    Route::get('/vj-series/{slug}', [FrontendController::class, 'vjSeriesDetail'])->name('frontend.vj_series_detail');
    Route::get('/vj-series/{slug}/more', [FrontendController::class, 'vjSeriesGenreLoadMore'])->name('frontend.vj_series_genre_more');
    Route::redirect('/tv-show', '/series', 301);

    //detail pages
    Route::get('/movie-detail/{slug?}', [FrontendController::class, 'movie_detail'])->name('frontend.movie_detail');
    Route::get('/watch/{slug?}', [FrontendController::class, 'movie_watch'])->middleware('auth')->name('frontend.watch');
    Route::get('/movie-player', [FrontendController::class, 'movie_player'])->name('frontend.movie_player');
    Route::get('/download', [FrontendController::class, 'download'])->name('frontend.download');
    Route::get('/view-more', [FrontendController::class, 'view_more'])->name('frontend.view-more');
    Route::get('/resticted', [FrontendController::class, 'resticted'])->name('frontend.resticted');
    Route::get('/series/{slug}', [FrontendController::class, 'tvshow_detail'])->name('frontend.series_detail');
    Route::get('/tv-show-detail/{slug?}', function (?string $slug = null) {
        return $slug
            ? redirect()->route('frontend.series_detail', ['slug' => $slug], 301)
            : redirect()->route('frontend.series', [], 301);
    });
    Route::get('/episode/{slug?}', [FrontendController::class, 'episode'])->middleware('auth')->name('frontend.episode');
    Route::get('/api/v1/episodes/{episode}/player-data', [FrontendController::class, 'episodePlayerData'])
        ->middleware('auth')
        ->name('frontend.episode_player_data');
    Route::delete('/api/v1/continue-watching/{type}/{id}', [FrontendController::class, 'removeFromContinueWatching'])
        ->middleware('auth')
        ->whereIn('type', ['movie', 'show'])
        ->whereNumber('id')
        ->name('frontend.continue_watching_remove');
    Route::get('/api/v1/watchlist/{slug}/player-data', [FrontendController::class, 'watchlistMoviePlayerData'])
        ->middleware('auth')
        ->where('slug', '[A-Za-z0-9-]+')
        ->name('frontend.watchlist_player_data');
    Route::post('/api/v1/watchlist/{type}/{id}', [FrontendController::class, 'toggleWatchlist'])
        ->middleware('auth')
        ->whereIn('type', ['movie', 'show', 'episode'])
        ->whereNumber('id')
        ->name('frontend.watchlist_toggle');
    Route::delete('/api/v1/watchlist/{id}', [FrontendController::class, 'removeFromWatchlist'])
        ->middleware('auth')
        ->whereNumber('id')
        ->name('frontend.watchlist_remove');
    Route::get('/person-detail', [FrontendController::class, 'person_detail'])->name('frontend.person_detail');
    Route::get('/watchlist', [FrontendController::class, 'watchlist_detail'])->middleware('auth')->name('frontend.watchlist_detail');
    // Prettier URL: /watchlist/{movie-slug}. Only Movies are served by
    // this endpoint — episodes in the queue link to /episode/{id}
    // (already wired), and shows to /series/{slug}.
    Route::get('/watchlist/{slug}', [FrontendController::class, 'watchlistPlay'])
        ->middleware('auth')
        ->where('slug', '[A-Za-z0-9-]+')
        ->name('frontend.watchlist_play');
    // Back-compat for any bookmarked URLs from the previous scheme.
    Route::redirect('/watchlist-detail', '/watchlist', 301);
    Route::get('/watchlist-detail/play/{id}', function ($id) {
        $item = \Modules\Streaming\app\Models\WatchlistItem::where('id', $id)
            ->where('user_id', auth()->id())
            ->with('watchable')
            ->first();
        if (!$item || !$item->watchable) {
            return redirect()->route('frontend.watchlist_detail');
        }
        $w = $item->watchable;
        if ($w instanceof \Modules\Content\app\Models\Movie) {
            return redirect()->route('frontend.watchlist_play', $w->slug, 301);
        }
        if ($w instanceof \Modules\Content\app\Models\Episode) {
            return redirect()->route('frontend.episode', $w->id, 301);
        }
        return redirect()->route('frontend.watchlist_detail');
    })->middleware('auth');
    Route::get('/playlist-detail', [FrontendController::class, 'playlist_detail'])->name('frontend.playlist_detail');
    Route::get('/view-all', [FrontendController::class, 'view_all'])->name('frontend.view_all');

    //Genres pages
    Route::get('/geners/{slug?}', [FrontendController::class, 'genres'])->name('frontend.genres');
    Route::get('/all-genres', [FrontendController::class, 'all_genres'])->name('frontend.all-genres');

    //cast pages
    Route::get('/cast-list', [FrontendController::class, 'cast_list'])->name('frontend.cast_list');
    Route::get('/cast-details/{slug?}', [FrontendController::class, 'cast_details'])->name('frontend.cast_details');
    Route::get('/all-personality', [FrontendController::class, 'all_personality'])->name('frontend.all_personality');

    //tag pages
    Route::get('/tag/{slug?}', [FrontendController::class, 'tag'])->name('frontend.tag');
    Route::get('/view-all-tags', [FrontendController::class, 'view_all_tags'])->name('frontend.view-all-tags');
    Route::get('/playlist', [FrontendController::class, 'play_list'])->name('frontend.play_list');

    // Extra Pages
    Route::get('/about-us', [FrontendController::class, 'about_us'])->name('frontend.about_us');
    Route::get('/contact-us', [FrontendController::class, 'contact_us'])->name('frontend.contact_us');
    Route::get('/faq_page', [FrontendController::class, 'faq_page'])->name('frontend.faq_page');
    Route::get('/privacy-policy', [FrontendController::class, 'privacy'])->name('frontend.privacy-policy');
    Route::get('/terms-and-policy', [FrontendController::class, 'terms_and_policy'])->name('frontend.terms-and-policy');
    Route::get('/comming-soon', [FrontendController::class, 'comming_soon_page'])->name('frontend.comming-soon');
    Route::get('/pricing-page', [FrontendController::class, 'pricing_page'])->name('frontend.pricing-page');
    Route::get('/error-page1', [FrontendController::class, 'error_page1'])->name('frontend.error_page1');
    Route::get('/error-page2', [FrontendController::class, 'error_page2'])->name('frontend.error_page2');

    //Profile
    Route::get('/profile-marvin', [FrontendController::class, 'profile_marvin'])->name('frontend.profile-marvin');
    Route::get('/archive-playlist', [FrontendController::class, 'archive_playlist'])->name('frontend.archive-playlist');
    Route::get('/membership-invoice', [FrontendController::class, 'membership_invoice'])->name('frontend.membership-invoice');
    Route::get('/membership-orders', [FrontendController::class, 'membership_orders'])->name('frontend.membership-orders');
    Route::get('/membership-account', [FrontendController::class, 'membership_account'])->name('frontend.membership-account');
    Route::get('/membership-level', [FrontendController::class, 'membership_level'])->name('frontend.membership-level');
    Route::get('/membership-comfirmation', [FrontendController::class, 'membership_comfirmation'])->name('frontend.membership-comfirmation');
    Route::get('/your-profile', [FrontendController::class, 'your_profile'])->middleware('auth')->name('frontend.your-profile');
    Route::post('/your-profile', [FrontendController::class, 'updateProfile'])->middleware('auth')->name('frontend.your-profile.update');
    Route::get('/change-password', [FrontendController::class, 'change_password'])->name('frontend.change-password');
});
