<section class="recommended-block section-top-spacing section-wraper">
    <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
        <h4 class="main-title text-capitalize mb-0">{{ __('frontendform.international_shows') }}</h4>
        @if (isset($viewAllBtn))
            <a href="{{ route('frontend.view_all') }}"
                class="text-primary iq-view-all text-decoration-none flex-none">{{ __('streamButtons.view_all') }}</a>
        @else
        @endif
    </div>
    <div class="card-style-slider">
        <div class="position-relative swiper swiper-card" data-slide="6" data-laptop="4" data-tab="3" data-mobile="2"
            data-mobile-sm="2" data-autoplay="false" data-loop="false" data-navigation="true" data-pagination="true">
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
                    'cardImage' => 'media/spiderman-portrait.webp',
                    'cardTitle' => __('streamMovies.spiderman'),
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
                    'cardImage' => 'media/minions-portrait.webp',
                    'cardTitle' => __('streamMovies.minions'),
                    'isnotlangCard' => true,
                    'cardPath' => 'tv-show-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/pirates-ofdayones-orignal.webp',
                    'cardTitle' => __('streamMovies.pirates_of_day_one'),
                    'isnotlangCard' => true,
                    'cardPath' => 'tv-show-detail',
                    'productPremium' => true,
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/lost-in-space-portrait.webp',
                    'cardTitle' => __('streamMovies.lost_in_space'),
                    'cardLang' => 'English',
                    'cardPath' => 'tv-show-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/castle-rock-portrait.webp',
                    'cardTitle' => __('streamMovies.castle_rock'),
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
