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
    Route::get('/video', [FrontendController::class, 'video'])->name('frontend.video');
    Route::get('/merchandise', [FrontendController::class, 'merchandise'])->name('frontend.merchandise');

    //detail pages
    Route::get('/movie-detail', [FrontendController::class, 'movie_detail'])->name('frontend.movie_detail');
    Route::get('/movie-player', [FrontendController::class, 'movie_player'])->name('frontend.movie_player');
    Route::get('/download', [FrontendController::class, 'download'])->name('frontend.download');
    Route::get('/view-more', [FrontendController::class, 'view_more'])->name('frontend.view-more');
    Route::get('/resticted', [FrontendController::class, 'resticted'])->name('frontend.resticted');
    Route::get('/releted-merchandies', [FrontendController::class, 'releted_merchandies'])->name('frontend.releted_merchandies');
    Route::get('/tv-show-detail', [FrontendController::class, 'tvshow_detail'])->name('frontend.tvshow_detail');
    Route::get('/episode', [FrontendController::class, 'episode'])->name('frontend.episode');
    Route::get('/video-detail', [FrontendController::class, 'video_detail'])->name('frontend.video_detail');
    Route::get('/video-player', [FrontendController::class, 'video_player'])->name('frontend.video_player');
    Route::get('/product-detail', [FrontendController::class, 'product_detail'])->name('frontend.product_detail');
    Route::get('/person-detail', [FrontendController::class, 'person_detail'])->name('frontend.person_detail');
    Route::get('/watchlist-detail', [FrontendController::class, 'watchlist_detail'])->name('frontend.watchlist_detail');
    Route::get('/playlist-detail', [FrontendController::class, 'playlist_detail'])->name('frontend.playlist_detail');
    Route::get('/view-all', [FrontendController::class, 'view_all'])->name('frontend.view_all');

    //Genres pages
    Route::get('/geners', [FrontendController::class, 'genres'])->name('frontend.genres');
    Route::get('/all-genres', [FrontendController::class, 'all_genres'])->name('frontend.all-genres');

    //cast pages
    Route::get('/cast-list', [FrontendController::class, 'cast_list'])->name('frontend.cast_list');
    Route::get('/cast-details', [FrontendController::class, 'cast_details'])->name('frontend.cast_details');
    Route::get('/all-personality', [FrontendController::class, 'all_personality'])->name('frontend.all_personality');

    //tag pages
    Route::get('/tag', [FrontendController::class, 'tag'])->name('frontend.tag');
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

    // Blog Pages
    Route::get('/blog-list-page', [FrontendController::class, 'blog_list_page'])->name('frontend.list_page');
    Route::get('/blog-blog-filter', [FrontendController::class, 'blog_filter'])->name('frontend.blog_filter');
    Route::get('/blog-detail-page', [FrontendController::class, 'blog_detail_page'])->name('frontend.detail_page');
    Route::get('/blog-grid-list', [FrontendController::class, 'blog_grid_list'])->name('frontend.grid_list');
    Route::get('/blog-right-sidebar', [FrontendController::class, 'blog_right_sidebar'])->name('frontend.right_sidebar');
    Route::get('/blog_sidebar-list', [FrontendController::class, 'blog_sidebar_list_page'])->name('frontend.sidebar_list_page');
    Route::get('/blog-category', [FrontendController::class, 'blog_category'])->name('frontend.blog_category');
    Route::get('/blog-tag', [FrontendController::class, 'blog_tag'])->name('frontend.blog_tag');
    Route::get('/blog-date', [FrontendController::class, 'blog_date'])->name('frontend.blog_date');
    Route::get('/blog-author', [FrontendController::class, 'blog_author'])->name('frontend.blog_author');

    //Blog columns
    Route::get('/one-column-grid', [FrontendController::class, 'one_column_grid'])->name('frontend.one_column_grid');
    Route::get('/two-column-grid', [FrontendController::class, 'two_column_grid'])->name('frontend.two_column_grid');
    Route::get('/three-column-grid', [FrontendController::class, 'three_column_grid'])->name('frontend.three_column_grid');
    Route::get('/four-column-grid', [FrontendController::class, 'four_column_grid'])->name('frontend.four_column_grid');


    //Blog sidebar
    Route::get('/blog_left_sidebar', [FrontendController::class, 'blog_left_sidebar'])->name('frontend.left_sidebar');
    Route::get('/blog_right-sidebar', [FrontendController::class, 'blog_right_sidebar'])->name('frontend.right_sidebar');

    //Blog single
    Route::get('/blog-template', [FrontendController::class, 'blog_template'])->name('frontend.blog_template');
    Route::get('/detail-page', [FrontendController::class, 'detail_page'])->name('frontend.detail_page');
    Route::get('/blog-audio', [FrontendController::class, 'blog_audio'])->name('frontend.blog_audio');
    Route::get('/blog-video', [FrontendController::class, 'blog_video'])->name('frontend.blog_video');
    Route::get('/blog-link', [FrontendController::class, 'blog_link'])->name('frontend.blog_link');
    Route::get('/blog-quote', [FrontendController::class, 'blog_quote'])->name('frontend.blog_quote');
    Route::get('/blog-gallery', [FrontendController::class, 'blog_gallery'])->name('frontend.blog_gallery');

    //Blog Pagination style
    Route::get('/blog-pagination', [FrontendController::class, 'blog_pagination'])->name('frontend.blog_pagination');
    Route::get('/blog-loadmore', [FrontendController::class, 'blog_loadmore'])->name('frontend.blog_loadmore');
    Route::get('/blog-infinite-scroll', [FrontendController::class, 'blog_infinite_scroll'])->name('frontend.blog_infinite_scroll');


    Route::get('/shop', [FrontendController::class, 'shop'])->name('frontend.shop');
    Route::get('/all-products', [FrontendController::class, 'all_products'])->name('frontend.all-products');
    Route::get('/my-account', [FrontendController::class, 'my_account'])->name('frontend.my_account');
    Route::get('/cart', [FrontendController::class, 'cart'])->name('frontend.cart');
    Route::get('/wishlist', [FrontendController::class, 'wishlist'])->name('frontend.wishlist');
    Route::get('/chekout', [FrontendController::class, 'chekout'])->name('frontend.chekout');
    Route::get('/order-tracking', [FrontendController::class, 'order_tracking'])->name('frontend.order_tracking');

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
