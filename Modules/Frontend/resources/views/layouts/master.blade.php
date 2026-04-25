<!DOCTYPE html>
<html lang="en" data-bs-theme="dark" dir="ltr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title>{{ isset($title) ? $title . ' - ' : '' }}{{ app_name() }}</title>
    <meta name="description" content="{{ meta_description() }}">

    <!-- PWA -->
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/jambo-192.png') }}">
    <meta name="theme-color" content="#1A98FF">

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

        {{-- Site-wide flash banner. `info` is used for soft redirects
             (e.g. trying to watch an upcoming movie bounces to detail
             with a "coming soon" note); `error` for hard failures.
             Sticky-dismissible toast-ish alert at the top of content
             so any redirect-with-flash surfaces consistently without
             every page wiring its own alert block. --}}
        @if (session('info'))
            <div class="container mt-3">
                <div class="alert alert-info alert-dismissible fade show text-center mb-0" role="alert">
                    {{ session('info') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        @endif
        @if (session('error'))
            <div class="container mt-3">
                <div class="alert alert-danger alert-dismissible fade show text-center mb-0" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    @include('frontend::components.partials.footer-default')

    @include('frontend::components.partials.back-to-top')
    {{-- Vite JS --}}
    {{ module_vite('build-frontend', 'resources/assets/js/app.js') }}

    @include('frontend::components.partials.scripts.plugins')

    @include('frontend::components.partials.scripts.script')

    {{-- Global watchlist toggle. Any .jambo-watchlist-toggle-btn with
         data-watchable-type + data-watchable-id posts to the toggle
         endpoint. Unauthenticated users get redirected to login. --}}
    <script>
    (function () {
        var csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        var isAuthed = {{ auth()->check() ? 'true' : 'false' }};
        var loginUrl = {{ Js::from(route('login')) }};

        // Remove-by-watchlist-id handler. Works on any page that renders
        // the .jambo-watchlist-remove-btn (watchlist detail, profile).
        // After success we fade out the row(s) tagged with the matching
        // data-watchlist-row / data-watchlist-item — otherwise the page
        // needs a full reload to reflect the removal.
        document.addEventListener('click', async function (e) {
            var rm = e.target.closest('.jambo-watchlist-remove-btn');
            if (!rm) return;
            e.preventDefault();
            e.stopPropagation();

            var id = rm.dataset.watchlistId;
            if (!id || rm.dataset.busy === '1') return;
            rm.dataset.busy = '1';

            try {
                var res = await fetch({{ Js::from(url('/api/v1/watchlist')) }} + '/' + id, {
                    method: 'DELETE',
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);

                document.querySelectorAll(
                    '[data-watchlist-row="' + id + '"], [data-watchlist-item="' + id + '"]'
                ).forEach(function (row) {
                    row.style.transition = 'opacity .2s, transform .2s';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(6px)';
                    setTimeout(function () { row.remove(); }, 200);
                });
            } catch (err) {
                console.warn('[watchlist-remove]', err);
                rm.dataset.busy = '';
            }
        });

        document.addEventListener('click', async function (e) {
            var btn = e.target.closest('.jambo-watchlist-toggle-btn');
            if (!btn) return;
            e.preventDefault();

            if (!isAuthed) {
                window.location.href = loginUrl;
                return;
            }

            var type = btn.dataset.watchableType;
            var id   = btn.dataset.watchableId;
            if (!type || !id) return;
            if (btn.dataset.busy === '1') return;
            btn.dataset.busy = '1';

            try {
                var res = await fetch({{ Js::from(url('/api/v1/watchlist')) }} + '/' + type + '/' + id, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                var data = await res.json();

                // Every button on the page that represents the same
                // (type, id) should flip together — a user might see the
                // same movie in multiple rails, and it would be
                // confusing if only the clicked card updated.
                var siblings = document.querySelectorAll(
                    '.jambo-watchlist-toggle-btn[data-watchable-type="' + type
                    + '"][data-watchable-id="' + id + '"]'
                );
                var addTip = {{ Js::from(__('sectionTitle.add_to_watchlist_tooltip')) }};
                var removeTip = {{ Js::from(__('streamPlaylist.remove_from_watchlist') ?? 'Remove from watchlist') }};
                siblings.forEach(function (el) {
                    var i = el.querySelector('i');
                    if (i) {
                        i.classList.toggle('ph-plus', !data.inList);
                        i.classList.toggle('ph-check', data.inList);
                    }
                    el.classList.toggle('is-in-watchlist', data.inList);
                    var newTitle = data.inList ? removeTip : addTip;
                    el.setAttribute('data-bs-title', newTitle);
                    el.setAttribute('aria-label', newTitle);
                    // Refresh the already-bound Bootstrap tooltip if any.
                    if (window.bootstrap && bootstrap.Tooltip) {
                        var tip = bootstrap.Tooltip.getInstance(el);
                        if (tip) { tip.setContent({ '.tooltip-inner': newTitle }); }
                    }
                    // Swap the visible label on buttons that have one
                    // (hero "Add to Watchlist" / "In Watchlist" button).
                    var label = el.querySelector('.jambo-watchlist-label');
                    if (label) {
                        var addLabel = el.getAttribute('data-watchlist-label-add');
                        var removeLabel = el.getAttribute('data-watchlist-label-remove');
                        if (addLabel && removeLabel) {
                            label.textContent = data.inList ? removeLabel : addLabel;
                        }
                    }
                });
            } catch (err) {
                console.warn('[watchlist-toggle]', err);
            } finally {
                btn.dataset.busy = '';
            }
        });
    })();
    </script>

    {{-- Inspect-deterrent: disables right-click + devtools shortcuts
         for non-admin users on the public site. Deterrent only — see
         the file header for the full honest-caveats write-up. Admins
         keep full browser tooling for debugging. --}}
    @unless (auth()->check() && auth()->user()->hasRole('admin'))
        <script src="{{ asset('frontend/js/jambo-inspect-deterrent.js') }}" defer></script>
    @endunless

    @include('components.partials.pwa-bootstrap')
    @include('components.partials.push-soft-prompt')
    @include('components.partials.install-prompt')

    {{-- Password-reveal toggle (mirrors the admin layout). Any button
         tagged [data-password-toggle] flips the sibling input between
         "password" and "text" types. --}}
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
