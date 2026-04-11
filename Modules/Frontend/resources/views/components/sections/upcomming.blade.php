@php
$tvshowUpcomming = $tvshowUpcomming ?? false;
$videoUpcoming = $videoUpcoming ?? false;
@endphp
@if ($tvshowUpcomming)
<div class="streamit-block section-wraper">
    <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
        <h4 class="main-title text-capitalize mb-0 fw-medium">{{ __('sectionTitle.tv_upcoming_title') }}</h4>
        @if (isset($viewAllBtn))
        <a href="{{ route('frontend.view_all') }}"
            class="text-primary iq-view-all text-decoration-none flex-none">{{ __('streamButtons.view_all') }}</a>
        @else
        @endif
    </div>
    <div class="card-style-slider">
        <div class="position-relative swiper swiper-card" data-slide="6" data-laptop="4" data-tab="3" data-mobile="2"
            data-mobile-sm="2" data-autoplay="false" data-loop="false" data-navigation="true"
            data-pagination="true">
            <ul class="p-0 swiper-wrapper m-0  list-inline">
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/the-first-of-us-portrait.webp',
                    'cardTitle' => __('streamMovies.the_first_ones'),
                    'cardLang' => 'English',
                    'cardPath' => 'tv-show-detail',
                    'productPremium' => true,
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/the-co-noueriing-portrait.webp',
                    'cardTitle' => __('streamMovies.the_co_noueriing'),
                    'cardLang' => 'English',
                    'cardPath' => 'tv-show-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/giirek-portrait.webp',
                    'cardTitle' => __('streamMovies.giikre'),
                    'cardLang' => 'English',
                    'cardPath' => 'tv-show-detail',
                    'productPremium' => true,
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/another-danger-portrait.webp',
                    'cardTitle' => __('streamMovies.another_danger'),
                    'cardLang' => 'English',
                    'cardPath' => 'tv-show-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/kung-fu-panda-portrait.webp',
                    'cardTitle' => __('streamMovies.kung_fu_panda'),
                    'isnotlangCard' => true,
                    'cardPath' => 'tv-show-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/john-wick-portrait.webp',
                    'cardTitle' => __('streamMovies.john_wick'),
                    'isnotlangCard' => true,
                    'cardPath' => 'tv-show-detail',
                    'productPremium' => true,
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/spiderman-portrait.webp',
                    'cardTitle' => __('streamMovies.spiderman'),
                    'cardLang' => 'English',
                    'cardPath' => 'tv-show-detail',
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
@elseif ($videoUpcoming)
<div class="streamit-block section-wraper">
    <div class="d-flex align-items-center justify-content-between px-md-3 px-1 mb-2 pb-1 mb-md-4 pb-md-0">
        <h4 class="main-title text-capitalize mb-0">{{ __('sectionTitle.upcoming_video') }}</h4>
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
                    'cardImage' => 'media/kung-fu-panda-portrait.webp',
                    'cardTitle' => __('streamMovies.kung_fu_panda'),
                    'movieTime' => '2h : 12m',
                    'cardLang' => 'English',
                    'cardPath' => route('frontend.video_detail'),
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/red-portrait.webp',
                    'cardTitle' => __('streamMovies.red'),
                    'cardLang' => 'English',
                    'cardPath' => route('frontend.video_detail'),
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/lost-in-space-portrait.webp',
                    'cardTitle' => __('streamMovies.lost_in_space'),
                    'cardLang' => 'English',
                    'cardPath' => route('frontend.video_detail'),
                    'productPremium' => true,
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/the-dragon-king-portrait.webp',
                    'cardTitle' => __('streamMovies.dragon_king'),
                    'cardLang' => 'English',
                    'cardPath' => route('frontend.video_detail'),
                    'productPremium' => true,
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/sonil-portrait.webp',
                    'cardTitle' => __('streamMovies.sonil'),
                    'cardLang' => 'English',
                    'cardPath' => route('frontend.video_detail'),
                    'productPremium' => true,
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/charlie-chaplin-portrait.webp',
                    'cardTitle' => __('streamMovies.charliechaplin'),
                    'cardLang' => 'English',
                    'cardPath' => route('frontend.video_detail'),
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/we-gare.webp',
                    'cardTitle' => __('streamMovies.we_gare'),
                    'cardLang' => 'English',
                    'cardPath' => route('frontend.video_detail'),
                    'productPremium' => true,
                    ])

                </li>
            </ul>
            <div class="swiper-button swiper-button-next"></div>
            <div class="swiper-button swiper-button-prev"></div>
        </div>
    </div>
</div>
@else
<div class="upcomimg-block section-wraper">
    <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
        <h4 class="main-title text-capitalize mb-0 fw-medium">{{ __('sectionTitle.upcoming_movies') }}</h4>
        @if (isset($viewAllBtn))
        <a href="{{ route('frontend.view_all') }}"
            class="text-primary iq-view-all text-decoration-none flex-none">{{ __('streamButtons.view_all') }}</a>
        @else
        @endif
    </div>
    <div class="card-style-slider">
        <div class="position-relative swiper swiper-card" data-slide="6" data-laptop="4" data-tab="3" data-mobile="2"
            data-mobile-sm="2" data-autoplay="false" data-loop="false" data-navigation="true"
            data-pagination="true">
            <ul class="p-0 swiper-wrapper m-0  list-inline">
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/the-first-of-us-portrait.webp',
                    'cardTitle' => __('streamMovies.the_first_ones'),
                    'cardLang' => 'English',
                    'cardPath' => 'tv-show-detail',
                    'productPremium' => true,
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/the-co-noueriing-portrait.webp',
                    'cardTitle' => __('streamMovies.the_co_noueriing'),
                    'cardLang' => 'English',
                    'cardPath' => 'tv-show-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/giirek-portrait.webp',
                    'cardTitle' => __('streamMovies.giikre'),
                    'cardLang' => 'English',
                    'cardPath' => 'tv-show-detail',
                    'productPremium' => true,
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/another-danger-portrait.webp',
                    'cardTitle' => __('streamMovies.another_danger'),
                    'cardLang' => 'English',
                    'cardPath' => 'tv-show-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/kung-fu-panda-portrait.webp',
                    'cardTitle' => __('streamMovies.kung_fu_panda'),
                    'isnotlangCard' => true,
                    'cardPath' => 'tv-show-detail',
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/john-wick-portrait.webp',
                    'cardTitle' => __('streamMovies.john_wick'),
                    'isnotlangCard' => true,
                    'cardPath' => 'tv-show-detail',
                    'productPremium' => true,
                    ])

                </li>
                <li class="swiper-slide">
                    @include('frontend::components.cards.card-style', [
                    'cardImage' => 'media/spiderman-portrait.webp',
                    'cardTitle' => __('streamMovies.spiderman'),
                    'cardLang' => 'English',
                    'cardPath' => 'tv-show-detail',
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