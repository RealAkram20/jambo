@extends('frontend::layouts.master', [
    'isSwiperSlider' => true,
    'isVideoJs' => true,
    'bodyClass' => 'custom-header-relative',
])

@php
    $fullName = trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? ''));
    $photo = $person->photo_url;
    $photoSrc = $photo && \Illuminate\Support\Str::startsWith($photo, ['http://', 'https://'])
        ? $photo
        : ($photo ? asset('frontend/images/cast/' . $photo) : asset('frontend/images/cast/charles-melton.webp'));
@endphp

@section('content')
    <div class="section-padding personality-detail">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-3">
                    <div class="cast-box position-relative">
                        <img src="{{ $photoSrc }}" class="img-fluid object-cover w-100 rounded-3" alt="{{ $fullName }}" loading="lazy">
                    </div>
                    <h5 class="mt-5 pt-4 mb-4 text-white fw-500">{{ __('favouritePersonalities.personal_details') }}</h5>
                    <ul class="list-inline p-0 m-0">
                        @if ($person->birth_date)
                            <li class="mb-3">
                                <h5 class="mt-0 mb-2">{{ __('favouritePersonalities.born') }}</h5>
                                <ul class="person-birth-detail d-flex align-items-center flex-wrap column-gap-5 row-gap-1 p-0 m-0">
                                    <li class="list-group-item">{{ __('favouritePersonalities.birthday') }}: {{ $person->birth_date->format('Y-m-d') }}</li>
                                </ul>
                            </li>
                        @endif
                        @if ($person->death_date)
                            <li class="mb-3">
                                <h5 class="mt-0 mb-2">Died</h5>
                                <ul class="person-birth-detail d-flex align-items-center flex-wrap column-gap-5 row-gap-1 p-0 m-0">
                                    <li class="list-group-item">{{ $person->death_date->format('Y-m-d') }}</li>
                                </ul>
                            </li>
                        @endif
                    </ul>
                </div>
                <div class="col-md-9 mt-5 mt-md-0">
                    <h4 class="mb-3">{{ $fullName }}</h4>
                    @if ($person->known_for)
                        <ul class="person-category d-flex flex-wrap align-items-center gap-5 ps-0">
                            <li class="list-group-item"><span>{{ $person->known_for }}</span></li>
                        </ul>
                    @endif

                    @if ($person->bio)
                        <p>{{ $person->bio }}</p>
                    @endif

                    <div class="actor-history">
                        <div class="">
                            <h4 class="">{{ __('favouritePersonalities.person_history') }}</h4>
                        </div>
                    </div>

                    {{-- tabs start --}}
                    <div class="content-details trending-info personal-detail">
                        <ul class="nav nav-underline d-flex nav nav-pills align-items-center text-center my-5" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active show fw-bold" data-bs-toggle="pill" href="#all" role="tab" aria-selected="true">
                                    {{ __('favouritePersonalities.all') }} ({{ $person->movies->count() + $person->shows->count() }})
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link fw-bold" data-bs-toggle="pill" href="#movies" role="tab" aria-selected="false">
                                    {{ __('frontendheader.movie') }} ({{ $person->movies->count() }})
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link fw-bold" data-bs-toggle="pill" href="#tvshows" role="tab" aria-selected="false">
                                    {{ __('frontendheader.tvshow') ?? 'Series' }} ({{ $person->shows->count() }})
                                </a>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div id="all" class="tab-pane animated fadeInUp active show" role="tabpanel">
                                <div class="row row-cols-xl-4 row-cols-md-3 row-cols-2 g-3">
                                    @foreach ($person->movies as $movie)
                                        <div class="col">
                                            @include('frontend::components.cards.card-style', [
                                                'cardImage' => $movie->poster_url ?: 'media/rabbit-portrait.webp',
                                                'cardTitle' => $movie->title,
                                                'movietime' => $movie->runtime_minutes ? floor($movie->runtime_minutes / 60) . 'hr : ' . ($movie->runtime_minutes % 60) . 'mins' : null,
                                                'cardLang' => 'English',
                                                'cardPath' => route('frontend.movie_detail', $movie->slug),
                                                'watchableType' => 'movie',
                                                'watchableId'   => $movie->id,
                                            ])
                                        </div>
                                    @endforeach
                                    @foreach ($person->shows as $show)
                                        <div class="col">
                                            @include('frontend::components.cards.card-style', [
                                                'cardImage' => $show->poster_url ?: 'media/vikings-portrait.webp',
                                                'cardTitle' => $show->title,
                                                'movietime' => null,
                                                'cardLang' => 'English',
                                                'cardPath' => route('frontend.series_detail', $show->slug),
                                                'watchableType' => 'show',
                                                'watchableId'   => $show->id,
                                            ])
                                        </div>
                                    @endforeach
                                    @if (! $person->movies->count() && ! $person->shows->count())
                                        <div class="col-12 text-center py-5 text-muted">{{ __('streamTag.no_results') ?? 'No appearances yet.' }}</div>
                                    @endif
                                </div>
                            </div>
                            <div id="movies" class="tab-pane animated fadeInUp" role="tabpanel">
                                <div class="row row-cols-xl-4 row-cols-md-3 row-cols-2 g-3">
                                    @forelse ($person->movies as $movie)
                                        <div class="col">
                                            @include('frontend::components.cards.card-style', [
                                                'cardImage' => $movie->poster_url ?: 'media/rabbit-portrait.webp',
                                                'cardTitle' => $movie->title,
                                                'movietime' => $movie->runtime_minutes ? floor($movie->runtime_minutes / 60) . 'hr : ' . ($movie->runtime_minutes % 60) . 'mins' : null,
                                                'cardLang' => 'English',
                                                'cardPath' => route('frontend.movie_detail', $movie->slug),
                                                'watchableType' => 'movie',
                                                'watchableId'   => $movie->id,
                                            ])
                                        </div>
                                    @empty
                                        <div class="col-12 text-center py-5 text-muted">{{ __('streamTag.no_results') ?? 'No movies yet.' }}</div>
                                    @endforelse
                                </div>
                            </div>
                            <div id="tvshows" class="tab-pane animated fadeInUp" role="tabpanel">
                                <div class="row row-cols-xl-4 row-cols-md-3 row-cols-2 g-3">
                                    @forelse ($person->shows as $show)
                                        <div class="col">
                                            @include('frontend::components.cards.card-style', [
                                                'cardImage' => $show->poster_url ?: 'media/vikings-portrait.webp',
                                                'cardTitle' => $show->title,
                                                'movietime' => null,
                                                'cardLang' => 'English',
                                                'cardPath' => route('frontend.series_detail', $show->slug),
                                                'watchableType' => 'show',
                                                'watchableId'   => $show->id,
                                            ])
                                        </div>
                                    @empty
                                        <div class="col-12 text-center py-5 text-muted">{{ __('streamTag.no_results') ?? 'No shows yet.' }}</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
