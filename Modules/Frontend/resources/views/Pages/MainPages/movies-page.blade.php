@extends('frontend::layouts.master', [
    'isSwiperSlider' => true,
    'isFslightbox' => true,
    'bodyClass' => 'custom-header-relative',
    'isSweetalert' => true,
    // This listing page had no title, so Google indexed it as the bare
    // brand name and it competed with the home page for the same query.
    'title' => 'Movies',
])

@section('seo:description', 'Browse every VJ translated movie on ' . app_name() . ' — action, comedy, drama and more, free to watch.')

@section('content')
    {{-- Single <h1>. Visually hidden for the same reason as the home page: the
         hero is a full-bleed slider with nowhere to put a heading. The text
         describes what the page actually lists (this page is entirely VJ
         carousels), so it matches the visible content rather than contradicting
         it. --}}
    <h1 class="visually-hidden">VJ Translated Movies</h1>

    <section class="banner-container">
        <div class="movie-banner">
            <div class="swiper swiper-banner-container" data-swiper="banner-detail-slider">
                <div class="swiper-wrapper">
                    @foreach ($featuredMovies as $i => $movie)
                        @include('frontend::components.cards.movie-slider', [
                            'movieCard' => 'movie-banner-' . ($i + 1),
                            'imagePath' => $movie->backdrop_url ?: $movie->poster_url ?: 'media/rabbit.webp',
                            'movieRating' => true,
                            'movieTitle' => $movie->title,
                            'movieRange' => $movie->rating ?: '4.0',
                            'movieCate' => $movie->tier_required ? strtoupper($movie->tier_required) : 'PG',
                            'movieTime' => $movie->runtime_minutes ? floor($movie->runtime_minutes / 60) . 'hr : ' . ($movie->runtime_minutes % 60) . 'm' : '1hr : 45m',
                            'movieYear' => $movie->year ?: ($movie->published_at?->format('F Y') ?? ''),
                            'calenderIcon' => true,
                            'buttonUrl' => route('frontend.movie_detail', $movie->slug),
                            'movieText' => $movie->synopsis ?: '',
                            'trailerUrl' => $movie->trailer_url ?: null,
                        ])
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

    {{-- No overflow-hidden wrapper: card-hover on .iq-card extends
         ~1.25em outside each card and ~5em below via the ::after
         pseudo, so clipping here cuts off the Play Now + wishlist
         reveal on the outer cards and the last row. Horizontal page
         overflow is handled at the body level in custom.css. --}}
    <div class="container-fluid pb-5 mb-4 px-2 px-md-3">
        <div>
            {{-- VJ carousels: each row is one VJ (narrator / translator).
                 Top 5 are rendered server-side; the rest are fetched by
                 the Load More button below, which appends server-rendered
                 HTML so this partial stays the single source of truth. --}}
            <div data-vj-list="movies" data-offset="{{ $vjs->count() }}" data-total="{{ $vjsTotal }}">
                @forelse ($vjs as $vj)
                    @include('frontend::components.sections.vj-carousel', [
                        'vj' => $vj,
                        'items' => $vj->movies,
                        'contentKind' => 'movie',
                    ])
                @empty
                    <section class="related-movie-block mt-5">
                        <p class="text-muted">No VJ-tagged movies yet.</p>
                    </section>
                @endforelse
            </div>

            @if ($vjsTotal > $vjs->count())
                <div class="text-center mt-4 mb-5">
                    <button type="button" class="btn btn-outline-primary px-4 py-2"
                            data-vj-more="movies"
                            data-endpoint="{{ route('frontend.movie_more_vjs') }}">
                        <i class="ph ph-plus-circle me-2"></i>
                        <span class="label">Load More VJs</span>
                    </button>
                </div>
            @endif
        </div>
    </div>

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}

    @include('frontend::components.partials.vj-load-more')
@endsection
