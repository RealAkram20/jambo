{{-- Mobile bottom navigation (shown <992px, hidden on desktop via CSS). --}}
@php
    $current = Route::currentRouteName() ?? '';

    // Group-based active state: a detail / watch / VJ page under a
    // section should still light up that section's tab.
    $isHome = in_array($current, [
        'frontend.ott', 'frontend.index',
        'frontend.genres', 'frontend.all-genres',
        'frontend.all-categories', 'frontend.category',
        'frontend.tag', 'frontend.view-all-tags',
        'frontend.search', 'frontend.view_all',
    ], true);

    $isMovies = in_array($current, [
        'frontend.movie', 'frontend.movie_detail',
        'frontend.movie_more_vjs', 'frontend.vj_detail', 'frontend.vj_genre_more',
        'frontend.watch', 'frontend.watchlist_play',
    ], true);

    $isSeries = in_array($current, [
        'frontend.series', 'frontend.series_detail',
        'frontend.series_more_vjs', 'frontend.vj_series_detail', 'frontend.vj_series_genre_more',
        'frontend.episode', 'frontend.watchlist_series_play',
    ], true);

    $isWatchlist = in_array($current, [
        'frontend.watchlist_detail', 'profile.watchlist',
    ], true);

    // Watchlist link: send authed users straight into their profile
    // hub watchlist so there's no 302 hop; guests bounce to login.
    if (auth()->check()) {
        $watchlistHref = route('profile.watchlist', ['username' => auth()->user()->username]);
    } else {
        $watchlistHref = route('login');
    }
@endphp
<nav class="jambo-mobile-nav" aria-label="Mobile navigation">
    <a href="{{ route('frontend.ott') }}" class="jambo-mobile-nav__item {{ $isHome ? 'is-active' : '' }}">
        <i class="ph ph-house-simple jambo-mobile-nav__icon"></i>
        <i class="ph-fill ph-house-simple jambo-mobile-nav__icon jambo-mobile-nav__icon--active"></i>
        <span class="jambo-mobile-nav__label">Home</span>
    </a>
    <a href="{{ route('frontend.movie') }}" class="jambo-mobile-nav__item {{ $isMovies ? 'is-active' : '' }}">
        <i class="ph ph-film-strip jambo-mobile-nav__icon"></i>
        <i class="ph-fill ph-film-strip jambo-mobile-nav__icon jambo-mobile-nav__icon--active"></i>
        <span class="jambo-mobile-nav__label">Movies</span>
    </a>
    <a href="{{ route('frontend.series') }}" class="jambo-mobile-nav__item {{ $isSeries ? 'is-active' : '' }}">
        <i class="ph ph-television jambo-mobile-nav__icon"></i>
        <i class="ph-fill ph-television jambo-mobile-nav__icon jambo-mobile-nav__icon--active"></i>
        <span class="jambo-mobile-nav__label">Series</span>
    </a>
    <a href="{{ $watchlistHref }}" class="jambo-mobile-nav__item {{ $isWatchlist ? 'is-active' : '' }}">
        <i class="ph ph-bookmark-simple jambo-mobile-nav__icon"></i>
        <i class="ph-fill ph-bookmark-simple jambo-mobile-nav__icon jambo-mobile-nav__icon--active"></i>
        <span class="jambo-mobile-nav__label">Watchlist</span>
    </a>
</nav>
