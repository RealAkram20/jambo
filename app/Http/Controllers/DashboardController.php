<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $title = __('header.home');
        return view('DashboardPages.IndexPage1', compact('title'));
    }

    public function index1(Request $request)
    {
        $title = __('sidebar.home1');
        return view('DashboardPages.IndexPage', compact('title'));
    }

    public function rating(Request $request)
    {
        $title = __('sidebar.rating');
        return view('DashboardPages.rating.RatingPage', compact('title'));
    }

    public function comment(Request $request)
    {
        $title = __('dashboard.Comment_List');
        return view('DashboardPages.CommentPage', compact('title'));
    }

    public function user(Request $request)
    {
        $title = __('dashboard.user_list');
        return view('DashboardPages.user.ListPage', compact('title'));
    }

    public function movieList(Request $request)
    {
        $title = __('sidebar.movie_list');
        return view('DashboardPages.movies.MovieListPage', compact('title'));
    }

    public function movieGenres(Request $request)
    {
        $title = __('streamTag.genre');
        return view('DashboardPages.movies.MovieGenres', compact('title'));
    }

    public function movieTags(Request $request)
    {
        $title = __('streamTag.tags');
        return view('DashboardPages.movies.MovieTag', compact('title'));
    }

    public function moviePlaylist(Request $request)
    {
        $title = __('sidebar.movie-playlists');
        return view('DashboardPages.movies.MoviePlaylist', compact('title'));
    }

    public function showList(Request $request)
    {
        $title = __('sidebar.show_list');
        return view('DashboardPages.tv-show.ShowListPage', compact('title'));
    }

    public function seasons(Request $request)
    {
        $title = __('sidebar.season');
        return view('DashboardPages.tv-show.SeasonsPage', compact('title'));
    }

    public function episodes(Request $request)
    {
        $title = __('sidebar.episodes');
        return view('DashboardPages.tv-show.EpisodesPage', compact('title'));
    }

    public function showGenres(Request $request)
    {
        $title = __('streamTag.genre');
        return view('DashboardPages.tv-show.ShowGenres', compact('title'));
    }

    public function showTags(Request $request)
    {
        $title = __('streamTag.tags');
        return view('DashboardPages.tv-show.ShowTag', compact('title'));
    }

    public function showPlaylist(Request $request)
    {
        $title = __('sidebar.movie-playlists');
        return view('DashboardPages.tv-show.ShowPlaylist', compact('title'));
    }

    public function videos(Request $request)
    {
        $title = __('sidebar.videos');
        return view('DashboardPages.videos.VideoPage', compact('title'));
    }

    public function videoCategory(Request $request)
    {
        $title = __('sidebar.video-category');
        return view('DashboardPages.videos.VideoCategory', compact('title'));
    }

    public function videoTags(Request $request)
    {
        $title = __('sidebar.video-tags');
        return view('DashboardPages.videos.VideoTag', compact('title'));
    }

    public function videoPlaylist(Request $request)
    {
        $title = __('sidebar.video-playlist');
        return view('DashboardPages.videos.VideoPlaylist', compact('title'));
    }

    public function person(Request $request)
    {
        $title = __('form.persons-list');
        return view('DashboardPages.persons.PersonPage', compact('title'));
    }

    public function personCategories(Request $request)
    {
        $title = __('sidebar.Person-Category');
        return view('DashboardPages.persons.PersonCategoies', compact('title'));
    }

    public function personTags(Request $request)
    {
        $title = __('streamTag.tags');
        return view('DashboardPages.persons.PersonTag', compact('title'));
    }

    public function review(Request $request)
    {
        $title = __('sidebar.review');
        return view('DashboardPages.review.ReviewPage', compact('title'));
    }

    public function pricing(Request $request)
    {
        $title = __('sidebar.pricing');
        return view('DashboardPages.spacial-pages.PricingPage', compact('title'));
    }

    public function login(Request $request)
    {
        $title = 'Login';
        return view('DashboardPages.auth.default.SignIn', compact('title'));
    }

    public function register(Request $request)
    {
        $title = 'Register';
        return view('DashboardPages.auth.default.SignUp', compact('title'));
    }

    public function reset_password(Request $request)
    {
        $title = 'Reset Password';
        return view('DashboardPages.auth.default.ResetPassword', compact('title'));
    }

    public function verify_email(Request $request)
    {
        $title = 'Verify Mail';
        return view('DashboardPages.auth.default.VarifyEmail', compact('title'));
    }

    public function lock_screen(Request $request)
    {
        $title = 'Lock Screen';
        return view('DashboardPages.auth.default.LockScreen', compact('title'));
    }

    public function TwoFactor(Request $request)
    {
        $title = 'Two Factor';
        return view('DashboardPages.auth.default.TwoFactor', compact('title'));
    }

    public function AccountDeactivated(Request $request)
    {
        $title = 'Account Deactivated';
        return view('DashboardPages.auth.default.AccountDeactivated', compact('title'));
    }

    public function error404(Request $request)
    {
        $title = 'Error 404';
        return view('DashboardPages.errors.Error404Page', compact('title'));
    }

    public function error500(Request $request)
    {
        $title = 'Error 500';
        return view('DashboardPages.errors.Error500Page', compact('title'));
    }

    public function maintenance(Request $request)
    {
        $title = 'Maintenance';
        return view('DashboardPages.errors.MaintenancePage', compact('title'));
    }

    public function coming(Request $request)
    {
        $title = 'Comming Soon';
        return view('DashboardPages.errors.ComingSoon', compact('title'));
    }

    public function blank(Request $request)
    {
        $title = __('sidebar.blank_page');
        return view('DashboardPages.BlankPage', compact('title'));
    }
    public function termsOfUse(Request $request)
    {
        $title = __('dashboard.terms_of_use');
        return view('DashboardPages.extra.TermsAndConditions', compact('title'));
    }
    public function dashboardPrivacy(Request $request)
    {
        $title = __('frontendheader.privacy_policy');
        return view('DashboardPages.extra.PrivacyPolicy', compact('title'));
    }

    public function profile(Request $request)
    {
        $title = __('sidebar.user_profile');
        return view('DashboardPages.user-profile', compact('title'));
    }
    public function privacy(Request $request)
    {
        $title = __('sidebar.privacy_setting');
        return view('DashboardPages.user-privacy-setting', compact('title'));
    }
    public function termsAndConditions(Request $request)
    {
        $title = __('sidebar.TermsAndConditions');
        return view('DashboardPages.ui-elements.terms-and-condition', compact('title'));
    }

    // UI Elements

    public function alert(Request $request)
    {
        $title = __('sidebar.ui-alert');
        return view('DashboardPages.ui-elements.AlertsView', compact('title'));
    }

    public function avatar(Request $request)
    {
        $title = __('sidebar.ui-avatars');
        return view('DashboardPages.ui-elements.AvatarsView', compact('title'));
    }

    public function badge(Request $request)
    {
        $title = __('sidebar.ui-badge');
        return view('DashboardPages.ui-elements.BadgeView', compact('title'));
    }

    public function breadcrumb(Request $request)
    {
        $title = __('sidebar.ui-breadcrumb');
        return view('DashboardPages.ui-elements.BreadCrumb', compact('title'));
    }

    public function buttonsGroup(Request $request)
    {
        $title = __('sidebar.ui-button-group');
        return view('DashboardPages.ui-elements.ButtonsGroup', compact('title'));
    }

    public function buttons(Request $request)
    {
        $title = __('sidebar.ui-button');
        return view('DashboardPages.ui-elements.ButtonsView', compact('title'));
    }

    public function cards(Request $request)
    {
        $title = __('sidebar.ui-card');
        return view('DashboardPages.ui-elements.CardsView', compact('title'));
    }

    public function carousel(Request $request)
    {
        $title = __('sidebar.ui-carousel');
        return view('DashboardPages.ui-elements.CarouselView', compact('title'));
    }

    public function colors(Request $request)
    {
        $title = __('sidebar.ui-color');
        return view('DashboardPages.ui-elements.ColorsView', compact('title'));
    }

    public function grid(Request $request)
    {
        $title = __('sidebar.ui-grid');
        return view('DashboardPages.ui-elements.GridView', compact('title'));
    }

    public function images(Request $request)
    {
        $title = __('sidebar.ui-images');
        return view('DashboardPages.ui-elements.ImagesView', compact('title'));
    }

    public function listgroup(Request $request)
    {
        $title = __('sidebar.ui-listgroup');
        return view('DashboardPages.ui-elements.ListGroup', compact('title'));
    }

    public function modal(Request $request)
    {
        $title = __('sidebar.ui-modal');
        return view('DashboardPages.ui-elements.ModalView', compact('title'));
    }

    public function notifications(Request $request)
    {
        $title = __('sidebar.ui-notifications');
        return view('DashboardPages.ui-elements.NotificationsView', compact('title'));
    }

    public function offcanvas(Request $request)
    {
        $title = __('sidebar.ui-offcanvas');
        return view('DashboardPages.ui-elements.OffCanvas', compact('title'));
    }

    public function pagination(Request $request)
    {
        $title = __('sidebar.ui-pagination');
        return view('DashboardPages.ui-elements.PaginationView', compact('title'));
    }

    public function popovers(Request $request)
    {
        $title = __('sidebar.ui-popovers');
        return view('DashboardPages.ui-elements.PopoversView', compact('title'));
    }

    public function tabs(Request $request)
    {
        $title = __('sidebar.ui-tab');
        return view('DashboardPages.ui-elements.TabsView', compact('title'));
    }

    public function tooltips(Request $request)
    {
        $title = __('sidebar.ui-tooltip');
        return view('DashboardPages.ui-elements.TooltipsView', compact('title'));
    }

    public function typography(Request $request)
    {
        $title = __('sidebar.ui-typography');
        return view('DashboardPages.ui-elements.TypographyView', compact('title'));
    }

    public function VideoView(Request $request)
    {
        $title = __('sidebar.ui-Embedvideo');
        return view('DashboardPages.ui-elements.VideoView', compact('title'));
    }

    // Widgets

    public function widgetBasic(Request $request)
    {
        $title = __('sidebar.widgets_basic');
        return view('DashboardPages.widgets.WidgetBasic', compact('title'));
    }

    public function widgetChart(Request $request)
    {
        $title = __('sidebar.widgets_chart');
        return view('DashboardPages.widgets.WidgetChart', compact('title'));
    }

    public function widgetCard(Request $request)
    {
        $title = __('sidebar.widgets_card');
        return view('DashboardPages.widgets.WidgetCard', compact('title'));
    }

    // Forms

    public function elements(Request $request)
    {
        $title = __('sidebar.form-elements');
        return view('DashboardPages.forms.ElementsPage', compact('title'));
    }

    public function wizard(Request $request)
    {
        $title = __('sidebar.form-wizard');
        return view('DashboardPages.forms.WizardPage', compact('title'));
    }

    public function validation(Request $request)
    {
        $title = __('sidebar.form-validation');
        return view('DashboardPages.forms.ValidationPage', compact('title'));
    }

    // Table

    public function bootstrap(Request $request)
    {
        $title = __('sidebar.bootstrap_table');
        return view('DashboardPages.tables.BootstrapTable', compact('title'));
    }

    public function border(Request $request)
    {
        $title = __('sidebar.bordered_table');
        return view('DashboardPages.tables.BorderTable', compact('title'));
    }

    public function fancy(Request $request)
    {
        $title = __('sidebar.fixed_table');
        return view('DashboardPages.tables.fixedTable', compact('title'));
    }

    public function fixed(Request $request)
    {
        $title = __('sidebar.table-data');
        return view('DashboardPages.tables.tableData', compact('title'));
    }

    // icons
    public function fontawesome(Request $request)
    {
        $title = 'font awesome';
        return view('DashboardPages.icons.FontAwesome', compact('title'));
    }

    public function phregular(Request $request)
    {
        $title = __('sidebar.ph-regular');
        return view('DashboardPages.icons.PhRegular', compact('title'));
    }

    public function phbold(Request $request)
    {
        $title = __('sidebar.ph-bold');
        return view('DashboardPages.icons.PhBold', compact('title'));
    }

    public function phfill(Request $request)
    {
        $title = __('sidebar.ph-fill');
        return view('DashboardPages.icons.PhFill', compact('title'));
    }

    public function premission(Request $request)
    {
        $title = __('sidebar.premission');
        return view('DashboardPages.premission.Access-Control', compact('title'));
    }

    public function manager(Request $request)
    {
        $title = __('sidebar.managers-list');
        return view('DashboardPages.manager.ListPage', compact('title'));
    }
}
