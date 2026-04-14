@php
    $gSrc = \Illuminate\Support\Str::startsWith($genersImage, ['http://', 'https://'])
        ? $genersImage
        : asset('frontend/images/' . $genersImage);
@endphp
<div class="iq-card-geners position-relative card-hover-style-two">
    <div class="img-box position-relative">
        <a href="{{$genersUrl}}">
            <img src="{{ $gSrc }}" alt="geners-img" class="img-fluid">
            <h6 class="blog-description">{{$genersTitle}}</h6>
        </a>
    </div>
</div>
