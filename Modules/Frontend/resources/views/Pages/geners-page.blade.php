@extends('frontend::layouts.master', ['isBreadCrumb' => true, 'title' => isset($genre) ? $genre->name : __('frontendheader.geners')])

@section('content')
    <section class="section-padding">
        <div class="container-fluid">
            @isset($genre)
                {{-- Single genre: show movies + shows in that genre --}}
                <div class="row">
                    <div class="col-sm-12 my-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <h5 class="main-title text-capitalize mb-0">{{ $genre->name }}</h5>
                            <a href="{{ route('frontend.all-genres') }}" class="text-primary iq-view-all text-decoration-none">{{ __('streamButtons.view_all') }}</a>
                        </div>
                    </div>
                </div>

                @if ($movies->count())
                    <h6 class="main-title text-capitalize mt-4 mb-3">{{ __('frontendheader.movies') ?? 'Movies' }}</h6>
                    <div class="row row-cols-xl-5 row-cols-md-3 row-cols-2 g-3">
                        @foreach ($movies as $movie)
                            <div class="col">
                                @include('frontend::components.cards.card-style', [
                                    'cardImage' => $movie->poster_url ?: 'media/rabbit-portrait.webp',
                                    'cardTitle' => $movie->title,
                                    'movietime' => $movie->runtime_minutes ? floor($movie->runtime_minutes / 60) . 'hr : ' . ($movie->runtime_minutes % 60) . 'mins' : null,
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

                @if ($shows->count())
                    <h6 class="main-title text-capitalize mt-5 mb-3">{{ __('frontendheader.tvshow') ?? 'Series' }}</h6>
                    <div class="row row-cols-xl-5 row-cols-md-3 row-cols-2 g-3">
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

                @if (! $movies->count() && ! $shows->count())
                    <div class="text-center py-5 text-muted">{{ __('streamTag.no_results') ?? 'No content in this genre yet.' }}</div>
                @endif
            @else
                {{-- Grid of all genres --}}
                <div class="row">
                    <div class="col-sm-12 my-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <h5 class="main-title text-capitalize mb-0">{{ __('frontendheader.geners') }}</h5>
                            <span class="text-muted">{{ $genres->count() }}</span>
                        </div>
                    </div>
                </div>
                <div class="row row-cols-xl-5 row-cols-md-2 row-cols-1 geners-card geners-style-grid">
                    @forelse ($genres as $g)
                        <div class="col slide-items">
                            @include('frontend::components.cards.genres-card', [
                                'genersTitle' => $g->name,
                                'genersImage' => $g->featured_image_url ?: 'media/rabbit.webp',
                                'genersUrl' => route('frontend.genres', $g->slug),
                            ])
                        </div>
                    @empty
                        <div class="col-12 text-center py-5 text-muted">{{ __('streamTag.no_results') ?? 'No genres yet.' }}</div>
                    @endforelse
                </div>
            @endisset
        </div>
    </section>
@endsection
