@php
    /**
     * One thumbnail in the upper rail of the OTT hero.
     *
     * $item      Movie or Show (must have _isShow set by the composer)
     */
    $isShow = $item->_isShow ?? false;
    $thumbImg = $item->poster_url;
    $thumbSrc = media_url($thumbImg, 'media/gameofhero-portrait.webp');

    if ($isShow) {
        $meta = $item->seasons->count() . ' ' . __('streamEpisode.season');
    } elseif ($item->runtime_minutes) {
        $meta = floor($item->runtime_minutes / 60) . 'hr : ' . ($item->runtime_minutes % 60) . 'm';
    } else {
        $meta = '';
    }
@endphp
<div class="swiper-slide swiper-bg">
    <div class="block-images position-relative ">
        <div class="img-box">
            <img src="{{ $thumbSrc }}" class="img-fluid" alt="img" loading="lazy">
            <div class="block-description">
                <h6 class="iq-title fw-500 line-count-1">{{ $item->title }}</h6>
                @if ($meta)
                    <div class="d-flex align-items-center gap-1">
                        <i class="ph ph-clock"></i>
                        <span class="fs-12">{{ $meta }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
