@extends('frontend::layouts.master', ['isSwiperSlider' => true, 'isVideoJs' => true, 'bodyClass' => 'custom-header-relative', 'isSelect2' => true])

@php
    $still = $episode->still_url ?: ($show->backdrop_url ?: $show->poster_url);
    $posterSrc = $still && \Illuminate\Support\Str::startsWith($still, ['http://', 'https://'])
        ? $still
        : ($still ? asset('frontend/images/' . $still) : asset('frontend/images/media/vikings.webp'));
    $trailer = $show->trailer_url ?: 'https://www.youtube.com/watch?v=spGSAeqxVUc';
    $videoSetup = json_encode([
        'techOrder' => ['youtube'],
        'sources' => [['type' => 'video/youtube', 'src' => $trailer]],
        'youtube' => ['modestbranding' => 1, 'rel' => 0, 'showinfo' => 0, 'autoplay' => 1],
        'fullscreen' => true,
    ]);
    $season = $episode->season;
    $seasons = $show->seasons->sortBy('number');
@endphp

@section('content')
    <div class="iq-main-slider site-video position-relative">
        <video id="my-video" poster="{{ $posterSrc }}"
            class="my-video video-js vjs-big-play-centered w-100" loop autoplay muted preload="auto"
            data-setup='{!! $videoSetup !!}'>
        </video>
    </div>
    <div class="details-part">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="trending-info pt-0 pb-0">
                        <div class="row justify-content-between">
                            <div class="col-xl-12 col-12 mb-auto">
                                @include('frontend::components.cards.movie-description', [
                                    'moveName' => 'S' . str_pad($season->number, 2, '0', STR_PAD_LEFT) . 'E' . str_pad($episode->number, 2, '0', STR_PAD_LEFT) . ' — ' . $episode->title,
                                    'movieReleased' => $episode->published_at?->format('Y') ?: ($show->year ?? ''),
                                    'movieViews' => '— ' . __('frontendplaylist.views'),
                                    'isNotimdbRating' => true,
                                    'movieDuration' => $episode->runtime_minutes ? $episode->runtime_minutes . ' min' : '—',
                                    'isNotTVShowbadge' => true,
                                    'isNotstartWatching' => true,
                                    'isNotwatchList' => true,
                                    'movieDescription' => $episode->synopsis,
                                    'movieGenres' => $show->genres->pluck('name')->all(),
                                ])
                                <div class="mt-3">
                                    <small class="text-muted">{{ __('sectionTitle.from') ?? 'From' }}:</small>
                                    <a href="{{ route('frontend.tvshow_detail', $show->slug) }}" class="ms-1 text-primary text-decoration-none">{{ $show->title }}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="overflow-hidden">
            @if ($seasons->count())
                <div class="show-episode section-padding">
                    <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
                        <h5 class="main-title text-capitalize mb-0 fw-medium">{{ __('header.episodes') }}</h5>
                    </div>
                    <ul class="nav nav-pills custom-tab-slider episode-nav-btn gap-3 mb-4 pb-2" role="tablist">
                        @foreach ($seasons as $s)
                            <li class="nav-item">
                                <a class="nav-link {{ $s->id === $season->id ? 'active show' : '' }}" data-bs-toggle="pill"
                                    href="#season-{{ $s->number }}" role="tab"
                                    aria-selected="{{ $s->id === $season->id ? 'true' : 'false' }}">
                                    {{ __('streamEpisode.season') }} {{ $s->number }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    <div class="tab-content">
                        @foreach ($seasons as $s)
                            <div id="season-{{ $s->number }}" class="tab-pane animated fadeInUp {{ $s->id === $season->id ? 'active show' : '' }}" role="tabpanel">
                                <div class="card-style-slider">
                                    <div class="position-relative swiper swiper-card mt-4 mb-5 overflow-hidden" data-slide="5"
                                        data-laptop="5" data-tab="2" data-mobile="2" data-mobile-sm="1"
                                        data-autoplay="false" data-loop="false">
                                        <div class="p-0 swiper-wrapper m-0 list-inline">
                                            @foreach ($s->episodes->sortBy('number') as $ep)
                                                <div class="swiper-slide">
                                                    @include('frontend::components.cards.episode-card', [
                                                        'episodePath' => route('frontend.episode', $ep->id),
                                                        'showImg' => $ep->still_url ?: 'media/episode/s1e1-the-buddha.webp',
                                                        'id' => $ep->id,
                                                        'episodeNumber' => 'S' . str_pad($s->number, 2, '0', STR_PAD_LEFT) . 'E' . str_pad($ep->number, 2, '0', STR_PAD_LEFT),
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
        </div>
    </div>

    @include('frontend::components.widgets.mobile-footer')
@endsection
