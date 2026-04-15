@extends('frontend::layouts.master', ['isSwiperSlider' => true, 'isVideoJs' => true, 'bodyClass' => 'custom-header-relative', 'isSelect2' => true])

@php
    $backdrop = $movie->backdrop_url ?: $movie->poster_url;
    $posterSrc = $backdrop && \Illuminate\Support\Str::startsWith($backdrop, ['http://', 'https://'])
        ? $backdrop
        : ($backdrop ? asset('frontend/images/' . $backdrop) : asset('frontend/images/media/gameofhero.webp'));
    $trailer = $movie->trailer_url ?: 'https://www.youtube.com/watch?v=spGSAeqxVUc';
    $cast = $movie->cast->filter(fn ($p) => ($p->pivot->role ?? null) === 'actor');
    $crew = $movie->cast->filter(fn ($p) => in_array(($p->pivot->role ?? null), ['director', 'writer', 'producer']));
@endphp

@section('content')
<div class="position-relative" id="jambo-hero-wrap">
    <div class="iq-main-slider site-video position-relative">
        @php
            $videoSetup = json_encode([
                'techOrder' => ['youtube'],
                'sources' => [['type' => 'video/youtube', 'src' => $trailer]],
                'youtube' => ['modestbranding' => 1, 'rel' => 0, 'showinfo' => 0, 'autoplay' => 1],
                'fullscreen' => true,
            ]);
        @endphp
        <video id="my-video" poster="{{ $posterSrc }}"
            class="my-video video-js vjs-big-play-centered w-100" loop autoplay muted preload="auto"
            data-setup='{!! $videoSetup !!}'>
        </video>

        {{-- Inline full-feature player. Hidden until the user clicks
             Start Watching. The `player-slot` is the sentinel the
             IntersectionObserver watches; it keeps its own 16:9 height
             whether or not the player-wrap is currently floated into
             mini mode, so toggling never causes layout oscillation. --}}
        <div id="jambo-player-slot" class="jambo-player-slot" hidden>
            <div id="jambo-inline-wrap" class="jambo-inline-wrap">
                <button type="button" class="jambo-mini-close" id="jambo-mini-close" aria-label="Close mini-player" title="Close">
                    <i class="ph ph-x"></i>
                </button>
                <video
                    id="jambo-inline-player"
                    class="video-js vjs-default-skin vjs-big-play-centered"
                    controls
                    preload="auto"
                    playsinline></video>
            </div>
        </div>
    </div>

    <div class="movie-detail-part position-relative">
        <div class="trending-info pt-0 pb-0">
            <div class="details-parts">
                @include('frontend::components.cards.movie-description', [
                    'moveName' => $movie->title,
                    'movieType' => $movie->tier_required ? strtoupper($movie->tier_required) : 'PG',
                    'movieDuration' => $movie->runtime_minutes ? floor($movie->runtime_minutes / 60) . 'hr : ' . ($movie->runtime_minutes % 60) . 'mins' : '—',
                    'movieReleased' => $movie->year ?: ($movie->published_at?->format('Y') ?? ''),
                    'movieViews' => number_format($movie->views_count) . ' ' . __('streamTag.views'),
                    'imdbRating' => $movie->rating ?: '—',
                    'movieLanguage' => 'english',
                    'videoUrl' => route('frontend.watch', ['slug' => $movie->slug]),
                    'movieDescription' => $movie->synopsis,
                    'movieGenres' => $movie->genres->pluck('name')->all(),
                    'subscribeToWatch' => ! $canWatch,
                ])
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="overflow-hidden">
        {{-- Starring start --}}
        @if ($cast->count())
            <div class="favourite-person-block section-wraper">
                <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
                    <h4 class="main-title text-capitalize mb-0 fw-medium">{{ __('sectionTitle.starring') }}</h4>
                </div>
                <div class="position-relative swiper swiper-card" data-slide="11" data-laptop="11" data-tab="4" data-mobile="2"
                    data-mobile-sm="2" data-autoplay="false" data-loop="true" data-navigation="true" data-pagination="true">
                    <ul class="p-0 swiper-wrapper m-0 list-inline personality-card">
                        @foreach ($cast as $actor)
                            <li class="swiper-slide">
                                @include('frontend::components.cards.personality-card', [
                                    'castImage' => $actor->photo_url ?: 'olivia-foster.webp',
                                    'castTitle' => trim(($actor->first_name ?? '') . ' ' . ($actor->last_name ?? '')),
                                    'castCategory' => $actor->pivot->character_name ?: 'Actor',
                                ])
                            </li>
                        @endforeach
                    </ul>
                    <div class="d-none d-lg-block">
                        <div class="swiper-button swiper-button-next"></div>
                        <div class="swiper-button swiper-button-prev"></div>
                    </div>
                </div>
            </div>
        @endif
        {{-- Starring End --}}

        {{-- Crew start --}}
        @if ($crew->count())
            <div class="favourite-person-block">
                <section class="overflow-hidden">
                    <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
                        <h4 class="main-title text-capitalize mb-0">{{ __('sectionTitle.crew') }}</h4>
                    </div>
                    <div class="position-relative swiper swiper-card" data-slide="11" data-laptop="11" data-tab="4"
                        data-mobile="2" data-mobile-sm="2" data-autoplay="false" data-loop="true" data-navigation="true"
                        data-pagination="true">
                        <ul class="p-0 swiper-wrapper m-0 list-inline personality-card">
                            @foreach ($crew as $person)
                                <li class="swiper-slide">
                                    @include('frontend::components.cards.personality-card', [
                                        'castImage' => $person->photo_url ?: 'maria-rodriguez.webp',
                                        'castTitle' => trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? '')),
                                        'castCategory' => ucfirst($person->pivot->role ?? 'Crew'),
                                    ])
                                </li>
                            @endforeach
                        </ul>
                        <div class="swiper-button swiper-button-next d-none d-lg-block"></div>
                        <div class="swiper-button swiper-button-prev d-none d-lg-block"></div>
                    </div>
                </section>
            </div>
        @endif
        {{-- Crew End --}}

        {{-- Recommended --}}
        @if ($recommended->count())
            <section class="related-movie-block">
                <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
                    <h4 class="main-title text-capitalize mb-0">{{ __('sectionTitle.recommended_movie') }}</h4>
                </div>
                <div class="card-style-slider">
                    <div class="position-relative swiper swiper-card" data-slide="6" data-laptop="6" data-tab="3"
                        data-mobile="2" data-mobile-sm="2" data-autoplay="false" data-loop="false" data-navigation="true"
                        data-pagination="true">
                        <ul class="p-0 swiper-wrapper m-0 list-inline">
                            @foreach ($recommended as $rec)
                                <li class="swiper-slide">
                                    @include('frontend::components.cards.card-style', [
                                        'cardImage' => $rec->poster_url ?: 'media/rabbit-portrait.webp',
                                        'cardTitle' => $rec->title,
                                        'movietime' => $rec->runtime_minutes ? floor($rec->runtime_minutes / 60) . 'hr : ' . ($rec->runtime_minutes % 60) . 'mins' : null,
                                        'cardLang' => 'English',
                                        'cardPath' => route('frontend.movie_detail', $rec->slug),
                                        'cardGenres' => $rec->genres->take(2)->pluck('name')->all(),
                                    ])
                                </li>
                            @endforeach
                        </ul>
                        <div class="swiper-button swiper-button-next d-none d-lg-block"></div>
                        <div class="swiper-button swiper-button-prev d-none d-lg-block"></div>
                    </div>
                </div>
            </section>
        @endif
    </div>
