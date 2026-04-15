{{-- Shared player extras: rounded-corner styling + a gear-icon settings
     menu that exposes Quality and Playback speed. Include once per page;
     then call `jamboAttachSettingsMenu(player)` after videojs(...) for
     each player instance you want the menu on. --}}
<style>
    /* Rounded corners, videojs.org-homepage look. */
    .jambo-player-slot,
    .jambo-inline-wrap {
        border-radius: 12px;
    }
    .jambo-inline-wrap .video-js,
    .jambo-inline-wrap .video-js .vjs-poster {
        border-radius: 12px;
    }
    .jambo-inline-wrap .video-js .vjs-control-bar {
        border-bottom-left-radius: 12px;
        border-bottom-right-radius: 12px;
    }

    /* Custom gear button — replaces the vanilla `1×` playback-rate btn. */
    .video-js .vjs-jambo-settings {
        cursor: pointer;
        position: relative;
        width: 3em;
    }
    .video-js .vjs-jambo-settings .vjs-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
    }
    .video-js .vjs-jambo-settings svg {
        width: 20px;
        height: 20px;
    }

    /* Popup menu — YouTube-style single-column with nested panes.
       Only one pane is visible at a time: the "main" list, or one of
       the drill-down panes (quality / speed). */
    .jambo-settings-menu {
        position: absolute;
        right: 0;
        bottom: calc(100% + 4px);
        min-width: 240px;
        max-width: 280px;
        background: rgba(18, 18, 18, 0.96);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 8px;
        padding: 0.35rem 0;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.5);
        z-index: 10;
        display: none;
        text-align: left;
        font-size: 13px;
        color: #fff;
        overflow: hidden;
    }
    .jambo-settings-menu.open { display: block; }
    .jambo-settings-pane[hidden] { display: none; }

    /* Row used by both the main list and the drill-down options. */
    .jambo-settings-row {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        width: 100%;
        background: none;
        border: 0;
        color: #fff;
        text-align: left;
        padding: 0.55rem 0.75rem;
        font-size: 13px;
        cursor: pointer;
    }
    .jambo-settings-row:hover { background: rgba(255, 255, 255, 0.08); }
    .jambo-settings-row .label { flex: 1; }
    .jambo-settings-row .value {
        color: rgba(255, 255, 255, 0.6);
        font-size: 12px;
    }
    .jambo-settings-row .chev {
        color: rgba(255, 255, 255, 0.45);
        font-size: 14px;
        line-height: 1;
    }

    /* Drill-down pane header — click to return to main list. */
    .jambo-settings-back {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        width: 100%;
        background: none;
        border: 0;
        color: #fff;
        padding: 0.55rem 0.75rem;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }
    .jambo-settings-back:hover { background: rgba(255, 255, 255, 0.06); }
    .jambo-settings-back .chev { color: rgba(255, 255, 255, 0.7); font-size: 14px; }

    .jambo-settings-options { display: flex; flex-direction: column; }
    .jambo-settings-options .jambo-settings-row::before {
        content: ' ';
        display: inline-block;
        width: 1em;
        color: #1A98FF;
    }
    .jambo-settings-options .jambo-settings-row.active::before { content: '✓'; }
</style>

