<div class="streamit-card-height-block">
    <div class="d-flex align-items-center justify-content-between  px-1 mb-4">
        <h4 class="main-title text-capitalize mb-0 fw-medium">{{ __('sectionTitle.best_in_tv') }}</h4>
        <a href="view-all"
            class="text-primary iq-view-all text-decoration-none flex-none">{{ __('streamButtons.view_all') }}</a>
    </div>
    <div class="card-style-slider">
        <div class="position-relative swiper swiper-card" data-slide="5" data-laptop="3" data-tab="3" data-mobile="2"
            data-mobile-sm="2" data-autoplay="false" data-loop="false" data-navigation="true" data-pagination="true">
            <ul class="p-0 swiper-wrapper m-0  list-inline">
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                        'isCardStyle2' => true,
                        'cardImage' => "media/migration.webp",
                        'cardTitle' => __('streamMovies.migration'),
                        'cardPath' => 'movie-detail',
                        'productPremium' => true,
                    ])
                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                        'isCardStyle2' => true,
                        'cardImage' => "media/giirek.webp",
                        'cardTitle' => __('streamMovies.giikre'),
                        'cardPath' => 'movie-detail',
                    ])
                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                        'isCardStyle2' => true,
                        'cardImage' => "media/john-wick.webp",
                        'cardTitle' => __('streamMovies.john_wick'),
                        'cardPath' => 'movie-detail',
                        'productPremium' => true,
                    ])
                </li>   
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                        'isCardStyle2' => true,
                        'cardImage' => "media/war-for-the-planet.webp",
                        'cardTitle' => __('streamMovies.war_for_the_planet'),
                        'cardPath' => 'movie-detail',
                    ])
                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                        'isCardStyle2' => true,
                        'cardImage' => "media/dinoosaur.webp",
                        'cardTitle' => __('streamMovies.dinoosaur'),
                        'cardPath' => 'movie-detail',
                    ])
                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                        'isCardStyle2' => true,
                        'cardImage' => "media/Bumblebee.webp",
                        'cardTitle' => __('streamMovies.bumblebee'),
                        'cardPath' => 'movie-detail',
                    ])
                </li>
            </ul>
            <div class="swiper-button swiper-button-next"></div>
            <div class="swiper-button swiper-button-prev"></div>
        </div>
    </div>
</div>
