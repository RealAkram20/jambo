{{-- One admin category shelf, in admin drag order. Replaces the
     retired algorithmic rails (Top Picks for You / Popular Movies /
     Fresh Picks) — `$slot` N renders the (N+2)th Visible Home category
     by sort_order (the 1st lives in the pinned category-rails block, so
     slots never duplicate it). Renders nothing when there aren't
     enough non-empty categories to fill the slot. --}}
@php $cat = ($randomHomeCategories ?? collect())->get($slot ?? 0); @endphp

@if ($cat)
    @include('frontend::components.partials.category-rail', ['cat' => $cat])
@endif
