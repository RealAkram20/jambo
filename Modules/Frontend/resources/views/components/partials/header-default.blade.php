<header class="jambo-header" id="jambo-header">
    {{-- Row 1: Logo | Search | Actions --}}
    <div class="jambo-header__bar">
        <div class="container-fluid d-flex align-items-center justify-content-between gap-3">
            {{-- Left: hamburger (desktop only) + logo + subscribe badge --}}
            <div class="jambo-header__left d-flex align-items-center gap-2 flex-shrink-0">
                {{-- Back / Forward shortcuts. Visible only when the
                     site is running as an installed PWA (display-mode:
                     standalone) — installed windows have no browser
                     chrome, so users have no other way to go back.
                     Hidden in regular browser tabs since the browser
                     toolbar already provides them. --}}
                <button type="button" class="jambo-header__menu-btn jambo-pwa-nav d-none"
                        id="jambo-pwa-back" aria-label="Go back" title="Back">
                    <i class="ph ph-caret-left"></i>
                </button>
                <button type="button" class="jambo-header__menu-btn jambo-pwa-nav d-none"
                        id="jambo-pwa-forward" aria-label="Go forward" title="Forward">
                    <i class="ph ph-caret-right"></i>
                </button>
                <button class="jambo-header__menu-btn d-none d-lg-flex" type="button" id="jambo-sidebar-toggle" aria-label="Menu">
                    <i class="ph ph-list"></i>
                </button>
                <a href="{{ route('frontend.ott') }}" class="jambo-header__logo">
                    <img src="{{ branding_asset('logo', 'frontend/images/logo.webp') }}" alt="{{ config('app.name') }}" class="img-fluid" loading="lazy">
                </a>
                @unless (auth()->check() && auth()->user()->hasRole('admin'))
                    <a href="{{ route('frontend.pricing-page') }}" class="jambo-subscribe-badge d-none d-md-inline-flex">
                        <i class="ph-fill ph-crown"></i>
                        <span>Subscribe</span>
                    </a>
                @endunless
            </div>

            {{-- Center: search bar --}}
            <div class="jambo-header__search flex-grow-1" id="jambo-search">
                <form class="jambo-search-form" action="{{ route('frontend.search') }}" method="GET" autocomplete="off">
                    <input type="text" class="jambo-search-input" id="jambo-search-input" name="q" placeholder="Search movies, series, cast..." aria-label="Search">
                    <button type="submit" class="jambo-search-btn" aria-label="Search">
                        <i class="ph ph-magnifying-glass"></i>
                    </button>
                </form>
                {{-- AJAX results dropdown --}}
                <div class="jambo-search-results" id="jambo-search-results" hidden></div>
            </div>

            {{-- Right: search toggle (mobile) + notification + user --}}
            <div class="jambo-header__right d-flex align-items-center flex-shrink-0">
                <button class="jambo-header__search-toggle jambo-header__icon d-lg-none" type="button" id="jambo-search-toggle" aria-label="Search">
                    <i class="ph ph-magnifying-glass"></i>
                </button>
                @php
                    // Cheap count — the `notifications` table has a
                    // composite index on notifiable; one extra query
                    // per request is fine.
                    $jamboNotifUnread = auth()->check()
                        ? auth()->user()->unreadNotifications()->count()
                        : 0;

                    // Guests -> login. Admins -> admin index. Regular
                    // users go straight to the profile hub's
                    // Notifications tab so the sidebar highlights it
                    // without an extra redirect.
                    if (!auth()->check()) {
                        $jamboBellUrl = route('login');
                    } elseif (auth()->user()->hasRole('admin')) {
                        $jamboBellUrl = route('notifications.index');
                    } else {
                        $jamboBellUrl = route('profile.notifications', [
                            'username' => auth()->user()->username,
                        ]);
                    }
                @endphp
                <a href="{{ $jamboBellUrl }}" data-notif-bell
                   class="jambo-header__icon position-relative" title="Notifications">
                    <i class="ph {{ $jamboNotifUnread > 0 ? 'ph-fill ph-bell' : 'ph-bell' }}" data-notif-bell-icon></i>
                    <span class="jambo-notif-badge" data-notif-bell-badge
                          style="{{ $jamboNotifUnread > 0 ? '' : 'display:none;' }}">{{ $jamboNotifUnread > 99 ? '99+' : $jamboNotifUnread }}</span>
                </a>

                @if (auth()->check())
                    @php
                        $jamboIsAdmin = auth()->user()->hasRole('admin');
                        $jamboHeaderAvatar = auth()->user()->profile_image
                            ?: asset('frontend/images/user/user6.jpg');
                    @endphp
                    <div class="dropdown">
                        <a href="javascript:void(0)" class="jambo-header__avatar" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="{{ $jamboHeaderAvatar }}" alt="{{ auth()->user()->full_name ?: (auth()->user()->username ?? 'User') }}" class="rounded-circle" width="32" height="32" loading="lazy" style="object-fit:cover;">
                        </a>
                        <div class="dropdown-menu dropdown-menu-end jambo-user-dropdown">
                            <div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom border-dark">
                                <img src="{{ $jamboHeaderAvatar }}" class="rounded-circle" width="40" height="40" alt="" style="object-fit:cover;">
                                <div>
                                    <div class="fw-semibold d-flex align-items-center gap-2">
                                        {{ auth()->user()->full_name ?: (auth()->user()->username ?? 'User') }}
                                        @if ($jamboIsAdmin)
                                            <span class="badge bg-primary" style="font-size:10px;">Admin</span>
                                        @endif
                                    </div>
                                    <small class="text-muted">{{ auth()->user()->email ?? '' }}</small>
                                </div>
                            </div>
                            @if ($jamboIsAdmin)
                                {{-- Admins never use the user profile hub
                                     (see feedback_admin_vs_user_separation).
                                     Single shortcut back to their dashboard. --}}
                                <a href="{{ url('/app') }}" class="dropdown-item d-flex align-items-center gap-2 py-2">
                                    <i class="ph ph-squares-four"></i> Admin Dashboard
                                </a>
                            @else
                                @php $jamboHubUser = auth()->user()->username; @endphp
                                <a href="{{ route('profile.show', ['username' => $jamboHubUser]) }}" class="dropdown-item d-flex align-items-center gap-2 py-2">
                                    <i class="ph ph-user-circle"></i> Profile
                                </a>
                                <a href="{{ route('profile.membership', ['username' => $jamboHubUser]) }}" class="dropdown-item d-flex align-items-center gap-2 py-2">
                                    <i class="ph ph-crown"></i> Membership
                                </a>
                                <a href="{{ route('profile.watchlist', ['username' => $jamboHubUser]) }}" class="dropdown-item d-flex align-items-center gap-2 py-2">
                                    <i class="ph ph-bookmarks-simple"></i> Watchlist
                                </a>
                                <a href="{{ route('profile.security', ['username' => $jamboHubUser]) }}" class="dropdown-item d-flex align-items-center gap-2 py-2">
                                    <i class="ph ph-shield-check"></i> Security
                                </a>
                                <a href="{{ route('frontend.pricing-page') }}" class="dropdown-item d-flex align-items-center gap-2 py-2">
                                    <i class="ph-fill ph-crown text-warning"></i> Subscribe
                                </a>
                            @endif
                            <div class="border-top border-dark">
                                <a href="{{ route('logout') }}" class="dropdown-item d-flex align-items-center gap-2 py-2"
                                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <i class="ph ph-sign-out"></i> Logout
                                </a>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                            </div>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="btn btn-sm btn-primary rounded-pill px-3">Sign in</a>
                @endif
            </div>
        </div>
    </div>

    {{-- Row 2: Genre chips bar --}}
    <div class="jambo-genres-bar" id="jambo-genres-bar">
        <div class="container-fluid">
            <div class="jambo-genres-scroll">
                <a href="{{ route('frontend.ott') }}" class="jambo-genre-chip {{ Route::currentRouteName() === 'frontend.ott' && !request('genre') ? 'active' : '' }}">All</a>
                @foreach ($headerGenres ?? [] as $genre)
                    <a href="{{ route('frontend.genres', ['slug' => $genre->slug]) }}" class="jambo-genre-chip {{ request('genre') === $genre->slug ? 'active' : '' }}">{{ $genre->name }}</a>
                @endforeach
            </div>
        </div>
    </div>
