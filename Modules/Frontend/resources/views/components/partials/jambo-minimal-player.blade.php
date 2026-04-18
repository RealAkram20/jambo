{{--
    Shared <video-player> skeleton using @videojs/html's minimal skin.
    Expects the caller to define these variables before @including:
      $playerSrc    : URL to the video file / HLS master
      $playerSrcLow : URL to the low-quality (Data Saver) version (nullable)
      $playerPoster : poster image URL (nullable)
      $playerId     : DOM id on the inner <video>, so JS can wire heartbeat/etc.

    Episode-only extras (pass from episode-page.blade.php):
      $isSeries          : true to render prev/next buttons and autoplay row
      $prevEpisodeUrl    : URL to the previous episode (nullable → disabled)
      $nextEpisodeUrl    : URL to the next episode     (nullable → disabled)
      $prevEpisodeLabel  : "S01E03 — Title" tooltip label
      $nextEpisodeLabel  : "S01E05 — Title" tooltip label

    The `<script type="module" src=".../video-minimal-ui.js">` import that
    upgrades these custom elements is loaded once on the page itself, not
    here — this partial is pure markup.
--}}
@php
    $isSeries         = $isSeries         ?? false;
    $prevEpisodeUrl   = $prevEpisodeUrl   ?? null;
    $nextEpisodeUrl   = $nextEpisodeUrl   ?? null;
    $prevEpisodeLabel = $prevEpisodeLabel ?? null;
    $nextEpisodeLabel = $nextEpisodeLabel ?? null;

    // Queue nav (e.g. /watchlist movie queue). Renders prev/next
    // buttons in the same positions as episode nav, but without the
    // autoplay-next switch that's series-specific.
    $showPrevNext     = $showPrevNext     ?? $isSeries;
    $prevContentUrl   = $prevContentUrl   ?? $prevEpisodeUrl;
    $nextContentUrl   = $nextContentUrl   ?? $nextEpisodeUrl;
    $prevContentLabel = $prevContentLabel ?? $prevEpisodeLabel;
    $nextContentLabel = $nextContentLabel ?? $nextEpisodeLabel;

    // Hide the ±10s seek buttons when there's a prev/next nav — the
    // left/right touch-gestures and arrow keys still perform ±10s.
    $hideSeekButtons  = $hideSeekButtons  ?? false;

    // Optional: hide the PIP control (used on the watchlist queue
    // player so the autoplay-next pill has room in the action row).
    $hidePipButton    = $hidePipButton    ?? false;

    // Autoplay-next pill — defaults to $isSeries (episode-only) for
    // back-compat. Opt in on other queue-style pages.
    $showAutoplayNext = $showAutoplayNext ?? $isSeries;
