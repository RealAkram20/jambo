<div class="streamit-card-height-block">
    <div class="d-flex align-items-center justify-content-between px-1 mb-4">
        <h4 class="main-title text-capitalize mb-0 fw-medium">{{ __('sectionTitle.best_in_tv') }}</h4>
        <a href="{{ route('frontend.series') }}" class="text-primary iq-view-all text-decoration-none flex-none">{{ __('streamButtons.view_all') }}</a>
    </div>
    <div class="card-style-slider">
        <div class="position-relative swiper swiper-card" data-slide="5" data-laptop="3" data-tab="3" data-mobile="2"
            data-mobile-sm="2" data-autoplay="false" data-loop="false" data-navigation="true" data-pagination="true">
            <ul class="p-0 swiper-wrapper m-0 list-inline">
                @include('frontend::components.partials.section-cards', [
                    'items' => $topShows ?? collect(),
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
</div>
