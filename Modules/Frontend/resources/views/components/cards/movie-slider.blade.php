<div class="swiper-slide {{ $movieCard }} p-0">
    <div class="movie-banner-image" style="background-image: url('{{ asset('frontend/images/' . $imagePath) }}');">

        <div class="shows-content h-100 container-fluid position-relative">
            <div class="row align-items-center h-100 slider-content-full-height">
                <div class="col-lg-7 col-md-12">
                    <h2 class="texture-text big-font letter-spacing-1 line-count-1 text-capitalize"
                        data-animation-in="fadeInLeft" data-delay-in="0.6">
                        {{ $movieTitle }}
                    </h2>
                    <div class="flex-wrap align-items-center">
                        <div class="slider-ratting d-flex align-items-center gap-3 flex-wrap mb-3 mb-md-0">
                            @if (isset($movieRating))
                                <ul
                                    class="ratting-start p-0 m-0 list-inline text-warning d-flex align-items-center justify-content-left">
                                    <li>
                                        <i class="ph-fill ph-star" aria-hidden="true"></i>
                                    </li>
                                    <li>
                                        <i class="ph-fill ph-star" aria-hidden="true"></i>
                                    </li>
                                    <li>
                                        <i class="ph-fill ph-star" aria-hidden="true"></i>
                                    </li>
                                    <li>
                                        <i class="ph-fill ph-star-half" aria-hidden="true"></i>
                                    </li>
                                    <li>
                                        <i class="ph ph-star" aria-hidden="true"></i>
                                    </li>
                                </ul>
                            @else
                            @endif

                            @if (isset($movieRange))
                                <div class="d-flex align-items-center gap-1">
                                    <span class="text-white">
                                        {{ $movieRange }}
                                    </span>

                                    <img class="imdb-img" alt="imdb-img"
                                        src="{{ asset('frontend/images/pages/imdb-logo.svg') }}">
                                </div>
                            @else
                            @endif

                            @if (isset($movieCate))
                                <span class="badge bg-secondary">
                                    @if (isset($movieCateIcon))
                                        <i class="ph ph-eye"></i>
                                    @else
                                    @endif
                                    {{ $movieCate }}
                                </span>
                            @else
                            @endif

                            @if (isset($movieTime))
                                <div class="font-size-16">
                                    <i class="ph ph-clock align-middle"></i>
                                    <span class="trending-time font-normal">
                                        {{ $movieTime }}
                                    </span>
                                </div>
                            @else
                            @endif

                            @if (isset($NoOfSeasons))
                                <div>
                                    <p class="mb-0">{{ $NoOfSeasons }} {{__('streamEpisode.season')}}</p>
                                </div>
                            @else
                            @endif

                            <div class="font-size-16">
                                @if (isset($calenderIcon))
                                    <i class="ph ph-calendar-dots align-middle"></i>
                                @else
                                @endif
                                <span class="trending-year font-normal">
                                    {{ $movieYear }}
                                </span>
                            </div>
                        </div>

                        <p class="movie-banner-text line-count-3">
                            {{ $movieText }}
                        </p>
                    </div>
                    <a href="{{ $buttonUrl }}" class="btn btn-primary position-relative rounded-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="button-text">{{ __('streamButtons.play_now') }}</span>
                            <i class="ph-fill ph-play"></i>
                        </div>
                    </a>
                </div>
                <div class="col-lg-5 col-md-12 trailor-video iq-slider d-none d-lg-block position-relative">
                    <a data-fslightbox="html5-video" href="https://www.youtube.com/watch?v=spGSAeqxVUc"
                        class="video-open playbtn text-decoration-none" tabindex="0">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg"
                            xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="80px" height="80px"
                            viewBox="0 0 213.7 213.7" enable-background="new 0 0 213.7 213.7" xml:space="preserve">
                            <polygon class="triangle" fill="none" stroke-width="7" stroke-linecap="round"
                                stroke-linejoin="round" stroke-miterlimit="10"
                                points="73.5,62.5 148.5,105.8 73.5,149.1 "></polygon>
                            <circle class="circle" fill="none" stroke-width="7" stroke-linecap="round"
                                stroke-linejoin="round" stroke-miterlimit="10" cx="106.8" cy="106.8" r="103.3">
                            </circle>
                        </svg>
                        <span class="w-trailor text-uppercase"> {{ __('streamButtons.watch_trailer') }} </span>
                    </a>
                    <video id="my-video" class="my-video video-js vjs-big-play-centered w-100 d-none" loop autoplay
                        muted preload="auto"
                        data-setup='{
                            "techOrder": ["youtube"],
                            "sources": [{
                                "type": "video/youtube",
                                "src": "https://www.youtube.com/watch?v=spGSAeqxVUc"
                            }],
                            "youtube": {
                                "modestbranding": 1,
                                "rel": 0,
                                "showinfo": 0,
                                "autoplay": 1
                            },
                            "fullscreen": true
                            }'>
                    </video>
                </div>
            </div>
        </div>
    </div>
</div>
