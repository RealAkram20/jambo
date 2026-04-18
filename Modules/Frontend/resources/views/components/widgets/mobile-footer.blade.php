{{-- Mobile bottom navigation (shown <992px, hidden on desktop via CSS). --}}
<nav class="jambo-mobile-nav" aria-label="Mobile navigation">
    @php
        $current = Route::currentRouteName();
    @endphp
    <a href="{{ route('frontend.ott') }}" class="jambo-mobile-nav__item {{ $current === 'frontend.ott' ? 'is-active' : '' }}">
        <i class="ph ph-house-simple jambo-mobile-nav__icon"></i>
        <i class="ph-fill ph-house-simple jambo-mobile-nav__icon jambo-mobile-nav__icon--active"></i>
        <span class="jambo-mobile-nav__label">Home</span>
    </a>
    <a href="{{ route('frontend.movie') }}" class="jambo-mobile-nav__item {{ $current === 'frontend.movie' ? 'is-active' : '' }}">
        <i class="ph ph-film-strip jambo-mobile-nav__icon"></i>
        <i class="ph-fill ph-film-strip jambo-mobile-nav__icon jambo-mobile-nav__icon--active"></i>
        <span class="jambo-mobile-nav__label">Movies</span>
    </a>
    <a href="{{ route('frontend.series') }}" class="jambo-mobile-nav__item {{ $current === 'frontend.series' ? 'is-active' : '' }}">
        <i class="ph ph-television jambo-mobile-nav__icon"></i>
        <i class="ph-fill ph-television jambo-mobile-nav__icon jambo-mobile-nav__icon--active"></i>
        <span class="jambo-mobile-nav__label">Series</span>
    </a>
    <a href="{{ route('frontend.watchlist_detail') }}" class="jambo-mobile-nav__item {{ $current === 'frontend.watchlist_detail' ? 'is-active' : '' }}">
        <i class="ph ph-bookmark-simple jambo-mobile-nav__icon"></i>
        <i class="ph-fill ph-bookmark-simple jambo-mobile-nav__icon jambo-mobile-nav__icon--active"></i>
        <span class="jambo-mobile-nav__label">Watchlist</span>
    </a>
</nav>
