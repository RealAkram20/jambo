@extends('frontend::layouts.master', [
    'isSwiperSlider' => true,
    'bodyClass' => 'custom-header-relative',
    'isSelect2' => true,
    // Browser <title> + og:title fallback. Format mirrors what the
    // share-link preview should read: "Show — S01E03: Episode Title".
    'title' => $show->title . ' — '
        . 'S' . str_pad($episode->season->number, 2, '0', STR_PAD_LEFT)
        . 'E' . str_pad($episode->number,         2, '0', STR_PAD_LEFT)
        . ': ' . $episode->title,
])

{{-- Social-preview metadata. Episode still wins for og:image (it's
     the most specific image we have), then show backdrop, then
     poster, then the site-wide default in head-tags. Description
     prefers episode synopsis, falls back to show synopsis so the
     preview is never just a generic site blurb. --}}
@if ($episode->still_url ?: ($show->backdrop_url ?: $show->poster_url))
    @section('seo:image', media_url($episode->still_url ?: ($show->backdrop_url ?: $show->poster_url)))
@endif
@php
    $epDescSource = $episode->synopsis ?: $show->synopsis;
@endphp
@if (!empty($epDescSource))
    @section('seo:description', \Illuminate\Support\Str::limit(strip_tags((string) $epDescSource), 200))
@endif
@section('seo:type', 'video.episode')

{{-- TVEpisode + VideoObject. The TVEpisode graph references the parent
     series by the same #series @id the show's detail page declares, so
     Google can assemble the whole series across pages. VideoObject is
     what makes the page eligible for the Videos tab and the video
     thumbnail in web results — it needs a still, so episodes without
     one fall back to the show artwork inside StructuredData rather
     than being dropped.

     $season isn't in scope yet at this point in the template, so we read
     $episode->season directly rather than depending on declaration order.
     The controller always loads the season relation.

     (Careful with the wording here: a literal PHP-block directive written
     inside a Blade comment is NOT safe. Blade extracts raw PHP blocks with
     a non-greedy match *before* it strips comments, so the opening tag in a
     comment pairs with the next real closing tag and silently swallows every
     line in between — which is exactly how this page started 500ing on an
     undefined $headline.) --}}
@push('seo:head')
    @include('seo::partials.json-ld', [
        'schemas' => [
            \Modules\Seo\app\Support\StructuredData::tvEpisode($episode, $show, $episode->season),
            \Modules\Seo\app\Support\StructuredData::videoObject(
                $show->title . ' S' . $episode->season->number . 'E' . $episode->number
                    . ($episode->title ? ': ' . $episode->title : ''),
                $episode->synopsis ?: $show->synopsis,
                $episode->still_url ?: $show->poster_url,
                $show->backdrop_url,
                $episode->published_at,
                $episode->runtime_minutes,
                $episode->frontendUrl($show),
            ),
            \Modules\Seo\app\Support\StructuredData::breadcrumbs([
                ['name' => 'Home', 'url' => route('frontend.ott')],
                ['name' => 'Series', 'url' => route('frontend.series')],
                ['name' => $show->title, 'url' => route('frontend.series_detail', $show->slug)],
                ['name' => 'S' . $episode->season->number . 'E' . $episode->number,
                 'url'  => $episode->frontendUrl($show)],
            ]),
        ],
    ])
@endpush

@php
    $poster = $episode->still_url ?: ($show->backdrop_url ?: $show->poster_url);
    $season = $episode->season;
    $seasons = $show->seasons->sortBy('number');
    $headline = 'S' . str_pad($season->number, 2, '0', STR_PAD_LEFT) . 'E' . str_pad($episode->number, 2, '0', STR_PAD_LEFT) . ' — ' . $episode->title;

    // Sibling-episode metadata for the player's prev/next buttons.
    $epLabel = function ($ep) {
        if (! $ep) return null;
        $sn = $ep->season->number ?? null;
        return 'S' . str_pad($sn ?? 0, 2, '0', STR_PAD_LEFT)
            . 'E' . str_pad($ep->number, 2, '0', STR_PAD_LEFT)
            . ' — ' . $ep->title;
    };
    $prevEpUrl   = $previousEpisode ? $previousEpisode->frontendUrl() : null;
    $nextEpUrl   = $nextEpisode     ? $nextEpisode->frontendUrl()     : null;
    $prevEpLabel = $epLabel($previousEpisode);
    $nextEpLabel = $epLabel($nextEpisode);

    // Site-wide default for the Episodes section layout, set in
    // Admin → Settings → General. A viewer's own toggle choice
    // (localStorage) overrides it client-side.
    $epDefaultLayout = setting('episode_layout_default', 'scroller') === 'grid' ? 'grid' : 'scroller';
