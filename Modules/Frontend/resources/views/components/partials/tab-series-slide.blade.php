@php
    /**
     * One series slide in the Tab Slider ("Top 10 Series of the Day").
     * Preserves the Streamit template's exact DOM/classes — only the
     * hardcoded data is swapped.
     *
     * Required variables:
     *   $item   Show (with seasons.episodes loaded)
     *   $rank   int, 1-based position in the Top 10
     */
    $bg = $item->backdrop_url ?: $item->poster_url;
    $bgSrc = media_url($bg, 'media/pirates-ofdayones-orignal.webp');

    $detailUrl = route('frontend.series_detail', $item->slug);
    $releaseLabel = ($item->published_at ?? $item->created_at)?->format('F Y') ?? '';
    $seasonsCount = $item->seasons->count();
    $seasons = $item->seasons->sortBy('number');
@endphp

<li class="swiper-slide tab-slider-banner p-0">
    <div class="tab-slider-banner-images" style="background-image: url('{{ $bgSrc }}');">
        <div class="block-images position-relative w-100">
            <div class="container-fluid">
                <div class="row align-items-center h-100 my-4">
                    <div class="col-lg-5 col-xxl-5">
                        <div class="tab-left-details">
                            <div class="d-flex align-items-center gap-3 mb-4">
                                <a href="javascript:void(0);">
                                    <img src="{{ asset('frontend/images/pages/trending-label.webp') }}"
                                        class="img-fluid trending-label-img rounded-3" alt="img">
                                </a>
                                <span class="text-gold fw-bold font-size-18">#{{ $rank }} {{ __('streamMovies.series_today') }}</span>
                            </div>
                            <h1 class="mb-2 fw-500 text-capitalize texture-text">
                                <a href="{{ $detailUrl }}" class="text-decoration-none text-reset">{{ $item->title }}</a>
                            </h1>
                            @if ($item->synopsis)
                                <p class="mb-0 font-size-14 line-count-3">{{ $item->synopsis }}</p>
                            @endif
                            <ul class="d-flex align-items-center list-inline gap-2 movie-tag p-0 mt-3 mb-40">
                                @if ($releaseLabel)
                                    <li class="font-size-18 trending-list">{{ $releaseLabel }}</li>
                                @endif
                                <li class="font-size-18">{{ $seasonsCount }} {{ __('streamEpisode.season') }}</li>
                            </ul>
                            @include('frontend::components.widgets.custom-button', [
                                'buttonUrl' => $detailUrl,
                                'buttonTitle' => __('streamButtons.stream_now'),
                            ])
                        </div>
                    </div>
                    <div class="col-md-1 col-lg-2 col-xxl-3"></div>
                    <div class="col-md-6 col-lg-5 col-xxl-3 mt-5 mt-md-0 d-none d-lg-block">
                        <div class="tab-block">
                            <h4 class="tab-title text-capitalize mb-0">{{ __('streamEpisode.all_episode') }}</h4>
                            <div class="tab-bottom-bordered border-0">
                                <ul class="nav nav-tabs nav-pills mb-3 overflow-x-scroll" role="tablist">
                                    @foreach ($seasons as $season)
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link {{ $loop->first ? 'active' : '' }}"
                                                data-bs-toggle="pill"
                                                data-bs-target="#series-{{ $item->id }}-s{{ $season->number }}"
                                                type="button" role="tab"
                                                aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                                {{ __('streamEpisode.season') }} {{ $season->number }}
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            <div class="tab-content iq-tab-fade-up">
                                @foreach ($seasons as $season)
                                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                                        id="series-{{ $item->id }}-s{{ $season->number }}"
                                        role="tabpanel" tabindex="0">
                                        <ul class="list-inline m-0 p-0">
                                            @foreach ($season->episodes->sortBy('number')->take(4) as $ep)
                                                @php
                                                    $epImg = $ep->still_url;
                                                    $epSrc = media_url($epImg, 'media/episode/s1e1-the-buddha.webp');
                                                @endphp
                                                <li class="d-flex align-items-center gap-3">
                                                    <div class="image-box flex-shrink-0">
                                                        <a href="{{ $ep->frontendUrl($item) }}">
                                                            <img src="{{ $epSrc }}" alt="{{ $ep->title }}" class="img-fluid rounded">
                                                        </a>
                                                    </div>
                                                    <div class="image-details">
                                                        <h6 class="mb-1 text-capitalize">
                                                            <a href="{{ $ep->frontendUrl($item) }}">
                                                                {{ __('streamEpisode.episode') ?? 'Episode' }} {{ $ep->number }}
                                                            </a>
                                                        </h6>
                                                        <div class="episode-time d-flex align-items-center gap-1 mt-2">
                                                            <i class="ph ph-clock font-size-14"></i>
                                                            <small>{{ $ep->runtime_minutes ? $ep->runtime_minutes . 'm' : '—' }}</small>
                                                        </div>
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</li>
