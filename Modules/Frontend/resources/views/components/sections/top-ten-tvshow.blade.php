<div class="top-ten-block">
    <div class="d-flex align-items-center justify-content-between  px-1 mb-2 pb-1 mb-md-4 pb-md-0">
        <h4 class="main-title text-capitalize mb-0">{{ __('sectionTitle.top_10_tvshow_to_watch') }}</h4>
    </div>
    <div class="card-style-slider">
        <div class="position-relative swiper swiper-card iq-top-ten-block-slider" data-slide="6" data-laptop="4"
            data-tab="3" data-mobile="2" data-mobile-sm="2" data-autoplay="false" data-loop="false"
            data-navigation="true" data-pagination="true" data-navigation-outer="true">
            <ul class="p-0 swiper-wrapper mb-5 list-inline">
                <li class="swiper-slide">
                    @include('frontend::components.cards.top-ten-card', [
                        'imagePath' => 'minions-portrait.webp',
                        'countValue' => '1',
                        'cardUrlPath' => 'tv-show-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.top-ten-card', [
                        'imagePath' => 'breaking-bad-portrait.webp',
                        'countValue' => '2',
                        'cardUrlPath' => 'tv-show-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.top-ten-card', [
                        'imagePath' => 'kung-fu-panda-portrait.webp',
                        'countValue' => '3',
                        'cardUrlPath' => 'tv-show-detail',
                        'productPremium' => true,
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.top-ten-card', [
                        'imagePath' => 'spiderman-portrait.webp',
                        'countValue' => '4',
                        'cardUrlPath' => 'tv-show-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.top-ten-card', [
                        'imagePath' => 'migration-portrait.webp',
                        'countValue' => '5',
                        'cardUrlPath' => 'tv-show-detail',
                        'productPremium' => true,
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.top-ten-card', [
                        'imagePath' => 'midnightHunter-portrait.webp',
                        'countValue' => '6',
                        'cardUrlPath' => 'tv-show-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.top-ten-card', [
                        'imagePath' => 'the-hunter-portrait.webp',
                        'countValue' => '7',
                        'cardUrlPath' => 'tv-show-detail',
                        'productPremium' => true,
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.top-ten-card', [
                        'imagePath' => 'hanuman-portrait.webp',
                        'countValue' => '8',
                        'cardUrlPath' => 'tv-show-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.top-ten-card', [
                        'imagePath' => 'lost-in-space-portrait.webp',
                        'countValue' => '9',
                        'cardUrlPath' => 'tv-show-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.top-ten-card', [
                        'imagePath' => 'krishna-portrait.webp',
                        'countValue' => '10',
                        'cardUrlPath' => 'tv-show-detail',
                    ])

                </li>
            </ul>
            <div class="d-none d-lg-block">
                <div class="swiper-button swiper-button-next"></div>
                <div class="swiper-button swiper-button-prev"></div>
            </div>
        </div>
        <div class="swiper-btn">
        </div>
    </div>
</div>
