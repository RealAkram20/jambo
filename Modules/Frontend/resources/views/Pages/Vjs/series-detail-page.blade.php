@extends('frontend::layouts.master', [
    'isSwiperSlider' => true,
    'isFslightbox' => true,
    'bodyClass' => 'custom-header-relative',
    'isSweetalert' => true,
    // "VJ Junior Series" — spoken order. See Vjs/detail-page for the movies
    // counterpart; this page likewise shipped no title at all.
    'title' => $vj->display_name . ' Series',
])

@php
    $vjSeriesDescription = 'Watch ' . $vj->display_name
        . ' translated series free on ' . app_name() . '.';
@endphp

@section('seo:description', $vjSeriesDescription)
@section('seo:type', 'profile')

@if ($vj->featured_image_url)
    @section('seo:image', media_url($vj->featured_image_url))
@endif

@push('seo:head')
    @include('seo::partials.json-ld', [
        'schemas' => [
            \Modules\Seo\app\Support\StructuredData::vjCollection(
                $vj,
                route('frontend.vj_series_detail', $vj->slug),
                $vj->display_name . ' Series',
                collect($buckets ?? [])->flatMap(fn ($b) => $b->shows)->all(),
            ),
            \Modules\Seo\app\Support\StructuredData::breadcrumbs([
                ['name' => 'Home', 'url' => route('frontend.ott')],
                ['name' => $vj->display_name, 'url' => route('frontend.vj_detail', $vj->slug)],
                ['name' => 'Series', 'url' => route('frontend.vj_series_detail', $vj->slug)],
            ]),
        ],
    ])
@endpush

