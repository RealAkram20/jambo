@extends('frontend::layouts.master', ['isBreadCrumb' => true, 'title' => __('frontendheader.pricing_plan')])

@section('content')
    <div class="section-padding">
        <div class="container">
            <div class="row">
                <div class="col-xl-4 col-md-6 mb-3 mb-lg-0">
                    <div class="pricing-plan-wrapper">
                        <div class="pricing-plan-header">
                            <div class="plan-wrapper">
                                <h4 class="plan-name">{{ __('streamTag.basic_plan') }}</h4>
                            </div>
                            <div class="pricing-plan-details">

                                <span class="plan-main-price">$10</span>

                                <span class="plan-period-time">/ {{ __('streamTag.lifetime') }}</span>
                            </div>
                        </div>
                        <div class="pricing-details">
                            <div class="pricing-plan-description">
                                <ul class="list-inline p-0">
                                    <li>
                                        <i class="ph ph-check fw-bold"></i>
                                        <span class="font-size-18 fw-500">{{ __('streamTag.free_movies') }}</span>
                                    </li>
                                    <li>
                                        <i class="ph ph-x fw-bold text-primary"></i>
                                        <span class="font-size-18 fw-500">{{ __('streamTag.watch_on_tv') }}</span>
                                    </li>
                                    <li>
                                        <i class="ph ph-x fw-bold text-primary"></i>
                                        <span class="font-size-18 fw-500">{{ __('streamTag.streamit_special') }}</span>
                                    </li>
                                    <li>
                                        <i class="ph ph-x fw-bold text-primary"></i>
                                        <span class="font-size-18 fw-500">{{ __('streamTag.video_quality') }}</span>
                                    </li>
                                </ul>
                            </div>
                            <div class="pricing-plan-footer">
                                <div class="iq-button">
                                    <a href="javascript:void(0)" class="btn btn-primary fw-semibold rounded-3">
                                        {{ __('streamShop.checkout') }} </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6 mb-3 mb-lg-0">
                    <div class="pricing-plan-wrapper">
                        <div class="pricing-plan-discount bg-primary p-2 text-center">
                            <span class="text-white">{{ __('streamTag.save') }} 20%</span>
                        </div>
                        <div class="pricing-plan-header">
                            <div class="plan-wrapper d-flex align-items-center justify-content-between">
                                <h4 class="plan-name">{{ __('streamTag.premium_plan') }}</h4>
                                <span class="badge bg-primary rounded-2">
                                    <small>{{ __('streamTag.active') }}</small>
                                </span>
                            </div>
                            <div class="pricing-plan-details">
                                <span class="sale-price text-decoration-line-through text-primary">
                                    <span>$267</span>
                                </span>

                                <span class="plan-main-price">$180</span>

                                <span class="plan-period-time">/ 3 {{ __('streamTag.month') }}</span>
                            </div>
                        </div>
                        <div class="pricing-details">
                            <div class="description">
                                <p class="m-0">{{ __('streamTag.benifits_of_streamit') }}</p>
                            </div>
                            <div class="pricing-plan-description">
                                <ul class="list-inline p-0">
                                    <li>
                                        <i class="ph ph-check fw-bold"></i>
                                        <span class="font-size-18 fw-500">{{ __('streamTag.free_movies') }}</span>
                                    </li>
                                    <li>
                                        <i class="ph ph-check fw-bold"></i>
                                        <span class="font-size-18 fw-500">{{ __('streamTag.watch_on_tv') }}</span>
                                    </li>
                                    <li>
                                        <i class="ph ph-check fw-bold"></i>
                                        <span class="font-size-18 fw-500">{{ __('streamTag.streamit_special') }}</span>
                                    </li>
                                    <li>
                                        <i class="ph ph-check fw-bold"></i>
                                        <span class="font-size-18 fw-500">{{ __('streamTag.video_quality') }}</span>
                                    </li>
                                </ul>
                            </div>
                            <div class="pricing-plan-footer">
                                <div class="iq-button">
                                    <a href="javascript:void(0)" class="btn btn-primary fw-semibold rounded-3">
                                        {{ __('streamShop.checkout') }} </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6">
                    <div class="pricing-plan-wrapper">
                        <div class="pricing-plan-header">
                            <div class="plan-wrapper">
                                <h4 class="plan-name">{{ __('streamTag.standard_plan') }}</h4>
                            </div>

                            <div class="pricing-plan-details">

                                <span class="plan-main-price">$79</span>

                                <span class="plan-period-time">/ 1 {{ __('streamTag.month') }}</span>
                            </div>
                        </div>
                        <div class="pricing-details">
                            <div class="pricing-plan-description">
                                <ul class="list-inline p-0">
                                    <li>
                                        <i class="ph ph-check fw-bold"></i>
                                        <span class="font-size-18 fw-500">{{ __('streamTag.free_movies') }}</span>
                                    </li>
                                    <li>
                                        <i class="ph ph-check fw-bold"></i>
                                        <span class="font-size-18 fw-500">{{ __('streamTag.watch_on_tv') }}</span>
                                    </li>
                                    <li>
                                        <i class="ph ph-check fw-bold"></i>
                                        <span class="font-size-18 fw-500">{{ __('streamTag.streamit_special') }}</span>
                                    </li>
                                    <li>
                                        <i class="ph ph-x fw-bold text-primary"></i>
                                        <span class="font-size-18 fw-500">{{ __('streamTag.video_quality') }}</span>
                                    </li>
                                </ul>
                            </div>
                            <div class="pricing-plan-footer">
                                <div class="iq-button">
                                    <a href="javascript:void(0)" class="btn btn-primary fw-semibold rounded-3">
                                        {{ __('streamShop.checkout') }} </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}
@endsection
