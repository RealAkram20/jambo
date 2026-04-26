{{--
    Transcode status banner for the movie / episode admin edit page.

    Props:
      $progressUrl  — JSON polling endpoint for this asset
      $row          — the Movie or Episode model

    Renders one of five visual states:
      - none           No video source yet → quiet hint
      - queued         Job queued, hasn't started → spinner
      - downloading    Pulling source from Dropbox → spinner + label
      - transcoding    ffmpeg running → live progress bar
      - ready          Done → green checkmark
      - failed         Errored → red panel with error text + retry hint

    The script polls the progressUrl every 5 seconds while the asset is
    still mid-encode, swaps the bar, and stops once status flips to
    ready / failed (or the tab goes hidden, to be neighbourly with CPU).
--}}
@php
    $status = $row->transcode_status ?? null;
    $publishWhenReady = (bool) ($row->publish_when_ready ?? false);
    $hasVideo = !empty($row->video_url) || !empty($row->dropbox_path) || !empty($row->source_path);
    $hlsReady = !empty($row->hls_master_path);
@endphp

<div id="jambo-transcode-banner"
     class="rounded-3 mb-4 px-3 py-3"
     style="border:1px solid rgba(255,255,255,.08);background:#0f1422;"
     data-progress-url="{{ $progressUrl }}"
     data-status="{{ $status ?: ($hasVideo ? 'pending' : 'none') }}"
     data-publish-when-ready="{{ $publishWhenReady ? '1' : '0' }}">
    <div class="d-flex align-items-center gap-3">
        <div id="jambo-transcode-icon"
             class="d-flex align-items-center justify-content-center flex-shrink-0"
             style="width:40px;height:40px;border-radius:10px;background:rgba(26,152,255,.12);color:#1A98FF;font-size:20px;">
            <i class="ph ph-film-strip"></i>
        </div>
        <div class="flex-grow-1" style="min-width:0;">
            <div id="jambo-transcode-title" class="fw-semibold" style="font-size:14px;">
                Encoding status
            </div>
            <div id="jambo-transcode-message" class="text-muted" style="font-size:12.5px;">
                Loading…
            </div>
        </div>
        <div id="jambo-transcode-percent" class="fw-semibold text-end d-none" style="font-size:13px;min-width:48px;">
            0%
        </div>
    </div>

    <div id="jambo-transcode-progress-wrap" class="mt-3 d-none">
        <div style="height:6px;background:rgba(255,255,255,.06);border-radius:999px;overflow:hidden;">
            <div id="jambo-transcode-bar"
                 style="height:100%;width:0%;background:linear-gradient(90deg,#1A98FF,#5fb6ff);transition:width .6s ease;"></div>
        </div>
    </div>

    @if ($publishWhenReady)
        <div id="jambo-transcode-publish-hint" class="mt-2 d-flex align-items-center gap-2"
             style="font-size:12px;color:#7cbcf5;">
            <i class="ph ph-paper-plane-tilt"></i>
            <span>This asset will publish automatically and notify subscribers as soon as encoding finishes.</span>
        </div>
    @endif
</div>