</header>

{{-- Desktop sidebar (YouTube-style, expands on hamburger click) --}}
<aside class="jambo-sidebar" id="jambo-sidebar">
    <div class="jambo-sidebar__inner">
        <nav class="jambo-sidebar__nav">
            <a href="{{ route('frontend.ott') }}" class="jambo-sidebar__link {{ Route::currentRouteName() === 'frontend.ott' ? 'active' : '' }}">
                <i class="ph ph-house"></i><span>Home</span>
            </a>
            <a href="{{ route('frontend.movie') }}" class="jambo-sidebar__link {{ Route::currentRouteName() === 'frontend.movie' ? 'active' : '' }}">
                <i class="ph ph-film-strip"></i><span>Movies</span>
            </a>
            <a href="{{ route('frontend.series') }}" class="jambo-sidebar__link {{ Route::currentRouteName() === 'frontend.series' ? 'active' : '' }}">
                <i class="ph ph-monitor-play"></i><span>Series</span>
            </a>
            <a href="{{ route('frontend.genres') }}" class="jambo-sidebar__link {{ Route::currentRouteName() === 'frontend.genres' ? 'active' : '' }}">
                <i class="ph ph-squares-four"></i><span>Genres</span>
            </a>
            <a href="{{ route('frontend.cast_list') }}" class="jambo-sidebar__link {{ Route::currentRouteName() === 'frontend.cast_list' ? 'active' : '' }}">
                <i class="ph ph-users"></i><span>Cast</span>
            </a>

            <div class="jambo-sidebar__divider"></div>

            <a href="{{ route('frontend.pricing-page') }}" class="jambo-sidebar__link {{ Route::currentRouteName() === 'frontend.pricing-page' ? 'active' : '' }}">
                <i class="ph-fill ph-crown text-warning"></i><span>Subscribe</span>
            </a>
            <a href="{{ route('frontend.about_us') }}" class="jambo-sidebar__link">
                <i class="ph ph-info"></i><span>About</span>
            </a>
            <a href="{{ route('frontend.contact_us') }}" class="jambo-sidebar__link">
                <i class="ph ph-envelope"></i><span>Contact</span>
            </a>
            <a href="{{ route('frontend.faq_page') }}" class="jambo-sidebar__link">
                <i class="ph ph-question"></i><span>FAQ</span>
            </a>
        </nav>
    </div>
