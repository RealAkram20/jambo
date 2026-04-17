<!DOCTYPE html>
{{-- <html lang="{{ str_replace('_', '-', app()->getLocale()) }}"> --}}
<html lang="{{ app()->getLocale() }}" data-bs-theme="dark" dir="{{ session('locale_dir') ?? language_direction() }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ app_name() }}</title>

    <meta name="app_name" content="{{ app_name() }}">
    <meta name="description" content="{{ meta_description() }}">

    @include('components.partials.head.head')
    <!-- Scripts -->
    @vite(['resources/css/app.scss', 'public/dashboard/scss/streamit.scss',
    'public/dashboard/scss/dashboard-custom.scss', 'public/dashboard/scss/customizer.scss',
    'public/dashboard/scss/pro.scss', 'public/dashboard/scss/rtl.scss', 'public/dashboard/scss/custom.scss',
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
        (function () {
            try {
                var serverDir = "{{ session('locale_dir') ?? language_direction() }}" || 'ltr';
                
                window.IQ_SERVER_DIR = serverDir;
                
                if (document && document.documentElement) {
                    document.documentElement.setAttribute('dir', serverDir);
                }

                var storageKey = 'streamit';
                var storageUsed = null;
                var raw = sessionStorage.getItem(storageKey);
                if (raw === null) {
                    raw = localStorage.getItem(storageKey);
                    storageUsed = raw === null ? null : 'localStorage';
                } else {
                    storageUsed = 'sessionStorage';
                }

                var parsed = null;
                if (raw !== null) {
                    try {
                        parsed = JSON.parse(raw);
                    } catch (e) {
                        parsed = null;
                    }
                }

                var newValue = serverDir === 'rtl' ? 'rtl' : 'ltr';

                if (parsed && parsed.setting && parsed.setting.theme_scheme_direction) {
                    parsed.setting.theme_scheme_direction.value = newValue;
                   
                    if (storageUsed === 'localStorage') localStorage.setItem(storageKey, JSON.stringify(parsed));
                    else sessionStorage.setItem(storageKey, JSON.stringify(parsed));
                } else if (!parsed) {

                    var init = {
                        saveLocal: 'sessionStorage',
                        storeKey: storageKey,
                        setting: {
                            theme_scheme_direction: {
                                target: 'html',
                                choices: ['ltr', 'rtl'],
                                value: newValue
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
                    };
                    sessionStorage.setItem(storageKey, JSON.stringify(init));
                }
            } catch (e) {
                // Fail silently if storage is unavailable
                console && console.debug && console.debug('locale-dir-sync error', e);
            }
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
    <!-- SwiperSlider Script -->
    <script src="{{ asset('dashboard/vendor/swiperSlider/swiper-bundle.min.js') }}"></script>
    <script src="{{ asset('dashboard/js/plugins/swiper-slider.js') }}" defer></script>
</body>

</html>