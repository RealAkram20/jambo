<!-- Sidebar Menu Start -->
{{--
    Menu is ordered by priority and grouped with labeled section
    headers (static-item + .sidenav-divider, styled in
    components/partials/theme-tokens.blade.php):

      (core)          Dashboard, Notifications, Performance — the
                      things an admin checks every session.
      CONTENT         The day-to-day catalogue work: Movies, Series,
                      Vjs, Persons, Rating, File Manager, Pages.
      USERS & ACCESS  Account administration: Users, Access Control.
      SYSTEM          Configuration + operational health: Settings,
                      SEO & Analytics, System info.
      SUPER ADMIN /   Money-shaping surface (Payments, Monetization);
      FINANCE         label follows the viewer's role.

    Template-demo groups (Authentication, Utilities, UI Elements,
    Widget, Form, Table, Icons, …) were removed in the admin cleanup —
    they pointed at static Streamit showcase routes, not real admin
    features.
--}}
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
    {{-- Performance — admin content-contribution tracking + earnings.
         Every admin sees the dashboard; only super-admin sees the rate
         Settings (money-shaping), matching the route middleware. --}}
    @role('admin')
    @if (Route::has('dashboard.performance'))
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('dashboard.performance') || request()->routeIs('dashboard.performance.*') ? 'active' : '' }}"
                data-bs-toggle="collapse" href="#sidebar-performance" role="button" aria-expanded="false"
                aria-controls="sidebar-performance">
                <i class="icon" data-bs-toggle="tooltip" title="Performance" data-bs-placement="right"
                    aria-label="Performance" data-bs-original-title="Performance">
                    <i class="ph ph-chart-line-up fs-4"></i>
                </i>
                <span class="item-name">Performance</span>
                <i class="right-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" class="icon-18" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </i>
            </a>
            <ul class="sub-nav collapse {{ request()->routeIs('dashboard.performance') || request()->routeIs('dashboard.performance.*') ? 'show' : '' }}"
                id="sidebar-performance" data-bs-parent="#sidebar-menu">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dashboard.performance') ? 'active' : '' }}"
                        href="{{ route('dashboard.performance') }}">
                        <i class="icon" data-bs-toggle="tooltip" title="Dashboard" data-bs-placement="right"
                            aria-label="Dashboard" data-bs-original-title="Dashboard">
                            <i class="ph ph-gauge fs-5"></i>
                        </i>
                        <span class="item-name">Dashboard</span>
                    </a>
                </li>
                @role('super-admin')
                @if (Route::has('dashboard.performance.settings'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard.performance.settings') ? 'active' : '' }}"
                            href="{{ route('dashboard.performance.settings') }}">
                            <i class="icon" data-bs-toggle="tooltip" title="Settings" data-bs-placement="right"
                                aria-label="Settings" data-bs-original-title="Settings">
                                <i class="ph ph-gear fs-5"></i>
                            </i>
                            <span class="item-name">Settings</span>
                        </a>
                    </li>
                @endif
                @endrole
            </ul>
        </li>
    @endif
    @endrole

    {{-- ============================== CONTENT ============================== --}}
    <li class="nav-item static-item">
        <hr class="sidenav-divider">
        <a class="nav-link static-item disabled" tabindex="-1">
            <span class="default-icon">Content</span>
            <span class="mini-icon">-</span>
        </a>
    </li>

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
        <a class="nav-link {{ activeRoute(route('dashboard.person-categories')) }}" aria-current="page"
            href="{{ route('dashboard.person-categories') }}">
            <i class="icon" data-bs-toggle="tooltip" title="Categories" data-bs-placement="right"
                aria-label="Categories" data-bs-original-title="Categories">
                <i class="ph ph-squares-four fs-4"></i>
            </i>
            <span class="item-name">{{ __('sidebar.categories') }}</span>
        </a>
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
        <a class="nav-link {{ activeRoute(route('dashboard.rating')) }}" aria-current="page"
            href="{{ route('dashboard.rating') }}">
            <i class="icon" data-bs-toggle="tooltip" data-bs-placement="right" aria-label="Rating"
                data-bs-original-title="Rating">
                <i class="ph ph-star-half fs-4"></i>
            </i>
            <span class="item-name">{{ __('sidebar.rating') }}</span>
        </a>
    </li>

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

    @can('pages_access')
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
    @endcan

    {{-- =========================== USERS & ACCESS =========================== --}}
    <li class="nav-item static-item">
        <hr class="sidenav-divider">
        <a class="nav-link static-item disabled" tabindex="-1">
            <span class="default-icon">Users &amp; Access</span>
            <span class="mini-icon">-</span>
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

    {{-- Access Control edits permissions → super-admin ONLY, never
         delegatable. Route group is role:super-admin to match. --}}
    @role('super-admin')
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
    @endrole

    {{-- ================================ SYSTEM ================================ --}}
    {{-- Each System page is delegatable: hidden unless the viewer holds its
         access permission (super-admins hold all via Gate::before). The
         section header shows only when at least one page is visible. --}}
    @canany(['settings_access', 'seo_access', 'system_info_access'])
        <li class="nav-item static-item">
            <hr class="sidenav-divider">
            <a class="nav-link static-item disabled" tabindex="-1">
                <span class="default-icon">System</span>
                <span class="mini-icon">-</span>
            </a>
        </li>
    @endcanany

    @can('settings_access')
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
    @endcan
    @can('seo_access')
        @if (Route::has('admin.seo.index'))
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.seo.*') ? 'active' : '' }}"
                    href="{{ route('admin.seo.index') }}">
                    <i class="icon" title="SEO &amp; Analytics" data-bs-toggle="tooltip" data-bs-placement="right"
                        aria-label="SEO &amp; Analytics" data-bs-original-title="SEO &amp; Analytics">
                        <i class="ph ph-magnifying-glass-plus fs-4"></i>
                    </i>
                    <span class="item-name">SEO &amp; Analytics</span>
                </a>
            </li>
        @endif
    @endcan
    {{-- "System info" — collapsible group bundling the four
         admin-side operational pages: System Updates, Error log,
         System status, Signup attempts. They all answer the same
         question ("how is the system doing right now"). --}}
    @can('system_info_access')
        @if (Route::has('admin.updates.index')
            || Route::has('admin.diagnostics.logs')
            || Route::has('admin.diagnostics.status')
            || Route::has('admin.diagnostics.signups'))
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.updates.*') || request()->routeIs('admin.diagnostics.*') ? 'active' : '' }}"
                    data-bs-toggle="collapse" href="#sidebar-system-info" role="button"
                    aria-expanded="{{ request()->routeIs('admin.updates.*') || request()->routeIs('admin.diagnostics.*') ? 'true' : 'false' }}"
                    aria-controls="sidebar-system-info">
                    <i class="icon" data-bs-toggle="tooltip" title="System info" data-bs-placement="right"
                        aria-label="System info" data-bs-original-title="System info">
                        <i class="ph ph-monitor fs-4"></i>
                    </i>
                    <span class="item-name">System info</span>
                    <i class="right-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" class="icon-18" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </i>
                </a>
                <ul class="sub-nav collapse {{ request()->routeIs('admin.updates.*') || request()->routeIs('admin.diagnostics.*') ? 'show' : '' }}"
                    id="sidebar-system-info" data-bs-parent="#sidebar-menu">
                    @if (Route::has('admin.updates.index'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.updates.*') ? 'active' : '' }}"
                                href="{{ route('admin.updates.index') }}">
                                <i class="icon" data-bs-toggle="tooltip" title="System Updates" data-bs-placement="right"
                                    aria-label="System Updates" data-bs-original-title="System Updates">
                                    <i class="ph ph-download-simple fs-5"></i>
                                </i>
                                <span class="item-name">System Updates</span>
                            </a>
                        </li>
                    @endif
                    @if (Route::has('admin.diagnostics.logs'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.diagnostics.logs') ? 'active' : '' }}"
                                href="{{ route('admin.diagnostics.logs') }}">
                                <i class="icon" data-bs-toggle="tooltip" title="Error log" data-bs-placement="right"
                                    aria-label="Error log" data-bs-original-title="Error log">
                                    <i class="ph ph-file-text fs-5"></i>
                                </i>
                                <span class="item-name">Error log</span>
                            </a>
                        </li>
                    @endif
                    @if (Route::has('admin.diagnostics.status'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.diagnostics.status') ? 'active' : '' }}"
                                href="{{ route('admin.diagnostics.status') }}">
                                <i class="icon" data-bs-toggle="tooltip" title="System status" data-bs-placement="right"
                                    aria-label="System status" data-bs-original-title="System status">
                                    <i class="ph ph-gauge fs-5"></i>
                                </i>
                                <span class="item-name">System status</span>
                            </a>
                        </li>
                    @endif
                    @if (Route::has('admin.diagnostics.signups'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.diagnostics.signups') ? 'active' : '' }}"
                                href="{{ route('admin.diagnostics.signups') }}">
                                <i class="icon" data-bs-toggle="tooltip" title="Signup attempts" data-bs-placement="right"
                                    aria-label="Signup attempts" data-bs-original-title="Signup attempts">
                                    <i class="ph ph-user-plus fs-5"></i>
                                </i>
                                <span class="item-name">Signup attempts</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </li>
        @endif
    @endcan

    {{-- ======================= SUPER ADMIN / FINANCE ======================= --}}
    {{-- Elevated access — the money-shaping surface, grouped under a
         labeled section at the bottom so it reads as a distinct tier
         of the menu. Only finance / super-admin ever see it; the
         header label follows the viewer's actual role. Route
         middleware enforces the same gates, so a direct URL also
         403s for anyone else. --}}
    @hasanyrole('finance|super-admin')
        @php
            $elevatedLabel = auth()->user()->hasRole('super-admin') ? 'Super Admin' : 'Finance';
            $showPayments = Route::has('admin.payments.index') || Route::has('admin.subscription-tiers.index');
            // Monetization additionally honours the
            // monetization.finance_can_view setting — when the owner
            // flips it off, finance loses even the menu (the
            // monetization.admin middleware 403s the routes to match).
            $showMonetization = Route::has('admin.monetization.partners.index')
                && (auth()->user()->hasRole('super-admin') || \Modules\Monetization\app\Services\MonetizationSettings::financeCanView());
        @endphp
        @if ($showPayments || $showMonetization)
            <li class="nav-item static-item">
                <hr class="sidenav-divider">
                <a class="nav-link static-item disabled" tabindex="-1">
                    <span class="default-icon">{{ $elevatedLabel }}</span>
                    <span class="mini-icon">-</span>
                </a>
            </li>
        @endif

        {{-- Payments group — Settings, Orders, and Pricing (tiers) all
             live under one parent so the sidebar stays compact. Route
             guards on each child so modules that aren't loaded don't
             surface a dead link. --}}
        @if ($showPayments)
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

        {{-- Monetization: partner revenue-share back office. --}}
        @if ($showMonetization)
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.monetization.*') ? 'active' : '' }}"
                    data-bs-toggle="collapse" href="#sidebar-monetization" role="button" aria-expanded="false"
                    aria-controls="sidebar-monetization">
                    <i class="icon" data-bs-toggle="tooltip" title="Monetization" data-bs-placement="right"
                        aria-label="Monetization" data-bs-original-title="Monetization">
                        <i class="ph ph-hand-coins fs-4"></i>
                    </i>
                    <span class="item-name">Monetization</span>
                    <i class="right-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" class="icon-18" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </i>
                </a>
                <ul class="sub-nav collapse {{ request()->routeIs('admin.monetization.*') ? 'show' : '' }}"
                    id="sidebar-monetization" data-bs-parent="#sidebar-menu">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.monetization.partners.*') ? 'active' : '' }}"
                            href="{{ route('admin.monetization.partners.index') }}">
                            <i class="icon"><i class="ph ph-users-three fs-5"></i></i>
                            <span class="item-name">Partners</span>
                        </a>
                    </li>
                    @role('super-admin')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.monetization.splits.*') ? 'active' : '' }}"
                            href="{{ route('admin.monetization.splits.index') }}">
                            <i class="icon"><i class="ph ph-chart-pie-slice fs-5"></i></i>
                            <span class="item-name">Title splits</span>
                        </a>
                    </li>
                    @endrole
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.monetization.statements.*') ? 'active' : '' }}"
                            href="{{ route('admin.monetization.statements.index') }}">
                            <i class="icon"><i class="ph ph-receipt fs-5"></i></i>
                            <span class="item-name">Statements</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.monetization.withdrawals.*') ? 'active' : '' }}"
                            href="{{ route('admin.monetization.withdrawals.index') }}">
                            <i class="icon"><i class="ph ph-hand-coins fs-5"></i></i>
                            <span class="item-name">Withdrawals</span>
                        </a>
                    </li>
                    @role('super-admin')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.monetization.settings') ? 'active' : '' }}"
                            href="{{ route('admin.monetization.settings') }}">
                            <i class="icon"><i class="ph ph-gear fs-5"></i></i>
                            <span class="item-name">Settings</span>
                        </a>
                    </li>
                    @endrole
                </ul>
            </li>
        @endif
    @endhasanyrole

    {{-- Referral program: ONE page with tabs (Refer & Earn for every
         panel user, Payouts for finance|super-admin, Overview for
         super-admin) so nothing needs a second sidebar trip. Only the
         money knobs keep their own super-admin item. --}}
    @if (Route::has('admin.referrals.index')
        && (auth()->user()->hasAnyRole(['finance', 'super-admin']) || \Modules\Referrals\app\Services\ReferralSettings::active()))
        <li class="nav-item static-item">
            <hr class="sidenav-divider">
            <a class="nav-link static-item disabled" tabindex="-1">
                <span class="default-icon">Growth</span>
                <span class="mini-icon">-</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.referrals.index') ? 'active' : '' }}"
                href="{{ route('admin.referrals.index') }}">
                <i class="icon" data-bs-toggle="tooltip" title="Referrals" data-bs-placement="right"
                    aria-label="Referrals" data-bs-original-title="Referrals">
                    <i class="ph ph-gift fs-4"></i>
                </i>
                <span class="item-name">Referrals</span>
            </a>
        </li>
        {{-- Staff who are ALSO enrolled Monetization partners (content
             creators before they were admins) get a shortcut into
             their Creator Studio — its own earnings, statements, and
             withdrawals live there, not in the admin panel. --}}
        @role('partner')
            <li class="nav-item">
                <a class="nav-link" href="{{ route('partner.dashboard') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Creator Studio" data-bs-placement="right"
                        aria-label="Creator Studio" data-bs-original-title="Creator Studio">
                        <i class="ph ph-video-camera fs-4"></i>
                    </i>
                    <span class="item-name">Creator Studio</span>
                </a>
            </li>
        @endrole
        {{-- The signed-in staff member's own universal wallet:
             performance + referral earnings on one balance. --}}
        @if (Route::has('admin.wallet.index'))
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.wallet.index') ? 'active' : '' }}"
                    href="{{ route('admin.wallet.index') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="My Wallet" data-bs-placement="right"
                        aria-label="My Wallet" data-bs-original-title="My Wallet">
                        <i class="ph ph-wallet fs-4"></i>
                    </i>
                    <span class="item-name">My Wallet</span>
                </a>
            </li>
        @endif
        @role('super-admin')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.referrals.settings') ? 'active' : '' }}"
                    href="{{ route('admin.referrals.settings') }}">
                    <i class="icon" data-bs-toggle="tooltip" title="Referral Settings" data-bs-placement="right"
                        aria-label="Referral Settings" data-bs-original-title="Referral Settings">
                        <i class="ph ph-gear fs-4"></i>
                    </i>
                    <span class="item-name">Referral Settings</span>
                </a>
            </li>
        @endrole
    @endif
</ul>

<!-- Sidebar Menu End -->