@endphp
<video-player>
    <media-container class="media-minimal-skin media-minimal-skin--video">
        <video id="{{ $playerId }}"
            data-src-default="{{ $playerSrc }}"
            @if (!empty($playerSrcLow)) data-src-low="{{ $playerSrcLow }}" @endif
            @if (!empty($resumePosition)) data-resume="{{ $resumePosition }}" @endif
            playsinline></video>
        {{-- Set src + preload immediately before the browser starts buffering.
             Data Saver ON  → preload="metadata" (only download what you watch)
             Data Saver OFF → preload="auto" (buffer ahead for smooth playback)
             Quality: if a low-quality URL exists and is selected, use it. --}}
        <script>
        (function(){
            var v = document.getElementById('{{ $playerId }}');
            if (!v) return;
            var ds = localStorage.getItem('jambo.dataSaver') === '1';
            var quality = localStorage.getItem('jambo.quality') || 'default';
            var low = v.dataset.srcLow;
            var resume = parseInt(v.dataset.resume || '0', 10);

            // Pick source based on quality preference.
            v.src = (quality === 'low' && low) ? low : v.dataset.srcDefault;

            // Data Saver: minimize buffering.
            v.preload = ds ? 'metadata' : 'auto';

            // Resume from last position once video metadata loads.
            if (resume > 0) {
                v.addEventListener('loadedmetadata', function onMeta() {
                    v.removeEventListener('loadedmetadata', onMeta);
                    // Don't resume if near the end (within 30s).
                    if (v.duration && resume < v.duration - 30) {
                        v.currentTime = resume;
                    }
                });
            }

            // --------------------------------------------------------
            // Transient error recovery. Demo hosts (archive.org, etc.)
            // occasionally 503 under load or mangle range requests,
            // which surfaces as a MediaError with code 3 (decode) or
            // 4 (src not supported). Most of these recover on a
            // second try, so we auto-retry once with a cache-buster
            // before handing off to the <media-error-dialog>.
            // --------------------------------------------------------
            var retryCount = 0;
            var MAX_RETRIES = 1;

            v.addEventListener('error', function(){
                var err = v.error;
                if (!err) return;
                // Codes: 1 aborted, 2 network, 3 decode, 4 src not supported.
                // We retry on decode / src / network — not on manual abort.
                if (err.code === 1) return;
                if (retryCount >= MAX_RETRIES) return;
                retryCount++;
                console.warn('[jambo-player] error code=' + err.code + ' (' + (err.message || '') + '), retry ' + retryCount);
                setTimeout(function(){
                    var q = localStorage.getItem('jambo.quality') || 'default';
                    var base = (q === 'low' && v.dataset.srcLow) ? v.dataset.srcLow : v.dataset.srcDefault;
                    if (!base) return;
                    // Cache-buster forces a fresh fetch so we don't
                    // replay a poisoned 206 response from earlier.
                    var sep = base.indexOf('?') === -1 ? '?' : '&';
                    v.src = base + sep + '_retry=' + Date.now();
                    v.load();
                    var p = v.play();
                    if (p && typeof p.catch === 'function') p.catch(function(){});
                }, 800);
            });

            // Reset the retry budget on successful playback so a later
            // unrelated blip can also get a second chance.
            v.addEventListener('playing', function(){ retryCount = 0; });
        })();
        </script>

        @if (!empty($playerPoster))
            <media-poster>
                <img src="{{ $playerPoster }}" alt="">
            </media-poster>
        @endif

        <media-buffering-indicator class="media-buffering-indicator">
            <svg class="media-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" aria-hidden="true" viewBox="0 0 18 18"><rect width="2" height="5" x="8" y=".5" opacity=".5" rx="1"><animate attributeName="opacity" begin="0s" calcMode="linear" dur="1s" repeatCount="indefinite" values="1;0"/></rect><rect width="2" height="5" x="12.243" y="2.257" opacity=".45" rx="1" transform="rotate(45 13.243 4.757)"><animate attributeName="opacity" begin="0.125s" calcMode="linear" dur="1s" repeatCount="indefinite" values="1;0"/></rect><rect width="5" height="2" x="12.5" y="8" opacity=".4" rx="1"><animate attributeName="opacity" begin="0.25s" calcMode="linear" dur="1s" repeatCount="indefinite" values="1;0"/></rect><rect width="5" height="2" x="10.743" y="12.243" opacity=".35" rx="1" transform="rotate(45 13.243 13.243)"><animate attributeName="opacity" begin="0.375s" calcMode="linear" dur="1s" repeatCount="indefinite" values="1;0"/></rect><rect width="2" height="5" x="8" y="12.5" opacity=".3" rx="1"><animate attributeName="opacity" begin="0.5s" calcMode="linear" dur="1s" repeatCount="indefinite" values="1;0"/></rect><rect width="2" height="5" x="3.757" y="10.743" opacity=".25" rx="1" transform="rotate(45 4.757 13.243)"><animate attributeName="opacity" begin="0.625s" calcMode="linear" dur="1s" repeatCount="indefinite" values="1;0"/></rect><rect width="5" height="2" x=".5" y="8" opacity=".15" rx="1"><animate attributeName="opacity" begin="0.75s" calcMode="linear" dur="1s" repeatCount="indefinite" values="1;0"/></rect><rect width="5" height="2" x="2.257" y="3.757" opacity=".1" rx="1" transform="rotate(45 4.757 4.757)"><animate attributeName="opacity" begin="0.875s" calcMode="linear" dur="1s" repeatCount="indefinite" values="1;0"/></rect></svg>
        </media-buffering-indicator>

        {{-- Mobile-only center overlay: YouTube-style big prev/play/next
             tap targets. Hidden on desktop via CSS. Media Chrome allows
             multiple <media-play-button> under the same <media-container>
             — they all share state, so this copy works transparently.
             The central button is the largest (60-64px); flanking seek
             buttons are 48-52px. Placed OUTSIDE media-controls so it's
             always anchored to the player center, independent of the
             bottom control-bar slide-up/fade transitions. --}}
        <div class="jambo-mobile-center" aria-hidden="false">
            @if ($hideSeekButtons && $showPrevNext && $prevContentUrl)
                {{-- On the watchlist/movie queue we swap the ±10s buttons
                     for Prev/Next movie on mobile too. Left/right
                     touch-gestures still seek ±10s (see player-gestures.js). --}}
                <a href="{{ $prevContentUrl }}" class="jambo-mobile-center__btn jambo-mobile-center__btn--seek" data-episode-nav="prev" aria-label="Previous">
                    <svg viewBox="0 0 24 24" fill="currentColor" width="28" height="28" aria-hidden="true"><path d="M6 6h2v12H6zm3.5 6 8.5 6V6z"/></svg>
                </a>
            @elseif (!$hideSeekButtons)
                <media-seek-button seconds="-10" class="jambo-mobile-center__btn jambo-mobile-center__btn--seek">
                    <svg viewBox="0 0 24 24" fill="currentColor" width="28" height="28" aria-hidden="true"><path d="M11.99 5V1l-5 5 5 5V7c3.31 0 6 2.69 6 6s-2.69 6-6 6-6-2.69-6-6h-2c0 4.42 3.58 8 8 8s8-3.58 8-8-3.58-8-8-8zm-1.1 11H10v-3.28L9 13v-.8l1.91-.7h.08V16z"/></svg>
                </media-seek-button>
            @elseif ($hideSeekButtons && $showPrevNext)
                <button type="button" class="jambo-mobile-center__btn jambo-mobile-center__btn--seek is-disabled" disabled aria-label="No previous item">
                    <svg viewBox="0 0 24 24" fill="currentColor" width="28" height="28" aria-hidden="true" style="opacity:.4"><path d="M6 6h2v12H6zm3.5 6 8.5 6V6z"/></svg>
                </button>
            @endif
            <media-play-button class="jambo-mobile-center__btn jambo-mobile-center__btn--play">
                <svg class="media-icon--play" viewBox="0 0 24 24" fill="currentColor" width="40" height="40" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                <svg class="media-icon--pause" viewBox="0 0 24 24" fill="currentColor" width="40" height="40" aria-hidden="true"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
            </media-play-button>
            @if ($hideSeekButtons && $showPrevNext && $nextContentUrl)
                <a href="{{ $nextContentUrl }}" class="jambo-mobile-center__btn jambo-mobile-center__btn--seek" data-episode-nav="next" aria-label="Next">
                    <svg viewBox="0 0 24 24" fill="currentColor" width="28" height="28" aria-hidden="true"><path d="M6 18V6l8.5 6zm10 0h2V6h-2z"/></svg>
                </a>
            @elseif (!$hideSeekButtons)
                <media-seek-button seconds="10" class="jambo-mobile-center__btn jambo-mobile-center__btn--seek">
                    <svg viewBox="0 0 24 24" fill="currentColor" width="28" height="28" aria-hidden="true"><path d="M12 5V1L7 6l5 5V7c3.31 0 6 2.69 6 6s-2.69 6-6 6-6-2.69-6-6H4c0 4.42 3.58 8 8 8s8-3.58 8-8-3.58-8-8-8zm-1.1 11H10v-3.28L9 13v-.8l1.91-.7h.08V16z" transform="matrix(-1 0 0 1 24 0)"/></svg>
                </media-seek-button>
            @elseif ($hideSeekButtons && $showPrevNext)
                <button type="button" class="jambo-mobile-center__btn jambo-mobile-center__btn--seek is-disabled" disabled aria-label="No next item">
                    <svg viewBox="0 0 24 24" fill="currentColor" width="28" height="28" aria-hidden="true" style="opacity:.4"><path d="M6 18V6l8.5 6zm10 0h2V6h-2z"/></svg>
                </button>
            @endif
        </div>

        <media-error-dialog class="media-error">
            <div class="media-error__dialog">
                <div class="media-error__content">
                    <media-alert-dialog-title class="media-error__title">Unable to play this video</media-alert-dialog-title>
                    <media-alert-dialog-description class="media-error__description">This video format may not be supported by your browser. For best results, use H.264 MP4 files.</media-alert-dialog-description>
                </div>
                <div class="media-error__actions">
                    <media-alert-dialog-close class="media-button media-button--primary">OK</media-alert-dialog-close>
                </div>
            </div>
        </media-error-dialog>

        <media-controls class="media-controls">
            <media-tooltip-group>
                <div class="media-button-group">
                    @if ($showPrevNext)
                        {{-- Previous item in the queue (episode or movie).
                             Rendered even when unavailable so the button
                             layout stays stable — just marked disabled.
                             Keyboard Shift+P and the gesture handler click
                             `[data-episode-nav="prev"]` so that attribute
                             is reused for both episode and movie queues. --}}
                        @if ($prevContentUrl)
                            <a href="{{ $prevContentUrl }}"
                               class="media-button media-button--subtle media-button--icon jambo-episode-button jambo-episode-button--prev"
                               data-episode-nav="prev"
                               aria-label="{{ $isSeries ? 'Previous episode' : 'Previous' }}"
                               title="{{ $isSeries ? 'Previous episode' : 'Previous' }}"
                               commandfor="{{ $playerId }}-prev-ep-tooltip">
                                <svg class="media-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true"><path fill="currentColor" d="M5.25 3a.75.75 0 0 1 .75.75v4.157l7.835-4.672A.75.75 0 0 1 15 3.879v10.242a.75.75 0 0 1-1.165.644L6 10.093v4.157a.75.75 0 0 1-1.5 0V3.75A.75.75 0 0 1 5.25 3"/></svg>
                            </a>
                            <media-tooltip id="{{ $playerId }}-prev-ep-tooltip" side="top" class="media-tooltip">Previous · {{ $prevContentLabel }}</media-tooltip>
                        @else
                            <button type="button"
                                    class="media-button media-button--subtle media-button--icon jambo-episode-button jambo-episode-button--prev is-disabled"
                                    disabled
                                    aria-label="Previous (unavailable)"
                                    title="{{ $isSeries ? 'No previous episode' : 'No previous item' }}">
                                <svg class="media-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true"><path fill="currentColor" d="M5.25 3a.75.75 0 0 1 .75.75v4.157l7.835-4.672A.75.75 0 0 1 15 3.879v10.242a.75.75 0 0 1-1.165.644L6 10.093v4.157a.75.75 0 0 1-1.5 0V3.75A.75.75 0 0 1 5.25 3"/></svg>
                            </button>
                        @endif
                    @endif
                    <media-play-button commandfor="{{ $playerId }}-play-tooltip" class="media-button media-button--subtle media-button--icon media-button--play">
                        <svg class="media-icon media-icon--restart" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" aria-hidden="true" viewBox="0 0 18 18"><path fill="currentColor" d="M9 17a8 8 0 0 1-8-8h1.5a6.5 6.5 0 1 0 1.43-4.07l1.643 1.643A.25.25 0 0 1 5.396 7H1.25A.25.25 0 0 1 1 6.75V2.604a.25.25 0 0 1 .427-.177l1.438 1.438A8 8 0 1 1 9 17"/><path fill="currentColor" d="m11.61 9.639-3.331 2.07a.826.826 0 0 1-1.15-.266.86.86 0 0 1-.129-.452V6.849C7 6.38 7.374 6 7.834 6c.158 0 .312.045.445.13l3.331 2.071a.858.858 0 0 1 0 1.438"/></svg>
                        <svg class="media-icon media-icon--play" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" aria-hidden="true" viewBox="0 0 18 18"><path fill="currentColor" d="m13.473 10.476-6.845 4.256a1.697 1.697 0 0 1-2.364-.547 1.77 1.77 0 0 1-.264-.93v-8.51C4 3.78 4.768 3 5.714 3c.324 0 .64.093.914.268l6.845 4.255a1.763 1.763 0 0 1 0 2.953"/></svg>
                        <svg class="media-icon media-icon--pause" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" aria-hidden="true" viewBox="0 0 18 18"><rect width="4" height="12" x="3" y="3" fill="currentColor" rx="1.75"/><rect width="4" height="12" x="11" y="3" fill="currentColor" rx="1.75"/></svg>
                    </media-play-button>
                    <media-tooltip id="{{ $playerId }}-play-tooltip" side="top" class="media-tooltip"></media-tooltip>

                    @unless ($hideSeekButtons)
                        <media-seek-button commandfor="{{ $playerId }}-seek-back-tooltip" seconds="-10" class="media-button media-button--subtle media-button--icon media-button--seek">
                            <span class="media-icon__container">
                                <svg class="media-icon media-icon--flipped" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" aria-hidden="true" viewBox="0 0 18 18"><path fill="currentColor" d="M1 9c0 2.21.895 4.21 2.343 5.657l1.06-1.06a6.5 6.5 0 1 1 9.665-8.665l-1.641 1.641a.25.25 0 0 0 .177.427h4.146a.25.25 0 0 0 .25-.25V2.604a.25.25 0 0 0-.427-.177l-1.438 1.438A8 8 0 0 0 1 9"/></svg>
                                <span class="media-icon__label">10</span>
                            </span>
                        </media-seek-button>
                        <media-tooltip id="{{ $playerId }}-seek-back-tooltip" side="top" class="media-tooltip">Seek backward 10 seconds</media-tooltip>

                        <media-seek-button commandfor="{{ $playerId }}-seek-fwd-tooltip" seconds="10" class="media-button media-button--subtle media-button--icon media-button--seek">
                            <span class="media-icon__container">
                                <svg class="media-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" aria-hidden="true" viewBox="0 0 18 18"><path fill="currentColor" d="M1 9c0 2.21.895 4.21 2.343 5.657l1.06-1.06a6.5 6.5 0 1 1 9.665-8.665l-1.641 1.641a.25.25 0 0 0 .177.427h4.146a.25.25 0 0 0 .25-.25V2.604a.25.25 0 0 0-.427-.177l-1.438 1.438A8 8 0 0 0 1 9"/></svg>
                                <span class="media-icon__label">10</span>
                            </span>
                        </media-seek-button>
                        <media-tooltip id="{{ $playerId }}-seek-fwd-tooltip" side="top" class="media-tooltip">Seek forward 10 seconds</media-tooltip>
                    @endunless

                    @if ($showPrevNext)
                        @if ($nextContentUrl)
                            <a href="{{ $nextContentUrl }}"
                               class="media-button media-button--subtle media-button--icon jambo-episode-button jambo-episode-button--next"
                               data-episode-nav="next"
                               aria-label="{{ $isSeries ? 'Next episode' : 'Next' }}"
                               title="{{ $isSeries ? 'Next episode' : 'Next' }}"
                               commandfor="{{ $playerId }}-next-ep-tooltip">
                                <svg class="media-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true"><path fill="currentColor" d="M12.75 3a.75.75 0 0 0-.75.75v4.157L4.165 3.235A.75.75 0 0 0 3 3.879v10.242a.75.75 0 0 0 1.165.644L12 10.093v4.157a.75.75 0 0 0 1.5 0V3.75A.75.75 0 0 0 12.75 3"/></svg>
                            </a>
                            <media-tooltip id="{{ $playerId }}-next-ep-tooltip" side="top" class="media-tooltip">Next · {{ $nextContentLabel }}</media-tooltip>
                        @else
                            <button type="button"
                                    class="media-button media-button--subtle media-button--icon jambo-episode-button jambo-episode-button--next is-disabled"
                                    disabled
                                    aria-label="Next (unavailable)"
                                    title="{{ $isSeries ? 'No next episode' : 'No next item' }}">
                                <svg class="media-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true"><path fill="currentColor" d="M12.75 3a.75.75 0 0 0-.75.75v4.157L4.165 3.235A.75.75 0 0 0 3 3.879v10.242a.75.75 0 0 0 1.165.644L12 10.093v4.157a.75.75 0 0 0 1.5 0V3.75A.75.75 0 0 0 12.75 3"/></svg>
                            </button>
                        @endif
                    @endif
                </div>

                <div class="media-time-controls">
                    <media-time-group class="media-time-group">
                        <media-time type="current" class="media-time media-time--current"></media-time>
                        <media-time-separator class="media-time-separator"></media-time-separator>
                        <media-time type="duration" class="media-time media-time--duration"></media-time>
                    </media-time-group>

                    <media-time-slider class="media-slider">
                        <media-slider-track class="media-slider__track">
                            <media-slider-fill class="media-slider__fill"></media-slider-fill>
                            <media-slider-buffer class="media-slider__buffer"></media-slider-buffer>
                        </media-slider-track>
                        <media-slider-thumb class="media-slider__thumb"></media-slider-thumb>

                        <div class="media-preview media-slider__preview">
                            <div class="media-preview__thumbnail-wrapper">
                                <media-slider-thumbnail class="media-preview__thumbnail"></media-slider-thumbnail>
                            </div>
                            <media-slider-value type="pointer" class="media-time media-preview__time"></media-slider-value>
                        </div>
                    </media-time-slider>
                </div>

                <div class="media-button-group">
                    @if ($showAutoplayNext)
                        {{-- Autoplay-next pill switch. Label appears as a
                             tooltip on hover; clicking flips localStorage
                             and toggles `.is-on` — no reload, the `ended`
                             handler reads the flag live. --}}
                        <button type="button"
                                class="jambo-autoplay-switch"
                                role="switch"
                                aria-checked="false"
                                aria-label="{{ $isSeries ? 'Autoplay next episode' : 'Autoplay next' }}"
                                data-action="toggle-autoplay-next"
                                commandfor="{{ $playerId }}-autoplay-tooltip">
                            <span class="jambo-autoplay-switch__track" aria-hidden="true">
                                <span class="jambo-autoplay-switch__thumb"></span>
                            </span>
                        </button>
                        <media-tooltip id="{{ $playerId }}-autoplay-tooltip" side="top" class="media-tooltip">Autoplay next</media-tooltip>
                    @endif
                    <media-mute-button commandfor="{{ $playerId }}-volume-popover" class="media-button media-button--subtle media-button--icon media-button--mute">
                        <svg class="media-icon media-icon--volume-off" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" aria-hidden="true" viewBox="0 0 18 18"><path fill="currentColor" d="M.714 6.008h3.072l4.071-3.857c.5-.376 1.143 0 1.143.601V15.28c0 .602-.643.903-1.143.602l-4.071-3.858H.714c-.428 0-.714-.3-.714-.752V6.76c0-.451.286-.752.714-.752M14.5 7.586l-1.768-1.768a1 1 0 1 0-1.414 1.414L13.085 9l-1.767 1.768a1 1 0 0 0 1.414 1.414l1.768-1.768 1.768 1.768a1 1 0 0 0 1.414-1.414L15.914 9l1.768-1.768a1 1 0 0 0-1.414-1.414z"/></svg>
                        <svg class="media-icon media-icon--volume-low" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" aria-hidden="true" viewBox="0 0 18 18"><path fill="currentColor" d="M.714 6.008h3.072l4.071-3.857c.5-.376 1.143 0 1.143.601V15.28c0 .602-.643.903-1.143.602l-4.071-3.858H.714c-.428 0-.714-.3-.714-.752V6.76c0-.451.286-.752.714-.752m10.568.59a.91.91 0 0 1 0-1.316.91.91 0 0 1 1.316 0c1.203 1.203 1.47 2.216 1.522 3.208q.012.255.011.51c0 1.16-.358 2.733-1.533 3.803a.7.7 0 0 1-.298.156c-.382.106-.873-.011-1.018-.156a.91.91 0 0 1 0-1.316c.57-.57.995-1.551.995-2.487 0-.944-.26-1.667-.995-2.402"/></svg>
                        <svg class="media-icon media-icon--volume-high" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" aria-hidden="true" viewBox="0 0 18 18"><path fill="currentColor" d="M15.6 3.3c-.4-.4-1-.4-1.4 0s-.4 1 0 1.4C15.4 5.9 16 7.4 16 9s-.6 3.1-1.8 4.3c-.4.4-.4 1 0 1.4.2.2.5.3.7.3.3 0 .5-.1.7-.3C17.1 13.2 18 11.2 18 9s-.9-4.2-2.4-5.7"/><path fill="currentColor" d="M.714 6.008h3.072l4.071-3.857c.5-.376 1.143 0 1.143.601V15.28c0 .602-.643.903-1.143.602l-4.071-3.858H.714c-.428 0-.714-.3-.714-.752V6.76c0-.451.286-.752.714-.752m10.568.59a.91.91 0 0 1 0-1.316.91.91 0 0 1 1.316 0c1.203 1.203 1.47 2.216 1.522 3.208q.012.255.011.51c0 1.16-.358 2.733-1.533 3.803a.7.7 0 0 1-.298.156c-.382.106-.873-.011-1.018-.156a.91.91 0 0 1 0-1.316c.57-.57.995-1.551.995-2.487 0-.944-.26-1.667-.995-2.402"/></svg>
                    </media-mute-button>

                    {{-- `close-delay` was 100ms, which fires before the
                         pointer can traverse the gap from the mute button
                         to the slider. Bumped to 600ms so there's enough
                         grace time to actually grab the thumb. --}}
                    <media-popover id="{{ $playerId }}-volume-popover" open-on-hover delay="150" close-delay="600" side="top" class="media-popover media-popover--volume">
                        <media-volume-slider class="media-slider" orientation="vertical" thumb-alignment="edge">
                            <media-slider-track class="media-slider__track">
                                <media-slider-fill class="media-slider__fill"></media-slider-fill>
                            </media-slider-track>
                            <media-slider-thumb class="media-slider__thumb media-slider__thumb--persistent"></media-slider-thumb>
                        </media-volume-slider>
                    </media-popover>

                    {{-- Jambo settings menu (gear). Opens a custom popover with
                         Subtitles/CC, Sleep timer, Playback speed, Quality. A
                         plain <button> is fine here — the settings-menu JS
                         handles open/close instead of Media Chrome's popover. --}}
                    <button type="button"
                            class="media-button media-button--subtle media-button--icon jambo-settings-trigger"
                            data-for="{{ $playerId }}"
                            aria-label="Settings"
                            title="Settings">
                        <svg class="media-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" aria-hidden="true" viewBox="0 0 24 24"><path d="M19.14,12.94c0.04-0.3,0.06-0.61,0.06-0.94c0-0.32-0.02-0.64-0.07-0.94l2.03-1.58c0.18-0.14,0.23-0.41,0.12-0.61l-1.92-3.32c-0.12-0.22-0.37-0.29-0.59-0.22l-2.39,0.96c-0.5-0.38-1.03-0.7-1.62-0.94L14.4,2.81c-0.04-0.24-0.24-0.41-0.48-0.41h-3.84c-0.24,0-0.43,0.17-0.47,0.41L9.25,5.35C8.66,5.59,8.12,5.92,7.63,6.29L5.24,5.33c-0.22-0.08-0.47,0-0.59,0.22L2.74,8.87C2.62,9.08,2.66,9.34,2.86,9.48l2.03,1.58C4.84,11.36,4.8,11.69,4.8,12s0.02,0.64,0.07,0.94l-2.03,1.58c-0.18,0.14-0.23,0.41-0.12,0.61l1.92,3.32c0.12,0.22,0.37,0.29,0.59,0.22l2.39-0.96c0.5,0.38,1.03,0.7,1.62,0.94l0.36,2.54c0.05,0.24,0.24,0.41,0.48,0.41h3.84c0.24,0,0.44-0.17,0.47-0.41l0.36-2.54c0.59-0.24,1.13-0.56,1.62-0.94l2.39,0.96c0.22,0.08,0.47,0,0.59-0.22l1.92-3.32c0.12-0.22,0.07-0.47-0.12-0.61L19.14,12.94z M12,15.6c-1.98,0-3.6-1.62-3.6-3.6s1.62-3.6,3.6-3.6s3.6,1.62,3.6,3.6S13.98,15.6,12,15.6z"/></svg>
                    </button>

                    <media-captions-button commandfor="{{ $playerId }}-cc-tooltip" class="media-button media-button--subtle media-button--icon media-button--captions">
                        <svg class="media-icon media-icon--captions-off" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" aria-hidden="true" viewBox="0 0 18 18"><rect width="16.5" height="12.5" x=".75" y="2.75" stroke="currentColor" stroke-width="1.5" rx="3"/><rect width="3" height="1.5" x="3" y="8.5" fill="currentColor" rx=".75"/><rect width="2" height="1.5" x="13" y="8.5" fill="currentColor" rx=".75"/><rect width="4" height="1.5" x="11" y="11.5" fill="currentColor" rx=".75"/><rect width="5" height="1.5" x="7" y="8.5" fill="currentColor" rx=".75"/><rect width="7" height="1.5" x="3" y="11.5" fill="currentColor" rx=".75"/></svg>
                        <svg class="media-icon media-icon--captions-on" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" aria-hidden="true" viewBox="0 0 18 18"><path fill="currentColor" d="M15 2a3 3 0 0 1 3 3v8a3 3 0 0 1-3 3H3a3 3 0 0 1-3-3V5a3 3 0 0 1 3-3zM3.75 11.5a.75.75 0 0 0 0 1.5h5.5a.75.75 0 0 0 0-1.5zm8 0a.75.75 0 0 0 0 1.5h2.5a.75.75 0 0 0 0-1.5zm-8-3a.75.75 0 0 0 0 1.5h1.5a.75.75 0 0 0 0-1.5zm4 0a.75.75 0 0 0 0 1.5h3.5a.75.75 0 0 0 0-1.5zm6 0a.75.75 0 0 0 0 1.5h.5a.75.75 0 0 0 0-1.5z"/></svg>
                    </media-captions-button>
                    <media-tooltip id="{{ $playerId }}-cc-tooltip" side="top" class="media-tooltip">Toggle captions</media-tooltip>

                    @unless ($hidePipButton)
                        <media-pip-button commandfor="{{ $playerId }}-pip-tooltip" class="media-button media-button--subtle media-button--icon media-button--pip">
                            <svg class="media-icon media-icon--pip-enter" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" aria-hidden="true" viewBox="0 0 18 18"><path fill="currentColor" d="M13 2a4 4 0 0 1 4 4v2.645a3.5 3.5 0 0 0-1-.145h-.5V6A2.5 2.5 0 0 0 13 3.5H4A2.5 2.5 0 0 0 1.5 6v6A2.5 2.5 0 0 0 4 14.5h2.5v.5c0 .347.05.683.145 1H4a4 4 0 0 1-4-4V6a4 4 0 0 1 4-4z"/><rect width="10" height="7" x="8" y="10" fill="currentColor" rx="2"/><path fill="currentColor" d="M7.25 10A.75.75 0 0 0 8 9.25v-3.5a.75.75 0 0 0-1.5 0V8.5H3.75a.75.75 0 0 0-.743.648L3 9.25c0 .414.336.75.75.75z"/><path fill="currentColor" d="M6.72 9.78a.75.75 0 0 0 1.06-1.06l-3.5-3.5a.75.75 0 0 0-1.06 1.06z"/></svg>
                            <svg class="media-icon media-icon--pip-exit" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" aria-hidden="true" viewBox="0 0 18 18"><path fill="currentColor" d="M13 2a4 4 0 0 1 4 4v2.646a3.5 3.5 0 0 0-1-.146h-.5V6A2.5 2.5 0 0 0 13 3.5H4A2.5 2.5 0 0 0 1.5 6v6A2.5 2.5 0 0 0 4 14.5h2.5v.5q.002.523.146 1H4a4 4 0 0 1-4-4V6a4 4 0 0 1 4-4z"/><rect width="10" height="7" x="8" y="10" fill="currentColor" rx="2"/><path fill="currentColor" d="M3.75 5a.75.75 0 0 0-.75.75v3.5a.75.75 0 0 0 1.5 0V6.5h2.75a.75.75 0 0 0 .743-.648L8 5.75A.75.75 0 0 0 7.25 5z"/><path fill="currentColor" d="M4.28 5.22a.75.75 0 0 0-1.06 1.06l3.5 3.5a.75.75 0 0 0 1.06-1.06z"/></svg>
                        </media-pip-button>
                        <media-tooltip id="{{ $playerId }}-pip-tooltip" side="top" class="media-tooltip">Picture-in-picture</media-tooltip>
                    @endunless

                    <media-fullscreen-button commandfor="{{ $playerId }}-fs-tooltip" class="media-button media-button--subtle media-button--icon media-button--fullscreen">
                        <svg class="media-icon media-icon--fullscreen-enter" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" aria-hidden="true" viewBox="0 0 18 18"><path fill="currentColor" d="M15.25 2a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0V3.5h-3.75a.75.75 0 0 1-.743-.648L10 2.75a.75.75 0 0 1 .75-.75z"/><path fill="currentColor" d="M14.72 2.22a.75.75 0 1 1 1.06 1.06l-4.5 4.5a.75.75 0 1 1-1.06-1.06zM2.75 10a.75.75 0 0 1 .75.75v3.75h3.75a.75.75 0 0 1 .743.648L8 15.25a.75.75 0 0 1-.75.75h-4.5a.75.75 0 0 1-.75-.75v-4.5a.75.75 0 0 1 .75-.75"/><path fill="currentColor" d="M6.72 10.22a.75.75 0 1 1 1.06 1.06l-4.5 4.5a.75.75 0 0 1-1.06-1.06z"/></svg>
                        <svg class="media-icon media-icon--fullscreen-exit" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" aria-hidden="true" viewBox="0 0 18 18"><path fill="currentColor" d="M10.75 2a.75.75 0 0 1 .75.75V6.5h3.75a.75.75 0 0 1 .743.648L16 7.25a.75.75 0 0 1-.75.75h-4.5a.75.75 0 0 1-.75-.75v-4.5a.75.75 0 0 1 .75-.75"/><path fill="currentColor" d="M14.72 2.22a.75.75 0 1 1 1.06 1.06l-4.5 4.5a.75.75 0 1 1-1.06-1.06zM7.25 10a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0V11.5H2.75a.75.75 0 0 1-.743-.648L2 10.75a.75.75 0 0 1 .75-.75z"/><path fill="currentColor" d="M6.72 10.22a.75.75 0 1 1 1.06 1.06l-4.5 4.5a.75.75 0 0 1-1.06-1.06z"/></svg>
                    </media-fullscreen-button>
                    <media-tooltip id="{{ $playerId }}-fs-tooltip" side="top" class="media-tooltip">Fullscreen</media-tooltip>
                </div>
            </media-tooltip-group>
        </media-controls>

        {{-- Settings popover. Positioned via CSS relative to the media
             container (which is position:relative by default in the skin),
             anchored bottom-right above the control bar. --}}
        <div class="jambo-settings-popover" id="{{ $playerId }}-settings-popover" hidden data-open="false">
            <div class="jambo-settings-pane" data-pane="main">
                <button type="button" class="jambo-settings-row" data-open-pane="cc">
                    <span class="jambo-settings-icon">
                        <svg viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x=".75" y="2.75" width="16.5" height="12.5" rx="3" stroke="currentColor" stroke-width="1.5"/><rect x="3" y="8.5" width="3" height="1.5" rx=".75" fill="currentColor"/><rect x="7" y="8.5" width="5" height="1.5" rx=".75" fill="currentColor"/><rect x="13" y="8.5" width="2" height="1.5" rx=".75" fill="currentColor"/><rect x="3" y="11.5" width="7" height="1.5" rx=".75" fill="currentColor"/><rect x="11" y="11.5" width="4" height="1.5" rx=".75" fill="currentColor"/></svg>
                    </span>
                    <span class="jambo-settings-label">Subtitles/CC</span>
                    <span class="jambo-settings-value" data-summary="cc">Off</span>
                    <span class="jambo-settings-chev">›</span>
                </button>
                <button type="button" class="jambo-settings-row" data-open-pane="sleep">
                    <span class="jambo-settings-icon">
                        <svg viewBox="0 0 18 18" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M15.5 11.5a5.5 5.5 0 0 1-9-5.5 6.5 6.5 0 1 0 9 5.5"/></svg>
                    </span>
                    <span class="jambo-settings-label">Sleep timer</span>
                    <span class="jambo-settings-value" data-summary="sleep">Off</span>
                    <span class="jambo-settings-chev">›</span>
                </button>
                <button type="button" class="jambo-settings-row" data-open-pane="speed">
                    <span class="jambo-settings-icon">
                        <svg viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="9" cy="9" r="6.5" stroke="currentColor" stroke-width="1.5"/><path d="M9 9l3-2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                    </span>
                    <span class="jambo-settings-label">Playback speed</span>
                    <span class="jambo-settings-value" data-summary="speed">Normal</span>
                    <span class="jambo-settings-chev">›</span>
                </button>
                <button type="button" class="jambo-settings-row jambo-datasaver-toggle" data-action="toggle-datasaver">
                    <span class="jambo-settings-icon">
                        <svg viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 2C5.134 2 2 5.134 2 9s3.134 7 7 7 7-3.134 7-7-3.134-7-7-7zm-.5 3.5a.75.75 0 0 1 1.5 0v4a.75.75 0 0 1-1.5 0v-4zM9 14a1 1 0 1 1 0-2 1 1 0 0 1 0 2z" fill="currentColor"/></svg>
                    </span>
                    <span class="jambo-settings-label">Data Saver</span>
                    <span class="jambo-settings-value" data-summary="datasaver">Off</span>
                </button>
                <button type="button" class="jambo-settings-row jambo-quality-row" data-open-pane="quality" style="display:none;">
                    <span class="jambo-settings-icon">
                        <svg viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 5h6M13 5h2M3 9h2M9 9h6M3 13h8M14 13h1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><circle cx="11" cy="5" r="1.5" fill="currentColor"/><circle cx="7" cy="9" r="1.5" fill="currentColor"/><circle cx="12.5" cy="13" r="1.5" fill="currentColor"/></svg>
                    </span>
                    <span class="jambo-settings-label">Quality</span>
                    <span class="jambo-settings-value" data-summary="quality">Original</span>
                    <span class="jambo-settings-chev">›</span>
                </button>
            </div>

            <div class="jambo-settings-pane" data-pane="cc" hidden>
                <button type="button" class="jambo-settings-back" data-back>
                    <span class="jambo-settings-chev">‹</span>
                    <span>Subtitles/CC</span>
                </button>
                <div class="jambo-settings-options" data-kind="cc">
                    {{-- Populated by JS from the <video>'s textTracks. --}}
                    <button type="button" class="jambo-settings-row is-active" data-value="off">
                        <span class="jambo-settings-icon"><svg viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 9l3 3 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        <span class="jambo-settings-label">Off</span>
                    </button>
                </div>
            </div>

            <div class="jambo-settings-pane" data-pane="sleep" hidden>
                <button type="button" class="jambo-settings-back" data-back>
                    <span class="jambo-settings-chev">‹</span>
                    <span>Sleep timer</span>
                </button>
                <div class="jambo-settings-options" data-kind="sleep">
                    @foreach ([['0','Off'],['15','15 minutes'],['30','30 minutes'],['45','45 minutes'],['60','1 hour'],['end','End of video']] as [$val, $label])
                        <button type="button" class="jambo-settings-row {{ $val === '0' ? 'is-active' : '' }}" data-value="{{ $val }}">
                            <span class="jambo-settings-icon"><svg viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 9l3 3 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                            <span class="jambo-settings-label">{{ $label }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="jambo-settings-pane" data-pane="speed" hidden>
                <button type="button" class="jambo-settings-back" data-back>
                    <span class="jambo-settings-chev">‹</span>
                    <span>Playback speed</span>
                </button>
                <div class="jambo-settings-options" data-kind="speed">
                    @foreach ([['0.25','0.25×'],['0.5','0.5×'],['0.75','0.75×'],['1','Normal'],['1.25','1.25×'],['1.5','1.5×'],['1.75','1.75×'],['2','2×']] as [$val, $label])
                        <button type="button" class="jambo-settings-row {{ $val === '1' ? 'is-active' : '' }}" data-value="{{ $val }}">
                            <span class="jambo-settings-icon"><svg viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 9l3 3 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                            <span class="jambo-settings-label">{{ $label }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="jambo-settings-pane" data-pane="quality" hidden>
                <button type="button" class="jambo-settings-back" data-back>
                    <span class="jambo-settings-chev">‹</span>
                    <span>Quality</span>
                </button>
                <div class="jambo-settings-options" data-kind="quality">
                    <button type="button" class="jambo-settings-row is-active" data-value="default">
                        <span class="jambo-settings-icon"><svg viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 9l3 3 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        <span class="jambo-settings-label">Original</span>
                    </button>
                    {{-- 480p option — only shown when video_url_low is set --}}
                    <button type="button" class="jambo-settings-row jambo-quality-low-option" data-value="low" style="display:none;">
                        <span class="jambo-settings-icon"><svg viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 9l3 3 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        <span class="jambo-settings-label">480p</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Gesture zones: left = rewind, center = play/pause, right = forward --}}
        <div class="jambo-gesture-zone jambo-gesture-zone--left" data-gesture="rewind"></div>
        <div class="jambo-gesture-zone jambo-gesture-zone--right" data-gesture="forward"></div>

        {{-- Visual feedback overlays --}}
        <div class="jambo-gesture-feedback" id="{{ $playerId }}-gesture-feedback">
            <svg class="jambo-gesture-icon" viewBox="0 0 48 48" fill="none"><path d="M16 10v28l22-14z" fill="currentColor"/></svg>
        </div>

        <div class="media-overlay"></div>
    </media-container>
</video-player>

{{-- Player-wide glue that must run after the element tree is fully
     parsed. Handles: (1) autoplay-next toggle wiring (series only);
     (2) play/pause icon swap for the mobile center overlay — Media
     Chrome's skin CSS only reliably auto-swaps icons on the primary
     play button, so we mirror the state manually. --}}
<script>
(function(){
    var videoEl = document.getElementById('{{ $playerId }}');
    if (!videoEl) return;

    /* --- Center overlay play/pause icon swap ---------------------- */
    // The overlay <media-play-button> holds both icons; we flip a data
    // attribute on the button and let CSS show exactly one at a time.
    var centerPlay = document.querySelector('.jambo-mobile-center__btn--play');
    if (centerPlay) {
        var syncPlayIcon = function(){
            if (videoEl.paused) {
                centerPlay.removeAttribute('data-playing');
            } else {
                centerPlay.setAttribute('data-playing', '1');
            }
        };
        syncPlayIcon();
        videoEl.addEventListener('play', syncPlayIcon);
        videoEl.addEventListener('pause', syncPlayIcon);
        videoEl.addEventListener('ended', syncPlayIcon);
    }

    /* --- Idle fade for the center overlay -------------------------- */
    // Media Chrome auto-fades its <media-controls>, but our overlay
    // lives outside that element so we need our own timer. Rule of
    // thumb (YouTube):
    //   • Paused → always visible (so users can find the play button)
    //   • Playing → hide after 2.5s of no pointer / touch / key activity
    //   • Any pointer / touch / key on the container → wake + restart
    var overlay = document.querySelector('.jambo-mobile-center');
    var mediaContainer = videoEl.closest('media-container') || videoEl.parentElement;
    if (overlay && mediaContainer) {
        var IDLE_MS = 2500;
        var idleTimer = null;

        var wake = function(){
            overlay.classList.remove('is-hidden');
            clearTimeout(idleTimer);
            if (!videoEl.paused && !videoEl.ended) {
                idleTimer = setTimeout(function(){
                    overlay.classList.add('is-hidden');
                }, IDLE_MS);
            }
        };

        // Any of these activities count as "the user is here".
        ['pointermove', 'pointerdown', 'touchstart', 'keydown'].forEach(function(ev){
            mediaContainer.addEventListener(ev, wake, { passive: true });
        });

        // State transitions: pause / end force visible; play starts timer.
        videoEl.addEventListener('play', wake);
        videoEl.addEventListener('pause', function(){
            clearTimeout(idleTimer);
            overlay.classList.remove('is-hidden');
        });
        videoEl.addEventListener('ended', function(){
            clearTimeout(idleTimer);
            overlay.classList.remove('is-hidden');
        });

        wake();
    }

    @if ($isSeries)
        /* --- Autoplay-next switch wiring ------------------------- */
        // Persists across all episodes, all series, all sessions via
        // localStorage. Episode-page's `ended` handler reads the same
        // key live.
        var apBtn = document.querySelector('button[data-action="toggle-autoplay-next"][commandfor="{{ $playerId }}-autoplay-tooltip"]');
        if (apBtn) {
            var apKey = 'jambo.autoplayNext';
            var apApply = function(on) {
                apBtn.classList.toggle('is-on', on);
                apBtn.setAttribute('aria-checked', on ? 'true' : 'false');
            };
            apApply(localStorage.getItem(apKey) === '1');
            // pointerdown beats any command-event the <media-tooltip>
            // activation might run on click. Snappier on mobile too.
            apBtn.addEventListener('pointerdown', function(e){
                e.preventDefault();
                e.stopPropagation();
                var next = localStorage.getItem(apKey) !== '1';
                localStorage.setItem(apKey, next ? '1' : '0');
                apApply(next);
            });
            apBtn.addEventListener('click', function(e){
                e.preventDefault();
                e.stopPropagation();
            });
        }
    @endif
})();
</script>
