@php
    $gSrc = media_url($genersImage);
@endphp
<div class="iq-card-geners position-relative card-hover-style-two">
    <div class="img-box position-relative">
        <a href="{{$genersUrl}}">
            <img src="{{ media_img($genersImage, 640) }}"
                 srcset="{{ media_srcset($genersImage, [320, 640]) }}"
                 sizes="(max-width: 768px) 320px, 640px"
                 alt="geners-img" class="img-fluid" loading="lazy" decoding="async">
            <h6 class="blog-description">{{$genersTitle}}</h6>
        </a>
    </div>
</div>
