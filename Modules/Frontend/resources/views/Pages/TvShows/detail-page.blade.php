@extends('frontend::layouts.master', [
    'isSwiperSlider' => true,
    'isVideoJs' => true,
    'isSelect2' => true,
    'bodyClass' => 'custom-header-relative',
])

@section('content')
    <div class="position-relative">
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

        <div class="movie-detail-part position-relative">
            <div class="trending-info pt-0 pb-0">
                <div class="details-parts">
                    @include('frontend::components.cards.movie-description', [
                        'moveName' => __('streamMovies.vikings'),
                        'isNotTVShowbadge' => true,
                        'movieReleased' => '2025',
                        'movieDuration' => '1hr : 10m',
                        'movieViews' => '141' . __('streamTag.views'),
                        'imdbRating' => '5',
                        'movieLanguage' => 'english (UK)',
                        'videoUrl' => '/episode',
                    ])
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

                <section class="recommended-block">
                    <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
                        <h4 class="main-title text-capitalize mb-0">{{ __('sectionTitle.recommended') }}</h4>
                    </div>
                    <div class="card-style-slider">
                        <div class="position-relative swiper swiper-card" data-slide="6" data-laptop="6" data-tab="3"
                            data-mobile="2" data-mobile-sm="2" data-autoplay="false" data-loop="false"
                            data-navigation="true" data-pagination="true">
                            <ul class="p-0 swiper-wrapper m-0  list-inline">
                                <li class="swiper-slide">
                                    @include('frontend::components.cards.card-style', [
                                    'cardImage' => 'media/midnightHunter-portrait.webp',
                                    'cardTitle' => __('streamMovies.midnight_hunter'),
                                    'cardLang' => 'English',
                                    'cardPath' => 'tv-show-detail',
                                    ])

                                </li>
                                <li class="swiper-slide">
                                    @include('frontend::components.cards.card-style', [
                                    'cardImage' => 'media/kung-fu-panda-portrait.webp',
                                    'cardTitle' => __('streamMovies.kung_fu_panda'),
                                    'cardLang' => 'English',
                                    'cardPath' => 'tv-show-detail',
                                    'productPremium' => true,
                                    ])

                                </li>
                                <li class="swiper-slide">
                                    @include('frontend::components.cards.card-style', [
                                    'cardImage' => 'media/hanuman-portrait.webp',
                                    'cardTitle' => __('streamMovies.hanuman'),
                                    'cardLang' => 'English',
                                    'cardPath' => 'tv-show-detail',
                                    ])

                                </li>
                                <li class="swiper-slide">
                                    @include('frontend::components.cards.card-style', [
                                    'cardImage' => 'media/krishna-portrait.webp',
                                    'cardTitle' => __('streamMovies.krishna'),
                                    'isnotlangCard' => true,
                                    'cardPath' => 'tv-show-detail',
                                    'productPremium' => true,
                                    ])

                                </li>
                                <li class="swiper-slide">
                                    @include('frontend::components.cards.card-style', [
                                    'cardImage' => 'media/minions-portrait.webp',
                                    'cardTitle' => __('streamMovies.minions'),
                                    'isnotlangCard' => true,
                                    'cardPath' => 'tv-show-detail',
                                    'productPremium' => true,
                                    ])

                                </li>
                                <li class="swiper-slide">
                                    @include('frontend::components.cards.card-style', [
                                    'cardImage' => 'media/breaking-bad-portrait.webp',
                                    'cardTitle' => __('streamMovies.breaking_bad'),
                                    'cardLang' => 'English',
                                    'cardPath' => 'tv-show-detail',
                                    ])

                                </li>
                                <li class="swiper-slide">
                                    @include('frontend::components.cards.card-style', [
                                    'cardImage' => 'media/deadpool-portrait.webp',
                                    'cardTitle' => __('streamMovies.deadpool'),
                                    'cardLang' => 'English',
                                    'cardPath' => 'tv-show-detail',
                                    ])

                                </li>
                            </ul>
                            <div class="swiper-button swiper-button-next"></div>
                            <div class="swiper-button swiper-button-prev"></div>
                        </div>
                    </div>
                </section>

                {{-- Starring start --}}
                <div class="favourite-person-block section-wraper">
                    <div class="d-flex align-items-center justify-content-between  px-1 mb-2 pb-1 mb-md-4 pb-md-0">
                        <h4 class="main-title text-capitalize mb-0 fw-medium">{{ __('sectionTitle.starring') }}</h4>
                        <a href="{{ route('frontend.all_personality') }}"
                            class="text-primary iq-view-all text-decoration-none">{{ __('streamButtons.view_all') }}</a>
                    </div>
                    <div class="position-relative swiper swiper-card" data-slide="11" data-laptop="11" data-tab="4" data-mobile="2"
                        data-mobile-sm="2" data-autoplay="false" data-loop="true" data-navigation="true" data-pagination="true">
                        <ul class="p-0 swiper-wrapper m-0  list-inline personality-card">
                            <li class="swiper-slide">
                                @include('frontend::components.cards.personality-card', [
                                'castImage' => 'olivia-foster.webp',
                                'castTitle' => __('favouritePersonalities.olivia_foster'),
                                'castCategory' => __('favouritePersonalities.actress'),
                                ])

                            </li>
                            <li class="swiper-slide">
                                @include('frontend::components.cards.personality-card', [
                                'castImage' => 'leena-burton.webp',
                                'castTitle' => __('favouritePersonalities.Leena_Burton'),
                                'castCategory' => __('favouritePersonalities.actress'),
                                ])

                            </li>
                            <li class="swiper-slide">
                                @include('frontend::components.cards.personality-card', [
                                'castImage' => 'ryan-pierce.webp',
                                'castTitle' => __('favouritePersonalities.Ryan_Pierce'),
                                'castCategory' => __('favouritePersonalities.actor'),
                                ])

                            </li>
                            <li class="swiper-slide">
                                @include('frontend::components.cards.personality-card', [
                                'castImage' => 'michael-fox.webp',
                                'castTitle' => __('favouritePersonalities.michael_fox'),
                                'castCategory' => __('favouritePersonalities.actor'),
                                ])

                            </li>
                            <li class="swiper-slide">
                                @include('frontend::components.cards.personality-card', [
                                'castImage' => 'ruby-scott.webp',
                                'castTitle' => __('favouritePersonalities.ruby_scott'),
                                'castCategory' => __('favouritePersonalities.actor'),
                                ])

                            </li>
                            <li class="swiper-slide">
                                @include('frontend::components.cards.personality-card', [
                                'castImage' => 'maxwell-carter.webp',
                                'castTitle' => __('favouritePersonalities.maxwell_carter'),
                                'castCategory' => __('favouritePersonalities.actor'),
                                ])

                            </li>
                            <li class="swiper-slide">
                                @include('frontend::components.cards.personality-card', [
                                'castImage' => 'mark-smith.webp',
                                'castTitle' => __('favouritePersonalities.mark_smith'),
                                'castCategory' => __('favouritePersonalities.director'),
                                ])

                            </li>
                            <li class="swiper-slide">
                                @include('frontend::components.cards.personality-card', [
                                'castImage' => 'ava-monroe.webp',
                                'castTitle' => __('favouritePersonalities.ava_monroe'),
                                'castCategory' => __('favouritePersonalities.producer'),
                                ])

                            </li>
                            <li class="swiper-slide">
                                @include('frontend::components.cards.personality-card', [
                                'castImage' => 'jack-nicholson.webp',
                                'castTitle' => __('favouritePersonalities.jack_nicholson'),
                                'castCategory' => 'actor',
                                ])

                            </li>
                            <li class="swiper-slide">
                                @include('frontend::components.cards.personality-card', [
                                'castImage' => 'charles-melton.webp',
                                'castTitle' => __('favouritePersonalities.charles_melton'),
                                'castCategory' => __('favouritePersonalities.actor'),
                                ])

                            </li>
                            <li class="swiper-slide">
                                @include('frontend::components.cards.personality-card', [
                                'castImage' => 'jeff-bridges.webp',
                                'castTitle' => __('favouritePersonalities.jeff_bridges'),
                                'castCategory' => __('favouritePersonalities.actor'),
                                ])

                            </li>
                            <li class="swiper-slide">
                                @include('frontend::components.cards.personality-card', [
                                'castImage' => 'james-stewart.webp',
                                'castTitle' => __('favouritePersonalities.james_stewart'),
                                'castCategory' => __('favouritePersonalities.actor'),
                                ])
                            </li>
                            <li class="swiper-slide">
                                @include('frontend::components.cards.personality-card', [
                                'castImage' => 'jordan-grant.webp',
                                'castTitle' => __('favouritePersonalities.jordan_grant'),
                                'castCategory' => __('favouritePersonalities.actor'),
                                ])
                            </li>
                        </ul>
                        <div class="d-none d-lg-block">
                            <div class="swiper-button swiper-button-next"></div>
                            <div class="swiper-button swiper-button-prev"></div>
                        </div>
                    </div>
                </div>
                {{-- Starring End --}}

                {{-- Crew start --}}
                <div class="favourite-person-block">
                    <section class="overflow-hidden">
                        <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
                            <h4 class="main-title text-capitalize mb-0">{{__('sectionTitle.crew')}}</h4>
                        </div>
                        <div class="position-relative swiper swiper-card" data-slide="11" data-laptop="11" data-tab="4"
                            data-mobile="2" data-mobile-sm="2" data-autoplay="false" data-loop="true" data-navigation="true"
                            data-pagination="true">
                            <ul class="p-0 swiper-wrapper m-0  list-inline personality-card">
                                <li class="swiper-slide">
                                    @include('frontend::components.cards.personality-card', [
                                    'castImage' => 'maria-rodriguez.webp',
                                    'castTitle' => __('favouritePersonalities.maria_rodriguez'),
                                    'castCategory' => __('favouritePersonalities.director'),
                                    ])
                                </li>
                                <li class="swiper-slide">
                                    @include('frontend::components.cards.personality-card', [
                                    'castImage' => 'oliver-turner.webp',
                                    'castTitle' => __('favouritePersonalities.Olivia_Foster'),
                                    'castCategory' => __('favouritePersonalities.director'),
                                    ])
                                </li>
                                <li class="swiper-slide">
                                    @include('frontend::components.cards.personality-card', [
                                    'castImage' => 'john-abraham.webp',
                                    'castTitle' => __('favouritePersonalities.john_abraham'),
                                    'castCategory' => __('favouritePersonalities.director'),
                                    ])
                                </li>
                                <li class="swiper-slide">
                                    @include('frontend::components.cards.personality-card', [
                                    'castImage' => 'benjamin-hayes.webp',
                                    'castTitle' => __('favouritePersonalities.benjamin_hayes'),
                                    'castCategory' => __('favouritePersonalities.producer'),
                                    ])
                                </li>
                                <li class="swiper-slide">
                                    @include('frontend::components.cards.personality-card', [
                                    'castImage' => 'ethan-crawford.webp',
                                    'castTitle' => __('favouritePersonalities.ethan_crawford'),
                                    'castCategory' => __('favouritePersonalities.producer'),
                                    ])
                                </li>
                                <li class="swiper-slide">
                                    @include('frontend::components.cards.personality-card', [
                                    'castImage' => 'mark-wilson.webp',
                                    'castTitle' => __('favouritePersonalities.mark_wilson'),
                                    'castCategory' => __('favouritePersonalities.producer'),
                                    ])
                                </li>
                                <li class="swiper-slide">
                                    @include('frontend::components.cards.personality-card', [
                                    'castImage' => 'jack-walker.webp',
                                    'castTitle' => __('favouritePersonalities.jack_walker'),
                                    'castCategory' => __('favouritePersonalities.director'),
                                    ])
                                </li>
                                <li class="swiper-slide">
                                    @include('frontend::components.cards.personality-card', [
                                    'castImage' => 'millar-joseph.webp',
                                    'castTitle' => __('favouritePersonalities.millar_joseph'),
                                    'castCategory' => __('favouritePersonalities.producer'),
                                    ])
                                </li>
                                <li class="swiper-slide">
                                    @include('frontend::components.cards.personality-card', [
                                    'castImage' => 'kelly-reilly.webp',
                                    'castTitle' => __('favouritePersonalities.kelly_reilly'),
                                    'castCategory' => __('favouritePersonalities.director'),
                                    ])
                                </li>
                                <li class="swiper-slide">
                                    @include('frontend::components.cards.personality-card', [
                                    'castImage' => 'smith-jonas.webp',
                                    'castTitle' => __('favouritePersonalities.smith_jonas'),
                                    'castCategory' => __('favouritePersonalities.director'),
                                    ])
                                </li>
                                <li class="swiper-slide">
                                    @include('frontend::components.cards.personality-card', [
                                    'castImage' => 'davina-decorous.webp',
                                    'castTitle' => __('favouritePersonalities.davina_decorous'),
                                    'castCategory' => __('favouritePersonalities.producer'),
                                    ])
                                </li>
                                <li class="swiper-slide">
                                    @include('frontend::components.cards.personality-card', [
                                    'castImage' => 'thomas-bailey.webp',
                                    'castTitle' => __('favouritePersonalities.thomas_bailey'),
                                    'castCategory' => __('favouritePersonalities.director'),
                                    'otherCastCategory' => __('favouritePersonalities.producer'),
                                    ])
                                </li>
                                <li class="swiper-slide">
                                    @include('frontend::components.cards.personality-card', [
                                    'castImage' => 'leena-burton.webp',
                                    'castTitle' => __('favouritePersonalities.Leena_Burton'),
                                    'castCategory' => __('favouritePersonalities.actress'),
                                    ])

                                </li>
                            </ul>
                            <div class="swiper-button swiper-button-next d-none d-lg-block"></div>
                            <div class="swiper-button swiper-button-prev d-none d-lg-block"></div>
                        </div>
                    </section>
                </div>
                {{-- Crew End --}}

                @include('frontend::components.sections.upcoming', ['tvshowdetailUpcoming' => true])

                <section class="mb-5">
                    @include('frontend::components.widgets.details-review')
                </section>

            </div>
        </div>
    </div>

    @include('frontend::components.widgets.details-description-modal', [
        'movieName' => __('form.vikings'),
        'year' => '2025',
        'views' => '141' . __('frontendplaylist.views'),
        'ratingCount' => '5',
    ])

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}
@endsection