</aside>
{{-- Sidebar overlay --}}
<div class="jambo-sidebar-overlay" id="jambo-sidebar-overlay"></div>

{{-- Search + sidebar JS --}}
<script>
(function() {
    // ---- Debounced AJAX search ----
    var input = document.getElementById('jambo-search-input');
    var results = document.getElementById('jambo-search-results');
    var searchUrl = {{ Js::from(route('frontend.search')) }};
    var timer = null;

    if (input && results) {
        input.addEventListener('input', function() {
            clearTimeout(timer);
            var q = this.value.trim();
            if (q.length < 2) { results.hidden = true; return; }
            timer = setTimeout(function() {
                fetch(searchUrl + '?q=' + encodeURIComponent(q), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    var items = [].concat(data.movies || [], data.shows || []);
                    if (items.length === 0) {
                        results.innerHTML = '<div class="jambo-search-empty">No results found</div>';
                    } else {
                        results.innerHTML = items.map(function(item) {
                            return '<a href="' + item.url + '" class="jambo-search-item">' +
                                '<img src="' + (item.poster || '') + '" alt="" class="jambo-search-thumb" onerror="this.style.display=\'none\'">' +
                                '<div class="jambo-search-info">' +
                                    '<div class="jambo-search-title">' + escapeHtml(item.title) + '</div>' +
                                    '<small class="text-muted">' + item.type + (item.year ? ' &middot; ' + item.year : '') + '</small>' +
                                '</div>' +
                            '</a>';
                        }).join('');
                    }
                    results.hidden = false;
                })
                .catch(function() { results.hidden = true; });
            }, 400);
        });

        document.addEventListener('click', function(e) {
            if (!e.target.closest('#jambo-search')) {
                results.hidden = true;
                // Close mobile expanded search when clicking outside.
                var sb = document.getElementById('jambo-search');
                if (sb && sb.classList.contains('jambo-search--expanded') && !e.target.closest('#jambo-search-toggle')) {
                    sb.classList.remove('jambo-search--expanded');
                }
            }
        });
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                results.hidden = true;
                this.blur();
                var sb = document.getElementById('jambo-search');
                if (sb) sb.classList.remove('jambo-search--expanded');
            }
        });
    }

    // ---- Mobile search toggle ----
    var toggle = document.getElementById('jambo-search-toggle');
    var searchBox = document.getElementById('jambo-search');
    if (toggle && searchBox) {
        toggle.addEventListener('click', function() {
            searchBox.classList.toggle('jambo-search--expanded');
            if (searchBox.classList.contains('jambo-search--expanded')) {
                input.focus();
            }
        });
    }

    // ---- Desktop sidebar toggle (YouTube-style) ----
    var sidebarBtn = document.getElementById('jambo-sidebar-toggle');
    var sidebar = document.getElementById('jambo-sidebar');
    var overlay = document.getElementById('jambo-sidebar-overlay');

    if (sidebarBtn && sidebar) {
        sidebarBtn.addEventListener('click', function() {
            sidebar.classList.toggle('jambo-sidebar--open');
            if (overlay) overlay.classList.toggle('jambo-sidebar-overlay--visible');
        });
        if (overlay) {
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('jambo-sidebar--open');
                overlay.classList.remove('jambo-sidebar-overlay--visible');
            });
        }
    }

    function escapeHtml(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }
})();
</script>

