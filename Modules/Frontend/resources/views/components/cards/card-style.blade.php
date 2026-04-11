@php
    // Set default values for all variables to prevent undefined errors
    $cardPath = $cardPath ?? '#';
    $cardImage = $cardImage ?? '';
    $cardTitle = $cardTitle ?? '';
    $movietime = $movietime ?? null;
    $cardLang = $cardLang ?? 'English';
    $productPremium = $productPremium ?? false; // Fixing the typo in variable name
    $isCardStyle2 = $isCardStyle2 ?? false;
    $addlist = $addlist ?? false;
    $isnotlangCard = $isnotlangCard ?? false;
@endphp

@if ($isCardStyle2)
<div class="iq-card card-hover landscape-card-hover">
  <div class="block-images position-relative w-100">
    <div class="img-box w-100">
      <a href="{{ $cardPath }}" class="position-relative top-0 bottom-0 start-0 end-0">
        <img src="{{ asset('frontend/images/'. $cardImage) }}" alt="movie-card"
          class="img-fluid object-cover w-100 d-block border-0 rounded-3">
      </a>
    </div>
    <div class="card-description with-transition">
      <ul class="genres-list p-0 mb-2 d-flex align-items-center flex-wrap list-inline">
        <li class="fw-semi-bold">
          <a href="view-all" tabindex="0" class="font-size-14">{{__('streamTag.action')}}</a>
        </li>
        @if ($addlist)
        <li class="fw-semi-bold">
          <a href="view-all" tabindex="0" class="font-size-14">{{__('streamTag.action')}}</a>
        </li>
        @endif
      </ul>
      <div class="cart-content">
        <div class="content-left">
          <h5 class="iq-title text-capitalize mb-0">
            <a href="{{ $cardPath }}">{{ $cardTitle }}</a>
          </h5>
        </div>
      </div>
      <div class="d-flex align-items-center justify-content-center gap-2 mt-3">
        <a href="{{ route('frontend.watchlist_detail') }}"
          class="d-flex align-items-center justify-content-center flex-shrink-0 border-0 add-to-wishlist-btn btn btn-secondary"
          data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip"
          data-bs-title="{{__('sectionTitle.add_to_watchlist_tooltip')}}">
          <i class="ph ph-plus font-size-18"></i>
        </a>
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
        <img src="{{ asset('frontend/images/'. $cardImage) }}" alt="movie-card"
          class="img-fluid object-cover w-100 d-block border-0 rounded-3">
      </a>
    </div>
    <div class="card-description with-transition">
      <ul class="genres-list p-0 mb-2 d-flex align-items-center flex-wrap list-inline">
        <li class="fw-semi-bold">
          <a href="view-all" tabindex="0" class="font-size-14">{{__('streamTag.action')}}</a>
        </li>
        @if ($addlist)
        <li class="fw-semi-bold">
          <a href="view-all" tabindex="0" class="font-size-14">{{__('streamTag.action')}}</a>
        </li>
        @endif
      </ul>
      <div class="cart-content">
        <div class="content-left">
          <h5 class="iq-title text-capitalize">
            <a href="{{ $cardPath }}">{{ $cardTitle }}</a>
          </h5>
          <div class="d-flex align-items-center gap-3">
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
        <a href="{{ route('frontend.watchlist_detail') }}"
          class="d-flex align-items-center justify-content-center flex-shrink-0 border-0 add-to-wishlist-btn btn btn-secondary"
          data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip"
          data-bs-title="{{__('sectionTitle.add_to_watchlist_tooltip')}}">
          <i class="ph ph-plus font-size-18"></i>
        </a>
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
