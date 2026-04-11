@extends('frontend::layouts.master', ['isSwiperSlider' => true, 'isFslightbox' => true, 'bodyClass' => 'custom-header-relative'])

@section('content')
    <section class="banner-container">
        <div class="movie-banner">
            <div class="swiper swiper-banner-container iq-rtl-direction" data-swiper="banner-detail-slider"
                data-pagination="true" data-autoplay="true">
                <div class="swiper-wrapper">
                    @include('frontend::components.cards.movie-slider', [
                        'movieCard' => 'video-1',
                        'imagePath' => 'media/toddler.webp',
                        'movieTitle' => __('streamMovies.toddler'),
                        'movieCate' => '782',
                        'movieCateIcon' => true,
                        'movieTime' => '50m',
                        'movieYear' => 'January 2025',
                        'buttonUrl' => route('frontend.video_detail'),
                        'movieText' => __('streamMovies.toddler_desc'),
                    ])
                    @include('frontend::components.cards.movie-slider', [
                        'movieCard' => 'video-2',
                        'imagePath' => 'media/the-crew.webp',
                        'movieTitle' => __('streamMovies.the_crew'),
                        'movieCate' => '178',
                        'movieCateIcon' => true,
                        'movieTime' => '1hr : 15m',
                        'movieYear' => 'Nov 2017',
                        'buttonUrl' => route('frontend.video_detail'),
                        'movieText' => __('streamMovies.the_crew_desc'),
                    ])
                     @include('frontend::components.cards.movie-slider', [
                        'movieCard' => 'video-3',
                        'imagePath' => 'media/red.webp',
                        'movieTitle' => __('streamMovies.red'),
                        'movieCate' => '42',
                        'movieCateIcon' => true,
                        'movieTime' => '45m',
                        'movieYear' => 'Dec 2018',
                        'buttonUrl' => route('frontend.video_detail'),
                        'movieText' => __('streamMovies.red_desc'),
                    ])
                    @include('frontend::components.cards.movie-slider', [
                        'movieCard' => 'video-4',
                        'imagePath' => 'media/kung-fu-panda.webp',
                        'movieTitle' => __('streamMovies.kung_fu_panda'),
                        'movieCate' => '42',
                        'movieCateIcon' => true,
                        'movieTime' => '45m',
                        'movieYear' => 'Dec 2018',
                        'buttonUrl' => route('frontend.video_detail'),
                        'movieText' => __('streamMovies.kung_fu_panda_desc'),
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
            @include('frontend::components.sections.top-ten-video')
            @include('frontend::components.sections.popular-videos', ['viewAllBtn' => true])
            @include('frontend::components.sections.upcomming', ['videoUpcoming' => true, 'viewAllBtn' => true])
            @include('frontend::components.sections.specials-latest-videol', ['viewAllBtn' => true])
            @include('frontend::components.sections.videos-recommended', ['viewAllBtn' => true])
        </div>
    </div>

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}
@endsection
