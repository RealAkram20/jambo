{{--
    Renders a stored `tier_required` as the access level it actually gates at.

    Expects:
      $slug — the raw tier_required value (null = free).

    Why not just print the slug: day-pass, weekly-basic, basic and
    basic-yearly all gate at Basic, so printing the slug implies four
    different paywalls where there is only one. ContentTiers::label maps any
    of them back to the level the gate enforces.
--}}
@php
    use Modules\Subscriptions\app\Support\ContentTiers;

    $planLabel = ContentTiers::label($slug ?? null);
@endphp

@if ($planLabel)
    {{-- The badge shows the level; the tooltip names the exact plan stored,
         so an admin can still tell Basic Yearly from Basic Monthly. --}}
    <span class="badge bg-primary d-inline-flex align-items-center gap-1" style="font-size:10px;"
          title="Plan: {{ $slug }}">
        <i class="ph ph-lock-simple" style="font-size:11px;"></i>{{ $planLabel }}
    </span>
@else
    <span class="badge bg-secondary-subtle text-secondary-emphasis" style="font-size:10px;">Free</span>
@endif
