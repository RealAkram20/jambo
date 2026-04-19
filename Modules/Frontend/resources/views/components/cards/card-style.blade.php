@php
    // Set default values for all variables to prevent undefined errors
    $cardPath = $cardPath ?? '#';
    $cardImage = $cardImage ?? '';
    $cardTitle = $cardTitle ?? '';
    $movietime = $movietime ?? null;
    $cardLang = $cardLang ?? 'English';
    $cardYear = $cardYear ?? null;
    $productPremium = $productPremium ?? false;
    $isCardStyle2 = $isCardStyle2 ?? false;
    $addlist = $addlist ?? false;
    $isnotlangCard = $isnotlangCard ?? false;
    $cardGenres = $cardGenres ?? null;
    $watchableType = $watchableType ?? null;
    $watchableId = $watchableId ?? null;
    $imgSrc = \Illuminate\Support\Str::startsWith($cardImage, ['http://', 'https://'])
        ? $cardImage
        : asset('frontend/images/' . $cardImage);

    // Lookup the "in watchlist" state from the per-request index
    // shared by SectionDataComposer. Guests always see "+".
    $userWatchlistIndex = $userWatchlistIndex ?? [];
    $isInWatchlist = $watchableType && $watchableId
        && isset($userWatchlistIndex[$watchableType . ':' . $watchableId]);
    $watchlistIcon = $isInWatchlist ? 'ph-check' : 'ph-plus';
    $watchlistTooltip = $isInWatchlist
        ? (__('streamPlaylist.remove_from_watchlist') ?? 'Remove from watchlist')
        : __('sectionTitle.add_to_watchlist_tooltip');
@endphp