@section('content')
    {{-- Hero banner — mirrors /vj/{slug} for movies, scoped to this
         VJ's series catalogue. --}}
    <section class="banner-container">
        <div class="movie-banner">
            <div class="swiper swiper-banner-container" data-swiper="banner-detail-slider">
                <div class="swiper-wrapper">
                    @foreach ($featuredShows as $i => $show)
                        @include('frontend::components.cards.movie-slider', [
                            'movieCard'    => 'series-banner-' . ($i + 1),
                            'imagePath'    => $show->backdrop_url ?: $show->poster_url ?: 'media/vikings.webp',
                            'movieRating'  => true,
                            'movieTitle'   => $show->title,
                            'movieRange'   => $show->rating ?: '4.0',
                            'movieCate'    => $show->tier_required ? strtoupper($show->tier_required) : 'PG',
                            'NoOfSeasons'  => $show->seasons()->count(),
                            'movieYear'    => $show->year ?: ($show->published_at?->format('F Y') ?? ''),
                            'calenderIcon' => true,
                            'buttonUrl'    => route('frontend.series_detail', $show->slug),
                            'movieText'    => $show->synopsis ?: '',
                            'trailerUrl'   => $show->trailer_url ?: null,
                        ])
                    @endforeach
                </div>
                <div class="swiper-banner-button-next d-none d-lg-block"><i class="ph ph-caret-right arrow-icon"></i></div>
                <div class="swiper-banner-button-prev d-none d-lg-block"><i class="ph ph-caret-left icli arrow-icon"></i></div>
                <div class="swiper-pagination d-block d-lg-none"></div>
            </div>
        </div>
    </section>

    {{-- No overflow-hidden wrapper here: the card-hover on .iq-card
         extends ~1.25em outside each card (decorative outline) and
         ~5em below (reveal panel). Clipping made those get cut off at
         the leftmost column and the last row. Horizontal page overflow
         is handled at the layout/body level. --}}
    <div class="container-fluid pb-5 mb-4 px-3 px-md-4">
        <div>
            <section class="related-movie-block mt-5 mb-2">
                <div class="d-flex align-items-center justify-content-between px-1 pb-2 border-bottom border-dark">
                    <div>
                        {{-- Page <h1>: "VJ Junior Series". Was an <h3>. --}}
                        <h1 class="main-title text-capitalize mb-1">{{ $vj->display_name }} Series</h1>
                        @if ($vj->description)
                            <p class="text-muted mb-0 small">{{ $vj->description }}</p>
                        @endif
                    </div>
                </div>
            </section>

            @forelse ($buckets as $bucket)
                <section class="related-movie-block mt-5 jambo-vj-genre-block"
                         data-genre-slug="{{ $bucket->genre->slug }}"
                         data-offset="{{ $bucket->shows->count() }}"
                         data-total="{{ $bucket->total }}">
                    {{-- Real links to the genre page — see the movies twin
                         (detail-page) for the SEO rationale. --}}
                    <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
                        <h4 class="main-title text-capitalize mb-0">
                            <a href="{{ route('frontend.vj_series_genre', [$vj->slug, $bucket->genre->slug]) }}"
                               class="text-body text-decoration-none">{{ $bucket->genre->name }}</a>
                        </h4>
                        <a href="{{ route('frontend.vj_series_genre', [$vj->slug, $bucket->genre->slug]) }}"
                           class="text-primary iq-view-all text-decoration-none flex-none">
                            {{ __('streamButtons.view_all') ?? 'View All' }}
                        </a>
                    </div>

                    <div class="row row-cols-3 row-cols-md-4 row-cols-lg-6 row-cols-xl-8 g-3 jambo-vj-grid">
                        @foreach ($bucket->shows as $show)
                            @include('frontend::components.partials.vj-grid-card', ['item' => $show, 'contentKind' => 'show'])
                        @endforeach
                    </div>

                    @if ($bucket->hasMore)
                        <div class="text-center mt-3 mb-2">
                            <button type="button"
                                    class="btn btn-outline-primary px-4 py-2 jambo-vj-load-more"
                                    data-genre-slug="{{ $bucket->genre->slug }}">
                                <i class="ph ph-plus-circle me-2"></i>
                                <span class="label">Load More</span>
                            </button>
                        </div>
                    @endif
                </section>
            @empty
                <section class="related-movie-block mt-5">
                    <p class="text-muted">This VJ has no published series yet.</p>
                </section>
            @endforelse
        </div>
    </div>

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')

    <script>
    (function () {
        var endpoint = {{ Js::from(route('frontend.vj_series_genre_more', ['slug' => $vj->slug])) }};
        // Must stay a multiple of every per-row count (8/6/4/3) or
        // appended pages leave ragged lines. Matches the server's 24.
        var LIMIT = 24;

        async function loadMore(section) {
            var genreSlug = section.dataset.genreSlug;
            var btn       = section.querySelector('.jambo-vj-load-more');
            var grid      = section.querySelector('.jambo-vj-grid');

            var label = btn ? btn.querySelector('.label') : null;
            var originalLabel = label ? label.textContent : '';

            if (btn) btn.disabled = true;
            if (label) label.textContent = 'Loading…';

            try {
                while (true) {
                    var offset = parseInt(section.dataset.offset || '0', 10);
                    var total  = parseInt(section.dataset.total  || '0', 10);
                    if (offset >= total) break;

                    var url = endpoint
                        + '?genre=' + encodeURIComponent(genreSlug)
                        + '&offset=' + offset
                        + '&limit=' + LIMIT;

                    var res = await fetch(url, {
                        credentials: 'same-origin',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);

                    var html = await res.text();
                    grid.insertAdjacentHTML('beforeend', html);

                    var appendedCount = (html.match(/class="col"/g) || []).length;
                    section.dataset.offset = (offset + appendedCount).toString();

                    var hasMore = res.headers.get('X-Has-More') === '1';
                    if (!hasMore || parseInt(section.dataset.offset, 10) >= total) {
                        if (btn) btn.parentElement.style.display = 'none';
                    }
                    break;
                }
            } catch (e) {
                console.warn('[vj-series-load-more]', e);
            } finally {
                if (btn) btn.disabled = false;
                if (label) label.textContent = originalLabel;
            }
        }

        document.querySelectorAll('.jambo-vj-genre-block').forEach(function (section) {
            var btn = section.querySelector('.jambo-vj-load-more');
            if (btn) btn.addEventListener('click', function () { loadMore(section); });
        });
    })();
    </script>
@endsection
