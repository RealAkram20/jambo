<div class="common_card">
    <div class="image-box w-100">
        <a href="tv-show-detail" class="d-block">
            <img decoding="async" src="{{asset('frontend/images/'. $cardImage)}}" alt="Game of Heros" class="img-fluid">
        </a>
    </div>
    <div class="css_prefix-detail-part">
        <h6 class="text-capitalize line-count-1 mb-0">
            <a href="tv-show-detail" class="color-inherit">
                {{$movieName}} </a>
        </h6>
        <button class="btn in-watchlist btn-secondary watch-list-btn" data-post-id="32"
            data-post-type="movie" data-action="remove" data-bs-toggle="tooltip" data-bs-title="Remove from watchlist"
            data-bs-placement="top" tabindex="0">
            <i class="icon-check-2"></i>
        </button>
    </div>
</div>
