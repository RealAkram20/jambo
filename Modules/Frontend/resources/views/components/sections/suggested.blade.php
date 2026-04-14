<div class="suggested-block section-wraper">
    <div class="suggested-block section-wraper">
        <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
            <h4 class="main-title text-capitalize mb-0 fw-medium">{{ __('sectionTitle.suggested_block') }}</h4>
        </div>
        <div class="card-style-slider">
            <div class="position-relative swiper swiper-card" data-slide="6" data-laptop="4" data-tab="3" data-mobile="2"
                data-mobile-sm="2" data-autoplay="false" data-loop="false" data-navigation="true" data-pagination="true">
                <ul class="p-0 swiper-wrapper m-0 list-inline">
                    @include('frontend::components.partials.section-cards', ['items' => $freshMovies ?? collect()])
                </ul>
                <div class="d-none d-lg-block">
                    <div class="swiper-button swiper-button-next"></div>
                    <div class="swiper-button swiper-button-prev"></div>
                </div>
            </div>
        </div>
    </div>
</div>
