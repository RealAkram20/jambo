<div class="top-ten-block">
    <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
        <h4 class="main-title text-capitalize mb-0">{{ __('sectionTitle.top_10_tvshow_to_watch') }}</h4>
    </div>
    <div class="card-style-slider">
        <div class="position-relative swiper swiper-card iq-top-ten-block-slider" data-slide="7" data-laptop="7"
            data-tab="4" data-mobile="3" data-mobile-sm="3" data-autoplay="false" data-loop="false"
            data-navigation="true" data-pagination="true">
            <ul class="p-0 swiper-wrapper mb-5 list-inline">
                @forelse ($topShows ?? collect() as $i => $show)
                    <li class="swiper-slide">
                        @include('frontend::components.cards.top-ten-card', [
                            'imagePath' => $show->poster_url ?: 'vikings-portrait.webp',
                            'countValue' => $i + 1,
                            'cardUrlPath' => route('frontend.series_detail', $show->slug),
                            'productPremium' => (bool) $show->tier_required,
                        ])
                    </li>
                @empty
                    <li class="swiper-slide"><p class="text-muted">{{ __('streamTag.no_results') ?? 'No top series yet.' }}</p></li>
                @endforelse
            </ul>
            <div class="d-none d-lg-block">
                <div class="swiper-button swiper-button-next"></div>
                <div class="swiper-button swiper-button-prev"></div>
            </div>
        </div>
    </div>
</div>
