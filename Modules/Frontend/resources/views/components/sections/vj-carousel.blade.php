{{--
    Renders a single VJ's carousel. Used by /movie (initial top-5 +
    load-more) and /series (same flow, just scoped to shows).

    Expects:
      $vj          : Vj model (needs ->name, ->slug)
      $items       : Collection of Movie OR Show models (with `genres` loaded)
      $contentKind : 'movie' (default) or 'show' — drives which detail
                     URL and which View-All URL the card / header link to.
--}}
@php
    $contentKind = $contentKind ?? 'movie';
    $isShow = $contentKind === 'show';
    $viewAllUrl = $isShow
        ? route('frontend.vj_series_detail', $vj->slug)
        : route('frontend.vj_detail', $vj->slug);
    $fallbackPoster = $isShow ? 'media/vikings-portrait.webp' : 'media/rabbit-portrait.webp';
    // Per-VJ display cap. The eager-loads in FrontendController
    // intentionally don't `->limit()` (Eloquent applies it to the
    // combined query and starves all but the first VJ), so we
    // slice here. View All link goes to the full per-VJ page.
    $items = $items->take(20);
@endphp
<section class="related-movie-block mt-5 jambo-vj-row" data-kind="{{ $contentKind }}">
    <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
        <h4 class="main-title text-capitalize mb-0">{{ $vj->name }}</h4>
        <a href="{{ $viewAllUrl }}"
           class="text-primary iq-view-all text-decoration-none flex-none">
            {{ __('streamButtons.view_all') ?? 'View All' }}
        </a>
    </div>
    <div class="card-style-slider">
        <div class="position-relative swiper swiper-card"
             data-slide="7" data-laptop="7" data-tab="4" data-mobile="3" data-mobile-sm="3"
             data-autoplay="false" data-loop="false"
             data-navigation="true" data-pagination="true">
            <ul class="p-0 swiper-wrapper m-0 list-inline">
                @forelse ($items as $item)
                    <li class="swiper-slide">
                        @include('frontend::components.cards.card-style', [
                            'cardImage'      => $item->poster_url ?: $fallbackPoster,
                            'cardTitle'      => $item->title,
                            'movietime'      => (! $isShow && $item->runtime_minutes)
                                ? floor($item->runtime_minutes / 60) . 'hr : ' . ($item->runtime_minutes % 60) . 'mins'
                                : null,
                            'cardLang'       => 'English',
                            'cardPath'       => $isShow
                                ? route('frontend.series_detail', $item->slug)
                                : route('frontend.movie_detail', $item->slug),
                            'cardGenres'     => $item->relationLoaded('genres') ? $item->genres->take(2)->pluck('name')->all() : null,
                            'productPremium' => (bool) $item->tier_required,
                            'watchableType'  => $isShow ? 'show' : 'movie',
                            'watchableId'    => $item->id,
                        ])
                    </li>
                @empty
                    <li class="swiper-slide"><p class="text-muted">Nothing here yet.</p></li>
                @endforelse
            </ul>
            <div class="swiper-button swiper-button-next d-none d-lg-block"></div>
            <div class="swiper-button swiper-button-prev d-none d-lg-block"></div>
        </div>
    </div>
</section>
