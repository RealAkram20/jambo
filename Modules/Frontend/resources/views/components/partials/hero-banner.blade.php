@php
    /**
     * One big banner slide in the lower rail of the OTT hero.
     * Preserves every class/animation from the Streamit template and just
     * replaces the per-slide hardcoded data with the real Movie/Show record.
     *
     * $item   Movie or Show (with _isShow flag set by the composer)
     */
    $isShow = $item->_isShow ?? false;

    // Background: backdrop → poster fallback → template default
    $bg = $item->backdrop_url ?: $item->poster_url;
    $bgSrc = $bg && \Illuminate\Support\Str::startsWith($bg, ['http://', 'https://'])
        ? $bg
        : ($bg ? asset('frontend/images/' . $bg) : asset('frontend/images/media/gameofhero.webp'));

    // Detail URL
    $detailUrl = $isShow
        ? route('frontend.tvshow_detail', $item->slug)
        : route('frontend.movie_detail', $item->slug);

    // Badge: certification (NC-17/PG) for movies, "N season" for shows
    $seasonsCount = $isShow ? $item->seasons->count() : 0;
    $badgeClasses = $isShow
        ? 'badge rounded-0 text-white text-capitalize px-2 py-1 bg-secondary mr-3 fw-bold'
        : 'badge rounded-0 text-white text-uppercase bg-secondary mr-3 fw-bold';
    $badgeText = $isShow
        ? $seasonsCount . ' ' . __('streamEpisode.season')
        : ($item->rating ?: 'PG');

    // Runtime display: hr:m for movies, "45m" for shows
    $runtimeText = null;
    if ($isShow) {
        // Use average episode runtime if available, fall back to nothing.
        $firstEp = $item->seasons->flatMap->episodes->first();
        if ($firstEp && $firstEp->runtime_minutes) {
            $runtimeText = $firstEp->runtime_minutes . 'm';
        }
    } elseif ($item->runtime_minutes) {
        $runtimeText = floor($item->runtime_minutes / 60) . 'hr : ' . ($item->runtime_minutes % 60) . 'm';
    }

    // 5-star display from average rating (0-5 scale).
    // Ratings table stars are 1-5; fall back to 5 filled if none.
    $avg = $item->ratings()->avg('stars') ?? 5;
    $avg = max(0, min(5, (float) $avg));

    // Top 3 of each taxonomy
    $genres = $item->relationLoaded('genres') ? $item->genres->take(3) : collect();
    $tags = $item->relationLoaded('tags') ? $item->tags->take(3) : collect();
    $cast = $item->relationLoaded('cast') ? $item->cast->take(3) : collect();
@endphp

<div class="swiper-slide banner-bg p-0">
    <div class="slider--image block-images"
        style="background-image: url('{{ $bgSrc }}');">
        <div class="container-fluid position-relative">
            <div class="row align-items-center h-100 slider-content-full-height">
                <div class="col-lg-5 col-md-12">
                    <div class="slider-content">
                        <h2 class="texture-text big-font letter-spacing-1 line-count-1 RightAnimate-two mb-1 mb-md-3">
                            {{ $item->title }}
                        </h2>
                        <div class="d-flex flex-wrap align-items-center gap-3 py-2 RightAnimate-three">
                            <span class="{{ $badgeClasses }}">{{ $badgeText }}</span>
                            <div class="d-flex align-items-center gap-3">
                                <ul class="ratting-start p-0 m-0 list-inline text-warning d-flex align-items-center justify-content-left gap-1">
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
                                <span>
                                    <img src="{{ asset('frontend/images/pages/imdb-logo.svg') }}"
                                        alt="imdb logo" class="img-fluid imdb-img">
                                </span>
                            </div>
                            @if ($runtimeText)
                                <div class="d-flex align-items-center gap-1">
                                    <i class="ph ph-clock"></i>
                                    <span class="font-size-16 fw-500">{{ $runtimeText }}</span>
                                </div>
                            @endif
                        </div>

                        @if ($item->synopsis)
                            <p class="line-count-3 my-3 RightAnimate-two">{{ $item->synopsis }}</p>
                        @endif

                        <div class="RightAnimate-three mt-2">
                            @if ($tags->count())
                                <div class="text-primary font-size-14 text-capitalize mb-1">
                                    {{ __('streamTag.tags') }}:
                                    @foreach ($tags as $t)
                                        <a href="{{ route('frontend.tag', $t->slug) }}"
                                            class="text-body text-decoration-none fw-normal">{{ $t->name }}{{ $loop->last ? '' : ',' }}</a>
                                    @endforeach
                                </div>
                            @endif
                            @if ($genres->count())
                                <div class="text-primary font-size-14 text-capitalize mb-1">
                                    {{ __('streamTag.genre') }}:
                                    @foreach ($genres as $g)
                                        <a href="{{ route('frontend.genres', $g->slug) }}"
                                            class="text-body text-decoration-none fw-normal">{{ $g->name }}{{ $loop->last ? '' : ',' }}</a>
                                    @endforeach
                                </div>
                            @endif
                            @if ($cast->count())
                                <div class="text-primary font-size-14 text-capitalize">
                                    {{ __('streamTag.starrting') }}:
                                    @foreach ($cast as $p)
                                        <a href="{{ route('frontend.cast_details', $p->slug) }}"
                                            class="text-body text-decoration-none fw-normal">{{ trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? '')) }}{{ $loop->last ? '' : ',' }}</a>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="RightAnimate-four mt-4 pt-2">
                            @include('frontend::components.widgets.custom-button', [
                                'buttonTitle' => __('streamButtons.play_now'),
                                'buttonUrl' => $detailUrl,
                            ])
                        </div>
                    </div>
                </div>
                <div class="col-lg-7 col-md-12"></div>
            </div>
        </div>
    </div>
</div>
