@extends('frontend::layouts.master', ['isBreadCrumb' => true, 'title' => isset($tag) ? $tag->name : __('frontendheader.tags')])

@section('content')
    <section class="section-padding">
        <div class="container-fluid">
            @isset($tag)
                {{-- Single tag: show tagged movies + shows --}}
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h4 class="main-title text-capitalize mb-0">#{{ $tag->name }}</h4>
                    <a href="{{ route('frontend.tag') }}" class="text-primary iq-view-all text-decoration-none">{{ __('streamButtons.view_all') }}</a>
                </div>

                @if ($movies->count())
                    <h6 class="main-title text-capitalize mt-4 mb-3">{{ __('frontendheader.movies') ?? 'Movies' }}</h6>
                    <div class="row row-cols-xl-5 row-cols-md-3 row-cols-3 g-3">
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

                @if (! $movies->count() && ! $shows->count())
                    <div class="text-center py-5 text-muted">{{ __('streamTag.no_results') ?? 'Nothing tagged yet.' }}</div>
                @endif
            @else
                {{-- All tags cloud --}}
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h4 class="main-title text-capitalize mb-0">{{ __('frontendheader.tags') }}</h4>
                    <span class="text-muted">{{ $tags->count() }}</span>
                </div>
                <div class="row g-3 g-lg-4 row-cols-3 row-cols-sm-3 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 row-cols-xxl-6">
                    @forelse ($tags as $t)
                        <div class="col">
                            @include('frontend::components.cards.tags-card', [
                                'title' => $t->name . ' (' . ($t->movies_count + $t->shows_count) . ')',
                                'tagUrl' => route('frontend.tag', $t->slug),
                            ])
                        </div>
                    @empty
                        <div class="col-12 text-center py-5 text-muted">{{ __('streamTag.no_results') ?? 'No tags yet.' }}</div>
                    @endforelse
                </div>
            @endisset
        </div>
    </section>
@endsection
