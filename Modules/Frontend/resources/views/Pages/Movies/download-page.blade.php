@extends('frontend::layouts.master', ['isSwiperSlider' => true, 'isVideoJs' => true, 'bodyClass' => 'custom-header-relative', 'isSelect2' => true])

@section('content')
    <div class="poition-relative">
        <div class="iq-main-slider site-video position-relative">
            <video id="my-video" poster="{{ asset('frontend/images/media/chosfies.webp') }}"
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
                        'moveName' => __('streamMovies.chosfies'),
                        'movieType' => 'NC-17',
                        'movieDuration' => '2hr : 6m',
                        'movieReleased' => '2025',
                        'movieViews' => '1.4k ' . __('streamPlaylist.views'),
                        'imdbRating' => '7',
                        'videoUrl' => route('frontend.movie_player'),
                    ])
                </div>
            </div>
        </div>
    </div>



    <div class="container-fluid">
        <div class="overflow-hidden">
            @include('frontend::components.sections.recommended', [
                'downloadUpcoming' => true,
            ])

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

            <section class="related-movie-block">
                <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
                    <h4 class="main-title text-capitalize mb-0">{{ __('sectionTitle.recommended_movie') }}</h4>
                </div>
                <div class="card-style-slider">
                    <div class="position-relative swiper swiper-card" data-slide="6" data-laptop="6" data-tab="3"
                        data-mobile="2" data-mobile-sm="2" data-autoplay="false" data-loop="false" data-navigation="true"
                        data-pagination="true">
                        <ul class="p-0 swiper-wrapper m-0  list-inline">
                            <li class="swiper-slide">
                                @include('frontend::components.cards.card-style', [
                                'cardImage' => 'media/rabbit-portrait.webp',
                                'cardTitle' => __('streamMovies.rabbit'),
                                'movieTime' => '2hr : 12mins',
                                'cardLang' => 'English',
                                'cardPath' => 'movie-detail',
                                ])
                            </li>
                            <li class="swiper-slide">
                                @include('frontend::components.cards.card-style', [
                                'cardImage' => 'media/krishna-portrait.webp',
                                'cardTitle' => __('streamMovies.krishna'),
                                'movieTime' => '1hr : 22mins',
                                'cardLang' => 'English',
                                'cardPath' => 'movie-detail',
                                ])
                            </li>
                            <li class="swiper-slide">
                                @include('frontend::components.cards.card-style', [
                                'cardImage' => 'media/kali-portrait.webp',
                                'cardTitle' => __('streamMovies.kali'),
                                'movieTime' => '1hr : 30mins',
                                'cardLang' => 'English',
                                'cardPath' => 'movie-detail',
                                ])
                            </li>
                            <li class="swiper-slide">
                                @include('frontend::components.cards.card-style', [
                                'cardImage' => 'media/joker-portrait.webp',
                                'cardTitle' => __('streamMovies.joker'),
                                'movieTime' => '1hr : 30mins',
                                'cardLang' => 'English',
                                'cardPath' => 'movie-detail',
                                ])
                            </li>
                            <li class="swiper-slide">
                                @include('frontend::components.cards.card-style', [
                                'cardImage' => 'media/the-hunter-portrait.webp',
                                'cardTitle' => __('streamMovies.the_hunter'),
                                'movieTime' => '1hr : 45mins',
                                'cardLang' => 'English',
                                'cardPath' => 'movie-detail',
                                ])
                            </li>
                            <li class="swiper-slide">
                                @include('frontend::components.cards.card-style', [
                                'cardImage' => 'media/jumanji-portrait.webp',
                                'cardTitle' => __('streamMovies.jumanji'),
                                'movieTime' => '1hr : 45mins',
                                'cardLang' => 'English',
                                'cardPath' => 'movie-detail',
                                ])
                            </li>
                        </ul>
                        <div class="swiper-button swiper-button-next d-none d-lg-block"></div>
                        <div class="swiper-button swiper-button-prev d-none d-lg-block"></div>
                    </div>
                </div>
            </section>

            <section class="video-block">
                <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
                    <h4 class="main-title text-capitalize mb-0">{{ __('sectionTitle.related_videos') }}</h4>
                </div>
                <div class="card-style-slider">
                    <div class="position-relative swiper swiper-card" data-slide="6" data-laptop="6" data-tab="3"
                        data-mobile="2" data-mobile-sm="2" data-autoplay="false" data-loop="false"
                        data-navigation="true" data-pagination="true">
                        <ul class="p-0 swiper-wrapper m-0  list-inline">
                            <li class="swiper-slide">
                                @include('frontend::components.cards.card-style', [
                                'cardImage' => 'media/frzzen-portrait.webp',
                                'cardTitle' => __('streamMovies.frzzen'),
                                'movieTime' => '2hr : 12mins',
                                'cardLang' => 'English',
                                'cardPath' => 'movie-detail',
                                ])
                            </li>
                            <li class="swiper-slide">
                                @include('frontend::components.cards.card-style', [
                                'cardImage' => 'media/titanic-portrait.webp',
                                'cardTitle' => __('streamMovies.titanic'),
                                'movieTime' => '1hr : 22mins',
                                'cardLang' => 'English',
                                'cardPath' => 'movie-detail',
                                ])
                            </li>
                            <li class="swiper-slide">
                                @include('frontend::components.cards.card-style', [
                                'cardImage' => 'media/Adventure-1.webp',
                                'cardTitle' => __('streamMovies.adventure'),
                                'movieTime' => '1hr : 30mins',
                                'cardLang' => 'English',
                                'cardPath' => 'movie-detail',
                                ])
                            </li>
                            <li class="swiper-slide">
                                @include('frontend::components.cards.card-style', [
                                'cardImage' => 'media/kung-fu-panda-portrait.webp',
                                'cardTitle' => __('streamMovies.kung_fu_panda'),
                                'movieTime' => '1hr : 30mins',
                                'cardLang' => 'English',
                                'cardPath' => 'movie-detail',
                                ])
                            </li>
                            <li class="swiper-slide">
                                @include('frontend::components.cards.card-style', [
                                'cardImage' => 'media/red-portrait.webp',
                                'cardTitle' => __('streamMovies.red'),
                                'movieTime' => '1hr : 45mins',
                                'cardLang' => 'English',
                                'cardPath' => 'movie-detail',
                                ])
                            </li>
                            <li class="swiper-slide">
                                @include('frontend::components.cards.card-style', [
                                'cardImage' => 'media/lost-in-space-portrait.webp',
                                'cardTitle' => __('streamMovies.lost_in_space'),
                                'movieTime' => '1hr : 45mins',
                                'cardLang' => 'English',
                                'cardPath' => 'movie-detail',
                                ])
                            </li>
                            <li class="swiper-slide">
                                @include('frontend::components.cards.card-style', [
                                'cardImage' => 'media/charlie-chaplin-portrait.webp',
                                'cardTitle' => __('streamMovies.charliechaplin'),
                                'movieTime' => '1hr : 45mins',
                                'cardLang' => 'English',
                                'cardPath' => 'movie-detail',
                                ])
                            </li>
                        </ul>
                        <div class="swiper-button swiper-button-next d-none d-lg-block"></div>
                        <div class="swiper-button swiper-button-prev d-none d-lg-block"></div>
                    </div>
                </div>
            </section>
            
            @include('frontend::components.sections.upcoming')

            <section class="mb-5">
                @include('frontend::components.widgets.details-review')
            </section>
        </div>
    </div>

    {{-- Modal --}}
    @include('frontend::components.widgets.details-description-modal', [
        'movieName' => __('streamMovies.the_hunter'),
        'year' => '2025',
        'views' => '141' . __('frontendplaylist.views'),
        'ratingCount' => '5',
    ])

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}
@endsection
