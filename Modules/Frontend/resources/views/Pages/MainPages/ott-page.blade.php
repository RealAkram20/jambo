@extends('frontend::layouts.master', ['isSwiperSlider' => true, 'IS_MEGA' => true])

@section('content')
<div class="iq-banner-thumb-slider overflow-hidden">
    <div class="slider">
        <div class="position-relative slider-bg my-auto">
            {{-- Horizontal Thumbnail Banner --}}
            <div class="horizontal_thumb_slider" data-swiper="slider-thumbs-ott">
                <div class="banner-thumb-slider-nav">
                    <div class="swiper-container" data-swiper="slider-thumbs-inner-ott">
                        <div class="swiper-wrapper">
                            @foreach ($heroMovies ?? collect() as $movie)
                                @php
                                    $thumb = $movie->poster_url;
                                    $thumbSrc = $thumb && \Illuminate\Support\Str::startsWith($thumb, ['http://', 'https://'])
                                        ? $thumb
                                        : ($thumb ? asset('frontend/images/' . $thumb) : asset('frontend/images/media/gameofhero-portrait.webp'));
                                @endphp
                                <div class="swiper-slide swiper-bg">
                                    <div class="block-images position-relative">
                                        <div class="img-box">
                                            <img src="{{ $thumbSrc }}" class="img-fluid" alt="{{ $movie->title }}" loading="lazy">
                                            <div class="block-description">
                                                <h6 class="iq-title fw-500 line-count-1">{{ $movie->title }}</h6>
                                                @if ($movie->runtime_minutes)
                                                    <div class="d-flex align-items-center gap-1">
                                                        <i class="ph ph-clock"></i>
                                                        <span class="fs-12">{{ floor($movie->runtime_minutes / 60) }}hr : {{ $movie->runtime_minutes % 60 }}m</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            {{-- Thumbnail Banner end --}}

            {{-- Big Banner --}}
            <div class="slider-images" data-swiper="slider-images-ott">
                <div class="swiper-container" data-swiper="slider-images-inner-ott">
                    <div class="swiper-wrapper m-0">
                        @foreach ($heroMovies ?? collect() as $movie)
                            @php
                                $bg = $movie->backdrop_url ?: $movie->poster_url;
                                $bgSrc = $bg && \Illuminate\Support\Str::startsWith($bg, ['http://', 'https://'])
                                    ? $bg
                                    : ($bg ? asset('frontend/images/' . $bg) : asset('frontend/images/media/gameofhero.webp'));
                                $heroCast = $movie->cast?->filter(fn ($p) => ($p->pivot->role ?? null) === 'actor')->take(3) ?? collect();
                            @endphp
                            <div class="swiper-slide banner-bg p-0">
                                <div class="slider--image block-images" style="background-image: url('{{ $bgSrc }}');">
                                    <div class="container-fluid position-relative">
                                        <div class="row align-items-center h-100 slider-content-full-height">
                                            <div class="col-lg-5 col-md-12">
                                                <div class="slider-content">
                                                    <h2 class="texture-text big-font letter-spacing-1 line-count-1 RightAnimate-two mb-1 mb-md-3">
                                                        {{ $movie->title }}
                                                    </h2>
                                                    <div class="d-flex flex-wrap align-items-center gap-3 py-2 RightAnimate-three">
                                                        @if ($movie->tier_required)
                                                            <span class="badge rounded-0 text-white text-uppercase bg-secondary mr-3 fw-bold">{{ strtoupper($movie->tier_required) }}</span>
                                                        @endif
                                                        <div class="d-flex align-items-center gap-3">
                                                            <ul class="ratting-start p-0 m-0 list-inline text-warning d-flex align-items-center justify-content-left gap-1">
                                                                @for ($i = 1; $i <= 5; $i++)
                                                                    <li><i class="ph-fill ph-star{{ $i > ($movie->rating ?? 5) ? '-half' : '' }}" aria-hidden="true"></i></li>
                                                                @endfor
                                                            </ul>
                                                            <span>
                                                                <img src="{{ asset('frontend/images/pages/imdb-logo.svg') }}" alt="imdb logo" class="img-fluid imdb-img">
                                                            </span>
                                                        </div>
                                                        @if ($movie->runtime_minutes)
                                                            <div class="d-flex align-items-center gap-1">
                                                                <i class="ph ph-clock"></i>
                                                                <span class="font-size-16 fw-500">{{ floor($movie->runtime_minutes / 60) }}hr : {{ $movie->runtime_minutes % 60 }}m</span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    @if ($movie->synopsis)
                                                        <p class="line-count-3 my-3 RightAnimate-two">{{ $movie->synopsis }}</p>
                                                    @endif

                                                    <div class="RightAnimate-three mt-2">
                                                        @if ($movie->relationLoaded('genres') && $movie->genres->count())
                                                            <div class="text-primary font-size-14 text-capitalize mb-1">
                                                                {{ __('streamTag.genre') }}:
                                                                @foreach ($movie->genres->take(3) as $g)
                                                                    <a href="javascript:void(0)" class="text-body text-decoration-none fw-normal">{{ $g->name }}{{ $loop->last ? '' : ',' }}</a>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                        @if ($heroCast->count())
                                                            <div class="text-primary font-size-14 text-capitalize">
                                                                {{ __('streamTag.starrting') ?? __('favouritePersonalities.starring') }}:
                                                                @foreach ($heroCast as $p)
                                                                    <a href="{{ route('frontend.cast_details', $p->slug) }}" class="text-body text-decoration-none fw-normal">{{ trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? '')) }}{{ $loop->last ? '' : ',' }}</a>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <div class="RightAnimate-four mt-4 pt-2">
                                                        @include('frontend::components.widgets.custom-button', [
                                                            'buttonTitle' => __('streamButtons.play_now'),
                                                            'buttonUrl' => route('frontend.movie_detail', $movie->slug),
                                                        ])
                                                    </div>
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
            {{-- Big Banner end --}}
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="overflow-hidden">
        @include('frontend::components.sections.continue-watching', ['value' => '6', 'sectionPaddingClass' => true])
        @include('frontend::components.sections.top-ten-block')
        @include('frontend::components.sections.upcomming', ['viewAllBtn' => true])
    </div>
</div>

@include('frontend::components.sections.verticle-slider')

<div class="container-fluid">
    <div class="overflow-hidden">
        @include('frontend::components.sections.Popular-movies', ['viewAllBtn' => true])
    </div>
</div>

@include('frontend::components.sections.tranding-tab')

<div class="container-fluid">
    <div class="overflow-hidden">
        @include('frontend::components.sections.recommended', [
            'recommended' => __('sectionTitle.recommended_for_you'),
            'viewAllBtn' => true,
        ])
    </div>
</div>

{{-- Mobile Footer --}}
@include('frontend::components.widgets.mobile-footer')
@endsection
