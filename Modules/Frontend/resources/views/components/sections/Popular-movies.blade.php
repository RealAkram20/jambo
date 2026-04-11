<div class="popular-movies-block section-wraper">
    <div class="d-flex align-items-center justify-content-between  px-1 mb-2 pb-1 mb-md-4 pb-md-0">
        <h4 class="main-title text-capitalize mb-0 fw-medium">{{ __('sectionTitle.popular_movies') }}</h4>
        @if (isset($viewAllBtn))
            <a href="{{ route('frontend.view_all') }}"
                class="text-primary iq-view-all text-decoration-none flex-none">{{ __('streamButtons.view_all') }}</a>
        @else
        @endif
    </div>
    <div class="card-style-slider">
        <div class="position-relative swiper swiper-card" data-slide="6" data-laptop="6" data-tab="3" data-mobile="2"
            data-mobile-sm="2" data-autoplay="false" data-loop="false" data-navigation="true" data-pagination="true">
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
                        'cardImage' => 'media/migration-portrait.webp',
                        'cardTitle' => __('streamMovies.migration'),
                        'movieTime' => '1hr : 22mins',
                        'cardLang' => 'English',
                        'cardPath' => 'movie-detail',
                        'productPremium' => true,
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                        'cardImage' => 'media/war-for-the-planet-portrait.webp',
                        'cardTitle' => __('streamMovies.war_for_the_planet'),
                        'movieTime' => '2hr : 30mins',
                        'cardLang' => 'English',
                        'cardPath' => 'movie-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                        'cardImage' => 'media/the-hunter-portrait.webp',
                        'cardTitle' => __('streamMovies.the_hunter'),
                        'movieTime' => '1hr : 30mins',
                        'cardLang' => 'English',
                        'cardPath' => 'movie-detail',
                        'productPremium' => true,
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                        'cardImage' => 'media/chosfies-portrait.webp',
                        'cardTitle' => __('streamMovies.chosfies'),
                        'movieTime' => '1hr : 30mins',
                        'cardLang' => 'English',
                        'cardPath' => 'movie-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                        'cardImage' => 'media/venom-portrait.webp',
                        'cardTitle' => __('streamMovies.venom'),
                        'movieTime' => '1hr : 30mins',
                        'cardLang' => 'English',
                        'cardPath' => 'movie-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                        'cardImage' => 'media/dinoosaur-portrait.webp',
                        'cardTitle' => __('streamMovies.dinoosaur'),
                        'movieTime' => '1hr : 30mins',
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
