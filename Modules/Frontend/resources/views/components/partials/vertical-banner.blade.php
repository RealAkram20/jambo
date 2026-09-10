@php
    /**
     * One big slide in the right rail of the vertical slider.
     * Keeps the template's exact classes and DOM nesting — only the
     * hardcoded content is swapped for real Eloquent data.
     *
     * $item  Movie (with genres loaded)
     */
    $img = $item->backdrop_url ?: $item->poster_url;
    // 1280: the large slide in the Top 10 vertical slider.
    $imgSrc = media_img($img, 1280, 'media/the-first-of-us.webp');

    $runtime = $item->runtime_minutes
        ? floor($item->runtime_minutes / 60) . 'hr : ' . ($item->runtime_minutes % 60) . 'm'
        : null;

    $genres = $item->relationLoaded('genres') ? $item->genres->take(4) : collect();
@endphp
<div class="swiper-slide">
    <div class="slider--image block-images">
        <img src="{{ $imgSrc }}" loading="lazy" alt="{{ $item->title }}">
    </div>
    <div class="description">
        <div class="block-description">
            {{-- "Top 10" rank badge — same gold trending-label tile + label
                 pill the series tab-slider uses, so the vertical movie hero
                 communicates the same Top-10-of-the-day intent. --}}
            @isset($rank)
                <div class="d-flex align-items-center gap-3 mb-3 justify-content-center justify-content-lg-start">
                    <img src="{{ asset('frontend/images/pages/trending-label.webp') }}"
                         class="img-fluid trending-label-img rounded-3" alt="{{ __('streamMovies.top_ten_label') }}">
                    @if (($item->recent_viewers ?? 0) > 0)
                        <span class="text-gold fw-bold font-size-18">#{{ $rank }} {{ __('streamMovies.movies_today') }}</span>
                    @else
                        {{-- Padded in from all-time popularity on a quiet day (see
                             TopPicksRecommender): no rank it did not earn today. --}}
                        <span class="text-gold fw-bold font-size-18">{{ __('streamMovies.popular_on_jambo') }}</span>
                    @endif
                </div>
            @endisset
            @if ($genres->count())
                <ul class="ps-0 mb-2 pb-1 list-inline d-flex flex-wrap align-items-center movie-tag justify-content-center justify-content-lg-start genres-list gap-1 gap-sm-0">
                    @foreach ($genres as $g)
                        <li class="text-capitalize font-size-14 letter-spacing-1">
                            <a href="{{ route('frontend.genres', $g->slug) }}" class="text-decoration-none">{{ $g->name }}</a>
                        </li>
                    @endforeach
                </ul>
            @endif
            <h2 class="iq-title m-0 line-count-2">
                <a href="{{ route('frontend.movie_detail', $item->slug) }}">{{ $item->title }}</a>
            </h2>
            <div class="d-flex align-items-center gap-3 py-2 justify-content-center justify-content-lg-start flex-wrap">
                {{-- Five-star row removed 2026-09-10, with the same one on
                     hero-banner: `ratings()->avg('stars') ?? 5` over an empty
                     ratings table nothing can write to. See docs/adr/0006.
                     The IMDb mark stays; the score beside it does not, because
                     it was the same invented average to one decimal place. --}}
                <div class="d-flex align-items-center gap-1">
                    <img class="imdb-img" alt="imdb-logo" src="{{ asset('frontend/images/pages/imdb-logo.svg') }}">
                </div>
                @if ($runtime)
                    <div class="d-flex align-items-center gap-1">
                        <i class="ph ph-clock font-size-14"></i>
                        <span class="text-body">{{ $runtime }}</span>
                    </div>
                @endif
            </div>
            @if ($item->synopsis)
                <p class="mt-2 mb-3 line-count-3">{{ $item->synopsis }}</p>
            @endif
            @include('frontend::components.widgets.custom-button', [
                'buttonUrl' => route('frontend.movie_detail', $item->slug),
                'buttonTitle' => __('streamButtons.play_now'),
            ])
        </div>
    </div>
</div>
