@extends('layouts.jambo-auth', ['title' => 'Maintenance'])

@section('styles')
    <link href="https://fonts.googleapis.com/css2?family=Anton&display=swap" rel="stylesheet">

    <style>
        .jambo-auth-main {
            min-height: calc(100vh - 170px);
            padding-block: 2.5rem;
        }

        .jambo-maintenance {
            width: 100%;
            max-width: 560px;
            text-align: center;
            padding: 0 1rem;
        }

        /* Eyebrow above the headline — small tracked label with the
           wrench glyph, anchors the page semantically. */
        .jambo-maintenance__eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            padding: 0.35rem 0.9rem;
            margin-bottom: 1.25rem;
            background: rgba(26, 152, 255, 0.08);
            border: 1px solid rgba(26, 152, 255, 0.22);
            border-radius: 999px;
            color: #7cbcf5;
            font-size: 0.72rem;
            font-weight: 500;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            opacity: 0;
            animation: jambo-maint-fade 0.6s ease-out 0.1s forwards;
        }

        .jambo-maintenance__eyebrow i { font-size: 0.95rem; line-height: 1; }

        /* Hero headline — large display type with the same gradient
           treatment as the 403 page so the family is recognisable. */
        .jambo-maintenance__title {
            display: block;
            font-family: 'Anton', 'Roboto', sans-serif;
            font-size: clamp(3rem, 7.5vw, 5rem);
            line-height: 1;
            letter-spacing: 0.01em;
            color: #fff;
            background: linear-gradient(180deg, #ffffff 0%, #8cc4f5 60%, #1A98FF 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 10px 28px rgba(26, 152, 255, 0.16));
            margin: 0 0 0.25rem;
            opacity: 0;
            transform: translateY(8px);
            animation: jambo-maint-rise 0.7s cubic-bezier(0.2, 0.7, 0.2, 1) 0.2s forwards;
        }

        .jambo-maintenance__message {
            max-width: 520px;
            margin: 1rem auto 0;
            padding: 1.5rem 1.5rem 1.75rem;
            background: rgba(17, 19, 24, 0.78);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 12px;
            box-shadow: 0 18px 50px rgba(0, 0, 0, 0.35);
            backdrop-filter: blur(8px);
            opacity: 0;
            transform: translateY(14px);
            animation: jambo-maint-rise 0.6s ease-out 0.55s forwards;
        }

        .jambo-maintenance__copy {
            color: #cfd3dc;
            font-size: 0.98rem;
            line-height: 1.6;
            margin: 0 0 1rem;
            white-space: pre-line; /* honour line breaks the admin types */
        }

        .jambo-maintenance__until {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.45rem 0.9rem;
            margin-top: 0.25rem;
            background: rgba(26, 152, 255, 0.08);
            border: 1px solid rgba(26, 152, 255, 0.22);
            border-radius: 8px;
            color: #cfd3dc;
            font-size: 0.88rem;
        }

        .jambo-maintenance__until strong { color: #fff; font-weight: 500; }

        .jambo-maintenance__until i { color: #7cbcf5; font-size: 1rem; }

        @keyframes jambo-maint-fade { to { opacity: 1; } }
        @keyframes jambo-maint-rise { to { opacity: 1; transform: translateY(0); } }

        /* ------------------------------------------------------------ */
        /* Floating admin gear (bottom-right)                           */
        /* ------------------------------------------------------------ */
        /* Subtle by default — anyone who knows what it is finds it,
           casual visitors don't notice. Hover lights it up. Position
           is fixed so it stays on screen regardless of message length.
           z-index above the page footer (which is positioned in the
           normal flow). */
        .jambo-admin-gear {
            position: fixed;
            right: 1.25rem;
            bottom: 1.25rem;
            width: 44px;
            height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(17, 19, 24, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            color: #6f7381;
            font-size: 1.2rem;
            text-decoration: none;
            transition: background 0.18s, color 0.18s, border-color 0.18s, transform 0.18s, box-shadow 0.18s;
            z-index: 50;
            backdrop-filter: blur(6px);
            opacity: 0.7;
        }

        .jambo-admin-gear:hover,
        .jambo-admin-gear:focus-visible {
            background: var(--bs-primary);
            border-color: var(--bs-primary);
            color: #fff;
            opacity: 1;
            transform: rotate(35deg);
            box-shadow: 0 0 0 6px rgba(26, 152, 255, 0.18);
            outline: none;
        }

        .jambo-admin-gear i {
            transition: transform 0.6s cubic-bezier(0.2, 0.7, 0.2, 1);
        }

        .jambo-admin-gear:hover i,
        .jambo-admin-gear:focus-visible i {
            transform: rotate(90deg);
        }

        @media (max-width: 520px) {
            .jambo-admin-gear {
                right: 1rem;
                bottom: 1rem;
                width: 40px;
                height: 40px;
                font-size: 1.1rem;
            }
        }
    </style>
@endsection

@section('content')
    <div class="jambo-maintenance">
        <span class="jambo-maintenance__eyebrow">
            <i class="ph ph-wrench"></i> Scheduled maintenance
        </span>

        <h1 class="jambo-maintenance__title">Be right back</h1>

        <div class="jambo-maintenance__message">
            <p class="jambo-maintenance__copy">{{ $message }}</p>

            @if (!empty($until))
                @php
                    $untilTs = strtotime($until);
                    $untilLabel = $untilTs ? date('M j, Y \a\t g:i a', $untilTs) : null;
                @endphp
                @if ($untilLabel)
                    <span class="jambo-maintenance__until">
                        <i class="ph ph-clock"></i>
                        Estimated back by <strong id="jambo-maint-back-by">{{ $untilLabel }}</strong>
                    </span>
                @endif
            @endif
        </div>
    </div>

    {{-- Floating admin gear. Visible to all visitors during maintenance
         since the middleware never reaches this view for admins (they
         bypass) — so anyone seeing this is by definition NOT signed in
         as admin and may need the login link. Plain anchor; no JS,
         keyboard-accessible (focus-visible state matches hover). --}}
    <a href="{{ route('login') }}"
       class="jambo-admin-gear"
       title="Admin sign in"
       aria-label="Admin sign in">
        <i class="ph-fill ph-gear-six"></i>
    </a>
@endsection
