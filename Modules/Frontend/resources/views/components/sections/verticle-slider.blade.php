@php
    $vs = $topMovies ?? collect();
@endphp
<div class="verticle-slider section-padding-bottom">
    <div class="slider">
        <div class="slider-flex position-relative">
            <div class="slider--col position-relative">
                <div class="vertical-slider-prev swiper-button"><i class="iconly-Arrow-Up-2 icli"></i></div>
                <div class="slider-thumbs" data-swiper="slider-thumbs">
                    <div class="swiper-container" data-swiper="slider-thumbs-inner">
                        <div class="swiper-wrapper top-ten-slider-nav">
                            @foreach ($vs as $item)
                                @php
                                    $thumb = $item->backdrop_url ?: $item->poster_url;
                                    $thumbSrc = $thumb && \Illuminate\Support\Str::startsWith($thumb, ['http://', 'https://'])
                                        ? $thumb
                                        : ($thumb ? asset('frontend/images/' . $thumb) : asset('frontend/images/media/the-first-of-us.webp'));
                                @endphp
                                <div class="swiper-slide swiper-bg">
                                    <div class="block-images position-relative">
                                        <div class="img-box slider--image">
                                            <img src="{{ $thumbSrc }}" class="w-100 rounded-3" alt="{{ $item->title }}" loading="lazy">
                                        </div>
                                        <div class="block-description">
                                            <h6 class="iq-title">{{ $item->title }}</h6>
                                            <div class="movie-time d-flex align-items-center my-2">
                                                @if ($item->runtime_minutes)
                                                    <div class="d-flex align-items-center gap-1 font-size-12">
                                                        <i class="ph ph-clock"></i>
                                                        <span class="text-body">{{ floor($item->runtime_minutes / 60) }}hr : {{ $item->runtime_minutes % 60 }}m</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="vertical-slider-next swiper-button"><i class="iconly-Arrow-Down-2 icli"></i></div>
            </div>
            <div class="slider--col-content position-relative overflow-hidden">
                <div class="slider-images" data-swiper="slider-images">
                    <div class="swiper-container" data-swiper="slider-images-inner">
                        <div class="swiper-wrapper">
                            @foreach ($vs as $item)
                                @php
                                    $bg = $item->backdrop_url ?: $item->poster_url;
                                    $bgSrc = $bg && \Illuminate\Support\Str::startsWith($bg, ['http://', 'https://'])
                                        ? $bg
                                        : ($bg ? asset('frontend/images/' . $bg) : asset('frontend/images/media/gameofhero.webp'));
                                @endphp
                                <div class="swiper-slide">
                                    <div class="slider--image block-images">
                                        <img src="{{ $bgSrc }}" loading="lazy" alt="{{ $item->title }}">
                                    </div>
                                    <div class="description">
                                        <div class="block-description">
                                            @if ($item->relationLoaded('genres') && $item->genres->count())
                                                <ul class="ps-0 mb-2 pb-1 list-inline d-flex flex-wrap align-items-center movie-tag justify-content-center justify-content-lg-start genres-list gap-1 gap-sm-0">
                                                    @foreach ($item->genres->take(3) as $g)
                                                        <li class="text-capitalize font-size-14 letter-spacing-1">
                                                            <a href="javascript:void(0)" class="text-decoration-none">{{ $g->name }}</a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                            <h2 class="iq-title m-0 line-count-2">
                                                <a href="{{ route('frontend.movie_detail', $item->slug) }}">{{ $item->title }}</a>
                                            </h2>
                                            @if ($item->synopsis)
                                                <p class="mt-2 mb-3 line-count-3">{{ $item->synopsis }}</p>
                                            @endif
                                            @include('frontend::components.widgets.custom-button', [
                                                'buttonUrl' => route('frontend.movie_detail', $item->slug),
                                                'buttonTitle' => __('streamButtons.play_now'),
                                            ])
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="swiper-button swiper-button-next"></div>
                        <div class="swiper-button swiper-button-prev"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
