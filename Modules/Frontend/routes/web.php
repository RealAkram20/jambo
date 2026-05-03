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
    // /search is the full-page results view (form's submit target).
    // /search/suggest is the JSON endpoint the header dropdown polls.
    // Splitting them prevents the form from landing the user on the
    // raw JSON when they press Enter — they get the styled results
    // page instead, while the live dropdown keeps its own JSON feed.
    Route::get('/search', [FrontendController::class, 'searchPage'])->name('frontend.search');
    Route::get('/search/suggest', [FrontendController::class, 'searchSuggest'])->name('frontend.search.suggest');
    Route::get('/movie', [FrontendController::class, 'movie'])->name('frontend.movie');
    Route::get('/upcoming', [FrontendController::class, 'upcomingPage'])->name('frontend.upcoming');
    Route::get('/upcoming/load-more', [FrontendController::class, 'upcomingLoadMore'])->name('frontend.upcoming_load_more');
    Route::get('/movie/more-vjs', [FrontendController::class, 'moreVjsForMoviesPage'])->name('frontend.movie_more_vjs');
    // Combined overview — movies + series for one VJ. The two
    // type-scoped detail pages live at /vj-movie/{slug} and
    // /vj-series/{slug} below.
    Route::get('/vj/{slug}', [FrontendController::class, 'vjOverview'])->name('frontend.vj_detail');
    // Movies-only VJ catalogue (formerly at /vj/{slug}). Renamed so
    // /vj/{slug} could host the combined overview. The 301 below
    // covers any old links pointing at /vj/{slug}/more.
    Route::get('/vj-movie/{slug}', [FrontendController::class, 'vjMovieDetail'])->name('frontend.vj_movie_detail');
    Route::get('/vj-movie/{slug}/more', [FrontendController::class, 'vjMovieGenreLoadMore'])->name('frontend.vj_movie_genre_more');
    Route::get('/vj/{slug}/more', function (string $slug) {
        return redirect()->route('frontend.vj_movie_genre_more', ['slug' => $slug] + request()->query(), 301);
    });
    Route::get('/series', [FrontendController::class, 'tv_show'])->name('frontend.series');
    // NOTE: /series/more-vjs must be registered before /series/{slug}
    // below, otherwise Laravel matches it as a show slug.
    Route::get('/series/more-vjs', [FrontendController::class, 'moreVjsForSeriesPage'])->name('frontend.series_more_vjs');
    Route::get('/vj-series/{slug}', [FrontendController::class, 'vjSeriesDetail'])->name('frontend.vj_series_detail');
    Route::get('/vj-series/{slug}/more', [FrontendController::class, 'vjSeriesGenreLoadMore'])->name('frontend.vj_series_genre_more');
    Route::redirect('/tv-show', '/series', 301);

    //detail pages
    Route::get('/movie-detail/{slug?}', [FrontendController::class, 'movie_detail'])->name('frontend.movie_detail');
    // Guest-friendly: free content plays for everyone; premium content
    // sends guests to login via the tier check inside the controller.
    Route::get('/watch/{slug?}', [FrontendController::class, 'movie_watch'])->name('frontend.watch');
    Route::get('/series/{slug}', [FrontendController::class, 'tvshow_detail'])->name('frontend.series_detail');
    Route::get('/tv-show-detail/{slug?}', function (?string $slug = null) {
        return $slug
            ? redirect()->route('frontend.series_detail', ['slug' => $slug], 301)
            : redirect()->route('frontend.series', [], 301);
    });
    // Same guest-friendly logic as /watch.
    // Pretty episode URL: /episode/<show-slug>/s<season-number>/ep<episode-number>.
    // The show segment matches any slug; season and episode are constrained
    // to digits via the 's' / 'ep' prefixes in the URL + numeric wheres.
    Route::get('/episode/{show}/s{season}/ep{episode}', [FrontendController::class, 'episode'])
        ->where('season', '\d+')
        ->where('episode', '\d+')
        ->name('frontend.episode');

    // Legacy fallback: old numeric-id URLs redirect into the pretty form so
    // any links shared before the rename keep working.
    Route::get('/episode/{id}', [FrontendController::class, 'episodeLegacyRedirect'])
        ->where('id', '\d+')
        ->name('frontend.episode_legacy');
    Route::get('/api/v1/episodes/{episode}/player-data', [FrontendController::class, 'episodePlayerData'])
        ->middleware('auth')
        ->name('frontend.episode_player_data');
    Route::delete('/api/v1/continue-watching/{type}/{id}', [FrontendController::class, 'removeFromContinueWatching'])
        ->middleware('auth')
        ->whereIn('type', ['movie', 'show'])
        ->whereNumber('id')
        ->name('frontend.continue_watching_remove');

    // Reviews + comments (auth-gated writes)
    Route::middleware('auth')->group(function () {
        Route::post('/movies/{slug}/reviews', [FrontendController::class, 'storeMovieReview'])->name('frontend.movie_review_store');
        Route::delete('/movies/{slug}/reviews', [FrontendController::class, 'destroyMovieReview'])->name('frontend.movie_review_destroy');

        Route::post('/series/{slug}/reviews', [FrontendController::class, 'storeShowReview'])->name('frontend.series_review_store');
        Route::delete('/series/{slug}/reviews', [FrontendController::class, 'destroyShowReview'])->name('frontend.series_review_destroy');

        Route::post('/episodes/{episode}/comments', [FrontendController::class, 'storeEpisodeComment'])->name('frontend.episode_comment_store');
        Route::delete('/comments/{comment}', [FrontendController::class, 'destroyComment'])->name('frontend.comment_destroy');
    });
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
    // Legacy /watchlist — now lives under the profile hub. Kept as a
    // named redirect so card/header templates referencing
    // `route('frontend.watchlist_detail')` still resolve.
    Route::get('/watchlist', function () {
        return redirect()->route('profile.watchlist', ['username' => auth()->user()->username]);
    })->middleware('auth')->name('frontend.watchlist_detail');
    // Prettier URL: /watchlist/{movie-slug}. Only Movies are served by
    // this endpoint — episodes in the queue link to /episode/{id}
    // (already wired), and shows to /series/{slug}.
    Route::get('/watchlist/{slug}', [FrontendController::class, 'watchlistPlay'])
        ->middleware('auth')
        ->where('slug', '[A-Za-z0-9-]+')
        ->name('frontend.watchlist_play');
    // Series entry point from the watchlist: resolves to the
    // resume-or-first episode, then redirects to /episode/{id}.
    Route::get('/watchlist/series/{slug}', [FrontendController::class, 'watchlistSeriesPlay'])
        ->middleware('auth')
        ->where('slug', '[A-Za-z0-9-]+')
        ->name('frontend.watchlist_series_play');
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
            return redirect($w->frontendUrl(), 301);
        }
        return redirect()->route('frontend.watchlist_detail');
    })->middleware('auth');
    Route::get('/view-all', [FrontendController::class, 'view_all'])->name('frontend.view_all');

    //Genres pages
    // NOTE: /geners/{slug}/vjs, /series-vjs and their more endpoints
    // must be registered before /geners/{slug?} so Laravel matches
    // them as their own handlers rather than falling through to the
    // single-genre view.
    Route::get('/geners/{slug}/vjs', [FrontendController::class, 'genreVjs'])->name('frontend.genre_vjs');
    Route::get('/geners/{slug}/vjs/more', [FrontendController::class, 'genreVjsLoadMore'])->name('frontend.genre_vjs_more');
    Route::get('/geners/{slug}/series-vjs', [FrontendController::class, 'genreVjsShows'])->name('frontend.genre_vjs_shows');
    Route::get('/geners/{slug}/series-vjs/more', [FrontendController::class, 'genreVjsShowsLoadMore'])->name('frontend.genre_vjs_shows_more');
    Route::get('/geners/{slug?}', [FrontendController::class, 'genres'])->name('frontend.genres');
    Route::get('/all-genres', [FrontendController::class, 'all_genres'])->name('frontend.all-genres');

    //Categories pages (mirror of genres)
    Route::get('/categories', [FrontendController::class, 'all_categories'])->name('frontend.all-categories');
    Route::get('/categories/{slug}', [FrontendController::class, 'category'])->name('frontend.category');

    // Notifications live in the Notifications module — its routes
    // are `notifications.index`, `notifications.read`,
    // `notifications.mark-all-read`, `notifications.destroy`. The
    // header bell + frontend notifications page both resolve to
    // them; no frontend-side duplicates here.

    //cast pages
    Route::get('/cast-list', [FrontendController::class, 'cast_list'])->name('frontend.cast_list');
    Route::get('/cast-details/{slug?}', [FrontendController::class, 'cast_details'])->name('frontend.cast_details');
    Route::get('/all-personality', [FrontendController::class, 'all_personality'])->name('frontend.all_personality');

    //tag pages
    Route::get('/tag/{slug?}', [FrontendController::class, 'tag'])->name('frontend.tag');
    Route::get('/view-all-tags', [FrontendController::class, 'view_all_tags'])->name('frontend.view-all-tags');

    // Extra Pages
    Route::get('/about-us', [FrontendController::class, 'about_us'])->name('frontend.about_us');
    Route::get('/contact-us', [FrontendController::class, 'contact_us'])->name('frontend.contact_us');
    Route::post('/contact-us', [FrontendController::class, 'contact_us_submit'])
        ->middleware('throttle:5,10')
        ->name('frontend.contact_us.submit');
    Route::get('/faq_page', [FrontendController::class, 'faq_page'])->name('frontend.faq_page');
    Route::get('/privacy-policy', [FrontendController::class, 'privacy'])->name('frontend.privacy-policy');
    Route::get('/terms-and-policy', [FrontendController::class, 'terms_and_policy'])->name('frontend.terms-and-policy');
    Route::get('/comming-soon', [FrontendController::class, 'comming_soon_page'])->name('frontend.comming-soon');
    // Pricing lives at /pricing. Route name kept as `frontend.pricing-page`
    // so the ~13 callsites that reference it don't need touching; the old
    // `/pricing-page` URL 301s into the new shorter form for any
    // bookmarks / external links that point at it.
    Route::get('/pricing', [FrontendController::class, 'pricing_page'])->name('frontend.pricing-page');
    Route::redirect('/pricing-page', '/pricing', 301);
    Route::get('/error-page1', [FrontendController::class, 'error_page1'])->name('frontend.error_page1');
    Route::get('/error-page2', [FrontendController::class, 'error_page2'])->name('frontend.error_page2');

    //Profile
    // Legacy membership URLs — all now redirect into the profile
    // hub's Membership / Billing tabs. Route names are preserved so
    // any templates still using `route('frontend.membership-*')` keep
    // working until they're rewritten.
    Route::middleware('auth')->group(function () {
        Route::get('/membership-account', function () {
            return redirect()->route('profile.membership', ['username' => auth()->user()->username]);
        })->name('frontend.membership-account');

        Route::get('/membership-orders', function () {
            return redirect()->route('profile.billing', ['username' => auth()->user()->username]);
        })->name('frontend.membership-orders');

        Route::get('/membership-invoice/{order?}', function ($order = null) {
            $username = auth()->user()->username;
            return $order
                ? redirect()->route('profile.invoice', ['username' => $username, 'orderId' => $order])
                : redirect()->route('profile.billing', ['username' => $username]);
        })->name('frontend.membership-invoice');

        Route::get('/membership-level', function () {
            return redirect()->route('profile.membership', ['username' => auth()->user()->username]);
        })->name('frontend.membership-level');

        Route::get('/membership-comfirmation', [FrontendController::class, 'membership_comfirmation'])
            ->name('frontend.membership-comfirmation');

        Route::get('/your-profile', function () {
            return redirect()->route('profile.show', ['username' => auth()->user()->username]);
        })->name('frontend.your-profile');

        Route::get('/change-password', function () {
            return redirect()->route('profile.security', ['username' => auth()->user()->username]);
        })->name('frontend.change-password');
    });
});
