@php
    /**
     * Shared archive for all three taxonomies:
     *   /categories/{slug}  ($taxonomy === 'categories')
     *   /geners/{slug}      ($taxonomy === 'genres')
     *   /tag/{slug}         ($taxonomy === 'tags')
     *
     * Layout: featured banner, then the catalogue itself — a
     * newest-first poster grid of everything in the term with
     * All | Movies | Series tabs — then per-VJ carousels scoped to the
     * term. The tabs are real links (?kind=movies|series) so the
     * whole page, VJ rows included, narrows to one kind server-side
     * and every state stays a crawlable URL.
     */
    $heading = $taxonomy === 'tags' ? '#' . $term->name : $term->name;
    $moviesLabel = __('frontendheader.movies');
    $seriesLabel = __('frontendheader.tvshow');

    $tabUrl = fn (?string $k) => request()->url() . ($k ? '?kind=' . $k : '');
    $tabs = [
        'all'    => ['label' => 'All',        'url' => $tabUrl(null)],
        'movies' => ['label' => $moviesLabel, 'url' => $tabUrl('movies')],
        'series' => ['label' => $seriesLabel, 'url' => $tabUrl('series')],
    ];

    $moreVjsUrl = fn (string $k) => route('frontend.taxonomy_more_vjs', [
        'taxonomy' => $taxonomy,
        'slug'     => $term->slug,
        'kind'     => $k,
    ]);
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
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 pt-4">
            {{-- h1 for the crawler (this page's target keyword, e.g.
                 "Action Movies"), h4 scale for the eye — matching the
                 section headings everywhere else. --}}
            <h1 class="main-title text-capitalize mb-0 h4 fw-medium">{{ $heading }}</h1>

            <div class="btn-group" role="group" aria-label="{{ $heading }}">
                @foreach ($tabs as $key => $tab)
                    <a href="{{ $tab['url'] }}"
                       class="btn btn-sm {{ $kind === $key ? 'btn-primary' : 'btn-outline-secondary' }}">
                        {{ $tab['label'] }}
                    </a>
                @endforeach
            </div>
        </div>

        @if ($grid->total())
            <div id="archive-grid" class="row row-cols-3 row-cols-md-4 row-cols-lg-6 row-cols-xl-8 g-3 mt-1">
                @foreach ($grid as $item)
                    @php $isShow = (bool) ($item->_isShow ?? false); @endphp
                    <div class="col">
                        @include('frontend::components.cards.card-style', [
                            'cardImage' => $item->poster_url ?: ($isShow ? 'media/vikings-portrait.webp' : 'media/rabbit-portrait.webp'),
                            'cardTitle' => $item->title,
                            'movietime' => ! $isShow && $item->runtime_minutes
                                ? floor($item->runtime_minutes / 60) . 'hr : ' . ($item->runtime_minutes % 60) . 'mins'
                                : null,
                            'cardLang' => 'English',
                            'cardPath' => $isShow
                                ? route('frontend.series_detail', $item->slug)
                                : route('frontend.movie_detail', $item->slug),
                            'cardGenres' => $item->genres->take(2)->pluck('name')->all(),
                            'productPremium' => (bool) $item->tier_required,
                            'watchableType' => $isShow ? 'show' : 'movie',
                            'watchableId'   => $item->id,
                        ])
                    </div>
                @endforeach
            </div>

            @include('frontend::components.partials.load-more-pagination', [
                'paginator'    => $grid,
                'gridSelector' => '#archive-grid',
            ])
        @else
            <div class="text-center py-5 my-4">
                <i class="ph ph-film-strip text-muted" style="font-size: 56px;"></i>
                <h5 class="mt-3 mb-2">{{ __('streamTag.no_results') ?? 'Nothing here yet' }}</h5>
                <a href="{{ route('frontend.movie') }}" class="btn btn-primary mt-3">
                    {{ __('frontendheader.movies') }}
                </a>
            </div>
        @endif

        {{-- Per-VJ carousels, scoped to this term — each VJ's row holds
             only their titles in this category/genre/tag. The blocks
             follow the tab: the controller hands over empty collections
             for the kind a narrowed page hides. --}}
        @if ($movieVjs->isNotEmpty())
            <h5 class="main-title text-capitalize mt-5 mb-0">{{ $moviesLabel }} by VJ</h5>

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
        @endif

        @if ($showVjs->isNotEmpty())
            <h5 class="main-title text-capitalize mt-5 mb-0">{{ $seriesLabel }} by VJ</h5>

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
        @endif
    </div>

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}

    @include('frontend::components.partials.vj-load-more')
@endsection
