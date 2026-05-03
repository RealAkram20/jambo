@extends('frontend::layouts.master', [
    'isSwiperSlider' => true,
    'isVideoJs' => true,
    'isSelect2' => true,
    'bodyClass' => 'custom-header-relative',
    // Drives both the browser <title> and og:title (via head-tags
    // fallback), so shared links preview as "Show Title - Jambo".
    'title' => $show->title,
])

{{-- Social-preview metadata for the show detail page. Same approach
     as Movies/detail-page: backdrop wins, poster fallback, site-wide
     default last. media_url() resolves legacy bare filenames before
     head-tags absolutes the URL. The Show model's prose field is
     `synopsis`, NOT `description` (the previous version of this
     block read $show->description and silently always fell back to
     the global default). --}}
@if ($show->backdrop_url ?: $show->poster_url)
    @section('seo:image', media_url($show->backdrop_url ?: $show->poster_url))
@endif
@if (!empty($show->synopsis))
    @section('seo:description', \Illuminate\Support\Str::limit(strip_tags((string) $show->synopsis), 200))
@endif

@php
    $backdrop = $show->backdrop_url ?: $show->poster_url;
    $posterSrc = media_url($backdrop, 'media/vikings.webp');
    $trailer = $show->trailer_url;
    // Dedupe by person id — a person may have multiple pivot rows
    // (e.g., both 'actor' and 'director' for the same series) which
    // otherwise surfaces them twice in the same rail.
    $cast = $show->cast
        ->filter(fn ($p) => in_array(($p->pivot->role ?? null), ['actor', 'actress'], true))
        ->unique('id')
        ->values();
    $crew = $show->cast
        ->filter(fn ($p) => in_array(($p->pivot->role ?? null), ['director', 'writer', 'producer']))
        ->unique('id')
        ->values();

    $seasons = $show->seasons->sortBy('number');

    // First episode of the show — the "Watch / Play now" hero button jumps
    // straight here so viewers don't have to click season-1-episode-1
    // manually after landing on the detail page.
    $firstSeason  = $seasons->first();
    $firstEpisode = $firstSeason?->episodes->sortBy('number')->first();
    $firstEpUrl   = $firstEpisode ? $firstEpisode->frontendUrl($show) : '#';
@endphp

