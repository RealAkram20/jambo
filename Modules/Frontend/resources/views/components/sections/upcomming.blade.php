@php
    $tvshowUpcomming = $tvshowUpcomming ?? false;
    $videoUpcoming   = $videoUpcoming   ?? false;
    $viewAllBtn      = $viewAllBtn      ?? false;

    if ($tvshowUpcomming) {
        $upcItems = $latestShows ?? collect();
        $upcIsShow = true;
        $upcTitle = __('sectionTitle.tv_upcoming_title');
        $upcHref = route('frontend.series');
        $upcFallback = 'media/vikings-portrait.webp';
    } else {
        $upcItems = ($upcomingMovies ?? collect())->count()
            ? $upcomingMovies
            : ($latestMovies ?? collect());
        $upcIsShow = false;
        $t = __('sectionTitle.upcoming_title');
        $upcTitle = $t === 'sectionTitle.upcoming_title' ? 'Upcoming' : $t;
        // Dedicated listing page now exists; old code routed to /movie
        // because there was nowhere upcoming-specific to send people.
        $upcHref = route('frontend.upcoming');
        $upcFallback = 'media/rabbit-portrait.webp';
    }
@endphp

<div class="streamit-block section-wraper">
    <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
        <h4 class="main-title text-capitalize mb-0 fw-medium">{{ $upcTitle }}</h4>
        @if ($viewAllBtn)
            <a href="{{ $upcHref }}" class="text-primary iq-view-all text-decoration-none flex-none">{{ __('streamButtons.view_all') }}</a>
        @endif
    </div>
    <div class="card-style-slider">
        <div class="position-relative swiper swiper-card" data-slide="7" data-laptop="5" data-tab="4" data-mobile="3"
            data-mobile-sm="3" data-autoplay="false" data-loop="false" data-navigation="true" data-pagination="true">
            <ul class="p-0 swiper-wrapper m-0 list-inline">
                @include('frontend::components.partials.section-cards', [
                    'items' => $upcItems,
                    'isShow' => $upcIsShow,
                    'fallbackImg' => $upcFallback,
                ])
            </ul>
            <div class="d-none d-lg-block">
                <div class="swiper-button swiper-button-next"></div>
                <div class="swiper-button swiper-button-prev"></div>
            </div>
        </div>
    </div>
</div>
