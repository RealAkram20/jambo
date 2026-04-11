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
        <a class="nav-link {{ activeRoute(route('dashboard1')) }}" aria-current="page" href="{{ route('dashboard1') }}">
            <i class="icon" data-bs-toggle="tooltip" data-bs-placement="right" aria-label="Dashboard"
                data-bs-original-title="Dashboard">
                <i class="ph ph-squares-four fs-4"></i>
            </i>
            <span class="item-name">{{ __('sidebar.dashboard1') }}</span>
        </a>
    </li>
    @can('view_rating')
        <li class="nav-item">
            <a class="nav-link {{ activeRoute(route('dashboard.rating')) }}" aria-current="page"
                href="{{ route('dashboard.rating') }} ">
                <i class="icon" data-bs-toggle="tooltip" data-bs-placement="right" aria-label="Rating"
                    data-bs-original-title="Rating">
                    <i class="ph ph-star-half fs-4"></i>
                </i>
                <span class="item-name">{{ __('sidebar.rating') }}</span>
            </a>
        </li>
    @endcan
    @can('view_comments')
        <li class="nav-item">
            <a class="nav-link {{ activeRoute(route('dashboard.comment')) }}" aria-current="page"
                href="{{ route('dashboard.comment') }}">
                <i class="icon" data-bs-toggle="tooltip" title="Comment" data-bs-placement="right" aria-label="Comment"
                    data-bs-original-title="Comment">
                    <i class="ph ph-chat-circle-dots fs-4"></i>
                </i>
                <span class="item-name">{{ __('sidebar.comments') }}</span>
            </a>
        </li>
    @endcan
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
                    <a class="nav-link {{ activeRoute(route('dashboard.movie-list')) }}"
                        href="{{ route('dashboard.movie-list') }}">
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
            <i class="icon" data-bs-toggle="tooltip" title="TV Shows" data-bs-placement="right"
                aria-label="TV Shows" data-bs-original-title="TV Shows">
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
            @can('view_shows')
                <li class="nav-item">
                    <a class="nav-link {{ activeRoute(route('dashboard.show-list')) }}"
                        href="{{ route('dashboard.show-list') }}">
                        <i class="icon" data-bs-toggle="tooltip" title="Show Lists" data-bs-placement="right"
                            aria-label="Show Lists" data-bs-original-title="Show Lists">
                            <i class="ph ph-monitor-play fs-5"></i>
                        </i>
                        <span class="item-name">{{ __('sidebar.show_list') }}</span>
                    </a>
                </li>
            @endcan
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
            @can('view_episodes')
                <li class="nav-item">
                    <a class="nav-link {{ activeRoute(route('dashboard.episodes')) }}"
                        href="{{ route('dashboard.episodes') }}">
                        <i class="icon" data-bs-toggle="tooltip" title="Episodes" data-bs-placement="right"
                            aria-label="Episodes" data-bs-original-title="Episodes">
                            <i class="ph ph-monitor-play fs-5"></i>
                        </i>
                        <span class="item-name">{{ __('sidebar.episodes') }}</span>
                    </a>
                </li>
            @endcan
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
        <a class="nav-link" data-bs-toggle="collapse" href="#sidebar-video" role="button" aria-expanded="false"
            aria-controls="sidebar-video">
            <i class="icon" data-bs-toggle="tooltip" title="Table" data-bs-placement="right" aria-label="Table"
                data-bs-original-title="Table">
                <i class="ph ph-video-camera fs-4"></i>
            </i>
            <span class="item-name">{{ __('sidebar.videos') }}</span>
            <i class="right-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" class="icon-18" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </i>
        </a>
        <ul class="sub-nav collapse" id="sidebar-video" data-bs-parent="#sidebar-menu">
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.videopage')) }}"
                    href="{{ route('dashboard.videopage') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Videos" data-bs-placement="right"
                        aria-label="Videos" data-bs-original-title="Videos">
                        <i class="ph ph-video fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.video') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.video-category')) }}"
                    href="{{ route('dashboard.video-category') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Video Category" data-bs-placement="right"
                        aria-label="Video Category" data-bs-original-title="Video Category">
                        <i class="ph ph-queue fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.video-category') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.video-tags')) }}"
                    href="{{ route('dashboard.video-tags') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Video Tags" data-bs-placement="right"
                        aria-label="Video Tags" data-bs-original-title="Video Tags">
                        <i class="ph ph-video-camera fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.video-tags') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.video-playlist')) }}"
                    href="{{ route('dashboard.video-playlist') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Video Playlist" data-bs-placement="right"
                        aria-label="Video Playlist" data-bs-original-title="Video Playlist">
                        <i class="ph ph-playlist fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.video-playlist') }}</span>
                </a>
            </li>
        </ul>
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

    <li class="nav-item">
        <a class="nav-link {{ activeRoute(route('dashboard.review')) }}" href="{{ route('dashboard.review') }}">
            <i class="icon" data-bs-toggle="tooltip" title="Review" data-bs-placement="right" aria-label="Review"
                data-bs-original-title="Review">
                <i class="ph ph-chat-centered fs-5"></i>
            </i>
            <span class="item-name">{{ __('sidebar.review') }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ activeRoute(route('dashboard.pricing')) }}" href="{{ route('dashboard.pricing') }}">
            <i class="icon" data-bs-toggle="tooltip" title="Pricing" data-bs-placement="right"
                aria-label="Pricing" data-bs-original-title="Pricing">
                <i class="ph ph-wallet fs-5"></i>
            </i>
            <span class="item-name">{{ __('sidebar.pricing') }}</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="collapse" href="#sidebar-auth" role="button" aria-expanded="false"
            aria-controls="sidebar-auth">
            <i class="icon" data-bs-toggle="tooltip" title="Authentication" data-bs-placement="right"
                aria-label="Authentication" data-bs-original-title="Authentication">
                <i class="ph ph-shield-check fs-5"></i>
            </i>
            <span class="item-name">{{ __('sidebar.authentication') }}</span>
            <i class="right-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" class="icon-18" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </i>
        </a>
        <ul class="sub-nav collapse" id="sidebar-auth" data-bs-parent="#sidebar-menu">
            <li class="nav-item">
                <a class="nav-link" href="{{ route('dashboard.login') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Login" data-bs-placement="right"
                        aria-label="Login" data-bs-original-title="Login">
                        <i class="ph ph-sign-in fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.login') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('dashboard.register') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Register" data-bs-placement="right"
                        aria-label="Register" data-bs-original-title="Register">
                        <i class="ph ph-trademark-registered fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.register') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('dashboard.reset-password') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Recover Password" data-bs-placement="right"
                        aria-label="Recover Password" data-bs-original-title="Recover Password">
                        <i class="ph ph-voicemail fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.recover_password') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('dashboard.verify-email') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Confirm Mail" data-bs-placement="right"
                        aria-label="Confirm Mail" data-bs-original-title="Confirm Mail">
                        <i class="ph ph-file fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.confirm_mail') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ route('dashboard.TwoFactor') }}" href="{{ route('dashboard.TwoFactor') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Two Factor Authentication"
                        data-bs-placement="right" aria-label="Two Factor Authentication"
                        data-bs-original-title="Two Factor Authentication">
                        <i class="ph ph-fingerprint fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.two_factor_authentication') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ route('dashboard.AccountDeactivated') }}"
                    href="{{ route('dashboard.AccountDeactivated') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Account Deactivate" data-bs-placement="right"
                        aria-label="Account Deactivate" data-bs-original-title="Account Deactivate">
                        <i class="ph ph-user-circle-minus fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.account_deactivated') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('dashboard.lock-screen') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Lock Screen" data-bs-placement="right"
                        aria-label="Lock Screen" data-bs-original-title="Lock Screen">
                        <i class="ph ph-lock fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.lock_screen') }}</span>
                </a>
            </li>
        </ul>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="collapse" href="#utilities-error" role="button" aria-expanded="false"
            aria-controls="utilities-error">
            <i class="icon" data-bs-toggle="tooltip" title="Utilities" data-bs-placement="right"
                aria-label="Utilities" data-bs-original-title="Utilities">
                <i class="ph-fill ph-note fs-4"></i>
            </i>
            <span class="item-name">{{ __('sidebar.utilities') }}</span>
            <i class="right-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" class="icon-18" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </i>
        </a>
        <ul class="sub-nav collapse" id="utilities-error" data-bs-parent="#sidebar-menu">
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.error-404')) }}"
                    href="{{ route('dashboard.error-404') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Error 404" data-bs-placement="right"
                        aria-label="Error 404" data-bs-original-title="Error 404">
                        <i class="ph ph-warning-circle fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.error_404') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.error-500')) }}"
                    href="{{ route('dashboard.error-500') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Error 500" data-bs-placement="right"
                        aria-label="Error 500" data-bs-original-title="Error 500">
                        <i class="ph ph-warning-circle fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.error_500') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.maintenance')) }}"
                    href="{{ route('dashboard.maintenance') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Maintenance" data-bs-placement="right"
                        aria-label="Maintenance" data-bs-original-title="Maintenance">
                        <i class="ph ph-archive fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.maintenance') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.coming-soon')) }}"
                    href="{{ route('dashboard.coming-soon') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Coming Soon" data-bs-placement="right"
                        aria-label="Coming Soon" data-bs-original-title="Coming Soon">
                        <i class="ph ph-clock fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.coming_soon') }}</span>
                </a>
            </li>
        </ul>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ activeRoute(route('dashboard.blank-page')) }} "
            href="{{ route('dashboard.blank-page') }}">
            <i class="icon" data-bs-toggle="tooltip" title="Blank Page" data-bs-placement="right"
                aria-label="Blank Page" data-bs-original-title="Blank Page">
                <i class="ph ph-note fs-5"></i>
            </i>
            <span class="item-name">{{ __('sidebar.blank_page') }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="collapse" href="#ui" role="button" aria-expanded="false"
            aria-controls="utilities-error">
            <i class="icon" data-bs-toggle="tooltip" title="UI Elements" data-bs-placement="right"
                aria-label="UI Elements" data-bs-original-title="UI Elements">
                <i class="ph ph-briefcase fs-4"></i>
            </i>
            <span class="item-name">{{ __('sidebar.ui_elements') }}</span>
            <i class="right-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </i>
        </a>
        <ul class="sub-nav collapse" id="ui" data-parent="#sidebar-menu">
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.avatars')) }}"
                    href="{{ route('dashboard.avatars') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Avatars" data-bs-placement="right"
                        aria-label="Avatars" data-bs-original-title="Avatars">
                        <i class="ph ph-user fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.avatars') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.alerts')) }}"
                    href="{{ route('dashboard.alerts') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Alerts" data-bs-placement="right"
                        aria-label="Alerts" data-bs-original-title="Alerts">
                        <i class="ph ph-bell fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.alerts') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.badge')) }}"
                    href="{{ route('dashboard.badge') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Badges" data-bs-placement="right"
                        aria-label="Badges" data-bs-original-title="Badges">
                        <i class="ph-duotone ph-shield-check fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.badge') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.breadcrumb')) }}"
                    href="{{ route('dashboard.breadcrumb') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Breadcrumb" data-bs-placement="right"
                        aria-label="Breadcrumb" data-bs-original-title="Breadcrumb">
                        <i class="ph ph-list fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.bread_crumb') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.buttons')) }}"
                    href="{{ route('dashboard.buttons') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Buttons" data-bs-placement="right"
                        aria-label="Buttons" data-bs-original-title="Buttons">
                        <i class="ph-duotone ph-device-tablet-camera fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.buttons') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.buttonsgroup')) }}"
                    href="{{ route('dashboard.buttonsgroup') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Button Group" data-bs-placement="right"
                        aria-label="Button Group" data-bs-original-title="Button Group">
                        <i class="ph ph-rows fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.buttons_group') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.offcanvas')) }}"
                    href="{{ route('dashboard.offcanvas') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Offcanvas" data-bs-placement="right"
                        aria-label="Offcanvas" data-bs-original-title="Offcanvas">
                        <i class="ph ph-corners-out fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.offcanvas') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.colors')) }}"
                    href="{{ route('dashboard.colors') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Colors" data-bs-placement="right"
                        aria-label="Colors" data-bs-original-title="Colors">
                        <i class="ph ph-palette fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.colors') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.cards')) }}"
                    href="{{ route('dashboard.cards') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Cards" data-bs-placement="right"
                        aria-label="Cards" data-bs-original-title="Cards">
                        <i class="ph ph-identification-card fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.cards') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.carousel')) }}"
                    href="{{ route('dashboard.carousel') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Carousel" data-bs-placement="right"
                        aria-label="Carousel" data-bs-original-title="Carousel">
                        <i class="ph-duotone ph-film-strip fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.carousel') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.grid')) }}"
                    href="{{ route('dashboard.grid') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Grid" data-bs-placement="right"
                        aria-label="Grid" data-bs-original-title="Grid">
                        <i class="ph ph-grid-four fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.grid') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.images')) }}"
                    href="{{ route('dashboard.images') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Images" data-bs-placement="right"
                        aria-label="Images" data-bs-original-title="Images">
                        <i class="ph ph-image fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.images') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.listgroup')) }}"
                    href="{{ route('dashboard.listgroup') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="List Group" data-bs-placement="right"
                        aria-label="List Group" data-bs-original-title="List Group">
                        <i class="ph ph-list-checks fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.list_group') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.modal')) }}"
                    href="{{ route('dashboard.modal') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Modal" data-bs-placement="right"
                        aria-label="Modal" data-bs-original-title="Modal">
                        <i class="ph ph-square-split-horizontal fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.modal') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.notificationss')) }}"
                    href="{{ route('dashboard.notificationss') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Notifications" data-bs-placement="right"
                        aria-label="Notifications" data-bs-original-title="Notifications">
                        <i class="ph ph-bell fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.notifications') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.pagination')) }}"
                    href="{{ route('dashboard.pagination') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Pagination" data-bs-placement="right"
                        aria-label="Pagination" data-bs-original-title="Pagination">
                        <i class="ph ph-dots-three-outline fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.pagination') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.popovers')) }}"
                    href="{{ route('dashboard.popovers') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Popovers" data-bs-placement="right"
                        aria-label="Popovers" data-bs-original-title="Popovers">
                        <i class="ph ph-eraser fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.popovers') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.typography')) }}"
                    href="{{ route('dashboard.typography') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Typography" data-bs-placement="right"
                        aria-label="Typography" data-bs-original-title="Typography">
                        <i class="ph ph-keyboard fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.typography') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.tabs')) }}"
                    href="{{ route('dashboard.tabs') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Tabs" data-bs-placement="right"
                        aria-label="Tabs" data-bs-original-title="Tabs">
                        <i class="ph ph-stack fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.tabs') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.tooltips')) }}"
                    href="{{ route('dashboard.tooltips') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Tooltips" data-bs-placement="right"
                        aria-label="Tooltips" data-bs-original-title="Tooltips">
                        <i class="ph ph-magnet fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.tooltips') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.VideoView')) }}"
                    href="{{ route('dashboard.VideoView') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Video" data-bs-placement="right"
                        aria-label="Video" data-bs-original-title="Video">
                        <i class="ph ph-video-camera fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.video') }}</span>
                </a>
            </li>
        </ul>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="collapse" href="#sidebar-widget" role="button" aria-expanded="false"
            aria-controls="sidebar-widget">
            <i class="icon" data-bs-toggle="tooltip" title="Widgets" data-bs-placement="right"
                aria-label="Widgets" data-bs-original-title="Widgets">
                <i class="ph ph-browser fs-4"></i>
            </i>
            <span class="item-name">{{ __('sidebar.widgets') }}</span>
            <i class="right-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" class="icon-18" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </i>
        </a>
        <ul class="sub-nav collapse" id="sidebar-widget" data-bs-parent="#sidebar-menu">
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.widget-basic')) }}"
                    href="{{ route('dashboard.widget-basic') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Widget Basic" data-bs-placement="right"
                        aria-label="Widget Basic" data-bs-original-title="Widget Basic">
                        <i class="ph ph-chart-bar fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.widgets_basic') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.widget-chart')) }}"
                    href="{{ route('dashboard.widget-chart') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Widget Chart" data-bs-placement="right"
                        aria-label="Widget Chart" data-bs-original-title="Widget Chart">
                        <i class="ph ph-chart-line fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.widgets_chart') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.widget-card')) }}"
                    href="{{ route('dashboard.widget-card') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Widget Card" data-bs-placement="right"
                        aria-label="Widget Card" data-bs-original-title="Widget Card">
                        <i class="ph ph-browsers fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.widgets_card') }}</span>
                </a>
            </li>
        </ul>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="collapse" href="#sidebar-form" role="button" aria-expanded="false"
            aria-controls="sidebar-form">
            <i class="icon" data-bs-toggle="tooltip" title="Form" data-bs-placement="right" aria-label="Form"
                data-bs-original-title="Form">
                <i class="ph ph-note fs-4"></i>
            </i>
            <span class="item-name">{{ __('sidebar.form') }}</span>
            <i class="right-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" class="icon-18" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </i>
        </a>
        <ul class="sub-nav collapse" id="sidebar-form" data-bs-parent="#sidebar-menu">
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.elements')) }}"
                    href="{{ route('dashboard.elements') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Elements" data-bs-placement="right"
                        aria-label="Elements" data-bs-original-title="Elements">
                        <i class="ph ph-book fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.elements') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.wizard')) }}"
                    href="{{ route('dashboard.wizard') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Wizard" data-bs-placement="right"
                        aria-label="Wizard" data-bs-original-title="Wizard">
                        <i class="ph ph-magic-wand fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.wizard') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.validation')) }}"
                    href="{{ route('dashboard.validation') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Validation" data-bs-placement="right"
                        aria-label="Validation" data-bs-original-title="Validation">
                        <i class="ph ph-note-pencil fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.validation') }}</span>
                </a>
            </li>
        </ul>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="collapse" href="#sidebar-table" role="button" aria-expanded="false"
            aria-controls="sidebar-table">
            <i class="icon" data-bs-toggle="tooltip" title="Table" data-bs-placement="right" aria-label="Table"
                data-bs-original-title="Table">
                <i class="ph-fill ph-table fs-4"></i>
            </i>
            <span class="item-name">{{ __('sidebar.table') }}</span>
            <i class="right-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" class="icon-18" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </i>
        </a>
        <ul class="sub-nav collapse" id="sidebar-table" data-bs-parent="#sidebar-menu">
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.bootstrap')) }}"
                    href="{{ route('dashboard.bootstrap') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Bootstrap Table" data-bs-placement="right"
                        aria-label="Bootstrap Table" data-bs-original-title="Bootstrap Table">
                        <i class="fa-brands fa-bootstrap"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.bootstrap_table') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.table-data')) }}"
                    href="{{ route('dashboard.table-data') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Datatable" data-bs-placement="right"
                        aria-label="Datatable" data-bs-original-title="Datatable">
                        <i class="ph ph-table fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.data_table') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.border')) }}"
                    href="{{ route('dashboard.border') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Bordered Table" data-bs-placement="right"
                        aria-label="Bordered Table" data-bs-original-title="Bordered Table">
                        <i class="ph ph-table fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.bordered_table') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.fixed-table')) }}"
                    href="{{ route('dashboard.fixed-table') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Fancy Table" data-bs-placement="right"
                        aria-label="Fancy Tabl" data-bs-original-title="Fancy Tabl">
                        <i class="ph ph-grid-nine fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.fixed_table') }}</span>
                </a>
            </li>
        </ul>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="collapse" href="#sidebar-icons" role="button" aria-expanded="false"
            aria-controls="sidebar-icons">
            <i class="icon" data-bs-toggle="tooltip" title="Table" data-bs-placement="right" aria-label="Table"
                data-bs-original-title="Table">
                <i class="ph-fill ph-table fs-4"></i>
            </i>
            <span class="item-name">{{ __('sidebar.icons') }}</span>
            <i class="right-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" class="icon-18" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </i>
        </a>
        <ul class="sub-nav collapse" id="sidebar-icons" data-bs-parent="#sidebar-menu">
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.ph-regular')) }}"
                    href="{{ route('dashboard.ph-regular') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Ph Regular" data-bs-placement="right"
                        aria-label="Ph Regular" data-bs-original-title="Ph Regular">
                        <i class="ph ph-currency-rub fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.ph-regular') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.ph-bold')) }}"
                    href="{{ route('dashboard.ph-bold') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Ph Bold" data-bs-placement="right"
                        aria-label="Ph Bold" data-bs-original-title="Ph Bold">
                        <i class="ph-bold ph-currency-rub fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.ph-bold') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ activeRoute(route('dashboard.ph-fill')) }}"
                    href="{{ route('dashboard.ph-fill') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Ph Fill" data-bs-placement="right"
                        aria-label="Ph Fill" data-bs-original-title="Ph Fill">
                        <i class="ph-fill ph-currency-rub fs-5"></i>
                    </i>
                    <span class="item-name">{{ __('sidebar.ph-fill') }}</span>
                </a>
            </li>
        </ul>
    </li>
    <li class="nav-item mb-4">
        <a class="nav-link {{ activeRoute(route('backend.permission-role')) }}"
            href="{{ route('backend.permission-role') }}">
            <i class="icon" title="Access Control" data-bs-toggle="tooltip" data-bs-placement="right"
                aria-label="Access Control" data-bs-original-title="Access Control">
                <i class="ph ph-user-gear fs-4"></i>
            </i>
            <span class="item-name">{{ __('sidebar.access_control') }}</span>
        </a>
    </li>
</ul>

<!-- Sidebar Menu End -->
