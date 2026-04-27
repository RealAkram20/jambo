<!-- Sidebar Menu Start -->
<ul class="navbar-nav iq-main-menu" id="sidebar-menu">
    <li class="nav-item">
        <a class="nav-link {{ activeRoute(route('dashboard')) }}" aria-current="page" href="{{ route('dashboard') }}">
            <i class="icon" data-bs-toggle="tooltip" data-bs-placement="right" aria-label="Dashboard"
                data-bs-original-title="Dashboard">
                <i class="ph ph-squares-four fs-4"></i>
            </i>
            <span class="item-name">{{ __('sidebar.dashboard') }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ activeRoute(route('dashboard.rating')) }}" aria-current="page"
            href="{{ route('dashboard.rating') }}">
            <i class="icon" data-bs-toggle="tooltip" data-bs-placement="right" aria-label="Rating"
                data-bs-original-title="Rating">
                <i class="ph ph-star-half fs-4"></i>
            </i>
            <span class="item-name">{{ __('sidebar.rating') }}</span>
        </a>
    </li>
    @can('view_users')
        <li class="nav-item">
            <a class="nav-link {{ activeRoute(route('dashboard.user-list')) }}" href="{{ route('dashboard.user-list') }}">
                <i class="icon" data-bs-toggle="tooltip" title="User" data-bs-placement="right" aria-label="User"
                    data-bs-original-title="User">
                    <i class="ph ph-user fs-5"></i>
                </i>
                <span class="item-name">{{ __('sidebar.users') }}</span>
            </a>
        </li>
    @endcan

    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="collapse" href="#sidebar-movies" role="button" aria-expanded="false"
            aria-controls="sidebar-movies">
            <i class="icon" data-bs-toggle="tooltip" title="Table" data-bs-placement="right" aria-label="Table"
                data-bs-original-title="Table">
                <i class="ph ph-film-strip fs-4"></i>
            </i>
            <span class="item-name">{{ __('sidebar.movie') }}</span>
            <i class="right-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" class="icon-18" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </i>
        </a>
        <ul class="sub-nav collapse" id="sidebar-movies" data-bs-parent="#sidebar-menu">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.movies.*') ? 'active' : '' }}"
                        href="{{ route('admin.movies.index') }}">
                        <i class="icon" data-bs-toggle="tooltip" title="Movie List" data-bs-placement="right"
                            aria-label="Movie List" data-bs-original-title="Movie List">
                            <i class="ph ph-film-strip fs-5"></i>
                        </i>
                        <span class="item-name">{{ __('sidebar.movie_list') }}</span>
                    </a>
                </li>
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.movie-genres')) }}"
                    href="{{ route('dashboard.movie-genres') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Genres" data-bs-placement="right"
                        aria-label="Genres" data-bs-original-title="Genres">
                        <i class="ph ph-faders-horizontal fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('streamTag.genre') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.movie-tags')) }}"
                    href="{{ route('dashboard.movie-tags') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Tags" data-bs-placement="right"
                        aria-label="Tags" data-bs-original-title="Tags">
                        <i class="ph ph-tag fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('streamTag.tags') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.movie-playlist')) }}"
                    href="{{ route('dashboard.movie-playlist') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Movie Playlists" data-bs-placement="right"
                        aria-label="Movie Playlists" data-bs-original-title="Movie Playlists">
                        <i class="ph ph-playlist fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.movie-playlists') }}</span>
                </a>
            </li>
        </ul>
    </li>

    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="collapse" href="#sidebar-Shows" role="button" aria-expanded="false">
            <i class="icon" data-bs-toggle="tooltip" title="Series" data-bs-placement="right"
                aria-label="Series" data-bs-original-title="Series">
                <i class="ph ph-television-simple fs-4"></i>
            </i>
            <span class="item-name">{{ __('sidebar.tv_shows') }}</span>
            <i class="right-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" class="icon-18" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </i>
        </a>
        <ul class="sub-nav collapse" id="sidebar-Shows" data-bs-parent="#sidebar-menu">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.series.*') ? 'active' : '' }}"
                    href="{{ route('admin.series.index') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Series Lists" data-bs-placement="right"
                        aria-label="Series Lists" data-bs-original-title="Series Lists">
                        <i class="ph ph-monitor-play fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.show_list') }}</span>
                </a>
            </li>
            <!-- @can('view_seasons')
                <li class="nav-item">
                    <a class="nav-link {{ activeRoute(route('dashboard.seasons')) }}"
                        href="{{ route('dashboard.seasons') }}">
                        <i class="icon" data-bs-toggle="tooltip" title="Seasons" data-bs-placement="right"
                            aria-label="Seasons" data-bs-original-title="Seasons">
                            <i class="ph ph-film-strip fs-5"></i>
                        </i>
                        <span class="item-name">{{ __('sidebar.seasons') }}</span>
                    </a>
                </li>
            @endcan -->
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.tvshow-genres')) }}"
                    href="{{ route('dashboard.tvshow-genres') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Genres" data-bs-placement="right"
                        aria-label="Genres" data-bs-original-title="Genres">
                        <i class="ph ph-faders-horizontal fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('streamTag.genre') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.tvshow-tags')) }}"
                    href="{{ route('dashboard.tvshow-tags') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Tags" data-bs-placement="right"
                        aria-label="Tags" data-bs-original-title="Tags">
                        <i class="ph ph-tag fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('streamTag.tags') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.tvshow-playlist')) }}"
                    href="{{ route('dashboard.tvshow-playlist') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Episodes Playlist" data-bs-placement="right"
                        aria-label="Episodes Playlist" data-bs-original-title="Episodes Playlist">
                        <i class="ph ph-playlist fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.episodes-playlist') }}</span>
                </a>
            </li>
        </ul>
    </li>

    <li class="nav-item">
        <a class="nav-link {{ activeRoute(route('dashboard.vjs')) }}" aria-current="page" href="{{ route('dashboard.vjs') }}">
            <i class="icon" data-bs-toggle="tooltip" title="Vjs" data-bs-placement="right" aria-label="Vjs"
                data-bs-original-title="Vjs">
                <i class="ph ph-microphone-stage fs-4"></i>
            </i>
            <span class="item-name">Vjs</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="collapse" href="#sidebar-person" role="button" aria-expanded="false"
            aria-controls="sidebar-person">
            <i class="icon" data-bs-toggle="tooltip" title="Table" data-bs-placement="right" aria-label="Table"
                data-bs-original-title="Table">
                <i class="ph ph-users-three fs-4"></i>
            </i>
            <span class="item-name">{{ __('sidebar.persons') }}</span>
            <i class="right-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" class="icon-18" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </i>
        </a>
        <ul class="sub-nav collapse" id="sidebar-person" data-bs-parent="#sidebar-menu">
                <li class="nav-item">
                    <a class="nav-link {{ activeRoute(route('dashboard.person')) }}"
                        href="{{ route('dashboard.person') }}">
                        <i class="icon" data-bs-toggle="tooltip" title="Person" data-bs-placement="right"
                            aria-label="Person" data-bs-original-title="Person">
                            <i class="ph ph-user-circle fs-5"></i>
                        </i>
                        <span class="item-name">{{ __('sidebar.person') }}</span>
                    </a>
                </li>
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.person-categories')) }}"
                    href="{{ route('dashboard.person-categories') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Categories" data-bs-placement="right"
                        aria-label="Categories" data-bs-original-title="Categories">
                        <i class="ph ph-user-circle-plus fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.categories') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.person-tags')) }}"
                    href="{{ route('dashboard.person-tags') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Tags" data-bs-placement="right"
                        aria-label="Tags" data-bs-original-title="Tags">
                        <i class="ph ph-tag fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('streamTag.tags') }}</span>
                </a>
            </li>
        </ul>
    </li>

    {{-- Payments group — Settings, Orders, and Pricing (tiers) all live
         under one parent so the top-level sidebar stays compact. Route
         guards on each child so modules that aren't loaded don't
         surface a dead link. Whole group hides if payments isn't
         configured at all. --}}
    @if (Route::has('admin.payments.index') || Route::has('admin.subscription-tiers.index'))
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.payments.*') || request()->routeIs('admin.subscription-tiers.*') ? 'active' : '' }}"
                data-bs-toggle="collapse" href="#sidebar-payments" role="button" aria-expanded="false"
                aria-controls="sidebar-payments">
                <i class="icon" data-bs-toggle="tooltip" title="Payments" data-bs-placement="right"
                    aria-label="Payments" data-bs-original-title="Payments">
                    <i class="ph ph-credit-card fs-4"></i>
                </i>
                <span class="item-name">Payments</span>
                <i class="right-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" class="icon-18" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </i>
            </a>
            <ul class="sub-nav collapse {{ request()->routeIs('admin.payments.*') || request()->routeIs('admin.subscription-tiers.*') ? 'show' : '' }}"
                id="sidebar-payments" data-bs-parent="#sidebar-menu">
                @if (Route::has('admin.payments.index'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.payments.index') || request()->routeIs('admin.payments.update') || request()->routeIs('admin.payments.register-ipn') ? 'active' : '' }}"
                            href="{{ route('admin.payments.index') }}">
                            <i class="icon" data-bs-toggle="tooltip" title="Payment settings" data-bs-placement="right"
                                aria-label="Payment settings" data-bs-original-title="Payment settings">
                                <i class="ph ph-gear fs-5"></i>
                            </i>
                            <span class="item-name">Settings</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.payments.orders') ? 'active' : '' }}"
                            href="{{ route('admin.payments.orders') }}">
                            <i class="icon" data-bs-toggle="tooltip" title="Payment orders" data-bs-placement="right"
                                aria-label="Payment orders" data-bs-original-title="Payment orders">
                                <i class="ph ph-receipt fs-5"></i>
                            </i>
                            <span class="item-name">Orders</span>
                        </a>
                    </li>
                @endif
                @if (Route::has('admin.subscription-tiers.index'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.subscription-tiers.*') ? 'active' : '' }}"
                            href="{{ route('admin.subscription-tiers.index') }}">
                            <i class="icon" data-bs-toggle="tooltip" title="Pricing" data-bs-placement="right"
                                aria-label="Pricing" data-bs-original-title="Pricing">
                                <i class="ph ph-wallet fs-5"></i>
                            </i>
                            <span class="item-name">{{ __('sidebar.pricing') }}</span>
                        </a>
                    </li>
                @endif
            </ul>
        </li>
    @endif


    {{--
        Template-demo groups removed for admin cleanup — Authentication,
        Utilities (error-404/500, maintenance, coming-soon), Blank Page,
        UI Elements, Widget, Form, Table, and Icons. These were sidebar
        showcase items that ship with the Streamit template but point
        at routes in DashboardController that render static UI demos
        with dummy data — not real admin features. Real admin flows
        (auth, account management, 2FA) live on the user-side profile
        hub; the error / maintenance pages are served by the framework
        when the conditions that trigger them hit.
    --}}

    <li class="nav-item">
        <a class="nav-link {{ activeRoute(route('backend.permission-role')) }}"
            href="{{ route('backend.permission-role') }}">
            <i class="icon" title="Access Control" data-bs-toggle="tooltip" data-bs-placement="right"
                aria-label="Access Control" data-bs-original-title="Access Control">
                <i class="ph ph-user-gear fs-4"></i>
            </i>
            <span class="item-name">{{ __('sidebar.access_control') }}</span>
        </a>
    </li>
    @if (Route::has('admin.updates.index'))
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.updates.*') ? 'active' : '' }}"
                href="{{ route('admin.updates.index') }}">
                <i class="icon" title="System Updates" data-bs-toggle="tooltip" data-bs-placement="right"
                    aria-label="System Updates" data-bs-original-title="System Updates">
                    <i class="ph ph-download-simple fs-4"></i>
                </i>
                <span class="item-name">System Updates</span>
            </a>
        </li>
    @endif
    @if (Route::has('notifications.index'))
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}"
                href="{{ route('notifications.index') }}">
                <i class="icon" title="Notifications" data-bs-toggle="tooltip" data-bs-placement="right"
                    aria-label="Notifications" data-bs-original-title="Notifications">
                    <i class="ph ph-bell fs-4"></i>
                </i>
                <span class="item-name">Notifications</span>
            </a>
        </li>
    @endif
    {{-- Payments lives in its own grouped submenu above Review now. --}}
    @if (Route::has('admin.file-manager.index'))
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.file-manager.*') ? 'active' : '' }}"
                href="{{ route('admin.file-manager.index') }}">
                <i class="icon" title="File Manager" data-bs-toggle="tooltip" data-bs-placement="right"
                    aria-label="File Manager" data-bs-original-title="File Manager">
                    <i class="ph ph-folder-open fs-4"></i>
                </i>
                <span class="item-name">File Manager</span>
            </a>
        </li>
    @endif
    @role('admin')
        @if (Route::has('admin.pages.index'))
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.pages.*') ? 'active' : '' }}"
                    href="{{ route('admin.pages.index') }}">
                    <i class="icon" title="Pages" data-bs-toggle="tooltip" data-bs-placement="right"
                        aria-label="Pages" data-bs-original-title="Pages">
                        <i class="ph ph-file-text fs-4"></i>
                    </i>
                    <span class="item-name">Pages</span>
                </a>
            </li>
        @endif
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}"
                href="{{ route('admin.settings.index') }}">
                <i class="icon" title="Settings" data-bs-toggle="tooltip" data-bs-placement="right"
                    aria-label="Settings" data-bs-original-title="Settings">
                    <i class="ph ph-gear fs-4"></i>
                </i>
                <span class="item-name">Settings</span>
            </a>
        </li>
    @endrole
</ul>

<!-- Sidebar Menu End -->
