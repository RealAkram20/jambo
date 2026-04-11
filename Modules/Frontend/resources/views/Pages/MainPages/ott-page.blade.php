@extends('frontend::layouts.master', ['isSwiperSlider' => true, 'IS_MEGA' => true])

@section('content')
<div class="iq-banner-thumb-slider overflow-hidden">
    <div class="slider">
        <div class="position-relative slider-bg my-auto">
            {{-- Horizontal Banner start --}}
            <div class="horizontal_thumb_slider" data-swiper="slider-thumbs-ott">
                <div class="banner-thumb-slider-nav">
                    <div class="swiper-container " data-swiper="slider-thumbs-inner-ott">
                        <div class="swiper-wrapper">
                            <div class="swiper-slide swiper-bg">
                                <div class="block-images position-relative ">
                                    <div class="img-box">
                                        <img src="{{ asset('frontend/images/media/gameofhero-portrait.webp') }}" class="img-fluid"
                                            alt="img" loading="lazy">
                                        <div class="block-description">
                                            <h6 class="iq-title fw-500 line-count-1">{{ __('streamMovies.game_of_heros') }}
                                            </h6>
                                            <div class="d-flex align-items-center gap-1">
                                                <i class="ph ph-clock"></i>
                                                <span class="fs-12">2hr : 30m</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="swiper-slide swiper-bg">
                                <div class="block-images position-relative ">
                                    <div class="img-box">
                                        <img src="{{ asset('frontend/images//media/fast-furious-portrait.webp') }}"
                                            class="img-fluid" alt="img" loading="lazy">
                                        <div class="block-description">
                                            <h6 class="iq-title fw-500 line-count-1">{{ __('streamMovies.fast_&_furious') }}
                                            </h6>
                                            <div class="d-flex align-items-center gap-1">
                                                <i class="ph ph-clock"></i>
                                                <span class="fs-12">2hr : 59m</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="swiper-slide swiper-bg">
                                <div class="block-images position-relative ">
                                    <div class="img-box">
                                        <img src="{{ asset('frontend/images/media/minions-portrait.webp') }}"
                                            class="img-fluid" alt="img" loading="lazy">
                                        <div class="block-description">
                                            <h6 class="iq-title fw-500 line-count-1">{{ __('streamMovies.minions') }}</h6>
                                            <div class="d-flex align-items-center gap-1">
                                                <i class="ph ph-clock"></i>
                                                <span class="fs-12">4 {{__('streamEpisode.season')}}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="swiper-slide swiper-bg">
                                <div class="block-images position-relative ">
                                    <div class="img-box">
                                        <img src="{{ asset('frontend/images/media/vikings-portrait.webp') }}"
                                            class="img-fluid" alt="img" loading="lazy">
                                        <div class="block-description">
                                            <h6 class="iq-title fw-500 line-count-1">{{ __('streamMovies.vikings') }}</h6>
                                            <div class="d-flex align-items-center gap-1">
                                                <i class="ph ph-clock"></i>
                                                <span class="fs-12">2 {{__('streamEpisode.season')}}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="swiper-slide swiper-bg">
                                <div class="block-images position-relative ">
                                    <div class="img-box">
                                        <img src="{{ asset('frontend/images/media/toddler-portrait.webp') }}"
                                            class="img-fluid" alt="img" loading="lazy">
                                        <div class="block-description">
                                            <h6 class="iq-title fw-500 line-count-1">{{ __('streamMovies.toddler') }}</h6>
                                            <div class="d-flex align-items-center gap-1">
                                                <i class="ph ph-clock"></i>
                                                <span class="fs-12">4 {{__('streamEpisode.season')}}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="swiper-slide swiper-bg">
                                <div class="block-images position-relative ">
                                    <div class="img-box">
                                        <img src="{{ asset('frontend/images/media/kung-fu-panda-portrait.webp') }}" class="img-fluid" alt="img" loading="lazy">
                                        <div class="block-description">
                                            <h6 class="iq-title fw-500 line-count-1">{{ __('streamMovies.kung_fu_panda') }}</h6>
                                            <div class="d-flex align-items-center gap-1">
                                                <i class="ph ph-clock"></i>
                                                <span class="fs-12">4 {{__('streamEpisode.season')}}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="slider-prev swiper-button d-flex align-items-center justify-content-center">
                        <i class="iconly-Arrow-Left-2 icli"></i>
                    </div>
                    <div class="slider-next swiper-button d-flex align-items-center justify-content-center">
                        <i class="iconly-Arrow-Right-2 icli"></i>
                    </div>
                </div>
            </div>
            {{-- Horizontal Banner end --}}
            {{-- Bg Banner start --}}
            <div class="slider-images" data-swiper="slider-images-ott">
                <div class="swiper-container" data-swiper="slider-images-inner-ott">
                    <div class="swiper-wrapper m-0">
                        <div class="swiper-slide banner-bg p-0">
                            <div class="slider--image block-images"
                                style="background-image: url('{{ asset('frontend/images/media/gameofhero.webp') }}');">
                                <div class="container-fluid position-relative">
                                    <div class="row align-items-center h-100 slider-content-full-height">
                                        <div class="col-lg-5 col-md-12">
                                            <div class="slider-content">
                                                <h2
                                                    class="texture-text big-font letter-spacing-1 line-count-1 RightAnimate-two mb-1 mb-md-3">
                                                    {{ __('streamMovies.game_of_heros') }}
                                                </h2>
                                                <div
                                                    class="d-flex flex-wrap align-items-center gap-3 py-2 RightAnimate-three">
                                                    <span
                                                        class="badge rounded-0 text-white text-uppercase bg-secondary mr-3 fw-bold">NC-17</span>
                                                    <div class="d-flex align-items-center gap-3">
                                                        <ul
                                                            class="ratting-start p-0 m-0 list-inline text-warning d-flex align-items-center justify-content-left gap-1">
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
                                                                <i class="ph-fill ph-star" aria-hidden="true"></i>
                                                            </li>
                                                        </ul>
                                                        <span>
                                                            <img src="{{ asset('frontend/images/pages/imdb-logo.svg') }}"
                                                                alt="imdb logo" class="img-fluid imdb-img">
                                                        </span>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-1">
                                                        <i class="ph ph-clock"></i>
                                                        <span class="font-size-16 fw-500">2hr : 30m</span>
                                                    </div>
                                                </div>
                                                <p class="line-count-3 my-3 RightAnimate-two">
                                                    {{ __('streamMovies.game_of_heros_desc') }}
                                                </p>

                                                <div class="RightAnimate-three mt-2">
                                                    <div class="text-primary font-size-14 text-capitalize mb-1">
                                                        {{ __('streamTag.tags') }}:
                                                        <a href="{{ route('frontend.view_all') }}"
                                                            class="text-body text-decoration-none fw-normal">{{__('streamTag.action')}},</a>
                                                        <a href="{{ route('frontend.view_all') }}"
                                                            class="text-body text-decoration-none fw-normal">{{__('streamTag.adventure')}},</a>
                                                        <a href="{{ route('frontend.view_all') }}"
                                                            class="text-body text-decoration-none fw-normal">{{__('streamTag.drama')}}</a>
                                                    </div>
                                                    <div class="text-primary font-size-14 text-capitalize mb-1">
                                                        {{ __('streamTag.genre') }}:
                                                        <a href="{{ route('frontend.view_all') }}"
                                                            class="text-body text-decoration-none fw-normal">{{__('streamTag.action')}},</a>
                                                        <a href="{{ route('frontend.view_all') }}"
                                                            class="text-body text-decoration-none fw-normal">{{__('streamTag.adventure')}},</a>
                                                        <a href="{{ route('frontend.view_all') }}"
                                                            class="text-body text-decoration-none fw-normal">{{__('streamTag.crime')}}</a>
                                                    </div>
                                                    <div class="text-primary font-size-14 text-capitalize">
                                                        {{ __('streamTag.starrting') }}:
                                                        <a href="{{ route('frontend.cast_details') }}" class="text-body text-decoration-none fw-normal">{{__('favouritePersonalities.olivia_foster')}},</a>
                                                        <a href="{{ route('frontend.cast_details') }}" class="text-body text-decoration-none fw-normal">{{__('favouritePersonalities.Leena_Burton')}},</a>
                                                        <a href="{{ route('frontend.cast_details') }}" class="text-body text-decoration-none fw-normal">{{__('favouritePersonalities.Ryan_Pierce')}}</a>
                                                    </div>
                                                </div>

                                                <div class="RightAnimate-four mt-4 pt-2">
                                                    @include(
                                                    'frontend::components.widgets.custom-button',
                                                    [
                                                    'buttonTitle' => __('streamButtons.play_now'),
                                                    'buttonUrl' => 'movie-detail',
                                                    ]
                                                    )

                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-7 col-md-12"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="swiper-slide banner-bg p-0">
                            <div class="slider--image block-images"
                                style="background-image: url('{{ asset('frontend/images/media/fast-furious.webp') }}');">
                                <div class="container-fluid position-relative">
                                    <div class="row align-items-center h-100 slider-content-full-height">
                                        <div class="col-lg-5 col-md-12">
                                            <div class="slider-content">
                                                <h2
                                                    class="texture-text big-font letter-spacing-1 line-count-1 RightAnimate-two mb-1 mb-md-3">
                                                    {{ __('streamMovies.fast_&_furious') }}
                                                </h2>
                                                <div
                                                    class="d-flex flex-wrap align-items-center gap-3 py-2 RightAnimate-three">
                                                    <span
                                                        class="badge rounded-0 text-white text-uppercase bg-secondary mr-3 fw-bold">NC-17</span>
                                                    <div class="d-flex align-items-center gap-3">
                                                        <ul
                                                            class="ratting-start p-0 m-0 list-inline text-warning d-flex align-items-center justify-content-left gap-1">
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
                                                                <i class="ph ph-star" aria-hidden="true"></i>
                                                            </li>
                                                        </ul>
                                                        <span>
                                                            <img src="{{ asset('frontend/images/pages/imdb-logo.svg') }}"
                                                                alt="imdb logo" class="img-fluid imdb-img">
                                                        </span>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-1">
                                                        <i class="ph ph-clock"></i>
                                                        <span class="font-size-16 fw-500">2hr :
                                                            14m</span>
                                                    </div>
                                                </div>
                                                <p class="line-count-3 my-3 RightAnimate-two">{{ __('streamMovies.fast_&_furious_desc') }}
                                                </p>
                                                <div class="RightAnimate-three mt-2">
                                                    <div class="text-primary font-size-14 fw-500 text-capitalize mb-1">
                                                        {{ __('streamTag.tags') }}:
                                                        <a href="{{ route('frontend.view_all') }}" class="text-body text-decoration-none fw-normal ">{{ __('streamTag.family') }},</a>
                                                        <a href="{{ route('frontend.view_all') }}" class="text-body text-decoration-none fw-normal ">{{ __('streamTag.hitman') }},</a>
                                                        <a href="{{ route('frontend.view_all') }}" class="text-body text-decoration-none fw-normal ">{{ __('streamTag.horror') }}</a>
                                                    </div>
                                                    <div class="text-primary font-size-14 fw-500 text-capitalize mb-1">
                                                        {{ __('streamTag.genre') }}:
                                                        <a href="{{ route('frontend.view_all') }}" class="text-body text-decoration-none fw-normal ms-1">{{ __('streamTag.action') }},</a>
                                                        <a href="{{ route('frontend.view_all') }}" class="text-body text-decoration-none fw-normal ms-1">{{ __('streamTag.adventure') }},</a>
                                                        <a href="{{ route('frontend.view_all') }}" class="text-body text-decoration-none fw-normal ms-1">{{ __('streamTag.crime') }}</a>
                                                    </div>
                                                    <div class="text-primary font-size-14 fw-500 text-capitalize">
                                                        {{ __('streamTag.starrting') }}:
                                                        <a href="{{ route('frontend.cast_details') }}" class="text-body text-decoration-none fw-normal  ms-1">{{__('favouritePersonalities.jordan_grant')}},</a>
                                                        <a href="{{ route('frontend.cast_details') }}" class="text-body text-decoration-none fw-normal  ms-1">{{__('favouritePersonalities.jeff_bridges')}},</a>
                                                        <a href="{{ route('frontend.cast_details') }}" class="text-body text-decoration-none fw-normal  ms-1">{{__('favouritePersonalities.james_stewart')}}</a>
                                                    </div>
                                                </div>
                                                <div class="RightAnimate-four mt-4 pt-2">

                                                    @include(
                                                    'frontend::components.widgets.custom-button',
                                                    [
                                                    'buttonTitle' => __('streamButtons.play_now'),
                                                    'buttonUrl' => 'movie-detail',
                                                    ]
                                                    )
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 col-md-12"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="swiper-slide banner-bg p-0">
                            <div class="slider--image block-images"
                                style="background-image: url('{{ asset('frontend/images/media/minions.webp') }}');">
                                <div class="container-fluid position-relative">
                                    <div class="row align-items-center h-100 slider-content-full-height">
                                        <div class="col-lg-5 col-md-12">
                                            <div class="slider-content">
                                                <h2
                                                    class="texture-text big-font letter-spacing-1 line-count-1 RightAnimate-two mb-1 mb-md-3">
                                                    {{ __('streamMovies.minions') }}
                                                </h2>
                                                <div
                                                    class="d-flex flex-wrap align-items-center gap-3 py-2 RightAnimate-three">
                                                    <span
                                                        class="badge rounded-0 text-white text-capitalize px-2 py-1 bg-secondary mr-3 fw-bold">
                                                        4 {{__('streamEpisode.season')}}</span>
                                                    <div class="d-flex align-items-center gap-3">
                                                        <ul
                                                            class="ratting-start p-0 m-0 list-inline text-warning d-flex align-items-center justify-content-left gap-1">
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
                                                                <i class="ph-fill ph-star-half"
                                                                    aria-hidden="true"></i>
                                                            </li>
                                                        </ul>                                                        
                                                        <span>
                                                            <img src="{{ asset('frontend/images/pages/imdb-logo.svg') }}"
                                                                alt="imdb logo" class="img-fluid imdb-img">
                                                        </span>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-1">
                                                        <i class="ph ph-clock"></i>
                                                        <span class="font-size-16 fw-500">{{ __('streamMonth.july') }} 2025</span>
                                                    </div>
                                                </div>
                                                <p class="line-count-3 my-3 RightAnimate-two">
                                                    {{ __('streamMovies.minions_desc') }}
                                                </p>
                                                <div class="RightAnimate-three mt-2">
                                                    <div class="text-primary font-size-14 fw-500 text-capitalize mb-1">
                                                        {{ __('streamTag.tags') }}:
                                                        <a href="{{ route('frontend.view_all') }}" class="text-body text-decoration-none fw-normal ms-1">{{__('streamTag.agents')}},</a>
                                                        <a href="{{ route('frontend.view_all') }}" class="text-body text-decoration-none fw-normal ms-1">{{ __('streamTag.brother') }},</a>
                                                        <a href="{{ route('frontend.view_all') }}" class="text-body text-decoration-none fw-normal ms-1">{{ __('streamTag.brother_relationship') }}</a>
                                                    </div>
                                                    <div class="text-primary font-size-14 fw-500 text-capitalize mb-1">
                                                        {{ __('streamTag.genre') }}:
                                                        <a href="{{ route('frontend.view_all') }}" class="text-body text-decoration-none fw-normal ms-1">{{__('streamTag.action')}},</a>
                                                        <a href="{{ route('frontend.view_all') }}" class="text-body text-decoration-none fw-normal ms-1">{{__('streamTag.adventure')}},</a>
                                                        <a href="{{ route('frontend.view_all') }}" class="text-body text-decoration-none fw-normal ms-1">{{__('streamTag.animation')}}</a>
                                                    </div>
                                                    <div class="text-primary font-size-14 fw-500 text-capitalize">
                                                        {{ __('streamTag.starrting') }}:
                                                        <a href="{{ route('frontend.cast_details') }}" class="text-body text-decoration-none fw-normal ms-1">{{__('favouritePersonalities.ava_monroe')}},</a>
                                                        <a href="{{ route('frontend.cast_details') }}" class="text-body text-decoration-none fw-normal ms-1">{{__('favouritePersonalities.charles_melton')}},</a>
                                                        <a href="{{ route('frontend.cast_details') }}" class="text-body text-decoration-none fw-normal  ms-1">{{__('favouritePersonalities.james_stewart')}}</a>
                                                    </div>
                                                </div>
                                                <div class="RightAnimate-four mt-4 pt-2">
                                                    @include(
                                                    'frontend::components.widgets.custom-button',
                                                    [
                                                    'buttonTitle' => __('streamButtons.play_now'),
                                                    'buttonUrl' => 'movie-detail',
                                                    ]
                                                    )
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-7 col-md-12"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="swiper-slide banner-bg p-0">
                            <div class="slider--image block-images"
                                style="background-image: url('{{ asset('frontend/images/media/vikings.webp') }}');">
                                <div class="container-fluid position-relative">
                                    <div class="row align-items-center h-100 slider-content-full-height">
                                        <div class="col-lg-5 col-md-12">
                                            <div class="slider-content">
                                                <h2
                                                    class="texture-text big-font letter-spacing-1 line-count-1 RightAnimate-two mb-1 mb-md-3">
                                                    {{ __('streamMovies.vikings') }}
                                                </h2>
                                                <div
                                                    class="d-flex flex-wrap align-items-center gap-3 py-2 RightAnimate-three">
                                                    <span
                                                        class="badge rounded-0 text-white text-capitalize px-2 py-1 bg-secondary mr-3 fw-bold">
                                                        4 {{__('streamEpisode.season')}}</span>
                                                    <div class="d-flex align-items-center gap-3">
                                                        <ul
                                                            class="ratting-start p-0 m-0 list-inline text-warning d-flex align-items-center justify-content-left gap-1">
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
                                                                <i class="ph-fill ph-star-half"
                                                                    aria-hidden="true"></i>
                                                            </li>
                                                        </ul>                                                        
                                                        <span>
                                                            <img src="{{ asset('frontend/images/pages/imdb-logo.svg') }}"
                                                                alt="imdb logo" class="img-fluid imdb-img">
                                                        </span>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-1">
                                                        <i class="ph ph-clock"></i>
                                                        <span class="font-size-16 fw-500">{{ __('streamMonth.january') }} 2025</span>
                                                    </div>
                                                </div>
                                                <p class="line-count-3 my-3 RightAnimate-two">
                                                    {{ __('streamMovies.vikings_desc') }}
                                                </p>
                                                <div class="RightAnimate-three mt-2">
                                                    <div class="text-primary font-size-14 fw-500 text-capitalize mb-1">
                                                        {{ __('streamTag.tags') }}:
                                                        <a href="{{ route('frontend.view_all') }}" class="text-body text-decoration-none fw-normal ms-1">{{__('streamTag.agents')}},</a>
                                                        <a href="{{ route('frontend.view_all') }}" class="text-body text-decoration-none fw-normal ms-1">{{ __('streamTag.brother') }},</a>
                                                        <a href="{{ route('frontend.view_all') }}" class="text-body text-decoration-none fw-normal ms-1">{{ __('streamTag.cricket') }}</a>
                                                    </div>
                                                    <div class="text-primary font-size-14 fw-500 text-capitalize mb-1">
                                                        {{ __('streamTag.genre') }}:
                                                        <a href="{{ route('frontend.view_all') }}" class="text-body text-decoration-none fw-normal ms-1">{{__('streamTag.action')}},</a>
                                                        <a href="{{ route('frontend.view_all') }}" class="text-body text-decoration-none fw-normal ms-1">{{__('streamTag.adventure')}},</a>
                                                        <a href="{{ route('frontend.view_all') }}" class="text-body text-decoration-none fw-normal ms-1">{{__('streamTag.animation')}}</a>
                                                    </div>
                                                    <div class="text-primary font-size-14 fw-500 text-capitalize">
                                                        {{ __('streamTag.starrting') }}:
                                                        <a href="{{ route('frontend.cast_details') }}" class="text-body text-decoration-none fw-normal ms-1">{{__('favouritePersonalities.olivia_foster')}},</a>
                                                        <a href="{{ route('frontend.cast_details') }}" class="text-body text-decoration-none fw-normal ms-1">{{__('favouritePersonalities.Leena_Burton')}},</a>
                                                        <a href="{{ route('frontend.cast_details') }}" class="text-body text-decoration-none fw-normal  ms-1">{{__('favouritePersonalities.Ryan_Pierce')}}</a>
                                                    </div>
                                                </div>
                                                <div class="RightAnimate-four mt-4 pt-2">
                                                    @include(
                                                    'frontend::components.widgets.custom-button',
                                                    [
                                                    'buttonTitle' => __('streamButtons.play_now'),
                                                    'buttonUrl' => 'movie-detail',
                                                    ]
                                                    )
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-7 col-md-12"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="swiper-slide banner-bg p-0">
                            <div class="slider--image block-images"
                                style="background-image: url('{{ asset('frontend/images/media/toddler.webp') }}');">
                                <div class="container-fluid position-relative">
                                    <div class="row align-items-center h-100 slider-content-full-height">
                                        <div class="col-lg-5 col-md-12">
                                            <div class="slider-content">
                                                <h2
                                                    class="texture-text big-font letter-spacing-1 line-count-1 RightAnimate-two mb-1 mb-md-3">
                                                    {{ __('streamMovies.toddler') }}
                                                </h2>
                                                <div
                                                    class="d-flex flex-wrap align-items-center gap-3 py-2 RightAnimate-three">
                                                    <span
                                                        class="badge rounded-0 text-white text-capitalize px-2 py-1 bg-secondary mr-3 fw-bold">
                                                        4 {{__('streamEpisode.season')}}</span>
                                                    <div class="d-flex align-items-center gap-3">
                                                        <ul
                                                            class="ratting-start p-0 m-0 list-inline text-warning d-flex align-items-center justify-content-left gap-1">
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
                                                                <i class="ph-fill ph-star-half"
                                                                    aria-hidden="true"></i>
                                                            </li>
                                                        </ul>                                                        
                                                        <span>
                                                            <img src="{{ asset('frontend/images/pages/imdb-logo.svg') }}"
                                                                alt="imdb logo" class="img-fluid imdb-img">
                                                        </span>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-1">
                                                        <i class="ph ph-clock"></i>
                                                        <span class="font-size-16 fw-500">50m</span>
                                                    </div>
                                                </div>
                                                <p class="line-count-3 my-3 RightAnimate-two">
                                                    {{ __('streamMovies.toddler_desc') }}
                                                </p>
                                                <div class="RightAnimate-three mt-2">
                                                    <div class="text-primary font-size-14 fw-500 text-capitalize mb-1">
                                                        {{ __('streamTag.tags') }}:
                                                        <a href="{{ route('frontend.view_all') }}" class="text-body text-decoration-none fw-normal ms-1">{{__('streamTag.action')}},</a>
                                                        <a href="{{ route('frontend.view_all') }}" class="text-body text-decoration-none fw-normal ms-1">{{ __('streamTag.adventure') }},</a>
                                                        <a href="{{ route('frontend.view_all') }}" class="text-body text-decoration-none fw-normal ms-1">{{ __('streamTag.astronomy') }}</a>
                                                    </div>
                                                    <div class="text-primary font-size-14 fw-500 text-capitalize mb-1">
                                                        {{ __('streamTag.genre') }}:
                                                        <a href="{{ route('frontend.view_all') }}" class="text-body text-decoration-none fw-normal ms-1">{{__('streamTag.action')}},</a>
                                                        <a href="{{ route('frontend.view_all') }}" class="text-body text-decoration-none fw-normal ms-1">{{__('streamTag.adventure')}},</a>
                                                        <a href="{{ route('frontend.view_all') }}" class="text-body text-decoration-none fw-normal ms-1">{{__('streamTag.crime')}}</a>
                                                    </div>
                                                </div>
                                                <div class="RightAnimate-four mt-4 pt-2">
                                                    @include(
                                                    'frontend::components.widgets.custom-button',
                                                    [
                                                    'buttonTitle' => __('streamButtons.play_now'),
                                                    'buttonUrl' => 'movie-detail',
                                                    ]
                                                    )
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-7 col-md-12"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="swiper-slide banner-bg p-0">
                            <div class="slider--image block-images"
                                style="background-image: url('{{ asset('frontend/images/media/minions.webp') }}');">
                                <div class="container-fluid position-relative">
                                    <div class="row align-items-center h-100 slider-content-full-height">
                                        <div class="col-lg-5 col-md-12">
                                            <div class="slider-content">
                                                <h2
                                                    class="texture-text big-font letter-spacing-1 line-count-1 RightAnimate-two mb-1 mb-md-3">
                                                    {{ __('streamMovies.kung_fu_panda') }}
                                                </h2>
                                                <div
                                                    class="d-flex flex-wrap align-items-center gap-3 py-2 RightAnimate-three">
                                                    <span
                                                        class="badge rounded-0 text-white text-capitalize px-2 py-1 bg-secondary mr-3 fw-bold">
                                                        4 {{__('streamEpisode.season')}}</span>
                                                    <div class="d-flex align-items-center gap-3">
                                                        <ul
                                                            class="ratting-start p-0 m-0 list-inline text-warning d-flex align-items-center justify-content-left gap-1">
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
                                                                <i class="ph-fill ph-star-half"
                                                                    aria-hidden="true"></i>
                                                            </li>
                                                        </ul>                                                        
                                                        <span>
                                                            <img src="{{ asset('frontend/images/pages/imdb-logo.svg') }}"
                                                                alt="imdb logo" class="img-fluid imdb-img">
                                                        </span>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-1">
                                                        <i class="ph ph-clock"></i>
                                                        <span class="font-size-16 fw-500">45m</span>
                                                    </div>
                                                </div>
                                                <p class="line-count-3 my-3 RightAnimate-two">
                                                    {{ __('streamMovies.kung_fu_panda_desc') }}
                                                </p>
                                                <div class="RightAnimate-three mt-2">
                                                    <div class="text-primary font-size-14 fw-500 text-capitalize mb-1">
                                                        {{ __('streamTag.tags') }}:
                                                        <a href="{{ route('frontend.view_all') }}" class="text-body text-decoration-none fw-normal ms-1">{{__('streamTag.action')}},</a>
                                                        <a href="{{ route('frontend.view_all') }}" class="text-body text-decoration-none fw-normal ms-1">{{ __('streamTag.adventure') }},</a>
                                                        <a href="{{ route('frontend.view_all') }}" class="text-body text-decoration-none fw-normal ms-1">{{ __('streamTag.astronomy') }}</a>
                                                    </div>
                                                    <div class="text-primary font-size-14 fw-500 text-capitalize mb-1">
                                                        {{ __('streamTag.genre') }}:
                                                        <a href="{{ route('frontend.view_all') }}" class="text-body text-decoration-none fw-normal ms-1">{{__('streamTag.action')}},</a>
                                                        <a href="{{ route('frontend.view_all') }}" class="text-body text-decoration-none fw-normal ms-1">{{__('streamTag.adventure')}},</a>
                                                        <a href="{{ route('frontend.view_all') }}" class="text-body text-decoration-none fw-normal ms-1">{{__('streamTag.crime')}}</a>
                                                    </div>
                                                </div>
                                                <div class="RightAnimate-four mt-4 pt-2">
                                                    @include(
                                                    'frontend::components.widgets.custom-button',
                                                    [
                                                    'buttonTitle' => __('streamButtons.play_now'),
                                                    'buttonUrl' => 'movie-detail',
                                                    ]
                                                    )
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-7 col-md-12"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="swiper-pagination d-block d-lg-none"></div>
                </div>
            </div>
            {{-- Bg Banner end --}}
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="overflow-hidden">
        @include('frontend::components.sections.continue-watching', ['value' => '6', 'sectionPaddingClass' => true])
        @include('frontend::components.sections.top-ten-block')
        @include('frontend::components.sections.only-on-streamit')
        @include('frontend::components.sections.fresh-picks-just-for-you')
        @include('frontend::components.sections.upcomming', ['viewAllBtn' => true])
    </div>
</div>

@include('frontend::components.sections.verticle-slider')

<div class="container-fluid">
    <div class="overflow-hidden">
        @include('frontend::components.sections.Your-Favourite-Personality')
        @include('frontend::components.sections.Popular-movies', ['viewAllBtn' => true])
    </div>
</div>

@include('frontend::components.sections.tab-slider')

<div class="container-fluid">
    <div class="overflow-hidden">
        @include('frontend::components.sections.geners')

        @include('frontend::components.sections.recommended', [
        'recommended' => __('sectionTitle.recommended_for_you'), 'viewAllBtn' => true,
        ])

        @include('frontend::components.sections.top-pict')
    </div>
</div>

{{-- Mobile Footer --}}
@include('frontend::components.widgets.mobile-footer')
{{-- Mobile Footer End --}}
@endsection