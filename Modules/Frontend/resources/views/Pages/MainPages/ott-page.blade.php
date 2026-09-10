@extends('frontend::layouts.master', ['isSwiperSlider' => true, 'IS_MEGA' => true])

{{-- The home page shipped no title at all, so it competed in Google as
     the bare brand name. seo:title is used verbatim (it is not suffixed
     with the app name), which is what lets the front door lead with what
     people actually search for rather than with "Jambo Films".

     The description is left to the global meta_description() — for the
     home page specifically, the site-wide blurb IS the right description. --}}
@section('seo:title', 'Watch Free VJ Translated Movies & Series — ' . app_name())

@section('content')
{{-- The page's single <h1>.

     The home page used to carry TEN — components/partials/tab-series-slide
     emitted one per slide, so the "primary heading" was ten different film
     titles and none of them described the page. Those are now <h2>, which
     left the front door with no <h1> at all.

     It is visually hidden because the hero is a full-bleed slider with no
     room for a heading in the design. This is the accessible fix, not a
     trick: the text matches the page's <title> and its actual subject, and a
     screen reader announces it exactly as a sighted user would read the page.
     Hiding text that contradicts the page would be cloaking; this does not. --}}
<h1 class="visually-hidden">Watch Free VJ Translated Movies &amp; Series on {{ app_name() }}</h1>

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

{{-- The shelves are no longer listed here.

     Their order, which of them show, and what each is called are one row per
     section in `home_sections`, dragged on /admin/home-sections, and the same
     rows order `GET /api/v1/home`. Editing this file to move a shelf would
     put the website back out of step with the app, which is the state this
     replaced. Move it on the screen instead.
 --}}
@include('frontend::components.sections.arranged')

{{-- Mobile Footer --}}
@include('frontend::components.widgets.mobile-footer')
{{-- Mobile Footer End --}}
@endsection
