@extends('frontend::layouts.master', ['isSwiperSlider' => true, 'IS_MEGA' => true])

@section('content')
<div class="iq-banner-thumb-slider overflow-hidden">
    <div class="slider">
        <div class="position-relative slider-bg my-auto">
            {{-- Horizontal Banner start --}}
            <div class="horizontal_thumb_slider" data-swiper="slider-thumbs-ott">
                <div class="banner-thumb-slider-nav">
                    <div class="swiper-container " data-swiper="slider-thumbs-inner-ott">
                        <div class="swiper-wrapper">
                            @foreach ($heroItems ?? collect() as $item)
                                @include('frontend::components.partials.hero-thumb', ['item' => $item])
                            @endforeach
                        </div>
                    </div>
                    <div class="slider-prev swiper-button d-flex align-items-center justify-content-center">
                        <i class="iconly-Arrow-Left-2 icli"></i>
                    </div>
                    <div class="slider-next swiper-button d-flex align-items-center justify-content-center">
                        <i class="iconly-Arrow-Right-2 icli"></i>
                    </div>
                </div>
            </div>
            {{-- Horizontal Banner end --}}
            {{-- Bg Banner start --}}
            <div class="slider-images" data-swiper="slider-images-ott">
                <div class="swiper-container" data-swiper="slider-images-inner-ott">
                    <div class="swiper-wrapper m-0">
                        @foreach ($heroItems ?? collect() as $item)
                            @include('frontend::components.partials.hero-banner', ['item' => $item])
                        @endforeach
                    </div>
                    <div class="swiper-pagination d-block d-lg-none"></div>
                </div>
            </div>
            {{-- Bg Banner end --}}
        </div>
    </div>
</div>

{{-- No overflow-hidden wrapper here: card-hover on .iq-card extends
     ~1.25em outside each card and ~5em below via the ::after pseudo,
     so clipping cuts off the Play Now / wishlist reveal on the
     leftmost column and the bottom row. Horizontal page overflow is
     handled at the body level in custom.css. Same pattern as
     /movie, /series, /upcoming, /genres/*. --}}
<div class="container-fluid">
    @include('frontend::components.sections.continue-watching', ['value' => '6', 'sectionPaddingClass' => true])
    @include('frontend::components.sections.top-ten-block')
    @include('frontend::components.sections.top-ten-tvshow')
    @include('frontend::components.sections.vjs')
    @include('frontend::components.sections.only-on-streamit')
    @include('frontend::components.sections.fresh-picks-just-for-you')
    @include('frontend::components.sections.upcomming', ['viewAllBtn' => true])
</div>

@include('frontend::components.sections.verticle-slider')

<div class="container-fluid">
    @include('frontend::components.sections.Your-Favourite-Personality')
    @include('frontend::components.sections.Popular-movies', ['viewAllBtn' => true])
</div>

@include('frontend::components.sections.tab-slider')

<div class="container-fluid">
    @include('frontend::components.sections.geners')

    @include('frontend::components.sections.recommended', [
    'recommended' => __('sectionTitle.smart_shuffle'), 'viewAllBtn' => true,
    ])

    @include('frontend::components.sections.top-pict')
</div>

{{-- Mobile Footer --}}
@include('frontend::components.widgets.mobile-footer')
{{-- Mobile Footer End --}}
@endsection
