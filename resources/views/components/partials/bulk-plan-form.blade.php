{{--
    Bulk "assign a subscription plan" control for an admin list page.

    Expects:
      $scope       — bulk scope key, e.g. 'movies' / 'series'. Must match the
                     page's data-bulk-scope so the shared JS mirrors the
                     selected row ids into this form.
      $action      — the bulk-tier route URL.
      $confirmKey  — data-jambo-confirm key ('bulk-tier-movies' | 'bulk-tier-series').
      $tierOptions — active tiers from ContentTiers::pickerOptions().

    On the option labels: several tiers share one access_level (Day Pass,
    Weekly, Basic Monthly and Basic Yearly are all Basic access), and the
    gate only ever compares levels. So each option spells out the access it
    grants alongside the plan's own name — otherwise "Basic Yearly" reads
    like a stricter gate than "Basic Monthly" when the two are identical.

    data-plan-label carries the ACCESS level rather than the plan name,
    because that's what the confirm dialog needs to promise accurately:
    picking Basic Yearly unlocks the title for anyone on any Basic plan.
--}}
@php
    use Modules\Subscriptions\app\Support\ContentTiers;
@endphp

<form id="{{ $scope }}-bulk-tier-form"
      method="POST"
      action="{{ $action }}"
      data-jambo-confirm="{{ $confirmKey }}"
      class="d-flex align-items-center gap-2 m-0">
    @csrf
    @method('PATCH')

    {{-- Filled with hidden ids[] inputs by the shared bulk JS. --}}
    <div data-bulk-ids="{{ $scope }}"></div>

    <label for="{{ $scope }}-bulk-tier" class="visually-hidden">Subscription plan</label>
    <select name="tier_required" id="{{ $scope }}-bulk-tier"
            class="form-select form-select-sm"
            style="min-width:260px;background:#161c2b;border-color:rgba(255,255,255,.12);color:#e9ecf2;">
        {{-- Empty value = untouched. The endpoint rejects it, so the Apply
             button stays disabled until a real choice is made. --}}
        <option value="">Set plan…</option>

        <option value="{{ ContentTiers::FREE }}" data-plan-label="Free" data-plan-free="1">
            Free — no plan needed
        </option>

        @foreach ($tierOptions as $tier)
            @continue($tier->access_level < 1)
            @php $levelLabel = ContentTiers::label($tier->slug); @endphp
            <option value="{{ $tier->slug }}" data-plan-label="{{ $levelLabel }}">
                {{ $tier->name }} — {{ $levelLabel }} access
                ({{ $tier->currency }} {{ number_format((float) $tier->price, 0) }} {{ $tier->periodLabel() }})
            </option>
        @endforeach
    </select>

    <button type="submit" class="btn btn-sm btn-primary text-nowrap" disabled>
        <i class="ph ph-lock-key me-1"></i> Apply plan
    </button>
</form>