</div>

@include('frontend::components.widgets.details-description-modal', [
    'movieName' => $movie->title,
    'year' => $movie->year ?: '',
    'views' => number_format($movie->views_count) . __('frontendplaylist.views'),
    'ratingCount' => $movie->rating ?: '',
])

{{-- Mobile Footer --}}
@include('frontend::components.widgets.mobile-footer')
{{-- Mobile Footer End --}}

@if ($canWatch && $source)
<style>
    /* The slot reserves hero space and is what the observer watches.
       Its aspect-ratio keeps the page from reflowing when the wrap
       goes fixed, which is what caused the earlier mini-mode flicker. */
    .jambo-player-slot {
        position: absolute;
        inset: 0;
        z-index: 5;
    }
    .jambo-player-slot[hidden] { display: none; }
    .jambo-inline-wrap {
        position: absolute;
        inset: 0;
        background: #000;
    }
    .jambo-inline-wrap .video-js { width: 100%; height: 100%; }

    /* Mini mode: float bottom-right, YouTube-style. Only the wrap
       leaves flow — the slot stays put so the observer doesn't ping. */
    body.jambo-mini-active .jambo-inline-wrap {
        position: fixed;
        inset: auto 16px 16px auto;
        width: min(380px, 80vw);
        aspect-ratio: 16/9;
        height: auto;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.6);
        /* Above Bootstrap modal backdrop (1050) and the sticky header. */
        z-index: 2147483646;
    }
    body.jambo-mini-active .jambo-inline-wrap .video-js { border-radius: 10px; }

    .jambo-mini-close {
        position: absolute;
        top: 6px;
        right: 6px;
        z-index: 10;
        width: 28px;
        height: 28px;
        border: 0;
        border-radius: 50%;
        background: rgba(0,0,0,0.65);
        color: #fff;
        display: none;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }
    .jambo-mini-close:hover { background: rgba(0,0,0,0.85); }
    body.jambo-mini-active .jambo-mini-close { display: flex; }
