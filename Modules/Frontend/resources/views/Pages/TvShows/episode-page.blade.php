@extends('frontend::layouts.master', ['isSwiperSlider' => true, 'isVideoJs' => true, 'bodyClass' => 'custom-header-relative', 'isSelect2' => true])

@section('content')
    <div class="iq-main-slider site-video position-relative">
        <video id="my-video" poster="{{ asset('frontend/images/media/vikings.webp') }}"
            class="my-video video-js vjs-big-play-centered w-100" loop autoplay muted preload="auto"
            data-setup='{
                "techOrder": ["youtube"],
                "sources": [{
                    "type": "video/youtube",
                    "src": "https://www.youtube.com/watch?v=spGSAeqxVUc"
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
    <div class="details-part">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="trending-info  pt-0 pb-0">
                        <div class="row justify-content-between">
                            <div class="col-xl-12 col-12 mb-auto">
                                @include('frontend::components.cards.movie-description', [
                                    'moveName' => __('streamEpisode.episode3'),
                                    'movieReleased' => '2025',
                                    'movieViews' => '1.2K' . __('frontendplaylist.views'),
                                    'isNotimdbRating' => true,
                                    'movieDuration' => '30min',
                                    'isNotTVShowbadge' => true,
                                    'isNotstartWatching' => true,
                                    'isNotwatchList' => true,
                                ])
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="overflow-hidden">
            <div class="show-episode section-padding">
                <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
                    <h5 class="main-title text-capitalize mb-0 fw-medium">{{ __('header.episodes') }}</h5>
                </div>
                <ul class="nav nav-pills custom-tab-slider episode-nav-btn gap-3 mb-4 pb-2" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active show" data-bs-toggle="pill" href="#season-1" role="tab"
                            aria-selected="true">
                            {{ __('streamEpisode.season') }} 1
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="pill" href="#season-2" role="tab" aria-selected="false">
                            {{ __('streamEpisode.season') }} 2
                        </a>
                    </li>
                </ul>
                <div class="tab-content">
                    <div id="season-1" class="tab-pane animated fadeInUp active show" role="tabpanel">
                        <div class="card-style-slider">
                            <div class="position-relative swiper swiper-card mt-4 mb-5 overflow-hidden" data-slide="5"
                                data-laptop="5" data-tab="2" data-mobile="2" data-mobile-sm="1" data-autoplay="false"
                                data-loop="false">
                                <div class="p-0 swiper-wrapper m-0 list-inline">
                                    <div class="swiper-slide">
                                        @include('frontend::components.cards.episode-card', [
                                            'episodePath' => 'episode',
                                            'showImg' => 'media/episode/s1e1-the-buddha.webp',
                                            'id' => '01',
                                            'showEpisod' => 'S01E01',
                                            'episodDate' => 'October 22, 2022',
                                            'episodTime' => '45m',
                                            'episodeNumber' => '01',
                                            'episodTitle' => __('streamEpisode.episode65'),
                                            'episodeTitlesText' => __('streamEpisode.episode65'),
                                            'episodeDetailText' => __('streamEpisode.episode65_desc'),
                                        ])
                                    </div>
                                    <div class="swiper-slide">
                                        @include('frontend::components.cards.episode-card', [
                                            'episodePath' => 'episode',
                                            'showImg' => 'media/episode/s1e2-king-of-kings.webp',
                                            'id' => '02',
                                            'showEpisod' => 'S01E02',
                                            'episodDate' => 'October 23, 2022',
                                            'episodTime' => '35m',
                                            'episodeNumber' => '02',
                                            'episodTitle' => __('streamEpisode.episode60'),
                                            'episodeTitlesText' => __('streamEpisode.episode60'),
                                            'episodeDetailText' => __('streamEpisode.episode60_desc'),
                                        ])
                                    </div>
                                    <div class="swiper-slide">
                                        @include('frontend::components.cards.episode-card', [
                                            'episodePath' => 'episode',
                                            'showImg' => 'media/episode/s1e3-All-Change.webp',
                                            'id' => '03',
                                            'showEpisod' => 'S01E03',
                                            'episodDate' => 'October 24, 2022',
                                            'episodTime' => '36m',
                                            'episodeNumber' => '03',
                                            'episodTitle' => __('streamEpisode.episode61'),
                                            'episodeTitlesText' => __('streamEpisode.episode61'),
                                            'episodeDetailText' => __('streamEpisode.episode61_desc'),
                                        ])
                                    </div>
                                    <div class="swiper-slide">
                                        @include('frontend::components.cards.episode-card', [
                                            'episodePath' => 'episode',
                                            'showImg' => 'media/episode/s1e4-the-signal.webp',
                                            'id' => '04',
                                            'showEpisod' => 'S01E04',
                                            'episodDate' => 'October 25, 2020',
                                            'episodTime' => '41m',
                                            'episodeNumber' => '04',
                                            'episodTitle' => __('streamEpisode.episode62'),
                                            'episodeTitlesText' => __('streamEpisode.episode62'),
                                            'episodeDetailText' => __('streamEpisode.episode62_desc'),
                                        ])
                                    </div>
                                    <div class="swiper-slide">
                                        @include('frontend::components.cards.episode-card', [
                                            'episodePath' => 'episode',
                                            'showImg' => 'media/episode/s1e5-Lost-Souls.webp',
                                            'id' => '05',
                                            'showEpisod' => 'S01E05',
                                            'episodDate' => 'October 29, 2020',
                                            'episodTime' => '41m',
                                            'episodeNumber' => '05',
                                            'episodTitle' => __('streamEpisode.episode63'),
                                            'episodeTitlesText' => __('streamEpisode.episode63'),
                                            'episodeDetailText' => __('streamEpisode.episode63_desc'),
                                        ])
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="season-2" class="tab-pane animated fadeInUp" role="tabpanel">
                        <div class="card-style-slider">
                            <div class="position-relative swiper swiper-card mt-4 mb-5 overflow-hidden" data-slide="5"
                                data-laptop="5" data-tab="2" data-mobile="2" data-mobile-sm="1" data-autoplay="false"
                                data-loop="false">
                                <div class="p-0 swiper-wrapper m-0 list-inline">
                                    <div class="swiper-slide">
                                        @include('frontend::components.cards.episode-card', [
                                            'episodePath' => 'episode',
                                            'showImg' => 'media/episode/s2e2-its-only-magic.webp',
                                            'id' => '01',
                                            'showEpisod' => 'S02E01',
                                            'episodDate' => 'October 1, 2021',
                                            'episodTime' => '45m',
                                            'episodeNumber' => '01',
                                            'episodTitle' => __('streamEpisode.episode64'),
                                            'episodeTitlesText' => __('streamEpisode.episode64'),
                                            'episodeDetailText' => __('streamEpisode.episode64_desc'),
                                        ])
                                    </div>
                                    <div class="swiper-slide">
                                        @include('frontend::components.cards.episode-card', [
                                            'episodePath' => 'episode',
                                            'showImg' => 'media/episode/s2e3-the-raft-of-the-medusa.webp',
                                            'id' => '02',
                                            'showEpisod' => 'S02E02',
                                            'episodDate' => 'October 8, 2021',
                                            'episodTime' => '35m',
                                            'episodeNumber' => '02',
                                            'episodTitle' => __('streamEpisode.episode65'),
                                            'episodeTitlesText' => __('streamEpisode.episode65'),
                                            'episodeDetailText' => __('streamEpisode.episode65_desc'),
                                        ])
                                    </div>
                                    <div class="swiper-slide">
                                        @include('frontend::components.cards.episode-card', [
                                            'episodePath' => 'episode',
                                            'showImg' => 'media/episode/s2e4-the-final-straw.webp',
                                            'id' => '03',
                                            'showEpisod' => 'S02E03',
                                            'episodDate' => 'October 15, 2021',
                                            'episodTime' => '41m',
                                            'episodeNumber' => '03',
                                            'episodTitle' => __('streamEpisode.episode66'),
                                            'episodeTitlesText' => __('streamEpisode.episode66'),
                                            'episodeDetailText' => __('streamEpisode.episode66_desc'),
                                        ])
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            {{-- Latest Episodes End --}}


            <!-- More Like This Start -->

            <section class="upcomimg-block">
                <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
                    <h5 class="main-title text-capitalize mb-0">{{ __('sectionTitle.more_like_this') }}</h5>
                </div>
                <div class="card-style-slider">
                    <div class="position-relative swiper swiper-card" data-slide="6" data-laptop="6" data-tab="3"
                        data-mobile="2" data-mobile-sm="2" data-autoplay="false" data-loop="true" data-navigation="true"
                        data-pagination="true">
                        <ul class="p-0 swiper-wrapper m-0  list-inline">
                            <li class="swiper-slide">
                                @include('frontend::components.cards.card-style', [
                                    'cardImage' => 'media/migration-portrait.webp',
                                    'cardTitle' => __('streamMovies.migration'),
                                    'movieTime' => '2hr : 30mins',
                                    'productPremium' => true,
                                    'cardLang' => 'English',
                                ])
                            </li>
                            <li class="swiper-slide">
                                @include('frontend::components.cards.card-style', [
                                    'cardImage' => 'media/tianic.webp',
                                    'cardTitle' => __('streamMovies.titanic'),
                                    'movieTime' => '1hr : 30mins',
                                    'productPremium' => true,
                                    'cardLang' => 'English',
                                ])
                            </li>
                            <li class="swiper-slide">
                                @include('frontend::components.cards.card-style', [
                                    'cardImage' => 'media/the-crew-portrait.webp',
                                    'cardTitle' => __('streamMovies.the_real_crew'),
                                    'movieTime' => '1hr : 55mins',
                                    'productPremium' => true,
                                    'cardLang' => 'English',
                                ])
                            </li>
                            <li class="swiper-slide">
                                @include('frontend::components.cards.card-style', [
                                    'cardImage' => 'media/midnightHunter-portrait.webp',
                                    'cardTitle' => __('streamMovies.midnight_hunter'),
                                    'movieTime' => '1hr : 22mins',
                                    'cardLang' => 'English',
                                ])
                            </li>
                            <li class="swiper-slide">
                                @include('frontend::components.cards.card-style', [
                                    'cardImage' => 'media/spiderman.webp',
                                    'cardTitle' => __('streamEpisode.episode11'),
                                    'movieTime' => '1hr : 30mins',
                                    'cardLang' => 'English',
                                ])
                            </li>
                            <li class="swiper-slide">
                                @include('frontend::components.cards.card-style', [
                                    'cardImage' => 'media/kung-fu-panda-portrait.webp',
                                    'cardTitle' => __('streamMovies.masters_of_kung_fu_legend'),
                                    'movieTime' => '2hr : 45mins',
                                    'productPremium' => true,
                                    'cardLang' => 'English',
                                ])
                            </li>
                            <li class="swiper-slide">
                                @include('frontend::components.cards.card-style', [
                                    'cardImage' => 'media/hanuman-portrait.webp',
                                    'cardTitle' => __('streamMovies.the_epic_journey_of_hanuman'),
                                    'movieTime' => '1hr : 45mins',
                                    'cardLang' => 'English',
                                ])
                            </li>
                            <li class="swiper-slide">
                                @include('frontend::components.cards.card-style', [
                                    'cardImage' => 'media/krishna-portrait.webp',
                                    'cardTitle' => __('streamMovies.vasudeva_the_eternal_journey'),
                                    'movieTime' => '2hr : 30mins',
                                    'cardLang' => 'English',
                                ])
                            </li>
                            <li class="swiper-slide">
                                @include('frontend::components.cards.card-style', [
                                    'cardImage' => 'media/minions-portrait.webp',
                                    'cardTitle' => __('streamMovies.minions'),
                                    'cardLang' => 'English',
                                ])
                            </li>
                            <li class="swiper-slide">
                                @include('frontend::components.cards.card-style', [
                                    'cardImage' => 'media/pirates-ofdayones-orignal.webp',
                                    'cardTitle' => __('streamMovies.pirates_of_day_one'),
                                    'cardLang' => 'English',
                                ])
                            </li>
                        </ul>
                        <div class="swiper-button swiper-button-next"></div>
                        <div class="swiper-button swiper-button-prev"></div>
                    </div>
                </div>
            </section>
            <!-- More Like This End -->

            <section class="mb-5">
                @include('frontend::components.widgets.details-review')
            </section>
        </div>
    </div>

    {{-- Modal --}}
    @include('frontend::components.widgets.details-description-modal', [
        'isEpisodepage' => true,
        'isnotmovieTitle' => true,
        'movieName' => __('streamMovies.hidden_allies'),
        'movieDuration' => '30min',
        'year' => '2025',
        'isnotimdbRating' => true,
        'views' => '1.2K' . __('frontendplaylist.views'),
        
    ])

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}
@endsection
