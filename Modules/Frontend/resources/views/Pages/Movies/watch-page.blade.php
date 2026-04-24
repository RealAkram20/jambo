@extends('frontend::layouts.master', ['isSwiperSlider' => true, 'bodyClass' => 'custom-header-relative', 'isSelect2' => true])

@php
    $poster = $movie->backdrop_url ?: $movie->poster_url;
@endphp

@section('content')

@if ($source)
    {{-- Minimal-skin player from @videojs/html. The CSS file is at
         public/frontend/css/player.css; the module script below registers
         the custom elements (<video-player>, <media-*>). --}}
    <link rel="stylesheet" href="{{ asset('frontend/css/player.css') }}">
    <script type="module" src="https://cdn.jsdelivr.net/npm/@videojs/html/cdn/video-minimal-ui.js"></script>
    <script src="{{ asset('frontend/js/jambo-settings-menu.js') }}" defer></script>
    <script src="{{ asset('frontend/js/jambo-player-gestures.js') }}" defer></script>

    <div class="jambo-watch-hero" id="jambo-player-hero">
        {{-- Sentinel: the IntersectionObserver watches this element, which
             never moves. The frame can be portalled to <body> on mini-mode
             without causing the observer to bounce. --}}
        <div class="jambo-player-sentinel" id="jambo-player-sentinel"></div>
        <div class="jambo-player-frame" id="jambo-player-frame" style="position:absolute; inset:0;">
            <button type="button" class="jambo-mini-close" id="jambo-mini-close" aria-label="Close mini-player" title="Close">
                <i class="ph ph-x"></i>
            </button>
            @include('frontend::components.partials.jambo-minimal-player', [
                'playerSrc' => $source['url'],
                'playerSrcLow' => ($movie->streamSourceLow()['url'] ?? null),
                'playerPoster' => $poster,
                'playerId' => 'jambo-watch-player',
                'resumePosition' => $resumePosition ?? 0,
            ])
        </div>
    </div>
@else
    <div class="jambo-watch-hero">
        <div class="jambo-player-frame d-flex align-items-center justify-content-center text-light">
            <div class="text-center p-5">
                <h3 class="mb-3">This title isn't streamable yet.</h3>
                <p class="text-muted mb-4">No Video URL has been set for <strong>{{ $movie->title }}</strong>.</p>
                <a href="{{ route('frontend.movie_detail', $movie->slug) }}" class="btn btn-outline-light">← Back to details</a>
            </div>
        </div>
    </div>
@endif