</style>

<script>
(function () {
    const config = {
        source: {{ Js::from([
            'type' => $source['type'],
            'url' => $source['url'],
            'mime' => $source['mime'] ?? null,
            'embed_url' => $source['embed_url'] ?? null,
        ]) }},
        payableId: {{ Js::from($movie->id) }},
        payableKind: 'movie',
        resume: {{ Js::from(0) }},
        heartbeatUrl: {{ Js::from(url('/api/v1/streaming/heartbeat')) }},
        csrf: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    };

    const heroWrap = document.getElementById('jambo-hero-wrap');
    const slot = document.getElementById('jambo-player-slot');
    const inlineWrap = document.getElementById('jambo-inline-wrap');
    const trailer = document.getElementById('my-video');
    const startBtn = document.querySelector('.iq-play-button a');
    const closeBtn = document.getElementById('jambo-mini-close');
    if (!heroWrap || !slot || !inlineWrap || !startBtn) return;

    let player = null;
    let lastPosition = 0;
    let duration = null;
    let miniObserver = null;

    function initPlayer() {
        const src = config.source;
        player = videojs('jambo-inline-player', {
            controls: true,
            fill: true,           // fill the parent; slot controls aspect ratio
            autoplay: 'muted',    // browsers block non-muted autoplay
            muted: true,
            playsinline: true,
            techOrder: src.type === 'youtube' ? ['youtube', 'html5'] : ['html5'],
            playbackRates: [0.5, 1, 1.25, 1.5, 2],
            sources: [
                src.type === 'youtube'
                    ? { src: src.url, type: 'video/youtube' }
                    : { src: src.url, type: src.mime || 'video/mp4' },
            ],
            youtube: { iv_load_policy: 3, modestbranding: 1, rel: 0 },
        });

        // Kick off playback explicitly — autoplay:'muted' can still be
        // denied on some browsers until the user interacts, so we try
        // and swallow the rejected promise if that happens.
        player.ready(function () {
            const p = player.play();
            if (p && typeof p.catch === 'function') p.catch(() => {});
        });

        player.one('loadedmetadata', function () {
            const total = player.duration();
            if (config.resume > 0 && total && config.resume < total - 5) {
                player.currentTime(config.resume);
            }
        });
        player.on('timeupdate', function () {
            lastPosition = player.currentTime() || 0;
            duration = player.duration() || null;
        });
        player.on('pause', sendHeartbeat);
        player.on('ended', sendHeartbeat);
        setInterval(sendHeartbeat, 15000);
        window.addEventListener('pagehide', sendHeartbeat);
    }

    async function sendHeartbeat() {
        if (lastPosition <= 0) return;
        try {
            await fetch(config.heartbeatUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': config.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    payable_type: config.payableKind,
                    payable_id: config.payableId,
                    position: Math.floor(lastPosition),
                    duration: duration ? Math.floor(duration) : null,
                }),
            });
        } catch (e) {
            console.debug('[watch-inline] heartbeat failed', e);
        }
    }

    function startWatching(e) {
        e.preventDefault();
        // Hide the trailer (keeps its slot height; the slot does too).
        if (trailer) {
            trailer.style.display = 'none';
            try { trailer.pause(); } catch (err) {}
        }
        slot.hidden = false;
        if (!player) initPlayer();
        attachMiniObserver();
    }

    function attachMiniObserver() {
        if (miniObserver || !('IntersectionObserver' in window)) return;
        miniObserver = new IntersectionObserver(function (entries) {
            const entry = entries[0];
            // Don't kick into mini-mode if the user is fullscreen.
            if (document.fullscreenElement) return;
            if (entry.intersectionRatio < 0.25) {
                document.body.classList.add('jambo-mini-active');
            } else if (entry.intersectionRatio > 0.5) {
                // Hysteresis — we only go back out of mini when most
                // of the slot is visible again, which prevents flicker
                // when the user parks near the boundary.
                document.body.classList.remove('jambo-mini-active');
            }
        }, { threshold: [0, 0.25, 0.5, 1] });
        // The slot is in normal flow and never repositions, so its
        // intersection with the viewport is a stable signal.
        miniObserver.observe(slot);
    }

    // Close button exits mini mode by dismissing the player entirely.
    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            document.body.classList.remove('jambo-mini-active');
            sendHeartbeat();
            if (player) {
                try { player.pause(); } catch (err) {}
                try { player.dispose(); } catch (err) {}
                player = null;
            }
            if (miniObserver) { miniObserver.disconnect(); miniObserver = null; }
            slot.hidden = true;
            if (trailer) { trailer.style.display = ''; }
        });
    }

    startBtn.addEventListener('click', startWatching);
})();
</script>
@endif
@endsection
