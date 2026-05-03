@extends('frontend::layouts.master', ['isSwiperSlider' => true, 'isFslightbox' => true, 'bodyClass' => 'custom-header-relative', 'isSweetalert' => true])

@section('content')
    {{-- Hero banner — mixes movies + series so the overview surfaces
         the VJ's most recent work regardless of type. Each $heroItems
         entry carries an `_isShow` flag set by the controller so the
         partial doesn't need to do an instanceof check. --}}
    <section class="banner-container">
        <div class="movie-banner">
            <div class="swiper swiper-banner-container" data-swiper="banner-detail-slider">
                <div class="swiper-wrapper">
                    @forelse ($heroItems as $i => $item)
                        @php
                            $isShow = (bool) ($item->_isShow ?? false);
                        @endphp
                        @include('frontend::components.cards.movie-slider', [
                            'movieCard'    => 'vj-banner-' . ($i + 1),
                            'imagePath'    => $item->backdrop_url ?: $item->poster_url ?: ($isShow ? 'media/vikings.webp' : 'media/rabbit.webp'),
                            'movieRating'  => true,
                            'movieTitle'   => $item->title,
                            'movieRange'   => $item->rating ?: '4.0',
                            'movieCate'    => $item->tier_required ? strtoupper($item->tier_required) : 'PG',
                            'movieTime'    => (! $isShow && $item->runtime_minutes)
                                ? floor($item->runtime_minutes / 60) . 'hr : ' . ($item->runtime_minutes % 60) . 'm'
                                : ($isShow ? null : '1hr : 45m'),
                            'NoOfSeasons'  => $isShow ? $item->seasons->count() : null,
                            'movieYear'    => $item->year ?: ($item->published_at?->format('F Y') ?? ''),
                            'calenderIcon' => true,
                            'buttonUrl'    => $isShow
                                ? route('frontend.series_detail', $item->slug)
                                : route('frontend.movie_detail', $item->slug),
                            'movieText'    => $item->synopsis ?: '',
                            'trailerUrl'   => $item->trailer_url ?: null,
                        ])
                    @empty
                        {{-- Empty hero is unlikely (we already filter VJs to those with
                             at least one published title) but the partial would crash
                             on an empty wrapper, so render a neutral fallback. --}}
                    @endforelse
                </div>
                <div class="swiper-banner-button-next d-none d-lg-block"><i class="ph ph-caret-right arrow-icon"></i></div>
                <div class="swiper-banner-button-prev d-none d-lg-block"><i class="ph ph-caret-left icli arrow-icon"></i></div>
                <div class="swiper-pagination d-block d-lg-none"></div>
            </div>
        </div>
    </section>

    {{-- No overflow-hidden wrapper here: card-hover extends ~1.25em
         outside each card and ~5em below, so clipping cuts off the
         leftmost column's reveal panel. Same pattern as the other VJ
         pages and /movie. --}}
    <div class="container-fluid pb-5 mb-4 px-3 px-md-4">
        <div>
            {{-- Page header — VJ name, optional bio, combined catalogue size. --}}
            <section class="related-movie-block mt-5 mb-2">
                <div class="d-flex align-items-center justify-content-between px-1 pb-2 border-bottom border-dark">
                    <div>
                        <h3 class="main-title text-capitalize mb-1">{{ $vj->name }}</h3>
                        @if ($vj->description)
                            <p class="text-muted mb-0 small">{{ $vj->description }}</p>
                        @endif
                    </div>
                    <span class="text-muted">
                        {{ $moviesTotal }} {{ __('streamTag.movies') ?? 'movies' }}
                        &middot;
                        {{ $showsTotal }} {{ __('streamTag.shows') ?? 'series' }}
                    </span>
                </div>
            </section>

            {{-- Movies rail. Reuses the standard vj-carousel partial so
                 the look matches the per-VJ rows on /movie. View-All
                 link goes to the dedicated movies-only catalogue page
                 where everything is genre-organised with Load More. --}}
            @if ($movies->isNotEmpty())
                <section class="related-movie-block mt-5">
                    <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
                        <h4 class="main-title text-capitalize mb-0">{{ __('streamTag.movies') ?? 'Movies' }}</h4>
                        <a href="{{ route('frontend.vj_movie_detail', $vj->slug) }}"
                           class="text-primary iq-view-all text-decoration-none flex-none">
                            {{ __('streamButtons.view_all') ?? 'View All' }}
                        </a>
                    </div>
                    <div class="card-style-slider">
                        <div class="position-relative swiper swiper-card"
                             data-slide="7" data-laptop="7" data-tab="4" data-mobile="3" data-mobile-sm="3"
                             data-autoplay="false" data-loop="false"
                             data-navigation="true" data-pagination="true">
                            <ul class="p-0 swiper-wrapper m-0 list-inline">
                                @foreach ($movies as $movie)
                                    <li class="swiper-slide">
                                        @include('frontend::components.cards.card-style', [
                                            'cardImage'      => $movie->poster_url ?: 'media/rabbit-portrait.webp',
                                            'cardTitle'      => $movie->title,
                                            'movietime'      => $movie->runtime_minutes
                                                ? floor($movie->runtime_minutes / 60) . 'hr : ' . ($movie->runtime_minutes % 60) . 'mins'
                                                : null,
                                            'cardLang'       => 'English',
                                            'cardPath'       => route('frontend.movie_detail', $movie->slug),
                                            'cardGenres'     => $movie->relationLoaded('genres') ? $movie->genres->take(2)->pluck('name')->all() : null,
                                            'productPremium' => (bool) $movie->tier_required,
                                            'watchableType'  => 'movie',
                                            'watchableId'    => $movie->id,
                                        ])
                                    </li>
                                @endforeach
                            </ul>
                            <div class="swiper-button swiper-button-next d-none d-lg-block"></div>
                            <div class="swiper-button swiper-button-prev d-none d-lg-block"></div>
                        </div>
                    </div>
                </section>
            @endif

            {{-- Series rail. View-All goes to the existing
                 /vj-series/{slug} page (unchanged from before). --}}
            @if ($shows->isNotEmpty())
                <section class="related-movie-block mt-5">
                    <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
                        <h4 class="main-title text-capitalize mb-0">{{ __('streamTag.shows') ?? 'Series' }}</h4>
                        <a href="{{ route('frontend.vj_series_detail', $vj->slug) }}"
                           class="text-primary iq-view-all text-decoration-none flex-none">
                            {{ __('streamButtons.view_all') ?? 'View All' }}
                        </a>
                    </div>
                    <div class="card-style-slider">
                        <div class="position-relative swiper swiper-card"
                             data-slide="7" data-laptop="7" data-tab="4" data-mobile="3" data-mobile-sm="3"
                             data-autoplay="false" data-loop="false"
                             data-navigation="true" data-pagination="true">
                            <ul class="p-0 swiper-wrapper m-0 list-inline">
                                @foreach ($shows as $show)
                                    <li class="swiper-slide">
                                        @include('frontend::components.cards.card-style', [
                                            'cardImage'      => $show->poster_url ?: 'media/vikings-portrait.webp',
                                            'cardTitle'      => $show->title,
                                            'movietime'      => null,
                                            'cardLang'       => 'English',
                                            'cardPath'       => route('frontend.series_detail', $show->slug),
                                            'cardGenres'     => $show->relationLoaded('genres') ? $show->genres->take(2)->pluck('name')->all() : null,
                                            'productPremium' => (bool) $show->tier_required,
                                            'watchableType'  => 'show',
                                            'watchableId'    => $show->id,
                                        ])
                                    </li>
                                @endforeach
                            </ul>
                            <div class="swiper-button swiper-button-next d-none d-lg-block"></div>
                            <div class="swiper-button swiper-button-prev d-none d-lg-block"></div>
                        </div>
                    </div>
                </section>
            @endif

            {{-- Defensive: if a VJ exists but has zero published titles
                 of either type they still slipped through (they would
                 need at least one published item to land here, but the
                 fallback keeps the page from rendering as just a
                 header). --}}
            @if ($movies->isEmpty() && $shows->isEmpty())
                <section class="related-movie-block mt-5">
                    <p class="text-muted">This VJ doesn't have any published titles yet.</p>
                </section>
            @endif
        </div>
    </div>

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
@endsection
