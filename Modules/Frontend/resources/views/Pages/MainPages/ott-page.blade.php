@extends('frontend::layouts.master', ['isSwiperSlider' => true, 'isFslightbox' => true, 'bodyClass' => 'custom-header-relative', 'IS_MEGA' => true])

@section('content')
<section>
    <div class="overflow-hidden">
        <div class="iq-main-slider p-0 swiper banner-home-swiper" data-swiper="home-banner-slider"
            data-pagination="true" data-loop="true">
            <div class="slider m-0 p-0 swiper-wrapper home-slider">
                @foreach ($featuredMovies as $movie)
                    @php
                        $heroBg = $movie->backdrop_url ?: $movie->poster_url;
                        $heroBgSrc = $heroBg && \Illuminate\Support\Str::startsWith($heroBg, ['http://', 'https://'])
                            ? $heroBg
                            : ($heroBg ? asset('frontend/images/' . $heroBg) : asset('frontend/images/media/krishna.webp'));
                        $heroTrailer = $movie->trailer_url ?: 'https://www.youtube.com/embed/_PhvjbegfBI?autoplay=1&mute=1&loop=1&playlist=_PhvjbegfBI';
                    @endphp
                    <div class="swiper-slide slide s-bg-1 p-0">
                        <div class="banner-home-swiper-image" style="background-image: url('{{ $heroBgSrc }}');">
                            <div class="container-fluid position-relative">
                                <div class="row align-items-center iq-ltr-direction h-100 slider-content-full-height">
                                    <div class="col-lg-6 col-md-12 col-xl-5">
                                        <h2 class="texture-text big-font letter-spacing-1 line-count-1 text-capitalize RightAnimate">
                                            {{ $movie->title }}
                                        </h2>
                                        <div class="d-flex flex-wrap align-items-center gap-3 r-mb-23 RightAnimate-two">
                                            @if ($movie->rating)
                                                <span class="d-flex align-items-center gap-1">
                                                    <span>{{ $movie->rating }}</span>
                                                    <img src="{{ asset('frontend/images/pages/imdb-logo.svg') }}" alt="imdb logo" class="img-fluid imdb-img">
                                                </span>
                                            @endif
                                            @if ($movie->runtime_minutes)
                                                <div class="d-flex align-items-center gap-1">
                                                    <i class="ph ph-clock"></i>
                                                    <span class="time">{{ floor($movie->runtime_minutes / 60) }}h : {{ $movie->runtime_minutes % 60 }}m</span>
                                                </div>
                                            @endif
                                        </div>
                                        <p class="line-count-3 my-3 RightAnimate-two">{{ $movie->synopsis }}</p>
                                        <div class="RightAnimate-four">
                                            @include('frontend::components.widgets.custom-button', [
                                                'buttonUrl' => route('frontend.movie_detail', $movie->slug),
                                                'buttonTitle' => __('streamButtons.play_now'),
                                            ])
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="swiper-pagination d-block d-lg-none"></div>
        </div>
    </div>
</section>

<div class="container-fluid">
    <div class="overflow-hidden">
        @if ($latestMovies->count())
            <section class="related-movie-block mt-5">
                <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
                    <h4 class="main-title text-capitalize mb-0">{{ __('sectionTitle.latest_movies') ?? 'Latest Movies' }}</h4>
                    <a href="{{ route('frontend.movie') }}" class="text-primary iq-view-all text-decoration-none">{{ __('streamButtons.view_all') }}</a>
                </div>
                <div class="card-style-slider">
                    <div class="position-relative swiper swiper-card" data-slide="6" data-laptop="6" data-tab="3"
                        data-mobile="2" data-mobile-sm="2" data-autoplay="false" data-loop="false"
                        data-navigation="true" data-pagination="true">
                        <ul class="p-0 swiper-wrapper m-0 list-inline">
                            @foreach ($latestMovies as $movie)
                                <li class="swiper-slide">
                                    @include('frontend::components.cards.card-style', [
                                        'cardImage' => $movie->poster_url ?: 'media/rabbit-portrait.webp',
                                        'cardTitle' => $movie->title,
                                        'movietime' => $movie->runtime_minutes ? floor($movie->runtime_minutes / 60) . 'hr : ' . ($movie->runtime_minutes % 60) . 'mins' : null,
                                        'cardLang' => 'English',
                                        'cardPath' => route('frontend.movie_detail', $movie->slug),
                                        'cardGenres' => $movie->genres->take(2)->pluck('name')->all(),
                                        'productPremium' => (bool) $movie->tier_required,
                                    ])
                                </li>
                            @endforeach
                        </ul>
                        <div class="swiper-button swiper-button-next d-none d-lg-block"></div>
                        <div class="swiper-button swiper-button-prev d-none d-lg-block"></div>
                    </div>
                </div>
            </section>
        @endif

        @if ($popularShows->count())
            <section class="related-movie-block mt-5 mb-5">
                <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
                    <h4 class="main-title text-capitalize mb-0">{{ __('sectionTitle.popular_show') ?? 'Popular Shows' }}</h4>
                    <a href="{{ route('frontend.tv-show') }}" class="text-primary iq-view-all text-decoration-none">{{ __('streamButtons.view_all') }}</a>
                </div>
                <div class="card-style-slider">
                    <div class="position-relative swiper swiper-card" data-slide="6" data-laptop="6" data-tab="3"
                        data-mobile="2" data-mobile-sm="2" data-autoplay="false" data-loop="false"
                        data-navigation="true" data-pagination="true">
                        <ul class="p-0 swiper-wrapper m-0 list-inline">
                            @foreach ($popularShows as $show)
                                <li class="swiper-slide">
                                    @include('frontend::components.cards.card-style', [
                                        'cardImage' => $show->poster_url ?: 'media/vikings-portrait.webp',
                                        'cardTitle' => $show->title,
                                        'movietime' => null,
                                        'cardLang' => 'English',
                                        'cardPath' => route('frontend.tvshow_detail', $show->slug),
                                        'cardGenres' => $show->genres->take(2)->pluck('name')->all(),
                                        'productPremium' => (bool) $show->tier_required,
                                    ])
                                </li>
                            @endforeach
                        </ul>
                        <div class="swiper-button swiper-button-next d-none d-lg-block"></div>
                        <div class="swiper-button swiper-button-prev d-none d-lg-block"></div>
                    </div>
                </div>
            </section>
        @endif
    </div>
</div>

@include('frontend::components.widgets.mobile-footer')
@endsection
