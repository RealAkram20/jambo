<!--Logo start-->
@php
    $customLogo = setting('logo');
    $customFavicon = setting('favicon');
@endphp
@if ($customLogo || $customFavicon)
    <img class="logo-normal"
        src="{{ branding_asset('favicon', $customLogo ?: 'dashboard/images/logo.png') }}"
        alt="{{ app_name() }}">
    <img class="logo-full"
        src="{{ branding_asset('logo', 'dashboard/images/logo-full.png') }}"
        alt="{{ app_name() }}">
@else
    <img class="logo-normal" src="{{ asset('dashboard/images/logo.png') }}" alt="#">
    <img class="logo-normal logo-white" src="{{ asset('dashboard/images/logo-white.png') }}" alt="#">
    <img class="logo-full"  src="{{ asset('dashboard/images/logo-full.png') }}" alt="#">
    <img class="logo-full logo-full-white"  src="{{ asset('dashboard/images/logo-full-white.png') }}" alt="#">
@endif
<!--logo End-->
