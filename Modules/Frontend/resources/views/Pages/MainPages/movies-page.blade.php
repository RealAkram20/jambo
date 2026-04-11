@extends('frontend::layouts.master', ['isSwiperSlider' => true, 'isFslightbox' => true, 'bodyClass' => 'custom-header-relative', 'isSweetalert' => true])

@section('content')
    <section class="banner-container">
        <div class="movie-banner">
            <div class="swiper swiper-banner-container" data-swiper="banner-detail-slider">
                <div class="swiper-wrapper">
                    @include('frontend::components.cards.movie-slider', [
                        'movieCard' => 'movie-banner-1',
                        'imagePath' => 'media/rabbit.webp',
                        'movieRating' => true,
                        'movieTitle' => __('streamMovies.rabbit'),
                        'movieRange' => '4',
                        'movieCate' => 'G',
                        'movieTime' => '2hr : 15m',
                        'movieYear' => 'January 2025',
                        'calenderIcon' => true,
                        'buttonUrl' => route('frontend.movie_detail'),
                        'movieText' => __('streamMovies.rabbit_desc'),
                    ])
                    @include('frontend::components.cards.movie-slider', [
                        'movieCard' => 'movie-banner-2',
                        'imagePath' => 'media/chosfies.webp',
                        'movieRating' => true,
                        'movieTitle' => __('streamMovies.chosfies'),
                        'movieRange' => '3.5',
                        'movieCate' => 'NC-17',
                        'movieTime' => '2hr : 06m',
                        'movieYear' => 'May 2025',
                        'calenderIcon' => true,
                        'buttonUrl' => route('frontend.movie_detail'),
                        'movieText' => __('streamMovies.chosfies_desc'),
                    ])
                    @include('frontend::components.cards.movie-slider', [
                        'movieCard' => 'movie-banner-3',
                        'imagePath' => 'media/arrival.webp',
                        'movieRating' => true,
                        'movieTitle' => __('streamMovies.arrival'),
                        'movieRange' => '4',
                        'movieCate' => 'PG',
                        'movieTime' => '2hr : 15m',
                        'movieYear' => 'May 2025',
                        'calenderIcon' => true,
                        'buttonUrl' => route('frontend.movie_detail'),
                        'movieText' => __('streamMovies.arrival_desc'),
                    ])
                </div>
                <div class="swiper-banner-button-next d-none d-lg-block">
                    <i class="ph ph-caret-right arrow-icon"></i>
                </div>
                <div class="swiper-banner-button-prev d-none d-lg-block">
                    <i class="ph ph-caret-left icli  arrow-icon"></i>
                </div>
                <div class="swiper-pagination d-block d-lg-none"></div>
            </div>
        </div>
    </section>

    <div class="container-fluid">
        <div class="overflow-hidden">
            @include('frontend::components.sections.top-ten-block')
            @include('frontend::components.sections.Popular-movies')
            @include('frontend::components.sections.upcomming')
            @include('frontend::components.sections.specials-latest-movies')
            @include('frontend::components.sections.recommended', [
                'recommended' => __('frontendform.movies_recommended'),
            ])
        </div>
    </div>

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}
@endsection
