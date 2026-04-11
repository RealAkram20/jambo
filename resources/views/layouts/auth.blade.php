<!DOCTYPE html>
<html lang="en"  class="theme-fs-md">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Auth</title>

    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('frontend/images/favicon.ico') }}" />
    <!-- Scripts -->
    @vite(['resources/css/app.scss', 'public/dashboard/scss/streamit.scss', 'public/dashboard/scss/dashboard-custom.scss', 'public/dashboard/scss/customizer.scss', 'public/dashboard/scss/pro.scss', 'public/dashboard/scss/rtl.scss', 'public/dashboard/scss/custom.scss', 'resources/js/app.js'])
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

<body class="dark theme-color-default">
    @yield('content')

    @include('components.partials.scripts.script')
</body>

</html>
