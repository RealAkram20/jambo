@php
    /**
     * Upcoming slider — strictly movies + series with status=upcoming.
     *
     * Expects `$upcomingItems` to be a collection from
     * TopPicksRecommender::upcomingListing(). Each item carries a
     * `_kind` attribute ('movie' | 'show') so the link + watchable
     * type can branch per-row.
     *
     * If the collection is empty, the whole section renders nothing
     * (previous behaviour silently served $latestMovies, making the
     * "Upcoming" label a lie).
     */
    $upcomingItems = $upcomingItems ?? collect();
    $viewAllBtn    = $viewAllBtn    ?? false;

    if ($upcomingItems->isEmpty()) {
        return;
    }

    $t = __('sectionTitle.upcoming_title');
    $upcTitle = $t === 'sectionTitle.upcoming_title' ? 'Upcoming' : $t;
@endphp

<div class="streamit-block section-wraper">
    <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
        <h4 class="main-title text-capitalize mb-0 fw-medium">{{ $upcTitle }}</h4>
        @if ($viewAllBtn)
            <a href="{{ route('frontend.upcoming') }}"
               class="text-primary iq-view-all text-decoration-none flex-none">
                {{ __('streamButtons.view_all') }}
            </a>
        @endif
    </div>
    <div class="card-style-slider">
        <div class="position-relative swiper swiper-card" data-slide="7" data-laptop="5" data-tab="4" data-mobile="3"
            data-mobile-sm="3" data-autoplay="false" data-loop="false" data-navigation="true" data-pagination="true">
            <ul class="p-0 swiper-wrapper m-0 list-inline">
                @foreach ($upcomingItems as $item)
                    @php
                        $isShow = ($item->_kind ?? null) === 'show';
                        $cardPath = $isShow
                            ? route('frontend.series_detail', $item->slug)
                            : route('frontend.movie_detail', $item->slug);
                        $fallbackImg = $isShow ? 'media/vikings-portrait.webp' : 'media/rabbit-portrait.webp';
                        // `published_at` doubles as the release date for
                        // upcoming titles (see MovieController). Format
                        // the ribbon label as "Mar 15, 2026" when we've
                        // got a date; fall back to "TBA" when the admin
                        // hasn't scheduled one yet, so the ribbon still
                        // communicates "upcoming" intent.
                        $releaseLabel = $item->published_at instanceof \DateTimeInterface
                            ? $item->published_at->format('M j, Y')
                            : 'TBA';
                    @endphp
                    <li class="swiper-slide">
                        @include('frontend::components.cards.card-style', [
                            'cardImage' => $item->poster_url ?: $fallbackImg,
                            'cardTitle' => $item->title,
                            'movietime' => ! $isShow && $item->runtime_minutes
                                ? floor($item->runtime_minutes / 60) . 'hr : ' . ($item->runtime_minutes % 60) . 'mins'
                                : null,
                            'cardLang' => 'English',
                            'cardPath' => $cardPath,
                            'cardGenres' => $item->relationLoaded('genres') ? $item->genres->take(2)->pluck('name')->all() : null,
                            'productPremium' => (bool) $item->tier_required,
                            'upcomingRelease' => $releaseLabel,
                            'watchableType' => $isShow ? 'show' : 'movie',
                            'watchableId'   => $item->id,
                        ])
                    </li>
                @endforeach
            </ul>
            <div class="d-none d-lg-block">
                <div class="swiper-button swiper-button-next"></div>
                <div class="swiper-button swiper-button-prev"></div>
            </div>
        </div>
    </div>
</div>
