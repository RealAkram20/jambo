<div class="top-ten-block">
    <div class="d-flex align-items-center justify-content-between  px-1 mb-2 pb-1 mb-md-4 pb-md-0">
        <h4 class="main-title text-capitalize mb-0 fw-medium">{{ __('sectionTitle.top_10_video_to_watch') }}</h4>
    </div>
    <div class="card-style-slider">
        <div class="position-relative swiper swiper-card iq-top-ten-block-slider" data-slide="6" data-laptop="6"
            data-tab="3" data-mobile="2" data-mobile-sm="2" data-autoplay="false" data-loop="false"
            data-navigation="true" data-pagination="true">
            <ul class="p-0 swiper-wrapper mb-5 list-inline">
                <li class="swiper-slide">
                    @include('frontend::components.cards.top-ten-card', [
                        'imagePath' => 'frzzen-portrait.webp',
                        'countValue' => '1',
                        'cardUrlPath' => 'video-detail',
                        'productPremium' => true,
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.top-ten-card', [
                        'imagePath' => 'toddler-portrait.webp',
                        'countValue' => '2',
                        'cardUrlPath' => 'video-detail',
                        'productPremium' => true,
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.top-ten-card', [
                        'imagePath' => 'gameofhero-portrait.webp',
                        'countValue' => '3',
                        'cardUrlPath' => 'video-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.top-ten-card', [
                        'imagePath' => 'krishna-portrait.webp',
                        'countValue' => '4',
                        'cardUrlPath' => 'video-detail',
                        'productPremium' => true,
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.top-ten-card', [
                        'imagePath' => 'titanic-portrait.webp',
                        'countValue' => '5',
                        'cardUrlPath' => 'video-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.top-ten-card', [
                        'imagePath' => 'reed-dog-portrait.webp',
                        'countValue' => '6',
                        'cardUrlPath' => 'video-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.top-ten-card', [
                        'imagePath' => 'adventure.webp',
                        'countValue' => '7',
                        'cardUrlPath' => 'video-detail',
                        'productPremium' => true,
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.top-ten-card', [
                        'imagePath' => 'the-crew-portrait.webp',
                        'countValue' => '8',
                        'cardUrlPath' => 'video-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.top-ten-card', [
                        'imagePath' => 'synchronic-portrait.webp',
                        'countValue' => '9',
                        'cardUrlPath' => 'video-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.top-ten-card', [
                        'imagePath' => 'guilty-portrait.webp',
                        'countValue' => '10',
                        'cardUrlPath' => 'video-detail',
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
