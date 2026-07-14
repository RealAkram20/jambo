@php
    /**
     * Shared archive for all three taxonomies:
     *   /categories/{slug}  ($taxonomy === 'categories')
     *   /geners/{slug}      ($taxonomy === 'genres')
     *   /tag/{slug}         ($taxonomy === 'tags')
     *
     * Layout matches /movie and /series: the catalogue is grouped into
     * one carousel per VJ, most-published VJ first, movies then series.
     * These pages were flat grids before, which made an archive read
     * nothing like the two pages visitors browse most.
     *
     * Both blocks pull more VJs from the same endpoint, told apart by
     * ?kind — see FrontendController::taxonomyMoreVjs().
     */
    $heading = $taxonomy === 'tags' ? '#' . $term->name : $term->name;
    $moviesLabel = __('frontendheader.movies');
    $seriesLabel = __('frontendheader.tvshow');

    $moreVjsUrl = fn (string $kind) => route('frontend.taxonomy_more_vjs', [
        'taxonomy' => $taxonomy,
        'slug'     => $term->slug,
        'kind'     => $kind,
    ]);

    $hasMovies = $movieVjs->isNotEmpty() || $looseMovies->isNotEmpty();
    $hasShows  = $showVjs->isNotEmpty() || $looseShows->isNotEmpty();
@endphp

@extends('frontend::layouts.master', [
    'isSwiperSlider' => true,
    'isFslightbox' => true,
    'bodyClass' => 'custom-header-relative',
    'isSweetalert' => true,
    'title' => $heading,
])

