<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark" dir="{{ language_direction() }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title>{{ isset($title) ? $title . ' - ' : '' }}{{ app_name() }}</title>
    <meta name="description" content="{{ meta_description() }}">

    @include('frontend::components.partials.head.plugins')
    {{-- Vite CSS --}}
    {{ module_vite('build-frontend', 'resources/assets/sass/app.scss') }}
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
        href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,300;0,400;0,500;0,700;0,900;1,300&display=swap"
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

    <link rel="stylesheet" href="{{ asset('frontend/css/jambo-header.css') }}">
</head>

<body class="{{ $bodyClass ?? '' }}">

    @if (Route::currentRouteName() === 'frontend.ott')
        @include('frontend::components.loader-component')
    @endif

    <main class="main-content">

        @include('frontend::components.partials.header-default')

        @if (isset($isBreadCrumb) && $isBreadCrumb)
            @include('frontend::components.breadcrumb-widget')
        @endif

        @yield('content')
    </main>

    @include('frontend::components.partials.footer-default')

    @include('frontend::components.partials.back-to-top')
    {{-- Vite JS --}}
    {{ module_vite('build-frontend', 'resources/assets/js/app.js') }}

    @include('frontend::components.partials.scripts.plugins')

    @include('frontend::components.partials.scripts.script')
</body>
