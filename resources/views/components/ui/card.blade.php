@props([
    'title' => null,
    'subtitle' => null,
    'padded' => true,   // :padded="false" for flush content like tables
])

{{-- Mirrors the admin module card (Content .../admin/movies/index.blade.php):
     plain .card with a card-header flex bar (h4.card-title + muted subtitle
     + optional actions), then card-body. --}}

<div {{ $attributes->class(['card']) }}>
    @if($title || isset($actions))
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                @if($title)<h4 class="card-title mb-1">{{ $title }}</h4>@endif
                @if($subtitle)<p class="text-muted mb-0" style="font-size:13px;">{{ $subtitle }}</p>@endif
            </div>
            @isset($actions)
                <div class="d-flex align-items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    <div class="{{ $padded ? 'card-body' : '' }}">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="card-footer">{{ $footer }}</div>
    @endisset
</div>
