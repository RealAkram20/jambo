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

    /* Popup menu */
    .jambo-settings-menu {
        position: absolute;
        right: 0;
        bottom: calc(100% + 4px);
        min-width: 200px;
        background: rgba(18, 18, 18, 0.96);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 8px;
        padding: 0.5rem 0.25rem;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.5);
        z-index: 10;
        display: none;
        text-align: left;
        font-size: 13px;
        color: #fff;
    }
    .jambo-settings-menu.open { display: block; }
    .jambo-settings-section + .jambo-settings-section {
        margin-top: 0.5rem;
        padding-top: 0.5rem;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
    }
    .jambo-settings-label {
        color: rgba(255, 255, 255, 0.55);
        text-transform: uppercase;
        font-size: 10px;
        letter-spacing: 0.6px;
        padding: 0 0.5rem 0.25rem;
    }
    .jambo-settings-options {
        display: flex;
        flex-direction: column;
    }
    .jambo-settings-options button {
        background: none;
        border: 0;
        color: #fff;
        text-align: left;
        padding: 0.35rem 0.5rem;
        cursor: pointer;
        border-radius: 4px;
        font-size: 13px;
    }
    .jambo-settings-options button:hover {
        background: rgba(255, 255, 255, 0.08);
    }
    .jambo-settings-options button::before {
        content: ' ';
        display: inline-block;
        width: 1em;
        margin-right: 0.35rem;
    }
    .jambo-settings-options button.active::before {
        content: '✓';
        color: #1A98FF;
    }
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

            const speedHtml = SPEEDS.map(function (s) {
                const cls = s.value === 1 ? 'active' : '';
                return '<button data-value="' + s.value + '" class="' + cls + '">' + s.label + '</button>';
            }).join('');

            menu.innerHTML =
                '<div class="jambo-settings-section">' +
                    '<div class="jambo-settings-label">Quality</div>' +
                    '<div class="jambo-settings-options" data-kind="quality">' +
                        '<button class="active" data-value="auto">Auto</button>' +
                    '</div>' +
                '</div>' +
                '<div class="jambo-settings-section">' +
                    '<div class="jambo-settings-label">Playback speed</div>' +
                    '<div class="jambo-settings-options" data-kind="speed">' + speedHtml + '</div>' +
                '</div>';

            btn.appendChild(menu);

            btn.addEventListener('click', function (e) {
                if (e.target.closest('.jambo-settings-menu')) return;
                e.stopPropagation();
                menu.classList.toggle('open');
            });
            document.addEventListener('click', function () {
                menu.classList.remove('open');
            });

            menu.addEventListener('click', function (e) {
                const opt = e.target.closest('button[data-value]');
                if (!opt) return;
                e.stopPropagation();
                const kind = opt.closest('[data-kind]').dataset.kind;
                const value = opt.dataset.value;

                if (kind === 'speed') {
                    player.playbackRate(parseFloat(value));
                } else if (kind === 'quality' && typeof player.qualityLevels === 'function') {
                    const levels = player.qualityLevels();
                    if (value === 'auto') {
                        for (let i = 0; i < levels.length; i++) levels[i].enabled = true;
                    } else {
                        const idx = parseInt(value, 10);
                        for (let i = 0; i < levels.length; i++) levels[i].enabled = (i === idx);
                    }
                }

                opt.parentElement.querySelectorAll('button').forEach(function (b) { b.classList.remove('active'); });
                opt.classList.add('active');
                menu.classList.remove('open');
            });

            // Quality levels: populate from the HLS plugin when present.
            // For plain MP4 / YouTube sources the plugin isn't registered,
            // so "Auto" stays as the only option — which is accurate.
            if (typeof player.qualityLevels === 'function') {
                const levels = player.qualityLevels();
                const container = menu.querySelector('[data-kind="quality"]');
                const refresh = function () {
                    let html = '<button class="active" data-value="auto">Auto</button>';
                    Array.from(levels).forEach(function (level, i) {
                        const label = level.height
                            ? level.height + 'p'
                            : (level.bitrate ? Math.round(level.bitrate / 1000) + 'k' : ('Level ' + (i + 1)));
                        html += '<button data-value="' + i + '">' + label + '</button>';
                    });
                    container.innerHTML = html;
                };
                levels.on('addqualitylevel', refresh);
            }
        });
    };
})();
</script>