@section('content')
    @if ($featured->isNotEmpty())
        <section class="banner-container">
            <div class="movie-banner">
                <div class="swiper swiper-banner-container" data-swiper="banner-detail-slider">
                    <div class="swiper-wrapper">
                        @foreach ($featured as $i => $item)
                            @php
                                $isShow = (bool) ($item->_isShow ?? false);
                                $fallback = $isShow ? 'media/vikings.webp' : 'media/rabbit.webp';
                                $buttonUrl = $isShow
                                    ? route('frontend.series_detail', $item->slug)
                                    : route('frontend.movie_detail', $item->slug);
                            @endphp
                            {{-- Movies carry a runtime, shows a season count. Passing
                                 null and filtering the key out keeps the partial's
                                 isset() checks from rendering an empty badge. --}}
                            @include('frontend::components.cards.movie-slider', array_filter([
                                'movieCard' => 'movie-banner-' . ($i + 1),
                                'imagePath' => $item->backdrop_url ?: $item->poster_url ?: $fallback,
                                'movieRating' => true,
                                'movieTitle' => $item->title,
                                'movieRange' => $item->rating ?: '4.0',
                                'movieCate' => $item->tier_required ? strtoupper($item->tier_required) : 'PG',
                                'movieTime' => ! $isShow && $item->runtime_minutes
                                    ? floor($item->runtime_minutes / 60) . 'hr : ' . ($item->runtime_minutes % 60) . 'm'
                                    : null,
                                'NoOfSeasons' => $isShow ? $item->seasons->count() : null,
                                'movieYear' => $item->year ?: ($item->published_at?->format('F Y') ?? ''),
                                'calenderIcon' => true,
                                'buttonUrl' => $buttonUrl,
                                'movieText' => $item->synopsis ?: '',
                                'trailerUrl' => $item->trailer_url ?: null,
                            ], fn ($v) => $v !== null))
                        @endforeach
                    </div>
                    <div class="swiper-banner-button-next d-none d-lg-block">
                        <i class="ph ph-caret-right arrow-icon"></i>
                    </div>
                    <div class="swiper-banner-button-prev d-none d-lg-block">
                        <i class="ph ph-caret-left icli arrow-icon"></i>
                    </div>
                    <div class="swiper-pagination d-block d-lg-none"></div>
                </div>
            </div>
        </section>
    @endif

    {{-- No overflow-hidden wrapper: card-hover on .iq-card extends past
         the card bounds, so clipping here cuts off the Play Now +
         wishlist reveal on the outer cards. Same as /movie. --}}
    <div class="container-fluid pb-5 mb-4 px-2 px-md-3">
        <div class="pt-4">
            <h2 class="main-title text-capitalize mb-0">{{ $heading }}</h2>
        </div>

        @if ($hasMovies)
            <h5 class="main-title text-capitalize mt-5 mb-0">{{ $moviesLabel }}</h5>

            <div data-vj-list="movies"
                 data-offset="{{ $movieVjs->count() }}"
                 data-total="{{ $movieVjsTotal }}">
                @foreach ($movieVjs as $vj)
                    @include('frontend::components.sections.vj-carousel', [
                        'vj' => $vj,
                        'items' => $vj->movies,
                        'contentKind' => 'movie',
                    ])
                @endforeach
            </div>

            @if ($movieVjsTotal > $movieVjs->count())
                <div class="text-center mt-4">
                    <button type="button" class="btn btn-outline-primary px-4 py-2"
                            data-vj-more="movies"
                            data-endpoint="{{ $moreVjsUrl('movie') }}">
                        <i class="ph ph-plus-circle me-2"></i>
                        <span class="label">{{ __('streamButtons.load_more') }}</span>
                    </button>
                </div>
            @endif

            {{-- Titles here that no VJ is credited on. Deliberately outside
                 [data-vj-list]: Load More appends to the end of that
                 container, so a row parked inside it would end up above the
                 VJs that arrive later. /movie and /series never show these
                 (they're built from the Vj table outward), but an archive is
                 a curated shelf — an untagged title an admin put in Trending
                 should still appear on Trending. --}}
            @if ($looseMovies->isNotEmpty())
                @include('frontend::components.sections.vj-carousel', [
                    'vj' => null,
                    'rowTitle' => 'Other ' . $moviesLabel,
                    'items' => $looseMovies,
                    'contentKind' => 'movie',
                ])
            @endif
        @endif

        @if ($hasShows)
            <h5 class="main-title text-capitalize mt-5 mb-0">{{ $seriesLabel }}</h5>

            <div data-vj-list="series"
                 data-offset="{{ $showVjs->count() }}"
                 data-total="{{ $showVjsTotal }}">
                @foreach ($showVjs as $vj)
                    @include('frontend::components.sections.vj-carousel', [
                        'vj' => $vj,
                        'items' => $vj->shows,
                        'contentKind' => 'show',
                    ])
                @endforeach
            </div>

            @if ($showVjsTotal > $showVjs->count())
                <div class="text-center mt-4">
                    <button type="button" class="btn btn-outline-primary px-4 py-2"
                            data-vj-more="series"
                            data-endpoint="{{ $moreVjsUrl('show') }}">
                        <i class="ph ph-plus-circle me-2"></i>
                        <span class="label">{{ __('streamButtons.load_more') }}</span>
                    </button>
                </div>
            @endif

            @if ($looseShows->isNotEmpty())
                @include('frontend::components.sections.vj-carousel', [
                    'vj' => null,
                    'rowTitle' => 'Other ' . $seriesLabel,
                    'items' => $looseShows,
                    'contentKind' => 'show',
                ])
            @endif
        @endif

        @if (! $hasMovies && ! $hasShows)
            <div class="text-center py-5 my-4">
                <i class="ph ph-film-strip text-muted" style="font-size: 56px;"></i>
                <h5 class="mt-3 mb-2">{{ __('streamTag.no_results') ?? 'Nothing here yet' }}</h5>
                <a href="{{ route('frontend.movie') }}" class="btn btn-primary mt-3">
                    {{ __('frontendheader.movies') }}
                </a>
            </div>
        @endif
    </div>

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}

    @include('frontend::components.partials.vj-load-more')
@endsection
