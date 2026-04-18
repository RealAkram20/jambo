{{--
    Single grid cell for the VJ detail pages. Wraps card-style in a
    Bootstrap column so both the server-rendered initial batch and the
    load-more AJAX response produce matching markup.

    Expects:
      $item        : Movie OR Show (with 'genres' loaded)
      $contentKind : 'movie' (default) or 'show'
--}}
@php
    $contentKind = $contentKind ?? 'movie';
    $isShow = $contentKind === 'show';
    $fallbackPoster = $isShow ? 'media/vikings-portrait.webp' : 'media/rabbit-portrait.webp';
@endphp
<div class="col">
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
</div>