<div class="details-part">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="trending-info pt-0 pb-0">
                    <div class="row justify-content-between">
                        <div class="col-xl-12 col-12 mb-auto">
                            @include('frontend::components.cards.movie-description', [
                                'moveName' => $movie->title,
                                'movieType' => $movie->tier_required ? strtoupper($movie->tier_required) : 'PG',
                                'movieDuration' => $movie->runtime_minutes ? floor($movie->runtime_minutes / 60) . 'hr : ' . ($movie->runtime_minutes % 60) . 'mins' : '—',
                                'movieReleased' => $movie->year ?: ($movie->published_at?->format('Y') ?? ''),
                                'movieViews' => number_format($movie->views_count) . ' ' . __('streamTag.views'),
                                'imdbRating' => $movie->rating ?: '—',
                                'movieLanguage' => 'english',
                                'movieDescription' => $movie->synopsis,
                                'movieGenres' => $movie->genres->pluck('name')->all(),
                                'isNotstartWatching' => true,
                            ])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="overflow-hidden">
        @if ($recommended->count())
            <div class="show-episode section-padding">
                <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
                    <h5 class="main-title text-capitalize mb-0 fw-medium">{{ __('sectionTitle.recommended_movie') }}</h5>
                </div>
                <div class="card-style-slider">
                    <div class="position-relative swiper swiper-card mt-4 mb-5" data-slide="7"
                        data-laptop="7" data-tab="4" data-mobile="3" data-mobile-sm="3"
                        data-autoplay="false" data-loop="false" data-navigation="true" data-pagination="true">
                        <ul class="p-0 swiper-wrapper m-0 list-inline">
                            @foreach ($recommended as $rec)
                                <li class="swiper-slide">
                                    @include('frontend::components.cards.card-style', [
                                        'cardImage' => $rec->poster_url ?: 'media/rabbit-portrait.webp',
                                        'cardTitle' => $rec->title,
                                        'movietime' => $rec->runtime_minutes ? floor($rec->runtime_minutes / 60) . 'hr : ' . ($rec->runtime_minutes % 60) . 'mins' : null,
                                        'cardLang' => 'English',
                                        'cardPath' => route('frontend.watch', $rec->slug),
                                        'cardGenres' => $rec->genres->take(2)->pluck('name')->all(),
                                        'watchableType' => 'movie',
                                        'watchableId'   => $rec->id,
                                    ])
                                </li>
                            @endforeach
                        </ul>
                        <div class="swiper-button swiper-button-next d-none d-lg-block"></div>
                        <div class="swiper-button swiper-button-prev d-none d-lg-block"></div>
                    </div>
                </div>
            </div>
        @endif

        @if ($similar->count())
            <div class="show-episode section-padding">
                <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
                    <h5 class="main-title text-capitalize mb-0 fw-medium">Similar Movies</h5>
                </div>
                <div class="card-style-slider">
                    <div class="position-relative swiper swiper-card mt-4 mb-5" data-slide="7"
                        data-laptop="7" data-tab="4" data-mobile="3" data-mobile-sm="3"
                        data-autoplay="false" data-loop="false" data-navigation="true" data-pagination="true">
                        <ul class="p-0 swiper-wrapper m-0 list-inline">
                            @foreach ($similar as $sim)
                                <li class="swiper-slide">
                                    @include('frontend::components.cards.card-style', [
                                        'cardImage' => $sim->poster_url ?: 'media/rabbit-portrait.webp',
                                        'cardTitle' => $sim->title,
                                        'movietime' => $sim->runtime_minutes ? floor($sim->runtime_minutes / 60) . 'hr : ' . ($sim->runtime_minutes % 60) . 'mins' : null,
                                        'cardLang' => 'English',
                                        'cardPath' => route('frontend.watch', $sim->slug),
                                        'cardGenres' => $sim->genres->take(2)->pluck('name')->all(),
                                        'watchableType' => 'movie',
                                        'watchableId'   => $sim->id,
                                    ])
                                </li>
                            @endforeach
                        </ul>
                        <div class="swiper-button swiper-button-next d-none d-lg-block"></div>
                        <div class="swiper-button swiper-button-prev d-none d-lg-block"></div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

@include('frontend::components.widgets.mobile-footer')

