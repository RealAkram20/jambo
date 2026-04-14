@extends('frontend::layouts.master', [
    'isSwiperSlider' => true,
    'isVideoJs' => true,
    'isSelect2' => true,
    'bodyClass' => 'custom-header-relative',
])

@php
    $backdrop = $show->backdrop_url ?: $show->poster_url;
    $posterSrc = $backdrop && \Illuminate\Support\Str::startsWith($backdrop, ['http://', 'https://'])
        ? $backdrop
        : ($backdrop ? asset('frontend/images/' . $backdrop) : asset('frontend/images/media/vikings.webp'));
    $trailer = $show->trailer_url ?: 'https://www.youtube.com/watch?v=spGSAeqxVUc';
    $cast = $show->cast->filter(fn ($p) => ($p->pivot->role ?? null) === 'actor');
    $crew = $show->cast->filter(fn ($p) => in_array(($p->pivot->role ?? null), ['director', 'writer', 'producer']));
    $seasons = $show->seasons->sortBy('number');
@endphp

@section('content')
    <div class="position-relative">
        <div class="iq-main-slider site-video position-relative">
            @php
                $videoSetup = json_encode([
                    'techOrder' => ['youtube'],
                    'sources' => [['type' => 'video/youtube', 'src' => $trailer]],
                    'youtube' => ['modestbranding' => 1, 'rel' => 0, 'showinfo' => 0, 'autoplay' => 1],
                    'fullscreen' => true,
                ]);
            @endphp
            <video id="my-video" poster="{{ $posterSrc }}"
                class="my-video video-js vjs-big-play-centered w-100" loop autoplay muted preload="auto"
                data-setup='{!! $videoSetup !!}'>
            </video>
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
                        'videoUrl' => route('frontend.episode'),
                        'movieDescription' => $show->synopsis,
                        'movieGenres' => $show->genres->pluck('name')->all(),
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
                                                        'episodePath' => route('frontend.episode'),
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
                    <div class="position-relative swiper swiper-card" data-slide="11" data-laptop="11" data-tab="4" data-mobile="2"
                        data-mobile-sm="2" data-autoplay="false" data-loop="true" data-navigation="true" data-pagination="true">
                        <ul class="p-0 swiper-wrapper m-0 list-inline personality-card">
                            @foreach ($cast as $actor)
                                <li class="swiper-slide">
                                    @include('frontend::components.cards.personality-card', [
                                        'castImage' => $actor->photo_url ?: 'olivia-foster.webp',
                                        'castTitle' => trim(($actor->first_name ?? '') . ' ' . ($actor->last_name ?? '')),
                                        'castCategory' => $actor->pivot->character_name ?: 'Actor',
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
                        <div class="position-relative swiper swiper-card" data-slide="11" data-laptop="11" data-tab="4"
                            data-mobile="2" data-mobile-sm="2" data-autoplay="false" data-loop="true"
                            data-navigation="true" data-pagination="true">
                            <ul class="p-0 swiper-wrapper m-0 list-inline personality-card">
                                @foreach ($crew as $person)
                                    <li class="swiper-slide">
                                        @include('frontend::components.cards.personality-card', [
                                            'castImage' => $person->photo_url ?: 'maria-rodriguez.webp',
                                            'castTitle' => trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? '')),
                                            'castCategory' => ucfirst($person->pivot->role ?? 'Crew'),
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
                        <h4 class="main-title text-capitalize mb-0">{{ __('sectionTitle.popular_show') ?? 'Popular Shows' }}</h4>
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
                                            'cardPath' => route('frontend.tvshow_detail', $rec->slug),
                                            'cardGenres' => $rec->genres->take(2)->pluck('name')->all(),
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

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}
@endsection
