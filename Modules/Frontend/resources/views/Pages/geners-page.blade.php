@extends('frontend::layouts.master', [
    // Breadcrumb on the all-genres grid; full-hero treatment on a
    // specific genre so the banner can run edge-to-edge.
    'isBreadCrumb' => !isset($genre),
    'isSwiperSlider' => isset($genre),
    'isFslightbox' => isset($genre),
    'bodyClass' => isset($genre) ? 'custom-header-relative' : null,
    'title' => isset($genre) ? $genre->name : __('frontendheader.geners'),
])

@section('content')
    @isset($genre)
        @if (isset($featured) && $featured->isNotEmpty())
            <section class="banner-container">
                <div class="movie-banner">
                    <div class="swiper swiper-banner-container" data-swiper="banner-detail-slider">
                        <div class="swiper-wrapper">
                            @foreach ($featured as $i => $item)
                                @php
                                    $isShow = $item->_isShow ?? false;
                                    $fallback = $isShow ? 'media/vikings.webp' : 'media/rabbit.webp';
                                    $buttonUrl = $isShow
                                        ? route('frontend.series_detail', $item->slug)
                                        : route('frontend.movie_detail', $item->slug);
                                @endphp
                                @include('frontend::components.cards.movie-slider', array_filter([
                                    'movieCard' => 'movie-banner-' . ($i + 1),
                                    'imagePath' => $item->backdrop_url ?: $item->poster_url ?: $fallback,
                                    'movieRating' => true,
                                    'movieTitle' => $item->title,
                                    'movieRange' => $item->rating ?: '4.0',
                                    'movieCate' => $item->tier_required ? strtoupper($item->tier_required) : 'PG',
                                    // Movies get runtime; shows get a season count.
                                    // Passing `null` and filtering out the key keeps
                                    // the partial's `isset()` checks from rendering
                                    // an empty clock/season badge.
                                    'movieTime' => !$isShow && $item->runtime_minutes
                                        ? floor($item->runtime_minutes / 60) . 'hr : ' . ($item->runtime_minutes % 60) . 'm'
                                        : null,
                                    'NoOfSeasons' => $isShow ? $item->seasons->count() : null,
                                    'movieYear' => $item->year ?: ($item->published_at?->format('F Y') ?? ''),
                                    'calenderIcon' => true,
                                    'buttonUrl' => $buttonUrl,
                                    'movieText' => $item->synopsis ?: '',
                                ], fn ($v) => $v !== null))
                            @endforeach
                        </div>
                        <div class="swiper-banner-button-next d-none d-lg-block">
                            <i class="ph ph-caret-right arrow-icon"></i>
                        </div>
                        <div class="swiper-banner-button-prev d-none d-lg-block">
                            <i class="ph ph-caret-left icli arrow-icon"></i>
                        </div>
                        <div class="swiper-pagination d-block d-lg-none"></div>
                    </div>
                </div>
            </section>
        @endif
    @endisset

    <section class="section-padding">
        <div class="container-fluid">
            @isset($genre)
                {{-- Single genre: show movies + shows in that genre --}}
                <div class="row">
                    <div class="col-sm-12 my-4">
                        <h5 class="main-title text-capitalize mb-0">{{ $genre->name }}</h5>
                    </div>
                </div>

                @if ($movies->count())
                    <div class="d-flex align-items-center justify-content-between mt-4 mb-3">
                        <h6 class="main-title text-capitalize mb-0">{{ __('frontendheader.movies') }}</h6>
                        <a href="{{ route('frontend.genre_vjs', $genre->slug) }}"
                           class="text-primary iq-view-all text-decoration-none">
                            {{ __('streamButtons.view_all') }}
                        </a>
                    </div>
                    <div class="row row-cols-xl-5 row-cols-md-3 row-cols-2 g-3">
                        @foreach ($movies as $movie)
                            <div class="col">
                                @include('frontend::components.cards.card-style', [
                                    'cardImage' => $movie->poster_url ?: 'media/rabbit-portrait.webp',
                                    'cardTitle' => $movie->title,
                                    'movietime' => $movie->runtime_minutes ? floor($movie->runtime_minutes / 60) . 'hr : ' . ($movie->runtime_minutes % 60) . 'mins' : null,
                                    'cardLang' => 'English',
                                    'cardPath' => route('frontend.movie_detail', $movie->slug),
                                    'cardGenres' => $movie->genres->take(2)->pluck('name')->all(),
                                    'productPremium' => (bool) $movie->tier_required,
                                    'watchableType' => 'movie',
                                    'watchableId'   => $movie->id,
                                ])
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($shows->count())
                    <div class="d-flex align-items-center justify-content-between mt-5 mb-3">
                        <h6 class="main-title text-capitalize mb-0">{{ __('frontendheader.tvshow') }}</h6>
                        <a href="{{ route('frontend.genre_vjs_shows', $genre->slug) }}"
                           class="text-primary iq-view-all text-decoration-none">
                            {{ __('streamButtons.view_all') }}
                        </a>
                    </div>
                    <div class="row row-cols-xl-5 row-cols-md-3 row-cols-2 g-3">
                        @foreach ($shows as $show)
                            <div class="col">
                                @include('frontend::components.cards.card-style', [
                                    'cardImage' => $show->poster_url ?: 'media/vikings-portrait.webp',
                                    'cardTitle' => $show->title,
                                    'movietime' => null,
                                    'cardLang' => 'English',
                                    'cardPath' => route('frontend.series_detail', $show->slug),
                                    'cardGenres' => $show->genres->take(2)->pluck('name')->all(),
                                    'productPremium' => (bool) $show->tier_required,
                                    'watchableType' => 'show',
                                    'watchableId'   => $show->id,
                                ])
                            </div>
                        @endforeach
                    </div>
                @endif

                @if (! $movies->count() && ! $shows->count())
                    <div class="text-center py-5 text-muted">{{ __('streamTag.no_results') ?? 'No content in this genre yet.' }}</div>
                @endif
            @else
                {{-- Grid of all genres --}}
                <div class="row">
                    <div class="col-sm-12 my-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <h5 class="main-title text-capitalize mb-0">{{ __('frontendheader.geners') }}</h5>
                            <span class="text-muted">{{ $genres->count() }}</span>
                        </div>
                    </div>
                </div>
                <div class="row row-cols-xl-5 row-cols-md-2 row-cols-1 geners-card geners-style-grid">
                    @forelse ($genres as $g)
                        <div class="col slide-items">
                            @include('frontend::components.cards.genres-card', [
                                'genersTitle' => $g->name,
                                'genersImage' => $g->featured_image_url ?: 'media/rabbit.webp',
                                'genersUrl' => route('frontend.genres', $g->slug),
                            ])
                        </div>
                    @empty
                        <div class="col-12 text-center py-5 text-muted">{{ __('streamTag.no_results') ?? 'No genres yet.' }}</div>
                    @endforelse
                </div>
            @endisset
        </div>
    </section>
@endsection
