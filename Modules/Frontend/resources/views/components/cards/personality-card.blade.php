@php
    $castSrc = media_url($castImage, null, 'frontend/images/cast');
    $castLink = $castLink ?? route('frontend.cast_details');
@endphp
<a href="{{ $castLink }}">
    <img src="{{ $castSrc }}" alt="personality" class="img-fluid object-cover mb-3 rounded-3 personality-img" loading="lazy" />
</a>
<div class="text-center">
    <h6 class="mb-0">
        <a href="{{ $castLink }}"
            class="font-size-14 text-decoration-none cast-title text-capitalize">{{ $castTitle }}</a>
    </h6>
    <a href="{{ $castLink }}"
        class="font-size-12 fw-semibold text-decoration-none text-capitalize text-body">{{ $castCategory }}&nbsp;&nbsp;{{ $otherCastCategory ?? '' }}</a>
</div>
