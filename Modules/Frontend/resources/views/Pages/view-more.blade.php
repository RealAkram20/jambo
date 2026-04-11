@extends('frontend::layouts.master', ['isSwiperSlider' => true, 'isVideoJs' => true, 'bodyClass' => 'custom-header-relative', 'isSelect2' => true])

@section('content')
    <section class="section-padding">
        <div class="rate-review-details">
            <div class="container-fluid">
                <div class="row gy-4">
                    <div class="col-md-4 col-xl-3">
                        <div class="card mb-4 border-0">
                            <div class="card-body p-4">
                                <div class="css_prefix-card">
                                    <div class="block-images position-relative w-100">
                                        <div class="image-box w-100">
                                            <a href="javascript:void(0)">
                                                <img src="{{ asset('frontend/images/media/vikings.webp') }}"
                                                    alt="Vikings" class="img-fluid object-cover w-100 d-block border-0">
                                            </a>
                                        </div>
                                        <div class="card-description with-transition">
                                            <ul class="genres-list p-0 mb-2 d-flex align-items-center flex-wrap">
                                                <li class="trending-list"><a href="view-all"
                                                        class="font-size-14">{{ __('streamTag.action') }}</a>
                                                </li>
                                                <li class="trending-list"><a href="view-all"
                                                        class="font-size-14">{{ __('streamTag.animation') }}</a>
                                                </li>
                                                <li class="trending-list"><a href="view-all"
                                                        class="font-size-14">{{ __('streamTag.crime') }}</a>
                                                </li>
                                                <li class="trending-list"><a href="view-all"
                                                        class="font-size-14">{{ __('streamTag.horror') }}</a>
                                                </li>
                                            </ul>
                                            <h5 class="css_prefix-title text-capitalize line-count-1 mb-0">
                                                <a href="javascript:void(0)" class="color-inherit"
                                                    tabindex="0">{{ __('form.vikings') }} </a>
                                            </h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card border-0">
                            <div class="card-body">
                                <div class="ratting-card">

                                    <div class="d-flex flex-wrap align-items-center gap-4 mb-4">
                                        <h2 class="m-0">2.75</h2>
                                        <div class="data">
                                            <ul class="list-inline m-0 p-0 d-flex align-items-center gap-1">
                                                <li class="text-warning"><i class="icon-star-fill-icon"></i></li>
                                                <li class="text-warning"><i class="icon-star-fill-icon"></i></li>
                                                <li class="text-warning"><i class="icon-star-fill-icon"></i></li>
                                                <li class="text-muted"><i class="icon-star-fill-icon"></i></li>
                                                <li class="text-muted"><i class="icon-star-fill-icon"></i></li>
                                            </ul>
                                            <p class="m-0">{{ __('streamTag.based_on_rating') }}</p>
                                        </div>
                                    </div>

                                    <div class="row align-items-center g-3">
                                        <div class="col-xl-2 col-lg-2 col-md-3 col-sm-1 col-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="h6 mb-0">5</span>
                                                <i class="icon-star-fill-icon text-warning"></i>
                                            </div>
                                        </div>
                                        <div class="col-xl-7 col-lg-8 col-md-7 col-sm-9 col-7">
                                            <div class="progress w-100 progress-ratings" role="progressbar"
                                                aria-label="Rating 5" aria-valuenow="0" aria-valuemin="0"
                                                aria-valuemax="100">
                                                <div class="progress-bar bg-success" style="width: 0%"></div>
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-lg-2 col-md-2 col-sm-2 col-2 flex-shrink-0">
                                            <span class="text-body">0</span>
                                        </div>
                                    </div>


                                    <div class="row align-items-center g-3">
                                        <div class="col-xl-2 col-lg-2 col-md-3 col-sm-1 col-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="h6 mb-0">4</span>
                                                <i class="icon-star-fill-icon text-warning"></i>
                                            </div>
                                        </div>
                                        <div class="col-xl-7 col-lg-8 col-md-7 col-sm-9 col-7">
                                            <div class="progress w-100 progress-ratings" role="progressbar"
                                                aria-label="Rating 4" aria-valuenow="25" aria-valuemin="0"
                                                aria-valuemax="100">
                                                <div class="progress-bar bg-success" style="width: 25%"></div>
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-lg-2 col-md-2 col-sm-2 col-2 flex-shrink-0">
                                            <span class="text-body">1</span>
                                        </div>
                                    </div>


                                    <div class="row align-items-center g-3">
                                        <div class="col-xl-2 col-lg-2 col-md-3 col-sm-1 col-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="h6 mb-0">3</span>
                                                <i class="icon-star-fill-icon text-warning"></i>
                                            </div>
                                        </div>
                                        <div class="col-xl-7 col-lg-8 col-md-7 col-sm-9 col-7">
                                            <div class="progress w-100 progress-ratings" role="progressbar"
                                                aria-label="Rating 3" aria-valuenow="25" aria-valuemin="0"
                                                aria-valuemax="100">
                                                <div class="progress-bar bg-success" style="width: 25%"></div>
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-lg-2 col-md-2 col-sm-2 col-2 flex-shrink-0">
                                            <span class="text-body">1</span>
                                        </div>
                                    </div>


                                    <div class="row align-items-center g-3">
                                        <div class="col-xl-2 col-lg-2 col-md-3 col-sm-1 col-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="h6 mb-0">2</span>
                                                <i class="icon-star-fill-icon text-warning"></i>
                                            </div>
                                        </div>
                                        <div class="col-xl-7 col-lg-8 col-md-7 col-sm-9 col-7">
                                            <div class="progress w-100 progress-ratings" role="progressbar"
                                                aria-label="Rating 2" aria-valuenow="25" aria-valuemin="0"
                                                aria-valuemax="100">
                                                <div class="progress-bar bg-success" style="width: 25%"></div>
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-lg-2 col-md-2 col-sm-2 col-2 flex-shrink-0">
                                            <span class="text-body">1</span>
                                        </div>
                                    </div>


                                    <div class="row align-items-center g-3">
                                        <div class="col-xl-2 col-lg-2 col-md-3 col-sm-1 col-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="h6 mb-0">1</span>
                                                <i class="icon-star-fill-icon text-warning"></i>
                                            </div>
                                        </div>
                                        <div class="col-xl-7 col-lg-8 col-md-7 col-sm-9 col-7">
                                            <div class="progress w-100 progress-ratings" role="progressbar"
                                                aria-label="Rating 1" aria-valuenow="25" aria-valuemin="0"
                                                aria-valuemax="100">
                                                <div class="progress-bar bg-success" style="width: 25%"></div>
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-lg-2 col-md-2 col-sm-2 col-2 flex-shrink-0">
                                            <span class="text-body">1</span>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8 col-xl-9 ">
                        <div class="mb-2 d-flex align-items-center justify-content-between">
                            <h5>4 {{ __('streamTag.reviews_for_vikings') }}</h5>
                        </div>
                        <div class="review-list-inner comments-section data-listing">
                            <div class="review-card">
                                <div class="review-detail rounded-3">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                                        <div class="d-flex align-items-center justify-content-center gap-3">
                                            <!-- Display user avatar -->
                                            <img src="{{ asset('frontend/images/user/user3.jpg') }}"
                                                class="img-fluid user-img rounded-circle" alt="leslie">

                                            <div>
                                                <!-- Display comment author's name and date -->
                                                <h6 class="line-count-1 m-0">{{ __('streamAccount.leslie') }}</h6>
                                                <p class="mb-0 mt-1 small">2024-12-20 11:40:19</p>
                                            </div>
                                        </div>

                                        <!-- Display star rating -->
                                        <div class="star-rating d-flex gap-1 align-items-center">
                                            <i class="ph-fill ph-star text-warning"></i>
                                            <i class="ph-fill ph-star text-warning"></i>
                                            <i class="ph-fill ph-star text-warning"></i>
                                            <i class="ph-fill ph-star"></i>
                                            <i class="ph-fill ph-star"></i>
                                        </div>
                                    </div>

                                    <!-- Display comment content -->
                                    <p class="mb-0 mt-3 pt-3 border-top fw-medium">{{ __('streamTag.review8') }}</p>

                                    <!-- actions  -->
                                </div>
                            </div>
                            <div class="review-card">
                                <div class="review-detail rounded-3">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                                        <div class="d-flex align-items-center justify-content-center gap-3">
                                            <!-- Display user avatar -->
                                            <img src="{{ asset('frontend/images/user/user4.png') }}"
                                                class="img-fluid user-img rounded-circle" alt="jerry">

                                            <div>
                                                <!-- Display comment author's name and date -->
                                                <h6 class="line-count-1 m-0">{{ __('streamAccount.jerry') }}</h6>
                                                <p class="mb-0 mt-1 small">2024-12-20 11:36:22</p>
                                            </div>
                                        </div>

                                        <!-- Display star rating -->
                                        <div class="star-rating d-flex gap-1 align-items-center">
                                            <i class="ph-fill ph-star text-warning"></i>
                                            <i class="ph-fill ph-star "></i>
                                            <i class="ph-fill ph-star "></i>
                                            <i class="ph-fill ph-star"></i>
                                            <i class="ph-fill ph-star"></i>
                                        </div>
                                    </div>

                                    <!-- Display comment content -->
                                    <p class="mb-0 mt-3 pt-3 border-top fw-medium">
                                        {{ __('streamTag.review8') }}</p>

                                    <!-- actions  -->
                                </div>
                            </div>
                            <div class="review-card">
                                <div class="review-detail rounded-3">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                                        <div class="d-flex align-items-center justify-content-center gap-3">
                                            <!-- Display user avatar -->
                                            <img src="{{ asset('frontend/images/user/user5.jpg') }}"
                                                class="img-fluid user-img rounded-circle" alt="arlene">

                                            <div>
                                                <!-- Display comment author's name and date -->
                                                <h6 class="line-count-1 m-0">{{ __('streamAccount.arlene') }}</h6>
                                                <p class="mb-0 mt-1 small">2024-12-20 10:21:08</p>
                                            </div>
                                        </div>

                                        <!-- Display star rating -->
                                        <div class="star-rating d-flex gap-1 align-items-center">
                                            <i class="ph-fill ph-star text-warning"></i>
                                            <i class="ph-fill ph-star text-warning"></i>
                                            <i class="ph-fill ph-star text-warning"></i>
                                            <i class="ph-fill ph-star text-warning"></i>
                                            <i class="ph-fill ph-star"></i>
                                        </div>
                                    </div>

                                    <!-- Display comment content -->
                                    <p class="mb-0 mt-3 pt-3 border-top fw-medium">
                                        {{ __('streamTag.review6') }}</p>

                                    <!-- actions  -->
                                </div>
                            </div>
                            <div class="review-card">
                                <div class="review-detail rounded-3">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                                        <div class="d-flex align-items-center justify-content-center gap-3">
                                            <!-- Display user avatar -->
                                            <img src="{{ asset('frontend/images/user/user2.jpg') }}"
                                                class="img-fluid user-img rounded-circle" alt="freya">

                                            <div>
                                                <!-- Display comment author's name and date -->
                                                <h6 class="line-count-1 m-0">{{ __('streamAccount.freya') }}</h6>
                                                <p class="mb-0 mt-1 small">2024-12-18 10:57:21</p>
                                            </div>
                                        </div>

                                        <!-- Display star rating -->
                                        <div class="star-rating d-flex gap-1 align-items-center">
                                            <i class="ph-fill ph-star text-warning"></i>
                                            <i class="ph-fill ph-star text-warning"></i>
                                            <i class="ph-fill ph-star text-warning"></i>
                                            <i class="ph-fill ph-star "></i>
                                            <i class="ph-fill ph-star"></i>
                                        </div>
                                    </div>

                                    <!-- Display comment content -->
                                    <p class="mb-0 mt-3 pt-3 border-top fw-medium">
                                        {{ __('streamTag.review5') }}</p>

                                    <!-- actions  -->
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>
@endsection
