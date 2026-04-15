/**
 * Jambo settings menu — YouTube-style nested popover for the minimal-skin
 * player. Call `jamboAttachSettingsMenu(videoId)` once the <video-player>
 * custom element has been upgraded and the inner <video> is addressable.
 *
 * Four panes:
 *   Subtitles/CC     — toggles textTracks on the <video> element
 *   Sleep timer      — wall-clock setTimeout that pauses the video
 *   Playback speed   — sets video.playbackRate
 *   Quality          — for plain MP4 shows only "Auto"; wired to
 *                      player.qualityLevels() semantics for HLS later
 *
 * The menu markup is already in the Blade partial; this script only wires
 * up event handlers + summary labels. That's deliberate: keeping the DOM
 * server-rendered means the first paint already shows the menu, and no
 * JS is needed to construct it — just to make it functional.
 */
(function () {
    if (window.jamboAttachSettingsMenu) return;

    const SPEED_LABEL = (v) => (v === 1 ? 'Normal' : v + '\u00d7');

    window.jamboAttachSettingsMenu = function (videoId) {
        const video = document.getElementById(videoId);
        const popover = document.getElementById(videoId + '-settings-popover');
        const trigger = document.querySelector('.jambo-settings-trigger[data-for="' + videoId + '"]');
        if (!video || !popover || !trigger) return;

        const panes = popover.querySelectorAll('.jambo-settings-pane');
        const summaries = {
            cc: popover.querySelector('[data-summary="cc"]'),
            sleep: popover.querySelector('[data-summary="sleep"]'),
            speed: popover.querySelector('[data-summary="speed"]'),
            quality: popover.querySelector('[data-summary="quality"]'),
        };

        function showPane(name) {
            panes.forEach((p) => {
                p.hidden = (p.dataset.pane !== name);
            });
        }

        function setOpen(open) {
            if (open) {
                popover.hidden = false;
                // Next frame so the hidden→visible transition runs.
                requestAnimationFrame(() => {
                    popover.dataset.open = 'true';
                });
                showPane('main');
            } else {
                popover.dataset.open = 'false';
                // Match the CSS transition duration before actually hiding.
                setTimeout(() => {
                    if (popover.dataset.open === 'false') popover.hidden = true;
                }, 150);
            }
        }

        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            setOpen(popover.hidden || popover.dataset.open === 'false');
        });

        // Outside-click to dismiss — capture-phase so it beats other handlers.
        document.addEventListener('click', (e) => {
            if (popover.hidden) return;
            if (popover.contains(e.target) || trigger.contains(e.target)) return;
            setOpen(false);
        });

        // Drill-down + back navigation inside the popover.
        popover.addEventListener('click', (e) => {
            const back = e.target.closest('[data-back]');
            if (back) {
                e.stopPropagation();
                showPane('main');
                return;
            }
            const drill = e.target.closest('[data-open-pane]');
            if (drill) {
                e.stopPropagation();
                showPane(drill.dataset.openPane);
                return;
            }
            // Option clicks are handled below per-kind.
        });

        /* --------------------------------------------------------------- */
        /* Subtitles / CC                                                  */
        /* --------------------------------------------------------------- */

        const ccPane = popover.querySelector('[data-kind="cc"]');
        function rebuildCcOptions() {
            const tracks = Array.from(video.textTracks || []).filter(
                (t) => t.kind === 'subtitles' || t.kind === 'captions'
            );
            // Off + one row per track.
            let html =
                '<button type="button" class="jambo-settings-row is-active" data-value="off">' +
                    '<span class="jambo-settings-icon"><svg viewBox="0 0 18 18" fill="none"><path d="M4 9l3 3 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>' +
                    '<span class="jambo-settings-label">Off</span>' +
                '</button>';
            tracks.forEach((t, i) => {
                const label = t.label || t.language || ('Track ' + (i + 1));
                html +=
                    '<button type="button" class="jambo-settings-row" data-value="' + i + '">' +
                        '<span class="jambo-settings-icon"><svg viewBox="0 0 18 18" fill="none"><path d="M4 9l3 3 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>' +
                        '<span class="jambo-settings-label">' + escapeHtml(label) + '</span>' +
                    '</button>';
            });
            ccPane.innerHTML = html;

            // Reflect whichever track is currently showing (if any).
            const activeIdx = tracks.findIndex((t) => t.mode === 'showing');
            if (activeIdx >= 0) {
                const btn = ccPane.querySelector('[data-value="' + activeIdx + '"]');
                if (btn) markActive(ccPane, btn);
                summaries.cc.textContent = tracks[activeIdx].label || tracks[activeIdx].language || 'On';
            }
        }

        ccPane.addEventListener('click', (e) => {
            const opt = e.target.closest('button[data-value]');
            if (!opt) return;
            e.stopPropagation();
            const value = opt.dataset.value;
            const tracks = Array.from(video.textTracks || []).filter(
                (t) => t.kind === 'subtitles' || t.kind === 'captions'
            );
            if (value === 'off') {
                tracks.forEach((t) => { t.mode = 'disabled'; });
                summaries.cc.textContent = 'Off';
            } else {
                const idx = parseInt(value, 10);
                tracks.forEach((t, i) => { t.mode = (i === idx ? 'showing' : 'disabled'); });
                summaries.cc.textContent = tracks[idx]?.label || tracks[idx]?.language || 'On';
            }
            markActive(ccPane, opt);
            showPane('main');
        });

        // Tracks can be added async (HLS manifest, remote WebVTT, etc.) so
        // re-render whenever the list changes.
        if (video.textTracks && typeof video.textTracks.addEventListener === 'function') {
            video.textTracks.addEventListener('addtrack', rebuildCcOptions);
            video.textTracks.addEventListener('removetrack', rebuildCcOptions);
        }
        rebuildCcOptions();

        /* --------------------------------------------------------------- */
        /* Sleep timer                                                     */
        /* --------------------------------------------------------------- */

        const sleepPane = popover.querySelector('[data-kind="sleep"]');
        let sleepHandle = null;
        let sleepEndHandler = null;

        function cancelSleep() {
            if (sleepHandle) { clearTimeout(sleepHandle); sleepHandle = null; }
            if (sleepEndHandler) {
                video.removeEventListener('ended', sleepEndHandler);
                sleepEndHandler = null;
            }
        }

        sleepPane.addEventListener('click', (e) => {
            const opt = e.target.closest('button[data-value]');
            if (!opt) return;
            e.stopPropagation();
            const value = opt.dataset.value;
            cancelSleep();

            if (value === '0') {
                summaries.sleep.textContent = 'Off';
            } else if (value === 'end') {
                // "End of video": just let the browser's `ended` fire.
                sleepEndHandler = () => { try { video.pause(); } catch (_) {} };
                video.addEventListener('ended', sleepEndHandler);
                summaries.sleep.textContent = 'End of video';
            } else {
                const minutes = parseInt(value, 10);
                sleepHandle = setTimeout(() => {
                    try { video.pause(); } catch (_) {}
                    summaries.sleep.textContent = 'Off';
                    sleepHandle = null;
                }, minutes * 60 * 1000);
                summaries.sleep.textContent = minutes + (minutes === 60 ? ' min' : ' min');
                if (minutes === 60) summaries.sleep.textContent = '1 hour';
            }
            markActive(sleepPane, opt);
            showPane('main');
        });

        /* --------------------------------------------------------------- */
        /* Playback speed                                                  */
        /* --------------------------------------------------------------- */

        const speedPane = popover.querySelector('[data-kind="speed"]');
        speedPane.addEventListener('click', (e) => {
            const opt = e.target.closest('button[data-value]');
            if (!opt) return;
            e.stopPropagation();
            const rate = parseFloat(opt.dataset.value);
            video.playbackRate = rate;
            summaries.speed.textContent = SPEED_LABEL(rate);
            markActive(speedPane, opt);
            showPane('main');
        });

        /* --------------------------------------------------------------- */
        /* Quality                                                         */
        /* --------------------------------------------------------------- */

        const qualityPane = popover.querySelector('[data-kind="quality"]');

        // Populate from an HLS manifest if the page exposes a hook. This
        // lets the HLS wiring (when added) attach its `qualityLevels` shim
        // to the element without this file knowing about it directly.
        // For plain MP4 the default single "Auto" row stays.
        function setQualityLevels(levels, activeIndex) {
            let html =
                '<button type="button" class="jambo-settings-row ' + (activeIndex == null ? 'is-active' : '') + '" data-value="auto">' +
                    '<span class="jambo-settings-icon"><svg viewBox="0 0 18 18" fill="none"><path d="M4 9l3 3 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>' +
                    '<span class="jambo-settings-label">Auto</span>' +
                '</button>';
            levels.forEach((lvl, i) => {
                const label = lvl.height ? lvl.height + 'p' : (lvl.bitrate ? Math.round(lvl.bitrate / 1000) + 'k' : ('Level ' + (i + 1)));
                const sup = qualitySup(lvl.height);
                html +=
                    '<button type="button" class="jambo-settings-row ' + (i === activeIndex ? 'is-active' : '') + '" data-value="' + i + '">' +
                        '<span class="jambo-settings-icon"><svg viewBox="0 0 18 18" fill="none"><path d="M4 9l3 3 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>' +
                        '<span class="jambo-settings-label">' + escapeHtml(label) + (sup ? ' <span class="jambo-settings-sup">' + sup + '</span>' : '') + '</span>' +
                    '</button>';
            });
            qualityPane.innerHTML = html;
            summaries.quality.textContent = (activeIndex == null) ? 'Auto' : (levels[activeIndex]?.height + 'p' || 'Auto');
        }
        // Expose on the element so HLS wiring can call it.
        video.jamboSetQualityLevels = setQualityLevels;

        qualityPane.addEventListener('click', (e) => {
            const opt = e.target.closest('button[data-value]');
            if (!opt) return;
            e.stopPropagation();
            const value = opt.dataset.value;
            if (typeof video.jamboOnQualityChange === 'function') {
                video.jamboOnQualityChange(value);
            }
            summaries.quality.textContent = opt.querySelector('.jambo-settings-label')?.textContent?.trim() || 'Auto';
            markActive(qualityPane, opt);
            showPane('main');
        });

        /* --------------------------------------------------------------- */
        /* Helpers                                                         */
        /* --------------------------------------------------------------- */

        function markActive(pane, button) {
            pane.querySelectorAll('.jambo-settings-row').forEach((r) => r.classList.remove('is-active'));
            button.classList.add('is-active');
        }

        function qualitySup(height) {
            if (!height) return '';
            if (height >= 2160) return '4K';
            if (height >= 1440) return 'QHD';
            if (height >= 1080) return 'HD';
            if (height >= 720) return 'HD';
            return '';
        }

        function escapeHtml(s) {
            return String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        }
    };
})();
