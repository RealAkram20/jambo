@php
    $row = is_object($row) ? (array) $row : (array) $row;
    $pid = $row['id'] ?? $row['person_id'] ?? '';
    $role = $row['role'] ?? 'actor';
    $charName = $row['character_name'] ?? '';
    $order = $row['display_order'] ?? 0;

    // Pre-resolve the selected person's display name so the dropdown
    // shows the chosen value on first render. Without this an edit
    // form would show just the id until the user opens the dropdown
    // (Select2 doesn't fetch labels for already-selected values).
    // $persons is still passed in by the form controllers as a
    // fallback for currently-attached cast — it's no longer used to
    // populate the entire <option> list (that was the bottleneck:
    // a million-row dropdown).
    $selectedPersonText = '';
    if ($pid && isset($persons)) {
        $selected = $persons->firstWhere('id', (int) $pid);
        if ($selected) {
            $selectedPersonText = trim($selected->first_name . ' ' . $selected->last_name);
        }
    }
@endphp
<div class="cast-row border rounded p-2 mb-2" style="background:#0b0f17;border-color:#1f2738 !important;">
    <div class="row g-2 align-items-center">
        <div class="col-md-4">
            <div class="input-group input-group-sm">
                <select class="form-select form-select-sm jambo-cast-person"
                        name="cast[{{ $i }}][person_id]"
                        data-placeholder="Search by name…"
                        style="flex: 1 1 auto; width: 1%;">
                    @if ($pid)
                        <option value="{{ $pid }}" selected>{{ $selectedPersonText ?: ('#' . $pid) }}</option>
                    @endif
                </select>
                <button type="button"
                        class="btn btn-primary"
                        data-jambo-new-person
                        title="Create a new person without leaving this page">
                    <i class="ph ph-plus"></i>
                </button>
            </div>
        </div>
        <div class="col-md-3">
            <select class="form-select form-select-sm" name="cast[{{ $i }}][role]">
                <option value="actor" @selected($role === 'actor')>Actor</option>
                <option value="actress" @selected($role === 'actress')>Actress</option>
                <option value="director" @selected($role === 'director')>Director</option>
                <option value="writer" @selected($role === 'writer')>Writer</option>
                <option value="producer" @selected($role === 'producer')>Producer</option>
                <option value="cinematographer" @selected($role === 'cinematographer')>Cinematographer</option>
            </select>
        </div>
        <div class="col-md-3">
            <input type="text" class="form-control form-control-sm" name="cast[{{ $i }}][character_name]" value="{{ $charName }}" placeholder="Character name (actors / actresses)">
        </div>
        <div class="col-md-1">
            <input type="number" class="form-control form-control-sm" name="cast[{{ $i }}][display_order]" value="{{ $order }}" min="0" title="Order">
        </div>
        <div class="col-md-1 text-end">
            <button type="button" class="btn btn-sm btn-danger-subtle remove-cast" title="Remove">
                <i class="ph ph-x"></i>
            </button>
        </div>
    </div>
</div>