@if ($source)
<script>
// Wait for the @videojs/html module to upgrade the <video-player> element
// before we poke at the inner <video>. Using `customElements.whenDefined`
// instead of a naive setTimeout so this is deterministic: the custom
// element upgrade has fired and querySelector-ing `video#id` returns the
// real HTMLVideoElement with a working media API.
document.addEventListener('DOMContentLoaded', async function () {
    await customElements.whenDefined('video-player');

    const video = document.getElementById('jambo-watch-player');
    const frame = document.getElementById('jambo-player-frame');
    const closeBtn = document.getElementById('jambo-mini-close');
    if (!video) return;

    // Best-effort muted autoplay. Browsers that block it leave the big
    // play icon up, which is the expected UX.
    video.muted = true;
    const playAttempt = video.play();
    if (playAttempt && typeof playAttempt.catch === 'function') playAttempt.catch(() => {});

    // --- Settings gear menu --------------------------------------------
    if (typeof window.jamboAttachSettingsMenu === 'function') {
        window.jamboAttachSettingsMenu('jambo-watch-player');
    }
    if (typeof window.jamboAttachGestures === 'function') {
        window.jamboAttachGestures('jambo-watch-player');
    }

    // --- Heartbeat ------------------------------------------------------
    // Guests browse + play free content but have no watch history, so
    // the heartbeat only fires for authed users.
    const isAuthed = {{ auth()->check() ? 'true' : 'false' }};
    const payableId = {{ Js::from($movie->id) }};
    const heartbeatUrl = {{ Js::from(url('/api/v1/streaming/heartbeat')) }};
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    let lastPosition = 0;
    let duration = null;

    video.addEventListener('timeupdate', () => {
        lastPosition = video.currentTime || 0;
        duration = Number.isFinite(video.duration) ? video.duration : null;
    });

    let heartbeatTimer = null;

    async function sendHeartbeat() {
        if (!isAuthed) return;
        if (window.jamboKicked) return; // short-circuit once kicked
        if (lastPosition <= 0) return;
        try {
            const res = await fetch(heartbeatUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    payable_type: 'movie',
                    payable_id: payableId,
                    position: Math.floor(lastPosition),
                    duration: duration ? Math.floor(duration) : null,
                }),
            });
            // 409 = another device took over. The shared handler
            // (defined by jambo-kick-overlay partial) pauses every
            // player on the page and raises the overlay. Stop the
            // interval so we don't keep pinging after kick.
            const handled = await window.jamboHandleHeartbeatResponse?.(res);
            if (handled && heartbeatTimer) {
                clearInterval(heartbeatTimer);
                heartbeatTimer = null;
            }
        } catch (e) { console.debug('[watch] heartbeat failed', e); }
    }
    if (isAuthed) {
        video.addEventListener('pause', sendHeartbeat);
        video.addEventListener('ended', sendHeartbeat);
        heartbeatTimer = setInterval(sendHeartbeat, 15000);
        window.addEventListener('pagehide', sendHeartbeat);
    }

    // --- Mini-mode on scroll -------------------------------------------
    // Observe the in-flow sentinel (never moves), not the frame itself
    // (which gets portalled). This eliminates the old flicker loop where
    // the frame going fixed changed its intersection ratio and bounced
    // back to non-mini immediately.
    const sentinel = document.getElementById('jambo-player-sentinel');
    const hero = document.getElementById('jambo-player-hero');

    function enterMini() {
        if (frame.classList.contains('jambo-player-frame--mini')) return;
        // Portal out to <body> so no ancestor stacking context (header,
        // transformed wrapper, etc.) can clip or cap our z-index.
        document.body.appendChild(frame);
        frame.style.position = '';
        frame.style.inset = '';
        frame.classList.add('jambo-player-frame--mini');
    }
    function exitMini() {
        if (!frame.classList.contains('jambo-player-frame--mini')) return;
        frame.classList.remove('jambo-player-frame--mini');
        frame.style.position = 'absolute';
        frame.style.inset = '0';
        if (hero) hero.appendChild(frame);
    }

    if ('IntersectionObserver' in window && sentinel) {
        const io = new IntersectionObserver((entries) => {
            if (document.fullscreenElement) return;
            const r = entries[0].intersectionRatio;
            // Hysteresis: only enter mini at <25% visible, only exit at
            // >50% visible. Prevents flicker when the user parks near
            // the boundary.
            if (r < 0.25) enterMini();
            else if (r > 0.5) exitMini();
        }, { threshold: [0, 0.25, 0.5, 1] });
        io.observe(sentinel);
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            exitMini();
            sendHeartbeat();
            try { video.pause(); } catch (e) {}
            if (hero) hero.style.display = 'none';
        });
    }
});
</script>

{{-- Shared kick overlay + heartbeat response handler. Defines
     window.jamboHandleHeartbeatResponse() which the heartbeat loop
     above calls after every fetch — on 409 terminated the overlay
     takes over and subsequent heartbeats skip. --}}
@include('frontend::components.partials.jambo-kick-overlay')
@endif
@endsection
