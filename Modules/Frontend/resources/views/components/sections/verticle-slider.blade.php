<div class="verticle-slider section-padding-bottom">
    <div class="slider">
        <div class="slider-flex position-relative">
            <div class="slider--col position-relative">
                <div class="vertical-slider-prev swiper-button"><i class="iconly-Arrow-Up-2 icli"></i></div>
                <div class="slider-thumbs" data-swiper="slider-thumbs">
                    <div class="swiper-container" data-swiper="slider-thumbs-inner">
                        <div class="swiper-wrapper top-ten-slider-nav">
                            <div class="swiper-slide swiper-bg">
                                <div class="block-images position-relative">
                                    <div class="img-box slider--image">
                                        <img src="{{ asset('frontend/images/media/the-first-of-us.webp') }}"
                                            class="w-100 rounded-3" alt="img" loading="lazy">
                                    </div>
                                    <div class="block-description">
                                        <h6 class="iq-title">{{ __('streamMovies.the_first_of_us') }}</h6>
                                        <div class="movie-time d-flex align-items-center my-2">
                                            <div class="d-flex align-items-center gap-1 font-size-12">
                                                <i class="ph ph-clock"></i>
                                                <span class="text-body">2hr : 59m</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="swiper-slide swiper-bg">
                                <div class="block-images position-relative">
                                    <div class="img-box slider--image">
                                        <img src="{{ asset('frontend/images/media/gameofhero.webp') }}"
                                            class="w-100 rounded-3" alt="img" loading="lazy">
                                    </div>
                                    <div class="block-description">
                                        <h6 class="iq-title">{{ __('streamMovies.game_of_heros') }}</h6>
                                        <div class="movie-time d-flex align-items-center my-2">
                                            <div class="d-flex align-items-center gap-1 font-size-12">
                                                <i class="ph ph-clock"></i>
                                                <span class="text-body">1hr : 45m</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="swiper-slide swiper-bg">
                                <div class="block-images position-relative">
                                    <div class="img-box slider--image">
                                        <img src="{{ asset('frontend/images/media/venom.webp') }}"
                                            class="w-100 rounded-3" alt="img" loading="lazy">
                                    </div>
                                    <div class="block-description">
                                        <h6 class="iq-title">{{ __('streamMovies.venom') }}</h6>
                                        <div class="movie-time d-flex align-items-center my-2">
                                            <div class="d-flex align-items-center gap-1 font-size-12">
                                                <i class="ph ph-clock"></i>
                                                <span class="text-body">2hr : 30m</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="swiper-slide swiper-bg">
                                <div class="block-images position-relative">
                                    <div class="img-box slider--image">
                                        <img src="{{ asset('frontend/images/media/rabbit.webp') }}"
                                            class="w-100 rounded-3" alt="img" loading="lazy">
                                    </div>
                                    <div class="block-description">
                                        <h6 class="iq-title">{{ __('streamMovies.rabbit') }}</h6>
                                        <div class="movie-time d-flex align-items-center my-2">
                                            <div class="d-flex align-items-center gap-1 font-size-12">
                                                <i class="ph ph-clock"></i>
                                                <span class="text-body">1hr : 45m</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="swiper-slide swiper-bg">
                                <div class="block-images position-relative">
                                    <div class="img-box slider--image">
                                        <img src="{{ asset('frontend/images/media/krishna.webp') }}"
                                            class="w-100 rounded-3" alt="img" loading="lazy">
                                    </div>
                                    <div class="block-description">
                                        <h6 class="iq-title">{{ __('streamMovies.krishna') }}</h6>
                                        <div class="movie-time d-flex align-items-center my-2">
                                            <div class="d-flex align-items-center gap-1 font-size-12">
                                                <i class="ph ph-clock"></i>
                                                <span class="text-body">1hr : 22m</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="vertical-slider-next swiper-button"><i class="iconly-Arrow-Down-2 icli"></i></div>
            </div>
            <div class="slider-images" data-swiper="slider-images">
                <div class="swiper-container" data-swiper="slider-images-inner">
                    <div class="swiper-wrapper">
                        <div class="swiper-slide">
                            <div class="slider--image block-images">
                                <img src="{{ asset('frontend/images/media/the-first-of-us.webp') }}"
                                    loading="lazy" alt="img">
                            </div>
                            <div class="description">
                                <div class="block-description">
                                    <ul
                                        class="ps-0 mb-2 pb-1 list-inline d-flex flex-wrap align-items-center movie-tag justify-content-center justify-content-lg-start genres-list gap-1 gap-sm-0">
                                        <li class="text-capitalize font-size-14 letter-spacing-1">
                                            <a href="{{ route('frontend.view_all') }}"
                                                class="text-decoration-none">{{ __('streamTag.action') }}</a>
                                        </li>
                                        <li class="text-capitalize font-size-14 letter-spacing-1">
                                            <a href="{{ route('frontend.view_all') }}"
                                                class="text-decoration-none">{{ __('streamTag.romance') }}</a>
                                        </li>
                                        <li class="text-capitalize font-size-14 letter-spacing-1">
                                            <a href="{{ route('frontend.view_all') }}"
                                                class="text-decoration-none">{{ __('streamTag.comedy') }}</a>
                                        </li>
                                        <li class="text-capitalize font-size-14 letter-spacing-1">
                                            <a href="{{ route('frontend.view_all') }}"
                                                class="text-decoration-none">{{ __('streamTag.mystery') }}</a>
                                        </li>
                                    </ul>
                                    <h2 class="iq-title m-0 line-count-2"><a
                                            href="{{ url('tv-show-detail.html') }}">{{ __('streamMovies.the_first_of_us') }}</a>
                                    </h2>
                                    <div
                                        class="d-flex align-items-center gap-3 py-2 justify-content-center justify-content-lg-start flex-wrap">
                                        <div class="slider-ratting d-flex align-items-center gap-1">
                                            <ul
                                                class="ratting-start p-0 m-0 list-inline text-warning d-flex align-items-center justify-content-left">
                                                <li><i class="ph-fill ph-star" aria-hidden="true"></i></li>
                                                <li><i class="ph-fill ph-star" aria-hidden="true"></i></li>
                                                <li><i class="ph-fill ph-star" aria-hidden="true"></i></li>
                                                <li><i class="ph-fill ph-star" aria-hidden="true"></i></li>
                                                <li><i class="ph-fill ph-star" aria-hidden="true"></i></li>
                                            </ul>
                                        </div>
                                        <div class="d-flex align-items-center gap-1">
                                            <p class="mb-0">4</p>
                                            <img class="imdb-img" alt="imdb-logo"
                                                src="{{ asset('frontend/images/pages/imdb-logo.svg') }}">
                                        </div>
                                        <div class="d-flex align-items-center gap-1">
                                            <i class="ph ph-clock font-size-14"></i>
                                            <span class="text-body">2hr : 30m</span>
                                        </div>
                                    </div>
                                    <p class="mt-2 mb-3 line-count-3">
                                        {{ __('streamMovies.the_first_of_us_desc') }}
                                    </p>
                                    @include('frontend::components.widgets.custom-button', [
                                        'buttonUrl' => 'movie-detail.html',
                                        'buttonTitle' => __('streamButtons.play_now'),
                                    ])
                                </div>
                            </div>
                        </div>
                        <div class="swiper-slide">
                            <div class="slider--image block-images">
                                <img src="{{ asset('frontend/images/media/gameofhero.webp') }}" loading="lazy"
                                    alt="img">
                            </div>
                            <div class="description">
                                <div class="block-description">
                                    <ul
                                        class="ps-0 mb-2 pb-1 list-inline d-flex flex-wrap align-items-center movie-tag justify-content-center justify-content-lg-start genres-list gap-1 gap-sm-0">
                                        <li class="text-capitalize font-size-14 letter-spacing-1">
                                            <a href="{{ route('frontend.view_all') }}"
                                                class="text-decoration-none">{{ __('streamTag.comedy') }}</a>
                                        </li>
                                        <li class="text-capitalize font-size-14 letter-spacing-1">
                                            <a href="{{ route('frontend.view_all') }}"
                                                class="text-decoration-none">{{ __('streamTag.romance') }}</a>
                                        </li>
                                        <li class="text-capitalize font-size-14 letter-spacing-1">
                                            <a href="{{ route('frontend.view_all') }}"
                                                class="text-decoration-none">{{ __('streamTag.action') }}</a>
                                        </li>
                                        <li class="text-capitalize font-size-14 letter-spacing-1">
                                            <a href="{{ route('frontend.view_all') }}"
                                                class="text-decoration-none">{{ __('streamTag.mystery') }}</a>
                                        </li>
                                    </ul>
                                    <h2 class="iq-title m-0 line-count-2"><a
                                            href="{{ url('tv-show-detail.html') }}">{{ __('streamMovies.game_of_heros') }}</a>
                                    </h2>
                                    <div
                                        class="d-flex align-items-center gap-3 py-2 justify-content-center justify-content-lg-start flex-wrap">
                                        <div class="slider-ratting d-flex align-items-center gap-1">
                                            <ul
                                                class="ratting-start p-0 m-0 list-inline text-warning d-flex align-items-center justify-content-left">
                                                <li><i class="ph-fill ph-star" aria-hidden="true"></i></li>
                                                <li><i class="ph-fill ph-star" aria-hidden="true"></i></li>
                                                <li><i class="ph-fill ph-star" aria-hidden="true"></i></li>
                                                <li><i class="ph ph-star" aria-hidden="true"></i></li>
                                                <li><i class="ph ph-star" aria-hidden="true"></i></li>
                                            </ul>
                                        </div>
                                        <div class="d-flex align-items-center gap-1">
                                            <p class="mb-0">2</p>
                                            <img class="imdb-img" alt="imdb-logo"
                                                src="{{ asset('frontend/images/pages/imdb-logo.svg') }}">
                                        </div>
                                        <div class="d-flex align-items-center gap-1">
                                            <i class="ph ph-clock font-size-14"></i>
                                            <span class="text-body">1hr : 45m</span>
                                        </div>
                                    </div>
                                    <p class="mt-2 mb-3 line-count-3">
                                        {{ __('streamMovies.game_of_heros_desc') }}
                                    </p>
                                    @include('frontend::components.widgets.custom-button', [
                                        'buttonUrl' => 'movie-detail.html',
                                        'buttonTitle' => __('streamButtons.play_now'),
                                    ])
                                </div>
                            </div>
                        </div>
                        <div class="swiper-slide">
                            <div class="slider--image block-images">
                                <img src="{{ asset('frontend/images/media/venom.webp') }}" loading="lazy"
                                    alt="img">
                            </div>
                            <div class="description">
                                <div class="block-description">
                                    <ul
                                    class="ps-0 mb-2 pb-1 list-inline d-flex flex-wrap align-items-center movie-tag justify-content-center justify-content-lg-start genres-list gap-1 gap-sm-0">
                                    <li class="text-capitalize font-size-14 letter-spacing-1">
                                        <a href="{{ route('frontend.view_all') }}"
                                            class="text-decoration-none">{{ __('streamTag.action') }}</a>
                                    </li>
                                        <li class="text-capitalize font-size-14 letter-spacing-1">
                                            <a href="{{ route('frontend.view_all') }}"
                                                class="text-decoration-none">{{ __('streamTag.crime') }}</a>
                                        </li>
                                        <li class="text-capitalize font-size-14 letter-spacing-1">
                                            <a href="{{ route('frontend.view_all') }}"
                                                class="text-decoration-none">{{ __('streamTag.hororr') }}</a>
                                        </li>
                                        <li class="text-capitalize font-size-14 letter-spacing-1">
                                            <a href="{{ route('frontend.view_all') }}"
                                                class="text-decoration-none">{{ __('streamTag.mystery') }}</a>
                                        </li>
                                    </ul>
                                    <h2 class="iq-title m-0 line-count-2"><a
                                            href="{{ url('tv-show-detail.html') }}">{{ __('streamMovies.venom') }}</a>
                                    </h2>
                                    <div
                                        class="d-flex align-items-center gap-3 py-2 justify-content-center justify-content-lg-start flex-wrap">
                                        <div class="slider-ratting d-flex align-items-center gap-1">
                                            <ul
                                                class="ratting-start p-0 m-0 list-inline text-warning d-flex align-items-center justify-content-left">
                                                <li><i class="ph-fill ph-star" aria-hidden="true"></i></li>
                                                <li><i class="ph-fill ph-star" aria-hidden="true"></i></li>
                                                <li><i class="ph-fill ph-star" aria-hidden="true"></i></li>
                                                <li><i class="ph-fill ph-star" aria-hidden="true"></i></li>
                                                <li><i class="ph-fill ph-star-half" aria-hidden="true"></i></li>
                                            </ul>
                                        </div>
                                        <div class="d-flex align-items-center gap-1">
                                            <p class="mb-0">10</p>
                                            <img class="imdb-img" alt="imdb-logo"
                                                src="{{ asset('frontend/images/pages/imdb-logo.svg') }}">
                                        </div>
                                        <div class="d-flex align-items-center gap-1">
                                            <i class="ph ph-clock font-size-14"></i>
                                            <span class="text-body">1hr : 45m</span>
                                        </div>
                                    </div>
                                    <p class="mt-2 mb-3 line-count-3">
                                        {{ __('streamMovies.venom_desc') }}
                                    </p>
                                    @include('frontend::components.widgets.custom-button', [
                                        'buttonUrl' => 'movie-detail.html',
                                        'buttonTitle' => __('streamButtons.play_now'),
                                    ])
                                </div>
                            </div>
                        </div>
                        <div class="swiper-slide">
                            <div class="slider--image block-images">
                                <img src="{{ asset('frontend/images/media/rabbit.webp') }}" loading="lazy"
                                    alt="img">
                            </div>
                            <div class="description">
                                <div class="block-description">
                                    <ul
                                        class="ps-0 mb-2 pb-1 list-inline d-flex flex-wrap align-items-center movie-tag justify-content-center justify-content-lg-start genres-list gap-1 gap-sm-0">
                                        <li class="text-capitalize font-size-14 letter-spacing-1">
                                            <a href="{{ route('frontend.view_all') }}"
                                                class="text-decoration-none">{{ __('streamTag.action') }}</a>
                                        </li>
                                        <li class="text-capitalize font-size-14 letter-spacing-1">
                                            <a href="{{ route('frontend.view_all') }}"
                                                class="text-decoration-none">{{ __('streamTag.horror') }}</a>
                                        </li>
                                        <li class="text-capitalize font-size-14 letter-spacing-1">
                                            <a href="{{ route('frontend.view_all') }}"
                                                class="text-decoration-none">{{ __('streamTag.adventure') }}</a>
                                        </li>
                                        <li class="text-capitalize font-size-14 letter-spacing-1">
                                            <a href="{{ route('frontend.view_all') }}"
                                                class="text-decoration-none">{{ __('streamTag.mystery') }}</a>
                                        </li>
                                    </ul>
                                    <h2 class="iq-title m-0 line-count-2"><a
                                            href="{{ url('tv-show-detail.html') }}">{{ __('streamMovies.rabbit') }}</a>
                                    </h2>
                                    <div
                                        class="d-flex align-items-center gap-3 py-2 justify-content-center justify-content-lg-start flex-wrap">
                                        <div class="slider-ratting d-flex align-items-center gap-1">
                                            <ul
                                                class="ratting-start p-0 m-0 list-inline text-warning d-flex align-items-center justify-content-left">
                                                <li><i class="ph-fill ph-star" aria-hidden="true"></i></li>
                                                <li><i class="ph-fill ph-star" aria-hidden="true"></i></li>
                                                <li><i class="ph-fill ph-star" aria-hidden="true"></i></li>
                                                <li><i class="ph-fill ph-star" aria-hidden="true"></i></li>
                                                <li><i class="ph-fill ph-star-half" aria-hidden="true"></i></li>
                                            </ul>
                                        </div>
                                        <div class="d-flex align-items-center gap-1">
                                            <p class="mb-0">10</p>
                                            <img class="imdb-img" alt="imdb-logo"
                                                src="{{ asset('frontend/images/pages/imdb-logo.svg') }}">
                                        </div>
                                        <div class="d-flex align-items-center gap-1">
                                            <i class="ph ph-clock font-size-14"></i>
                                            <span class="text-body">1hr : 45m</span>
                                        </div>
                                    </div>
                                    <p class="mt-2 mb-3 line-count-3">
                                        {{ __('streamMovies.rabbit_desc') }}
                                    </p>
                                    @include('frontend::components.widgets.custom-button', [
                                        'buttonUrl' => 'movie-detail.html',
                                        'buttonTitle' => __('streamButtons.play_now'),
                                    ])
                                </div>
                            </div>
                        </div>
                        <div class="swiper-slide">
                            <div class="slider--image block-images">
                                <img src="{{ asset('frontend/images/media/krishna.webp') }}" loading="lazy"
                                    alt="img">
                            </div>
                            <div class="description">
                                <div class="block-description">
                                    <ul
                                        class="ps-0 mb-2 pb-1 list-inline d-flex flex-wrap align-items-center movie-tag justify-content-center justify-content-lg-start genres-list gap-1 gap-sm-0">
                                        <li class="text-capitalize font-size-14 letter-spacing-1">
                                            <a href="{{ route('frontend.view_all') }}"
                                                class="text-decoration-none">{{ __('streamTag.comedy') }}</a>
                                        </li>
                                        <li class="text-capitalize font-size-14 letter-spacing-1">
                                            <a href="{{ route('frontend.view_all') }}"
                                                class="text-decoration-none">{{ __('streamTag.romance') }}</a>
                                        </li>
                                        <li class="text-capitalize font-size-14 letter-spacing-1">
                                            <a href="{{ route('frontend.view_all') }}"
                                                class="text-decoration-none">{{ __('streamTag.action') }}</a>
                                        </li>
                                        <li class="text-capitalize font-size-14 letter-spacing-1">
                                            <a href="{{ route('frontend.view_all') }}"
                                                class="text-decoration-none">{{ __('streamTag.mystery') }}</a>
                                        </li>
                                    </ul>
                                    <h2 class="iq-title m-0 line-count-2"><a
                                            href="{{ url('tv-show-detail.html') }}">{{ __('streamMovies.krishna') }}</a>
                                    </h2>
                                    <div
                                        class="d-flex align-items-center gap-3 py-2 justify-content-center justify-content-lg-start flex-wrap">
                                        <div class="slider-ratting d-flex align-items-center gap-1">
                                            <ul
                                                class="ratting-start p-0 m-0 list-inline text-warning d-flex align-items-center justify-content-left">
                                                <li><i class="ph-fill ph-star" aria-hidden="true"></i></li>
                                                <li><i class="ph-fill ph-star" aria-hidden="true"></i></li>
                                                <li><i class="ph-fill ph-star" aria-hidden="true"></i></li>
                                                <li><i class="ph-fill ph-star" aria-hidden="true"></i></li>
                                                <li><i class="ph ph-star" aria-hidden="true"></i></li>
                                            </ul>
                                        </div>
                                        <div class="d-flex align-items-center gap-1">
                                            <p class="mb-0">8</p>
                                            <img class="imdb-img" alt="imdb-logo"
                                                src="{{ asset('frontend/images/pages/imdb-logo.svg') }}">
                                        </div>
                                        <div class="d-flex align-items-center gap-1">
                                            <i class="ph ph-clock font-size-14"></i>
                                            <span class="text-body">1hr : 22m</span>
                                        </div>
                                    </div>
                                    <p class="mt-2 mb-3 line-count-3">
                                        {{ __('streamMovies.krishna_desc') }}</p>
                                    @include('frontend::components.widgets.custom-button', [
                                        'buttonUrl' => 'movie-detail.html',
                                        'buttonTitle' => __('streamButtons.play_now'),
                                    ])
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-block d-lg-none">
                        <div class="swiper-button swiper-button-next"></div>
                        <div class="swiper-button swiper-button-prev"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