<script>
(function () {
    var root      = document.getElementById('jambo-transcode-banner');
    if (!root) return;

    var iconEl    = document.getElementById('jambo-transcode-icon');
    var titleEl   = document.getElementById('jambo-transcode-title');
    var msgEl     = document.getElementById('jambo-transcode-message');
    var pctEl     = document.getElementById('jambo-transcode-percent');
    var barWrap   = document.getElementById('jambo-transcode-progress-wrap');
    var barEl     = document.getElementById('jambo-transcode-bar');
    var hintEl    = document.getElementById('jambo-transcode-publish-hint');

    var url       = root.dataset.progressUrl;
    var POLL_MS   = 5000;
    var timer     = null;

    var STATES = {
        none:        { icon: 'ph-film-strip',     bg: 'rgba(255,255,255,.05)', fg: '#a4a9b3', title: 'No video yet',         msg: 'Add a video source and save to start encoding.' },
        pending:     { icon: 'ph-hourglass',      bg: 'rgba(255,193,7,.12)',   fg: '#ffc107', title: 'Save to start',         msg: 'A video source is set but no transcode job has been queued yet.' },
        queued:      { icon: 'ph-hourglass',      bg: 'rgba(26,152,255,.12)',  fg: '#1A98FF', title: 'Queued',                 msg: 'Waiting for the encoder to pick this up.' },
        downloading: { icon: 'ph-cloud-arrow-down', bg: 'rgba(26,152,255,.12)', fg: '#1A98FF', title: 'Downloading source',    msg: 'Pulling the file from the source.' },
        transcoding: { icon: 'ph-spinner-gap',    bg: 'rgba(26,152,255,.12)',  fg: '#1A98FF', title: 'Encoding to HLS',       msg: 'ffmpeg is running. This page will update automatically.' },
        ready:       { icon: 'ph-check-circle',   bg: 'rgba(40,167,69,.15)',   fg: '#28a745', title: 'Encoding complete',     msg: 'This asset is ready to play in any browser.' },
        failed:      { icon: 'ph-warning-circle', bg: 'rgba(220,53,69,.15)',   fg: '#dc3545', title: 'Encoding failed',       msg: 'Re-save the form to retry. Error details below.' },
    };

    function applyState(state, percent, errorText) {
        var cfg = STATES[state] || STATES.none;
        iconEl.style.background = cfg.bg;
        iconEl.style.color = cfg.fg;
        iconEl.firstElementChild.className = 'ph ' + cfg.icon;
        titleEl.textContent = cfg.title;

        if (state === 'failed' && errorText) {
            msgEl.textContent = errorText;
            msgEl.style.color = '#ffb1ba';
        } else {
            msgEl.textContent = cfg.msg;
            msgEl.style.color = '';
        }

        var showBar = (state === 'transcoding' || state === 'downloading') && typeof percent === 'number';
        var showPct = (state === 'transcoding' && typeof percent === 'number') || state === 'ready';
        barWrap.classList.toggle('d-none', !showBar);
        pctEl.classList.toggle('d-none', !showPct);

        if (showBar) {
            barEl.style.width = Math.max(2, percent) + '%';
        }
        if (state === 'ready') {
            pctEl.textContent = '100%';
            barWrap.classList.remove('d-none');
            barEl.style.width = '100%';
            barEl.style.background = '#28a745';
        } else if (state === 'transcoding') {
            pctEl.textContent = (percent || 0) + '%';
        }
    }

    function poll() {
        if (document.hidden) return; // don't burn cycles in background tabs
        fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (!data) return;
                var state = data.transcode_status || (data.is_published ? 'ready' : 'none');
                if (data.hls_ready && state !== 'failed') state = 'ready';

                if (state === 'ready' && data.publish_when_ready === false && data.is_published) {
                    // Auto-published in the background — surface a friendly note.
                    if (hintEl) {
                        hintEl.firstElementChild.className = 'ph ph-check-circle';
                        hintEl.querySelector('span').textContent = 'Published. Subscribers were notified.';
                        hintEl.style.color = '#5cd6a3';
                    }
                }

                applyState(state, data.percent, data.transcode_error);

                // Stop polling on terminal states.
                if (state === 'ready' || state === 'failed' || state === 'none') {
                    if (timer) { clearInterval(timer); timer = null; }
                }
            })
            .catch(function () { /* silent retry on next tick */ });
    }

    // Initial render from the data attribute, then start polling if
    // the state is still moving.
    var initial = root.dataset.status || 'none';
    applyState(initial, null, null);
    if (initial && initial !== 'ready' && initial !== 'none' && initial !== 'failed') {
        poll();
        timer = setInterval(poll, POLL_MS);
    } else if (initial === 'pending') {
        // No job queued yet (admin probably just set the URL but didn't
        // save). Don't poll — but still show the right copy.
    }

    // Resume polling when the tab comes back into focus.
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden && !timer && root.dataset.status !== 'ready') {
            poll();
            timer = setInterval(poll, POLL_MS);
        }
    });
})();
</script>
