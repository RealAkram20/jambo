@php
    $row = is_object($row) ? (array) $row : (array) $row;
    $pid = $row['id'] ?? $row['person_id'] ?? '';
    $role = $row['role'] ?? 'actor';
    $charName = $row['character_name'] ?? '';
    $order = $row['display_order'] ?? 0;
@endphp
<div class="cast-row border rounded p-2 mb-2" style="background:#0b0f17;border-color:#1f2738 !important;">
    <div class="row g-2 align-items-center">
        <div class="col-md-4">
            <select class="form-select form-select-sm" name="cast[{{ $i }}][person_id]">
                <option value="">— Select person —</option>
                @foreach ($persons as $person)
                    <option value="{{ $person->id }}" @selected((string) $pid === (string) $person->id)>
                        {{ trim($person->first_name . ' ' . $person->last_name) }}
                    </option>
                @endforeach
            </select>
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
