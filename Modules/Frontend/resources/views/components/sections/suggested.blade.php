<div class="suggested-block section-wraper">
    <div class="suggested-block section-wraper">
        <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
            <h4 class="main-title text-capitalize mb-0 fw-medium">{{ __('sectionTitle.suggested_block') }}</h4>
        </div>
        <div class="card-style-slider">
            <div class="position-relative swiper swiper-card" data-slide="6" data-laptop="4" data-tab="3" data-mobile="2"
                data-mobile-sm="2" data-autoplay="false" data-loop="false" data-navigation="true"
                data-pagination="true">
                <ul class="p-0 swiper-wrapper m-0 list-inline">
                    <li class="swiper-slide">
                        @include('frontend::components.cards.card-style', [
                        'cardImage' => 'media/giirek-portrait.webp',
                        'cardTitle' => __('streamMovies.giirek'),
                        'movieTime' => '2h : 12m',
                        'cardLang' => 'English',
                        'cardPath' => 'movie-detail',
                        'productPremium' => true,
                        ])
                    </li>
                    <li class="swiper-slide">
                        @include('frontend::components.cards.card-style', [
                        'cardImage' => 'media/another-danger-portrait.webp',
                        'cardTitle' => __('streamMovies.another_danger'),
                        'movieTime' => '1h : 22m',
                        'cardLang' => 'English',
                        'cardPath' => 'movie-detail',
                        'productPremium' => true,
                        ])
                    </li>
                    <li class="swiper-slide">
                        @include('frontend::components.cards.card-style', [
                        'cardImage' => 'media/revenge-of-the-sith-portrait.webp',
                        'cardTitle' => __('streamMovies.revenge_of_sith'),
                        'cardLang' => 'English',
                        'cardPath' => 'movie-detail',
                        'productPremium' => true,
                        ])
                    </li>
                    <li class="swiper-slide">
                        @include('frontend::components.cards.card-style', [
                        'cardImage' => 'media/fast-furious-portrait.webp',
                        'cardTitle' => __('streamMovies.fast_&_furious'),
                        'movieTime' => '1h : 45m',
                        'cardLang' => 'English',
                        'cardPath' => 'movie-detail',
                        ])
                    </li>
                    <li class="swiper-slide">
                        @include('frontend::components.cards.card-style', [
                        'cardImage' => 'media/dinoosaur-portrait.webp',
                        'cardTitle' => __('streamMovies.dinoosaur'),
                        'cardLang' => 'English',
                        'cardPath' => 'movie-detail',
                        ])
                    </li>
                    <li class="swiper-slide">
                        @include('frontend::components.cards.card-style', [
                        'cardImage' => 'media/bumblebee-portrait.webp',
                        'cardTitle' => __('streamMovies.bumblebee'),
                        'cardLang' => 'English',
                        'cardPath' => 'movie-detail',
                        'productPremium' => true,
                        ])
                    </li>
                    <li class="swiper-slide">
                        @include('frontend::components.cards.card-style', [
                        'cardImage' => 'media/arrival-portrait.webp',
                        'cardTitle' => __('streamMovies.arrival'),
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
</div>