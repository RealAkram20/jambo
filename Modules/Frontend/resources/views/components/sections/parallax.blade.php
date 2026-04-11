<section id="parallex" class="parallax-window bg-attachment-fixed"
    style="background: url('{{ asset('frontend/images/parallax/parallex.webp') }}') fixed;">
    <div class="container-fluid h-100">
        <div
            class="row align-items-center justify-content-center h-100 parallaxt-details flex-column-reverse flex-lg-row gap-4 gap-lg-0">
            <div class="col-xl-6 col-lg-6 col-md-12 r-mb-23">
                <div class="parallax-window-detail">
                    <h2 class="mb-0 parallaxt-details-heading">{{ __('streamMovies.godzilla') }}</h2>
                    <div
                        class="d-flex flex-column flex-md-row gap-2 flex-wrap align-items-center r-mb-23 mt-2 mb-3 gap-md-3 justify-content-center justify-content-lg-start">
                        <div class="slider-ratting d-flex align-items-center">
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
                                    <i class="ph-fill ph-star" aria-hidden="true"></i>
                                </li>
                                <li>
                                    <i class="ph ph-star" aria-hidden="true"></i>
                                </li>
                            </ul>
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            <span>5
                            </span>
                            <img src="{{ asset('frontend/images/pages/imdb-logo.svg') }}" alt="imdb logo"
                                class="img-fluid">
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <i class="ph ph-clock"></i>
                            <span class="">2h : 30m</span>
                        </div>
                    </div>
                    <h4 class="iq-title mb-2 pb-1 pb-lg-0 mb-lg-3 fw-medium">
                        {{ __('streamMovies.movie_year') }}
                    </h4>
                    <p class="mb-4 pb-2 parallaxt-details-descripttion">{{ __('streamMovies.godzilla_desc') }}
                    </p>
                    <div class="parallax-buttons">
                        @include('frontend::components.widgets.custom-button', [
                            'buttonUrl' => route('frontend.movie_detail'),
                            'buttonTitle' => __('streamButtons.play_now'),
                        ])

                    </div>
                </div>
            </div>
            <div class="col-xl-6 col-lg-6 col-md-12 mt-0 mt-lg-5 mt-xl-0">
                <div class="parallax-img">
                    <a href="{{ route('frontend.movie_detail') }}">
                        <img src="{{ asset('frontend/images/pages/Movieof-the-year.webp') }}" class="img-fluid w-100" loading="lazy" alt="bailey">
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
