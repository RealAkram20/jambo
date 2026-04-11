@php
    $productPremium = $productPremium ?? false;
@endphp
<div class="iq-top-ten-block position-relative">
    <div class="block-image position-relative">
        <div class="img-box">
            <a class="overly-images" href="{{ $cardUrlPath }}">
                <img src="{{ asset('frontend/images/media/' . $imagePath) }}" alt="movie-card" class="object-cover rounded-3"
                    loading="lazy" />
            </a>
            <span class="top-ten-numbers texture-text">{{ $countValue }}</span>
        </div>
    </div>
    @if ($productPremium)
        <div class="position-absolute z-1 premium-product d-flex align-items-center justify-content-center"
            data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Premium" data-bs-original-title="{{__('streamPricing.premium')}}">
            <i class="ph-fill ph-crown fs-5"></i>
        </div>
    @endif
</div>
