@extends('frontend::layouts.blank')

@section('content')

<link href="https://vjs.zencdn.net/8.21.1/video-js.css" rel="stylesheet" />

<div class="back-btn">
    <a class="btn btn-link text-white text-decoration-none p-0" href="{{ $backUrl }}" title="{{ $backLabel }}">
        <i class="ph ph-x"></i>
    </a>
</div>

<div class="video-container d-flex align-items-center justify-content-center" style="min-height: 100vh; background:#000;">
    @if (! $source)
        <div class="text-center text-light p-5">
            <h3 class="mb-3">This title isn't streamable yet.</h3>
            <p class="text-muted mb-4">No Video URL has been set for <strong>{{ $title }}</strong>.</p>
            <a href="{{ $backUrl }}" class="btn btn-outline-light">{{ $backLabel }}</a>
        </div>
    @else
        <div class="w-100" style="max-width: 1280px;">
            <video
                id="jambo-player"
                class="video-js vjs-default-skin vjs-big-play-centered vjs-fluid"
                controls
                preload="auto"
                playsinline
                @if ($poster) poster="{{ $poster }}" @endif
                data-resume="{{ $resumePosition }}">
            </video>
        </div>
    @endif
</div>

@if ($source)
<script src="https://vjs.zencdn.net/8.21.1/video.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/videojs-youtube@3.0.1/dist/Youtube.min.js"></script>
<script>
(function () {
    @php
        $payableKind = str_ends_with($payableType, 'Episode') ? 'episode' : 'movie';
    @endphp
    // Guests can reach this page for free content — they have no
    // watch history, so the heartbeat loop is only set up for authed
    // users.
    const isAuthed = {{ auth()->check() ? 'true' : 'false' }};
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const heartbeatUrl = '{{ url('/api/v1/streaming/heartbeat') }}';
    const payload = {
        payable_type: {{ Js::from($payableKind) }},
        payable_id: {{ Js::from($payableId) }},
    };
    const HEARTBEAT_MS = 15000;
    const resume = Number({{ Js::from((int) $resumePosition) }});
    const source = {{ Js::from([
        'type' => $source['type'],
        'url' => $source['url'],
        'mime' => $source['mime'] ?? null,
    ]) }};

    const options = {
        controls: true,
        fluid: true,
        playsinline: true,
        // YouTube tech has to come first so YouTube sources route to it.
        techOrder: source.type === 'youtube' ? ['youtube', 'html5'] : ['html5'],
        // Light play-rate menu.
        playbackRates: [0.5, 1, 1.25, 1.5, 2],
        // YouTube plugin prefers the original watch URL. Feed html5 the
        // direct file URL + mime the server resolved.
        sources: [
            source.type === 'youtube'
                ? { src: source.url, type: 'video/youtube' }
                : { src: source.url, type: source.mime || 'video/mp4' },
        ],
        youtube: {
            iv_load_policy: 3,
            modestbranding: 1,
            rel: 0,
        },
    };

    const player = videojs('jambo-player', options);

    // Resume: seek once metadata is available. Leave a 5s buffer off
    // the end so a fully-watched title doesn't immediately skip to
    // the last frame on replay.
    player.one('loadedmetadata', function () {
        const total = player.duration();
        if (resume > 0 && total && resume < total - 5) {
            player.currentTime(resume);
        }
    });

    let lastPosition = 0;
    let duration = null;

    player.on('timeupdate', function () {
        lastPosition = player.currentTime() || 0;
        duration = player.duration() || null;
    });

    async function sendHeartbeat() {
        if (!isAuthed) return;
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
                    ...payload,
                    position: Math.floor(lastPosition),
                    duration: duration ? Math.floor(duration) : null,
                }),
            });
        } catch (e) {
            console.debug('[watch] heartbeat failed', e);
        }
    }

    if (isAuthed) {
        player.on('pause', sendHeartbeat);
        player.on('ended', sendHeartbeat);
        setInterval(sendHeartbeat, HEARTBEAT_MS);
        window.addEventListener('pagehide', sendHeartbeat);
    }
})();
</script>
@endif

@endsection
