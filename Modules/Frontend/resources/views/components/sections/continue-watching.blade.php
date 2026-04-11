<section class="continue-watching-block home-continue-watch {{ $sectionPaddingClass ? 'section-padding-top' : '' }}">
    <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
        <h4 class="main-title text-capitalize mb-0 fw-medium">{{ __('sectionTitle.continue_watching') }}</h4>
    </div>
        <div class="position-relative swiper swiper-card" data-slide="6" data-laptop="3" data-tab="3" data-mobile="2"
            data-mobile-sm="2" data-autoplay="false" data-loop="true" data-navigation="true" data-pagination="false">
            <ul class="p-0 swiper-wrapper m-0  list-inline">
                <li class="swiper-slide">
                    @include('frontend::components.cards.continue-watch-card', [
                        'imagePath' => 'gameofhero.webp',
                        'progressValue' => '50%',
                        'dataLeftTime' => '70',
                        'watchMovieTitle' => __('streamMovies.game_of_heros'),
                        'watchMovieDate' => 'Jun-2025',
                    ])
                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.continue-watch-card', [
                        'imagePath' => 'the-first-of-us.webp',
                        'progressValue' => '30%',
                        'dataLeftTime' => '120',
                        'watchMovieTitle' => __('streamMovies.the_first_of_us'),
                        'watchMovieDate' => 'Jun-2025',
                    ])
                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.continue-watch-card', [
                        'imagePath' => 'the-co-noueriing.webp',
                        'progressValue' => '20%',
                        'dataLeftTime' => '60',
                        'watchMovieTitle' => __('streamMovies.the_co_noueriing'),
                        'watchMovieDate' => 'Nov-2024',
                    ])
                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.continue-watch-card', [
                        'imagePath' => 'giirek.webp',
                        'progressValue' => '45%',
                        'dataLeftTime' => '60',
                        'watchMovieTitle' => __('streamMovies.giikre'),
                        'watchMovieDate' => 'Dec-2024',
                    ])
                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.continue-watch-card', [
                        'imagePath' => 'rabbit.webp',
                        'progressValue' => '70%',
                        'dataLeftTime' => '45',
                        'watchMovieTitle' => __('streamMovies.rabbit'),
                        'watchMovieDate' => 'May-2025',
                    ])
                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.continue-watch-card', [
                        'imagePath' => 'jumanjj.webp',
                        'progressValue' => '80%',
                        'dataLeftTime' => '20',
                        'watchMovieTitle' => __('streamMovies.jumanji'),
                        'watchMovieDate' => 'March, 2025',
                    ])
                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.continue-watch-card', [
                        'imagePath' => 'chosfies.webp',
                        'progressValue' => '10%',
                        'dataLeftTime' => '80',
                        'watchMovieTitle' => __('streamMovies.chosfies'),
                        'watchMovieDate' => 'May, 2025',
                    ])
                </li>
            </ul>
            <div class="d-none d-lg-block">
                <div class="swiper-button swiper-button-next"></div>
                <div class="swiper-button swiper-button-prev"></div>
            </div>
        </div>
</section>