@endphp

@section('content')

@if ($source)
    <link rel="stylesheet" href="{{ versioned_asset('frontend/css/player.css') }}">
    <script type="module" src="{{ versioned_asset('frontend/vendor/videojs-html/video-minimal-ui.js') }}"></script>
    <script src="{{ versioned_asset('frontend/js/jambo-settings-menu.js') }}" defer></script>
    <script src="{{ versioned_asset('frontend/js/jambo-player-gestures.js') }}" defer></script>

    <div class="jambo-watch-hero" id="jambo-player-hero">
        <div class="jambo-player-sentinel" id="jambo-player-sentinel"></div>
        <div class="jambo-player-frame" id="jambo-player-frame" style="position:absolute; inset:0;">
            <button type="button" class="jambo-mini-close" id="jambo-mini-close" aria-label="Close mini-player" title="Close">
                <i class="ph ph-x"></i>
            </button>
            @include('frontend::components.partials.jambo-minimal-player', [
                'playerSrc' => $source['url'],
                'playerSrcLow' => ($episode->streamSourceLow()['url'] ?? null),
                'playerPoster' => $poster,
                'playerId' => 'jambo-watch-player',
                'resumePosition' => $resumePosition ?? 0,
                'isSeries' => true,
                'prevEpisodeUrl' => $prevEpUrl,
                'nextEpisodeUrl' => $nextEpUrl,
                'prevEpisodeLabel' => $prevEpLabel,
                'nextEpisodeLabel' => $nextEpLabel,
                // Free episodes count guest views the same as free
                // movies. tier_required lives on the parent show,
                // so check that — episode rows don't carry their own.
                'guestCountTarget' => (!auth()->check() && empty($episode->season?->show?->tier_required))
                    ? ['type' => 'episode', 'id' => $episode->id]
                    : null,
            ])
        </div>
    </div>
@else
    <div class="jambo-watch-hero">
        <div class="jambo-player-frame d-flex align-items-center justify-content-center text-light">
            <div class="text-center p-5">
                <h3 class="mb-3">This episode isn't streamable yet.</h3>
                <p class="text-muted mb-4">No Video URL has been set for <strong>{{ $headline }}</strong>.</p>
                <a href="{{ route('frontend.series_detail', $show->slug) }}" class="btn btn-outline-light">← Back to series</a>
            </div>
        </div>
    </div>
@endif

