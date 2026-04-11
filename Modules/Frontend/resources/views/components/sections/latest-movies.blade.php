<div class="latest-block section-wraper">
    <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
        <h4 class="main-title text-capitalize mb-0 fw-medium">{{ __('sectionTitle.latest_movies') }}</h4>
        @if (isset($viewAllBtn))
        <a href="{{ route('frontend.view_all') }}"
            class="text-primary iq-view-all text-decoration-none flex-none">{{ __('streamButtons.view_all') }}</a>
        @else
        @endif
    </div>
    <div class="card-style-slider">
        <div class="position-relative swiper swiper-card" data-slide="6" data-laptop="4" data-tab="3" data-mobile="2"
            data-mobile-sm="2" data-autoplay="false" data-loop="true" data-navigation="true" data-pagination="true">
            <ul class="p-0 swiper-wrapper m-0 list-inline">
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/deadpool-portrait.webp',
                    'cardTitle' => __('streamMovies.deadpool'),
                    'movieTime' => '2h : 12m',
                    'cardLang' => 'English',
                    'cardPath' => 'movie-detail',
                    ])
                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/the-hunter-portrait.webp',
                    'cardTitle' => __('streamMovies.the_hunter'),
                    'movieTime' => '1h : 22m',
                    'cardLang' => 'English',
                    'cardPath' => 'movie-detail',
                    ])
                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/gameofhero-portrait.webp',
                    'cardTitle' => __('streamMovies.game_of_heros'),
                    'movieTime' => '1h : 30m',
                    'cardLang' => 'English',
                    'cardPath' => 'movie-detail',
                    ])
                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/the-first-of-us-portrait.webp',
                    'cardTitle' => __('streamMovies.the_first_of_us'),
                    'movieTime' => '1h : 30m',
                    'cardLang' => 'English',
                    'cardPath' => 'movie-detail',
                    ])
                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/rabbit-portrait.webp',
                    'cardTitle' => __('streamMovies.rabbit'),
                    'movieTime' => '1h : 30m',
                    'cardLang' => 'English',
                    'cardPath' => 'movie-detail',
                    ])
                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/migration-portrait.webp',
                    'cardTitle' => __('streamMovies.migration'),
                    'movieTime' => '1h : 30m',
                    'cardLang' => 'English',
                    'cardPath' => 'movie-detail',
                    ])
                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/chosfies.webp',
                    'cardTitle' => __('streamMovies.chosfies'),
                    'movieTime' => '1h : 45m',
                    'cardLang' => 'English',
                    'cardPath' => 'movie-detail',
                    ])
                </li>
            </ul>
            <div class="d-none d-lg-block">
                <div class="swiper-button swiper-button-next"></div>
                <div class="swiper-button swiper-button-prev"></div>
            </div>
        </div>
    </div>
</div>