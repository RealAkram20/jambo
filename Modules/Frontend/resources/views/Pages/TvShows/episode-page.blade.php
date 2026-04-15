@extends('frontend::layouts.master', ['isSwiperSlider' => true, 'isVideoJs' => true, 'bodyClass' => 'custom-header-relative', 'isSelect2' => true])

@php
    $poster = $episode->still_url ?: ($show->backdrop_url ?: $show->poster_url);
    $season = $episode->season;
    $seasons = $show->seasons->sortBy('number');
    $headline = 'S' . str_pad($season->number, 2, '0', STR_PAD_LEFT) . 'E' . str_pad($episode->number, 2, '0', STR_PAD_LEFT) . ' — ' . $episode->title;
@endphp

@section('content')

{{-- Clean player hero, same pattern as /watch/{slug}. --}}
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
                <h3 class="mb-3">This episode isn't streamable yet.</h3>
                <p class="text-muted mb-4">No Video URL has been set for <strong>{{ $headline }}</strong>.</p>
                <a href="{{ route('frontend.tvshow_detail', $show->slug) }}" class="btn btn-outline-light">← Back to series</a>
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
                                'moveName' => $headline,
                                'movieReleased' => $episode->published_at?->format('Y') ?: ($show->year ?? ''),
                                'movieViews' => '— ' . __('frontendplaylist.views'),
                                'isNotimdbRating' => true,
                                'movieDuration' => $episode->runtime_minutes ? $episode->runtime_minutes . ' min' : '—',
                                'isNotTVShowbadge' => true,
                                'isNotstartWatching' => true,
                                'isNotwatchList' => true,
                                'movieDescription' => $episode->synopsis,
                                'movieGenres' => $show->genres->pluck('name')->all(),
                            ])

                            {{-- Autoplay next episode toggle. Preference
                                 persists in localStorage under
                                 `jambo.autoplayNext` so it survives
                                 navigation to the next episode. --}}
                            @if ($nextEpisode)
                                <div class="d-flex align-items-center gap-3 mb-4">
                                    <label class="d-flex align-items-center gap-2 form-check-label" for="autoplay-next" style="cursor:pointer;">
                                        <input class="form-check-input m-0" type="checkbox" role="switch" id="autoplay-next">
                                        <span class="fw-medium">Autoplay next episode</span>
                                        <span class="text-muted" style="font-size: 12px;">
                                            Up next: S{{ str_pad($nextEpisode->season->number ?? $season->number, 2, '0', STR_PAD_LEFT) }}E{{ str_pad($nextEpisode->number, 2, '0', STR_PAD_LEFT) }} — {{ $nextEpisode->title }}
                                        </span>
                                    </label>
                                </div>
                            @endif

                            <div class="mt-3">
                                <small class="text-muted">{{ __('sectionTitle.from') ?? 'From' }}:</small>
                                <a href="{{ route('frontend.tvshow_detail', $show->slug) }}" class="ms-1 text-primary text-decoration-none">{{ $show->title }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="overflow-hidden">
        @if ($seasons->count())
            <div class="show-episode section-padding">
                <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
                    <h5 class="main-title text-capitalize mb-0 fw-medium">{{ __('header.episodes') }}</h5>
                </div>
                <ul class="nav nav-pills custom-tab-slider episode-nav-btn gap-3 mb-4 pb-2" role="tablist">
                    @foreach ($seasons as $s)
                        <li class="nav-item">
                            <a class="nav-link {{ $s->id === $season->id ? 'active show' : '' }}" data-bs-toggle="pill"
                                href="#season-{{ $s->number }}" role="tab"
                                aria-selected="{{ $s->id === $season->id ? 'true' : 'false' }}">
                                {{ __('streamEpisode.season') }} {{ $s->number }}
                            </a>
                        </li>
                    @endforeach
                </ul>
                <div class="tab-content">
                    @foreach ($seasons as $s)
                        <div id="season-{{ $s->number }}" class="tab-pane animated fadeInUp {{ $s->id === $season->id ? 'active show' : '' }}" role="tabpanel">
                            <div class="card-style-slider">
                                <div class="position-relative swiper swiper-card mt-4 mb-5 overflow-hidden" data-slide="5"
                                    data-laptop="5" data-tab="2" data-mobile="2" data-mobile-sm="1"
                                    data-autoplay="false" data-loop="false">
                                    <div class="p-0 swiper-wrapper m-0 list-inline">
                                        @foreach ($s->episodes->sortBy('number') as $ep)
                                            <div class="swiper-slide">
                                                @include('frontend::components.cards.episode-card', [
                                                    'episodePath' => route('frontend.episode', $ep->id),
                                                    'showImg' => $ep->still_url ?: 'media/episode/s1e1-the-buddha.webp',
                                                    'id' => $ep->id,
                                                    'episodeNumber' => 'S' . str_pad($s->number, 2, '0', STR_PAD_LEFT) . 'E' . str_pad($ep->number, 2, '0', STR_PAD_LEFT),
                                                    'episodTitle' => $ep->title,
                                                    'episodeTitlesText' => $ep->title,
                                                    'episodeDetailText' => $ep->synopsis ?: '',
                                                    'episodTime' => $ep->runtime_minutes ? $ep->runtime_minutes . 'm' : '—',
                                                ])
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

@include('frontend::components.widgets.mobile-footer')

@if ($source)
@include('frontend::components.partials.jambo-player-extras')
<style>
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
    const payableId = {{ Js::from($episode->id) }};
    const heartbeatUrl = {{ Js::from(url('/api/v1/streaming/heartbeat')) }};
    const nextEpisodeUrl = {{ Js::from($nextEpisode ? route('frontend.episode', $nextEpisode->id) : null) }};
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const slot = document.getElementById('jambo-player-slot');
    const inlineWrap = document.getElementById('jambo-inline-wrap');
    const closeBtn = document.getElementById('jambo-mini-close');
    const autoplayToggle = document.getElementById('autoplay-next');

    // Restore the autoplay-next preference from the previous episode.
    const AUTOPLAY_KEY = 'jambo.autoplayNext';
    if (autoplayToggle) {
        autoplayToggle.checked = localStorage.getItem(AUTOPLAY_KEY) === '1';
        autoplayToggle.addEventListener('change', () => {
            localStorage.setItem(AUTOPLAY_KEY, autoplayToggle.checked ? '1' : '0');
        });
    }

    const player = videojs('jambo-watch-player', {
        controls: true,
        fill: true,
        autoplay: 'muted',
        muted: true,
        playsinline: true,
        techOrder: src.type === 'youtube' ? ['youtube', 'html5'] : ['html5'],
        sources: [
            src.type === 'youtube'
                ? { src: src.url, type: 'video/youtube' }
                : { src: src.url, type: src.mime || 'video/mp4' },
        ],
        youtube: { iv_load_policy: 3, modestbranding: 1, rel: 0 },
    });

    jamboAttachSettingsMenu(player);

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
                    payable_type: 'episode',
                    payable_id: payableId,
                    position: Math.floor(lastPosition),
                    duration: duration ? Math.floor(duration) : null,
                }),
            });
        } catch (e) { console.debug('[watch] heartbeat failed', e); }
    }
    player.on('pause', sendHeartbeat);
    setInterval(sendHeartbeat, 15000);
    window.addEventListener('pagehide', sendHeartbeat);

    // Autoplay-next: on end, flush progress, then navigate if enabled.
    player.on('ended', async function () {
        await sendHeartbeat();
        if (nextEpisodeUrl && autoplayToggle && autoplayToggle.checked) {
            window.location.href = nextEpisodeUrl;
        }
    });

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
