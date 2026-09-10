@php
    // 640: a genre tile in the grid, matching genres-card.
    $ggSrc = media_img($image, 640);
@endphp
<div class="iq-card-geners position-relative card-hover-style-two">
    <div class="img-box position-relative">
        <a href="{{ $genersUrl }}">
            <img src="{{ $ggSrc }}" alt="geners-img" class="img-fluid" loading="lazy" />
            <h6 class="blog-description">{{ $title }}</h6>
        </a>
    </div>
</div>
