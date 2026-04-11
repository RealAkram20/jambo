<div class="top-pics-block section-wraper">
    <div class="d-flex align-items-center justify-content-between  px-1 mb-2 pb-1 mb-md-4 pb-md-0">
        <h4 class="main-title text-capitalize mb-0 fw-medium">{{ __('sectionTitle.top_picks') }}</h4>
        <a href="{{ route('frontend.view_all') }}"
            class="text-primary iq-view-all text-decoration-none flex-none">{{ __('streamButtons.view_all') }}</a>
    </div>
    <div class="card-style-slider">
        <div class="position-relative swiper swiper-card" data-slide="6" data-laptop="6" data-tab="3" data-mobile="2"
            data-mobile-sm="2" data-autoplay="false" data-loop="true" data-navigation="true" data-pagination="true">
            <ul class="p-0 swiper-wrapper m-0  list-inline">
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                        'cardImage' => 'media/batter-caill-portrait.webp',
                        'cardTitle' => __('streamMovies.better_call_saul'),
                        'cardLang' => 'English',
                        'cardPath' => 'movie-detail',
                        'productPremium' => true,
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                        'cardImage' => 'media/jumanji-portrait.webp',
                        'cardTitle' => __('streamMovies.jumanji'),
                        'cardLang' => 'English',
                        'cardPath' => 'movie-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                        'cardImage' => 'media/another-danger-portrait.webp',
                        'cardTitle' => __('streamMovies.another_danger'),
                        'cardLang' => 'English',
                        'cardPath' => 'movie-detail',
                        'productPremium' => true,
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                        'cardImage' => 'media/spiderman-portrait.webp',
                        'cardTitle' => __('streamMovies.spiderman'),
                        'cardLang' => 'English',
                        'cardPath' => 'movie-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                        'cardImage' => 'media/war-for-the-planet-portrait.webp',
                        'cardTitle' => __('streamMovies.war_for_the_planet'),
                        'cardLang' => 'English',
                        'cardPath' => 'movie-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                        'cardImage' => 'media/fast-furious-portrait.webp',
                        'cardTitle' => __('streamMovies.fast_&_furious'),
                        'cardLang' => 'English',
                        'cardPath' => 'movie-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                        'cardImage' => 'media/joker-portrait.webp',
                        'cardTitle' => __('streamMovies.joker'),
                        'cardLang' => 'English',
                        'cardPath' => 'movie-detail',
                        'productPremium' => true,
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
