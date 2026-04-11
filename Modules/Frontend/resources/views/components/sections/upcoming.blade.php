@php
$videodetailUpcoming = $videodetailUpcoming ?? false;
$tvshowdetailUpcoming = $tvshowdetailUpcoming ?? false;
@endphp
@if ($videodetailUpcoming)
<section class="upcomimg-block section-padding-top">
    <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
        <h5 class="main-title text-capitalize mb-0">{{ __('widgets.Upcoming') }}</h5>
    </div>
    <div class="card-style-slider">
        <div class="position-relative swiper swiper-card" data-slide="6" data-laptop="6" data-tab="3" data-mobile="2"
            data-mobile-sm="2" data-autoplay="false" data-loop="false" data-navigation="true" data-pagination="true">
            <ul class="p-0 swiper-wrapper m-0  list-inline">
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/kung-fu-panda-portrait.webp',
                    'cardTitle' => __('streamMovies.kung_fu_panda'),
                    'movieTime' => '1hr : 30mins',
                    'cardLang' => 'English',
                    'cardPath' => 'video-detail',
                    ])
                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/red-portrait.webp',
                    'cardTitle' => __('streamMovies.red'),
                    'movieTime' => '1hr : 55mins',
                    'cardLang' => 'English',
                    'cardPath' => 'video-detail',
                    ])
                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/lost-in-space-portrait.webp',
                    'cardTitle' => __('streamMovies.lost_in_space'),
                    'movieTime' => '1hr : 22mins',
                    'cardLang' => 'English',
                    'cardPath' => 'video-detail',
                    ])
                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/the-dragon-king-portrait.webp',
                    'cardTitle' => __('streamMovies.dragon_king'),
                    'movieTime' => '1hr : 30mins',
                    'cardLang' => 'English',
                    'cardPath' => 'video-detail',
                    'productPremium' => true,
                    ])
                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/sonil-portrait.webp',
                    'cardTitle' => __('streamMovies.sonil'),
                    'movieTime' => '2hr : 45mins',
                    'cardLang' => 'English',
                    'cardPath' => 'video-detail',
                    ])
                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/charlie-chaplin-portrait.webp',
                    'cardTitle' => __('streamMovies.charliechaplin'),
                    'movieTime' => '1hr : 45mins',
                    'cardLang' => 'English',
                    'cardPath' => 'video-detail',
                    ])
                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/gameofhero-portrait.webp',
                    'cardTitle' => __('streamMovies.game_of_heros'),
                    'movieTime' => '2hr : 30mins',
                    'cardLang' => 'English',
                    'cardPath' => 'video-detail',
                    ])
                </li>
            </ul>
            <div class="swiper-button swiper-button-next"></div>
            <div class="swiper-button swiper-button-prev"></div>
        </div>
    </div>
</section>
@elseif ($tvshowdetailUpcoming)
<section class="upcomimg-block">
    <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
        <h4 class="main-title text-capitalize mb-0">{{ __('widgets.Upcoming') }}</h4>
    </div>
    <div class="card-style-slider">
        <div class="position-relative swiper swiper-card" data-slide="6" data-laptop="6" data-tab="3"
            data-mobile="2" data-mobile-sm="2" data-autoplay="false" data-loop="false" data-navigation="true"
            data-pagination="true">
                      <ul class="p-0 swiper-wrapper m-0  list-inline">
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/the-first-of-us-portrait.webp',
                    'cardTitle' => __('streamMovies.the_first_of_us'),
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
                    'productPremium' => true,
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/tianic.webp',
                    'cardTitle' => __('streamMovies.titanic'),
                    'cardLang' => 'English',
                    'cardPath' => 'movie-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/the-crew-portrait.webp',
                    'cardTitle' => __('streamMovies.the_crew'),
                    'cardLang' => 'English',
                    'cardPath' => 'movie-detail',
                    ])

                </li>
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
                    'cardImage' => 'media/castle-rock-portrait.webp',
                    'cardTitle' => __('streamMovies.castle_rock'),
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
            <div class="swiper-button swiper-button-next"></div>
            <div class="swiper-button swiper-button-prev"></div>
        </div>
    </div>
</section>
@else
<section class="upcomimg-block">
    <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
        <h4 class="main-title text-capitalize mb-0">{{ __('widgets.Upcoming') }}</h4>
    </div>
    <div class="card-style-slider">
        <div class="position-relative swiper swiper-card" data-slide="6" data-laptop="6" data-tab="3"
            data-mobile="2" data-mobile-sm="2" data-autoplay="false" data-loop="false" data-navigation="true"
            data-pagination="true">
            <ul class="p-0 swiper-wrapper m-0  list-inline">
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/the-first-of-us-portrait.webp',
                    'cardTitle' => __('streamMovies.the_first_of_us'),
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
                    'productPremium' => true,
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/tianic.webp',
                    'cardTitle' => __('streamMovies.titanic'),
                    'cardLang' => 'English',
                    'cardPath' => 'movie-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/the-crew-portrait.webp',
                    'cardTitle' => __('streamMovies.the_crew'),
                    'cardLang' => 'English',
                    'cardPath' => 'movie-detail',
                    ])

                </li>
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
                    'cardImage' => 'media/castle-rock-portrait.webp',
                    'cardTitle' => __('streamMovies.castle_rock'),
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
            <div class="swiper-button swiper-button-next d-none d-lg-block"></div>
            <div class="swiper-button swiper-button-prev d-none d-lg-block"></div>
        </div>
    </div>
</section>
@endif