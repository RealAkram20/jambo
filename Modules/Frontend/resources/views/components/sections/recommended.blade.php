@php
$downloadUpcoming = $downloadUpcoming ?? false;
$restrictedUpcoming = $restrictedUpcoming ?? false;
$relatedUpcoming = $relatedUpcoming ?? false;
@endphp
@if ($downloadUpcoming)
<section class="recommended-block section-padding-top">
    <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
        <h4 class="main-title text-capitalize mb-0">{{ __('streamPricing.recommended') }}</h4>
    </div>
    <div class="card-style-slider">
        <div class="position-relative swiper swiper-card" data-slide="6 " data-laptop="6" data-tab="3" data-mobile="2"
            data-mobile-sm="2" data-autoplay="false" data-loop="false" data-navigation="true" data-pagination="true">
            <ul class="p-0 swiper-wrapper m-0  list-inline">
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/rabbit-portrait.webp',
                    'cardTitle' => __('streamMovies.rabbit'),
                    'cardLang' => 'English',
                    'cardPath' => 'movie-detail',
                    'productPremium' => true,
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/venom-portrait.webp',
                    'cardTitle' => __('streamMovies.venom'),
                    'cardLang' => 'English',
                    'cardPath' => 'movie-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/migration-portrait.webp',
                    'cardTitle' => __('streamMovies.migration'),
                    'cardLang' => 'English',
                    'cardPath' => 'movie-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/gameofhero-portrait.webp',
                    'cardTitle' => __('streamMovies.game_of_heros'),
                    'cardLang' => 'English',
                    'cardPath' => 'movie-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/batter-caill-portrait.webp',
                    'cardTitle' => __('streamMovies.better_call_saul'),
                    'cardLang' => 'English',
                    'cardPath' => 'movie-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/kali-portrait.webp',
                    'cardTitle' => __('streamMovies.kali'),
                    'cardLang' => 'English',
                    'cardPath' => 'movie-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/deadpool-portrait.webp',
                    'cardTitle' => __('streamMovies.deadpool'),
                    'cardLang' => 'English',
                    'cardPath' => 'movie-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/krishna-portrait.webp',
                    'cardTitle' => __('streamMovies.krishna'),
                    'cardLang' => 'English',
                    'cardPath' => 'movie-detail',
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
                    'cardImage' => 'media/any-name-portrait.webp',
                    'cardTitle' => __('streamMovies.any_name'),
                    'cardLang' => 'English',
                    'cardPath' => 'movie-detail',
                    ])

                </li>
            </ul>
            <div class="swiper-button swiper-button-next d-none d-lg-block"></div>
            <div class="swiper-button swiper-button-prev d-none d-lg-block"></div>
        </div>
    </div>
</section>
@elseif ($restrictedUpcoming)
<section class="recommended-block section-padding-top">
    <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
        <h4 class="main-title text-capitalize mb-0">{{ __('streamPricing.recommended') }}</h4>
    </div>
    <div class="card-style-slider">
        <div class="position-relative swiper swiper-card" data-slide="6 " data-laptop="6" data-tab="3"
            data-mobile="2" data-mobile-sm="2" data-autoplay="false" data-loop="false" data-navigation="true"
            data-pagination="true">
            <ul class="p-0 swiper-wrapper m-0  list-inline">
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/rabbit-portrait.webp',
                    'cardTitle' => __('streamMovies.rabbit'),
                    'cardLang' => 'English',
                    'cardPath' => 'movie-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/venom-portrait.webp',
                    'cardTitle' => __('streamMovies.venom'),
                    'cardLang' => 'English',
                    'cardPath' => 'movie-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/migration-portrait.webp',
                    'cardTitle' => __('streamMovies.migration'),
                    'cardLang' => 'English',
                    'cardPath' => 'movie-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/gameofhero-portrait.webp',
                    'cardTitle' => __('streamMovies.game_of_heros'),
                    'cardLang' => 'English',
                    'cardPath' => 'movie-detail',
                    'productPremium' => true,
                    ])

                </li>
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
                    'cardImage' => 'media/kali-portrait.webp',
                    'cardTitle' => __('streamMovies.kali'),
                    'cardLang' => 'English',
                    'cardPath' => 'movie-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/deadpool-portrait.webp',
                    'cardTitle' => __('streamMovies.deadpool'),
                    'cardLang' => 'English',
                    'cardPath' => 'movie-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/krishna-portrait.webp',
                    'cardTitle' => __('streamMovies.krishna'),
                    'cardLang' => 'English',
                    'cardPath' => 'movie-detail',
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
                    'cardImage' => 'media/any-name-portrait.webp',
                    'cardTitle' => __('streamMovies.any_name'),
                    'cardLang' => 'English',
                    'cardPath' => 'movie-detail',
                    ])

                </li>
            </ul>
            <div class="swiper-button swiper-button-next d-none d-lg-block"></div>
            <div class="swiper-button swiper-button-prev d-none d-lg-block"></div>
        </div>
    </div>
