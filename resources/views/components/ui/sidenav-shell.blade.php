{{--
    <x-ui.sidenav-shell> — the Streamit sidebar chrome (fixed <aside>
    with logo header + collapse toggle + scrollable body) for
    admin-STYLE surfaces that are not the admin panel itself (e.g. the
    partner Creator Studio). Mirrors
    components/partials/sidebar.blade.php exactly, so pages that load
    the dashboard SCSS/JS bundle get the identical collapse/mini
    behavior via dashboard/js/sidebar.js.

    Slot: the menu — normally an <x-ui.sidenav :items=...>.

    Props:
      homeHref   — where the brand logo links
      brandLabel — optional text shown next to the logo (e.g. "Creator
                   Studio"); hidden when the sidebar minifies, like the
                   logo-title the theme uses.
--}}
@props([
    'homeHref' => null,
    'brandLabel' => null,
])

<aside class="sidebar sidebar-base sidebar-white sidebar-default navs-rounded-all"
    data-toggle="main-sidebar" data-sidebar="responsive">
    <div class="sidebar-header d-flex align-items-center justify-content-start">
        <a href="{{ $homeHref ?? route('dashboard') }}" class="navbar-brand">
            @include('components.widget.logo')
            @if ($brandLabel)
                <h5 class="logo-title mb-0">{{ $brandLabel }}</h5>
            @endif
        </a>
        <div class="sidebar-toggle" data-toggle="sidebar" data-active="true">
            <i class="chevron-right">
                <svg xmlns="http://www.w3.org/2000/svg" height="1.2rem" viewBox="0 0 512 512" fill="white">
                    <path
                        d="M470.6 278.6c12.5-12.5 12.5-32.8 0-45.3l-160-160c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L402.7 256 265.4 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l160-160zm-352 160l160-160c12.5-12.5 12.5-32.8 0-45.3l-160-160c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L210.7 256 73.4 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0z" />
                </svg>
            </i>
            <i class="chevron-left">
                <svg xmlns="http://www.w3.org/2000/svg" height="1.2rem" viewBox="0 0 512 512" fill="white"
                    transform="rotate(180)">
                    <path
                        d="M470.6 278.6c12.5-12.5 12.5-32.8 0-45.3l-160-160c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L402.7 256 265.4 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l160-160zm-352 160l160-160c12.5-12.5 12.5-32.8 0-45.3l-160-160c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L210.7 256 73.4 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0z" />
                </svg>
            </i>
        </div>
    </div>
    <div class="sidebar-body pt-0 data-scrollbar">
        <div class="sidebar-list">
            {{ $slot }}
        </div>
    </div>
    <div class="sidebar-footer"></div>
</aside>
