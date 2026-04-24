{{--
    Shared "you've been signed out here" overlay + heartbeat response
    handler. Included once per player view; exposes a small global
    API every player's heartbeat loop can call in one line:

        const handled = await window.jamboHandleHeartbeatResponse(res);
        if (handled) return;   // overlay is up; stop firing heartbeats.

    Why: we have four player views (Streaming/watch, Movies/watch-page,
    TvShows/episode-page, watchlist-play-page) each with its own
    heartbeat loop. Rather than inline the overlay HTML + reclaim wiring
    in all four, the overlay lives here and each loop only needs to
    call the exposed function. Loops still poke their own player
    instance on the pause side (videojs vs <video> element differ per
    view), but everything above that is shared.

    Backward-compat: if a view already has its own `#jambo-kicked-overlay`
    (the Streaming module's watch.blade.php does), this partial is a
    no-op on that page because the elements already exist — the first
    `getElementById` will return those pre-existing nodes.
--}}

<div id="jambo-kicked-overlay"
     class="d-none position-fixed top-0 start-0 w-100 h-100 align-items-center justify-content-center text-center"
     style="background: rgba(5, 6, 8, 0.94); backdrop-filter: blur(6px); z-index: 10050; padding: 2rem;"
     aria-live="assertive" role="alertdialog" aria-modal="true">
    <div style="max-width: 440px;">
        <div class="d-inline-flex align-items-center justify-content-center mb-3"
             style="width:64px; height:64px; border-radius:50%; background: rgba(var(--bs-warning-rgb), 0.15); color: var(--bs-warning);">
            <i class="ph ph-sign-out" style="font-size: 1.9rem;"></i>
        </div>
        <h3 class="text-white mb-2" id="jambo-kicked-title">You've been signed out</h3>
        <p class="text-muted mb-4" id="jambo-kicked-body">
            Another device on your account started streaming. This device has been signed out. Log in again to keep watching here.
        </p>
        <div class="d-flex gap-2 flex-wrap justify-content-center">
            <a href="{{ route('login') }}" class="btn btn-primary" id="jambo-kicked-login-btn">
                <i class="ph ph-sign-in me-1"></i> Sign in
            </a>
            <a href="{{ route('frontend.ott') }}" class="btn btn-outline-light">
                <i class="ph ph-house me-1"></i> Back to home
            </a>
        </div>
    </div>
</div>

<script>
(function () {
    // Idempotent: if a page included the partial twice (possible when
    // one view @includes another), only the first definition sticks.
    if (typeof window.jamboHandleHeartbeatResponse === 'function') return;

    // Global flag heartbeat loops check to stop firing after the
    // overlay is up. Read as `window.jamboKicked`.
    window.jamboKicked = false;

    function pauseAnyPlayer() {
        // HTML5 <video> elements
        document.querySelectorAll('video').forEach(function (v) {
            try { v.pause(); } catch (e) {}
        });
        // videojs registry (used by the Streaming module's watch page)
        if (window.videojs && typeof window.videojs.getPlayers === 'function') {
            try {
                const players = window.videojs.getPlayers();
                for (const id in players) {
                    try { players[id] && players[id].pause && players[id].pause(); } catch (e) {}
                }
            } catch (e) {}
        }
    }

    function showOverlay(body) {
        const ov = document.getElementById('jambo-kicked-overlay');
        if (!ov) return;
        ov.classList.remove('d-none');
        ov.classList.add('d-flex');
        // Server passes login_url/home_url in the 409 body; prefer
        // those over the ones baked into the href attributes so we
        // can steer kicked users at different destinations in the
        // future without shipping a new blade.
        const loginBtn = document.getElementById('jambo-kicked-login-btn');
        if (loginBtn && body && body.login_url) {
            loginBtn.setAttribute('href', body.login_url);
        }
    }

    /**
     * Inspect a heartbeat fetch Response. Returns true if this was a
     * kick (409 terminated) and the overlay has been shown — the
     * caller should stop firing further heartbeats.
     *
     * By the time this returns true the server has already invalidated
     * the Set-Cookie session, so the user's next navigation is
     * guaranteed to land in the guest path. The overlay just gives
     * them warning + a clean way to sign back in.
     */
    window.jamboHandleHeartbeatResponse = async function (res) {
        if (!res || res.status !== 409) return false;
        let body = {};
        try { body = await res.clone().json(); } catch (e) {}
        if (!body || !body.terminated) return false;

        window.jamboKicked = true;
        pauseAnyPlayer();
        showOverlay(body);
        return true;
    };
})();
</script>