</section>
@elseif ($relatedUpcoming)
<section class="recommended-block section-padding-top">
    <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
        <h4 class="main-title text-capitalize mb-0">{{ __('streamPricing.recommended') }}</h4>
    </div>
    <div class="card-style-slider">
        <div class="position-relative swiper swiper-card" data-slide="6" data-laptop="6" data-tab="3"
            data-mobile="2" data-mobile-sm="2" data-autoplay="false" data-loop="false" data-navigation="true"
            data-pagination="true">
            <ul class="p-0 swiper-wrapper m-0  list-inline">
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/rabbit-portrait.webp',
                    'cardTitle' => __('streamMovies.rabbit'),
                    'cardLang' => 'English',
                    'cardPath' => 'movie-detail',
                    'productPremium' => true,
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/test-portrait.webp',
                    'cardTitle' => __('streamMovies.test'),
                    'cardLang' => 'English',
                    'cardPath' => 'movie-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/test-portrait.webp',
                    'cardTitle' => __('streamMovies.test'),
                    'cardLang' => 'English',
                    'cardPath' => 'movie-detail',
                    'productPremium' => true,
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/test-portrait.webp',
                    'cardTitle' => __('streamMovies.test'),
                    'cardLang' => 'English',
                    'cardPath' => 'movie-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/test-portrait.webp',
                    'cardTitle' => __('streamMovies.test'),
                    'cardLang' => 'English',
                    'cardPath' => 'movie-detail',
                    'productPremium' => true,
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/test-portrait.webp',
                    'cardTitle' => __('streamMovies.test'),
                    'cardLang' => 'English',
                    'cardPath' => 'movie-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/test-portrait.webp',
                    'cardTitle' => __('streamMovies.test'),
                    'cardLang' => 'English',
                    'cardPath' => 'movie-detail',
                    'productPremium' => true,
                    ])

                </li>
            </ul>
            <div class="swiper-button swiper-button-next"></div>
            <div class="swiper-button swiper-button-prev"></div>
        </div>
    </div>
</section>
@else
<div class="recommended-block section-wraper">
    <div class="d-flex align-items-center justify-content-between  px-1 mb-2 pb-1 mb-md-4 pb-md-0">
        <h4 class="main-title text-capitalize mb-0 fw-medium">{{ $recommended }}</h4>
        @if (isset($viewAllBtn))
        <a href="{{ route('frontend.view_all') }}"
            class="text-primary iq-view-all text-decoration-none flex-none">{{ __('streamButtons.view_all') }}</a>
        @else
        @endif
    </div>
    <div class="card-style-slider">
        <div class="position-relative swiper swiper-card" data-slide="6" data-laptop="6" data-tab="3"
            data-mobile="2" data-mobile-sm="2" data-autoplay="false" data-loop="true" data-navigation="true"
            data-pagination="true">
            <ul class="p-0 swiper-wrapper m-0  list-inline">
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/pirates-ofdayones-orignal.webp',
                    'cardTitle' => __('streamMovies.pirates_of_day_one'),
                    'cardLang' => 'English',
                    'cardPath' => 'movie-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/the-hunter-portrait.webp',
                    'cardTitle' => __('streamMovies.the_hunter'),
                    'cardLang' => 'English',
                    'cardPath' => 'movie-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/lost-in-space-portrait.webp',
                    'cardTitle' => __('streamMovies.lost_in_space'),
                    'cardLang' => 'English',
                    'cardPath' => 'movie-detail',
                    'productPremium' => true,
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/castle-rock-portrait.webp',
                    'cardTitle' => __('streamMovies.castle_rock'),
                    'cardLang' => 'English',
                    'cardPath' => 'movie-detail',
                    'productPremium' => true,
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/breaking-bad-portrait.webp',
                    'cardTitle' => __('streamMovies.breaking_bad'),
                    'cardLang' => 'English',
                    'cardPath' => 'movie-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/assassins-creed-portrait.webp',
                    'cardTitle' => __('streamMovies.assassins_creed'),
                    'cardLang' => 'English',
                    'cardPath' => 'movie-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/vikings-portrait.webp',
                    'cardTitle' => __('streamMovies.vikings'),
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
@endif