@extends('frontend::layouts.master', [
    'isSwiperSlider' => true,
    'isFslightbox' => true,
    'bodyClass' => 'custom-header-relative',
    'isSweetalert' => true,
    // The VJ hub is the landing page for the site's highest-volume query
    // ("vj junior"). It previously shipped no title at all — the browser tab
    // and the Google result both read a bare "Jambo Films" — so it ranked on
    // the strength of the catalogue alone, with nothing on the page naming
    // the thing people searched for. display_name normalises "Vj" -> "VJ",
    // which is how the audience actually writes it.
    'title' => $vj->display_name,
])

@php
    // Prefer whatever an admin has written about this VJ. The generated
    // fallback still differs per VJ (the name is in it), so it is not the
    // duplicate-description problem the movie pages had — but a real bio is
    // better, and this is the string that becomes the search snippet.
    $vjMetaDescription = trim(strip_tags((string) $vj->description)) !== ''
        ? \Illuminate\Support\Str::limit(strip_tags((string) $vj->description), 160)
        : 'Watch ' . $vj->display_name . ' translated movies and series free on ' . app_name() . '.';
@endphp

@section('seo:description', $vjMetaDescription)
@section('seo:type', 'profile')

@if ($vj->featured_image_url)
    @section('seo:image', media_url($vj->featured_image_url))
@endif

{{-- Person + CollectionPage. The Person node is the entity anchor for every
     "vj junior ..." query; the CollectionPage points back at it by @id so
     Google reads this page as "VJ Junior's work" rather than as a loose grid
     of films that happen to share a page. --}}
@push('seo:head')
    @include('seo::partials.json-ld', [
        'schemas' => [
            \Modules\Seo\app\Support\StructuredData::vjPerson($vj),
            \Modules\Seo\app\Support\StructuredData::vjCollection(
                $vj,
                route('frontend.vj_detail', $vj->slug),
                $vj->display_name,
                // The real catalogue the page renders, not $vjHeroItems —
                // those are just the banner slides.
                collect($movies ?? [])->concat($shows ?? [])->all(),
            ),
            \Modules\Seo\app\Support\StructuredData::breadcrumbs([
                ['name' => 'Home', 'url' => route('frontend.ott')],
                ['name' => $vj->display_name, 'url' => route('frontend.vj_detail', $vj->slug)],
            ]),
        ],
    ])
@endpush