<div class="details-part">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="trending-info pt-0 pb-0">
                    <div class="row justify-content-between">
                        <div class="col-xl-12 col-12 mb-auto">
                            @include('frontend::components.cards.movie-description', [
                                'moveName' => $headline,
                                'movieReleased' => $episode->published_at?->format('Y') ?: ($show->year ?? ''),
                                'movieViews' => number_format($show->views_count ?? 0) . ' ' . __('frontendplaylist.views'),
                                'isNotimdbRating' => true,
                                'movieDuration' => $episode->runtime_minutes ? $episode->runtime_minutes . ' min' : '—',
                                'isNotTVShowbadge' => true,
                                'isNotstartWatching' => true,
                                'isNotwatchList' => true,
                                'movieDescription' => $episode->synopsis,
                                'movieGenres' => $show->genres->pluck('name')->all(),
                                // Player is the CTA here — push Like / Share
                                // below the synopsis (same as /watch).
                                'actionsBelowDescription' => true,
                            ])

                            <div class="mt-3">
                                <small class="text-muted">{{ __('sectionTitle.from') ?? 'From' }}:</small>
                                <a href="{{ route('frontend.series_detail', $show->slug) }}" class="ms-1 text-primary text-decoration-none">{{ $show->title }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="overflow-hidden">
        @if ($seasons->count())
            <div class="show-episode section-padding">
                <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
                    <h5 class="main-title text-capitalize mb-0 fw-medium">{{ __('header.episodes') }}</h5>
                    {{-- Layout switch: card scroller vs compact number grid.
                         The grid exists for long seasons (90+ episodes) where
                         swiping a carousel to reach EP 67 is hopeless.
                         Site-wide default comes from Admin → Settings →
                         General (episode_layout_default); a viewer's own
                         toggle choice persists in localStorage and wins. --}}
                    <div class="btn-group jambo-ep-layout-toggle" role="group" aria-label="Episode layout">
                        <button type="button" class="btn btn-sm {{ $epDefaultLayout === 'scroller' ? 'active' : '' }}"
                                data-ep-layout="scroller" title="Card view"
                                aria-pressed="{{ $epDefaultLayout === 'scroller' ? 'true' : 'false' }}">
                            <i class="ph ph-cards"></i>
                        </button>
                        <button type="button" class="btn btn-sm {{ $epDefaultLayout === 'grid' ? 'active' : '' }}"
                                data-ep-layout="grid" title="Grid view (all episodes)"
                                aria-pressed="{{ $epDefaultLayout === 'grid' ? 'true' : 'false' }}">
                            <i class="ph ph-squares-four"></i>
                        </button>
                    </div>
                </div>
                <ul class="nav nav-pills custom-tab-slider episode-nav-btn gap-3 mb-4 pb-2" role="tablist">
                    @foreach ($seasons as $s)
                        <li class="nav-item">
                            <a class="nav-link {{ $s->id === $season->id ? 'active show' : '' }}" data-bs-toggle="pill"
                                href="#season-{{ $s->number }}" role="tab"
                                aria-selected="{{ $s->id === $season->id ? 'true' : 'false' }}">
                                {{ __('streamEpisode.season') }} {{ $s->number }}
                            </a>
                        </li>
                    @endforeach
                </ul>
                <div class="tab-content">
                    @foreach ($seasons as $s)
                        @php
                            // In the current season's tab, rotate the list
                            // so the episode the user is watching sits at
                            // the left edge. Episodes that come after it
                            // follow naturally, then the ones before it
                            // wrap around to the end. Other seasons keep
                            // their natural 1→N order.
                            $sEpisodes = $s->episodes->sortBy('number')->values();
                            if ($s->id === $season->id) {
                                $currentIdx = $sEpisodes->search(fn ($e) => $e->id === $episode->id);
                                if ($currentIdx !== false && $currentIdx > 0) {
                                    $sEpisodes = $sEpisodes->slice($currentIdx)
                                        ->concat($sEpisodes->slice(0, $currentIdx))
                                        ->values();
                                }
                            }
                        @endphp
                        <div id="season-{{ $s->number }}" class="tab-pane animated fadeInUp {{ $s->id === $season->id ? 'active show' : '' }}" role="tabpanel">
                            <div class="jambo-ep-scroller" @if ($epDefaultLayout === 'grid') hidden @endif>
                                <div class="card-style-slider">
                                    <div class="position-relative swiper swiper-card mt-4 mb-5 overflow-hidden" data-slide="5"
                                        data-laptop="5" data-tab="3" data-mobile="2" data-mobile-sm="2"
                                        data-autoplay="false" data-loop="false">
                                        <div class="p-0 swiper-wrapper m-0 list-inline">
                                            @foreach ($sEpisodes as $ep)
                                                <div class="swiper-slide {{ $ep->id === $episode->id ? 'is-playing' : '' }}">
                                                    @include('frontend::components.cards.episode-card', [
                                                        'episodePath' => $ep->frontendUrl($show),
                                                        'showImg' => $ep->still_url ?: 'media/episode/s1e1-the-buddha.webp',
                                                        'id' => $ep->id,
                                                        'episodeNumber' => 'S' . str_pad($s->number, 2, '0', STR_PAD_LEFT) . 'E' . str_pad($ep->number, 2, '0', STR_PAD_LEFT),
                                                        'episodTitle' => $ep->title,
                                                        'episodeTitlesText' => $ep->title,
                                                        'episodeDetailText' => $ep->synopsis ?: '',
                                                        'episodTime' => $ep->runtime_minutes ? $ep->runtime_minutes . 'm' : '—',
                                                    ])
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                            {{-- Compact number grid — natural 1→N order (no
                                 rotation: the grid is for jumping straight to
                                 a specific episode number). --}}
                            <div class="jambo-ep-grid mt-4 mb-5" @if ($epDefaultLayout !== 'grid') hidden @endif>
                                @foreach ($s->episodes->sortBy('number')->values() as $ep)
                                    <a href="{{ $ep->frontendUrl($show) }}"
                                       class="jambo-ep-grid__item {{ $ep->id === $episode->id ? 'is-playing' : '' }}"
                                       title="S{{ str_pad($s->number, 2, '0', STR_PAD_LEFT) }}E{{ str_pad($ep->number, 2, '0', STR_PAD_LEFT) }}{{ $ep->title ? ': ' . $ep->title : '' }}">
                                        @if ($ep->id === $episode->id)
                                            <i class="ph-fill ph-play-circle jambo-ep-grid__playing" aria-hidden="true"></i>
                                        @endif
                                        <span class="jambo-ep-grid__label">EP</span>
                                        <span class="jambo-ep-grid__num">{{ str_pad($ep->number, 2, '0', STR_PAD_LEFT) }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if (!empty($similarShows) && $similarShows->count())
            <div class="show-episode section-padding">
                <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
                    <h5 class="main-title text-capitalize mb-0 fw-medium">Similar Series</h5>
                </div>
                <div class="card-style-slider">
                    <div class="position-relative swiper swiper-card mt-4 mb-5" data-slide="8"
                        data-laptop="8" data-tab="4" data-mobile="3.5" data-mobile-sm="3.5"
                        data-autoplay="false" data-loop="false" data-navigation="true" data-pagination="true">
                        <ul class="p-0 swiper-wrapper m-0 list-inline">
                            @foreach ($similarShows as $sim)
                                <li class="swiper-slide">
                                    @include('frontend::components.cards.card-style', [
                                        'cardImage' => $sim->poster_url ?: 'media/vikings-portrait.webp',
                                        'cardTitle' => $sim->title,
                                        'movietime' => null,
                                        'cardLang' => 'English',
                                        'cardPath' => route('frontend.series_detail', $sim->slug),
                                        'cardGenres' => $sim->relationLoaded('genres') ? $sim->genres->take(2)->pluck('name')->all() : null,
                                        'productPremium' => (bool) $sim->tier_required,
                                        'watchableType' => 'show',
                                        'watchableId'   => $sim->id,
                                    ])
                                </li>
                            @endforeach
                        </ul>
                        <div class="swiper-button swiper-button-next d-none d-lg-block"></div>
                        <div class="swiper-button swiper-button-prev d-none d-lg-block"></div>
                    </div>
                </div>
            </div>
        @endif

        @if (!empty($recommendedShows) && $recommendedShows->count())
            <div class="show-episode section-padding">
                <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
                    <h5 class="main-title text-capitalize mb-0 fw-medium">{{ __('sectionTitle.recommended_tv_show') ?? 'Recommended Series' }}</h5>
                </div>
                <div class="card-style-slider">
                    <div class="position-relative swiper swiper-card mt-4 mb-5" data-slide="8"
                        data-laptop="8" data-tab="4" data-mobile="3.5" data-mobile-sm="3.5"
                        data-autoplay="false" data-loop="false" data-navigation="true" data-pagination="true">
                        <ul class="p-0 swiper-wrapper m-0 list-inline">
                            @foreach ($recommendedShows as $rec)
                                <li class="swiper-slide">
                                    @include('frontend::components.cards.card-style', [
                                        'cardImage' => $rec->poster_url ?: 'media/vikings-portrait.webp',
                                        'cardTitle' => $rec->title,
                                        'movietime' => null,
                                        'cardLang' => 'English',
                                        'cardPath' => route('frontend.series_detail', $rec->slug),
                                        'cardGenres' => $rec->relationLoaded('genres') ? $rec->genres->take(2)->pluck('name')->all() : null,
                                        'productPremium' => (bool) $rec->tier_required,
                                        'watchableType' => 'show',
                                        'watchableId'   => $rec->id,
                                    ])
                                </li>
                            @endforeach
                        </ul>
                        <div class="swiper-button swiper-button-next d-none d-lg-block"></div>
                        <div class="swiper-button swiper-button-prev d-none d-lg-block"></div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

{{-- Episode layout toggle: card scroller vs compact number grid.
     Inlined (like the footer's scoped CSS) so deploys don't need a
     Vite rebuild to pick up changes. --}}
<style>
    .jambo-ep-layout-toggle .btn {
        color: rgba(255, 255, 255, 0.55);
        border: 1px solid rgba(255, 255, 255, 0.15);
        background: transparent;
        font-size: 17px;
        line-height: 1;
        padding: 7px 12px;
    }
    .jambo-ep-layout-toggle .btn.active {
        color: #fff;
        background: var(--bs-primary);
        border-color: var(--bs-primary);
    }
    .jambo-ep-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(86px, 1fr));
        gap: 12px;
    }
    .jambo-ep-grid__item {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 2px;
        padding: 14px 6px;
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.02);
        color: #fff;
        text-decoration: none;
        transition: border-color 0.15s ease, background 0.15s ease, transform 0.15s ease;
    }
    .jambo-ep-grid__item:hover,
    .jambo-ep-grid__item:focus {
        color: #fff;
        border-color: var(--bs-primary);
        background: rgba(255, 255, 255, 0.06);
        transform: translateY(-2px);
    }
    .jambo-ep-grid__item.is-playing {
        background: var(--bs-primary);
        border-color: var(--bs-primary);
    }
    .jambo-ep-grid__label {
        font-size: 11px;
        letter-spacing: 0.08em;
        color: var(--bs-primary);
        font-weight: 600;
    }
    .jambo-ep-grid__item.is-playing .jambo-ep-grid__label { color: rgba(255, 255, 255, 0.85); }
    .jambo-ep-grid__num {
        font-size: 20px;
        font-weight: 700;
        line-height: 1.1;
    }
    .jambo-ep-grid__playing {
        position: absolute;
        top: 6px;
        left: 8px;
        font-size: 18px;
    }
