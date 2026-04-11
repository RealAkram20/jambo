@extends('frontend::layouts.master', ['isSwiperSlider' => true, 'isFslightbox' => true, 'bodyClass' => 'custom-header-relative', 'isSelect2' => true])

@section('content')
<section>
    <div class="overflow-hidden">
        <div class="iq-main-slider p-0 swiper banner-home-swiper" data-swiper="home-banner-slider"
            data-pagination="true" data-loop="true">
            <div class="slider m-0 p-0 swiper-wrapper home-slider">
                <div class="swiper-slide slide s-bg-1 p-0">
                    <div class="banner-home-swiper-image"
                        style="background-image: url('{{ asset('frontend/images/media/krishna.webp') }}');">

                        <div class="container-fluid position-relative">

                            <div class="row align-items-center iq-ltr-direction h-100 slider-content-full-height">
                                <div class="col-lg-6 col-md-12 col-xl-5">
                                    <h2
                                        class="texture-text big-font letter-spacing-1 line-count-1 text-capitalize RightAnimate">
                                        {{ __('streamMovies.krishna') }}
                                    </h2>
                                    <div class="d-flex flex-wrap align-items-center gap-3 r-mb-23 RightAnimate-two">
                                        <div class="slider-ratting d-flex align-items-center">
                                            <ul
                                                class="ratting-start p-0 m-0 list-inline text-warning d-flex align-items-center justify-content-left">
                                                <li>
                                                    <i class="ph-fill ph-star" aria-hidden="true"></i>
                                                </li>
                                                <li>
                                                    <i class="ph-fill ph-star" aria-hidden="true"></i>
                                                </li>
                                                <li>
                                                    <i class="ph-fill ph-star" aria-hidden="true"></i>
                                                </li>
                                                <li>
                                                    <i class="ph-fill ph-star" aria-hidden="true"></i>
                                                </li>
                                                <li>
                                                    <i class="ph-fill ph-star-half" aria-hidden="true"></i>
                                                </li>
                                            </ul>
                                        </div>
                                        <span class="d-flex align-items-center gap-1">
                                            <span class="">8</span><img
                                                src="{{ asset('frontend/images/pages/imdb-logo.svg') }}"
                                                alt="imdb logo" class="img-fluid imdb-img">
                                        </span>
                                        <span class="badge rounded-2 text-white bg-secondary font-size-12">NC-17</span>
                                        <div class="d-flex align-items-center gap-1">
                                            <i class="ph ph-clock"></i>
                                            <span class="time">2h : 45m</span>
                                        </div>
                                    </div>
                                    <p class="line-count-3 my-3 RightAnimate-two"> {{ __('streamMovies.krishna_desc') }}
                                    </p>
                                    <div class="trending-list RightAnimate-three">
                                        <div class="text-primary genres font-size-14 mb-1">
                                            {{ __('favouritePersonalities.starring') }}:
                                            <a href="{{ route('frontend.view_all') }}" class="fw-normal text-decoration-none text-body">{{ __('favouritePersonalities.Olivia_Foster') }},</a>
                                            <a href="{{ route('frontend.view_all') }}" class="fw-normal text-decoration-none text-body">{{ __('favouritePersonalities.Leena_Burton') }},</a>
                                            <a href="{{ route('frontend.view_all') }}" class="fw-normal text-decoration-none text-body">{{ __('favouritePersonalities.Ryan_Pierce') }}</a>
                                        </div>
                                        <div class="text-primary genres font-size-14 mb-1"> {{ __('streamTag.genre') }}:
                                            <a href="{{ route('frontend.view_all') }}" class="fw-normal text-decoration-none text-body">{{ __('streamTag.action') }},</a>
                                            <a href="{{ route('frontend.view_all') }}" class="fw-normal text-decoration-none text-body">{{ __('streamTag.animation') }},</a>
                                            <a href="{{ route('frontend.view_all') }}" class="fw-normal text-decoration-none text-body">{{ __('streamTag.mystery') }}</a>
                                        </div>
                                        <div class="text-primary tag font-size-14"> {{ __('streamTag.tags') }}:
                                            <a href="{{ route('frontend.view_all') }}" class="fw-normal text-decoration-none text-body">{{ __('streamTag.action') }},</a>
                                            <a href="{{ route('frontend.view_all') }}" class="fw-normal text-decoration-none text-body">{{ __('streamTag.adventure') }},</a>
                                            <a href="{{ route('frontend.view_all') }}" class="fw-normal text-decoration-none text-body">{{ __('streamTag.horror') }}</a>
                                        </div>
                                    </div>
                                    <div class="RightAnimate-four">
                                        @include('frontend::components.widgets.custom-button', [
                                        'buttonUrl' => route('frontend.movie_detail'),
                                        'buttonTitle' => __('streamButtons.play_now'),
                                        ])
                                    </div>
                                </div>
                                <div class="col-xl-7 col-lg-6 col-md-12 trailor-video iq-slider d-none d-lg-block">
                                    <a data-fslightbox="html5-video"
                                        href="https://www.youtube.com/embed/_PhvjbegfBI?autoplay=1&mute=1&loop=1&playlist=_PhvjbegfBI"
                                        class="video-open playbtn text-decoration-none" tabindex="0">
                                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg"
                                            xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="80px"
                                            height="80px" viewBox="0 0 213.7 213.7"
                                            enable-background="new 0 0 213.7 213.7" xml:space="preserve">
                                            <polygon class="triangle" fill="none" stroke-width="7"
                                                stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10"
                                                points="73.5,62.5 148.5,105.8 73.5,149.1 ">
                                            </polygon>
                                            <circle class="circle" fill="none" stroke-width="7"
                                                stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10"
                                                cx="106.8" cy="106.8" r="103.3">
                                            </circle>
                                        </svg>
                                        <span class="w-trailor text-uppercase"> {{ __('streamButtons.watch_trailer') }}
                                        </span>
                                    </a>
                                    <video id="my-video" class="my-video video-js vjs-big-play-centered w-100 d-none"
                                        loop autoplay muted preload="auto"
                                        data-setup='{
                                                "techOrder": ["youtube"],
                                                "sources": [{
                                                    "type": "video/youtube",
                                                    "src": "https://www.youtube.com/embed/_PhvjbegfBI?autoplay=1&mute=1&loop=1&playlist=_PhvjbegfBI"
                                                }],
                                                "youtube": {
                                                    "modestbranding": 1,
                                                    "rel": 0,
                                                    "showinfo": 0,
                                                    "autoplay": 1
                                                },
                                                "fullscreen": true
                                                }'>
                                    </video>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                <div class="swiper-slide slide s-bg-1 p-0">
                    <div class="banner-home-swiper-image"
                        style="background-image: url('{{ asset('frontend/images/media/breaking-bad.webp') }}');">

                        <div class="container-fluid position-relative">

                            <div class="row align-items-center iq-ltr-direction h-100 slider-content-full-height">
                                <div class="col-lg-6 col-md-12 col-xl-5">
                                    <h2
                                        class="texture-text big-font letter-spacing-1 line-count-1 text-capitalize RightAnimate">
                                        {{ __('streamMovies.breaking_bad') }}
                                    </h2>
                                    <div class="d-flex flex-wrap align-items-center gap-3 r-mb-23 RightAnimate-two">
                                        <div class="slider-ratting d-flex align-items-center">
                                            <ul
                                                class="ratting-start p-0 m-0 list-inline text-warning d-flex align-items-center justify-content-left">
                                                <li>
                                                    <i class="ph-fill ph-star" aria-hidden="true"></i>
                                                </li>
                                                <li>
                                                    <i class="ph-fill ph-star" aria-hidden="true"></i>
                                                </li>
                                                <li>
                                                    <i class="ph-fill ph-star" aria-hidden="true"></i>
                                                </li>
                                                <li>
                                                    <i class="ph-fill ph-star" aria-hidden="true"></i>
                                                </li>
                                                <li>
                                                    <i class="ph-fill ph-star-half" aria-hidden="true"></i>
                                                </li>
                                            </ul>
                                        </div>
                                        <span class="d-flex align-items-center gap-1">
                                            <span class="">8</span><img
                                                src="{{ asset('frontend/images/pages/imdb-logo.svg') }}"
                                                alt="imdb logo" class="img-fluid imdb-img">
                                        </span>
                                        <span class="badge rounded-2 text-white bg-secondary font-size-12">NC-17</span>
                                        <div class="d-flex align-items-center gap-1">
                                            <i class="ph ph-clock"></i>
                                            <span class="time">2 : 45m</span>
                                        </div>
                                    </div>
                                    <p class="line-count-3 my-3 RightAnimate-two"> {{ __('streamMovies.breaking_bad_desc') }}
                                    </p>
                                    <div class="trending-list RightAnimate-three">
                                        <div class="text-primary genres font-size-14 mb-1">
                                            {{ __('favouritePersonalities.starring') }}:
                                            <a href="{{ route('frontend.view_all') }}" class="fw-normal text-decoration-none text-body">{{ __('favouritePersonalities.Leena_Burton') }},</a>
                                            <a href="{{ route('frontend.view_all') }}" class="fw-normal text-decoration-none text-body">{{ __('favouritePersonalities.michael_fox') }},</a>
                                            <a href="{{ route('frontend.view_all') }}" class="fw-normal text-decoration-none text-body">{{ __('favouritePersonalities.maxwell_carter') }}</a>
                                        </div>
                                        <div class="text-primary genres font-size-14 mb-1"> {{ __('streamTag.genre') }}:
                                            <a href="{{ route('frontend.view_all') }}" class="fw-normal text-decoration-none text-body">{{ __('streamTag.action') }},</a>
                                            <a href="{{ route('frontend.view_all') }}" class="fw-normal text-decoration-none text-body">{{ __('streamTag.animation') }},</a>
                                            <a href="{{ route('frontend.view_all') }}" class="fw-normal text-decoration-none text-body">{{ __('streamTag.crime') }}</a>
                                        </div>
                                        <div class="text-primary tag font-size-14"> {{ __('streamTag.tags') }}:
                                            <a href="{{ route('frontend.view_all') }}" class="fw-normal text-decoration-none text-body">{{ __('streamTag.brother') }},</a>
                                            <a href="{{ route('frontend.view_all') }}" class="fw-normal text-decoration-none text-body">{{ __('streamTag.cricket') }},</a>
                                            <a href="{{ route('frontend.view_all') }}" class="fw-normal text-decoration-none text-body">{{ __('streamTag.detective') }}</a>
                                        </div>
                                    </div>
                                    <div class="RightAnimate-four">
                                        @include('frontend::components.widgets.custom-button', [
                                        'buttonUrl' => route('frontend.movie_detail'),
                                        'buttonTitle' => __('streamButtons.play_now'),
                                        ])
                                    </div>
                                </div>
                                <div class="col-xl-7 col-lg-6 col-md-12 trailor-video iq-slider d-none d-lg-block">
                                    <a data-fslightbox="html5-video"
                                        href="https://www.youtube.com/embed/_PhvjbegfBI?autoplay=1&mute=1&loop=1&playlist=_PhvjbegfBI"
                                        class="video-open playbtn text-decoration-none" tabindex="0">
                                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg"
                                            xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="80px"
                                            height="80px" viewBox="0 0 213.7 213.7"
                                            enable-background="new 0 0 213.7 213.7" xml:space="preserve">
                                            <polygon class="triangle" fill="none" stroke-width="7"
                                                stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10"
                                                points="73.5,62.5 148.5,105.8 73.5,149.1 ">
                                            </polygon>
                                            <circle class="circle" fill="none" stroke-width="7"
                                                stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10"
                                                cx="106.8" cy="106.8" r="103.3">
                                            </circle>
                                        </svg>
                                        <span class="w-trailor text-uppercase"> {{ __('streamButtons.watch_trailer') }}
                                        </span>
                                    </a>
                                    <video id="my-video" class="my-video video-js vjs-big-play-centered w-100 d-none"
                                        loop autoplay muted preload="auto"
                                        data-setup='{
                                                "techOrder": ["youtube"],
                                                "sources": [{
                                                    "type": "video/youtube",
                                                    "src": "https://www.youtube.com/embed/_PhvjbegfBI?autoplay=1&mute=1&loop=1&playlist=_PhvjbegfBI"
                                                }],
                                                "youtube": {
                                                    "modestbranding": 1,
                                                    "rel": 0,
                                                    "showinfo": 0,
                                                    "autoplay": 1
                                                },
                                                "fullscreen": true
                                                }'>
                                    </video>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                <div class="swiper-slide slide s-bg-1 p-0">
                    <div class="banner-home-swiper-image"
                        style="background-image: url('{{ asset('frontend/images/media/charlie-chaplin.webp') }}');">

                        <div class="container-fluid position-relative">

                            <div class="row align-items-center iq-ltr-direction h-100 slider-content-full-height">
                                <div class="col-lg-6 col-md-12 col-xl-5">
                                    <h2
                                        class="texture-text big-font letter-spacing-1 line-count-1 text-capitalize RightAnimate">
                                        {{ __('streamMovies.charliechaplin') }}
                                    </h2>
                                    <div class="d-flex flex-wrap align-items-center gap-3 r-mb-23 RightAnimate-two">
                                        <div class="slider-ratting d-flex align-items-center">
                                            <ul
                                                class="ratting-start p-0 m-0 list-inline text-warning d-flex align-items-center justify-content-left">
                                                <li>
                                                    <i class="ph-fill ph-star" aria-hidden="true"></i>
                                                </li>
                                                <li>
                                                    <i class="ph-fill ph-star" aria-hidden="true"></i>
                                                </li>
                                                <li>
                                                    <i class="ph-fill ph-star" aria-hidden="true"></i>
                                                </li>
                                                <li>
                                                    <i class="ph-fill ph-star" aria-hidden="true"></i>
                                                </li>
                                                <li>
                                                    <i class="ph-fill ph-star-half" aria-hidden="true"></i>
                                                </li>
                                            </ul>
                                        </div>
                                        <span class="d-flex align-items-center gap-1">
                                            <span class="">247</span><img
                                                src="{{ asset('frontend/images/pages/imdb-logo.svg') }}"
                                                alt="imdb logo" class="img-fluid imdb-img">
                                        </span>
                                        <span class="badge rounded-2 text-white bg-secondary font-size-12">80</span>
                                    </div>
                                    <p class="line-count-3 my-3 RightAnimate-two"> {{ __('streamMovies.charliechaplin_desc') }}
                                    </p>
                                    <div class="trending-list RightAnimate-three">
                                        <div class="text-primary genres font-size-14 mb-1"> {{ __('streamTag.genre') }}:
                                            <a href="{{ route('frontend.view_all') }}" class="fw-normal text-decoration-none text-body">{{ __('streamTag.action') }}</a>
                                        </div>
                                        <div class="text-primary tag font-size-14"> {{ __('streamTag.tags') }}:
                                            <a href="{{ route('frontend.view_all') }}" class="fw-normal text-decoration-none text-body">{{ __('streamTag.action') }},</a>
                                            <a href="{{ route('frontend.view_all') }}" class="fw-normal text-decoration-none text-body">{{ __('streamTag.adventure') }},</a>
                                            <a href="{{ route('frontend.view_all') }}" class="fw-normal text-decoration-none text-body">{{ __('streamTag.horror') }}</a>
                                        </div>
                                    </div>
                                    <div class="RightAnimate-four">
                                        @include('frontend::components.widgets.custom-button', [
                                        'buttonUrl' => route('frontend.movie_detail'),
                                        'buttonTitle' => __('streamButtons.play_now'),
                                        ])
                                    </div>
                                </div>
                                <div class="col-xl-7 col-lg-6 col-md-12 trailor-video iq-slider d-none d-lg-block">
                                    <a data-fslightbox="html5-video"
                                        href="https://www.youtube.com/embed/_PhvjbegfBI?autoplay=1&mute=1&loop=1&playlist=_PhvjbegfBI"
                                        class="video-open playbtn text-decoration-none" tabindex="0">
                                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg"
                                            xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="80px"
                                            height="80px" viewBox="0 0 213.7 213.7"
                                            enable-background="new 0 0 213.7 213.7" xml:space="preserve">
                                            <polygon class="triangle" fill="none" stroke-width="7"
                                                stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10"
                                                points="73.5,62.5 148.5,105.8 73.5,149.1 ">
                                            </polygon>
                                            <circle class="circle" fill="none" stroke-width="7"
                                                stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10"
                                                cx="106.8" cy="106.8" r="103.3">
                                            </circle>
                                        </svg>
                                        <span class="w-trailor text-uppercase"> {{ __('streamButtons.watch_trailer') }}
                                        </span>
                                    </a>
                                    <video id="my-video" class="my-video video-js vjs-big-play-centered w-100 d-none"
                                        loop autoplay muted preload="auto"
                                        data-setup='{
                                                "techOrder": ["youtube"],
                                                "sources": [{
                                                    "type": "video/youtube",
                                                    "src": "https://www.youtube.com/embed/_PhvjbegfBI?autoplay=1&mute=1&loop=1&playlist=_PhvjbegfBI"
                                                }],
                                                "youtube": {
                                                    "modestbranding": 1,
                                                    "rel": 0,
                                                    "showinfo": 0,
                                                    "autoplay": 1
                                                },
                                                "fullscreen": true
                                                }'>
                                    </video>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <button class="PreArrow-two swiper-arrows d-flex align-items-center justify-content-center d-xl-block d-none"
                id="home-banner-slider-prev"><i class="ph ph-caret-left"></i></button>
            <button class="NextArrow-two swiper-arrows d-flex align-items-center justify-content-center d-xl-block d-none"
                id="home-banner-slider-next"><i class="ph ph-caret-right"></i></button>
            <div class="swiper-pagination d-block d-lg-none"></div>
        </div>
    </div>
</section>

<div class="container-fluid">
    <div class="overflow-hidden">
        @include('frontend::components.sections.continue-watching', ['value' => '4', 'sectionPaddingClass' => true])

        @include('frontend::components.sections.upcomming')

        @include('frontend::components.sections.best-in-tv')

        @include('frontend::components.sections.latest-movies')
    </div>
</div>

@include('frontend::components.sections.verticle-slider')

<div class="container-fluid">
    <div class="overflow-hidden">
        @include('frontend::components.sections.suggested')
    </div>
</div>

@include('frontend::components.sections.parallax')

@include('frontend::components.sections.tranding-tab')

<div class="container-fluid">
    <div class="overflow-hidden">
        @include('frontend::components.sections.recommended', [
        'recommended' => __('sectionTitle.recommended_tv_show'), 'viewAllBtn' => true,
        ])
    </div>
</div>

{{-- Mobile Footer --}}
@include('frontend::components.widgets.mobile-footer')
{{-- Mobile Footer End --}}
@endsection