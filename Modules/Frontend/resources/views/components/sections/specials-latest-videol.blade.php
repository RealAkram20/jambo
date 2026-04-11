<section class="recommended-block section-top-spacing  section-wraper">
    <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
        <h4 class="main-title text-capitalize mb-0">{{ __('sectionTitle.specials_latest_videos') }}</h4>
        @if (isset($viewAllBtn))
            <a href="{{ route('frontend.view_all') }}"
                class="text-primary iq-view-all text-decoration-none flex-none">{{ __('streamButtons.view_all') }}</a>
        @else
        @endif
    </div>
    <div class="card-style-slider">
        <div class="position-relative swiper swiper-card" data-slide="6" data-laptop="5" data-tab="3" data-mobile="2"
            data-mobile-sm="2" data-autoplay="false" data-loop="true" data-navigation="true" data-pagination="true">
            <ul class="p-0 swiper-wrapper m-0  list-inline">
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                        'cardImage' => 'media/gameofhero-portrait.webp',
                        'cardTitle' => __('streamMovies.game_of_heros'),
                        'movieTime' => '2hr : 12mins',
                        'cardLang' => 'English',
                        'cardPath' => route('frontend.video_detail'),
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                        'cardImage' => 'media/krishna-portrait.webp',
                        'cardTitle' =>__('streamMovies.krishna'),
                        'movieTime' => '1hr : 30mins',
                        'cardLang' => 'English',
                        'cardPath' => route('frontend.video_detail'),
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                        'cardImage' => 'media/reed-dog-portrait.webp',
                        'cardTitle' => __('streamMovies.reed_dog'),
                        'movieTime' => '2hr : 30mins',
                        'cardLang' => 'English',
                        'cardPath' => route('frontend.video_detail'),
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                        'cardImage' => 'media/frzzen-portrait.webp',
                        'cardTitle' => __('streamMovies.frzzen'),
                        'movieTime' => '2hr : 45mins',
                        'cardLang' => 'English',
                        'cardPath' => route('frontend.video_detail'),
                        'productPremium' => true,
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                        'cardImage' => 'media/adventure.webp',
                        'cardTitle' => __('streamMovies.adventure'),
                        'movieTime' => '1hr : 55mins',
                        'cardLang' => 'English',
                        'cardPath' => route('frontend.video_detail'),
                        'productPremium' => true,
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                        'cardImage' => 'media/kung-fu-panda-portrait.webp',
                        'cardTitle' => __('streamMovies.kung_fu_panda'),
                        'movieTime' => '1hr : 25mins',
                        'cardLang' => 'English',
                        'cardPath' => route('frontend.video_detail'),
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                        'cardImage' => 'media/red-portrait.webp',
                        'cardTitle' => __('streamMovies.red'),
                        'movieTime' => '1hr : 45mins',
                        'cardLang' => 'English',
                        'cardPath' => route('frontend.video_detail'),
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
