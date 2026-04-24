@extends('layouts.jambo-auth', ['title' => 'Access denied'])

@section('header-cta')
    Need access?
    <a href="{{ route('frontend.contact_us') }}"
       class="text-primary fw-semibold text-decoration-none ms-1">
        Contact support
    </a>
@endsection

@section('styles')
    {{-- One display face, lazy-loaded, used only for the hero "403".
         Anton is a condensed marquee-weight sans — gives the number a
         cinema-billboard silhouette without dragging a second font into
         the rest of the page. Body copy stays on Roboto from the parent
         layout. --}}
    <link href="https://fonts.googleapis.com/css2?family=Anton&display=swap"
          rel="stylesheet">

    <style>
        /* Override jambo-auth-main centering — we want a bit more
           breathing room above/below the hero on tall screens. */
        .jambo-auth-main {
            min-height: calc(100vh - 170px);
            padding-block: 2.5rem;
        }

        .jambo-403 {
            position: relative;
            width: 100%;
            /* Match the card's max-width so the hero and the message
               below share the same left/right edges — reads as a single
               composed unit instead of two stacked elements of different
               widths. */
            max-width: 520px;
            text-align: center;
            padding: 0 1rem;
        }

        /* Eyebrow — small tracked label above the number. */
        .jambo-403__eyebrow {
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
            animation: jambo-403-fade-in 0.6s ease-out 0.1s forwards;
        }

        .jambo-403__eyebrow i { font-size: 0.95rem; line-height: 1; }

        /* The hero "403". Anton at display scale, white→primary gradient
           fill, soft blue ground-shadow. No grain, no flicker, no
           scanline — a single clean type moment reads more professional
           than a stack of visual effects.
           `display: block` is required so the number sits on its own
           line; leaving it inline-block makes the eyebrow pill share
           the number's baseline and visually collapse beside it. */
        .jambo-403__number {
            display: block;
            font-family: 'Anton', 'Roboto', sans-serif;
            font-size: clamp(6.5rem, 16vw, 10rem);
            line-height: 0.95;
            letter-spacing: 0.015em;
            color: #fff;
            background: linear-gradient(180deg, #ffffff 0%, #8cc4f5 60%, #1A98FF 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 10px 28px rgba(26, 152, 255, 0.16));
            opacity: 0;
            transform: translateY(8px);
            animation: jambo-403-projector-in 0.7s cubic-bezier(0.2, 0.7, 0.2, 1) 0.2s forwards;
        }

        /* Headline + body copy card — small, quiet, under the hero. */
        .jambo-403__message {
            max-width: 520px;
            margin: 1.25rem auto 0;
            padding: 1.5rem 1.5rem 1.75rem;
            background: rgba(17, 19, 24, 0.78);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 12px;
            box-shadow: 0 18px 50px rgba(0, 0, 0, 0.35);
            backdrop-filter: blur(8px);
            opacity: 0;
            transform: translateY(14px);
            animation: jambo-403-rise 0.6s ease-out 0.6s forwards;
        }

        .jambo-403__title {
            font-size: 1.4rem;
            font-weight: 600;
            margin: 0 0 0.35rem;
            color: #fff;
            letter-spacing: -0.01em;
        }

        .jambo-403__copy {
            color: #9aa0aa;
            font-size: 0.95rem;
            line-height: 1.55;
            margin: 0 0 1.25rem;
        }

        @if (isset($exception))
            .jambo-403__detail {
                display: block;
                text-align: left;
                padding: 0.6rem 0.8rem;
                margin: 0 0 1.25rem;
                background: rgba(255, 255, 255, 0.025);
                border: 1px solid rgba(255, 255, 255, 0.06);
                border-radius: 8px;
                color: #6f7381;
                font-family: 'Roboto Mono', ui-monospace, SFMono-Regular, Menlo, monospace;
                font-size: 0.78rem;
                line-height: 1.5;
                word-break: break-word;
            }

            .jambo-403__detail-label {
                display: block;
                color: #4d515c;
                font-size: 0.68rem;
                letter-spacing: 0.18em;
                text-transform: uppercase;
                margin-bottom: 0.2rem;
            }
        @endif

        /* Actions row — primary filled blue, secondary ghost. */
        .jambo-403__actions {
            display: flex;
            gap: 0.65rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .jambo-403__btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            height: 44px;
            padding: 0 1.15rem;
            border-radius: 8px;
            font-size: 0.92rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.15s, border-color 0.15s, color 0.15s, transform 0.1s;
            border: 1px solid transparent;
        }

        .jambo-403__btn--primary {
            background: var(--bs-primary);
            color: #fff;
        }

        .jambo-403__btn--primary:hover {
            background: var(--bs-link-hover-color);
            color: #fff;
        }

        .jambo-403__btn--primary:active { transform: scale(0.98); }

        .jambo-403__btn--ghost {
            background: rgba(255, 255, 255, 0.04);
            border-color: rgba(255, 255, 255, 0.12);
            color: #cfd3dc;
        }

        .jambo-403__btn--ghost:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.2);
            color: #fff;
        }

        /* Who's signed in — a tiny muted footprint so admins know the
           session isn't stale. Rendered as a plain centered sentence
           that wraps naturally at any width (no inline-flex / bullet
           separator / form-glued-to-text shenanigans). Only shown
           when authenticated. */
        .jambo-403__signed-in {
            max-width: 520px;
            margin: 1.1rem auto 0;
            color: #6f7381;
            font-size: 0.82rem;
            line-height: 1.55;
            text-align: center;
            opacity: 0;
            animation: jambo-403-fade-in 0.5s ease-out 0.9s forwards;
        }

        .jambo-403__signed-in strong {
            color: #9aa0aa;
            font-weight: 500;
            word-break: break-word;
        }

        /* ---------- keyframes ---------- */

        @keyframes jambo-403-fade-in {
            to { opacity: 1; }
        }

        @keyframes jambo-403-rise {
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes jambo-403-projector-in {
            to { opacity: 1; transform: translateY(0); }
        }

        @media (prefers-reduced-motion: reduce) {
            .jambo-403__eyebrow,
            .jambo-403__number,
            .jambo-403__message,
            .jambo-403__signed-in {
                animation: none !important;
                opacity: 1 !important;
                transform: none !important;
            }
        }

        /* Logout form ships invisibly so the "Sign out" link can POST
           inline with the surrounding sentence. */
        .jambo-403__logout-form {
            display: inline;
        }

        .jambo-403__logout-form button {
            background: none;
            border: 0;
            padding: 0;
            color: #9aa0aa;
            font: inherit;
            cursor: pointer;
            text-decoration: underline;
            text-decoration-color: rgba(255, 255, 255, 0.18);
            text-underline-offset: 3px;
            transition: color 0.15s, text-decoration-color 0.15s;
        }

        .jambo-403__logout-form button:hover {
            color: #fff;
            text-decoration-color: rgba(255, 255, 255, 0.45);
        }
    </style>
@endsection

@section('content')
    <div class="jambo-403">
        <span class="jambo-403__eyebrow">
            <i class="ph ph-shield-warning"></i>
            Restricted access
        </span>

        <div class="jambo-403__number" aria-hidden="true">403</div>
        <span class="visually-hidden">Error 403 — Access denied</span>

        <div class="jambo-403__message">
            <h1 class="jambo-403__title">You're off the guest list.</h1>
            <p class="jambo-403__copy">
                Your account is signed in, but this area needs a role your
                account doesn't have yet. If this looks wrong, ask an admin
                to update your access.
            </p>

            @isset($exception)
                @php
                    $detail = trim((string) $exception->getMessage());
                @endphp
                @if ($detail !== '' && $detail !== 'Forbidden')
                    <code class="jambo-403__detail">
                        <span class="jambo-403__detail-label">Server said</span>
                        {{ $detail }}
                    </code>
                @endif
            @endisset

            <div class="jambo-403__actions">
                <a href="{{ url('/') }}" class="jambo-403__btn jambo-403__btn--primary">
                    <i class="ph ph-house"></i>
                    Take me home
                </a>
                <a href="javascript:history.length > 1 ? history.back() : location.assign('{{ url('/') }}')"
                   class="jambo-403__btn jambo-403__btn--ghost">
                    <i class="ph ph-arrow-u-up-left"></i>
                    Go back
                </a>
            </div>
        </div>

        @auth
            <div class="jambo-403__signed-in">
                Signed in as <strong>{{ auth()->user()->email }}</strong>.
                <form method="POST" action="{{ route('logout') }}" class="jambo-403__logout-form">
                    @csrf
                    <button type="submit">Sign out</button>
                </form>
            </div>
        @endauth
    </div>
@endsection
