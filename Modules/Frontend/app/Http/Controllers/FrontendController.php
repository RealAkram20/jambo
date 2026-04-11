<?php

namespace Modules\Frontend\app\Http\Controllers;

use App\Http\Controllers\Controller;

class FrontendController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    //main pages
    public function index()
    {
        return view('frontend::Pages.MainPages.index-page');
    }

    public function ott()
    {
        return view('frontend::Pages.MainPages.ott-page');
    }

    public function movie()
    {
        return view('frontend::Pages.MainPages.movies-page');
    }

    public function tv_show()
    {
        return view('frontend::Pages.MainPages.tv-shows-page');
    }

    public function video()
    {
        return view('frontend::Pages.MainPages.videos-Page');
    }

    public function merchandise()
    {
        return view('frontend::Pages.MainPages.merchandise-page');
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

    public function releted_merchandies()
    {
        return view('frontend::Pages.Movies.releted-merchandies-page');
    }

    //deatil pages
    public function movie_detail()
    {
        return view('frontend::Pages.Movies.detail-page');
    }

    public function movie_player()
    {
        return view('frontend::Pages.Movies.movie-player');
    }

    public function tvshow_detail()
    {
        return view('frontend::Pages.TvShows.detail-page');
    }

    public function episode()
    {
        return view('frontend::Pages.TvShows.episode-page');
    }

    public function video_detail()
    {
        return view('frontend::Pages.videos-detail');
    }

    public function video_player()
    {
        return view('frontend::Pages.video-player');
    }

    public function product_detail()
    {
        return view('frontend::Pages.product-detail');
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

    // Blog Pages
    public function blog_list_page()
    {
        return view('frontend::Pages.Blog.list-page');
    }

    public function blog_filter()
    {
        return view('frontend::Pages.Blog.blog-filter');
    }

    public function blog_detail_page()
    {
        return view('frontend::Pages.Blog.detail-page');
    }

    public function blog_grid_list()
    {
        return view('frontend::Pages.Blog.grid-list');
    }

    public function blog_right_sidebar()
    {
        return view('frontend::Pages.Blog.right-sidebar');
    }

    public function blog_sidebar_list_page()
    {
        return view('frontend::Pages.Blog.sidebar-list-page');
    }

    public function blog_category()
    {
        return view('frontend::Pages.Blog.blog-category');
    }

    public function blog_tag()
    {
        return view('frontend::Pages.Blog.blog-tag');
    }

    public function blog_date()
    {
        return view('frontend::Pages.Blog.blog-date');
    }

    public function blog_author()
    {
        return view('frontend::Pages.Blog.blog-author');
    }

    // Blog column
    public function one_column_grid()
    {
        return view('frontend::Pages.Blog.one-column');
    }

    public function two_column_grid()
    {
        return view('frontend::Pages.Blog.two-column');
    }

    public function three_column_grid()
    {
        return view('frontend::Pages.Blog.three-column');
    }

    public function four_column_grid()
    {
        return view('frontend::Pages.Blog.four-column');
    }

    //Blog sidebar
    public function blog_left_sidebar()
    {
        return view('frontend::Pages.Blog.left-sidebar');
    }

    public function blog_template()
    {
        return view('frontend::Pages.Blog.detail-page');
    }

    public function detail_page()
    {
        return view('frontend::Pages.Blog.detail-page');
    }

    public function blog_audio()
    {
        return view('frontend::Pages.Blog.blog-audio');
    }

    public function blog_video()
    {
        return view('frontend::Pages.Blog.blog-video');
    }

    public function blog_link()
    {
        return view('frontend::Pages.Blog.blog-link');
    }

    public function blog_quote()
    {
        return view('frontend::Pages.Blog.blog-quote');
    }

    public function blog_gallery()
    {
        return view('frontend::Pages.Blog.blog-gallery');
    }

    //Blog Pagination style
    public function blog_pagination()
    {
        return view('frontend::Pages.Blog.blog-pagination');
    }

    public function blog_loadmore()
    {
        return view('frontend::Pages.Blog.blog-loadmore');
    }

    public function blog_infinite_scroll()
    {
        return view('frontend::Pages.Blog.blog-infinite-scroll');
    }

    //shop pages
    public function all_products()
    {
        return view('frontend::Pages.MerchandiseShopPages.all-product-page');
    }

    public function shop()
    {
        return view('frontend::Pages.MerchandiseShopPages.shop-page');
    }

    public function my_account()
    {
        return view('frontend::Pages.MerchandiseShopPages.my-account-page');
    }

    public function cart()
    {
        return view('frontend::Pages.MerchandiseShopPages.cart-page');
    }

    public function wishlist()
    {
        return view('frontend::Pages.MerchandiseShopPages.wishlist-page');
    }

    public function chekout()
    {
        return view('frontend::Pages.MerchandiseShopPages.chekout-page');
    }

    public function order_tracking()
    {
        return view('frontend::Pages.MerchandiseShopPages.order-tracking');
    }

    // new development
    public function profile_marvin()
    {
        return view('frontend::Pages.profile-marvin');
    }

    public function watchlist()
    {
        return view('frontend::Pages.MerchandiseShopPages.order-tracking');
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
