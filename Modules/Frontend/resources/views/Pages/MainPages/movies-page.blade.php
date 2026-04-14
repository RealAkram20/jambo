@extends('frontend::layouts.master', ['isSwiperSlider' => true, 'isFslightbox' => true, 'bodyClass' => 'custom-header-relative', 'isSweetalert' => true])

@section('content')
    <section class="banner-container">
        <div class="movie-banner">
            <div class="swiper swiper-banner-container" data-swiper="banner-detail-slider">
                <div class="swiper-wrapper">
                    @foreach ($featuredMovies as $i => $movie)
                        @include('frontend::components.cards.movie-slider', [
                            'movieCard' => 'movie-banner-' . ($i + 1),
                            'imagePath' => $movie->backdrop_url ?: $movie->poster_url ?: 'media/rabbit.webp',
                            'movieRating' => true,
                            'movieTitle' => $movie->title,
                            'movieRange' => $movie->rating ?: '4.0',
                            'movieCate' => $movie->tier_required ? strtoupper($movie->tier_required) : 'PG',
                            'movieTime' => $movie->runtime_minutes ? floor($movie->runtime_minutes / 60) . 'hr : ' . ($movie->runtime_minutes % 60) . 'm' : '1hr : 45m',
                            'movieYear' => $movie->year ?: ($movie->published_at?->format('F Y') ?? ''),
                            'calenderIcon' => true,
                            'buttonUrl' => route('frontend.movie_detail', $movie->slug),
                            'movieText' => $movie->synopsis ?: '',
                        ])
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

    <div class="container-fluid">
        <div class="overflow-hidden">
            <section class="related-movie-block mt-5">
                <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
                    <h4 class="main-title text-capitalize mb-0">{{ __('frontendform.movies_recommended') }}</h4>
                    <span class="text-muted">{{ $movies->count() }} {{ __('streamTag.movies') ?? 'Movies' }}</span>
                </div>
                <div class="card-style-slider">
                    <div class="position-relative swiper swiper-card" data-slide="6" data-laptop="6" data-tab="3"
                        data-mobile="2" data-mobile-sm="2" data-autoplay="false" data-loop="false"
                        data-navigation="true" data-pagination="true">
                        <ul class="p-0 swiper-wrapper m-0 list-inline">
                            @forelse ($movies as $movie)
                                <li class="swiper-slide">
                                    @include('frontend::components.cards.card-style', [
                                        'cardImage' => $movie->poster_url ?: 'media/rabbit-portrait.webp',
                                        'cardTitle' => $movie->title,
                                        'movietime' => $movie->runtime_minutes ? floor($movie->runtime_minutes / 60) . 'hr : ' . ($movie->runtime_minutes % 60) . 'mins' : null,
                                        'cardLang' => 'English',
                                        'cardPath' => route('frontend.movie_detail', $movie->slug),
                                        'cardGenres' => $movie->genres->take(2)->pluck('name')->all(),
                                        'productPremium' => (bool) $movie->tier_required,
                                    ])
                                </li>
                            @empty
                                <li class="swiper-slide"><p class="text-muted">No movies published yet.</p></li>
                            @endforelse
                        </ul>
                        <div class="swiper-button swiper-button-next d-none d-lg-block"></div>
                        <div class="swiper-button swiper-button-prev d-none d-lg-block"></div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}
@endsection
