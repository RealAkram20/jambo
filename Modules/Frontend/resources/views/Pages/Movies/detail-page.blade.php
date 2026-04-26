@extends('frontend::layouts.master', ['isSwiperSlider' => true, 'isVideoJs' => true, 'bodyClass' => 'custom-header-relative', 'isSelect2' => true])

@php
    $backdrop = $movie->backdrop_url ?: $movie->poster_url;
    $posterSrc = media_url($backdrop, 'media/gameofhero.webp');
    $trailer = $movie->trailer_url;
    // Dedupe by person id — a person may have multiple pivot rows
    // (e.g., both 'actor' and 'director' for the same title) which
    // otherwise surfaces them twice in the same rail.
    $cast = $movie->cast
        ->filter(fn ($p) => ($p->pivot->role ?? null) === 'actor')
        ->unique('id')
        ->values();
    $crew = $movie->cast
        ->filter(fn ($p) => in_array(($p->pivot->role ?? null), ['director', 'writer', 'producer']))
        ->unique('id')
        ->values();
@endphp

@section('content')
<div class="position-relative" id="jambo-hero-wrap">
    <div class="iq-main-slider site-video position-relative">
        @php
            $trailerUrl = $movie->trailer_url;
            $isYouTubeTrailer = $trailerUrl && (
                str_contains($trailerUrl, 'youtube.com') ||
                str_contains($trailerUrl, 'youtu.be')
            );
            $hasTrailer = !empty($trailerUrl);

            if ($isYouTubeTrailer) {
                $videoSetup = json_encode([
                    'techOrder' => ['youtube'],
                    'sources' => [['type' => 'video/youtube', 'src' => $trailerUrl]],
                    'youtube' => ['modestbranding' => 1, 'rel' => 0, 'showinfo' => 0, 'autoplay' => 1],
                    'fullscreen' => true,
                ]);
            } elseif ($hasTrailer) {
                $videoSetup = json_encode([
                    'sources' => [['type' => 'video/mp4', 'src' => $trailerUrl]],
                    'fullscreen' => true,
                ]);
            }
        @endphp

        @if ($hasTrailer)
            <video id="my-video" poster="{{ $posterSrc }}"
                class="my-video video-js vjs-big-play-centered w-100" loop autoplay muted preload="auto"
                data-setup='{!! $videoSetup !!}'>
            </video>
        @else
            <div class="w-100" style="aspect-ratio:21/9; background: url('{{ $posterSrc }}') center/cover no-repeat;"></div>
        @endif
    </div>

    <div class="movie-detail-part position-relative">
        <div class="trending-info pt-0 pb-0">
            <div class="details-parts">
                @include('frontend::components.cards.movie-description', [
                    'moveName' => $movie->title,
                    'movieType' => $movie->tier_required ? strtoupper($movie->tier_required) : 'PG',
                    'movieDuration' => $movie->runtime_minutes ? floor($movie->runtime_minutes / 60) . 'hr : ' . ($movie->runtime_minutes % 60) . 'mins' : '—',
                    'movieReleased' => $movie->year ?: ($movie->published_at?->format('Y') ?? ''),
                    'movieViews' => number_format($movie->views_count) . ' ' . __('streamTag.views'),
                    'imdbRating' => $movie->rating ?: '—',
                    'movieLanguage' => 'english',
                    'videoUrl' => route('frontend.watch', ['slug' => $movie->slug]),
                    'movieDescription' => $movie->synopsis,
                    'movieGenres' => $movie->genres->pluck('name')->all(),
                    'subscribeToWatch' => ! $canWatch,
                    'isUpcoming' => $isUpcoming ?? false,
                    'watchableType' => 'movie',
                    'watchableId'   => $movie->id,
                ])
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="overflow-hidden">
        {{-- Starring start --}}
        @if ($cast->count())
            <div class="favourite-person-block section-wraper">
                <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
                    <h4 class="main-title text-capitalize mb-0 fw-medium">{{ __('sectionTitle.starring') }}</h4>
                </div>
                <div class="position-relative swiper swiper-card" data-slide="11" data-laptop="11" data-tab="4" data-mobile="2"
                    data-mobile-sm="2" data-autoplay="false" data-loop="true" data-navigation="true" data-pagination="true">
                    <ul class="p-0 swiper-wrapper m-0 list-inline personality-card">
                        @foreach ($cast as $actor)
                            <li class="swiper-slide">
                                @include('frontend::components.cards.personality-card', [
                                    'castImage' => $actor->photo_url ?: 'olivia-foster.webp',
                                    'castTitle' => trim(($actor->first_name ?? '') . ' ' . ($actor->last_name ?? '')),
                                    'castCategory' => $actor->pivot->character_name ?: 'Actor',
                                    'castLink' => $actor->slug ? route('frontend.cast_details', $actor->slug) : '#',
                                ])
                            </li>
                        @endforeach
                    </ul>
                    <div class="d-none d-lg-block">
                        <div class="swiper-button swiper-button-next"></div>
                        <div class="swiper-button swiper-button-prev"></div>
                    </div>
                </div>
            </div>
        @endif
        {{-- Starring End --}}

        {{-- Crew start --}}
        @if ($crew->count())
            <div class="favourite-person-block">
                <section class="overflow-hidden">
                    <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
                        <h4 class="main-title text-capitalize mb-0">{{ __('sectionTitle.crew') }}</h4>
                    </div>
                    <div class="position-relative swiper swiper-card" data-slide="11" data-laptop="11" data-tab="4"
                        data-mobile="2" data-mobile-sm="2" data-autoplay="false" data-loop="true" data-navigation="true"
                        data-pagination="true">
                        <ul class="p-0 swiper-wrapper m-0 list-inline personality-card">
                            @foreach ($crew as $person)
                                <li class="swiper-slide">
                                    @include('frontend::components.cards.personality-card', [
                                        'castImage' => $person->photo_url ?: 'maria-rodriguez.webp',
                                        'castTitle' => trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? '')),
                                        'castCategory' => ucfirst($person->pivot->role ?? 'Crew'),
                                        'castLink' => $person->slug ? route('frontend.cast_details', $person->slug) : '#',
                                    ])
                                </li>
                            @endforeach
                        </ul>
                        <div class="swiper-button swiper-button-next d-none d-lg-block"></div>
                        <div class="swiper-button swiper-button-prev d-none d-lg-block"></div>
                    </div>
                </section>
            </div>
        @endif
        {{-- Crew End --}}

        {{-- Recommended --}}
        @if ($recommended->count())
            <section class="related-movie-block">
                <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
                    <h4 class="main-title text-capitalize mb-0">{{ __('sectionTitle.recommended_movie') }}</h4>
                </div>
                <div class="card-style-slider">
                    <div class="position-relative swiper swiper-card" data-slide="6" data-laptop="6" data-tab="3"
                        data-mobile="2" data-mobile-sm="2" data-autoplay="false" data-loop="false" data-navigation="true"
                        data-pagination="true">
                        <ul class="p-0 swiper-wrapper m-0 list-inline">
                            @foreach ($recommended as $rec)
                                <li class="swiper-slide">
                                    @include('frontend::components.cards.card-style', [
                                        'cardImage' => $rec->poster_url ?: 'media/rabbit-portrait.webp',
                                        'cardTitle' => $rec->title,
                                        'movietime' => $rec->runtime_minutes ? floor($rec->runtime_minutes / 60) . 'hr : ' . ($rec->runtime_minutes % 60) . 'mins' : null,
                                        'cardLang' => 'English',
                                        'cardPath' => route('frontend.movie_detail', $rec->slug),
                                        'cardGenres' => $rec->genres->take(2)->pluck('name')->all(),
                                        'watchableType' => 'movie',
                                        'watchableId'   => $rec->id,
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
    </div>
</div>

@include('frontend::components.widgets.details-description-modal', [
    'movieName'     => $movie->title,
    'description'   => $movie->synopsis,
    'year'          => $movie->year ?: ($movie->published_at?->format('Y') ?: null),
    'views'         => number_format($movie->views_count) . ' ' . __('streamTag.views'),
    'movieDuration' => $movie->runtime_minutes
        ? floor($movie->runtime_minutes / 60) . 'hr : ' . ($movie->runtime_minutes % 60) . 'mins'
        : null,
    'ratingCount'   => $movie->rating ?: null,
    'genres'        => $movie->genres->pluck('name')->all(),
    'tags'          => $movie->relationLoaded('tags') ? $movie->tags->pluck('name')->all() : [],
    'cast'          => $cast,
    'crew'          => $crew,
    'releaseLabel'  => ($isUpcoming ?? false) && $movie->published_at
        ? $movie->published_at->format('M j, Y')
        : null,
])

{{-- Reviews & ratings --}}
@include('frontend::components.partials.reviews-block', [
    'storeRoute'   => route('frontend.movie_review_store',   $movie->slug),
    'destroyRoute' => route('frontend.movie_review_destroy', $movie->slug),
])

{{-- Mobile Footer --}}
@include('frontend::components.widgets.mobile-footer')
{{-- Mobile Footer End --}}

@endsection
