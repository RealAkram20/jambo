{{-- One category shelf. Expects `$cat` — a Category carrying a
     `railItems` collection (published movies + series merged, items
     tagged `_isShow` for per-card routing). Shared by the pinned
     homepage rails (category-rails) and the random replacement rails
     (random-category-rail). Cards can't use section-cards here
     because that partial assumes one content type per rail; this
     mirrors its card args. --}}
<div class="category-rail-block section-wraper">
    <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
        <h4 class="main-title text-capitalize mb-0 fw-medium">{{ $cat->name }}</h4>
        <a href="{{ route('frontend.category', $cat->slug) }}" class="text-primary iq-view-all text-decoration-none flex-none">{{ __('streamButtons.view_all') }}</a>
    </div>
    <div class="card-style-slider">
        <div class="position-relative swiper swiper-card" data-slide="7" data-laptop="7" data-tab="4" data-mobile="3"
            data-mobile-sm="3" data-autoplay="false" data-loop="false" data-navigation="true" data-pagination="true">
            <ul class="p-0 swiper-wrapper m-0 list-inline">
                @foreach ($cat->railItems as $item)
                    @php $isShow = (bool) ($item->_isShow ?? false); @endphp
                    <li class="swiper-slide">
                        @include('frontend::components.cards.card-style', [
                            'cardImage' => $item->poster_url ?: ($isShow ? 'media/vikings-portrait.webp' : 'media/rabbit-portrait.webp'),
                            'cardTitle' => $item->title,
                            'movietime' => ! $isShow && $item->runtime_minutes
                                ? floor($item->runtime_minutes / 60) . 'hr : ' . ($item->runtime_minutes % 60) . 'mins'
                                : null,
                            'cardLang' => 'English',
                            'cardPath' => $isShow
                                ? route('frontend.series_detail', $item->slug)
                                : route('frontend.movie_detail', $item->slug),
                            'cardGenres' => $item->relationLoaded('genres') ? $item->genres->take(2)->pluck('name')->all() : null,
                            'productPremium' => (bool) $item->tier_required,
                            'watchableType' => $isShow ? 'show' : 'movie',
                            'watchableId'   => $item->id,
                        ])
                    </li>
                @endforeach
            </ul>
            <div class="d-none d-lg-block">
                <div class="swiper-button swiper-button-next"></div>
                <div class="swiper-button swiper-button-prev"></div>
            </div>
        </div>
    </div>
</div>
