{{-- One randomly-drawn admin category shelf. Replaces the retired
     algorithmic rails (Top Picks for You / Popular Movies / Fresh
     Picks) — each page load draws fresh categories the admin has NOT
     pinned to the homepage, so these never duplicate the pinned
     category-rails block. `$slot` (0-based) picks which draw this
     include renders; the draws are shared per-request, so distinct
     slots on one page never repeat a category. Renders nothing when
     there aren't enough non-empty categories to fill the slot. --}}
@php $cat = ($randomHomeCategories ?? collect())->get($slot ?? 0); @endphp

@if ($cat)
    @include('frontend::components.partials.category-rail', ['cat' => $cat])
@endif
