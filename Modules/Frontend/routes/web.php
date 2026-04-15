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
    Route::get('/movie', [FrontendController::class, 'movie'])->name('frontend.movie');
    Route::get('/tv-show', [FrontendController::class, 'tv_show'])->name('frontend.tv-show');

    //detail pages
    Route::get('/movie-detail/{slug?}', [FrontendController::class, 'movie_detail'])->name('frontend.movie_detail');
    Route::get('/watch/{slug?}', [FrontendController::class, 'movie_watch'])->middleware('auth')->name('frontend.watch');
    Route::get('/movie-player', [FrontendController::class, 'movie_player'])->name('frontend.movie_player');
    Route::get('/download', [FrontendController::class, 'download'])->name('frontend.download');
    Route::get('/view-more', [FrontendController::class, 'view_more'])->name('frontend.view-more');
    Route::get('/resticted', [FrontendController::class, 'resticted'])->name('frontend.resticted');
    Route::get('/tv-show-detail/{slug?}', [FrontendController::class, 'tvshow_detail'])->name('frontend.tvshow_detail');
    Route::get('/episode/{slug?}', [FrontendController::class, 'episode'])->middleware('auth')->name('frontend.episode');
    Route::get('/person-detail', [FrontendController::class, 'person_detail'])->name('frontend.person_detail');
    Route::get('/watchlist-detail', [FrontendController::class, 'watchlist_detail'])->name('frontend.watchlist_detail');
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
    Route::get('/your-profile', [FrontendController::class, 'your_profile'])->name('frontend.your-profile');
    Route::get('/change-password', [FrontendController::class, 'change_password'])->name('frontend.change-password');
});
