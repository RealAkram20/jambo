@php
    /**
     * $items       collection of Movie or Show models
     * $isShow      true to link to tvshow detail, else movie detail
     * $fallbackImg default poster path when a row has none
     */
    $items = $items ?? collect();
    $isShow = $isShow ?? false;
    $fallbackImg = $fallbackImg ?? 'media/rabbit-portrait.webp';
    $isCardStyle2 = $isCardStyle2 ?? false;
@endphp

@forelse ($items as $item)
    <li class="swiper-slide">
        @include('frontend::components.cards.card-style', [
            'cardImage' => $item->poster_url ?: $fallbackImg,
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
            'isCardStyle2' => $isCardStyle2,
            'watchableType' => $isShow ? 'show' : 'movie',
            'watchableId'   => $item->id,
        ])
    </li>
@empty
    <li class="swiper-slide"><p class="text-muted">{{ __('streamTag.no_results') ?? 'Nothing here yet.' }}</p></li>
@endforelse
