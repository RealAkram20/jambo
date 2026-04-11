@extends('frontend::layouts.master', ['isBreadCrumb' => true, 'title' => __('frontendheader.about_us')])

@section('content')
    <section class="custom-site-main">
        <div class="e-con-inner">
            <div class="container px-3 px-sm-0">
                <div class="row justify-content-center">
                    <!-- Text Content -->
                    <div class="col-md-12 text-center">
                        <h3 class="about-title">{{ __('pages_sections.about_streamit_ott_platform') }}</h3>
                        <p>{{ __('pages_sections.desc21') }}
                        </p>
                    </div>
                </div>

                <!-- Additional Text Section -->
                <div class="row justify-content-center">
                    <div class="col-md-12 text-center">
                        <p class="custom-about-decs">{{ __('pages_sections.desc22') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="about-section-padding-bottom">
        <div class="container-fluid">
            <div class="row align-items-center">
                <!-- Image Section -->
                <div class="col-xl-6">
                    <img class="img-fluid rounded mb-4 mb-xl-0 about-content-image"
                        src="{{ 'frontend/images/pages/content-Image-min.png' }}" alt="content-image">
                </div>

                <!-- Text and Features Section -->
                <div class="col-xl-6">
                    <h3 class="about-title">{{ __('pages_sections.why_choose_streamit') }}</h3>
                    <p class="text-light">{{ __('pages_sections.desc23') }}</p>

                    <div class="divider"></div>
                    <div class="d-flex flex-column flex-wrap w-100 gap-3 mt-3">
                        <div class="feature-box mb-3 d-flex align-items-center flex-column flex-md-row">
                            <img src="{{ 'frontend/images/pages/lab-test-icon.png' }}" alt="Movies">
                            <div class="feature-content">
                                <h5 class="feature-title"><span class="text-primary">10,000+</span>
                                    {{ __('pages_sections.movies_and_shows_across') }}
                                </h5>
                                <p class="mb-0 font-size-14">{{ __('pages_sections.feature1_description') }}</p>
                            </div>
                        </div>
                        <div class="feature-box mb-3 d-flex align-items-center flex-column flex-md-row">
                            <img src="{{ 'frontend/images/pages/ai-powered.png' }}" alt="AI Powered">
                            <div class="feature-content">
                                <h5 class="feature-title">
                                    {{ __('pages_sections.ai_powered_title') }}</h5>
                                <p class="mb-0 font-size-14">{{ __('pages_sections.ai_powered_description') }}
                                </p>
                            </div>
                        </div>

                        <div class="feature-box mb-3 d-flex align-items-center flex-column flex-md-row">
                            <img src="{{ 'frontend/images/pages/buffer-free.png' }}" alt="Buffer-Free">
                            <div class="feature-content">
                                <h5 class="feature-title">99.9% {{ __('pages_sections.buffer_free_title') }}</h5>
                                <p class="mb-0 font-size-14">{{ __('pages_sections.buffer_free_description') }}</p>
                            </div>
                        </div>

                        <div class="feature-box d-flex align-items-center flex-column flex-md-row">
                            <img src="{{ 'frontend/images/pages/secure-payment.png' }}" alt="Secure Payment">
                            <div class="feature-content">
                                <h5 class="feature-title">{{ __('pages_sections.secure_payment_title') }}</h5>
                                <p class="mb-0 font-size-14">{{ __('pages_sections.secure_payment_description') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="about-section-padding-bottom">
        <div class="streaming-section text-center"
            style="background-image: url('{{ asset('frontend/images/pages/bg-aboutus-min.png') }}'); background-position: center center; background-repeat: no-repeat; background-size: cover;">
            <div class="container">
                <div class="streaming-section-spacing">
                    <div class="row justify-content-center align-items-center">
                        <div class="col-xl-6 col-md-8">
                            <!-- Title -->
                            <div class="">
                                <h3 class="mt-0 streaming-title">
                                    {{ __('pages_sections.streaming_cta_title') }} </h3>
                            </div>

                            <!-- Subtitle -->
                            <div class="">
                                <p class="description-trail mb-0 fw">{{ __('pages_sections.streaming_cta_subtitle') }}
                                    <strong>$7.49/Month</strong>—{{ __('pages_sections.streaming_cta_note') }}
                                </p>
                            </div>
                            <!-- CTA Button -->
                            <div class="streaming-section-btn">
                                <div class="iq-button">
                                    <a href="{{ route('frontend.pricing-page') }}" class="btn btn-primary rounded-3">
                                        <span class="d-flex align-items-center justify-content-center gap-2 fw-semibold">
                                            {{ __('pages_sections.start_free_trial') }}
                                            <i class="ph-fill ph-play fs-6 ms-0"></i>
                                        </span>
                                    </a>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>



    <div class="section-padding-bottom">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-2"></div>
                <div class="col-lg-8 col-md-12">
                    <div class="title-box text-center">
                        <h3 class="">{{ __('pages_sections.mastermind_team') }}</h3>
                        <p class="mb-0">{{ __('frontendabout_us.streamit_build') }}</p>
                    </div>
                </div>
                <div class="col-lg-3"></div>
            </div>
            <div class="row about-us-detail gy-4">
                <div class="col-md-3">
                    <div class="">
                        <img src="{{ 'frontend/images/pages/team1.webp' }}" class="img-fluid w-100 mastermind-team-img"
                            alt="team">
                    </div>
                    <div class="box-content">
                        <h6 class="title-box">{{ __('pages_sections.ceo') }}</h6>
                        <h5 class="title-box-name">{{ __('pages_sections.tonny_smith') }}</h5>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="">
                        <img src="{{ 'frontend/images/pages/team2.webp' }}" class="img-fluid w-100 mastermind-team-img"
                            alt="team">
                    </div>
                    <div class="box-content">
                        <h6 class="title-box">{{ __('pages_sections.designer') }}</h6>
                        <h5 class="title-box-name">{{ __('pages_sections.barry_tech') }}</h5>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="">
                        <img src="{{ 'frontend/images/pages/team3.webp' }}" class="img-fluid w-100 mastermind-team-img"
                            alt="team">
                    </div>
                    <div class="box-content">
                        <h6 class="title-box">{{ __('pages_sections.developer') }}</h6>
                        <h5 class="title-box-name">{{ __('pages_sections.kep_john') }}</h5>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="">
                        <img src="{{ 'frontend/images/pages/team4.webp' }}" class="img-fluid w-100 mastermind-team-img"
                            alt="team">
                    </div>
                    <div class="box-content">
                        <h6 class="title-box">{{ __('pages_sections.designer') }}</h6>
                        <h5 class="title-box-name">{{ __('pages_sections.monty_rock') }}</h5>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="section-padding" style="background-color: var(--bs-gray-900);">
        <div class="container">
            <div class="row about-us-row align-items-center">
                <div class="col-lg-6 col-md-5">
                    <div class="text-center">
                        <img width="497" height="477" src="{{ 'frontend/images/pages/map.webp' }}"
                            class=" img-fluid attachment-large size-large" alt="img" loading="lazy">
                    </div>
                </div>
                <div class="col-lg-6 col-md-7">
                    <h3 class="text-capitalize text-start mb-4">{{ __('pages_sections.connect_with_us') }}</h3>
                    <p class="mb-0 text-start">{{ __('pages_sections.streamit_located') }} <a
                            href="mailto:hello@jambo.co">hello@jambo.co</a> {{ __('pages_sections.tech_related') }}
                    </p>
                    <div class="mt-4">
                        <div class="text-capitalize mb-1 d-flex gap-2">
                            <h6>{{ __('streamAccount.company') }}:</h6>
                            <h6 class="text-decoration-none ms-1">Jambo</h6>
                        </div>
                        <div class="text-capitalize mb-1 d-flex gap-2">
                            <h6>{{ __('pages_sections.product') }}:</h6>
                            <h6 class="text-decoration-none ms-1">Jambo </h6>
                        </div>
                        <div>{{ __('frontendheader.contact_us') }}:
                            <a href="javascript:void(0)" class="text-decoration-none ms-1">hello@jambo.co</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="section-padding">
        <div class="container">
            <h3 class="text-capitalize text-center">{{ __('pages_sections.work_best') }}</h3>
            <div class="image-box-container">
                <div class="image-box-1">
                    <img class="img-fluid" src="{{ asset('frontend/images/pages/about-us1.svg') }}" alt="content-image">
                </div>

                <div class="image-box-1">
                    <img class="img-fluid" src="{{ asset('frontend/images/pages/about-us2.svg') }}" alt="content-image">
                </div>

                <div class="image-box-1">
                    <img class="img-fluid" src="{{ asset('frontend/images/pages/about-us3.svg') }}" alt="content-image">
                </div>

                <div class="image-box-1">
                    <img class="img-fluid" src="{{ asset('frontend/images/pages/about-us4.svg') }}" alt="content-image">
                </div>

                <div class="image-box-1">
                    <img class="img-fluid" src="{{ asset('frontend/images/pages/about-us5.svg') }}" alt="content-image">
                </div>
            </div>
        </div>
    </div>

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}
@endsection