</style>
<script>
(function () {
    var KEY = 'jambo.episodeLayout';
    var serverDefault = @json($epDefaultLayout ?? 'scroller');
    var buttons = document.querySelectorAll('[data-ep-layout]');
    if (!buttons.length) return;

    function apply(layout) {
        document.querySelectorAll('.jambo-ep-scroller').forEach(function (el) {
            el.hidden = layout === 'grid';
        });
        document.querySelectorAll('.jambo-ep-grid').forEach(function (el) {
            el.hidden = layout !== 'grid';
        });
        buttons.forEach(function (btn) {
            var on = btn.dataset.epLayout === layout;
            btn.classList.toggle('active', on);
            btn.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
        // Swiper measures 0 while hidden; poke it after re-showing.
        if (layout !== 'grid') window.dispatchEvent(new Event('resize'));
    }

    buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var layout = btn.dataset.epLayout;
            try { localStorage.setItem(KEY, layout); } catch (e) {}
            apply(layout);
            // With 90+ episodes the playing one can sit way below the
            // fold of the grid — nudge it into view on switch. Never on
            // page load: that would scroll the viewer away from the
            // player they came to watch.
            if (layout === 'grid') {
                var playing = document.querySelector('.tab-pane.active .jambo-ep-grid__item.is-playing');
                if (playing && playing.scrollIntoView) {
                    playing.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                }
            }
        });
    });

    // The admin-chosen default is already server-rendered (no flash).
    // A viewer's own explicit choice, saved on a previous page, wins.
    var saved = null;
    try { saved = localStorage.getItem(KEY); } catch (e) {}
    if ((saved === 'grid' || saved === 'scroller') && saved !== serverDefault) {
        apply(saved);
    }
})();
</script>