@if ($isCardStyle2)
<div class="iq-card card-hover landscape-card-hover">
  <div class="block-images position-relative w-100">
    <div class="img-box w-100">
      <a href="{{ $cardPath }}" class="position-relative top-0 bottom-0 start-0 end-0">
        <img src="{{ $imgSrc }}" alt="movie-card"
          class="img-fluid object-cover w-100 d-block border-0 rounded-3">
      </a>
    </div>
    <div class="card-description with-transition">
      {{-- Genre chips. Shown only when the caller actually passes
           genre names — no placeholder text. They render as spans
           because we only receive names, not slugs; until callers
           pass slugs, there's nothing to link to. --}}
      @if ($cardGenres)
        <ul class="genres-list p-0 mb-2 d-flex align-items-center flex-wrap list-inline">
          @foreach ($cardGenres as $g)
            <li class="fw-semi-bold">
              <span tabindex="0" class="font-size-14">{{ $g }}</span>
            </li>
          @endforeach
        </ul>
      @endif
      <div class="cart-content">
        <div class="content-left">
          <h5 class="iq-title text-capitalize mb-0">
            <a href="{{ $cardPath }}">{{ $cardTitle }}</a>
          </h5>
        </div>
      </div>
      <div class="d-flex align-items-center justify-content-center gap-2 mt-3">
        @if (!empty($watchableType) && !empty($watchableId))
          {{-- Real watchlist toggle. Initial icon reflects the server-side
               index from SectionDataComposer; the shared JS delegate in
               layouts/master.blade.php flips it after a successful POST. --}}
          <button type="button"
            class="d-flex align-items-center justify-content-center flex-shrink-0 border-0 add-to-wishlist-btn btn btn-secondary jambo-watchlist-toggle-btn {{ $isInWatchlist ? 'is-in-watchlist' : '' }}"
            data-watchable-type="{{ $watchableType }}"
            data-watchable-id="{{ $watchableId }}"
            data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip"
            data-bs-title="{{ $watchlistTooltip }}">
            <i class="ph {{ $watchlistIcon }} font-size-18"></i>
          </button>
        @else
          {{-- Fallback when the caller hasn't passed watchableType/Id
               (guest users, legacy pages). Show the affordance but
               don't navigate anywhere — tooltip nudges sign-in so the
               click isn't a silent dead end. --}}
          <button type="button"
            class="d-flex align-items-center justify-content-center flex-shrink-0 border-0 add-to-wishlist-btn btn btn-secondary"
            data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip"
            data-bs-title="Sign in to save"
            onclick="event.preventDefault();">
            <i class="ph ph-plus font-size-18"></i>
          </button>
        @endif
        <div class="iq-play-button iq-button">
          <a href="{{ $cardPath }}" class="btn btn-primary w-100">{{__('streamButtons.play_now')}}</a>
        </div>
      </div>
    </div>
    @if ($productPremium)
    <div class="position-absolute z-1 premium-product d-flex align-items-center justify-content-center"
      data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Premium" data-bs-original-title="{{__('streamPricing.premium')}}">
      <i class="ph-fill ph-crown"></i>
    </div>
    @endif
  </div>
</div>
@else
<div class="iq-card card-hover">
  <div class="block-images position-relative w-100">
    <div class="img-box w-100">
      <a href="{{ $cardPath }}" class="position-relative top-0 bottom-0 start-0 end-0">
        <img src="{{ $imgSrc }}" alt="movie-card"
          class="img-fluid object-cover w-100 d-block border-0 rounded-3">
      </a>
    </div>
    <div class="card-description with-transition">
      @if ($cardGenres)
        <ul class="genres-list p-0 mb-2 d-flex align-items-center flex-wrap list-inline">
          @foreach ($cardGenres as $g)
            <li class="fw-semi-bold">
              <span tabindex="0" class="font-size-14">{{ $g }}</span>
            </li>
          @endforeach
        </ul>
      @endif
      <div class="cart-content">
        <div class="content-left">
          <h5 class="iq-title text-capitalize">
            <a href="{{ $cardPath }}">{{ $cardTitle }}</a>
          </h5>
          <div class="d-flex align-items-center gap-3 flex-wrap">
            @if ($cardYear)
            <div class="d-flex align-items-center gap-1">
              <i class="ph ph-calendar-blank font-size-12"></i>
              <small class="font-size-12">{{ $cardYear }}</small>
            </div>
            @endif
            @if ($movietime)
            <div class="d-flex align-items-center gap-1">
              <i class="ph ph-clock font-size-12"></i>
              <small class="font-size-12">{{ $movietime }}</small>
            </div>
            @endif
            <div class="d-flex align-items-center gap-2">
              @if (!$isnotlangCard)
              <i class="ph ph-translate"></i>
              @endif
              <small class="font-size-12">{{ $cardLang }}</small>
            </div>
          </div>
        </div>
      </div>
      <div class="d-flex align-items-center justify-content-center gap-2 mt-3">
        @if (!empty($watchableType) && !empty($watchableId))
          {{-- Real watchlist toggle. Initial icon reflects the server-side
               index from SectionDataComposer; the shared JS delegate in
               layouts/master.blade.php flips it after a successful POST. --}}
          <button type="button"
            class="d-flex align-items-center justify-content-center flex-shrink-0 border-0 add-to-wishlist-btn btn btn-secondary jambo-watchlist-toggle-btn {{ $isInWatchlist ? 'is-in-watchlist' : '' }}"
            data-watchable-type="{{ $watchableType }}"
            data-watchable-id="{{ $watchableId }}"
            data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip"
            data-bs-title="{{ $watchlistTooltip }}">
            <i class="ph {{ $watchlistIcon }} font-size-18"></i>
          </button>
        @else
          {{-- Fallback when the caller hasn't passed watchableType/Id
               (guest users, legacy pages). Show the affordance but
               don't navigate anywhere — tooltip nudges sign-in so the
               click isn't a silent dead end. --}}
          <button type="button"
            class="d-flex align-items-center justify-content-center flex-shrink-0 border-0 add-to-wishlist-btn btn btn-secondary"
            data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip"
            data-bs-title="Sign in to save"
            onclick="event.preventDefault();">
            <i class="ph ph-plus font-size-18"></i>
          </button>
        @endif
        <div class="iq-play-button iq-button">
          <a href="{{ $cardPath }}" class="btn btn-primary w-100">{{__('streamButtons.play_now')}}</a>
        </div>
      </div>
    </div>
    @if ($productPremium)
    <div class="position-absolute z-1 premium-product d-flex align-items-center justify-content-center"
      data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Premium" data-bs-original-title="{{__('streamPricing.premium')}}">
      <i class="ph-fill ph-crown"></i>
    </div>
    @endif
  </div>
</div>
@endif
