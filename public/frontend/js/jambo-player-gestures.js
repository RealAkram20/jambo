/**
 * Jambo player gestures + keyboard shortcuts.
 *
 * Call `jamboAttachGestures(videoId)` after the <video> element is ready.
 *
 * Gestures (touch & click):
 *   Double-tap left   → rewind 10s
 *   Double-tap right  → forward 10s
 *   Single-tap center → play / pause
 *
 * Keyboard shortcuts (when player is focused or page-wide):
 *   Space / K         → play / pause
 *   F                 → fullscreen
 *   M                 → mute / unmute
 *   ← Left arrow      → rewind 10s
 *   → Right arrow     → forward 10s
 *   J                 → rewind 30s
 *   L                 → forward 30s
 *   ↑ Up arrow        → volume up 10%
 *   ↓ Down arrow      → volume down 10%
 *   0-9               → seek to 0%-90%
 */
(function () {
    if (window.jamboAttachGestures) return;

    // SVG icons for feedback overlay.
    var ICONS = {
        play: '<svg viewBox="0 0 48 48" fill="none"><path d="M16 10v28l22-14z" fill="currentColor"/></svg>',
        pause: '<svg viewBox="0 0 48 48" fill="none"><rect x="12" y="10" width="8" height="28" rx="2" fill="currentColor"/><rect x="28" y="10" width="8" height="28" rx="2" fill="currentColor"/></svg>',
        rewind: '<svg viewBox="0 0 48 48" fill="none"><path d="M24 14l-14 10 14 10V14z" fill="currentColor"/><path d="M40 14l-14 10 14 10V14z" fill="currentColor"/></svg>',
        forward: '<svg viewBox="0 0 48 48" fill="none"><path d="M8 14l14 10-14 10V14z" fill="currentColor"/><path d="M24 14l14 10-14 10V14z" fill="currentColor"/></svg>',
        volumeUp: '<svg viewBox="0 0 48 48" fill="none"><path d="M8 18h6l10-8v28l-10-8H8a2 2 0 01-2-2v-8a2 2 0 012-2z" fill="currentColor"/><path d="M32 16c2.8 2.7 4 6 4 8s-1.2 5.3-4 8" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>',
        volumeDown: '<svg viewBox="0 0 48 48" fill="none"><path d="M8 18h6l10-8v28l-10-8H8a2 2 0 01-2-2v-8a2 2 0 012-2z" fill="currentColor"/></svg>',
        mute: '<svg viewBox="0 0 48 48" fill="none"><path d="M8 18h6l10-8v28l-10-8H8a2 2 0 01-2-2v-8a2 2 0 012-2z" fill="currentColor"/><path d="M34 20l8 8m0-8l-8 8" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>',
        fullscreen: '<svg viewBox="0 0 48 48" fill="none"><path d="M8 16V8h8M40 16V8h-8M8 32v8h8M40 32v8h-8" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    };

    window.jamboAttachGestures = function (videoId) {
        var video = document.getElementById(videoId);
        if (!video) return;

        var container = video.closest('media-container') || video.parentElement;
        var feedback = document.getElementById(videoId + '-gesture-feedback');

        // ---- Feedback overlay ----
        var feedbackTimer = null;
        function showFeedback(iconKey, text) {
            if (!feedback) return;
            var icon = ICONS[iconKey] || '';
            feedback.innerHTML =
                '<div class="jambo-gesture-icon">' + icon + '</div>' +
                (text ? '<div class="jambo-gesture-text">' + text + '</div>' : '');
            feedback.classList.remove('jambo-feedback-show');
            // Force reflow so animation restarts.
            void feedback.offsetWidth;
            feedback.classList.add('jambo-feedback-show');
            clearTimeout(feedbackTimer);
            feedbackTimer = setTimeout(function () {
                feedback.classList.remove('jambo-feedback-show');
            }, 700);
        }

        // ---- Ripple on gesture zones ----
        function ripple(zone) {
            zone.classList.remove('jambo-ripple');
            void zone.offsetWidth;
            zone.classList.add('jambo-ripple');
            setTimeout(function () { zone.classList.remove('jambo-ripple'); }, 600);
        }

        // ---- Core actions ----
        function togglePlay() {
            if (video.paused) {
                var p = video.play();
                if (p && typeof p.catch === 'function') p.catch(function () {});
                showFeedback('play');
            } else {
                video.pause();
                showFeedback('pause');
            }
        }

        function seek(seconds) {
            video.currentTime = Math.max(0, Math.min(video.duration || Infinity, video.currentTime + seconds));
            if (seconds < 0) {
                showFeedback('rewind', Math.abs(seconds) + 's');
            } else {
                showFeedback('forward', seconds + 's');
            }
        }

        function adjustVolume(delta) {
            video.muted = false;
            video.volume = Math.max(0, Math.min(1, video.volume + delta));
            var pct = Math.round(video.volume * 100);
            showFeedback(delta > 0 ? 'volumeUp' : 'volumeDown', pct + '%');
        }

        function toggleMute() {
            video.muted = !video.muted;
            showFeedback(video.muted ? 'mute' : 'volumeUp', video.muted ? 'Muted' : Math.round(video.volume * 100) + '%');
        }

        // Use the browser's `document.fullscreenElement` as the single
        // source of truth. Media Chrome's internal tracking can drift
        // from reality (it watches one element; our F shortcut and
        // gesture handlers can fullscreen another), and once the two
        // diverge the button click ends up sending the wrong request.
        // Doing the toggle ourselves removes that whole drift surface.
        var fullscreenTarget = container.closest('video-player') || container;

        function toggleFullscreen() {
            if (document.fullscreenElement) {
                if (document.exitFullscreen) document.exitFullscreen().catch(function () {});
            } else if (fullscreenTarget.requestFullscreen) {
                fullscreenTarget.requestFullscreen().catch(function () {});
            }
            showFeedback('fullscreen');
        }

        // Hijack the Media Chrome fullscreen button so its click also
        // routes through toggleFullscreen, which means click-and-key
        // share an identical code path with the same target element.
        // Capture phase + stopImmediatePropagation prevents Media
        // Chrome's own click handler from firing afterwards and
        // double-toggling.
        var fsBtn = container && container.querySelector('media-fullscreen-button');
        if (fsBtn) {
            fsBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                toggleFullscreen();
            }, true);
        }

        // Keep the button's `mediaisfullscreen` attribute in sync with
        // reality so its enter/exit icon flips correctly regardless of
        // how the state changed (F key, gesture, browser ESC, the OS
        // fullscreen tray, etc.). Without this nudge the icon can drift
        // because Media Chrome only updates from its own controller's
        // state events.
        document.addEventListener('fullscreenchange', function () {
            if (!fsBtn) return;
            if (document.fullscreenElement) {
                fsBtn.setAttribute('mediaisfullscreen', '');
            } else {
                fsBtn.removeAttribute('mediaisfullscreen');
            }
        });

        function seekPercent(pct) {
            if (video.duration && isFinite(video.duration)) {
                video.currentTime = video.duration * (pct / 100);
                showFeedback('forward', pct + '%');
            }
        }

        // Navigate to the sibling episode by "clicking" the corresponding
        // prev/next button in the control bar. We piggyback on the <a> so
        // the navigation path (and any page leave handlers like the final
        // heartbeat) stays identical to a mouse click.
        function goToEpisode(which) {
            var sel = '[data-episode-nav="' + which + '"]';
            var btn = (container && container.querySelector(sel)) || document.querySelector(sel);
            if (btn && !btn.classList.contains('is-disabled') && !btn.disabled) {
                btn.click();
            }
        }

        // ---- Double-tap gesture zones ----
        var zones = container.querySelectorAll('.jambo-gesture-zone');
        zones.forEach(function (zone) {
            var tapCount = 0;
            var tapTimer = null;
            var gesture = zone.dataset.gesture; // 'rewind' or 'forward'

            zone.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                tapCount++;

                if (tapCount === 1) {
                    // Wait to see if it's a double-tap.
                    tapTimer = setTimeout(function () {
                        // Single tap on side zone → play/pause.
                        tapCount = 0;
                        togglePlay();
                    }, 300);
                } else if (tapCount === 2) {
                    // Double tap → seek.
                    clearTimeout(tapTimer);
                    tapCount = 0;
                    ripple(zone);
                    if (gesture === 'rewind') {
                        seek(-10);
                    } else {
                        seek(10);
                    }
                }
            });
        });

        // Single tap on the center area (the video element itself) → play/pause.
        // Only when not clicking a control or gesture zone.
        video.addEventListener('click', function (e) {
            // Don't interfere with controls.
            if (e.target.closest('.media-controls') || e.target.closest('.jambo-settings-popover')) return;
            togglePlay();
        });

        // ---- Keyboard shortcuts ----
        document.addEventListener('keydown', function (e) {
            // Don't capture when typing in an input/textarea.
            var tag = (e.target.tagName || '').toLowerCase();
            if (tag === 'input' || tag === 'textarea' || tag === 'select' || e.target.isContentEditable) return;

            var handled = true;
            switch (e.key) {
                case ' ':
                case 'k':
                case 'K':
                    togglePlay();
                    break;
                case 'f':
                case 'F':
                    toggleFullscreen();
                    break;
                case 'm':
                case 'M':
                    toggleMute();
                    break;
                case 'ArrowLeft':
                    seek(-10);
                    break;
                case 'ArrowRight':
                    seek(10);
                    break;
                case 'j':
                case 'J':
                    seek(-30);
                    break;
                case 'l':
                case 'L':
                    seek(30);
                    break;
                case 'ArrowUp':
                    adjustVolume(0.1);
                    break;
                case 'ArrowDown':
                    adjustVolume(-0.1);
                    break;
                case '0': seekPercent(0); break;
                case '1': seekPercent(10); break;
                case '2': seekPercent(20); break;
                case '3': seekPercent(30); break;
                case '4': seekPercent(40); break;
                case '5': seekPercent(50); break;
                case '6': seekPercent(60); break;
                case '7': seekPercent(70); break;
                case '8': seekPercent(80); break;
                case '9': seekPercent(90); break;
                case 'N': // Shift+N — next episode (YouTube convention)
                    if (e.shiftKey) goToEpisode('next'); else handled = false;
                    break;
                case 'P': // Shift+P — previous episode
                    if (e.shiftKey) goToEpisode('prev'); else handled = false;
                    break;
                default:
                    handled = false;
            }

            if (handled) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
    };
})();
