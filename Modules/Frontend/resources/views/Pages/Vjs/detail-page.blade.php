@extends('frontend::layouts.master', ['isSwiperSlider' => true, 'isFslightbox' => true, 'bodyClass' => 'custom-header-relative', 'isSweetalert' => true])

@section('content')
    {{-- Hero banner — mirrors /movie's hero structure so the page
         feels like a natural extension, just scoped to this VJ's
         catalogue. --}}
    <section class="banner-container">
        <div class="movie-banner">
            <div class="swiper swiper-banner-container" data-swiper="banner-detail-slider">
                <div class="swiper-wrapper">
                    @foreach ($featuredMovies as $i => $movie)
                        @include('frontend::components.cards.movie-slider', [
                            'movieCard'    => 'movie-banner-' . ($i + 1),
                            'imagePath'    => $movie->backdrop_url ?: $movie->poster_url ?: 'media/rabbit.webp',
                            'movieRating'  => true,
                            'movieTitle'   => $movie->title,
                            'movieRange'   => $movie->rating ?: '4.0',
                            'movieCate'    => $movie->tier_required ? strtoupper($movie->tier_required) : 'PG',
                            'movieTime'    => $movie->runtime_minutes
                                ? floor($movie->runtime_minutes / 60) . 'hr : ' . ($movie->runtime_minutes % 60) . 'm'
                                : '1hr : 45m',
                            'movieYear'    => $movie->year ?: ($movie->published_at?->format('F Y') ?? ''),
                            'calenderIcon' => true,
                            'buttonUrl'    => route('frontend.movie_detail', $movie->slug),
                            'movieText'    => $movie->synopsis ?: '',
                            'trailerUrl'   => $movie->trailer_url ?: null,
                        ])
                    @endforeach
                </div>
                <div class="swiper-banner-button-next d-none d-lg-block"><i class="ph ph-caret-right arrow-icon"></i></div>
                <div class="swiper-banner-button-prev d-none d-lg-block"><i class="ph ph-caret-left icli arrow-icon"></i></div>
                <div class="swiper-pagination d-block d-lg-none"></div>
            </div>
        </div>
    </section>

    {{-- No overflow-hidden wrapper here: card-hover extends ~1.25em
         outside each card and ~5em below, so clipping cuts off the
         leftmost column and the last row. --}}
    <div class="container-fluid pb-5 mb-4 px-3 px-md-4">
        <div>
            {{-- Page title: VJ name and total catalogue size. --}}
            <section class="related-movie-block mt-5 mb-2">
                <div class="d-flex align-items-center justify-content-between px-1 pb-2 border-bottom border-dark">
                    <div>
                        <h3 class="main-title text-capitalize mb-1">{{ $vj->name }}</h3>
                        @if ($vj->description)
                            <p class="text-muted mb-0 small">{{ $vj->description }}</p>
                        @endif
                    </div>
                    <span class="text-muted">
                        {{ $buckets->sum('total') }} {{ __('streamTag.movies') ?? 'movies' }}
                    </span>
                </div>
            </section>

            {{-- One grid section per genre. Each bucket is wired up
                 with data attributes so the single shared JS handler
                 knows where to append load-more results. --}}
            @forelse ($buckets as $bucket)
                <section class="related-movie-block mt-5 jambo-vj-genre-block"
                         data-genre-slug="{{ $bucket->genre->slug }}"
                         data-offset="{{ $bucket->movies->count() }}"
                         data-total="{{ $bucket->total }}">
                    <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
                        <h4 class="main-title text-capitalize mb-0">{{ $bucket->genre->name }}</h4>
                        @if ($bucket->hasMore)
                            <a href="javascript:void(0)"
                               class="text-primary iq-view-all text-decoration-none flex-none jambo-vj-view-all"
                               data-genre-slug="{{ $bucket->genre->slug }}">
                                {{ __('streamButtons.view_all') ?? 'View All' }}
                            </a>
                        @endif
                    </div>

                    <div class="row row-cols-xl-5 row-cols-lg-4 row-cols-md-3 row-cols-3 g-3 jambo-vj-grid">
                        @foreach ($bucket->movies as $movie)
                            @include('frontend::components.partials.vj-grid-card', ['item' => $movie, 'contentKind' => 'movie'])
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
                    <p class="text-muted">This VJ has no published movies yet.</p>
                </section>
            @endforelse
        </div>
    </div>

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')

    <script>
    (function () {
        var endpoint = {{ Js::from(route('frontend.vj_movie_genre_more', ['slug' => $vj->slug])) }};
        var LIMIT = 15;

        /**
         * Fetch and append the next slice of movies for a given
         * genre. `expandAll=true` walks until the server says it's
         * out — used by the "View All" link. Otherwise loads one
         * page (15) and re-enables the button.
         */
        async function loadMore(section, expandAll) {
            var genreSlug = section.dataset.genreSlug;
            var btn       = section.querySelector('.jambo-vj-load-more');
            var viewAll   = section.querySelector('.jambo-vj-view-all');
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

                    // Count newly-appended `.col` children — they are
                    // the per-movie wrappers rendered server-side.
                    var appendedCount = (html.match(/class="col"/g) || []).length;
                    section.dataset.offset = (offset + appendedCount).toString();

                    var hasMore = res.headers.get('X-Has-More') === '1';
                    if (!hasMore || parseInt(section.dataset.offset, 10) >= total) {
                        if (btn) btn.parentElement.style.display = 'none';
                        if (viewAll) viewAll.style.display = 'none';
                        break;
                    }

                    if (!expandAll) break;
                }
            } catch (e) {
                console.warn('[vj-load-more]', e);
            } finally {
                if (btn) btn.disabled = false;
                if (label) label.textContent = originalLabel;
            }
        }

        document.querySelectorAll('.jambo-vj-genre-block').forEach(function (section) {
            var btn = section.querySelector('.jambo-vj-load-more');
            if (btn) btn.addEventListener('click', function () { loadMore(section, false); });

            var viewAll = section.querySelector('.jambo-vj-view-all');
            if (viewAll) viewAll.addEventListener('click', function (e) {
                e.preventDefault();
                loadMore(section, true);
            });
        });
    })();
    </script>
@endsection
