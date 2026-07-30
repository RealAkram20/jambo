{{--
    Single-item "Required plan" select for the movie / show / episode forms.

    Expects:
      $current     — the model's stored tier_required (null = free).
      $tierOptions — active tiers from ContentTiers::pickerOptions().

    This has to list every assignable tier, not just Basic and Premium. The
    bulk plan action can set any of them, and a select that lacks an option
    for the stored value silently falls back to its first option — so
    opening a Day Pass title in this form and saving would have quietly
    downgraded it to Free.

    The same guard covers a tier that has since been deactivated: if the
    stored slug isn't in the active list, it's still rendered (flagged
    inactive) so a routine save can't drop it.
--}}
@php
    use Modules\Subscriptions\app\Support\ContentTiers;

    $selected = old('tier_required', $current);
    $activeSlugs = $tierOptions->pluck('slug')->all();
    $isOrphaned = $selected && !in_array($selected, $activeSlugs, true);
@endphp

<label for="tier_required" class="form-label">Required plan</label>
<select name="tier_required" id="tier_required" class="form-select">
    <option value="" @selected(!$selected)>Free — no subscription required</option>

    @foreach ($tierOptions as $tier)
        @continue($tier->access_level < 1)
        @php $levelLabel = ContentTiers::label($tier->slug); @endphp
        <option value="{{ $tier->slug }}" @selected($selected === $tier->slug)>
            {{ $tier->name }} — {{ $levelLabel }} access
            ({{ $tier->currency }} {{ number_format((float) $tier->price, 0) }} {{ $tier->periodLabel() }})
        </option>
    @endforeach

    @if ($isOrphaned)
        <option value="{{ $selected }}" selected>{{ $selected }} — inactive plan (kept as-is)</option>
    @endif
</select>

<p class="text-muted mb-0 mt-2" style="font-size:12px;">
    Access is enforced by level, so every plan that grants the same level unlocks this title —
    a Day Pass viewer sees everything a Basic Monthly viewer sees.
</p>
