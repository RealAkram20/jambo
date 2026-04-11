<section class="recommended-block section-wraper">
    <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
        <h4 class="main-title text-capitalize mb-0">{{ __('sectionTitle.specials_latest_movies') }}</h4>
            @if (isset($viewAllBtn))
                <a href="{{ route('frontend.view_all') }}"
                    class="text-primary iq-view-all text-decoration-none flex-none">{{ __('streamButtons.view_all') }}</a>
            @else
            @endif
    </div>
    <div class="card-style-slider">
        <div class="position-relative swiper swiper-card" data-slide="6" data-laptop="5" data-tab="3" data-mobile="2"
            data-mobile-sm="2" data-autoplay="false" data-loop="false" data-navigation="true" data-pagination="true">
              <ul class="p-0 swiper-wrapper m-0  list-inline">
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'isCardStyle2' => true,
                    'cardImage' => 'media/deadpool.webp',
                    'cardTitle' => __('streamMovies.deadpool'),
                    'cardLang' => 'English',
                    'cardPath' => 'tv-show-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'isCardStyle2' => true,
                    'cardImage' => 'media/gameofhero.webp',
                    'cardTitle' => __('streamMovies.game_of_heros'),
                    'cardLang' => 'English',
                    'cardPath' => 'tv-show-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'isCardStyle2' => true,
                    'cardImage' => 'media/rabbit.webp',
                    'cardTitle' => __('streamMovies.rabbit'),
                    'isnotlangCard' => true,
                    'cardPath' => 'tv-show-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'isCardStyle2' => true,
                    'cardImage' => 'media/the-first-of-us.webp',
                    'cardTitle' => __('streamMovies.the_first_of_us'),
                    'cardLang' => 'English',
                    'cardPath' => 'tv-show-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'isCardStyle2' => true,
                    'cardImage' => 'media/the-hunter.webp',
                    'cardTitle' => __('streamMovies.the_hunter'),
                    'cardLang' => 'English',
                    'cardPath' => 'tv-show-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'isCardStyle2' => true,
                    'cardImage' => 'media/migration.webp',
                    'cardTitle' => __('streamMovies.migration'),
                    'isnotlangCard' => true,
                    'cardPath' => 'tv-show-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'isCardStyle2' => true,
                    'cardImage' => 'media/chosfies.webp',
                    'cardTitle' => __('streamMovies.chosfies'),
                    'cardLang' => 'English',
                    'cardPath' => 'tv-show-detail',
                    ])

                </li>
            </ul>
            <div class="d-none d-lg-block">
                <div class="swiper-button swiper-button-next"></div>
                <div class="swiper-button swiper-button-prev"></div>
            </div>
        </div>
    </div>
</section>
