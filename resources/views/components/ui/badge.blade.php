@props([
    'variant' => 'secondary', // success|danger|warning|info|primary|secondary
    'soft' => false,          // false = solid bg-* (admin status default);
                              // true  = subtle bg-*-subtle text-*-emphasis (admin tag default)
])

{{-- Mirrors admin badges: solid `badge bg-success` for statuses
     (movies/index.blade.php:124), subtle `badge bg-*-subtle text-*-emphasis`
     for tags/genres (movies/index.blade.php:113). --}}

@php
    $cls = $soft
        ? "badge bg-{$variant}-subtle text-{$variant}-emphasis"
        : "badge bg-{$variant}";
@endphp

<span {{ $attributes->class([$cls]) }}>{{ $slot }}</span>
