@php
    /**
     * One thumbnail in the left rail of the vertical slider.
     * Shows landscape image + title + runtime (no overlay — the title sits below).
     *
     * $item  Movie (with genres loaded)
     */
    $thumbImg = $item->backdrop_url ?: $item->poster_url;
    $thumbSrc = $thumbImg && \Illuminate\Support\Str::startsWith($thumbImg, ['http://', 'https://'])
        ? $thumbImg
        : ($thumbImg ? asset('frontend/images/' . $thumbImg) : asset('frontend/images/media/the-first-of-us.webp'));

    $runtime = $item->runtime_minutes
        ? floor($item->runtime_minutes / 60) . 'hr : ' . ($item->runtime_minutes % 60) . 'm'
        : null;
@endphp
<div class="swiper-slide swiper-bg">
    <div class="block-images position-relative">
        <div class="img-box slider--image">
            <img src="{{ $thumbSrc }}" class="w-100 rounded-3" alt="{{ $item->title }}" loading="lazy">
        </div>
        <div class="block-description">
            <h6 class="iq-title">{{ $item->title }}</h6>
            @if ($runtime)
                <div class="movie-time d-flex align-items-center my-2">
                    <div class="d-flex align-items-center gap-1 font-size-12">
                        <i class="ph ph-clock"></i>
                        <span class="text-body">{{ $runtime }}</span>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
