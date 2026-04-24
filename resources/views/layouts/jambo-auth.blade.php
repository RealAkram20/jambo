<!DOCTYPE html>
<html lang="en" data-bs-theme="dark" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ isset($title) ? $title . ' — ' : '' }}{{ config('app.name', 'Jambo') }}</title>

    <link rel="shortcut icon" href="{{ asset('frontend/images/favicon.ico') }}" />

    {{-- Streamit frontend bundle so Bootstrap + theme tokens match the
         rest of the site. --}}
    {{ module_vite('build-frontend', 'resources/assets/sass/app.scss') }}

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,300;0,400;0,500;0,700;1,300&display=swap"
          rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('frontend/vendor/phosphor-icons/Fonts/regular/style.css') }}" />
    <link rel="stylesheet" href="{{ asset('frontend/vendor/phosphor-icons/Fonts/fill/style.css') }}" />

    <style>
        :root {
            --bs-primary: #1A98FF;
            --bs-primary-rgb: 26, 152, 255;
            --bs-link-color: #1A98FF;
            --bs-link-hover-color: #147acc;
        }

        /* ------------------------------------------------------------ */
        /* Streaming-site auth page shell                              */
        /* ------------------------------------------------------------ */
        body.jambo-auth {
            min-height: 100vh;
            margin: 0;
            color: #fff;
            font-family: 'Roboto', system-ui, sans-serif;
            background:
                radial-gradient(ellipse 60% 40% at 50% 0%, rgba(26, 152, 255, 0.18), transparent 70%),
                linear-gradient(180deg, #0b0c10 0%, #050608 60%);
            background-attachment: fixed;
        }

        .jambo-auth-header {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem 1.5rem;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem 2rem;
            position: relative;
            z-index: 1;
        }

        .jambo-auth-header__brand img {
            display: block;
            height: 36px;
            width: auto;
        }

        /* CTA on the right of the header. `white-space: nowrap` on the
           anchor keeps "Sign up" / "Contact support" from splitting
           across lines mid-phrase; the whole CTA block will drop to its
           own line below the logo if the viewport gets too narrow
           (flex-wrap on the parent handles that). */
        .jambo-auth-header__cta {
            color: #cfd3dc;
            font-size: 0.9rem;
            line-height: 1.35;
        }

        .jambo-auth-header__cta a { white-space: nowrap; }

        .jambo-auth-main {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 200px);
            padding: 2rem 1rem;
        }

        /* The card — translucent, subtle border, soft inner glow. */
        .jambo-auth-card {
            width: 100%;
            max-width: 440px;
            padding: 2.25rem 2rem;
            background: rgba(17, 19, 24, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.45);
            backdrop-filter: blur(8px);
        }

        .jambo-auth-card--wide {
            max-width: 560px;
        }

        .jambo-auth-card h1 {
            font-size: 1.6rem;
            font-weight: 600;
            margin: 0 0 0.35rem;
        }

        .jambo-auth-card__subtitle {
            color: #9aa0aa;
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
        }

        /* Field primitives. Leaner than Bootstrap defaults so the card
           doesn't feel like a form in a dashboard. */
        .jambo-field {
            margin-bottom: 1rem;
        }

        .jambo-field label {
            display: block;
            font-size: 0.85rem;
            color: #cfd3dc;
            margin-bottom: 0.35rem;
        }

        .jambo-field input {
            width: 100%;
            height: 44px;
            padding: 0.5rem 0.85rem;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: #fff;
            font-size: 0.95rem;
            transition: border-color 0.15s, background 0.15s;
        }

        .jambo-field input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.07);
            border-color: var(--bs-primary);
            box-shadow: 0 0 0 3px rgba(26, 152, 255, 0.18);
        }

        .jambo-field--with-toggle {
            position: relative;
        }

        .jambo-field__toggle {
            position: absolute;
            /* Bottom-anchored vertical centering — the input is always
               the last block in the field and 44px tall, so bottom:13px
               keeps the ~18px icon perfectly centered on the input no
               matter how tall the label above ends up rendering (label
               font-size, padding, or extra hint text can grow without
               knocking the icon off-centre). The previous `top: 30px`
               was a magic number tuned for one specific label height
               and visibly drifted on any variant. */
            top: auto;
            bottom: 13px;
            right: 0.85rem;
            color: #9aa0aa;
            cursor: pointer;
            background: none;
            border: 0;
            padding: 0;
            font-size: 1.1rem;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .jambo-field__toggle:hover { color: #fff; }

        /* Primary submit button — blue, full-width on the card. */
        .jambo-auth-btn {
            width: 100%;
            height: 46px;
            border: 0;
            border-radius: 8px;
            background: var(--bs-primary);
            color: #fff;
            font-weight: 600;
            font-size: 0.95rem;
            transition: background 0.15s, transform 0.1s;
            cursor: pointer;
        }

        .jambo-auth-btn:hover { background: var(--bs-link-hover-color); }
        .jambo-auth-btn:active { transform: scale(0.98); }
        .jambo-auth-btn:disabled { opacity: 0.6; cursor: not-allowed; }

        /* Secondary link row beneath the submit. */
        .jambo-auth-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 0.75rem;
            font-size: 0.9rem;
        }

        .jambo-auth-meta a { color: #cfd3dc; text-decoration: none; }
        .jambo-auth-meta a:hover { color: var(--bs-primary); }

        /* Divider between password and social login. */
        .jambo-auth-divider {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 1.5rem 0;
            color: #6f7381;
            font-size: 0.85rem;
        }

        .jambo-auth-divider::before,
        .jambo-auth-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255, 255, 255, 0.08);
        }

        /* Social / OAuth button. */
        .jambo-social-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            width: 100%;
            height: 44px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: #fff;
            font-size: 0.95rem;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.15s, border-color 0.15s;
        }

        .jambo-social-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
            color: #fff;
        }

        .jambo-social-btn svg { width: 18px; height: 18px; }

        .jambo-auth-footer {
            text-align: center;
            margin-top: 1.5rem;
            color: #9aa0aa;
            font-size: 0.9rem;
        }

        .jambo-auth-footer a {
            color: var(--bs-primary);
            text-decoration: none;
        }

        .jambo-auth-footer a:hover { color: var(--bs-link-hover-color); }

        /* Errors + flash messages. */
        .jambo-auth-alert {
            padding: 0.65rem 0.85rem;
            margin-bottom: 1rem;
            background: rgba(220, 53, 69, 0.12);
            border: 1px solid rgba(220, 53, 69, 0.3);
            border-radius: 8px;
            color: #f8a7ad;
            font-size: 0.88rem;
        }

        .jambo-auth-alert--success {
            background: rgba(40, 167, 69, 0.1);
            border-color: rgba(40, 167, 69, 0.3);
            color: #7ddb93;
        }

        .jambo-auth-alert ul { margin: 0; padding-left: 1.1rem; }

        /* Small footer under the card. Real flex row with explicit
           separator spans — avoids the &nbsp;·&nbsp; pattern which
           wraps unpredictably on narrow widths (the non-breaking
           spaces glue the dot to the neighboring link, so a wrap
           only ever happens between whole groups, not between a
           link and its following bullet). */
        .jambo-auth-page-footer {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.35rem 0.85rem;
            padding: 1.5rem 1rem;
            color: #6f7381;
            font-size: 0.8rem;
        }

        .jambo-auth-page-footer a {
            color: #9aa0aa;
            text-decoration: none;
        }

        .jambo-auth-page-footer a:hover { color: #fff; }

        .jambo-auth-page-footer__sep {
            color: #4d515c;
            user-select: none;
        }

        @media (max-width: 520px) {
            .jambo-auth-header {
                padding: 1rem 1.25rem;
                gap: 0.4rem 1rem;
            }
            .jambo-auth-header__cta { font-size: 0.85rem; }
            .jambo-auth-card { padding: 1.75rem 1.25rem; }
        }
    </style>

    @yield('styles')
</head>
<body class="jambo-auth">
    <header class="jambo-auth-header">
        <a href="{{ route('frontend.ott') }}" class="jambo-auth-header__brand">
            <img src="{{ branding_asset('logo', 'frontend/images/logo.webp') }}"
                 alt="{{ config('app.name') }}" loading="lazy">
        </a>
        <div class="jambo-auth-header__cta">
            @yield('header-cta')
        </div>
    </header>

    <main class="jambo-auth-main">
        @yield('content')
    </main>

    <footer class="jambo-auth-page-footer">
        <a href="{{ route('frontend.terms-and-policy') }}">Terms</a>
        <span class="jambo-auth-page-footer__sep" aria-hidden="true">·</span>
        <a href="{{ route('frontend.privacy-policy') }}">Privacy</a>
        <span class="jambo-auth-page-footer__sep" aria-hidden="true">·</span>
        <a href="{{ route('frontend.contact_us') }}">Contact</a>
    </footer>

    {{-- Shared password-toggle behavior — each .jambo-field__toggle
         flips the visibility of the input sitting inside its parent. --}}
    <script>
        document.querySelectorAll('.jambo-field__toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var input = btn.parentElement.querySelector('input');
                if (!input) return;
                var isText = input.type === 'text';
                input.type = isText ? 'password' : 'text';
                btn.querySelector('i').className = isText ? 'ph ph-eye-slash' : 'ph ph-eye';
            });
        });
    </script>

    @yield('scripts')
</body>
</html>
