<!-- Movie Description Start-->
{{-- Genre chips. Render as plain spans until callers start passing
     genre slugs alongside names — there's no slug here to link to. --}}
@if (isset($movieGenres) && count($movieGenres))
    <ul class="p-0 mb-2 list-inline d-flex flex-wrap movie-tag">
        @foreach ($movieGenres as $g)
            <li class="trending-list"><span>{{ $g }}</span></li>
        @endforeach
    </ul>
@endif
<div class="d-block d-lg-flex align-items-center">
    @if(isset($isnotmovieTitle) && $isnotmovieTitle)
        <h5 class="css_prefix-title text-capitalize line-count-1">
            {{-- Plain heading: the surrounding card already links the
                 poster to the detail page, wrapping the title in a
                 dummy anchor added nothing. --}}
            {{ $moveName }}
        </h5>
    @else
        <h3 class="trending-text fw-bold texture-text text-uppercase my-0 fadeInLeft animated d-inline-block"
            data-animation-in="fadeInLeft" data-delay-in="0.6" style="opacity: 1; animation-delay: 0.6s">
            {{ $moveName }}
        </h3>
    @endif
</div>
<div class="movie-description mt-3 mb-4" id="readmore-wrapper">
    <p class="line-count-3 RightAnimate-two mb-0">
        {{ $movieDescription ?? __('streamMovies.game_of_heros_desc') }}
    </p>
    <div class="iq-blog-meta-cat-tag iq-blogtag readmore-tags">
        <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#viewMoreDataModal" class="position-relative">{{__('frontendform.read_more')}}</a>
    </div>
</div>

@if(isset($isVideoPageData) && $isVideoPageData)
    <ul class="list-inline mx-0 p-0 d-flex align-items-center flex-wrap gap-3 movie-metalist">
        <li>
            <div class="d-flex align-items-center gap-1">
                <i class="ph ph-eye"></i>
                <span>{{ $movieViews }}</span>
            </div>
        </li>
        <li>
            <span class="d-flex align-items-center gap-1">
                <span class="d-flex align-items-center justify-content-center"><i class="ph ph-clock"></i></span>
                {{ $movieDuration }}
            </span>
        </li>
        <li>
            <span class="d-flex align-items-center gap-1">
                <span class="fw-medium">{{ $movieReleased }}</span>
            </span>
        </li>
    </ul>
@else
    <ul class="list-inline mb-0 mx-0 p-0 d-flex align-items-center flex-wrap gap-3 movie-metalist">
        <li>
            <span class="d-flex align-items-center gap-1">
                <span class="fw-medium">{{ $movieReleased }}</span>
            </span>
        </li>
        @if(empty($isNotClockduration))
            <li>
                <span class="d-flex align-items-center gap-1">
                    <span class="d-flex align-items-center justify-content-center"><i class="ph ph-clock"></i></span>
                    {{ $movieDuration }}
                </span>
            </li>
        @endif
        <li>
            <div class="d-flex align-items-center gap-1">
                <i class="ph ph-eye"></i>
                <span>{{ $movieViews }}</span>
            </div>
        </li>
        @if(empty($isNotimdbRating))
            <li>
                <span class="d-flex align-items-center gap-1">
                    <span class="fw-medium">
                        <span>{{ $imdbRating }}</span>
                        <span class="imdb-logo ms-1">
                            <img src="{{ asset('frontend/images/pages/imdb-logo.svg') }}" loading="lazy" decoding="async" alt="imdb logo" class="img-fluid imdb-logo1">
                        </span>
                    </span>
                </span>
            </li>
        @endif
        @if(empty($isNotTVShowbadge))
            <li>
                <span class="badge bg-secondary d-flex align-items-center gap-2 fw-bold font-size-12 movie-type-tag">
                    <span>{{ $movieType }}</span>
                </span>
            </li>
        @endif
    </ul>
@endif

@if(!empty($movieLanguage))
    <div class="video-language d-flex align-items-center gap-1 mt-2">
        <i class="ph ph-translate"></i>
        <ul class="list-inline m-0 p-0 d-inline-flex align-items-center gap-3 flex-wrap">
            <li>
                <small class="text-capitalize">{{ $movieLanguage }}</small>
            </li>
            {{-- Add more languages if needed --}}
        </ul>
    </div>
@endif

<div class="d-flex align-items-center flex-wrap gap-3 gap-md-4 my-5">
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
<!-- Movie Description End -->

<!-- Modals -->
@include('frontend::components.widgets.share-modal')
<!-- Modals End -->
