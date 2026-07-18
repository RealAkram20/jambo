{{-- Action row for movie-description: Start watching / Watch List /
     Like / Share. Extracted so the parent can place it either above the
     title (detail pages — the CTA has to be the first thing you see) or
     below the synopsis (watch pages — the player is already the CTA, so
     these are secondary). Inherits every variable from the caller. --}}
<div class="d-flex align-items-center flex-wrap gap-3 gap-md-4 {{ $actionsBelowDescription ?? false ? 'mt-3 mb-4' : 'mb-4' }}">
    @if(empty($isNotstartWatching))
        <div class="iq-play-button iq-button">
            @php
                $isUpcoming = !empty($isUpcoming);
                // An upcoming title has no stream yet — neither "Start
                // watching" nor "Subscribe to watch" apply. Show a
                // disabled "Coming soon" pill with the release date (or
                // "date TBA" when the admin hasn't set `published_at`).
                //
                // Component is used from both movie + TV show detail
                // pages, so only $movie OR $show is defined at any time
                // — probe both before giving up on a release label.
                $releaseLabel = null;
                if ($isUpcoming) {
                    $releaseDate = (isset($movie) && $movie->published_at) ? $movie->published_at : null;
                    if (!$releaseDate && isset($show) && $show->published_at) {
                        $releaseDate = $show->published_at;
                    }
                    if ($releaseDate instanceof \DateTimeInterface) {
                        $releaseLabel = $releaseDate->format('M j, Y');
                    }
                }
            @endphp
            @if($isUpcoming)
                <button type="button" disabled
                    class="btn btn-outline-primary w-100 rounded d-flex align-items-center justify-content-center gap-2 lh-1"
                    style="cursor: not-allowed; opacity: 0.85;"
                    aria-label="Coming soon">
                    <i class="ph-fill ph-calendar-check fs-6"></i>
                    <span>
                        {{ __('streamTag.coming_soon') !== 'streamTag.coming_soon' ? __('streamTag.coming_soon') : 'Coming soon' }}
                        @if ($releaseLabel)
                            <span class="opacity-75">· {{ $releaseLabel }}</span>
                        @endif
                    </span>
                </button>
            @elseif(!auth()->check() && setting('require_signup_to_watch'))
                {{-- Site-wide "require sign-up to watch" is ON and this is a
                     guest: the whole catalogue is gated behind an account, so
                     there's nothing to subscribe to yet — prompt them to sign
                     in / create an account instead of pushing pricing. Linking
                     to the watch URL lets the route's guest handler stash it as
                     the post-login intended target (redirect()->guest()), so
                     after signing in they land back here and can start watching. --}}
                <a href="{{ $videoUrl ?? route('login') }}" class="btn btn-primary w-100 rounded d-flex align-items-center justify-content-center gap-2 lh-1">
                    <i class="ph-fill ph-sign-in fs-6"></i>
                    <span>{{__('streamButtons.signin_watch')}}</span>
                </a>
            @elseif(!empty($subscribeToWatch))
                <a href="{{ route('frontend.pricing-page') }}" class="btn btn-primary w-100 rounded d-flex align-items-center justify-content-center gap-2 lh-1">
                    <i class="ph-fill ph-crown fs-6"></i>
                    <span>{{__('streamButtons.subscribe_watch')}}</span>
                </a>
            @else
                <a href="{{ $videoUrl }}" class="btn btn-primary w-100 rounded d-flex align-items-center justify-content-center gap-2 lh-1">
                    <i class="ph-fill ph-play"></i>
                    <span>{{__('streamButtons.start_watching')}}</span>
                </a>
            @endif
        </div>
    @endif

    @if(empty($isNotwatchList))
        @php
            $watchableType = $watchableType ?? null;
            $watchableId   = $watchableId   ?? null;
            $userWatchlistIndex = $userWatchlistIndex ?? [];
            $isInWatchlist = $watchableType && $watchableId
                && isset($userWatchlistIndex[$watchableType . ':' . $watchableId]);
            $watchlistIcon = $isInWatchlist ? 'ph-check' : 'ph-plus';
            $watchlistTooltip = $isInWatchlist
                ? (__('streamPlaylist.remove_from_watchlist') ?? 'Remove from watchlist')
                : __('sectionTitle.add_to_watchlist_tooltip');
            $watchlistLabel = $isInWatchlist
                ? (__('streamTag.in_watch_list') ?? 'In Watchlist')
                : __('streamTag.watch_lists');
        @endphp
        <div class="watchlist-button-wrapper">
            @if ($watchableType && $watchableId)
                {{-- Real toggle — global delegate in layouts/master.blade.php
                     handles the POST and flips the icon + label. --}}
                <button type="button"
                    class="btn btn-secondary border rounded-3 jambo-watchlist-toggle-btn {{ $isInWatchlist ? 'is-in-watchlist' : '' }}"
                    data-watchable-type="{{ $watchableType }}"
                    data-watchable-id="{{ $watchableId }}"
                    data-watchlist-label-add="{{ __('streamTag.watch_lists') }}"
                    data-watchlist-label-remove="{{ __('streamTag.in_watch_list') ?? 'In Watchlist' }}"
                    data-bs-toggle="tooltip" data-bs-placement="top"
                    data-bs-title="{{ $watchlistTooltip }}">
                    <span class="d-flex align-items-center justify-content-center gap-2">
                        <span class="fw-semibold"><i class="ph {{ $watchlistIcon }}"></i></span>
                        <span class="fw-semibold jambo-watchlist-label">{{ $watchlistLabel }}</span>
                    </span>
                </button>
            @else
                {{-- Fallback when the caller hasn't passed watchableType/Id
                     (guests, legacy pages). Keep the affordance visible but
                     nudge to sign in instead of navigating into a dead end. --}}
                <button type="button" class="btn btn-secondary border rounded-3" data-bs-toggle="tooltip"
                    data-bs-placement="top" title="Sign in to save"
                    onclick="event.preventDefault();">
                    <span class="d-flex align-items-center justify-content-center gap-2">
                        <span class="fw-semibold"><i class="ph ph-plus"></i></span>
                        <span class="fw-semibold">{{__('streamTag.watch_lists')}}</span>
                    </span>
                </button>
            @endif
        </div>
    @endif

    <div class="d-flex align-items-center gap-3 flex-wrap">
        @if(empty($isNotLikemovies))
            <button type="button" class="action-btn btn btn-secondary border" data-bs-toggle="modal" data-bs-target="#likeModal" id="like-toggle">
                <span id="like-movies">
                    <span class="h-100 w-100 d-block" data-bs-toggle="tooltip" data-bs-placement="top" title="{{__('streamTag.like')}}">
                        <i class="ph ph-heart heart-icon"></i>
                    </span>
                </span>
            </button>
        @endif

        @if(empty($isNotSharenetwork))
            <button type="button" class="action-btn btn btn-secondary border" data-bs-toggle="modal" data-bs-target="#shareModal">
                <span class="h-100 w-100 d-block" data-bs-toggle="tooltip" data-bs-placement="top" title="{{__('streamTag.share')}}">
                    <i class="ph ph-share-network"></i>
                </span>
            </button>
        @endif

    </div>
</div>
