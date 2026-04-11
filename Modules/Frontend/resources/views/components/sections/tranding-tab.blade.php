<section class="tranding-tab-slider section-padding">
    <div class="container-fluid">
        <div class="row m-0 p-0">
            <div id="iq-trending" class="s-margin iq-tvshow-tabs iq-trending-tabs overflow-hidden">
                <div class="d-flex align-items-center justify-content-between px-1">
                    <h4 class="main-title text-capitalize mb-0 fw-medium">{{ __('streamTag.trending') }}</h4>
                </div>
                <div class="trending-contens position-relative ">
                    <div id="gallery-top" class="swiper gallery-thumbs pt-0" data-swiper="gallery-top">
                        <ul
                            class="swiper-wrapper list-inline m-0 trending-swiper-padding trending-slider-nav align-items-center ">
                            <li class="swiper-slide">
                                <a href="javascript:void(0);" tabindex="0">
                                    <div class="movie-swiper position-relative">
                                        <img src="{{ asset('frontend/images/media/pirates-ofdayones-orignal.webp') }}"
                                            alt="img" />
                                    </div>
                                </a>
                            </li>
                            <li class="swiper-slide">
                                <a href="javascript:void(0);">
                                    <div class="movie-swiper position-relative">
                                        <img src="{{ asset('frontend/images/media/the-hunter.webp') }}" alt="img" />
                                    </div>
                                </a>
                            </li>
                            <li class="swiper-slide">
                                <a href="javascript:void(0);">
                                    <div class="movie-swiper position-relative">
                                        <img src="{{ asset('frontend/images/media/lost-in-space.webp') }}"
                                            alt="img" />
                                    </div>
                                </a>
                            </li>
                            <li class="swiper-slide">
                                <a href="javascript:void(0);">
                                    <div class="movie-swiper position-relative">
                                        <img src="{{ asset('frontend/images/media/castle-rock.webp') }}"
                                            alt="img" />
                                    </div>
                                </a>
                            </li>
                            <li class="swiper-slide">
                                <a href="javascript:void(0);">
                                    <div class="movie-swiper position-relative">
                                        <img src="{{ asset('frontend/images/media/assassins-creed.webp') }}" alt="img" />
                                    </div>
                                </a>
                            </li>
                            <li class="swiper-slide">
                                <a href="javascript:void(0);" tabindex="0">
                                    <div class="movie-swiper position-relative">
                                        <img src="{{ asset('frontend/images/media/vikings.webp') }}"
                                            alt="img" />
                                    </div>
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div id="gallery-bottom" class="swiper trending-tab-slider swiper-no-swiping"
                        data-swiper="gallery-bottom">
                        <ul class="swiper-wrapper list-inline p-0 m-0 d-flex trending-slider">
                            <li class="swiper-slide slider-big-img-6">
                                <div class="trending-tab-slider-image">
                                    <img src="{{ asset('frontend/images/media/pirates-ofdayones-orignal.webp') }}"
                                        alt="trending-tab-slider-image">
                                </div>
                                <div class="tranding-block position-relative">
                                    <div class="trending-custom-tab">
                                        <div class="tab-title-info position-relative">
                                            <ul class="trending-pills iq-custom-tab flex-nowrap d-flex nav nav-pills justify-content-md-center align-items-center text-center list-inline"
                                                id="trending-tab-6" role="tablist">
                                                <li class="nav-item" role="presentation">
                                                    <a class="nav-link active" id="trending-data-tab-21"
                                                        data-bs-toggle="pill" data-bs-target="#trending-data-21"
                                                        aria-controls="trending-data-21" role="tab"
                                                        aria-selected="true">{{ __('streamTag.overview') }}</a>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <a class="nav-link" id="trending-data-tab-22"
                                                        data-bs-toggle="pill" data-bs-target="#trending-data-22"
                                                        aria-controls="trending-data-22" role="tab"
                                                        aria-selected="false">{{ __('streamTag.episodes') }}</a>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <a class="nav-link" id="trending-data-tab-23"
                                                        data-bs-toggle="pill" data-bs-target="#trending-data-23"
                                                        aria-controls="trending-data-23" role="tab"
                                                        aria-selected="false">{{ __('streamTag.trailers') }}</a>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <a class="nav-link" id="trending-data-tab-24"
                                                        data-bs-toggle="pill" data-bs-target="#trending-data-24"
                                                        aria-controls="trending-data-24" role="tab"
                                                        aria-selected="false">{{ __('streamTag.similar_like') }}</a>
                                                </li>
                                            </ul>
                                        </div>
                                        <div class="tab-content trending-content" id="trending-tab-6-content">
                                            <div id="trending-data-21" class="tab-pane fade show active"
                                                role="tabpanel" aria-labelledby="trending-data-tab-21"
                                                tabindex="0">
                                                <div
                                                    class=" trending-info align-items-center w-100 animated fadeInUp iq-ltr-direction">

                                                    <h1 class="trending-text big-title text-capitalize texture-text">
                                                        {{ __('streamMovies.pirates_of_day_one') }}
                                                    </h1>
                                                    <div
                                                        class="d-flex align-items-center text-white text-detail flex-wrap gap-1 gap-md-3">
                                                        <span>3 {{ __('streamEpisode.season') }}</span>
                                                        <div class="d-flex align-items-center gap-1">
                                                            <i class="ph ph-calendar-dots"></i>
                                                            <span class="trending-year">
                                                                May 2023</span>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex align-items-center flex-wrap series mb-4 gap-3">
                                                        <a href="javascript:void(0);"><img
                                                                src="{{ asset('frontend/images/pages/trending-label.webp') }}"
                                                                class="img-fluid
                                                                trending-label-img rounded-3"
                                                                alt="img"></a>
                                                        <span class="fw-bold">#1
                                                            {{ __('streamMovies.series_today') }}</span>
                                                    </div>
                                                    <p class="trending-dec line-count-4">{{ __('streamMovies.pirates_of_day_one_desc') }}
                                                    </p>
                                                    <div class="p-btns">
                                                        <div class="iq-button">
                                                            <a href="tv-show-detail"
                                                                class="btn btn-primary text-uppercase position-relative rounded-3">
                                                                <div class="d-flex align-items-center gap-2">
                                                                    <span
                                                                        class="button-text">{{ __('streamButtons.play_now') }}</span>
                                                                    <i class="ph-fill ph-play"></i>
                                                                </div>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="trending-data-22" class="tab-pane fade" role="tabpanel"
                                                aria-labelledby="trending-data-tab-22" tabindex="0">
                                                <div
                                                    class=" trending-info align-items-center w-100 animated fadeInUp iq-ltr-direction">

                                                    <h1 class="trending-text big-title text-capitalize texture-text">
                                                        {{ __('streamMovies.pirates_of_day_one') }}
                                                    </h1>
                                                    <div
                                                        class="d-flex align-items-center text-white text-detail flex-wrap mb-4 gap-3">
                                                        <span class="season_date">
                                                            2 {{ __('streamEpisode.season') }}
                                                        </span>
                                                        <div class="d-flex align-items-center gap-1">
                                                            <i class="ph ph-calendar-dots"></i>
                                                            <span class="trending-year">Feb 2019</span>
                                                        </div>
                                                    </div>
                                                    <div class="iq-custom-select d-inline-block sea-epi mb-4">
                                                        <select name="cars"
                                                            class="form-control season-select select2-basic-single js-states">
                                                            <option value="season1">{{ __('streamEpisode.season') }} 1
                                                            </option>
                                                            <option value="season2">{{ __('streamEpisode.season') }} 2
                                                            </option>
                                                            <option value="season3">{{ __('streamEpisode.season') }} 3
                                                            </option>
                                                        </select>
                                                    </div>
                                                    <div class="position-relative swiper swiper-card" data-slide="4"
                                                        data-laptop="3" data-tab="2" data-mobile="2"
                                                        data-mobile-sm="1" data-autoplay="false" data-loop="true"
                                                        data-navigation="false" data-pagination="true">
                                                        <ul class="p-0 swiper-wrapper m-0  list-inline">
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => 'media/episode/s1e4-island-of-secrets.webp',
                                                                'id' => '01',
                                                                'showEpisod' => 'S01E01',
                                                                'episodDate' => 'October 1, 2022',
                                                                'episodTime' => '45m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '04',
                                                                'episodeTitlesText' => __(
                                                                'streamEpisode.episode5'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamEpisode.episode5_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => 'media/episode/s1e3-rivals-and-revelations.webp',
                                                                'id' => '02',
                                                                'showEpisod' => 'S01E02',
                                                                'episodDate' => 'October 8, 2022',
                                                                'episodTime' => '35m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '03',
                                                                'episodeTitlesText' => __(
                                                                'streamEpisode.episode4'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamEpisode.episode4_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => 'media/episode/s1e2-hidden-allies.webp',
                                                                'id' => '03',
                                                                'showEpisod' => 'S01E03',
                                                                'episodDate' => 'October 15, 2022',
                                                                'episodTime' => '36m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '02',
                                                                'episodeTitlesText' => __(
                                                                'streamEpisode.episode3'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamEpisode.episode3_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => 'media/episode/s1e1-setting-sail.webp',
                                                                'id' => '04',
                                                                'showEpisod' => 'S01E04',
                                                                'episodDate' => 'October 22, 2022',
                                                                'episodTime' => '41m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '01',
                                                                'episodeTitlesText' => __(
                                                                'streamEpisode.episode2'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamEpisode.episode2_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                        </ul>
                                                        <div class="swiper-pagination d-block"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="trending-data-23" class="tab-pane fade" role="tabpanel"
                                                aria-labelledby="trending-data-tab-23" tabindex="0">
                                                <div
                                                    class=" trending-info align-items-center w-100 animated fadeInUp iq-ltr-direction text-center">
                                                    <h3
                                                        class="trending-text big-title text-uppercase texture-text mt-2">
                                                        {{ __('streamButtons.watch_trailer') }}
                                                    </h3>
                                                    <div class="episodes-contens mt-5">
                                                        <div
                                                            class="tab-watch-trailer-container d-inline-block rounded-3 overflow-hidden">
                                                            <div
                                                                class="tab-watch-trailer position-relative rounded-3 overflow-hidden">
                                                                <img src="{{ asset('frontend/images/video/trailer-poster.webp') }}"
                                                                    class="trailer-image" alt="trailer-image">
                                                                <a data-fslightbox="html5-video"
                                                                    href="{{ asset('frontend/images/video/trailer.mp4') }}"
                                                                    class="video-open playbtn text-descoration-none"
                                                                    tabindex="0">
                                                                    <svg version="1.1"
                                                                        xmlns="http://www.w3.org/2000/svg"
                                                                        xmlns:xlink="http://www.w3.org/1999/xlink"
                                                                        x="0px" y="0px" width="80px"
                                                                        height="80px" viewBox="0 0 213.7 213.7"
                                                                        enable-background="new 0 0 213.7 213.7"
                                                                        xml:space="preserve">
                                                                        <polygon class="triangle" fill="none"
                                                                            stroke-width="7" stroke-linecap="round"
                                                                            stroke-linejoin="round"
                                                                            stroke-miterlimit="10"
                                                                            points="73.5,62.5 148.5,105.8 73.5,149.1 ">
                                                                        </polygon>
                                                                        <circle class="circle" fill="none"
                                                                            stroke-width="7" stroke-linecap="round"
                                                                            stroke-linejoin="round"
                                                                            stroke-miterlimit="10" cx="106.8"
                                                                            cy="106.8" r="103.3">
                                                                        </circle>
                                                                    </svg>
                                                                    <span
                                                                        class="w-trailor text-uppercase">{{ __('streamButtons.watch_trailer') }}
                                                                    </span>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="trending-data-24" class="tab-pane fade" role="tabpanel"
                                                aria-labelledby="trending-data-tab-24" tabindex="0">
                                                <div
                                                    class=" trending-info align-items-center w-100 animated fadeInUp iq-ltr-direction">
                                                    <h3
                                                        class="trending-text big-title text-uppercase texture-text mb-5">
                                                        {{ __('sectionTitle.recommended_for_you') }}
                                                    </h3>
                                                    <div class="position-relative swiper swiper-card" data-slide="4"
                                                        data-laptop="3" data-tab="2" data-mobile="1"
                                                        data-mobile-sm="1" data-autoplay="false" data-loop="true"
                                                        data-navigation="false" data-pagination="true">
                                                        <ul class="p-0 swiper-wrapper m-0  list-inline">
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => 'media/episode/s2e4-tides-of-betrayal.webp',
                                                                'id' => '06',
                                                                'showEpisod' => 'Seasons 1-2',
                                                                'episodDate' => 'October 1, 2022',
                                                                'episodTime' => '45m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '04',
                                                                'episodeTitlesText' => __(
                                                                'streamEpisode.episode33'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamEpisode.episode33_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => 'media/episode/s1e5-Lost-Souls.webp',
                                                                'id' => '07',
                                                                'showEpisod' => 'Seasons 1-3',
                                                                'episodDate' => 'October 8, 2022',
                                                                'episodTime' => '35m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '03',
                                                                'episodeTitlesText' => __(
                                                                'streamEpisode.episode34'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamEpisode.episode34_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => '/media/episode/s3e3-cursed-waters.webp',
                                                                'id' => '08',
                                                                'showEpisod' => 'Seasons 1-4',
                                                                'episodDate' => 'October 15, 2022',
                                                                'episodTime' => '36m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '02',
                                                                'episodeTitlesText' => __(
                                                                'streamEpisode.episode35'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamEpisode.episode35_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => 'media/episode/s3e1-betrayals-and-bonds.webp',
                                                                'id' => '09',
                                                                'showEpisod' => 'Seasons 1-5',
                                                                'episodDate' => 'October 22, 2022',
                                                                'episodTime' => '41m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '01',
                                                                'episodeTitlesText' => __(
                                                                'streamEpisode.episode36'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamEpisode.episode36_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                        </ul>
                                                        <div class="swiper-pagination d-block"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <li class="swiper-slide slider-big-img-1 p-0">
                                <div class="trending-tab-slider-image">
                                    <img src="{{ asset('frontend/images/media/the-hunter.webp') }}"
                                        alt="trending-tab-slider-image">
                                </div>
                                <div class="tranding-block position-relative">
                                    <div class="trending-custom-tab">
                                        <div class="tab-title-info position-relative">
                                            <ul class="trending-pills iq-custom-tab flex-nowrap d-flex nav nav-pills justify-content-md-center align-items-center text-center list-inline"
                                                id="trending-tab-1" role="tablist">
                                                <li class="nav-item" role="presentation">
                                                    <a class="nav-link active" id="trending-data-tab-1"
                                                        data-bs-toggle="pill" data-bs-target="#trending-data-1"
                                                        aria-controls="trending-data-1" role="tab"
                                                        aria-selected="true">{{ __('streamTag.overview') }}</a>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <a class="nav-link" id="trending-data-tab-2" data-bs-toggle="pill"
                                                        data-bs-target="#trending-data-2"
                                                        aria-controls="trending-data-2" role="tab"
                                                        aria-selected="false">{{ __('streamTag.episodes') }}</a>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <a class="nav-link" id="trending-data-tab-3" data-bs-toggle="pill"
                                                        data-bs-target="#trending-data-3"
                                                        aria-controls="trending-data-3" role="tab"
                                                        aria-selected="false">{{ __('streamTag.trailers') }}</a>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <a class="nav-link" id="trending-data-tab-4" data-bs-toggle="pill"
                                                        data-bs-target="#trending-data-4"
                                                        aria-controls="trending-data-4" role="tab"
                                                        aria-selected="false">{{ __('streamTag.similar_like') }}</a>
                                                </li>
                                            </ul>
                                        </div>
                                        <div class="tab-content trending-content" id="trending-tab-1-content">
                                            <div id="trending-data-1" class="tab-pane fade show active" role="tabpanel"
                                                aria-labelledby="trending-data-tab-1" tabindex="0">
                                                <div
                                                    class=" trending-info align-items-center w-100 animated fadeInUp iq-ltr-direction">

                                                    <h1 class="trending-text big-title text-capitalize texture-text">
                                                        {{ __('streamMovies.the_hunter') }}
                                                    </h1>
                                                    <div
                                                        class="d-flex align-items-start align-items-md-center text-white text-detail flex-wrap gap-1 gap-md-3 flex-column flex-md-row">
                                                        <span>3 {{ __('streamEpisode.season') }}</span>
                                                        <div class="d-flex align-items-center gap-1">
                                                            <i class="ph ph-calendar-dots"></i>
                                                            <span class="trending-year">
                                                                January 2023</span>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex align-items-center flex-wrap series mb-4 gap-3">
                                                        <a href="javascript:void(0);"><img
                                                                src="{{ asset('frontend/images/pages/trending-label.webp') }}"
                                                                class="img-fluid
                                                                trending-label-img rounded-3"
                                                                alt="img"></a>
                                                        <span class="fw-bold">#2
                                                            {{ __('streamMovies.series_today') }}</span>
                                                    </div>
                                                    <p class="trending-dec line-count-4">{{ __('streamMovies.the_hunter_desc') }}
                                                    </p>
                                                    <div class="p-btns">
                                                        <div class="iq-button">
                                                            <a href="tv-show-detail"
                                                                class="btn text-uppercase position-relative btn-primary rounded-3">
                                                                <div class="d-flex align-items-center gap-2">
                                                                    <span
                                                                        class="button-text">{{ __('streamButtons.play_now') }}</span>
                                                                    <i class="ph-fill ph-play"></i>
                                                                </div>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="trending-data-2" class="tab-pane fade" role="tabpanel"
                                                aria-labelledby="trending-data-tab-2" tabindex="0">
                                                <div
                                                    class=" trending-info align-items-center w-100 animated fadeInUp iq-ltr-direction">

                                                    <h1 class="trending-text big-title text-capitalize texture-text">
                                                        {{ __('streamMovies.the_hunter') }}
                                                    </h1>
                                                    <div
                                                        class="d-flex align-items-center text-white text-detail flex-wrap mb-4 gap-3">
                                                        <span class="season_date">
                                                            2 {{ __('streamEpisode.season') }}
                                                        </span>
                                                        <div class="d-flex align-items-center gap-1">
                                                            <i class="ph ph-calendar-dots"></i>
                                                            <span class="trending-year">Feb 2019</span>
                                                        </div>
                                                    </div>
                                                    <div class="iq-custom-select d-inline-block sea-epi mb-4">
                                                        <select name="cars"
                                                            class="form-control select2-basic-single js-states season-select">
                                                            <option value="season1">{{ __('streamEpisode.season') }} 1
                                                            </option>
                                                            <option value="season2">{{ __('streamEpisode.season') }} 2
                                                            </option>
                                                            <option value="season3">{{ __('streamEpisode.season') }} 3
                                                            </option>
                                                        </select>
                                                    </div>
                                                    <div class="position-relative swiper swiper-card" data-slide="4"
                                                        data-laptop="3" data-tab="2" data-mobile="2"
                                                        data-mobile-sm="1" data-autoplay="false" data-loop="true"
                                                        data-navigation="false" data-pagination="false">
                                                        <ul class="p-0 swiper-wrapper m-0  list-inline">
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => 'media/episode/s1e3-fire-and-bloodlines.webp',
                                                                'id' => '01',
                                                                'episodTime' => '45m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '04',
                                                                'episodeTitlesText' => __(
                                                                'streamEpisode.episode37'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamEpisode.episode37_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => 'media/episode/s1e2-kings-and-conspiracies.webp',
                                                                'id' => '02',
                                                                'episodTime' => '35m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '03',
                                                                'episodeTitlesText' => __(
                                                                'streamEpisode.episode38'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamEpisode.episode38_desc'),
                                                                ]
                                                                )

                                                            </li>
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => 'media/episode/s1e1-awakening-of-the-drakes.webp',
                                                                'id' => '03',
                                                                'episodTime' => '36m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '02',
                                                                'episodeTitlesText' => __(
                                                                'streamEpisode.episode39'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamEpisode.episode39_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                        </ul>
                                                        <div class="swiper-pagination d-block"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="trending-data-3" class="tab-pane fade" role="tabpanel"
                                                aria-labelledby="trending-data-tab-3" tabindex="0">
                                                <div
                                                    class=" trending-info align-items-center w-100 animated fadeInUp iq-ltr-direction text-center">
                                                    <h3
                                                        class="trending-text big-title text-uppercase texture-text mt-2">
                                                        {{ __('streamButtons.watch_trailer') }}
                                                    </h3>
                                                    <div class="episodes-contens mt-5">
                                                        <div
                                                            class="tab-watch-trailer-container d-inline-block rounded-3 overflow-hidden">
                                                            <div
                                                                class="tab-watch-trailer position-relative rounded-3 overflow-hidden">
                                                                <img src="{{ asset('frontend/images/video/trailer-poster.webp') }}"
                                                                    class="trailer-image" alt="trailer-image">
                                                                <a data-fslightbox="html5-video"
                                                                    href="{{ asset('frontend/images/video/trailer.mp4') }}"
                                                                    class="video-open playbtn text-descoration-none"
                                                                    tabindex="0">
                                                                    <svg version="1.1"
                                                                        xmlns="http://www.w3.org/2000/svg"
                                                                        xmlns:xlink="http://www.w3.org/1999/xlink"
                                                                        x="0px" y="0px" width="80px" height="80px"
                                                                        viewBox="0 0 213.7 213.7"
                                                                        enable-background="new 0 0 213.7 213.7"
                                                                        xml:space="preserve">
                                                                        <polygon class="triangle" fill="none"
                                                                            stroke-width="7" stroke-linecap="round"
                                                                            stroke-linejoin="round"
                                                                            stroke-miterlimit="10"
                                                                            points="73.5,62.5 148.5,105.8 73.5,149.1 ">
                                                                        </polygon>
                                                                        <circle class="circle" fill="none"
                                                                            stroke-width="7" stroke-linecap="round"
                                                                            stroke-linejoin="round"
                                                                            stroke-miterlimit="10" cx="106.8"
                                                                            cy="106.8" r="103.3">
                                                                        </circle>
                                                                    </svg>
                                                                    <span
                                                                        class="w-trailor text-uppercase">{{ __('streamButtons.watch_trailer') }}
                                                                    </span>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="trending-data-4" class="tab-pane fade" role="tabpanel"
                                                aria-labelledby="trending-data-tab-4" tabindex="0">
                                                <div
                                                    class=" trending-info align-items-center w-100 animated fadeInUp iq-ltr-direction">
                                                    <h3
                                                        class="trending-text big-title text-uppercase texture-text mb-5">
                                                        {{ __('sectionTitle.recommended_for_you') }}
                                                    </h3>
                                                    <div class="position-relative swiper swiper-card" data-slide="4"
                                                        data-laptop="3" data-tab="2" data-mobile="1"
                                                        data-mobile-sm="1" data-autoplay="false" data-loop="true"
                                                        data-navigation="false" data-pagination="true">
                                                        <ul class="p-0 swiper-wrapper m-0  list-inline">
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => 'media/episode/s4e1-rebirth-of-the-realm.webp',
                                                                'id' => '06',
                                                                'showEpisod' => 'Seasons 1-2',
                                                                'episodDate' => 'October 1, 2022',
                                                                'episodTime' => '45m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '01',
                                                                'episodeTitlesText' => __(
                                                                'streamEpisode.episode41'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamEpisode.episode41_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => 'media/episode/s2e3-the-dragons-redemption.webp',
                                                                'id' => '07',
                                                                'showEpisod' => 'Seasons 1-3',
                                                                'episodDate' => 'October 8, 2022',
                                                                'episodTime' => '35m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '03',
                                                                'episodeTitlesText' => __(
                                                                'streamEpisode.episode41'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamEpisode.episode41_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => 'media/episode/s4e3-the-final-dawn.webp',
                                                                'id' => '08',
                                                                'showEpisod' => 'Seasons 1-4',
                                                                'episodDate' => 'October 15, 2022',
                                                                'episodTime' => '36m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '02',
                                                                'episodeTitlesText' => __(
                                                                'streamEpisode.episode42'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamEpisode.episode42_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => 'media/episode/s4e2-echoes-of-legends.webp',
                                                                'id' => '09',
                                                                'showEpisod' => 'Seasons 1-5',
                                                                'episodDate' => 'October 22, 2022',
                                                                'episodTime' => '41m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '01',
                                                                'episodeTitlesText' => __(
                                                                'streamEpisode.episode43'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamEpisode.episode43_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                        </ul>
                                                        <div class="swiper-pagination d-block"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <li class="swiper-slide slider-big-img-2">
                                <div class="trending-tab-slider-image">
                                    <img src="{{ asset('frontend/images/media/lost-in-space.webp') }}"
                                        alt="trending-tab-slider-image">
                                </div>
                                <div class="tranding-block position-relative">
                                    <div class="trending-custom-tab">
                                        <div class="tab-title-info position-relative">
                                            <ul class="trending-pills iq-custom-tab flex-nowrap d-flex nav nav-pills justify-content-md-center align-items-center text-center list-inline"
                                                id="trending-tab-2" role="tablist">
                                                <li class="nav-item" role="presentation">
                                                    <a class="nav-link active" id="trending-data-tab-5"
                                                        data-bs-toggle="pill" data-bs-target="#trending-data-5"
                                                        aria-controls="trending-data-5" role="tab"
                                                        aria-selected="true">{{ __('streamTag.overview') }}</a>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <a class="nav-link" id="trending-data-tab-6"
                                                        data-bs-toggle="pill" data-bs-target="#trending-data-6"
                                                        aria-controls="trending-data-6" role="tab"
                                                        aria-selected="false">{{ __('streamTag.episodes') }}</a>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <a class="nav-link" id="trending-data-tab-7"
                                                        data-bs-toggle="pill" data-bs-target="#trending-data-7"
                                                        aria-controls="trending-data-7" role="tab"
                                                        aria-selected="false">{{ __('streamTag.trailers') }}</a>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <a class="nav-link" id="trending-data-tab-8"
                                                        data-bs-toggle="pill" data-bs-target="#trending-data-8"
                                                        aria-controls="trending-data-8" role="tab"
                                                        aria-selected="false">{{ __('streamTag.similar_like') }}</a>
                                                </li>
                                            </ul>
                                        </div>
                                        <div class="tab-content trending-content" id="trending-tab-2-content">
                                            <div id="trending-data-5" class="tab-pane fade show active"
                                                role="tabpanel" aria-labelledby="trending-data-tab-5" tabindex="0">
                                                <div
                                                    class=" trending-info align-items-center w-100 animated fadeInUp iq-ltr-direction">

                                                    <h1 class="trending-text big-title text-capitalize texture-text">
                                                        {{ __('streamMovies.lost_in_space') }}
                                                    </h1>
                                                    <div
                                                        class="d-flex align-items-center text-white text-detail flex-wrap gap-1 gap-md-3">
                                                        <span>3 {{ __('streamEpisode.season') }}</span>
                                                        <div class="d-flex align-items-center gap-1">
                                                            <i class="ph ph-calendar-dots"></i>
                                                            <span class="trending-year">
                                                                March 2023</span>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex align-items-center flex-wrap series mb-4 gap-3">
                                                        <a href="javascript:void(0);"><img
                                                                src="{{ asset('frontend/images/pages/trending-label.webp') }}"
                                                                class="img-fluid
                                                                trending-label-img rounded-3"
                                                                alt="img"></a>
                                                        <span class="fw-bold">#2
                                                            {{ __('streamMovies.series_today') }}</span>
                                                    </div>
                                                    <p class="trending-dec line-count-4">{{ __('streamMovies.lost_in_space_desc') }}
                                                    </p>
                                                    <div class="p-btns">
                                                        <div class="iq-button">
                                                            <a href="tv-show-detail"
                                                                class="btn btn-primary text-uppercase position-relative rounded-3">
                                                                <div class="d-flex align-items-center gap-2">
                                                                    <span
                                                                        class="button-text">{{ __('streamButtons.play_now') }}</span>
                                                                    <i class="ph-fill ph-play"></i>
                                                                </div>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="trending-data-6" class="tab-pane fade" role="tabpanel"
                                                aria-labelledby="trending-data-tab-6" tabindex="0">
                                                <div
                                                    class=" trending-info align-items-center w-100 animated fadeInUp iq-ltr-direction">

                                                    <h1 class="trending-text big-title text-capitalize texture-text">
                                                        {{ __('streamMovies.castle_rock') }}
                                                    </h1>
                                                    <div
                                                        class="d-flex align-items-center text-white text-detail flex-wrap mb-4 gap-3">
                                                        <span class="season_date">
                                                            2 {{ __('streamEpisode.season') }}
                                                        </span>
                                                        <div class="d-flex align-items-center gap-1">
                                                            <i class="ph ph-calendar-dots"></i>
                                                            <span class="trending-year">Feb 2019</span>
                                                        </div>
                                                    </div>
                                                    <div class="iq-custom-select d-inline-block sea-epi mb-4">
                                                        <select name="cars"
                                                            class="form-control select2-basic-single js-states season-select">
                                                            <option value="season1">{{ __('streamEpisode.season') }} 1
                                                            </option>
                                                            <option value="season2">{{ __('streamEpisode.season') }} 2
                                                            </option>
                                                            <option value="season3">{{ __('streamEpisode.season') }} 3
                                                            </option>
                                                        </select>
                                                    </div>
                                                    <div class="position-relative swiper swiper-card" data-slide="4"
                                                        data-laptop="3" data-tab="2" data-mobile="2"
                                                        data-mobile-sm="1" data-autoplay="false" data-loop="false"
                                                        data-navigation="false" data-pagination="true">
                                                        <ul class="p-0 swiper-wrapper m-0  list-inline">
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' =>
                                                                'media/episode/s3e1-contact.webp',
                                                                'id' => '01',
                                                                'episodTime' => '45m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '04',
                                                                'episodeTitlesText' => __(
                                                                'streamEpisode.episode32'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamEpisode.episode32_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' =>
                                                                'media/episode/s1e3-shipwrecked.webp',
                                                                'id' => '02',
                                                                'episodTime' => '35m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '03',
                                                                'episodeTitlesText' => __(
                                                                'streamEpisode.episode24'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamEpisode.episode24_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' =>
                                                                'media/episode/s1e2-the-new-guy.webp',
                                                                'id' => '03',
                                                                'episodTime' => '36m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '02',
                                                                'episodeTitlesText' => __(
                                                                'streamEpisode.episode25'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamEpisode.episode25_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' =>
                                                                'media/episode/s1e1-trust.webp',
                                                                'id' => '04',
                                                                'episodTime' => '41m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '01',
                                                                'episodeTitlesText' => __(
                                                                'streamEpisode.episode26'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamEpisode.episode26_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                        </ul>
                                                        <div class="swiper-pagination d-block"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="trending-data-7" class="tab-pane fade" role="tabpanel"
                                                aria-labelledby="trending-data-tab-7" tabindex="0">
                                                <div
                                                    class=" trending-info align-items-center w-100 animated fadeInUp iq-ltr-direction text-center">
                                                    <h3
                                                        class="trending-text big-title text-uppercase texture-text mt-2">
                                                        {{ __('streamButtons.watch_trailer') }}
                                                    </h3>
                                                    <div class="episodes-contens mt-5">
                                                        <div
                                                            class="tab-watch-trailer-container d-inline-block rounded-3 overflow-hidden">
                                                            <div
                                                                class="tab-watch-trailer position-relative rounded-3 overflow-hidden">
                                                                <img src="{{ asset('frontend/images/video/trailer-poster.webp') }}"
                                                                    class="trailer-image" alt="trailer-image">
                                                                <a data-fslightbox="html5-video"
                                                                    href="{{ asset('frontend/images/video/trailer.mp4') }}"
                                                                    class="video-open playbtn text-descoration-none"
                                                                    tabindex="0">
                                                                    <svg version="1.1"
                                                                        xmlns="http://www.w3.org/2000/svg"
                                                                        xmlns:xlink="http://www.w3.org/1999/xlink"
                                                                        x="0px" y="0px" width="80px" height="80px"
                                                                        viewBox="0 0 213.7 213.7"
                                                                        enable-background="new 0 0 213.7 213.7"
                                                                        xml:space="preserve">
                                                                        <polygon class="triangle" fill="none"
                                                                            stroke-width="7" stroke-linecap="round"
                                                                            stroke-linejoin="round"
                                                                            stroke-miterlimit="10"
                                                                            points="73.5,62.5 148.5,105.8 73.5,149.1 ">
                                                                        </polygon>
                                                                        <circle class="circle" fill="none"
                                                                            stroke-width="7" stroke-linecap="round"
                                                                            stroke-linejoin="round"
                                                                            stroke-miterlimit="10" cx="106.8"
                                                                            cy="106.8" r="103.3">
                                                                        </circle>
                                                                    </svg>
                                                                    <span
                                                                        class="w-trailor text-uppercase">{{ __('streamButtons.watch_trailer') }}
                                                                    </span>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="trending-data-8" class="tab-pane fade" role="tabpanel"
                                                aria-labelledby="trending-data-tab-8" tabindex="0">
                                                <div
                                                    class=" trending-info align-items-center w-100 animated fadeInUp iq-ltr-direction">
                                                    <h3
                                                        class="trending-text big-title text-uppercase texture-text mb-5">
                                                        {{ __('sectionTitle.recommended_for_you') }}
                                                    </h3>
                                                    <div class="position-relative swiper swiper-card" data-slide="4"
                                                        data-laptop="3" data-tab="2" data-mobile="1"
                                                        data-mobile-sm="1" data-autoplay="false" data-loop="true"
                                                        data-navigation="false" data-pagination="true">
                                                        <ul class="p-0 swiper-wrapper m-0  list-inline">
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => 'media/kung-fu-panda.webp',
                                                                'id' => '06',
                                                                'showEpisod' => 'Seasons 1-2',
                                                                'episodDate' => 'October 1, 2022',
                                                                'episodTime' => '45m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '04',
                                                                'episodeTitlesText' => __(
                                                                'streamMovies.kung_fu_panda'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamMovies.kung_fu_panda_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => 'media/dinoosaur.webp',
                                                                'id' => '07',
                                                                'showEpisod' => 'Seasons 1-3',
                                                                'episodDate' => 'October 8, 2022',
                                                                'episodTime' => '35m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '03',
                                                                'episodeTitlesText' => __(
                                                                'streamMovies.the_green_mile'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamMovies.the_green_mile_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => 'media/arrival.webp',
                                                                'id' => '08',
                                                                'showEpisod' => 'Seasons 1-4',
                                                                'episodDate' => 'October 15, 2022',
                                                                'episodTime' => '36m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '02',
                                                                'episodeTitlesText' => __(
                                                                'streamMovies.arrival'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamMovies.arrival_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => 'media/venom.webp',
                                                                'id' => '09',
                                                                'showEpisod' => 'Seasons 1-5',
                                                                'episodDate' => 'October 22, 2022',
                                                                'episodTime' => '41m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '01',
                                                                'episodeTitlesText' => __(
                                                                'streamMovies.venom'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamMovies.venom_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                        </ul>
                                                        <div class="swiper-pagination d-block"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <li class="swiper-slide slider-big-img-3">
                                <div class="trending-tab-slider-image">
                                    <img src="{{ asset('frontend/images/media/castle-rock.webp') }}"
                                        alt="trending-tab-slider-image">
                                </div>
                                <div class="tranding-block position-relative">
                                    <div class="trending-custom-tab">
                                        <div class="tab-title-info position-relative">
                                            <ul class="trending-pills iq-custom-tab flex-nowrap d-flex nav nav-pills justify-content-md-center align-items-center text-center list-inline"
                                                id="trending-tab-3" role="tablist">
                                                <li class="nav-item" role="presentation">
                                                    <a class="nav-link active" id="trending-data-tab-9"
                                                        data-bs-toggle="pill" data-bs-target="#trending-data-9"
                                                        aria-controls="trending-data-9" role="tab"
                                                        aria-selected="true">{{ __('streamTag.overview') }}</a>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <a class="nav-link" id="trending-data-tab-10"
                                                        data-bs-toggle="pill" data-bs-target="#trending-data-10"
                                                        aria-controls="trending-data-10" role="tab"
                                                        aria-selected="false">{{ __('streamTag.episodes') }}</a>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <a class="nav-link" id="trending-data-tab-11"
                                                        data-bs-toggle="pill" data-bs-target="#trending-data-11"
                                                        aria-controls="trending-data-11" role="tab"
                                                        aria-selected="false">{{ __('streamTag.trailers') }}</a>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <a class="nav-link" id="trending-data-tab-12"
                                                        data-bs-toggle="pill" data-bs-target="#trending-data-12"
                                                        aria-controls="trending-data-12" role="tab"
                                                        aria-selected="false">{{ __('streamTag.similar_like') }}</a>
                                                </li>
                                            </ul>
                                        </div>
                                        <div class="tab-content trending-content" id="trending-tab-3-content">
                                            <div id="trending-data-9" class="tab-pane fade show active"
                                                role="tabpanel" aria-labelledby="trending-data-tab-9" tabindex="0">
                                                <div
                                                    class=" trending-info align-items-center w-100 animated fadeInUp iq-ltr-direction">

                                                    <h1 class="trending-text big-title text-capitalize texture-text">
                                                        {{ __('streamMovies.castle_rock') }}
                                                    </h1>
                                                    <div
                                                        class="d-flex align-items-center text-white text-detail flex-wrap gap-1 gap-md-3">
                                                        <span>3 {{ __('streamEpisode.season') }}</span>
                                                        <div class="d-flex align-items-center gap-1">
                                                            <i class="ph ph-calendar-dots"></i>
                                                            <span class="trending-year">
                                                                January 2023</span>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex align-items-center flex-wrap series mb-4 gap-3">
                                                        <a href="javascript:void(0);"><img
                                                                src="{{ asset('frontend/images/pages/trending-label.webp') }}"
                                                                class="img-fluid
                                                                trending-label-img rounded-3"
                                                                alt="img"></a>
                                                        <span class="fw-bold">#2
                                                            {{ __('streamMovies.series_today') }}</span>
                                                    </div>
                                                    <p class="trending-dec line-count-4">{{ __('streamMovies.castle_rock_desc') }}
                                                    </p>
                                                    <div class="p-btns">
                                                        <div class="iq-button">
                                                            <a href="tv-show-detail"
                                                                class="btn btn-primary text-uppercase position-relative rounded-3">
                                                                <div class="d-flex align-items-center gap-2">
                                                                    <span
                                                                        class="button-text">{{ __('streamButtons.play_now') }}</span>
                                                                    <i class="ph-fill ph-play"></i>
                                                                </div>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="trending-data-10" class="tab-pane fade" role="tabpanel"
                                                aria-labelledby="trending-data-tab-10" tabindex="0">
                                                <div
                                                    class=" trending-info align-items-center w-100 animated fadeInUp iq-ltr-direction">

                                                    <h1 class="trending-text big-title text-capitalize texture-text">
                                                        {{ __('streamMovies.castle_rock') }}
                                                    </h1>
                                                    <div
                                                        class="d-flex align-items-center text-white text-detail flex-wrap mb-4 gap-3">
                                                        <span class="season_date">
                                                            2 {{ __('streamEpisode.season') }}
                                                        </span>
                                                        <div class="d-flex align-items-center gap-1">
                                                            <i class="ph ph-calendar-dots"></i>
                                                            <span class="trending-year">Feb 2019</span>
                                                        </div>
                                                    </div>
                                                    <div class="iq-custom-select d-inline-block sea-epi mb-4">
                                                        <select name="cars"
                                                            class="form-control season-select select2-basic-single js-states">
                                                            <option value="season1">{{ __('streamEpisode.season') }} 1
                                                            </option>
                                                            <option value="season2">{{ __('streamEpisode.season') }} 2
                                                            </option>
                                                            <option value="season3">{{ __('streamEpisode.season') }} 3
                                                            </option>
                                                        </select>
                                                    </div>
                                                    <div class="position-relative swiper swiper-card" data-slide="4"
                                                        data-laptop="3" data-tab="2" data-mobile="2"
                                                        data-mobile-sm="1" data-autoplay="false" data-loop="false"
                                                        data-navigation="false" data-pagination="true">
                                                        <ul class="p-0 swiper-wrapper m-0  list-inline">
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => 'media/episode/s1e4-the-box.webp',
                                                                'id' => '01',
                                                                'showEpisod' => 'S01E01',
                                                                'episodDate' => 'October 1, 2022',
                                                                'episodTime' => '45m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '04',
                                                                'episodeTitlesText' => __(
                                                                'streamEpisode.episode44'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamEpisode.episode44_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => 'media/episode/s1e3-local-color.webp',
                                                                'id' => '02',
                                                                'showEpisod' => 'S01E02',
                                                                'episodDate' => 'October 8, 2022',
                                                                'episodTime' => '35m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '03',
                                                                'episodeTitlesText' => __(
                                                                'streamEpisode.episode45'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamEpisode.episode45_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => 'media/episode/s1e2-habeas-corpus.webp',
                                                                'id' => '03',
                                                                'showEpisod' => 'S01E03',
                                                                'episodDate' => 'October 15, 2022',
                                                                'episodTime' => '36m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '02',
                                                                'episodeTitlesText' => __(
                                                                'sstreamEpisode.episode46'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'sstreamEpisode.episode46_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => 'media/episode/s1e1-recap-severance.webp',
                                                                'id' => '04',
                                                                'showEpisod' => 'S01E04',
                                                                'episodDate' => 'October 22, 2022',
                                                                'episodTime' => '41m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '01',
                                                                'episodeTitlesText' => __(
                                                                'streamEpisode.episode48'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamEpisode.episode48_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                        </ul>
                                                        <div class="swiper-pagination d-block"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="trending-data-11" class="tab-pane fade" role="tabpanel"
                                                aria-labelledby="trending-data-tab-11" tabindex="0">
                                                <div
                                                    class=" trending-info align-items-center w-100 animated fadeInUp iq-ltr-direction text-center">
                                                    <h3
                                                        class="trending-text big-title text-uppercase texture-text mt-2">
                                                        {{ __('streamButtons.watch_trailer') }}
                                                    </h3>
                                                    <div class="episodes-contens mt-5">
                                                        <div
                                                            class="tab-watch-trailer-container d-inline-block rounded-3 overflow-hidden">
                                                            <div
                                                                class="tab-watch-trailer position-relative rounded-3 overflow-hidden">
                                                                <img src="{{ asset('frontend/images/tv-show/season/35.webp') }}"
                                                                    class="trailer-image" alt="trailer-image">
                                                                <a data-fslightbox="html5-video"
                                                                    href="{{ asset('frontend/images/video/trailer.mp4') }}"
                                                                    class="video-open playbtn text-descoration-none"
                                                                    tabindex="0">
                                                                    <svg version="1.1"
                                                                        xmlns="http://www.w3.org/2000/svg"
                                                                        xmlns:xlink="http://www.w3.org/1999/xlink"
                                                                        x="0px" y="0px" width="80px" height="80px"
                                                                        viewBox="0 0 213.7 213.7"
                                                                        enable-background="new 0 0 213.7 213.7"
                                                                        xml:space="preserve">
                                                                        <polygon class="triangle" fill="none"
                                                                            stroke-width="7" stroke-linecap="round"
                                                                            stroke-linejoin="round"
                                                                            stroke-miterlimit="10"
                                                                            points="73.5,62.5 148.5,105.8 73.5,149.1 ">
                                                                        </polygon>
                                                                        <circle class="circle" fill="none"
                                                                            stroke-width="7" stroke-linecap="round"
                                                                            stroke-linejoin="round"
                                                                            stroke-miterlimit="10" cx="106.8"
                                                                            cy="106.8" r="103.3">
                                                                        </circle>
                                                                    </svg>
                                                                    <span
                                                                        class="w-trailor text-uppercase">{{ __('streamButtons.watch_trailer') }}
                                                                    </span>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="trending-data-12" class="tab-pane fade" role="tabpanel"
                                                aria-labelledby="trending-data-tab-12" tabindex="0">
                                                <div
                                                    class=" trending-info align-items-center w-100 animated fadeInUp iq-ltr-direction">
                                                    <h3
                                                        class="trending-text big-title text-uppercase texture-text mb-5">
                                                        {{ __('sectionTitle.recommended_for_you') }}
                                                    </h3>
                                                    <div class="position-relative swiper swiper-card" data-slide="4"
                                                        data-laptop="3" data-tab="2" data-mobile="1"
                                                        data-mobile-sm="1" data-autoplay="false" data-loop="true"
                                                        data-navigation="false" data-pagination="true">
                                                        <ul class="p-0 swiper-wrapper m-0  list-inline">
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => 'media/episode/s2e2-filter.webp',
                                                                'id' => '06',
                                                                'showEpisod' => 'Seasons 1-2',
                                                                'episodDate' => 'October 1, 2022',
                                                                'episodTime' => '45m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '04',
                                                                'episodeTitlesText' => __(
                                                                'streamEpisode.episode48'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamEpisode.episode48_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => 'media/episode/s3e1-sunset.webp',
                                                                'id' => '07',
                                                                'showEpisod' => 'Seasons 1-3',
                                                                'episodDate' => 'October 8, 2022',
                                                                'episodTime' => '35m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '03',
                                                                'episodeTitlesText' => __(
                                                                'streamEpisode.episode49'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamEpisode.episode49_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => 'media/episode/s2e1-the-harvest.webp',
                                                                'id' => '08',
                                                                'showEpisod' => 'Seasons 1-4',
                                                                'episodDate' => 'October 15, 2022',
                                                                'episodTime' => '36m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '04',
                                                                'episodeTitlesText' => __(
                                                                'streamEpisode.episode50'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamEpisode.episode50_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => 'media/episode/s1e3-local-color.webp',
                                                                'id' => '09',
                                                                'showEpisod' => 'Seasons 1-5',
                                                                'episodDate' => 'October 22, 2022',
                                                                'episodTime' => '41m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '01',
                                                                'episodeTitlesText' => __(
                                                                'streamEpisode.episode51'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamEpisode.episode51_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                        </ul>
                                                        <div class="swiper-pagination d-block"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <li class="swiper-slide slider-big-img-4">
                                <div class="trending-tab-slider-image">
                                    <img src="{{ asset('frontend/images/media/assassins-creed.webp') }}"
                                        alt="trending-tab-slider-image">
                                </div>
                                <div class="tranding-block position-relative">
                                    <div class="trending-custom-tab">
                                        <div class="tab-title-info position-relative">
                                            <ul class="trending-pills iq-custom-tab flex-nowrap d-flex nav nav-pills justify-content-md-center align-items-center text-center list-inline"
                                                id="trending-tab-4" role="tablist">
                                                <li class="nav-item" role="presentation">
                                                    <a class="nav-link active" id="trending-data-tab-13"
                                                        data-bs-toggle="pill" data-bs-target="#trending-data-13"
                                                        aria-controls="trending-data-13" role="tab"
                                                        aria-selected="true">{{ __('streamTag.overview') }}</a>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <a class="nav-link" id="trending-data-tab-14"
                                                        data-bs-toggle="pill" data-bs-target="#trending-data-14"
                                                        aria-controls="trending-data-14" role="tab"
                                                        aria-selected="false">{{ __('streamTag.episodes') }}</a>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <a class="nav-link" id="trending-data-tab-15"
                                                        data-bs-toggle="pill" data-bs-target="#trending-data-15"
                                                        aria-controls="trending-data-15" role="tab"
                                                        aria-selected="false">{{ __('streamTag.trailers') }}</a>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <a class="nav-link" id="trending-data-tab-16"
                                                        data-bs-toggle="pill" data-bs-target="#trending-data-16"
                                                        aria-controls="trending-data-16" role="tab"
                                                        aria-selected="false">{{ __('streamTag.similar_like') }}</a>
                                                </li>
                                            </ul>
                                        </div>
                                        <div class="tab-content trending-content" id="trending-tab-4-content">
                                            <div id="trending-data-13" class="tab-pane fade show active"
                                                role="tabpanel" aria-labelledby="trending-data-tab-13"
                                                tabindex="0">
                                                <div
                                                    class=" trending-info align-items-center w-100 animated fadeInUp iq-ltr-direction">

                                                    <h1 class="trending-text big-title text-capitalize texture-text">
                                                        {{ __('streamMovies.assassins_creed') }}
                                                    </h1>
                                                    <div
                                                        class="d-flex align-items-center text-white text-detail flex-wrap gap-1 gap-md-3">
                                                        <span>3 {{ __('streamEpisode.season') }}</span>
                                                        <div class="d-flex align-items-center gap-1">
                                                            <i class="ph ph-calendar-dots"></i>
                                                            <span class="trending-year">
                                                                April 2023</span>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex align-items-center flex-wrap series mb-4 gap-3">
                                                        <a href="javascript:void(0);"><img
                                                                src="{{ asset('frontend/images/pages/trending-label.webp') }}"
                                                                class="img-fluid trending-label-img rounded-3"
                                                                alt="img"></a>
                                                        <span class="fw-bold">#2
                                                            {{ __('streamMovies.series_today') }}</span>
                                                    </div>
                                                    <p class="trending-dec line-count-4">{{ __('streamMovies.assassins_creed_desc') }}
                                                    </p>
                                                    <div class="p-btns">
                                                        <div class="iq-button">
                                                            <a href="tv-show-detail"
                                                                class="btn btn-primary text-uppercase position-relative rounded-3">
                                                                <div class="d-flex align-items-center gap-2">
                                                                    <span
                                                                        class="button-text">{{ __('streamButtons.play_now') }}</span>
                                                                    <i class="ph-fill ph-play"></i>
                                                                </div>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="trending-data-14" class="tab-pane fade" role="tabpanel"
                                                aria-labelledby="trending-data-tab-14" tabindex="0">
                                                <div
                                                    class=" trending-info align-items-center w-100 animated fadeInUp iq-ltr-direction">

                                                    <h1 class="trending-text big-title text-capitalize texture-text">
                                                        {{ __('streamMovies.assassins_creed') }}
                                                    </h1>
                                                    <div
                                                        class="d-flex align-items-center text-white text-detail flex-wrap mb-4 gap-3">
                                                        <span class="season_date">
                                                            2 {{ __('streamEpisode.season') }}
                                                        </span>
                                                        <div class="d-flex align-items-center gap-1">
                                                            <i class="ph ph-calendar-dots"></i>
                                                            <span class="trending-year">Feb 2019</span>
                                                        </div>
                                                    </div>
                                                    <div class="iq-custom-select d-inline-block sea-epi mb-4">
                                                        <select name="cars"
                                                            class="form-control season-select select2-basic-single js-states">
                                                            <option value="season1">{{ __('streamEpisode.season') }} 1
                                                            </option>
                                                            <option value="season2">{{ __('streamEpisode.season') }} 2
                                                            </option>
                                                            <option value="season3">{{ __('streamEpisode.season') }} 3
                                                            </option>
                                                        </select>
                                                    </div>
                                                    <div class="position-relative swiper swiper-card" data-slide="4"
                                                        data-laptop="3" data-tab="2" data-mobile="2"
                                                        data-mobile-sm="1" data-autoplay="false" data-loop="false"
                                                        data-navigation="false" data-pagination="true">
                                                        <ul class="p-0 swiper-wrapper m-0  list-inline">
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => 'media/episode/s1e3-rivals-and-revelations.webp',
                                                                'id' => '01',
                                                                'showEpisod' => 'S01E01',
                                                                'episodDate' => 'October 1, 2022',
                                                                'episodTime' => '45m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '04',
                                                                'episodeTitlesText' => __(
                                                                'streamEpisode.episode50'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamEpisode.episode50_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => 'media/episode/s1e2-hidden-allies.webp',
                                                                'id' => '02',
                                                                'showEpisod' => 'S01E02',
                                                                'episodDate' => 'October 8, 2022',
                                                                'episodTime' => '35m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '03',
                                                                'episodeTitlesText' => __(
                                                                'streamEpisode.episode52'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamEpisode.episode52_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => 'media/episode/s1e1-setting-sail.webp',
                                                                'id' => '03',
                                                                'showEpisod' => 'S01E03',
                                                                'episodDate' => 'October 15, 2022',
                                                                'episodTime' => '36m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '02',
                                                                'episodeTitlesText' => __(
                                                                'streamEpisode.episode53'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamEpisode.episode53_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => 'media/episode/s1e1-setting-sail.webp',
                                                                'id' => '04',
                                                                'showEpisod' => 'S01E04',
                                                                'episodDate' => 'October 22, 2022',
                                                                'episodTime' => '41m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '01',
                                                                'episodeTitlesText' => __(
                                                                'streamEpisode.episode54'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamEpisode.episode54_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                        </ul>
                                                        <div class="swiper-pagination d-block"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="trending-data-15" class="tab-pane fade" role="tabpanel"
                                                aria-labelledby="trending-data-tab-15" tabindex="0">
                                                <div
                                                    class=" trending-info align-items-center w-100 animated fadeInUp iq-ltr-direction text-center">
                                                    <h3
                                                        class="trending-text big-title text-uppercase texture-text mt-2">
                                                        {{ __('streamButtons.watch_trailer') }}
                                                    </h3>
                                                    <div class="episodes-contens mt-5">
                                                        <div
                                                            class="tab-watch-trailer-container d-inline-block rounded-3 overflow-hidden">
                                                            <div
                                                                class="tab-watch-trailer position-relative rounded-3 overflow-hidden">
                                                                <img src="{{ asset('frontend/images/video/trailer-poster.webp') }}"
                                                                    class="trailer-image" alt="trailer-image">
                                                                <a data-fslightbox="html5-video"
                                                                    href="{{ asset('frontend/images/video/trailer.mp4') }}"
                                                                    class="video-open playbtn text-descoration-none"
                                                                    tabindex="0">
                                                                    <svg version="1.1"
                                                                        xmlns="http://www.w3.org/2000/svg"
                                                                        xmlns:xlink="http://www.w3.org/1999/xlink"
                                                                        x="0px" y="0px" width="80px" height="80px"
                                                                        viewBox="0 0 213.7 213.7"
                                                                        enable-background="new 0 0 213.7 213.7"
                                                                        xml:space="preserve">
                                                                        <polygon class="triangle" fill="none"
                                                                            stroke-width="7" stroke-linecap="round"
                                                                            stroke-linejoin="round"
                                                                            stroke-miterlimit="10"
                                                                            points="73.5,62.5 148.5,105.8 73.5,149.1 ">
                                                                        </polygon>
                                                                        <circle class="circle" fill="none"
                                                                            stroke-width="7" stroke-linecap="round"
                                                                            stroke-linejoin="round"
                                                                            stroke-miterlimit="10" cx="106.8"
                                                                            cy="106.8" r="103.3">
                                                                        </circle>
                                                                    </svg>
                                                                    <span
                                                                        class="w-trailor text-uppercase">{{ __('streamButtons.watch_trailer') }}
                                                                    </span>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="trending-data-16" class="tab-pane fade" role="tabpanel"
                                                aria-labelledby="trending-data-tab-16" tabindex="0">
                                                <div
                                                    class=" trending-info align-items-center w-100 animated fadeInUp iq-ltr-direction">
                                                    <h3
                                                        class="trending-text big-title text-uppercase texture-text mb-5">
                                                        {{ __('sectionTitle.recommended_for_you') }}
                                                    </h3>
                                                    <div class="position-relative swiper swiper-card" data-slide="4"
                                                        data-laptop="3" data-tab="2" data-mobile="1"
                                                        data-mobile-sm="1" data-autoplay="false" data-loop="true"
                                                        data-navigation="false" data-pagination="true">
                                                        <ul class="p-0 swiper-wrapper m-0  list-inline">
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => 'media/episode/s1e3-rivals-and-revelations.webp',
                                                                'id' => '06',
                                                                'showEpisod' => 'Seasons 1-2',
                                                                'episodDate' => 'October 1, 2022',
                                                                'episodTime' => '45m',
                                                                'episodTime' => '41m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '04',
                                                                'episodeTitlesText' => __(
                                                                'streamEpisode.episode4'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamEpisode.episode4_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => 'media/episode/s1e2-hidden-allies.webp',
                                                                'id' => '07',
                                                                'showEpisod' => 'Seasons 1-3',
                                                                'episodDate' => 'October 8, 2022',
                                                                'episodTime' => '35m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '03',
                                                                'episodeTitlesText' => __(
                                                                'streamEpisode.episode5'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamEpisode.episode5_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => 'media/episode/s1e1-setting-sail.webp',
                                                                'id' => '08',
                                                                'showEpisod' => 'Seasons 1-4',
                                                                'episodDate' => 'October 15, 2022',
                                                                'episodTime' => '36m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '02',
                                                                'episodeTitlesText' => __(
                                                                'streamEpisode.episode6'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamEpisode.episode6_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => 'media/episode/s1e1-setting-sail.webp',
                                                                'id' => '09',
                                                                'showEpisod' => 'Seasons 1-5',
                                                                'episodDate' => 'October 22, 2022',
                                                                'episodTime' => '41m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '01',
                                                                'episodeTitlesText' => __(
                                                                'streamEpisode.episode7'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamEpisode.episode7_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                        </ul>
                                                        <div class="swiper-pagination d-block"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <li class="swiper-slide slider-big-img-5">
                                <div class="trending-tab-slider-image">
                                    <img src="{{ asset('frontend/images/media/vikings.webp') }}"
                                        alt="trending-tab-slider-image">
                                </div>
                                <div class="tranding-block position-relative">
                                    <div class="trending-custom-tab">
                                        <div class="tab-title-info position-relative">
                                            <ul class="trending-pills iq-custom-tab flex-nowrap d-flex nav nav-pills justify-content-md-center align-items-center text-center list-inline"
                                                id="trending-tab-5" role="tablist">
                                                <li class="nav-item" role="presentation">
                                                    <a class="nav-link active" id="trending-data-tab-17"
                                                        data-bs-toggle="pill" data-bs-target="#trending-data-17"
                                                        aria-controls="trending-data-17" role="tab"
                                                        aria-selected="true">{{ __('streamTag.overview') }}</a>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <a class="nav-link" id="trending-data-tab-18"
                                                        data-bs-toggle="pill" data-bs-target="#trending-data-18"
                                                        aria-controls="trending-data-18" role="tab"
                                                        aria-selected="false">{{ __('streamTag.episodes') }}</a>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <a class="nav-link" id="trending-data-tab-19"
                                                        data-bs-toggle="pill" data-bs-target="#trending-data-19"
                                                        aria-controls="trending-data-19" role="tab"
                                                        aria-selected="false">{{ __('streamTag.trailers') }}</a>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <a class="nav-link" id="trending-data-tab-20"
                                                        data-bs-toggle="pill" data-bs-target="#trending-data-20"
                                                        aria-controls="trending-data-20" role="tab"
                                                        aria-selected="false">{{ __('streamTag.similar_like') }}</a>
                                                </li>
                                            </ul>
                                        </div>
                                        <div class="tab-content trending-content" id="trending-tab-5-content">
                                            <div id="trending-data-17" class="tab-pane fade show active"
                                                role="tabpanel" aria-labelledby="trending-data-tab-17"
                                                tabindex="0">
                                                <div
                                                    class=" trending-info align-items-center w-100 animated fadeInUp iq-ltr-direction">

                                                    <h1 class="trending-text big-title text-capitalize texture-text">
                                                        {{ __('streamMovies.vikings') }}
                                                    </h1>
                                                    <div
                                                        class="d-flex align-items-center text-white text-detail flex-wrap gap-1 gap-md-3">
                                                        <span>3 {{ __('streamEpisode.season') }}</span>
                                                        <div class="d-flex align-items-center gap-1">
                                                            <i class="ph ph-calendar-dots"></i>
                                                            <span class="trending-year">
                                                                November 2023</span>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex align-items-center flex-wrap series mb-4 gap-3">
                                                        <a href="javascript:void(0);"><img
                                                                src="{{ asset('frontend/images/pages/trending-label.webp') }}"
                                                                class="img-fluid
                                                                trending-label-img rounded-3"
                                                                alt="img"></a>
                                                        <span class="fw-bold">#2
                                                            {{ __('streamMovies.series_today') }}</span>
                                                    </div>
                                                    <p class="trending-dec line-count-4">{{ __('streamMovies.vikings_desc') }}
                                                    </p>
                                                    <div class="p-btns">
                                                        <div class="iq-button">
                                                            <a href="tv-show-detail"
                                                                class="btn btn-primary text-uppercase position-relative rounded-3">
                                                                <div class="d-flex align-items-center gap-2">
                                                                    <span
                                                                        class="button-text">{{ __('streamButtons.play_now') }}</span>
                                                                    <i class="ph-fill ph-play"></i>
                                                                </div>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="trending-data-18" class="tab-pane fade" role="tabpanel"
                                                aria-labelledby="trending-data-tab-18" tabindex="0">
                                                <div
                                                    class=" trending-info align-items-center w-100 animated fadeInUp iq-ltr-direction">

                                                    <h1 class="trending-text big-title text-capitalize texture-text">
                                                        {{ __('streamMovies.vikings') }}
                                                    </h1>
                                                    <div
                                                        class="d-flex align-items-center text-white text-detail flex-wrap mb-4 gap-3">
                                                        <span class="season_date">
                                                            2 {{ __('streamEpisode.season') }}
                                                        </span>
                                                        <div class="d-flex align-items-center gap-1">
                                                            <i class="ph ph-calendar-dots"></i>
                                                            <span class="trending-year">Feb 2019</span>
                                                        </div>
                                                    </div>
                                                    <div class="iq-custom-select d-inline-block sea-epi mb-4">
                                                        <select name="cars"
                                                            class="form-control season-select select2-basic-single js-states">
                                                            <option value="season1">{{ __('streamEpisode.season') }} 1
                                                            </option>
                                                            <option value="season2">{{ __('streamEpisode.season') }} 2
                                                            </option>
                                                            <option value="season3">{{ __('streamEpisode.season') }} 3
                                                            </option>
                                                        </select>
                                                    </div>
                                                    <div class="position-relative swiper swiper-card" data-slide="4"
                                                        data-laptop="3" data-tab="2" data-mobile="2"
                                                        data-mobile-sm="1" data-autoplay="false" data-loop="true"
                                                        data-navigation="false" data-pagination="true">
                                                        <ul class="p-0 swiper-wrapper m-0  list-inline">
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => 'media/episode/s1e5-Lost-Souls.webp',
                                                                'id' => '01',
                                                                'episodTime' => '45m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '04',
                                                                'episodeTitlesText' => __(
                                                                'streamEpisode.episode3'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamEpisode.episode3_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => 'media/episode/s1e4-the-signal.webp',
                                                                'id' => '02',
                                                                'episodTime' => '35m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '03',
                                                                'episodeTitlesText' => __(
                                                                'streamEpisode.episode4'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamEpisode.episode4_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => 'media/episode/s1e3-All-Change.webp',
                                                                'id' => '03',
                                                                'episodTime' => '36m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '02',
                                                                'episodeTitlesText' => __(
                                                                'streamEpisode.episode5'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamEpisode.episode5_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => 'media/episode/s1e2-king-of-kings.webp',
                                                                'id' => '04',
                                                                'episodTime' => '41m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '01',
                                                                'episodeTitlesText' => __(
                                                                'streamEpisode.episode6'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamEpisode.episode6_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                        </ul>
                                                        <div class="swiper-pagination d-block"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="trending-data-19" class="tab-pane fade" role="tabpanel"
                                                aria-labelledby="trending-data-tab-19" tabindex="0">
                                                <div
                                                    class=" trending-info align-items-center w-100 animated fadeInUp iq-ltr-direction text-center">
                                                    <h3
                                                        class="trending-text big-title text-uppercase texture-text mt-2">
                                                        {{ __('streamButtons.watch_trailer') }}
                                                    </h3>
                                                    <div class="episodes-contens mt-5">
                                                        <div
                                                            class="tab-watch-trailer-container d-inline-block rounded-3 overflow-hidden">
                                                            <div
                                                                class="tab-watch-trailer position-relative rounded-3 overflow-hidden">
                                                                <img src="{{ asset('frontend/images/video/trailer-poster.webp') }}"
                                                                    class="trailer-image" alt="trailer-image">
                                                                <a data-fslightbox="html5-video"
                                                                    href="{{ asset('frontend/images/video/trailer.mp4') }}"
                                                                    class="video-open playbtn text-descoration-none"
                                                                    tabindex="0">
                                                                    <svg version="1.1"
                                                                        xmlns="http://www.w3.org/2000/svg"
                                                                        xmlns:xlink="http://www.w3.org/1999/xlink"
                                                                        x="0px" y="0px" width="80px" height="80px"
                                                                        viewBox="0 0 213.7 213.7"
                                                                        enable-background="new 0 0 213.7 213.7"
                                                                        xml:space="preserve">
                                                                        <polygon class="triangle" fill="none"
                                                                            stroke-width="7" stroke-linecap="round"
                                                                            stroke-linejoin="round"
                                                                            stroke-miterlimit="10"
                                                                            points="73.5,62.5 148.5,105.8 73.5,149.1 ">
                                                                        </polygon>
                                                                        <circle class="circle" fill="none"
                                                                            stroke-width="7" stroke-linecap="round"
                                                                            stroke-linejoin="round"
                                                                            stroke-miterlimit="10" cx="106.8"
                                                                            cy="106.8" r="103.3">
                                                                        </circle>
                                                                    </svg>
                                                                    <span
                                                                        class="w-trailor text-uppercase">{{ __('streamButtons.watch_trailer') }}
                                                                    </span>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="trending-data-20" class="tab-pane fade" role="tabpanel"
                                                aria-labelledby="trending-data-tab-20" tabindex="0">
                                                <div
                                                    class=" trending-info align-items-center w-100 animated fadeInUp iq-ltr-direction">
                                                    <h3
                                                        class="trending-text big-title text-uppercase texture-text mb-5">
                                                        {{ __('sectionTitle.recommended_for_you') }}
                                                    </h3>
                                                    <div class="position-relative swiper swiper-card" data-slide="4"
                                                        data-laptop="3" data-tab="2" data-mobile="1"
                                                        data-mobile-sm="1" data-autoplay="false" data-loop="true"
                                                        data-navigation="false" data-pagination="true">
                                                        <ul class="p-0 swiper-wrapper m-0  list-inline">
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => 'media/episode/s2e3-the-raft-of-the-medusa.webp',
                                                                'id' => '06',
                                                                'showEpisod' => 'Seasons 1-2',
                                                                'episodDate' => 'October 1, 2022',
                                                                'episodTime' => '45m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '04',
                                                                'episodeTitlesText' => __(
                                                                'streamEpisode.episode26'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamEpisode.episode26_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => 'media/episode/s2e2-its-only-magic.webp',
                                                                'id' => '07',
                                                                'showEpisod' => 'Seasons 1-3',
                                                                'episodDate' => 'October 8, 2022',
                                                                'episodTime' => '35m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '03',
                                                                'episodeTitlesText' => __(
                                                                'streamEpisode.episode27'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamEpisode.episode27_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => 'media/episode/s2e1-the-signal.webp',
                                                                'id' => '08',
                                                                'showEpisod' => 'Seasons 1-4',
                                                                'episodDate' => 'October 15, 2022',
                                                                'episodTime' => '36m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '02',
                                                                'episodeTitlesText' => __(
                                                                'streamEpisode.episode28'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamEpisode.episode28_desc'),
                                                                ]
                                                                )
                                                            </li>
                                                            <li class="swiper-slide">
                                                                @include(
                                                                'frontend::components.cards.episode-card',
                                                                [
                                                                'episodePath' => 'episode',
                                                                'showImg' => 'media/episode/s2e2-its-only-magic.webp',
                                                                'id' => '09',
                                                                'showEpisod' => 'Seasons 1-5',
                                                                'episodDate' => 'October 22, 2022',
                                                                'episodTime' => '41m',
                                                                'episodeTitles' => true,
                                                                'episodeNumber' => '01',
                                                                'episodeTitlesText' => __(
                                                                'streamEpisode.episode29'),
                                                                'episodeDetail' => true,
                                                                'episodeDetailText' => __(
                                                                'streamEpisode.episode29_desc'),
                                                                ]
                                                                )
                                                            </li>

                                                        </ul>
                                                        <div class="swiper-pagination d-block"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>