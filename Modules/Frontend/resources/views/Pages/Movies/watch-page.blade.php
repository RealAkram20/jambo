@extends('frontend::layouts.master', ['isSwiperSlider' => true, 'isVideoJs' => true, 'bodyClass' => 'custom-header-relative', 'isSelect2' => true])

@php
    $poster = $movie->backdrop_url ?: $movie->poster_url;
@endphp

@section('content')

{{-- Clean YouTube-style player container. Deliberately NOT using the
     template's .iq-main-slider wrapper, which baked in min-height:80vh
     + thick padding + a gradient scrim — all wrong for a playback
     hero where the video itself should own the visual weight. --}}
<div class="jambo-watch-hero" id="jambo-hero">
    @if ($source)
        <div id="jambo-player-slot" class="jambo-player-slot">
            <div class="jambo-inline-wrap" id="jambo-inline-wrap">
                <button type="button" class="jambo-mini-close" id="jambo-mini-close" aria-label="Close mini-player" title="Close">
                    <i class="ph ph-x"></i>
                </button>
                <video
                    id="jambo-watch-player"
                    class="video-js vjs-default-skin vjs-big-play-centered"
                    controls
                    preload="auto"
                    playsinline
                    @if ($poster) poster="{{ $poster }}" @endif></video>
            </div>
        </div>
    @else
        <div class="jambo-player-slot d-flex align-items-center justify-content-center text-light">
            <div class="text-center p-5">
                <h3 class="mb-3">This title isn't streamable yet.</h3>
                <p class="text-muted mb-4">No Video URL has been set for <strong>{{ $movie->title }}</strong>.</p>
                <a href="{{ route('frontend.movie_detail', $movie->slug) }}" class="btn btn-outline-light">← Back to details</a>
            </div>
        </div>
    @endif
</div>

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
        {{-- Recommended Movies — mirrors /episode's season+episode section:
             the same show-episode container pattern, without the pill nav. --}}
        @if ($recommended->count())
            <div class="show-episode section-padding">
                <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
                    <h5 class="main-title text-capitalize mb-0 fw-medium">{{ __('sectionTitle.recommended_movie') }}</h5>
                </div>
                <div class="card-style-slider">
                    <div class="position-relative swiper swiper-card mt-4 mb-5 overflow-hidden" data-slide="5"
                        data-laptop="5" data-tab="3" data-mobile="2" data-mobile-sm="2"
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

        {{-- Similar Movies — scored by shared cast (×2) + shared genres.
             Same visual container as Recommended so the rails read as
             a pair. --}}
        @if ($similar->count())
            <div class="show-episode section-padding">
                <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
                    <h5 class="main-title text-capitalize mb-0 fw-medium">Similar Movies</h5>
                </div>
                <div class="card-style-slider">
                    <div class="position-relative swiper swiper-card mt-4 mb-5 overflow-hidden" data-slide="5"
                        data-laptop="5" data-tab="3" data-mobile="2" data-mobile-sm="2"
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
<style>
    /* Hero wrapper: centered, max 1600px, a little breathing room from
       the edges. Replaces the template's .iq-main-slider which had
       80vh min-height + 3.75em padding baked in. */
    .jambo-watch-hero {
        width: 100%;
        max-width: 1600px;
        margin: 0 auto;
        padding: 0;
        background: #000;
    }
    .jambo-player-slot {
        position: relative;
        width: 100%;
        aspect-ratio: 16 / 9;
        background: #000;
        overflow: hidden;
    }
    .jambo-inline-wrap {
        position: absolute;
        inset: 0;
        background: #000;
    }
    /* Video.js in "fill" mode uses absolute positioning itself; force
       full-bleed so its controls reach the edges of the slot. */
    .jambo-inline-wrap .video-js {
        position: absolute;
        inset: 0;
        width: 100% !important;
        height: 100% !important;
    }
    .jambo-inline-wrap .video-js .vjs-tech { width: 100%; height: 100%; }

    body.jambo-mini-active .jambo-inline-wrap {
        position: fixed;
        inset: auto 16px 16px auto;
        width: min(380px, 80vw);
        aspect-ratio: 16/9;
        height: auto;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.6);
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
    const src = {{ Js::from([
        'type' => $source['type'],
        'url' => $source['url'],
        'mime' => $source['mime'] ?? null,
    ]) }};
    const payableId = {{ Js::from($movie->id) }};
    const heartbeatUrl = {{ Js::from(url('/api/v1/streaming/heartbeat')) }};
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const slot = document.getElementById('jambo-player-slot');
    const inlineWrap = document.getElementById('jambo-inline-wrap');
    const closeBtn = document.getElementById('jambo-mini-close');

    const player = videojs('jambo-watch-player', {
        controls: true,
        fill: true,
        autoplay: 'muted',
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

    player.ready(function () {
        const p = player.play();
        if (p && typeof p.catch === 'function') p.catch(() => {});
    });

    let lastPosition = 0;
    let duration = null;

    player.on('timeupdate', () => {
        lastPosition = player.currentTime() || 0;
        duration = player.duration() || null;
    });

    async function sendHeartbeat() {
        if (lastPosition <= 0) return;
        try {
            await fetch(heartbeatUrl, {
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
        } catch (e) { console.debug('[watch] heartbeat failed', e); }
    }
    player.on('pause', sendHeartbeat);
    player.on('ended', sendHeartbeat);
    setInterval(sendHeartbeat, 15000);
    window.addEventListener('pagehide', sendHeartbeat);

    if ('IntersectionObserver' in window && slot) {
        const io = new IntersectionObserver((entries) => {
            if (document.fullscreenElement) return;
            const r = entries[0].intersectionRatio;
            if (r < 0.25) {
                document.body.classList.add('jambo-mini-active');
            } else if (r > 0.5) {
                document.body.classList.remove('jambo-mini-active');
            }
        }, { threshold: [0, 0.25, 0.5, 1] });
        io.observe(slot);
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            document.body.classList.remove('jambo-mini-active');
            sendHeartbeat();
            try { player.pause(); } catch (e) {}
            try { player.dispose(); } catch (e) {}
            if (slot) slot.style.display = 'none';
        });
    }
})();
</script>
@endif
@endsection
