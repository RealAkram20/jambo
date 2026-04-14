@php
    // Use popular movies as trending candidates; fall back to latest if empty.
    $trending = ($popularMovies ?? collect())->count()
        ? $popularMovies
        : ($latestMovies ?? collect());
    $trending = $trending->take(8);
@endphp

<section class="tranding-tab-slider section-padding">
    <div class="container-fluid">
        <div class="row m-0 p-0">
            <div id="iq-trending" class="s-margin iq-tvshow-tabs iq-trending-tabs overflow-hidden">
                <div class="d-flex align-items-center justify-content-between px-1">
                    <h4 class="main-title text-capitalize mb-0 fw-medium">{{ __('streamTag.trending') }}</h4>
                </div>

                @if ($trending->count())
                    <div class="trending-contens position-relative">
                        <div id="gallery-top" class="swiper gallery-thumbs pt-0" data-swiper="gallery-top">
                            <ul class="swiper-wrapper list-inline m-0 trending-swiper-padding trending-slider-nav align-items-center">
                                @foreach ($trending as $item)
                                    @php
                                        $thumb = $item->backdrop_url ?: $item->poster_url;
                                        $thumbSrc = $thumb && \Illuminate\Support\Str::startsWith($thumb, ['http://', 'https://'])
                                            ? $thumb
                                            : ($thumb ? asset('frontend/images/' . $thumb) : asset('frontend/images/media/rabbit.webp'));
                                    @endphp
                                    <li class="swiper-slide">
                                        <a href="{{ route('frontend.movie_detail', $item->slug) }}" tabindex="0">
                                            <div class="movie-swiper position-relative">
                                                <img src="{{ $thumbSrc }}" alt="{{ $item->title }}" />
                                            </div>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <div id="gallery-thumbs" class="swiper gallery-top" data-swiper="gallery-thumbs">
                            <ul class="swiper-wrapper list-inline m-0 p-0">
                                @foreach ($trending as $item)
                                    @php
                                        $big = $item->backdrop_url ?: $item->poster_url;
                                        $bigSrc = $big && \Illuminate\Support\Str::startsWith($big, ['http://', 'https://'])
                                            ? $big
                                            : ($big ? asset('frontend/images/' . $big) : asset('frontend/images/media/rabbit.webp'));
                                    @endphp
                                    <li class="swiper-slide slider-big-img-6">
                                        <div class="shows-img position-relative">
                                            <img src="{{ $bigSrc }}" alt="{{ $item->title }}" class="img-fluid w-100 rounded-3" />
                                            <div class="shows-content position-absolute">
                                                <div class="row align-items-center h-100">
                                                    <div class="col-lg-7">
                                                        @if ($item->relationLoaded('genres') && $item->genres->count())
                                                            <ul class="p-0 mb-1 list-inline d-flex flex-wrap align-items-center movie-tag">
                                                                @foreach ($item->genres->take(3) as $g)
                                                                    <li class="trending-list"><a href="javascript:void(0)">{{ $g->name }}</a></li>
                                                                @endforeach
                                                            </ul>
                                                        @endif
                                                        <h3 class="trending-text big-font fw-bold texture-text text-uppercase mb-0">
                                                            <a href="{{ route('frontend.movie_detail', $item->slug) }}">{{ $item->title }}</a>
                                                        </h3>
                                                        @if ($item->synopsis)
                                                            <p class="trending-dec line-count-2 mt-2 mb-3">{{ $item->synopsis }}</p>
                                                        @endif
                                                        <a href="{{ route('frontend.movie_detail', $item->slug) }}" class="btn btn-primary position-relative rounded-3">
                                                            <div class="d-flex align-items-center gap-2">
                                                                <span class="button-text">{{ __('streamButtons.play_now') }}</span>
                                                                <i class="ph-fill ph-play"></i>
                                                            </div>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