{{-- PWA back/forward chrome. Standalone (installed) desktop windows
     have no browser toolbar, so without these buttons users can't
     navigate. On mobile PWAs we leave them off because the OS already
     provides a back gesture / hardware button, and the extra header
     chrome eats horizontal space on small screens. We toggle visibility
     from JS rather than CSS-only because the standalone state can
     change at runtime (window install / uninstall), and we also want
     to disable Forward when there's no forward history. --}}
<script>
(function () {
    var backBtn = document.getElementById('jambo-pwa-back');
    var fwdBtn  = document.getElementById('jambo-pwa-forward');
    if (!backBtn || !fwdBtn) return;

    function isStandalone() {
        return (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches)
            || window.navigator.standalone === true;
    }

    // Bootstrap's `lg` breakpoint. Mobile / tablet portrait stays
    // chromeless; desktop / large tablets get the buttons.
    var DESKTOP_MIN_PX = 992;

    function applyVisibility() {
        var show = isStandalone() && window.innerWidth >= DESKTOP_MIN_PX;
        if (show) {
            backBtn.classList.remove('d-none');
            fwdBtn.classList.remove('d-none');
        } else {
            backBtn.classList.add('d-none');
            fwdBtn.classList.add('d-none');
        }
    }

    function applyEnabled() {
        // history.length is 1 on a fresh-load entry. Disable back when
        // there's no prior page to go to so users get a hint instead
        // of a no-op click.
        var hasHistory = window.history.length > 1;
        backBtn.disabled = !hasHistory;
        backBtn.style.opacity = hasHistory ? '' : '0.4';
        backBtn.style.cursor = hasHistory ? '' : 'not-allowed';
    }

    backBtn.addEventListener('click', function () {
        if (window.history.length > 1) window.history.back();
    });
    fwdBtn.addEventListener('click', function () {
        window.history.forward();
    });

    // Keyboard: Alt+ArrowLeft / Alt+ArrowRight (matches browser default).
    document.addEventListener('keydown', function (e) {
        if (!e.altKey) return;
        if (e.key === 'ArrowLeft')  { backBtn.click();  e.preventDefault(); }
        if (e.key === 'ArrowRight') { fwdBtn.click();   e.preventDefault(); }
    });

    applyVisibility();
    applyEnabled();
    if (window.matchMedia) {
        window.matchMedia('(display-mode: standalone)').addEventListener('change', applyVisibility);
    }
    // Re-evaluate on tablet rotation / window resize across the lg
    // breakpoint so the buttons appear/disappear immediately rather
    // than only on the next page load.
    window.addEventListener('resize', applyVisibility);
    window.addEventListener('popstate', applyEnabled);
    window.addEventListener('pageshow', applyEnabled);
})();
</script>
