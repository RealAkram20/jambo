@extends('frontend::layouts.master', ['isSwiperSlider' => true, 'active' => 'playlist', 'bodyClass' => 'custom-header-relative', 'title' => $title])

@section('content')
    {{-- Jambo minimal player assets — mirror /watch and /episode so
         the in-page player behaves identically here. --}}
    <link rel="stylesheet" href="{{ asset('frontend/css/player.css') }}">
    <script type="module" src="https://cdn.jsdelivr.net/npm/@videojs/html/cdn/video-minimal-ui.js"></script>
    <script src="{{ asset('frontend/js/jambo-settings-menu.js') }}" defer></script>
    <script src="{{ asset('frontend/js/jambo-player-gestures.js') }}" defer></script>

    <section class="section-padding">
        <div class="playlist-detail-page">
            <div class="container-fluid">
                <div class="row flex-row-reverse">

                    {{-- Right queue: the entire watchlist. Active item gets
                         a highlighted border (template CSS handles this via
                         .playlist-data-card.active). --}}
                    <div class="col-xxl-3 col-xl-4 col-lg-5">
                        <div class="card border-0">
                            <div class="card-header pb-3 mb-3 border-bottom d-flex align-items-center justify-content-between gap-1">
                                <h5 class="m-0">{{ __('frontendheader.watch_list') ?? 'My Watchlist' }}</h5>
                                <small class="text-muted">
                                    {{ $currentIndex + 1 }}/{{ $items->count() }}
                                </small>
                            </div>

                            <div class="card-body px-0 pt-0 pb-3">
                                <div class="playlist-data">
                                    @foreach ($items as $item)
                                        @php
                                            $w = $item->watchable;
                                            if (!$w) continue;

                                            $isShow    = $w instanceof \Modules\Content\app\Models\Show;
                                            $isEpisode = $w instanceof \Modules\Content\app\Models\Episode;

                                            $rowPoster = $w->poster_url
                                                ?: ($isEpisode ? ($w->still_url ?: ($w->season->show->poster_url ?? null)) : null);
                                            $rowImg = $rowPoster ?: asset('frontend/images/media/rabbit-portrait.webp');

                                            $rowTitle = $isEpisode
                                                ? 'S' . str_pad($w->season->number ?? 0, 2, '0', STR_PAD_LEFT)
                                                    . 'E' . str_pad($w->number ?? 0, 2, '0', STR_PAD_LEFT)
                                                    . ' — ' . ($w->title ?? '')
                                                : ($w->title ?? '');

                                            // Shows go to /series/{slug}, episodes to /episode/{id},
                                            // movies to the pretty /watchlist/{slug} queue player.
                                            $rowUrl = match (true) {
                                                $isShow    => route('frontend.series_detail', $w->slug),
                                                $isEpisode => route('frontend.episode', $w->id),
                                                default    => route('frontend.watchlist_play', $w->slug),
                                            };

                                            $isActive = $current && $item->id === $current->id;
                                            $addedLabel = $item->added_at?->diffForHumans() ?? '';
                                        @endphp
                                        <div class="playlist-data-card {{ $isActive ? 'active' : '' }}"
                                             data-watchlist-queue-item="{{ $item->id }}">
                                            <div class="playlist-data-card-image">
                                                <a href="{{ $rowUrl }}">
                                                    <img src="{{ $rowImg }}" alt="{{ $rowTitle }}"
                                                         class="img-fluid object-cover w-100 border-0">
                                                </a>
                                                @if ($isActive)
                                                    <span class="badge bg-primary position-absolute top-0 start-0 m-2">
                                                        <i class="ph ph-play-circle me-1"></i>{{ __('streamButtons.play_now') }}
                                                    </span>
                                                @elseif ($isShow)
                                                    <span class="badge bg-secondary position-absolute top-0 start-0 m-2">
                                                        <i class="ph ph-television me-1"></i>{{ __('frontendheader.tv_show') }}
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="playlist-data-card-content">
                                                <h6 class="mt-0 mb-2 line-count-2 playlist-data-title">
                                                    <a href="{{ $rowUrl }}">{{ $rowTitle }}</a>
                                                </h6>
                                                <ul class="playlist-category list-inline d-flex flex-wrap align-items-center m-0 p-0 column-gap-3 row-gap-1">
                                                    @if ($w->relationLoaded('genres') && $w->genres->count())
                                                        <li class="text-truncate">
                                                            <i class="ph ph-tag me-1"></i>{{ $w->genres->take(2)->pluck('name')->join(', ') }}
                                                        </li>
                                                    @endif
                                                    @if ($addedLabel)
                                                        <li class="text-muted small">{{ $addedLabel }}</li>
                                                    @endif
                                                </ul>
                                            </div>
                                            <button type="button"
                                                    class="btn btn-sm btn-link text-danger p-1 ms-auto align-self-start jambo-watchlist-queue-remove"
                                                    data-watchlist-id="{{ $item->id }}"
                                                    aria-label="Remove from watchlist"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-title="{{ __('streamPlaylist.remove_from_watchlist') ?? 'Remove from watchlist' }}">
                                                <i class="ph ph-trash"></i>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Left: Jambo player + title + share/remove actions. --}}
                    <div class="col-xxl-9 col-xl-8 col-lg-7 mt-lg-0 mt-4">
                        @if ($source && ($source['url'] ?? null))
                            <div class="jambo-watch-hero" id="jambo-player-hero">
                                {{-- Sentinel: the IntersectionObserver watches this element, which
                                     never moves. The frame can be portalled to <body> on mini-mode
                                     without causing the observer to bounce. --}}
                                <div class="jambo-player-sentinel" id="jambo-player-sentinel"></div>
                                <div class="jambo-player-frame" id="jambo-player-frame" style="position:absolute; inset:0;">
                                    <button type="button" class="jambo-mini-close" id="jambo-mini-close" aria-label="Close mini-player" title="Close">
                                        <i class="ph ph-x"></i>
                                    </button>
                                    @include('frontend::components.partials.jambo-minimal-player', [
                                        'playerSrc'        => $source['url'],
                                        'playerSrcLow'     => $sourceLow,
                                        'playerPoster'     => $poster,
                                        'playerId'         => 'jambo-watchlist-player',
                                        'resumePosition'   => $resumePosition,
                                        // Queue nav replaces ±10s seek buttons — ±10s
                                        // stays available via ← / → keys and double-tap
                                        // gestures (see jambo-player-gestures.js).
                                        'hideSeekButtons'  => true,
                                        'showPrevNext'     => true,
                                        'prevContentUrl'   => $prevMovie ? route('frontend.watchlist_play', $prevMovie->slug) : null,
                                        'nextContentUrl'   => $nextMovie ? route('frontend.watchlist_play', $nextMovie->slug) : null,
                                        'prevContentLabel' => $prevMovie?->title,
                                        'nextContentLabel' => $nextMovie?->title,
                                        // PIP dropped so the autoplay pill fits in the
                                        // action row without crowding mute/fullscreen.
                                        'hidePipButton'    => true,
                                        'showAutoplayNext' => true,
                                    ])
                                </div>
                            </div>
                        @else
                            <div class="jambo-watch-hero" style="aspect-ratio:16/9; background:#000; display:flex; align-items:center; justify-content:center;">
                                <div class="text-center p-5 text-light">
                                    <h4 class="mb-2">{{ __('streamBlog.not_available_yet') ?? "This title isn't streamable yet." }}</h4>
                                    <p class="text-muted mb-3">No Video URL is set for <strong>{{ $title }}</strong>.</p>
                                    <a href="{{ $detailUrl }}" class="btn btn-outline-light">← Back to details</a>
                                </div>
                            </div>
                        @endif

                        <div id="streamit_player_container"
                             class="trending-info d-flex justify-content-between align-items-center gap-4 flex-wrap">
                            <a href="{{ $detailUrl }}" class="text-decoration-none">
                                <h4 class="my-2 fw-bold">{{ $title }}</h4>
                            </a>
                            <ul class="actions-playlist list-inline my-2 p-0 d-flex gap-2 justify-content-md-end">
                                <li>
                                    <a href="{{ $detailUrl }}" class="btn btn-secondary border action-btn"
                                       data-bs-toggle="tooltip" data-bs-placement="top"
                                       data-bs-title="{{ __('streamButtons.details') ?? 'Details' }}">
                                        <i class="ph ph-info"></i>
                                    </a>
                                </li>
                                <li class="position-relative share-button dropend dropdown">
                                    <button type="button" class="btn btn-secondary border action-btn"
                                            data-bs-toggle="modal" data-bs-target="#shareModal">
                                        <span class="h-100 w-100 d-block" data-bs-toggle="tooltip" data-bs-placement="top"
                                              data-bs-title="{{ __('streamTag.share') }}">
                                            <i class="ph ph-share-network"></i>
                                        </span>
                                    </button>
                                </li>
                                <li>
                                    <button type="button"
                                            class="btn btn-outline-danger action-btn jambo-watchlist-queue-remove"
                                            data-watchlist-id="{{ $current->id }}"
                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                            data-bs-title="{{ __('streamPlaylist.remove_from_watchlist') ?? 'Remove from watchlist' }}">
                                        <i class="ph ph-bookmark-simple"></i>
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>

    @include('frontend::components.widgets.share-modal')

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}

    @if ($source && ($source['url'] ?? null))
    <script>
    // Full player wiring — mirrors the /watch and /episode pages so
    // the watchlist queue gets identical behavior: keyboard shortcuts,
    // double-tap gestures, heartbeat to the watch-history endpoint,
    // mini-mode on scroll, and the close-×  for the mini player.
    document.addEventListener('DOMContentLoaded', async function () {
        await customElements.whenDefined('video-player');

        const video    = document.getElementById('jambo-watchlist-player');
        const frame    = document.getElementById('jambo-player-frame');
        const hero     = document.getElementById('jambo-player-hero');
        const sentinel = document.getElementById('jambo-player-sentinel');
        const closeBtn = document.getElementById('jambo-mini-close');
        if (!video) return;

        // Best-effort muted autoplay. Browsers that block unmuted
        // autoplay still allow muted, so the user sees immediate
        // motion and can unmute if they want.
        video.muted = true;
        const p = video.play();
        if (p && typeof p.catch === 'function') p.catch(function () {});

        // Settings gear (Data Saver + Quality) + keyboard/touch shortcuts.
        if (typeof window.jamboAttachSettingsMenu === 'function') {
            window.jamboAttachSettingsMenu('jambo-watchlist-player');
        }
        if (typeof window.jamboAttachGestures === 'function') {
            window.jamboAttachGestures('jambo-watchlist-player');
        }

        // ---- Heartbeat ------------------------------------------------
        // currentPayableId is `let` so the in-place fullscreen swap can
        // update it without reloading the page — the heartbeat always
        // writes against whichever movie is actually playing now.
        let currentPayableId = {{ Js::from($watchable->id) }};
        const heartbeatUrl = {{ Js::from(url('/api/v1/streaming/heartbeat')) }};
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
                        payable_type: 'movie',
                        payable_id: currentPayableId,
                        position: Math.floor(lastPosition),
                        duration: duration ? Math.floor(duration) : null,
                    }),
                });
            } catch (e) { console.debug('[watchlist-play] heartbeat failed', e); }
        }
        video.addEventListener('pause', sendHeartbeat);
        video.addEventListener('ended', sendHeartbeat);
        setInterval(sendHeartbeat, 15000);
        window.addEventListener('pagehide', sendHeartbeat);

        // ---- Mini-mode on scroll -------------------------------------
        // Observe the in-flow sentinel (never moves), not the frame
        // itself (which gets portalled). Prevents flicker loops where
        // the frame going fixed changes its own intersection ratio.
        function enterMini() {
            if (!frame || frame.classList.contains('jambo-player-frame--mini')) return;
            document.body.appendChild(frame);
            frame.style.position = '';
            frame.style.inset = '';
            frame.classList.add('jambo-player-frame--mini');
        }
        function exitMini() {
            if (!frame || !frame.classList.contains('jambo-player-frame--mini')) return;
            frame.classList.remove('jambo-player-frame--mini');
            frame.style.position = 'absolute';
            frame.style.inset = '0';
            if (hero) hero.appendChild(frame);
        }

        if ('IntersectionObserver' in window && sentinel) {
            const io = new IntersectionObserver((entries) => {
                if (document.fullscreenElement) return;
                const r = entries[0].intersectionRatio;
                // Hysteresis: enter mini at <25% visible, exit at >50%.
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

        // ---- Autoplay-next toggle ------------------------------------
        // Single source of truth: localStorage jambo.autoplayNext.
        // The pill in the control bar reflects that value; clicking it
        // flips the flag, the `ended` handler below reads it live.
        const AUTOPLAY_KEY = 'jambo.autoplayNext';
        const autoplayToggle = document.querySelector('button[data-action="toggle-autoplay-next"]');

        function refreshAutoplayUi() {
            const on = localStorage.getItem(AUTOPLAY_KEY) === '1';
            if (autoplayToggle) {
                autoplayToggle.classList.toggle('is-on', on);
                autoplayToggle.setAttribute('aria-checked', on ? 'true' : 'false');
            }
        }
        refreshAutoplayUi();
        if (autoplayToggle) {
            autoplayToggle.addEventListener('click', () => {
                const cur = localStorage.getItem(AUTOPLAY_KEY) === '1';
                localStorage.setItem(AUTOPLAY_KEY, cur ? '0' : '1');
                refreshAutoplayUi();
            });
        }

        // ---- In-place fullscreen swap --------------------------------
        // Full-page navigation drops fullscreen (browser security rule),
        // so prev / next / autoplay-end while fullscreen instead fetch
        // the target movie's JSON player-data and swap <video>.src in
        // place. history.pushState keeps the URL bar honest for the
        // heartbeat. Outside fullscreen we fall back to normal nav so
        // the rest of the page chrome (title, queue highlight) stays
        // in sync with what's playing.
        let currentSlug = {{ Js::from($watchable->slug) }};
        let prevSlug    = {{ Js::from($prevMovie?->slug) }};
        let nextSlug    = {{ Js::from($nextMovie?->slug) }};
        const playerDataBase = {{ Js::from(url('/api/v1/watchlist')) }}; // + /{slug}/player-data

        // Two sets of prev/next anchors live in the DOM: the mobile
        // center overlay and the desktop control bar. Both need the
        // fullscreen-intercept handler, and both hrefs need to be
        // kept in sync after a swap — so work with arrays.
        const prevBtns = Array.from(document.querySelectorAll('[data-episode-nav="prev"]'));
        const nextBtns = Array.from(document.querySelectorAll('[data-episode-nav="next"]'));

        function inFullscreen() {
            return !!(document.fullscreenElement || document.webkitFullscreenElement);
        }

        function applyNavState(btns, contentUrl) {
            btns.forEach(b => {
                if (contentUrl) {
                    b.setAttribute('href', contentUrl);
                    b.classList.remove('is-disabled');
                    b.removeAttribute('aria-disabled');
                } else {
                    b.removeAttribute('href');
                    b.classList.add('is-disabled');
                    b.setAttribute('aria-disabled', 'true');
                }
            });
        }

        async function swapToMovie(targetSlug) {
            if (!targetSlug) return false;
            try {
                // Flush heartbeat BEFORE we clobber currentSlug so the
                // write targets the outgoing movie.
                await sendHeartbeat();

                const res = await fetch(playerDataBase + '/' + encodeURIComponent(targetSlug) + '/player-data', {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!res.ok) return false;
                const data = await res.json();
                if (!data.videoUrl) return false;

                video.dataset.srcDefault = data.videoUrl;
                if (data.videoUrlLow) {
                    video.dataset.srcLow = data.videoUrlLow;
                } else {
                    delete video.dataset.srcLow;
                }
                const quality = localStorage.getItem('jambo.quality') || 'default';
                video.src = (quality === 'low' && data.videoUrlLow) ? data.videoUrlLow : data.videoUrl;
                video.load();
                const resume = data.resumePosition || 0;
                if (resume > 0) {
                    video.addEventListener('loadedmetadata', function onMeta() {
                        video.removeEventListener('loadedmetadata', onMeta);
                        if (video.duration && resume < video.duration - 30) {
                            video.currentTime = resume;
                        }
                    });
                }
                const p = video.play();
                if (p && typeof p.catch === 'function') p.catch(() => {});

                // Roll state forward so subsequent prev/next clicks
                // (still in fullscreen) target the new neighbours.
                currentSlug = targetSlug;
                nextSlug = data.nextContent ? data.nextContent.slug : null;
                prevSlug = data.previousContent ? data.previousContent.slug : null;
                // The heartbeat writes against the current payable_id,
                // so push the new id into scope too.
                currentPayableId = data.id || currentPayableId;
                lastPosition = 0;
                duration = null;

                applyNavState(nextBtns, data.nextContent ? data.nextContent.url : null);
                applyNavState(prevBtns, data.previousContent ? data.previousContent.url : null);

                if (data.detailUrl) history.pushState(null, '', data.detailUrl);
                if (data.title) document.title = data.title;

                return true;
            } catch (e) {
                console.debug('[watchlist-play] in-place swap failed', e);
                return false;
            }
        }

        // Intercept prev/next link clicks while fullscreen — swap in
        // place instead of navigating (which would exit fullscreen).
        // Using a capturing document-level listener ensures we beat
        // any other click handlers (media-controls, gesture layer)
        // that might fire first and drop the event before our
        // preventDefault runs.
        document.addEventListener('click', async function (e) {
            const btn = e.target.closest('[data-episode-nav="prev"], [data-episode-nav="next"]');
            if (!btn) return;
            if (!inFullscreen()) return; // regular nav exits fullscreen naturally
            if (btn.classList.contains('is-disabled') || btn.hasAttribute('aria-disabled')) {
                e.preventDefault();
                e.stopPropagation();
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            const which = btn.getAttribute('data-episode-nav');
            const target = which === 'next' ? nextSlug : prevSlug;
            await swapToMovie(target);
        }, true);

        // Autoplay on end: in-place if fullscreen, otherwise navigate.
        // When autoplay is off we just sit on the ended state so the
        // user can pick what to play next from the queue sidebar.
        video.addEventListener('ended', async function () {
            if (localStorage.getItem(AUTOPLAY_KEY) !== '1') return;
            if (!nextSlug) return;
            if (inFullscreen()) {
                await swapToMovie(nextSlug);
            } else {
                const nextUrl = nextBtn && nextBtn.getAttribute('href');
                if (nextUrl) window.location.href = nextUrl;
            }
        });
    });
    </script>
    @endif

    <script>
    // Remove-from-queue handler. After a successful DELETE, fade out
    // the row. If the user removed the one that's currently playing,
    // bounce back to the watchlist list so they can pick a new one.
    (function () {
        var currentId = {{ (int) $current->id }};
        var listUrl   = {{ Js::from(route('frontend.watchlist_detail')) }};
        var apiBase   = {{ Js::from(url('/api/v1/watchlist')) }};
        var csrf      = document.querySelector('meta[name="csrf-token"]')?.content || '';

        document.querySelectorAll('.jambo-watchlist-queue-remove').forEach(function (btn) {
            btn.addEventListener('click', async function (e) {
                e.preventDefault();
                e.stopPropagation();
                var id = btn.dataset.watchlistId;
                if (!id || btn.dataset.busy === '1') return;
                btn.dataset.busy = '1';

                try {
                    var res = await fetch(apiBase + '/' + id, {
                        method: 'DELETE',
                        credentials: 'same-origin',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);

                    if (parseInt(id, 10) === currentId) {
                        // Current item got removed — back to the list.
                        window.location.href = listUrl;
                        return;
                    }

                    var row = document.querySelector('[data-watchlist-queue-item="' + id + '"]');
                    if (row) {
                        row.style.transition = 'opacity .2s, transform .2s';
                        row.style.opacity = '0';
                        row.style.transform = 'translateX(8px)';
                        setTimeout(function () { row.remove(); }, 200);
                    }
                } catch (err) {
                    console.warn('[watchlist-queue-remove]', err);
                    btn.dataset.busy = '';
                }
            });
        });
    })();
    </script>
@endsection
