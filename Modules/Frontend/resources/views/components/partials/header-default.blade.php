<header class="jambo-header" id="jambo-header">
    {{-- Row 1: Logo | Search | Actions --}}
    <div class="jambo-header__bar">
        <div class="container-fluid d-flex align-items-center justify-content-between gap-3">
            {{-- Left: hamburger (desktop only) + logo + subscribe badge --}}
            <div class="jambo-header__left d-flex align-items-center gap-2 flex-shrink-0">
                <button class="jambo-header__menu-btn d-none d-lg-flex" type="button" id="jambo-sidebar-toggle" aria-label="Menu">
                    <i class="ph ph-list"></i>
                </button>
                <a href="{{ route('frontend.ott') }}" class="jambo-header__logo">
                    <img src="{{ branding_asset('logo', 'frontend/images/logo.webp') }}" alt="{{ config('app.name') }}" class="img-fluid" loading="lazy">
                </a>
                <a href="{{ route('frontend.pricing-page') }}" class="jambo-subscribe-badge d-none d-md-inline-flex">
                    <i class="ph-fill ph-crown"></i>
                    <span>Subscribe</span>
                </a>
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
                @endphp
                <a href="{{ auth()->check() ? route('notifications.index') : route('login') }}"
                   class="jambo-header__icon position-relative" title="Notifications">
                    <i class="ph {{ $jamboNotifUnread > 0 ? 'ph-fill ph-bell' : 'ph-bell' }}"></i>
                    @if ($jamboNotifUnread > 0)
                        <span class="jambo-notif-badge">{{ $jamboNotifUnread > 99 ? '99+' : $jamboNotifUnread }}</span>
                    @endif
                </a>

                @if (auth()->check())
                    <div class="dropdown">
                        <a href="javascript:void(0)" class="jambo-header__avatar" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="{{ asset('frontend/images/user/user6.jpg') }}" alt="{{ auth()->user()->full_name ?: (auth()->user()->username ?? 'User') }}" class="rounded-circle" width="32" height="32" loading="lazy">
                        </a>
                        <div class="dropdown-menu dropdown-menu-end jambo-user-dropdown">
                            <div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom border-dark">
                                <img src="{{ asset('frontend/images/user/user6.jpg') }}" class="rounded-circle" width="40" height="40" alt="">
                                <div>
                                    <div class="fw-semibold">{{ auth()->user()->full_name ?: (auth()->user()->username ?? 'User') }}</div>
                                    <small class="text-muted">{{ auth()->user()->email ?? '' }}</small>
                                </div>
                            </div>
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
