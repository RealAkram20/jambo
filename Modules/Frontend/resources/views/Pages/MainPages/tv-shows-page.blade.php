@extends('frontend::layouts.master', ['isSwiperSlider' => true, 'isFslightbox' => true, 'bodyClass' => 'custom-header-relative'])

@section('content')
    <section class="banner-container ">
        <div class="movie-banner">
            <div class="swiper swiper-banner-container iq-rtl-direction" data-swiper="banner-detail-slider">
                <div class="swiper-wrapper">
                    @include('frontend::components.cards.movie-slider', [
                        'movieCard' => 'tv-show-1',
                        'imagePath' => 'media/vikings.webp',
                        'movieTitle' => __('streamMovies.vikings'),
                        'movieRating' => true,
                        'movieRange' => '3.5',
                        'NoOfSeasons' => '2',
                        'movieYear' => 'Feb 2018',
                        'calenderIcon' => true,
                        'buttonUrl' => route('frontend.tvshow_detail'),
                        'movieText' => __('streamMovies.vikings_desc'),
                    ])
                    @include('frontend::components.cards.movie-slider', [
                        'movieCard' => 'tv-show-2',
                        'imagePath' => 'media/minions.webp',
                        'movieTitle' => __('streamMovies.minions'),
                        'movieRating' => true,
                        'movieRange' => '3.5',
                        'NoOfSeasons' => '4',
                        'movieYear' => 'November 2017',
                        'calenderIcon' => true,
                        'buttonUrl' => route('frontend.tvshow_detail'),
                        'movieText' => __('streamMovies.minions_desc'),
                    ])
                    @include('frontend::components.cards.movie-slider', [
                        'movieCard' => 'tv-show-3',
                        'imagePath' => 'media/breaking-bad.webp',
                        'movieTitle' => __('streamMovies.breaking_bad'),
                        'movieRating' => true,
                        'movieRange' => '3.5',
                        'NoOfSeasons' => '4',
                        'movieYear' => 'December 2018',
                        'calenderIcon' => true,
                        'buttonUrl' => route('frontend.tvshow_detail'),
                        'movieText' => __('streamMovies.breaking_bad_desc'),
                    ])
                </div>
                <div class="swiper-pagination d-block d-lg-none"></div>

                <div class="swiper-banner-button-next d-none d-lg-block">
                    <i class="ph ph-caret-right arrow-icon"></i>
                </div>
                <div class="swiper-banner-button-prev d-none d-lg-block">
                    <i class="ph ph-caret-left icli  arrow-icon"></i>
                </div>
            </div>
        </div>
    </section>

    <div class="container-fluid">
        <div class="overflow-hidden">
            @include('frontend::components.sections.top-ten-tvshow')
            @include('frontend::components.sections.popular-show', ['viewAllBtn' => true])
            @include('frontend::components.sections.upcomming', ['tvshowUpcomming' => true, 'viewAllBtn' => true])
            @include('frontend::components.sections.best-of-international-shows', ['viewAllBtn' => true])
            @include('frontend::components.sections.shows-we-recommend', ['viewAllBtn' => true])
        </div>
    </div>

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}
@endsection
