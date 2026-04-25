<!DOCTYPE html>
<html lang="en" data-bs-theme="dark" dir="ltr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ app_name() }}</title>

    <meta name="app_name" content="{{ app_name() }}">
    <meta name="description" content="{{ meta_description() }}">

    <!-- PWA -->
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/jambo-192.png') }}">
    <meta name="theme-color" content="#1A98FF">

    @include('components.partials.head.head')
    <!-- Scripts -->
    @vite(['resources/css/app.scss', 'public/dashboard/scss/streamit.scss',
    'public/dashboard/scss/dashboard-custom.scss', 'public/dashboard/scss/customizer.scss',
    'public/dashboard/scss/pro.scss', 'public/dashboard/scss/custom.scss',
    'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('dashboard/vendor/swiperSlider/swiper-bundle.min.css') }}" />
    <style>
        :root {
            --bs-primary: #1A98FF;
            --bs-primary-rgb: 26, 152, 255;
            --bs-link-color: #1A98FF;
            --bs-link-color-rgb: 26, 152, 255;
            --bs-link-hover-color: #147acc;
        }
    </style>
    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;1,100;1,300&display=swap"
        rel="stylesheet">

    <script>
        // Seed the Streamit template's persisted theme storage so its
        // customizer JS doesn't flash/reset colours on first load.
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

<body class=" {{ isset($bodyClass) ? $bodyClass : '' }}">
    @include('components.partials.sidebar')
    <main class="main-content">
        <div class="position-relative {{ isset($isBanner) && $isBanner ? 'iq-banner' : '' }}">
            @include('components.partials.header')
            @if (isset($isBanner) && $isBanner)
            @include('components.partials.sub-header')
            @endif
        </div>
        <div class="content-inner container-fluid pb-0" id="page_layout">
            @yield('content')
        </div>
        @include('components.partials.footer')
    </main>
    <!-- loader END -->

    @include('components.partials.customizer')
    @include('components.setting-offcanvas')
    @auth
        @include('components.partials.media-picker')
    @endauth
    @include('components.partials.scripts.plugin')
    @include('components.partials.scripts.script')
    @include('components.partials.pwa-bootstrap')
    <!-- SwiperSlider Script -->
    <script src="{{ asset('dashboard/vendor/swiperSlider/swiper-bundle.min.js') }}"></script>
    <script src="{{ asset('dashboard/js/plugins/swiper-slider.js') }}" defer></script>

    {{-- Global password-reveal toggle. Any `[data-password-toggle]`
         button flips the visibility of the input that shares its
         parent (same pattern Bootstrap input-group produces). The
         <x-password-input> component emits this attribute, but the
         handler also works on hand-rolled markup. --}}
    <script>
    (function () {
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-password-toggle]');
            if (!btn) return;
            var input = btn.parentElement && btn.parentElement.querySelector('input');
            if (!input) return;
            var isText = input.type === 'text';
            input.type = isText ? 'password' : 'text';
            var icon = btn.querySelector('i');
            if (icon) icon.className = isText ? 'ph ph-eye-slash' : 'ph ph-eye';
            btn.setAttribute('aria-label', isText ? 'Show password' : 'Hide password');
        });
    })();
    </script>
</body>

</html>