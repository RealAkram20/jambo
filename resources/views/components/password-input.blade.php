@props([
    'name',
    'id' => null,
    'value' => '',
    'placeholder' => '',
    'autocomplete' => 'new-password',
    'required' => false,
    'size' => null, // 'sm' | 'lg' | null — matches Bootstrap input-group sizes
])

{{--
    Bootstrap 5 input-group with a built-in show/hide toggle. Works
    anywhere the admin theme is loaded — no JS wiring needed per-usage
    because the global delegated handler (see layouts/app.blade.php)
    picks up any element with [data-password-toggle] on click.

    Using `.input-group` means the toggle sits flush next to the input
    at exactly the same height — automatically. No hardcoded offsets
    that can drift when the surrounding layout changes.

    Props:
      name          — input name attribute (required)
      id            — defaults to name, override for unique ids
      value         — pre-fill (usually old() on validation re-render)
      placeholder   — input placeholder text
      autocomplete  — defaults to "new-password"; pass "current-password"
                      on sign-in / confirm-password forms
      required      — boolean

    Any extra attributes (minlength, pattern, data-*, etc.) are
    forwarded to the <input> via Blade's $attributes bag.
--}}

@php
    $fieldId = $id ?? $name;
    $groupSize = $size ? 'input-group-' . $size : '';
    $inputSize = $size ? 'form-control-' . $size : '';
    $btnSize = $size ? 'btn-' . $size : '';
@endphp

<div class="input-group {{ $groupSize }} jambo-password-field">
    <input
        type="password"
        name="{{ $name }}"
        id="{{ $fieldId }}"
        value="{{ $value }}"
        placeholder="{{ $placeholder }}"
        autocomplete="{{ $autocomplete }}"
        @required($required)
        {{ $attributes->merge(['class' => trim('form-control ' . $inputSize)]) }}
    >
    <button type="button" class="btn btn-outline-secondary {{ $btnSize }}" data-password-toggle
            tabindex="-1" aria-label="Show password">
        <i class="ph ph-eye-slash"></i>
    </button>
</div>