@section('content')
    {{-- Hero banner — mixes movies + series so the overview surfaces
         the VJ's most recent work regardless of type. Each
         $vjHeroItems entry carries an `_isShow` flag set by the
         controller so the partial doesn't need an instanceof check.
         The variable is `vjHeroItems` (not `heroItems`) because
         SectionDataComposer publishes a global `heroItems` for every
         frontend::Pages.* view, and composer data overrides
         controller data — the global mix would silently replace
         this VJ's titles. --}}
    <section class="banner-container">
        <div class="movie-banner">
            <div class="swiper swiper-banner-container" data-swiper="banner-detail-slider">
                <div class="swiper-wrapper">
                    @forelse ($vjHeroItems as $i => $item)
                        @php
                            $isShow = (bool) ($item->_isShow ?? false);
                        @endphp
                        @include('frontend::components.cards.movie-slider', [
                            'movieCard'    => 'vj-banner-' . ($i + 1),
                            'imagePath'    => $item->backdrop_url ?: $item->poster_url ?: ($isShow ? 'media/vikings.webp' : 'media/rabbit.webp'),
                            'movieRating'  => true,
                            'movieTitle'   => $item->title,
                            'movieRange'   => $item->rating ?: '4.0',
                            'movieCate'    => $item->tier_required ? strtoupper($item->tier_required) : 'PG',
                            'movieTime'    => (! $isShow && $item->runtime_minutes)
                                ? floor($item->runtime_minutes / 60) . 'hr : ' . ($item->runtime_minutes % 60) . 'm'
                                : ($isShow ? null : '1hr : 45m'),
                            'NoOfSeasons'  => $isShow ? $item->seasons->count() : null,
                            'movieYear'    => $item->year ?: ($item->published_at?->format('F Y') ?? ''),
                            'calenderIcon' => true,
                            'buttonUrl'    => $isShow
                                ? route('frontend.series_detail', $item->slug)
                                : route('frontend.movie_detail', $item->slug),
                            'movieText'    => $item->synopsis ?: '',
                            'trailerUrl'   => $item->trailer_url ?: null,
                        ])
                    @empty
                        {{-- Empty hero is unlikely (we already filter VJs to those with
                             at least one published title) but the partial would crash
                             on an empty wrapper, so render a neutral fallback. --}}
                    @endforelse
                </div>
                <div class="swiper-banner-button-next d-none d-lg-block"><i class="ph ph-caret-right arrow-icon"></i></div>
                <div class="swiper-banner-button-prev d-none d-lg-block"><i class="ph ph-caret-left icli arrow-icon"></i></div>
                <div class="swiper-pagination d-block d-lg-none"></div>
            </div>
        </div>
    </section>

    {{-- No overflow-hidden wrapper here: card-hover extends ~1.25em
         outside each card and ~5em below, so clipping cuts off the
         leftmost column's reveal panel. Same pattern as the other VJ
         pages and /movie. --}}
    <div class="container-fluid pb-5 mb-4 px-3 px-md-4">
        <div>
            {{-- Page header — VJ name, optional bio, combined catalogue size. --}}
            <section class="related-movie-block mt-5 mb-2">
                <div class="d-flex align-items-center justify-content-between px-1 pb-2 border-bottom border-dark">
                    <div>
                        {{-- The page's one and only <h1>. It was an <h3>, which
                             left the page with no primary heading at all — the
                             keyword it ranks for was stated nowhere in the
                             markup. Same classes, so the styling is unchanged. --}}
                        <h1 class="main-title text-capitalize mb-1">{{ $vj->display_name }}</h1>
                        {{-- The bio used to be repeated here as a muted one-liner.
                             It now lives in the About card at the foot of the page,
                             where it sits alongside the photo and the social links —
                             printing the same prose twice on one page helps neither
                             the reader nor Google. --}}
                    </div>
                </div>
            </section>

            {{-- Movies rail. Reuses the standard vj-carousel partial so
                 the look matches the per-VJ rows on /movie. View-All
                 link goes to the dedicated movies-only catalogue page
                 where everything is genre-organised with Load More. --}}
            @if ($movies->isNotEmpty())
                <section class="related-movie-block mt-5">
                    <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
                        <h4 class="main-title text-capitalize mb-0">{{ __('streamTag.movies') ?? 'Movies' }}</h4>
                        <a href="{{ route('frontend.vj_movie_detail', $vj->slug) }}"
                           class="text-primary iq-view-all text-decoration-none flex-none">
                            {{ __('streamButtons.view_all') ?? 'View All' }}
                        </a>
                    </div>
                    <div class="card-style-slider">
                        <div class="position-relative swiper swiper-card"
                             data-slide="8" data-laptop="8" data-tab="4" data-mobile="3.5" data-mobile-sm="3.5"
                             data-autoplay="false" data-loop="false"
                             data-navigation="true" data-pagination="true">
                            <ul class="p-0 swiper-wrapper m-0 list-inline">
                                @foreach ($movies as $movie)
                                    <li class="swiper-slide">
                                        @include('frontend::components.cards.card-style', [
                                            'cardImage'      => $movie->poster_url ?: 'media/rabbit-portrait.webp',
                                            'cardTitle'      => $movie->title,
                                            'movietime'      => $movie->runtime_minutes
                                                ? floor($movie->runtime_minutes / 60) . 'hr : ' . ($movie->runtime_minutes % 60) . 'mins'
                                                : null,
                                            'cardLang'       => 'English',
                                            'cardPath'       => route('frontend.movie_detail', $movie->slug),
                                            'cardGenres'     => $movie->relationLoaded('genres') ? $movie->genres->take(2)->pluck('name')->all() : null,
                                            'productPremium' => (bool) $movie->tier_required,
                                            'watchableType'  => 'movie',
                                            'watchableId'    => $movie->id,
                                        ])
                                    </li>
                                @endforeach
                            </ul>
                            <div class="swiper-button swiper-button-next d-none d-lg-block"></div>
                            <div class="swiper-button swiper-button-prev d-none d-lg-block"></div>
                        </div>
                    </div>
                </section>
            @endif

            {{-- Series rail. View-All goes to the existing
                 /vj-series/{slug} page (unchanged from before). --}}
            @if ($shows->isNotEmpty())
                <section class="related-movie-block mt-5">
                    <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
                        <h4 class="main-title text-capitalize mb-0">{{ __('streamTag.series') ?? 'Series' }}</h4>
                        <a href="{{ route('frontend.vj_series_detail', $vj->slug) }}"
                           class="text-primary iq-view-all text-decoration-none flex-none">
                            {{ __('streamButtons.view_all') ?? 'View All' }}
                        </a>
                    </div>
                    <div class="card-style-slider">
                        <div class="position-relative swiper swiper-card"
                             data-slide="8" data-laptop="8" data-tab="4" data-mobile="3.5" data-mobile-sm="3.5"
                             data-autoplay="false" data-loop="false"
                             data-navigation="true" data-pagination="true">
                            <ul class="p-0 swiper-wrapper m-0 list-inline">
                                @foreach ($shows as $show)
                                    <li class="swiper-slide">
                                        @include('frontend::components.cards.card-style', [
                                            'cardImage'      => $show->poster_url ?: 'media/vikings-portrait.webp',
                                            'cardTitle'      => $show->title,
                                            'movietime'      => null,
                                            'cardLang'       => 'English',
                                            'cardPath'       => route('frontend.series_detail', $show->slug),
                                            'cardGenres'     => $show->relationLoaded('genres') ? $show->genres->take(2)->pluck('name')->all() : null,
                                            'productPremium' => (bool) $show->tier_required,
                                            'watchableType'  => 'show',
                                            'watchableId'    => $show->id,
                                        ])
                                    </li>
                                @endforeach
                            </ul>
                            <div class="swiper-button swiper-button-next d-none d-lg-block"></div>
                            <div class="swiper-button swiper-button-prev d-none d-lg-block"></div>
                        </div>
                    </div>
                </section>
            @endif

            {{-- Defensive: if a VJ exists but has zero published titles
                 of either type they still slipped through (they would
                 need at least one published item to land here, but the
                 fallback keeps the page from rendering as just a
                 header). --}}
            @if ($movies->isEmpty() && $shows->isEmpty())
                <section class="related-movie-block mt-5">
                    <p class="text-muted">This VJ doesn't have any published titles yet.</p>
                </section>
            @endif

            {{-- "About <VJ>" — photo, bio, social links. Sits below the catalogue
                 so the posters stay above the fold. This is the only unique prose
                 on the page; without it every VJ page is a poster grid that reads
                 as a clone of the other 35. Renders nothing when the VJ has no
                 photo, bio or socials. --}}
            @include('frontend::components.sections.vj-bio-card', ['vj' => $vj])
        </div>
    </div>

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
@endsection
