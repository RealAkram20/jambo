@php
    $tag = $tag ?? 'button';
@endphp
@if ($tag === 'inputType')
    <input
        {{ $attributes->merge(['type' => 'submit', 'value' => __('streamAccount.log_in'), 'class' => 'btn btn-primary w-100 custom-sign-btn']) }}>
    {{ $slot }}
    </input>
@else
    <button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn btn-btn btn-primary']) }}>
        {{ $slot }}
    </button>
@endif