{{-- Reviews & ratings — attached to the parent show, rendered here
     (not on the series detail page) so viewers can rate right after
     finishing an episode. --}}
@include('frontend::components.partials.reviews-block', [
    'storeRoute'   => route('frontend.series_review_store',   $show->slug),
    'destroyRoute' => route('frontend.series_review_destroy', $show->slug),
])

{{-- Comments thread --}}
@include('frontend::components.partials.comments-block', [
    'storeRoute'      => route('frontend.episode_comment_store', $episode->id),
    'destroyRouteFn'  => fn ($c) => route('frontend.comment_destroy', $c->id),
])

@include('frontend::components.widgets.mobile-footer')

@if ($source)
<script>
document.addEventListener('DOMContentLoaded', async function () {
    await customElements.whenDefined('video-player');

    const video = document.getElementById('jambo-watch-player');
    const frame = document.getElementById('jambo-player-frame');
    const closeBtn = document.getElementById('jambo-mini-close');
    if (!video) return;

    // Nudge playback in case the partial's early-play attempt lost a
    // race with the custom-element upgrade. Don't touch .muted here —
    // the player partial owns the sound-on-by-default logic and its
    // muted-autoplay fallback.
    const playAttempt = video.play();
    if (playAttempt && typeof playAttempt.catch === 'function') playAttempt.catch(() => {});

    if (typeof window.jamboAttachSettingsMenu === 'function') {
        window.jamboAttachSettingsMenu('jambo-watch-player');
    }
    if (typeof window.jamboAttachGestures === 'function') {
        window.jamboAttachGestures('jambo-watch-player');
    }

    // Autoplay-next preference lives in localStorage; the pill switch
    // in the control bar is the single source of truth. We only read
    // it on `ended` below.
    const AUTOPLAY_KEY = 'jambo.autoplayNext';

    // Mutable so the fullscreen in-place swap can update them. Payable
    // id is what the heartbeat writes against.
    let payableId = {{ Js::from($episode->id) }};
    let nextEpisodeId = {{ Js::from($nextEpisode ? $nextEpisode->id : null) }};
    let nextEpisodeUrl = {{ Js::from($nextEpisode ? $nextEpisode->frontendUrl() : null) }};
    let prevEpisodeId = {{ Js::from($previousEpisode ? $previousEpisode->id : null) }};
    let prevEpisodeUrl = {{ Js::from($previousEpisode ? $previousEpisode->frontendUrl() : null) }};

    // Guests don't have watch history — skip the heartbeat loop.
    const isAuthed = {{ auth()->check() ? 'true' : 'false' }};
    const heartbeatUrl = {{ Js::from(url('/api/v1/streaming/heartbeat')) }};
    const playerDataBase = {{ Js::from(url('/api/v1/episodes')) }}; // + /{id}/player-data
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    let lastPosition = 0;
    let duration = null;

    video.addEventListener('timeupdate', () => {
        lastPosition = video.currentTime || 0;
        duration = Number.isFinite(video.duration) ? video.duration : null;
    });

    let heartbeatTimer = null;

    async function sendHeartbeat() {
        if (!isAuthed) return;
        if (window.jamboKicked) return;
        if (lastPosition <= 0) return;
        try {
            const res = await fetch(heartbeatUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    payable_type: 'episode',
                    payable_id: payableId,
                    position: Math.floor(lastPosition),
                    duration: duration ? Math.floor(duration) : null,
                }),
            });
            const handled = await window.jamboHandleHeartbeatResponse?.(res);
            if (handled && heartbeatTimer) {
                clearInterval(heartbeatTimer);
                heartbeatTimer = null;
            }
        } catch (e) { console.debug('[watch] heartbeat failed', e); }
    }
    if (isAuthed) {
        video.addEventListener('pause', sendHeartbeat);
        heartbeatTimer = setInterval(sendHeartbeat, 15000);
        window.addEventListener('pagehide', sendHeartbeat);
    }

    // ------------------------------------------------------------------
    // In-place episode swap (fullscreen only).
    //
    // Full page navigation always drops fullscreen per browser security
    // rules, so when the user hits next / prev / autoplays while in
    // fullscreen we instead fetch the target episode's player data and
    // swap <video>.src in place. history.pushState keeps the URL bar
    // honest, and the heartbeat + button refs follow along.
    //
    // When NOT in fullscreen, we fall back to a normal navigation so
    // the page chrome below the player (episode carousel, title,
    // synopsis) stays in sync with what's playing.
    // ------------------------------------------------------------------
    const prevEpBtn = document.querySelector('[data-episode-nav="prev"]');
    const nextEpBtn = document.querySelector('[data-episode-nav="next"]');
    const autoplayToggle = document.querySelector('button[data-action="toggle-autoplay-next"]');

    function updateAutoplayTooltip(label) {
        const tip = document.querySelector('#jambo-watch-player-autoplay-tooltip');
        if (tip && label) tip.textContent = 'Autoplay next · ' + label;
    }

    async function swapToEpisode(targetId) {
        if (!targetId) return false;
        try {
            // Flush current progress BEFORE we clobber payableId so the
            // heartbeat targets the outgoing episode.
            await sendHeartbeat();

            const res = await fetch(playerDataBase + '/' + targetId + '/player-data', {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) return false;
            const data = await res.json();
            if (!data.videoUrl) return false;

            // Swap source. Using the data-src-default path mirrors what
            // the partial's load-time script does, so quality switches
            // keep working after a swap.
            video.dataset.srcDefault = data.videoUrl;
            if (data.videoUrlLow) {
                video.dataset.srcLow = data.videoUrlLow;
            } else {
                delete video.dataset.srcLow;
            }
            const quality = localStorage.getItem('jambo.quality') || 'default';
            video.src = (quality === 'low' && data.videoUrlLow) ? data.videoUrlLow : data.videoUrl;
            video.load();
            // Resume position (if any).
            const resume = data.resumePosition || 0;
            if (resume > 0) {
                video.addEventListener('loadedmetadata', function onMeta(){
                    video.removeEventListener('loadedmetadata', onMeta);
                    if (video.duration && resume < video.duration - 30) {
                        video.currentTime = resume;
                    }
                });
            }
            const p = video.play();
            if (p && typeof p.catch === 'function') p.catch(() => {});

            // Update rolling state.
            payableId = data.id;
            nextEpisodeId = data.nextEpisode ? data.nextEpisode.id : null;
            nextEpisodeUrl = data.nextEpisode ? data.nextEpisode.url : null;
            prevEpisodeId = data.previousEpisode ? data.previousEpisode.id : null;
            prevEpisodeUrl = data.previousEpisode ? data.previousEpisode.url : null;
            lastPosition = 0;
            duration = null;

            // Update button hrefs + disabled state so subsequent clicks
            // (still in fullscreen) continue to work.
            if (nextEpBtn) {
                if (nextEpisodeUrl) { nextEpBtn.setAttribute('href', nextEpisodeUrl); nextEpBtn.classList.remove('is-disabled'); }
                else                { nextEpBtn.removeAttribute('href'); nextEpBtn.classList.add('is-disabled'); }
            }
            if (prevEpBtn) {
                if (prevEpisodeUrl) { prevEpBtn.setAttribute('href', prevEpisodeUrl); prevEpBtn.classList.remove('is-disabled'); }
                else                { prevEpBtn.removeAttribute('href'); prevEpBtn.classList.add('is-disabled'); }
            }
            updateAutoplayTooltip(data.nextEpisode ? data.nextEpisode.label : null);

            // URL + title — keep the back button honest.
            if (data.detailUrl) history.pushState(null, '', data.detailUrl);
            if (data.title) document.title = data.title + ' — ' + data.showTitle;

            return true;
        } catch (e) {
            console.debug('[watch] in-place swap failed', e);
            return false;
        }
    }

    function inFullscreen() {
        return !!(document.fullscreenElement || document.webkitFullscreenElement);
    }

    // Intercept prev/next link clicks while fullscreen — swap in place
    // instead of navigating.
    [prevEpBtn, nextEpBtn].forEach(btn => {
        if (!btn) return;
        btn.addEventListener('click', async (e) => {
            if (!inFullscreen()) return; // regular nav
            e.preventDefault();
            e.stopPropagation();
            const isNext = btn === nextEpBtn;
            const swapped = await swapToEpisode(isNext ? nextEpisodeId : prevEpisodeId);
            // The in-place swap can fail (guest session — the
            // player-data endpoint is auth-only — premium gate,
            // network blip). Never swallow the click: fall back to a
            // normal navigation. It drops fullscreen, but the episode
            // still changes, which is what the user asked for.
            if (!swapped) {
                const url = isNext ? nextEpisodeUrl : prevEpisodeUrl;
                if (url) window.location.href = url;
            }
        });
    });

    // Autoplay-next on end: in-place if fullscreen, otherwise navigate.
    video.addEventListener('ended', async function () {
        await sendHeartbeat();
        if (!nextEpisodeId || localStorage.getItem(AUTOPLAY_KEY) !== '1') return;
        if (inFullscreen()) {
            const swapped = await swapToEpisode(nextEpisodeId);
            if (!swapped && nextEpisodeUrl) window.location.href = nextEpisodeUrl;
        } else if (nextEpisodeUrl) {
            window.location.href = nextEpisodeUrl;
        }
    });

    // Mini-mode: observe the stable sentinel, portal the frame to <body>
    // (see watch-page.blade.php for the full rationale).
    const sentinel = document.getElementById('jambo-player-sentinel');
    const hero = document.getElementById('jambo-player-hero');

    function enterMini() {
        if (frame.classList.contains('jambo-player-frame--mini')) return;
        document.body.appendChild(frame);
        frame.style.position = '';
        frame.style.inset = '';
        frame.classList.add('jambo-player-frame--mini');
    }
    function exitMini() {
        if (!frame.classList.contains('jambo-player-frame--mini')) return;
        frame.classList.remove('jambo-player-frame--mini');
        frame.style.position = 'absolute';
        frame.style.inset = '0';
        if (hero) hero.appendChild(frame);
    }

    // Same fullscreen-toggle settle suppression as watch-page so the
    // post-fullscreen reflow can't trick the observer into flickering
    // mini-mode for a frame.
    let suppressMiniUntil = 0;
    document.addEventListener('fullscreenchange', () => {
        suppressMiniUntil = Date.now() + 600;
    });

    if ('IntersectionObserver' in window && sentinel) {
        const io = new IntersectionObserver((entries) => {
            if (document.fullscreenElement) return;
            if (Date.now() < suppressMiniUntil) return;
            const r = entries[0].intersectionRatio;
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
});
</script>
@endif

{{-- "Read more" details sheet for the Read more link inside
     movie-description. Composed from episode + parent show so
     the modal reflects the specific episode you're watching. --}}
@include('frontend::components.widgets.details-description-modal', [
    'movieName'     => $headline,
    'description'   => $episode->synopsis ?: $show->synopsis,
    'year'          => $episode->published_at?->format('Y') ?: ($show->year ?? null),
    'views'         => number_format($show->views_count ?? 0) . ' ' . __('streamTag.views'),
    'movieDuration' => $episode->runtime_minutes ? $episode->runtime_minutes . ' min' : null,
    'ratingCount'   => null, // episodes don't carry an IMDb rating of their own
    'genres'        => $show->genres->pluck('name')->all(),
    'tags'          => $show->relationLoaded('tags') ? $show->tags->pluck('name')->all() : [],
    // Dedupe by person id — same fix as the movie/show detail pages.
    // A single person with multiple pivot rows (e.g., both an actor
    // and a director credit) would otherwise show up twice in the
    // Read-more modal's cast / crew lists.
    'cast'          => $show->cast
        ->filter(fn ($p) => in_array(($p->pivot->role ?? null), ['actor', 'actress'], true))
        ->unique('id')
        ->values(),
    'crew'          => $show->cast
        ->filter(fn ($p) => in_array(($p->pivot->role ?? null), ['director', 'writer', 'producer']))
        ->unique('id')
        ->values(),
])

{{-- Device-limit kick overlay (see partial for what it does). Only
     included when a source exists — there's no heartbeat loop on a
     non-streamable episode page to signal a kick. --}}
@if ($source)
    @include('frontend::components.partials.jambo-kick-overlay')
@endif
@endsection
