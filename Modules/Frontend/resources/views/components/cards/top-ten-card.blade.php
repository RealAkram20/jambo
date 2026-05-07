@php
    $productPremium = $productPremium ?? false;
    // Optional badge label like "in Movies Today" / "in Series Today".
    // When null, the rank badge is hidden so the shared card stays usable
    // by callers that don't want it.
    $badgeLabel = $badgeLabel ?? null;
    $topTenSrc = media_url($imagePath, null, 'frontend/images/media');
@endphp
<div class="iq-top-ten-block position-relative">
    <div class="block-image position-relative">
        <div class="img-box">
            <a class="overly-images" href="{{ $cardUrlPath }}">
                <img src="{{ media_img($imagePath, 640, null, 'frontend/images/media') }}"
                     srcset="{{ media_srcset($imagePath, [320, 640], null, 'frontend/images/media') }}"
                     sizes="(max-width: 768px) 320px, 640px"
                     alt="movie-card" class="object-cover rounded-3" loading="lazy" decoding="async" />
            </a>
            {{-- Rank badge mirrors the series tab-slider so the rail's ordering is
                 explicit instead of relying on the decorative big numeral alone.
                 Only renders when the caller passes $badgeLabel. --}}
            @if ($badgeLabel)
                <div class="d-flex align-items-center gap-2 position-absolute top-0 start-0 m-2 px-2 py-1 rounded-3"
                     style="background: rgba(0, 0, 0, 0.55); z-index: 3; pointer-events: none;">
                    <img src="{{ asset('frontend/images/pages/trending-label.webp') }}"
                         alt="{{ __('sectionTitle.top_ten') }}"
                         style="height: 1.5em; width: auto;"
                         class="rounded-2">
                    <span class="text-gold fw-bold text-nowrap" style="font-size: 0.75em; line-height: 1;">
                        #{{ $countValue }} {{ $badgeLabel }}
                    </span>
                </div>
            @endif
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
