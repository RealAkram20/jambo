@php
    /**
     * One big slide in the right rail of the vertical slider.
     * Keeps the template's exact classes and DOM nesting — only the
     * hardcoded content is swapped for real Eloquent data.
     *
     * $item  Movie (with genres loaded)
     */
    $img = $item->backdrop_url ?: $item->poster_url;
    $imgSrc = media_url($img, 'media/the-first-of-us.webp');

    $runtime = $item->runtime_minutes
        ? floor($item->runtime_minutes / 60) . 'hr : ' . ($item->runtime_minutes % 60) . 'm'
        : null;

    // 5-star display from average rating (0-5 scale). Prefer the
    // eager-loaded `ratings_avg_stars` from SectionDataComposer's
    // loadAvg() so we don't N+1 once per slide; fall back to a lazy
    // ratings()->avg() if some other caller skipped the eager load.
    $avg = $item->ratings_avg_stars ?? $item->ratings()->avg('stars');
    $avg = $avg !== null ? max(0, min(5, (float) $avg)) : 5;

    // IMDB-style numeric score (display to 1 dp, 0–5 range to match stars).
    $score = $avg !== null ? number_format($avg, 1) : '—';

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
                         class="img-fluid trending-label-img rounded-3" alt="{{ __('sectionTitle.top_ten') }}">
                    <span class="text-gold fw-bold font-size-18">#{{ $rank }} {{ __('streamMovies.movies_today') }}</span>
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
                <div class="slider-ratting d-flex align-items-center gap-1">
                    <ul class="ratting-start p-0 m-0 list-inline text-warning d-flex align-items-center justify-content-left">
                        @for ($i = 1; $i <= 5; $i++)
                            <li>
                                @if ($i <= floor($avg))
                                    <i class="ph-fill ph-star" aria-hidden="true"></i>
                                @elseif ($i - $avg < 1)
                                    <i class="ph-fill ph-star-half" aria-hidden="true"></i>
                                @else
                                    <i class="ph ph-star" aria-hidden="true"></i>
                                @endif
                            </li>
                        @endfor
                    </ul>
                </div>
                <div class="d-flex align-items-center gap-1">
                    <p class="mb-0">{{ $score }}</p>
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
