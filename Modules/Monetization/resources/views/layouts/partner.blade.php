<!DOCTYPE html>
<html lang="en" data-bs-theme="dark" dir="ltr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ app_name() }} — Creator Studio</title>

    @include('components.partials.head.head')
    @vite(['resources/css/app.scss', 'public/dashboard/scss/streamit.scss',
    'public/dashboard/scss/dashboard-custom.scss', 'public/dashboard/scss/custom.scss',
    'resources/js/app.js'])
    @include('components.partials.theme-tokens')

    <script>
        // Seed the Streamit template's persisted theme storage so its
        // setting JS doesn't flash/reset colours on first load — same
        // seed the admin layout plants (layouts/app.blade.php).
        (function () {
            try {
                var storageKey = 'streamit';
                if (sessionStorage.getItem(storageKey) || localStorage.getItem(storageKey)) return;
                sessionStorage.setItem(storageKey, JSON.stringify({
                    saveLocal: 'sessionStorage',
                    storeKey: storageKey,
                    setting: {
                        theme_scheme_direction: {
                            target: 'html', choices: ['ltr', 'rtl'], value: 'ltr'
                        },
                        theme_color: {
                            colors: {
                                "--bs-primary": "#1A98FF",
                                "--bs-primary-rgb": "26, 152, 255",
                                "--bs-secondary": "#adafb8",
                                "--bs-tertiray": "#89F425"
                            },
                            value: "color-2"
                        }
                    }
                }));
            } catch (e) {}
        })();
    </script>
</head>

<body>
    @php
        // Creator Studio menu — same shape everywhere the unified
        // sidenav renders. Role gating is already done by the route
        // group (role:partner); every partner sees all entries.
        //
        // The Account section surfaces the profile-hub tabs so a
        // partner/VJ has ONE dashboard: those pages render inside
        // this same shell for partners (profile-hub/_layout picks the
        // shell by role), so clicking them never leaves the studio.
        $partnerUsername = auth()->user()->username;
        $partnerUnread = auth()->user()->unreadNotifications()->count();
        $partnerNavItems = [
            ['section' => 'Studio'],
            ['label' => 'Overview',       'icon' => 'ph-squares-four',        'href' => route('partner.dashboard'),        'active' => request()->routeIs('partner.dashboard')],
            ['label' => 'My titles',      'icon' => 'ph-film-strip',          'href' => route('partner.titles'),           'active' => request()->routeIs('partner.titles') || request()->routeIs('partner.content.*')],
            ['label' => 'Statements',     'icon' => 'ph-receipt',             'href' => route('partner.statements.index'), 'active' => request()->routeIs('partner.statements.*')],
            ['section' => 'Payouts'],
            ['label' => 'Wallet',         'icon' => 'ph-wallet',              'href' => route('partner.wallet'),           'active' => request()->routeIs('partner.wallet')],
            ['label' => 'Withdrawals',    'icon' => 'ph-hand-coins',          'href' => route('partner.withdrawals.index'), 'active' => request()->routeIs('partner.withdrawals.*')],
            ['label' => 'Payout details', 'icon' => 'ph-identification-card', 'href' => route('partner.payout-profile'),   'active' => request()->routeIs('partner.payout-profile')],
            ['section' => 'Account'],
            ['label' => 'Profile',       'icon' => 'ph-user-circle',  'href' => route('partner.profile'),       'active' => request()->routeIs('partner.profile')],
            ['label' => 'Security',      'icon' => 'ph-shield-check', 'href' => route('partner.security'),      'active' => request()->routeIs('partner.security')],
            ['label' => 'Devices',       'icon' => 'ph-devices',      'href' => route('partner.devices'),       'active' => request()->routeIs('partner.devices')],
            ['label' => 'Notifications', 'icon' => 'ph-bell',         'href' => route('partner.notifications'), 'active' => request()->routeIs('partner.notifications'), 'badge' => $partnerUnread, 'badge-attr' => 'data-hub-unread-badge'],
        ];
    @endphp

    <x-ui.sidenav-shell :home-href="route('partner.dashboard')">
        <x-ui.sidenav :items="$partnerNavItems" menu-id="partner-sidebar-menu" />
    </x-ui.sidenav-shell>

    <main class="main-content">
        <div class="position-relative">
            <nav class="nav navbar navbar-expand-xl header-hover-menu navbar-light iq-navbar">
                <div class="container-fluid navbar-inner">
                    <a href="{{ route('partner.dashboard') }}" class="navbar-brand">
                        @include('components.widget.logo')
                    </a>
                    <div class="sidebar-toggle" data-toggle="sidebar" data-active="true">
                        <i class="icon d-flex">
                            <svg class="icon-20" width="20" viewBox="0 0 24 24">
                                <path fill="currentColor"
                                    d="M4,11V13H16L10.5,18.5L11.92,19.92L19.84,12L11.92,4.08L10.5,5.5L16,11H4Z" />
                            </svg>
                        </i>
                    </div>
                    <div class="d-flex align-items-center justify-content-between product-offcanvas">
                        <div class="breadcrumb-title pe-3 d-none d-xl-block">
                            <small class="mb-0 text-capitalize">Creator Studio</small>
                        </div>
                    </div>
                    <ul class="navbar-nav ms-auto align-items-center navbar-list flex-row mb-0">
                        <li class="nav-item">
                            <a href="{{ route('frontend.ott') }}" class="nav-link" title="View site"
                                data-bs-toggle="tooltip" data-bs-placement="bottom" target="_blank" rel="noopener">
                                <i class="ph ph-house fs-4 align-middle"></i>
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="py-0 nav-link d-flex align-items-center ps-3" href="#" id="partner-profile-setting"
                                role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                @php
                                    $partnerAvatarSrc = auth()->user()?->profile_image
                                        ?: asset('dashboard/images/user/01.jpg');
                                @endphp
                                <img src="{{ $partnerAvatarSrc }}"
                                    alt="{{ auth()->user()?->full_name ?: (auth()->user()?->username ?? 'Profile') }}"
                                    class="img-fluid avatar avatar-50 avatar-rounded" loading="lazy"
                                    style="object-fit:cover;">
                                <div class="caption ms-3 d-none d-md-block">
                                    <h6 class="mb-0 caption-title">
                                        {{ auth()->user()?->full_name ?: (auth()->user()?->username ?? '') }}
                                    </h6>
                                    <p class="mb-0 caption-sub-title">Partner</p>
                                </div>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="partner-profile-setting">
                                <li>
                                    <a class="dropdown-item" href="{{ route('partner.profile') }}">
                                        My account
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                        onclick="event.preventDefault(); document.getElementById('partner-logout-form').submit();">
                                        Sign out
                                    </a>
                                </li>
                                <form id="partner-logout-form" action="{{ route('logout') }}" method="POST"
                                    class="d-none"> @csrf </form>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>
        </div>

        <div class="content-inner container-fluid pb-0" id="page_layout">
            @if (empty(auth()->user()->two_factor_confirmed_at))
                <div class="alert alert-warning d-flex align-items-center gap-2" style="font-size:14px;">
                    <i class="ph ph-shield-warning" style="font-size:20px;"></i>
                    <div>
                        Your account handles real money — protect it.
                        <a href="{{ route('partner.security') }}">Enable two-factor authentication</a>.
                    </div>
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @yield('content')
        </div>
    </main>

    @include('components.partials.scripts.script')
    @stack('scripts')
</body>

</html>
