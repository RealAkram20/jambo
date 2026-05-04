@php
    $castSrc = media_url($castImage, null, 'frontend/images/cast');
    $castLink = $castLink ?? route('frontend.cast_details');
@endphp
<a href="{{ $castLink }}">
    <img src="{{ media_img($castImage, 384, null, 'frontend/images/cast') }}"
         srcset="{{ media_srcset($castImage, [192, 384], null, 'frontend/images/cast') }}"
         sizes="(max-width: 768px) 192px, 384px"
         alt="personality" class="img-fluid object-cover mb-3 rounded-3 personality-img" loading="lazy" decoding="async" />
</a>
<div class="text-center">
    <h6 class="mb-0">
        <a href="{{ $castLink }}"
            class="font-size-14 text-decoration-none cast-title text-capitalize">{{ $castTitle }}</a>
    </h6>
    @if (!empty($castCategory))
        <a href="{{ $castLink }}"
            class="font-size-12 fw-semibold text-decoration-none text-capitalize text-body">{{ $castCategory }}&nbsp;&nbsp;{{ $otherCastCategory ?? '' }}</a>
    @endif
</div>
