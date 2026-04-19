@extends('profile-hub._layout', ['pageTitle' => 'Watchlist', 'user' => $user, 'activeTab' => $activeTab])

@php
    // Every card on this page is, by definition, already in the
    // user's watchlist. Pre-build the index so the toggle button
    // shows the "in list" (check) state on initial render — clicking
    // it removes the item, matching the rest of the site.
    $watchlistIndex = [];
    foreach ($movies as $m) { $watchlistIndex['movie:' . $m->id] = true; }
    foreach ($shows  as $s) { $watchlistIndex['show:'  . $s->id] = true; }
@endphp

@section('hub-content')
    <div class="jambo-hub-card">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h5 class="mb-1">Your watchlist</h5>
                <p class="jambo-hub-card__subtitle mb-0">
                    {{ $movies->count() + $shows->count() }}
                    {{ Str::plural('title', $movies->count() + $shows->count()) }} saved for later.
                </p>
            </div>
            <i class="ph ph-bookmarks-simple fs-2 text-muted"></i>
        </div>

        <ul class="nav nav-underline mb-4" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#hub-wl-movies"
                        type="button" role="tab">
                    Movies <span class="text-muted small">({{ $movies->count() }})</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#hub-wl-shows"
                        type="button" role="tab">
                    Series <span class="text-muted small">({{ $shows->count() }})</span>
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <div id="hub-wl-movies" class="tab-pane fade show active" role="tabpanel">
                @if ($movies->count())
                    <div class="row g-3 row-cols-2 row-cols-md-3 row-cols-lg-4">
                        @foreach ($movies as $m)
                            <div class="col">
                                @include('frontend::components.cards.card-style', [
                                    'cardImage'         => $m->poster_url ?: 'media/rabbit-portrait.webp',
                                    'cardTitle'         => $m->title,
                                    'cardYear'          => $m->year,
                                    'movietime'         => $m->runtime_minutes && $m->runtime_minutes >= 10
                                        ? floor($m->runtime_minutes / 60) . 'h ' . ($m->runtime_minutes % 60) . 'm'
                                        : null,
                                    'cardLang'          => 'English',
                                    'cardPath'          => route('frontend.watchlist_play', $m->slug),
                                    'cardGenres'        => $m->relationLoaded('genres') ? $m->genres->take(2)->pluck('name')->all() : null,
                                    'productPremium'    => (bool) $m->tier_required,
                                    'watchableType'     => 'movie',
                                    'watchableId'      => $m->id,
                                    'userWatchlistIndex' => $watchlistIndex,
                                ])
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="ph ph-film-slate fs-1 text-muted d-block mb-2"></i>
                        <p class="text-muted mb-3">
                            No movies saved yet. Tap the <i class="ph ph-plus"></i> on any title to save it for later.
                        </p>
                        <a href="{{ route('frontend.movie') }}" class="btn btn-primary btn-sm">
                            <i class="ph ph-film-strip me-1"></i> Browse movies
                        </a>
                    </div>
                @endif
            </div>

            <div id="hub-wl-shows" class="tab-pane fade" role="tabpanel">
                @if ($shows->count())
                    <div class="row g-3 row-cols-2 row-cols-md-3 row-cols-lg-4">
                        @foreach ($shows as $s)
                            @php
                                $seasonsLabel = isset($s->seasons_count) && $s->seasons_count > 0
                                    ? $s->seasons_count . ' ' . Str::plural('season', $s->seasons_count)
                                    : null;
                            @endphp
                            <div class="col">
                                @include('frontend::components.cards.card-style', [
                                    'cardImage'         => $s->poster_url ?: 'media/vikings-portrait.webp',
                                    'cardTitle'         => $s->title,
                                    'cardYear'          => $s->year,
                                    'movietime'         => $seasonsLabel,
                                    'cardLang'          => 'English',
                                    'cardPath'          => route('frontend.watchlist_series_play', $s->slug),
                                    'cardGenres'        => $s->relationLoaded('genres') ? $s->genres->take(2)->pluck('name')->all() : null,
                                    'productPremium'    => (bool) $s->tier_required,
                                    'watchableType'     => 'show',
                                    'watchableId'      => $s->id,
                                    'userWatchlistIndex' => $watchlistIndex,
                                ])
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="ph ph-television fs-1 text-muted d-block mb-2"></i>
                        <p class="text-muted mb-3">
                            No series saved yet. Tap the <i class="ph ph-plus"></i> on any series to save it for later.
                        </p>
                        <a href="{{ route('frontend.series') }}" class="btn btn-primary btn-sm">
                            <i class="ph ph-television me-1"></i> Browse series
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
