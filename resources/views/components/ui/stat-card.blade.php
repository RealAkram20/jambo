@props([
    'label' => '',
    'value' => '',
    'icon' => null,        // Phosphor class, e.g. "ph ph-user"
    'sub' => null,         // optional caption under the label
    'trend' => null,       // optional numeric delta
    'trendSuffix' => '%',
    'href' => null,
])

{{-- Mirrors the Streamit admin stat card (DashboardPages/IndexPage1.blade.php):
     plain .card > .card-body > .icon-space + .card-details. No accent border,
     no shadow, no hover — identical to the admin dashboard's cards. --}}

@php $tag = $href ? 'a' : 'div'; @endphp

<{{ $tag }} @if($href) href="{{ $href }}" @endif {{ $attributes->class(['card h-100', 'text-decoration-none text-reset' => (bool) $href]) }}>
    <div class="card-body">
        @if($icon)
            <div class="icon-space mb-5"><i class="{{ $icon }} fs-1"></i></div>
        @endif
        <div class="card-details">
            <h1 class="fw-semibold card-details-title mb-0">{{ $value }}</h1>
            <p class="mb-0 fs-6">{{ $label }}</p>
            @if($trend !== null || $sub)
                <div class="mt-1" style="font-size:12px;">
                    @if($trend !== null)
                        <span class="{{ (float) $trend >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ (float) $trend >= 0 ? '▲' : '▼' }} {{ abs((float) $trend) }}{{ $trendSuffix }}
                        </span>
                    @endif
                    @if($sub)<span class="text-muted">{{ $sub }}</span>@endif
                </div>
            @endif
        </div>
    </div>
</{{ $tag }}>
