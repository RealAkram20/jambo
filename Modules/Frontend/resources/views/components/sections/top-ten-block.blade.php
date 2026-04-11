<div class="top-ten-block">
    <div class="d-flex align-items-center justify-content-between  px-1 mb-2 pb-1 mb-md-4 pb-md-0">
        <h4 class="main-title text-capitalize mb-0 fw-medium">{{ __('sectionTitle.top_ten') }}</h4>
    </div>
    <div class="card-style-slider">
        <div class="position-relative swiper swiper-card iq-top-ten-block-slider" data-slide="6" data-laptop="6"
            data-tab="3" data-mobile="2" data-mobile-sm="2" data-autoplay="false" data-loop="false"
            data-navigation="true" data-pagination="true">
            <ul class="p-0 swiper-wrapper mb-5 list-inline">
                <li class="swiper-slide">
                    @include('frontend::components.cards.top-ten-card', [
                        'imagePath' => 'gameofhero-portrait.webp',
                        'countValue' => '1',
                        'cardUrlPath' => 'movie-detail'
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.top-ten-card', [
                        'imagePath' => 'rabbit-portrait.webp',
                        'countValue' => '2',
                        'cardUrlPath' => 'movie-detail'
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.top-ten-card', [
                        'imagePath' => 'chosfies-portrait.webp',
                        'countValue' => '3',
                        'cardUrlPath' => 'movie-detail'
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.top-ten-card', [
                        'imagePath' => 'dinoosaur-portrait.webp',
                        'countValue' => '4',
                        'cardUrlPath' => 'movie-detail'
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.top-ten-card', [
                        'imagePath' => 'krishna-portrait.webp',
                        'countValue' => '5',
                        'cardUrlPath' => 'movie-detail'
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.top-ten-card', [
                        'imagePath' => 'venom-portrait.webp',
                        'countValue' => '6',
                        'cardUrlPath' => 'movie-detail'
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.top-ten-card', [
                        'imagePath' => 'the-hunter-portrait.webp',
                        'countValue' => '7',
                        'cardUrlPath' => 'movie-detail'
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.top-ten-card', [
                        'imagePath' => 'kali-portrait.webp',
                        'countValue' => '8',
                        'cardUrlPath' => 'movie-detail'
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.top-ten-card', [
                        'imagePath' => 'jumanji-portrait.webp',
                        'countValue' => '9',
                        'cardUrlPath' => 'movie-detail'
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.top-ten-card', [
                        'imagePath' => 'migration-portrait.webp',
                        'countValue' => '10',
                        'cardUrlPath' => 'movie-detail'
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
