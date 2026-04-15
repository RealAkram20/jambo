@extends('frontend::layouts.master', ['isSwiperSlider' => true, 'bodyClass' => 'custom-header-relative', 'isSelect2' => true])

@php
    $poster = $episode->still_url ?: ($show->backdrop_url ?: $show->poster_url);
    $season = $episode->season;
    $seasons = $show->seasons->sortBy('number');
    $headline = 'S' . str_pad($season->number, 2, '0', STR_PAD_LEFT) . 'E' . str_pad($episode->number, 2, '0', STR_PAD_LEFT) . ' — ' . $episode->title;
@endphp

@section('content')

@if ($source)
    <link rel="stylesheet" href="{{ asset('frontend/css/player.css') }}">
    <script type="module" src="https://cdn.jsdelivr.net/npm/@videojs/html/cdn/video-minimal-ui.js"></script>
    <script src="{{ asset('frontend/js/jambo-settings-menu.js') }}" defer></script>

    <div class="jambo-watch-hero" id="jambo-player-hero">
        <div class="jambo-player-sentinel" id="jambo-player-sentinel"></div>
        <div class="jambo-player-frame" id="jambo-player-frame" style="position:absolute; inset:0;">
            <button type="button" class="jambo-mini-close" id="jambo-mini-close" aria-label="Close mini-player" title="Close">
                <i class="ph ph-x"></i>
            </button>
            @include('frontend::components.partials.jambo-minimal-player', [
                'playerSrc' => $source['url'],
                'playerPoster' => $poster,
                'playerId' => 'jambo-watch-player',
            ])
        </div>
    </div>
@else
    <div class="jambo-watch-hero">
        <div class="jambo-player-frame d-flex align-items-center justify-content-center text-light">
            <div class="text-center p-5">
                <h3 class="mb-3">This episode isn't streamable yet.</h3>
                <p class="text-muted mb-4">No Video URL has been set for <strong>{{ $headline }}</strong>.</p>
                <a href="{{ route('frontend.tvshow_detail', $show->slug) }}" class="btn btn-outline-light">← Back to series</a>
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
<script>
document.addEventListener('DOMContentLoaded', async function () {
    await customElements.whenDefined('video-player');

    const video = document.getElementById('jambo-watch-player');
    const frame = document.getElementById('jambo-player-frame');
    const closeBtn = document.getElementById('jambo-mini-close');
    const autoplayToggle = document.getElementById('autoplay-next');
    if (!video) return;

    video.muted = true;
    const playAttempt = video.play();
    if (playAttempt && typeof playAttempt.catch === 'function') playAttempt.catch(() => {});

    if (typeof window.jamboAttachSettingsMenu === 'function') {
        window.jamboAttachSettingsMenu('jambo-watch-player');
    }

    // Persist the autoplay-next preference across episodes.
    const AUTOPLAY_KEY = 'jambo.autoplayNext';
    if (autoplayToggle) {
        autoplayToggle.checked = localStorage.getItem(AUTOPLAY_KEY) === '1';
        autoplayToggle.addEventListener('change', () => {
            localStorage.setItem(AUTOPLAY_KEY, autoplayToggle.checked ? '1' : '0');
        });
    }

    const payableId = {{ Js::from($episode->id) }};
    const heartbeatUrl = {{ Js::from(url('/api/v1/streaming/heartbeat')) }};
    const nextEpisodeUrl = {{ Js::from($nextEpisode ? route('frontend.episode', $nextEpisode->id) : null) }};
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    let lastPosition = 0;
    let duration = null;

    video.addEventListener('timeupdate', () => {
        lastPosition = video.currentTime || 0;
        duration = Number.isFinite(video.duration) ? video.duration : null;
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
    video.addEventListener('pause', sendHeartbeat);
    setInterval(sendHeartbeat, 15000);
    window.addEventListener('pagehide', sendHeartbeat);

    // Autoplay-next: flush progress, then navigate when enabled.
    video.addEventListener('ended', async function () {
        await sendHeartbeat();
        if (nextEpisodeUrl && autoplayToggle && autoplayToggle.checked) {
            window.location.href = nextEpisodeUrl;
        }
    });

    // Mini-mode: observe the stable sentinel, portal the frame to <body>
    // (see watch-page.blade.php for the full rationale).
    const sentinel = document.getElementById('jambo-player-sentinel');
    const hero = document.getElementById('jambo-player-hero');

    function enterMini() {
        if (frame.classList.contains('jambo-player-frame--mini')) return;
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
@endif
@endsection
