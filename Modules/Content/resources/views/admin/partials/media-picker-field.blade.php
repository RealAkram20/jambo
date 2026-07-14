{{--
    Reusable media picker field (image-style with preview + browse button).

    Props:
        key         — form field name & id
        label       — visible label
        value       — current value
        accept      — array of allowed extensions (e.g. ['jpg','png','webp'])
        aspect      — preview aspect ratio: '2/3', '16/9', '1/1'
        placeholder — input placeholder
        hint        — optional small helper text
        uid         — optional. Unique DOM handle for this instance.

    On `uid`: media-picker-script wires the Browse button to its preview with a
    document-wide querySelector on the key, so two pickers sharing a key on one
    page both resolve to the first one and the second silently stops working.
    That happens as soon as a field is repeated — e.g. one edit modal per row in
    a list. Pass `uid` (say "photo_url_7") to give each instance its own DOM
    handle while `name` stays the real column. Defaults to `key`, so every
    existing single-instance caller is unaffected.
--}}
@php
    $fieldKey     = $key;
    $fieldUid     = $uid ?? $key;
    $acceptCsv    = implode(',', $accept);
    $acceptLabel  = strtoupper(implode(' / ', $accept));
    $previewWidth = ($aspect ?? '2/3') === '2/3' ? '90px' : (($aspect ?? '') === '1/1' ? '100px' : '160px');
    $placeholder  = $placeholder ?? 'https://... or /storage/media/...';
@endphp

<div class="mb-3">
    <label for="{{ $fieldUid }}" class="form-label">{{ $label }}</label>
    <div class="d-flex align-items-start gap-3">
        <div class="border rounded bg-dark d-flex align-items-center justify-content-center overflow-hidden"
            style="width: {{ $previewWidth }}; aspect-ratio: {{ $aspect ?? '2/3' }}; flex-shrink:0;">
            <img data-media-preview="{{ $fieldUid }}"
                src="{{ $value ?: asset('dashboard/images/placeholder.png') }}"
                alt="{{ $label }} preview"
                style="width:100%; height:100%; object-fit: cover; {{ $value ? '' : 'opacity:.25;' }}"
                onerror="this.style.opacity='.25';">
        </div>
        <div class="flex-grow-1">
            <div class="input-group">
                {{-- id/data-* use the uid so repeated instances stay addressable;
                     name stays the column so the payload is unchanged. --}}
                <input type="text"
                    class="form-control @error($fieldKey) is-invalid @enderror"
                    id="{{ $fieldUid }}" name="{{ $fieldKey }}"
                    value="{{ $value }}" placeholder="{{ $placeholder }}"
                    data-media-url="{{ $fieldUid }}">
                <button type="button" class="btn btn-primary"
                    data-media-browse="{{ $fieldUid }}"
                    data-media-accept="{{ $acceptCsv }}"
                    data-media-preview-target="{{ $fieldUid }}">
                    <i class="ph ph-folder-open me-1"></i> Browse
                </button>
            </div>
            <small class="text-secondary">{{ $hint ?? $acceptLabel }}</small>
            @error($fieldKey) <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>
    </div>
</div>
