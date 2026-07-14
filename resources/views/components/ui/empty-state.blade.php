@props([
    'icon' => null,        // optional Phosphor class
    'title' => null,
    'message' => null,
])

{{-- Mirrors the admin empty state (movies/index.blade.php:152): centered,
     muted, 14px, optional CTA. When used inside a table, wrap this in a
     <td colspan="N"> yourself. --}}

<div {{ $attributes->class(['text-center py-5 text-muted']) }} style="font-size:14px;">
    @if($icon)
        <i class="{{ $icon }} d-block mb-2" style="font-size:32px;opacity:.5;"></i>
    @endif
    @if($title)<div class="mb-1">{{ $title }}</div>@endif
    @if($message)<div style="font-size:13px;">{{ $message }}</div>@endif
    @isset($action)<div class="mt-2">{{ $action }}</div>@endisset
</div>
