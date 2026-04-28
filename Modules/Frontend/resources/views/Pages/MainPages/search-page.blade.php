@extends('frontend::layouts.master', [
    'isSweetalert' => true,
    'title' => $q ? 'Search results for: ' . $q : 'Search',
])

@section('content')
    <section class="section-padding">
        <div class="container-fluid px-2 px-md-3">
            {{-- Header: query + count summary. The count line keeps it
                 obvious whether a thin grid is "no results" or just a
                 narrow query, and gives the page a clear focal point. --}}
            <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mt-3 mb-4">
                <div>
                    @if ($q !== '')
                        <h4 class="main-title text-capitalize mb-1 fw-medium">
                            {{ __('streamButtons.search') ?? 'Search' }}:
                            <span class="text-primary">"{{ $q }}"</span>
                        </h4>
                        <p class="text-muted mb-0" style="font-size: 14px;">
                            {{ $movies->count() }} {{ \Illuminate\Support\Str::plural('movie', $movies->count()) }},
                            {{ $shows->count() }} {{ \Illuminate\Support\Str::plural('series', $shows->count()) }}
                        </p>
                    @else
                        <h4 class="main-title text-capitalize mb-1 fw-medium">
                            {{ __('streamButtons.search') ?? 'Search' }}
                        </h4>
                        <p class="text-muted mb-0" style="font-size: 14px;">
                            Type at least two characters in the search bar above to find a movie or series.
                        </p>
                    @endif
                </div>
            </div>

            {{-- Movies --}}
            @if ($movies->count())
                <div class="d-flex align-items-center justify-content-between mt-4 mb-3">
                    <h6 class="main-title text-capitalize mb-0">{{ __('frontendheader.movies') ?? 'Movies' }}</h6>
                </div>
                <div class="row row-cols-xl-5 row-cols-md-3 row-cols-3 g-3">
                    @foreach ($movies as $movie)
                        <div class="col">
                            @include('frontend::components.cards.card-style', [
                                'cardImage' => $movie->poster_url ?: 'media/rabbit-portrait.webp',
                                'cardTitle' => $movie->title,
                                'movietime' => $movie->runtime_minutes
                                    ? floor($movie->runtime_minutes / 60) . 'hr : ' . ($movie->runtime_minutes % 60) . 'mins'
                                    : null,
                                'cardLang' => 'English',
                                'cardPath' => route('frontend.movie_detail', $movie->slug),
                                'cardGenres' => $movie->genres->take(2)->pluck('name')->all(),
                                'productPremium' => (bool) $movie->tier_required,
                                'watchableType' => 'movie',
                                'watchableId'   => $movie->id,
                            ])
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Series --}}
            @if ($shows->count())
                <div class="d-flex align-items-center justify-content-between mt-5 mb-3">
                    <h6 class="main-title text-capitalize mb-0">{{ __('frontendheader.tvshow') ?? 'Series' }}</h6>
                </div>
                <div class="row row-cols-xl-5 row-cols-md-3 row-cols-3 g-3">
                    @foreach ($shows as $show)
                        <div class="col">
                            @include('frontend::components.cards.card-style', [
                                'cardImage' => $show->poster_url ?: 'media/vikings-portrait.webp',
                                'cardTitle' => $show->title,
                                'movietime' => null,
                                'cardLang' => 'English',
                                'cardPath' => route('frontend.series_detail', $show->slug),
                                'cardGenres' => $show->genres->take(2)->pluck('name')->all(),
                                'productPremium' => (bool) $show->tier_required,
                                'watchableType' => 'show',
                                'watchableId'   => $show->id,
                            ])
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- No matches --}}
            @if ($q !== '' && !$movies->count() && !$shows->count())
                <div class="text-center py-5 my-4">
                    <i class="ph ph-magnifying-glass text-muted" style="font-size: 56px;"></i>
                    <h5 class="mt-3 mb-2">No results found</h5>
                    <p class="text-muted mb-4">
                        Nothing matches <strong>"{{ $q }}"</strong>. Try a different title or check the spelling.
                    </p>
                    <a href="{{ route('frontend.movie') }}" class="btn btn-primary">
                        <i class="ph ph-film-strip me-1"></i> Browse all movies
                    </a>
                    <a href="{{ route('frontend.series') }}" class="btn btn-outline-light ms-2">
                        <i class="ph ph-television me-1"></i> Browse all series
                    </a>
                </div>
            @endif
        </div>
    </section>

    @include('frontend::components.widgets.mobile-footer')
@endsection
