@props([
    'title' => '',
    'subtitle' => null,
])

{{-- Standalone header matching the admin card-header vocabulary
     (h4 title + muted 13px subtitle + right-aligned actions).
     Use when a header is needed OUTSIDE a card; inside a card use
     <x-ui.card title="..."> which renders the same bar. --}}

<div {{ $attributes->class(['d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4']) }}>
    <div>
        <h4 class="mb-1">{{ $title }}</h4>
        @if($subtitle)
            <p class="text-muted mb-0" style="font-size:13px;">{{ $subtitle }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="d-flex align-items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
