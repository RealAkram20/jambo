@extends('frontend::layouts.blank')

@section('content')

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
    @elseif ($source['type'] === 'youtube')
        <div class="ratio ratio-16x9 w-100" style="max-width: 1280px;">
            <iframe
                id="yt-player"
                src="{{ $source['embed_url'] }}&enablejsapi=1&origin={{ urlencode(url('/')) }}"
                title="{{ $title }}"
                frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                referrerpolicy="strict-origin-when-cross-origin"
                allowfullscreen></iframe>
        </div>
    @else
        <video
            id="native-player"
            class="w-100"
            style="max-width: 1280px; max-height: 100vh;"
            controls
            preload="metadata"
            @if ($poster) poster="{{ $poster }}" @endif
            data-resume="{{ $resumePosition }}">
            <source src="{{ $source['url'] }}" type="{{ $source['mime'] }}">
            Your browser doesn't support HTML5 video.
        </video>
    @endif
</div>

<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const heartbeatUrl = '{{ url('/api/v1/streaming/heartbeat') }}';
    const payload = {
        payable_type: @json($payableType === 'Modules\Content\app\Models\Episode' ? 'episode' : 'movie'),
        payable_id: @json($payableId),
    };
    const HEARTBEAT_MS = 15000; // 15s — cheap and enough for resume granularity
    let lastPosition = 0;
    let duration = null;

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
                    ...payload,
                    position: Math.floor(lastPosition),
                    duration: duration ? Math.floor(duration) : null,
                }),
            });
        } catch (e) {
            console.debug('[watch] heartbeat failed', e);
        }
    }

    // Native <video>
    const native = document.getElementById('native-player');
    if (native) {
        const resume = Number(native.dataset.resume || 0);
        if (resume > 0) {
            native.addEventListener('loadedmetadata', () => {
                if (native.duration && resume < native.duration - 5) {
                    native.currentTime = resume;
                }
            }, { once: true });
        }
        native.addEventListener('timeupdate', () => {
            lastPosition = native.currentTime;
            duration = native.duration || null;
        });
        native.addEventListener('pause', sendHeartbeat);
        native.addEventListener('ended', sendHeartbeat);
        setInterval(sendHeartbeat, HEARTBEAT_MS);
    }

    // YouTube iframe — uses the IFrame API to get currentTime/duration.
    const yt = document.getElementById('yt-player');
    if (yt) {
        const tag = document.createElement('script');
        tag.src = 'https://www.youtube.com/iframe_api';
        document.head.appendChild(tag);

        window.onYouTubeIframeAPIReady = function () {
            const player = new YT.Player('yt-player', {
                events: {
                    onReady: (ev) => {
                        const resume = @json((int) $resumePosition);
                        const total = ev.target.getDuration();
                        if (resume > 0 && total && resume < total - 5) {
                            ev.target.seekTo(resume, true);
                        }
                        setInterval(() => {
                            lastPosition = ev.target.getCurrentTime() || 0;
                            duration = ev.target.getDuration() || null;
                        }, 1000);
                        setInterval(sendHeartbeat, HEARTBEAT_MS);
                    },
                    onStateChange: (ev) => {
                        // 0=ended, 2=paused
                        if (ev.data === 0 || ev.data === 2) sendHeartbeat();
                    },
                },
            });
        };
    }

    // Final flush when the user navigates away.
    window.addEventListener('pagehide', sendHeartbeat);
})();
</script>

@endsection