@section('content')
    <div class="position-relative">
        <div class="iq-main-slider site-video position-relative">
            @php
                $trailerUrl = $show->trailer_url;
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
                    class="my-video video-js vjs-big-play-centered w-100" loop autoplay muted playsinline preload="metadata"
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
                        'moveName' => $show->title,
                        'isNotTVShowbadge' => true,
                        'movieReleased' => $show->year ?: ($show->published_at?->format('Y') ?? ''),
                        'movieDuration' => $seasons->count() . ' ' . __('streamEpisode.season') . ($seasons->count() > 1 ? 's' : ''),
                        'movieViews' => number_format($show->views_count) . ' ' . __('streamTag.views'),
                        'imdbRating' => $show->rating ?: '—',
                        'movieLanguage' => 'english',
                        'videoUrl' => $firstEpUrl,
                        'movieDescription' => $show->synopsis,
                        'movieGenres' => $show->genres->pluck('name')->all(),
                        'isUpcoming' => $isUpcoming ?? false,
                        'watchableType' => 'show',
                        'watchableId'   => $show->id,
                    ])
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="overflow-hidden">
            {{-- Seasons + Episodes --}}
            @if ($seasons->count())
                <div class="show-episode section-padding">
                    <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
                        <h5 class="main-title text-capitalize mb-0 fw-medium">{{ __('header.episodes') }}</h5>
                    </div>
                    <ul class="nav nav-pills custom-tab-slider episode-nav-btn gap-3 mb-4 pb-2" role="tablist">
                        @foreach ($seasons as $season)
                            <li class="nav-item">
                                <a class="nav-link {{ $loop->first ? 'active show' : '' }}" data-bs-toggle="pill"
                                    href="#season-{{ $season->number }}" role="tab"
                                    aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                    {{ __('streamEpisode.season') }} {{ $season->number }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    <div class="tab-content">
                        @foreach ($seasons as $season)
                            <div id="season-{{ $season->number }}" class="tab-pane animated fadeInUp {{ $loop->first ? 'active show' : '' }}" role="tabpanel">
                                <div class="card-style-slider">
                                    <div class="position-relative swiper swiper-card mt-4 mb-5 overflow-hidden" data-slide="5"
                                        data-laptop="5" data-tab="2" data-mobile="2" data-mobile-sm="1"
                                        data-autoplay="false" data-loop="false">
                                        <div class="p-0 swiper-wrapper m-0 list-inline">
                                            @foreach ($season->episodes->sortBy('number') as $ep)
                                                <div class="swiper-slide">
                                                    @include('frontend::components.cards.episode-card', [
                                                        'episodePath' => $ep->frontendUrl($show),
                                                        'showImg' => $ep->still_url ?: 'media/episode/s1e1-the-buddha.webp',
                                                        'id' => $ep->id,
                                                        'episodeNumber' => 'S' . str_pad($season->number, 2, '0', STR_PAD_LEFT) . 'E' . str_pad($ep->number, 2, '0', STR_PAD_LEFT),
                                                        'episodTitle' => $ep->title,
                                                        'episodeTitlesText' => $ep->title,
                                                        'episodeDetailText' => $ep->synopsis ?: '',
                                                        'episodTime' => $ep->runtime_minutes ? $ep->runtime_minutes . 'm' : '—',
                                                    ])
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Starring --}}
            @if ($cast->count())
                <div class="favourite-person-block section-wraper">
                    <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
                        <h4 class="main-title text-capitalize mb-0 fw-medium">{{ __('sectionTitle.starring') }}</h4>
                    </div>
                    <div class="position-relative swiper swiper-card" data-slide="6" data-laptop="6" data-tab="4" data-mobile="3"
                        data-mobile-sm="3" data-autoplay="false" data-loop="false" data-navigation="true" data-pagination="true">
                        <ul class="p-0 swiper-wrapper m-0 list-inline personality-card">
                            @foreach ($cast as $actor)
                                <li class="swiper-slide">
                                    @include('frontend::components.cards.personality-card', [
                                        'castImage' => $actor->photo_url ?: 'olivia-foster.webp',
                                        'castTitle' => trim(($actor->first_name ?? '') . ' ' . ($actor->last_name ?? '')),
                                        'castCategory' => $actor->pivot->character_name ?: ucfirst($actor->pivot->role ?? 'Actor'),
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

            {{-- Crew --}}
            @if ($crew->count())
                <div class="favourite-person-block">
                    <section class="overflow-hidden">
                        <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
                            <h4 class="main-title text-capitalize mb-0">{{ __('sectionTitle.crew') }}</h4>
                        </div>
                        <div class="position-relative swiper swiper-card" data-slide="6" data-laptop="6" data-tab="4"
                            data-mobile="3" data-mobile-sm="3" data-autoplay="false" data-loop="false"
                            data-navigation="true" data-pagination="true">
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

            {{-- Recommended --}}
            @if ($recommended->count())
                <section class="related-movie-block">
                    <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
                        <h4 class="main-title text-capitalize mb-0">{{ __('sectionTitle.popular_show') ?? 'Popular Series' }}</h4>
                    </div>
                    <div class="card-style-slider">
                        <div class="position-relative swiper swiper-card" data-slide="6" data-laptop="6" data-tab="3"
                            data-mobile="2" data-mobile-sm="2" data-autoplay="false" data-loop="false"
                            data-navigation="true" data-pagination="true">
                            <ul class="p-0 swiper-wrapper m-0 list-inline">
                                @foreach ($recommended as $rec)
                                    <li class="swiper-slide">
                                        @include('frontend::components.cards.card-style', [
                                            'cardImage' => $rec->poster_url ?: 'media/vikings-portrait.webp',
                                            'cardTitle' => $rec->title,
                                            'movietime' => null,
                                            'cardLang' => 'English',
                                            'cardPath' => route('frontend.series_detail', $rec->slug),
                                            'cardGenres' => $rec->genres->take(2)->pluck('name')->all(),
                                            'watchableType' => 'show',
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
        'movieName'     => $show->title,
        'description'   => $show->synopsis,
        'year'          => $show->year ?: ($show->published_at?->format('Y') ?: null),
        'views'         => number_format($show->views_count) . ' ' . __('streamTag.views'),
        'movieDuration' => $seasons->count() . ' ' . __('streamEpisode.season') . ($seasons->count() > 1 ? 's' : ''),
        'ratingCount'   => $show->rating ?: null,
        'genres'        => $show->genres->pluck('name')->all(),
        'tags'          => $show->relationLoaded('tags') ? $show->tags->pluck('name')->all() : [],
        'cast'          => $cast,
        'crew'          => $crew,
        'releaseLabel'  => ($isUpcoming ?? false) && $show->published_at
            ? $show->published_at->format('M j, Y')
            : null,
    ])

    {{-- Reviews & ratings --}}
    @include('frontend::components.partials.reviews-block', [
        'storeRoute'   => route('frontend.series_review_store',   $show->slug),
        'destroyRoute' => route('frontend.series_review_destroy', $show->slug),
    ])

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}
@endsection