<script>
(function () {
    if (window.jamboAttachSettingsMenu) return;

    const SETTINGS_ICON_SVG = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.14,12.94c0.04-0.3,0.06-0.61,0.06-0.94c0-0.32-0.02-0.64-0.07-0.94l2.03-1.58c0.18-0.14,0.23-0.41,0.12-0.61 l-1.92-3.32c-0.12-0.22-0.37-0.29-0.59-0.22l-2.39,0.96c-0.5-0.38-1.03-0.7-1.62-0.94L14.4,2.81c-0.04-0.24-0.24-0.41-0.48-0.41 h-3.84c-0.24,0-0.43,0.17-0.47,0.41L9.25,5.35C8.66,5.59,8.12,5.92,7.63,6.29L5.24,5.33c-0.22-0.08-0.47,0-0.59,0.22L2.74,8.87 C2.62,9.08,2.66,9.34,2.86,9.48l2.03,1.58C4.84,11.36,4.8,11.69,4.8,12s0.02,0.64,0.07,0.94l-2.03,1.58 c-0.18,0.14-0.23,0.41-0.12,0.61l1.92,3.32c0.12,0.22,0.37,0.29,0.59,0.22l2.39-0.96c0.5,0.38,1.03,0.7,1.62,0.94l0.36,2.54 c0.05,0.24,0.24,0.41,0.48,0.41h3.84c0.24,0,0.44-0.17,0.47-0.41l0.36-2.54c0.59-0.24,1.13-0.56,1.62-0.94l2.39,0.96 c0.22,0.08,0.47,0,0.59-0.22l1.92-3.32c0.12-0.22,0.07-0.47-0.12-0.61L19.14,12.94z M12,15.6c-1.98,0-3.6-1.62-3.6-3.6 s1.62-3.6,3.6-3.6s3.6,1.62,3.6,3.6S13.98,15.6,12,15.6z"/></svg>';

    const SPEEDS = [
        { value: 0.5, label: '0.5×' },
        { value: 0.75, label: '0.75×' },
        { value: 1, label: 'Normal' },
        { value: 1.25, label: '1.25×' },
        { value: 1.5, label: '1.5×' },
        { value: 2, label: '2×' },
    ];

    window.jamboAttachSettingsMenu = function (player) {
        player.ready(function () {
            const controlBar = player.controlBar && player.controlBar.el_;
            if (!controlBar) return;

            // Hide the default playback-rate control since we replace it.
            const rateBtn = controlBar.querySelector('.vjs-playback-rate');
            if (rateBtn) rateBtn.style.display = 'none';

            const btn = document.createElement('button');
            btn.className = 'vjs-control vjs-button vjs-jambo-settings';
            btn.type = 'button';
            btn.title = 'Settings';
            btn.innerHTML = '<span aria-hidden="true" class="vjs-icon">' + SETTINGS_ICON_SVG + '</span><span class="vjs-control-text">Settings</span>';

            const fsBtn = controlBar.querySelector('.vjs-fullscreen-control');
            if (fsBtn) controlBar.insertBefore(btn, fsBtn);
            else controlBar.appendChild(btn);

            const menu = document.createElement('div');
            menu.className = 'jambo-settings-menu';
            menu.innerHTML =
                '<div class="jambo-settings-pane" data-pane="main">' +
                    '<button type="button" class="jambo-settings-row" data-open="quality">' +
                        '<span class="label">Quality</span>' +
                        '<span class="value" data-summary="quality">Auto</span>' +
                        '<span class="chev">›</span>' +
                    '</button>' +
                    '<button type="button" class="jambo-settings-row" data-open="speed">' +
                        '<span class="label">Playback speed</span>' +
                        '<span class="value" data-summary="speed">Normal</span>' +
                        '<span class="chev">›</span>' +
                    '</button>' +
                '</div>' +
                '<div class="jambo-settings-pane" data-pane="quality" hidden>' +
                    '<button type="button" class="jambo-settings-back" data-back>' +
                        '<span class="chev">‹</span><span>Quality</span>' +
                    '</button>' +
                    '<div class="jambo-settings-options" data-kind="quality">' +
                        '<button type="button" class="jambo-settings-row active" data-value="auto">Auto</button>' +
                    '</div>' +
                '</div>' +
                '<div class="jambo-settings-pane" data-pane="speed" hidden>' +
                    '<button type="button" class="jambo-settings-back" data-back>' +
                        '<span class="chev">‹</span><span>Playback speed</span>' +
                    '</button>' +
                    '<div class="jambo-settings-options" data-kind="speed">' +
                        SPEEDS.map(function (s) {
                            const cls = s.value === 1 ? ' active' : '';
                            return '<button type="button" class="jambo-settings-row' + cls + '" data-value="' + s.value + '">' + s.label + '</button>';
                        }).join('') +
                    '</div>' +
                '</div>';

            btn.appendChild(menu);

            const panes = menu.querySelectorAll('.jambo-settings-pane');
            function showPane(name) {
                panes.forEach(function (p) {
                    p.hidden = (p.dataset.pane !== name);
                });
            }

            btn.addEventListener('click', function (e) {
                if (e.target.closest('.jambo-settings-menu')) return;
                e.stopPropagation();
                const willOpen = !menu.classList.contains('open');
                menu.classList.toggle('open');
                if (willOpen) showPane('main');
            });
            document.addEventListener('click', function () {
                menu.classList.remove('open');
            });

            menu.addEventListener('click', function (e) {
                e.stopPropagation();

                const back = e.target.closest('[data-back]');
                if (back) { showPane('main'); return; }

                const drill = e.target.closest('[data-open]');
                if (drill) { showPane(drill.dataset.open); return; }

                const opt = e.target.closest('button[data-value]');
                if (!opt) return;

                const kind = opt.closest('[data-kind]').dataset.kind;
                const value = opt.dataset.value;

                if (kind === 'speed') {
                    player.playbackRate(parseFloat(value));
                    menu.querySelector('[data-summary="speed"]').textContent = opt.textContent;
                } else if (kind === 'quality' && typeof player.qualityLevels === 'function') {
                    const levels = player.qualityLevels();
                    if (value === 'auto') {
                        for (let i = 0; i < levels.length; i++) levels[i].enabled = true;
                    } else {
                        const idx = parseInt(value, 10);
                        for (let i = 0; i < levels.length; i++) levels[i].enabled = (i === idx);
                    }
                    menu.querySelector('[data-summary="quality"]').textContent = opt.textContent;
                }

                opt.parentElement.querySelectorAll('button').forEach(function (b) { b.classList.remove('active'); });
                opt.classList.add('active');
                showPane('main');
            });

            // Quality levels: populate from HLS plugin when present.
            // For plain MP4 / YouTube sources nothing registers and the
            // Quality pane keeps its single "Auto" row.
            if (typeof player.qualityLevels === 'function') {
                const levels = player.qualityLevels();
                const container = menu.querySelector('[data-kind="quality"]');
                const refresh = function () {
                    let html = '<button type="button" class="jambo-settings-row active" data-value="auto">Auto</button>';
                    Array.from(levels).forEach(function (level, i) {
                        const label = level.height
                            ? level.height + 'p'
                            : (level.bitrate ? Math.round(level.bitrate / 1000) + 'k' : ('Level ' + (i + 1)));
                        html += '<button type="button" class="jambo-settings-row" data-value="' + i + '">' + label + '</button>';
                    });
                    container.innerHTML = html;
                };
                levels.on('addqualitylevel', refresh);
            }
        });
    };
})();
</script>
