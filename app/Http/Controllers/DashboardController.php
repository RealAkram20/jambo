<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Show;
use Modules\Content\app\Models\Person;
use Modules\Content\app\Models\Genre;
use Modules\Content\app\Models\Category;
use Modules\Content\app\Models\Tag;
use Modules\Content\app\Models\Rating;
use Modules\Content\app\Models\Review;
use Modules\Content\app\Models\Comment;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Vj;
use Modules\Payments\app\Models\PaymentOrder;
use Modules\Streaming\app\Models\WatchHistoryItem;
use Modules\Subscriptions\app\Models\SubscriptionTier;
use Modules\Subscriptions\app\Models\UserSubscription;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $title = __('header.home');

        $stats = [
            'total_users'       => User::count(),
            'active_users'      => User::where('updated_at', '>=', now()->subDays(30))->count(),
            'total_subscribers' => UserSubscription::where('status', 'active')->count(),
            'total_movies'      => Movie::count(),
            'total_shows'       => Show::count(),
            'total_episodes'    => Episode::count(),
        ];

        $recentReviews = Review::with(['user', 'reviewable'])
            ->latest()
            ->take(6)
            ->get();

        $recentPayments = PaymentOrder::with('user')
            ->where('status', PaymentOrder::STATUS_COMPLETED)
            ->latest()
            ->take(6)
            ->get();

        // Live chart data. Everything below comes from the DB, not
        // chart-custom.js's hardcoded demo arrays.
        $chartData = [
            'genres'      => $this->buildTopGenresChart(),
            'revenue'     => $this->buildMonthlyRevenueChart(12),
            'newSubs'     => $this->buildNewSubscribersChart(12),
            'mostWatched' => $this->buildMostWatchedChart(7),
            'topRated'    => $this->buildTopRatedChart(),
        ];

        return view('DashboardPages.IndexPage1', compact(
            'title',
            'stats',
            'recentReviews',
            'recentPayments',
            'chartData',
        ));
    }

    /**
     * Top 5 genres by movie count for the donut chart. Returns
     * { labels: [...], series: [...] }. Genres with zero movies
     * are excluded so the chart never shows empty slices.
     */
    private function buildTopGenresChart(): array
    {
        $rows = Genre::withCount('movies')
            ->having('movies_count', '>', 0)
            ->orderByDesc('movies_count')
            ->take(5)
            ->get(['name']);

        return [
            'labels' => $rows->pluck('name')->all(),
            'series' => $rows->pluck('movies_count')->map(fn ($n) => (int) $n)->all(),
        ];
    }

    /**
     * Monthly revenue for the last N months. Sums completed
     * payment_orders grouped by year-month, then back-fills months
     * with zero payments so the X axis is continuous.
     */
    private function buildMonthlyRevenueChart(int $months = 12): array
    {
        $since = Carbon::now()->subMonths($months - 1)->startOfMonth();

        $raw = PaymentOrder::where('status', PaymentOrder::STATUS_COMPLETED)
            ->where('created_at', '>=', $since)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as bucket, SUM(amount) as total")
            ->groupBy('bucket')
            ->pluck('total', 'bucket');

        $labels = [];
        $series = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $d = Carbon::now()->subMonths($i)->startOfMonth();
            $key = $d->format('Y-m');
            $labels[] = $d->format('M Y');
            $series[] = (float) ($raw[$key] ?? 0);
        }

        return [
            'labels' => $labels,
            'series' => $series,
            'currency' => config('payments.currency', 'UGX'),
        ];
    }

    /**
     * New subscribers per tier per month for the last N months.
     * One series per subscription tier, back-filled with zeros so
     * every tier shows the same X-axis length.
     */
    private function buildNewSubscribersChart(int $months = 12): array
    {
        $since = Carbon::now()->subMonths($months - 1)->startOfMonth();

        $tiers = SubscriptionTier::orderBy('sort_order')->orderBy('name')->get(['id', 'name']);

        $rows = UserSubscription::where('user_subscriptions.created_at', '>=', $since)
            ->join('subscription_tiers', 'subscription_tiers.id', '=', 'user_subscriptions.subscription_tier_id')
            ->selectRaw("DATE_FORMAT(user_subscriptions.created_at, '%Y-%m') as bucket, subscription_tiers.id as tier_id, COUNT(*) as total")
            ->groupBy('bucket', 'subscription_tiers.id')
            ->get();

        $byTier = [];
        foreach ($rows as $r) {
            $byTier[$r->tier_id][$r->bucket] = (int) $r->total;
        }

        $labels = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $labels[] = Carbon::now()->subMonths($i)->format('M Y');
        }

        $series = $tiers->map(function ($tier) use ($byTier, $months) {
            $data = [];
            for ($i = $months - 1; $i >= 0; $i--) {
                $key = Carbon::now()->subMonths($i)->format('Y-m');
                $data[] = (int) ($byTier[$tier->id][$key] ?? 0);
            }
            return ['name' => $tier->name, 'data' => $data];
        })->values()->all();

        return [
            'labels' => $labels,
            'series' => $series,
        ];
    }

    /**
     * Stacked bar — distinct-user watch events per day over the
     * last N days, split into Movies vs Series.
     */
    private function buildMostWatchedChart(int $days = 7): array
    {
        $since = Carbon::now()->subDays($days - 1)->startOfDay();
        $movieType = (new Movie)->getMorphClass();
        $episodeType = (new Episode)->getMorphClass();

        $rows = WatchHistoryItem::where('watched_at', '>=', $since)
            ->selectRaw("DATE(watched_at) as bucket, watchable_type, COUNT(DISTINCT user_id) as total")
            ->groupBy('bucket', 'watchable_type')
            ->get();

        $movieData = $showData = [];
        $labels = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = Carbon::now()->subDays($i)->format('Y-m-d');
            $labels[] = Carbon::parse($d)->format('D');
            $movieData[] = (int) ($rows->firstWhere(fn ($r) => $r->bucket === $d && $r->watchable_type === $movieType)?->total ?? 0);
            $showData[]  = (int) ($rows->firstWhere(fn ($r) => $r->bucket === $d && $r->watchable_type === $episodeType)?->total ?? 0);
        }

        return [
            'labels' => $labels,
            'series' => [
                ['name' => 'Movies',  'data' => $movieData],
                ['name' => 'Series', 'data' => $showData],
            ],
        ];
    }

    /**
     * Top 5 titles across Movies + Shows by average star rating.
     * Requires at least 3 ratings to qualify — stops a single
     * 5-star from leaving a random unheard-of title on the podium.
     */
    private function buildTopRatedChart(): array
    {
        $combined = collect();

        $movieType = (new Movie)->getMorphClass();
        $showType = (new Show)->getMorphClass();

        foreach ([$movieType => Movie::class, $showType => Show::class] as $morph => $class) {
            $rows = DB::table('ratings')
                ->where('ratable_type', $morph)
                ->selectRaw('ratable_id, AVG(stars) as avg_stars, COUNT(*) as n')
                ->havingRaw('n >= 3')
                ->groupBy('ratable_id')
                ->get();

            foreach ($rows as $r) {
                $model = $class::find($r->ratable_id, ['id', 'title']);
                if ($model) {
                    $combined->push([
                        'title' => $model->title,
                        'avg' => round((float) $r->avg_stars, 2),
                    ]);
                }
            }
        }

        $top = $combined->sortByDesc('avg')->take(5)->values();

        return [
            'labels' => $top->pluck('title')->all(),
            'series' => $top->pluck('avg')->map(fn ($n) => (float) $n)->all(),
        ];
    }

    public function rating(Request $request)
    {
        $title = __('sidebar.rating');

        $query = Rating::with(['user', 'ratable'])->latest();

        if ($type = $request->query('type')) {
            $map = [
                'movie' => \Modules\Content\app\Models\Movie::class,
                'show'  => \Modules\Content\app\Models\Show::class,
                'episode' => \Modules\Content\app\Models\Episode::class,
            ];
            if (isset($map[$type])) {
                $query->where('ratable_type', $map[$type]);
            }
        }

        $ratings = $query->paginate(20)->withQueryString();

        return view('DashboardPages.rating.RatingPage', compact('title', 'ratings'));
    }

    public function comment(Request $request)
    {
        $title = __('dashboard.Comment_List');

        $query = Comment::with(['user', 'commentable'])->latest();

        if ($request->query('status') === 'approved') {
            $query->where('is_approved', true);
        } elseif ($request->query('status') === 'unapproved') {
            $query->where('is_approved', false);
        }

        if ($type = $request->query('type')) {
            $map = [
                'movie' => \Modules\Content\app\Models\Movie::class,
                'show'  => \Modules\Content\app\Models\Show::class,
                'episode' => \Modules\Content\app\Models\Episode::class,
            ];
            if (isset($map[$type])) {
                $query->where('commentable_type', $map[$type]);
            }
        }

        $comments = $query->paginate(20)->withQueryString();
        $filter = $request->query('status', '');

        return view('DashboardPages.CommentPage', compact('title', 'comments', 'filter'));
    }

    // The /user-list index has moved to App\Http\Controllers\Admin\UserController
    // so it can serve real data + CRUD. The route name
    // `dashboard.user-list` is unchanged so the sidebar link keeps
    // working.

    public function movieList(Request $request)
    {
        $title = __('sidebar.movie_list');
        $movies = Movie::with(['genres', 'categories'])
            ->orderByDesc('updated_at')
            ->get();
        $genres = Genre::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();
        $persons = Person::orderBy('last_name')->orderBy('first_name')->get();
        return view('DashboardPages.movies.MovieListPage', compact('title', 'movies', 'genres', 'categories', 'tags', 'persons'));
    }

    public function movieGenres(Request $request)
    {
        $title = __('streamTag.genre');
        $genres = Genre::withCount('movies')->orderBy('name')->get();
        return view('DashboardPages.movies.MovieGenres', compact('title', 'genres'));
    }

    public function vjs(Request $request)
    {
        $title = 'Vjs';
        $vjs = Vj::withCount(['movies', 'shows'])->orderBy('name')->get();
        return view('DashboardPages.vjs.VjsPage', compact('title', 'vjs'));
    }

    public function movieTags(Request $request)
    {
        $title = __('streamTag.tags');
        $tags = Tag::withCount('movies')->orderBy('name')->get();
        return view('DashboardPages.movies.MovieTag', compact('title', 'tags'));
    }

    public function moviePlaylist(Request $request)
    {
        $title = __('sidebar.movie-playlists');
        return view('DashboardPages.movies.MoviePlaylist', compact('title'));
    }

    public function showList(Request $request)
    {
        $title = __('sidebar.show_list');
        $shows = Show::with(['genres', 'categories'])
            ->withCount('seasons')
            ->orderByDesc('updated_at')
            ->get();
        $genres = Genre::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();
        $persons = Person::orderBy('last_name')->orderBy('first_name')->get();
        return view('DashboardPages.tv-show.ShowListPage', compact('title', 'shows', 'genres', 'categories', 'tags', 'persons'));
    }

    public function seasons(Request $request)
    {
        $title = __('sidebar.season');
        return view('DashboardPages.tv-show.SeasonsPage', compact('title'));
    }

    public function showGenres(Request $request)
    {
        $title = __('streamTag.genre');
        $genres = Genre::withCount('shows')->orderBy('name')->get();
        return view('DashboardPages.tv-show.ShowGenres', compact('title', 'genres'));
    }

    public function showTags(Request $request)
    {
        $title = __('streamTag.tags');
        $tags = Tag::withCount('shows')->orderBy('name')->get();
        return view('DashboardPages.tv-show.ShowTag', compact('title', 'tags'));
    }

    public function showPlaylist(Request $request)
    {
        $title = __('sidebar.movie-playlists');
        return view('DashboardPages.tv-show.ShowPlaylist', compact('title'));
    }

    public function person(Request $request)
    {
        $title = __('form.persons-list');
        $persons = Person::withCount(['movies', 'shows'])
            ->orderByDesc('updated_at')
            ->get();
        return view('DashboardPages.persons.PersonPage', compact('title', 'persons'));
    }

    public function personCategories(Request $request)
    {
        $title = __('sidebar.Person-Category');
        $categories = Category::withCount(['movies', 'shows'])->orderBy('name')->get();
        return view('DashboardPages.persons.PersonCategoies', compact('title', 'categories'));
    }

    public function personTags(Request $request)
    {
        $title = __('streamTag.tags');
        $tags = Tag::withCount(['movies', 'shows'])->orderBy('name')->get();
        return view('DashboardPages.persons.PersonTag', compact('title', 'tags'));
    }

    public function review(Request $request)
    {
        $title = __('sidebar.review');

        $query = Review::with(['user', 'reviewable'])->latest();

        if ($type = $request->query('type')) {
            $map = [
                'movie' => \Modules\Content\app\Models\Movie::class,
                'show'  => \Modules\Content\app\Models\Show::class,
                'episode' => \Modules\Content\app\Models\Episode::class,
            ];
            if (isset($map[$type])) {
                $query->where('reviewable_type', $map[$type]);
            }
        }

        if ($request->query('status') === 'published') {
            $query->where('is_published', true);
        } elseif ($request->query('status') === 'unpublished') {
            $query->where('is_published', false);
        }

        $reviews = $query->paginate(20)->withQueryString();
        $filter = $request->query('status', '');

        return view('DashboardPages.review.ReviewPage', compact('title', 'reviews', 'filter'));
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
