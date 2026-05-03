{{--
    Homepage VJs slider — visually mirrors the genres slider, but each
    card is one VJ (narrator / translator). Clicking a card lands on
    the new combined VJ overview (/vj/{slug}) which shows the VJ's
    movies AND series in one place. Data wired by SectionDataComposer
    as $homeVjs.
--}}
<div class="movie-geners-block section-wraper">
    <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
        <h4 class="main-title text-capitalize mb-0 fw-medium">{{ __('streamTag.vjs') ?? 'VJs' }}</h4>
        <a href="{{ route('frontend.movie') }}" class="text-primary iq-view-all text-decoration-none flex-none">{{ __('streamButtons.view_all') }}</a>
    </div>
    <div class="card-style-slider">
        <div class="position-relative swiper swiper-card" data-slide="6" data-laptop="6" data-tab="3" data-mobile="2"
            data-mobile-sm="2" data-autoplay="false" data-loop="true" data-navigation="true" data-pagination="true">
            <ul class="p-0 swiper-wrapper m-0 list-inline geners-card genres-list">
                @forelse ($homeVjs ?? collect() as $vj)
                    <li class="swiper-slide">
                        @include('frontend::components.cards.card-genres-grid', [
                            'image' => $vj->featured_image_url ?: 'media/rabbit.webp',
                            'title' => $vj->name,
                            'genersUrl' => route('frontend.vj_detail', $vj->slug),
                        ])
                    </li>
                @empty
                    <li class="swiper-slide"><p class="text-muted">{{ __('streamTag.no_results') ?? 'No VJs yet.' }}</p></li>
                @endforelse
            </ul>
            <div class="d-none d-lg-block">
                <div class="swiper-button swiper-button-next"></div>
                <div class="swiper-button swiper-button-prev"></div>
            </div>
        </div>
    </div>
</div>
