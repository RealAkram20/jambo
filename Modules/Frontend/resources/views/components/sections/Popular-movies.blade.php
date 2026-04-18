<div class="popular-movies-block section-wraper">
    <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
        <h4 class="main-title text-capitalize mb-0 fw-medium">{{ __('sectionTitle.popular_movies') }}</h4>
        @if (isset($viewAllBtn))
            <a href="{{ route('frontend.movie') }}" class="text-primary iq-view-all text-decoration-none">{{ __('streamButtons.view_all') }}</a>
        @endif
    </div>
    <div class="card-style-slider">
        <div class="position-relative swiper swiper-card" data-slide="7" data-laptop="5" data-tab="4" data-mobile="3"
            data-mobile-sm="3" data-autoplay="false" data-loop="true" data-navigation="true" data-pagination="true">
            <ul class="p-0 swiper-wrapper m-0 list-inline">
                @include('frontend::components.partials.section-cards', ['items' => $popularMovies ?? collect()])
            </ul>
            <div class="d-none d-lg-block">
                <div class="swiper-button swiper-button-next"></div>
                <div class="swiper-button swiper-button-prev"></div>
            </div>
        </div>
    </div>
</div>
