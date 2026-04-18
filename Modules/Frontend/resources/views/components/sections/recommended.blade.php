@php
    $downloadUpcoming   = $downloadUpcoming   ?? false;
    $restrictedUpcoming = $restrictedUpcoming ?? false;
    $relatedUpcoming    = $relatedUpcoming    ?? false;
    $recommended        = $recommended        ?? __('sectionTitle.recommended_tv_show');
    $viewAllBtn         = $viewAllBtn         ?? false;

    // Pick which collection to display based on the caller's mode flags.
    // movies recommended by default; show recommendations when "related"
    // or the caller passes a TV-show phrase.
    $items = $recommendedMovies ?? collect();
    if ($relatedUpcoming) {
        $items = $recommendedShows ?? collect();
    }
    $isShow = $relatedUpcoming;
    $fallbackImg = $isShow ? 'media/vikings-portrait.webp' : 'media/rabbit-portrait.webp';
    $sectionClass = $downloadUpcoming ? 'recommended-block section-padding-top' : 'recommended-block section-wraper';
@endphp

<section class="{{ $sectionClass }}">
    <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
        <h4 class="main-title text-capitalize mb-0">{{ $recommended }}</h4>
        @if ($viewAllBtn)
            <a href="{{ $isShow ? route('frontend.series') : route('frontend.movie') }}" class="text-primary iq-view-all text-decoration-none">{{ __('streamButtons.view_all') }}</a>
        @endif
    </div>
    <div class="card-style-slider">
        <div class="position-relative swiper swiper-card" data-slide="7" data-laptop="7" data-tab="4" data-mobile="3"
            data-mobile-sm="3" data-autoplay="false" data-loop="false" data-navigation="true" data-pagination="true">
            <ul class="p-0 swiper-wrapper m-0 list-inline">
                @include('frontend::components.partials.section-cards', [
                    'items' => $items,
                    'isShow' => $isShow,
                    'fallbackImg' => $fallbackImg,
                ])
            </ul>
            <div class="d-none d-lg-block">
                <div class="swiper-button swiper-button-next"></div>
                <div class="swiper-button swiper-button-prev"></div>
            </div>
        </div>
    </div>
</section>
