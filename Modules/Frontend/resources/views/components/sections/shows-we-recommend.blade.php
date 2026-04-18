<section class="recommended-blocks section-wraper">
    <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
        <h4 class="main-title text-capitalize mb-0">{{ __('frontendform.shows_recommend') }}</h4>
        @if (isset($viewAllBtn))
            <a href="{{ route('frontend.series') }}" class="text-primary iq-view-all text-decoration-none">{{ __('streamButtons.view_all') }}</a>
        @endif
    </div>
    <div class="card-style-slider">
        <div class="position-relative swiper swiper-card" data-slide="6" data-laptop="4" data-tab="3" data-mobile="2"
            data-mobile-sm="2" data-autoplay="false" data-loop="true" data-navigation="true" data-pagination="true">
            <ul class="p-0 swiper-wrapper m-0 list-inline">
                @include('frontend::components.partials.section-cards', [
                    'items' => $recommendedShows ?? collect(),
                    'isShow' => true,
                    'fallbackImg' => 'media/vikings-portrait.webp',
                ])
            </ul>
            <div class="d-none d-lg-block">
                <div class="swiper-button swiper-button-next"></div>
                <div class="swiper-button swiper-button-prev"></div>
            </div>
        </div>
    </div>
</section>
