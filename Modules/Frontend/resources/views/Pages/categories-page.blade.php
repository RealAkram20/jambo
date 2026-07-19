@extends('frontend::layouts.master', ['isBreadCrumb' => true, 'title' => isset($category) ? $category->name : 'Categories'])

@section('content')
    <section class="section-padding">
        <div class="container-fluid">
            @isset($category)
                {{-- Single category: movies + shows assigned to it --}}
                <div class="row">
                    <div class="col-sm-12 my-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h1 class="main-title text-capitalize mb-1 h5 fw-medium">{{ $category->name }}</h1>
                                @if ($category->description)
                                    <p class="text-muted mb-0 small">{{ $category->description }}</p>
                                @endif
                            </div>
                            <a href="{{ route('frontend.all-categories') }}"
                               class="text-primary iq-view-all text-decoration-none">
                                {{ __('streamButtons.view_all') ?? 'All categories' }}
                            </a>
                        </div>
                    </div>
                </div>

                @if ($movies->count())
                    <h6 class="main-title text-capitalize mt-4 mb-3">
                        {{ __('frontendheader.movies') ?? 'Movies' }}
                    </h6>
                    <div class="row row-cols-3 row-cols-md-4 row-cols-lg-6 row-cols-xl-8 g-3">
                        @foreach ($movies as $movie)
                            <div class="col">
                                @include('frontend::components.cards.card-style', [
                                    'cardImage'      => $movie->poster_url ?: 'media/rabbit-portrait.webp',
                                    'cardTitle'      => $movie->title,
                                    'movietime'      => $movie->runtime_minutes
                                        ? floor($movie->runtime_minutes / 60) . 'hr : ' . ($movie->runtime_minutes % 60) . 'mins'
                                        : null,
                                    'cardLang'       => 'English',
                                    'cardPath'       => route('frontend.movie_detail', $movie->slug),
                                    'cardGenres'     => $movie->genres->take(2)->pluck('name')->all(),
                                    'productPremium' => (bool) $movie->tier_required,
                                    'watchableType'  => 'movie',
                                    'watchableId'    => $movie->id,
                                ])
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($shows->count())
                    <h6 class="main-title text-capitalize mt-5 mb-3">
                        {{ __('frontendheader.tvshow') ?? 'Series' }}
                    </h6>
                    <div class="row row-cols-3 row-cols-md-4 row-cols-lg-6 row-cols-xl-8 g-3">
                        @foreach ($shows as $show)
                            <div class="col">
                                @include('frontend::components.cards.card-style', [
                                    'cardImage'      => $show->poster_url ?: 'media/vikings-portrait.webp',
                                    'cardTitle'      => $show->title,
                                    'movietime'      => null,
                                    'cardLang'       => 'English',
                                    'cardPath'       => route('frontend.series_detail', $show->slug),
                                    'cardGenres'     => $show->genres->take(2)->pluck('name')->all(),
                                    'productPremium' => (bool) $show->tier_required,
                                    'watchableType'  => 'show',
                                    'watchableId'    => $show->id,
                                ])
                            </div>
                        @endforeach
                    </div>
                @endif

                @if (! $movies->count() && ! $shows->count())
                    <div class="text-center py-5 text-muted">
                        Nothing assigned to this category yet.
                    </div>
                @endif
            @else
                {{-- Grid of all categories --}}
                <div class="row">
                    <div class="col-sm-12 my-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <h1 class="main-title text-capitalize mb-0 h5 fw-medium">Categories</h1>
                        </div>
                    </div>
                </div>
                <div class="row row-cols-xl-5 row-cols-md-2 row-cols-1 geners-card geners-style-grid">
                    @forelse ($categories as $cat)
                        <div class="col slide-items">
                            @include('frontend::components.cards.genres-card', [
                                'genersTitle' => $cat->name,
                                'genersImage' => $cat->cover_url ?: 'media/rabbit.webp',
                                'genersUrl'   => route('frontend.category', $cat->slug),
                            ])
                        </div>
                    @empty
                        <div class="col-12 text-center py-5 text-muted">
                            {{ __('streamTag.no_results') ?? 'No categories yet.' }}
                        </div>
                    @endforelse
                </div>
            @endisset
        </div>
    </section>

    @include('frontend::components.widgets.mobile-footer')
@endsection
