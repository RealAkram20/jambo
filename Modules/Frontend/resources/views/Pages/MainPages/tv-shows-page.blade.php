@extends('frontend::layouts.master', [
    'isSwiperSlider' => true,
    'isFslightbox' => true,
    'bodyClass' => 'custom-header-relative',
    // See movies-page: this listing carried no title either.
    'title' => 'TV Series',
])

@section('seo:description', 'Browse every VJ translated series on ' . app_name() . ' — full seasons and episodes, free to watch.')

@section('content')
    {{-- Single <h1> — see movies-page for why it is visually hidden. --}}
    <h1 class="visually-hidden">VJ Translated Series</h1>

    <section class="banner-container">
        <div class="movie-banner">
            <div class="swiper swiper-banner-container iq-rtl-direction" data-swiper="banner-detail-slider">
                <div class="swiper-wrapper">
                    @foreach ($featuredShows as $i => $show)
                        @include('frontend::components.cards.movie-slider', [
                            'movieCard' => 'tv-show-' . ($i + 1),
                            'imagePath' => $show->backdrop_url ?: $show->poster_url ?: 'media/vikings.webp',
                            'movieTitle' => $show->title,
                            'movieRating' => true,
                            'movieRange' => $show->rating ?: '4.0',
                            'NoOfSeasons' => $show->seasons()->count(),
                            'movieYear' => $show->year ?: ($show->published_at?->format('F Y') ?? ''),
                            'calenderIcon' => true,
                            'buttonUrl' => route('frontend.series_detail', $show->slug),
                            'movieText' => $show->synopsis ?: '',
                            'trailerUrl' => $show->trailer_url ?: null,
                        ])
                    @endforeach
                </div>
                <div class="swiper-pagination d-block d-lg-none"></div>

                <div class="swiper-banner-button-next d-none d-lg-block">
                    <i class="ph ph-caret-right arrow-icon"></i>
                </div>
                <div class="swiper-banner-button-prev d-none d-lg-block">
                    <i class="ph ph-caret-left icli arrow-icon"></i>
                </div>
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
            {{-- Top 10 Series — scoring algorithm and data are shared
                 with Top 10 Movies on the home page. SectionDataComposer
                 auto-populates $topShows with the same cold-start →
                 weighted blend used for movies (views + completions +
                 watchlist + ratings + reviews + editor_boost). --}}
            <div class="overflow-hidden mb-4">
                @include('frontend::components.sections.top-ten-tvshow')
            </div>

            {{-- VJ carousels for series: each row is one VJ (narrator /
                 translator). Mirrors the /movie page layout — first 5
                 server-rendered, the rest fetched via Load More so the
                 same partial renders in both places. --}}
            <div data-vj-list="series" data-offset="{{ $vjs->count() }}" data-total="{{ $vjsTotal }}">
                @forelse ($vjs as $vj)
                    @include('frontend::components.sections.vj-carousel', [
                        'vj' => $vj,
                        'items' => $vj->shows,
                        'contentKind' => 'show',
                    ])
                @empty
                    <section class="related-movie-block mt-5">
                        <p class="text-muted">No VJ-tagged series yet.</p>
                    </section>
                @endforelse
            </div>

            @if ($vjsTotal > $vjs->count())
                <div class="text-center mt-4 mb-5">
                    <button type="button" class="btn btn-outline-primary px-4 py-2"
                            data-vj-more="series"
                            data-endpoint="{{ route('frontend.series_more_vjs') }}">
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
