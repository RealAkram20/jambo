{{--
    Single watchlist grid cell.

    New signature (preferred — used by /watchlist-detail):
      $item : Modules\Streaming\app\Models\WatchlistItem (watchable preloaded)
      $kind : 'movie' | 'show' | 'episode'

    Legacy signature (template-only pages like profile-marvin that
    haven't been wired to real data yet):
      $cardImage : string — path under frontend/images/
      $movieName : string — display title
--}}
@php
    $item = $item ?? null;
    $kind = $kind ?? 'movie';
    $cardImage = $cardImage ?? null;
    $movieName = $movieName ?? null;
@endphp

@if ($item)
    @php
        $watchable = $item->watchable;
    @endphp
    @if ($watchable)
        @php
            $fallback = match ($kind) {
                'show'    => 'frontend/images/media/vikings-portrait.webp',
                'episode' => 'frontend/images/media/movie-detail.webp',
                default   => 'frontend/images/media/rabbit-portrait.webp',
            };

            $poster = $watchable->poster_url
                ?: ($kind === 'episode' ? ($watchable->still_url ?: ($watchable->season->show->poster_url ?? null)) : null);
            $imgSrc = $poster ?: asset($fallback);

            $title = $kind === 'episode'
                ? 'S' . str_pad($watchable->season->number ?? 0, 2, '0', STR_PAD_LEFT)
                    . 'E' . str_pad($watchable->number ?? 0, 2, '0', STR_PAD_LEFT)
                    . ' — ' . ($watchable->title ?? '')
                : ($watchable->title ?? '');

            // Shows open the series page (pick an episode).
            // Episodes open the episode watch page directly.
            // Movies open the pretty /watchlist/{slug} queue player.
            $detailUrl = match ($kind) {
                'show'    => route('frontend.series_detail', $watchable->slug),
                'episode' => $watchable->frontendUrl(),
                default   => route('frontend.watchlist_play', $watchable->slug),
            };
        @endphp
        <div class="common_card" data-watchlist-item="{{ $item->id }}">
            <div class="image-box w-100">
                <a href="{{ $detailUrl }}" class="d-block">
                    <img decoding="async" src="{{ $imgSrc }}" alt="{{ $title }}" class="img-fluid">
                </a>
            </div>
            <div class="css_prefix-detail-part">
                <h6 class="text-capitalize line-count-1 mb-0">
                    <a href="{{ $detailUrl }}" class="color-inherit">{{ $title }}</a>
                </h6>
                <button type="button"
                        class="btn in-watchlist btn-secondary watch-list-btn jambo-watchlist-remove-btn"
                        data-watchlist-id="{{ $item->id }}"
                        data-bs-toggle="tooltip"
                        data-bs-title="{{ __('streamPlaylist.remove_from_watchlist') ?? 'Remove from watchlist' }}"
                        data-bs-placement="top" tabindex="0">
                    <i class="icon-check-2"></i>
                </button>
            </div>
        </div>
    @endif
@else
    {{-- Legacy placeholder render for pages not yet wired to real
         watchlist data. No remove button — nothing to remove. --}}
    <div class="common_card">
        <div class="image-box w-100">
            <a href="{{ route('frontend.series') }}" class="d-block">
                <img decoding="async" src="{{ asset('frontend/images/' . ($cardImage ?? '')) }}" alt="{{ $movieName ?? '' }}" class="img-fluid">
            </a>
        </div>
        <div class="css_prefix-detail-part">
            <h6 class="text-capitalize line-count-1 mb-0">
                <a href="{{ route('frontend.series') }}" class="color-inherit">{{ $movieName ?? '' }}</a>
            </h6>
        </div>
    </div>
@endif
