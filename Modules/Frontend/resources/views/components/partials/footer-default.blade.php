<footer class="footer footer-default">
    <div class="footer-top">
        <div class="container-fluid">
            <div class="row gy-4">
                <div class="col-lg-3 col-sm-6">
                    <div class="footer-logo">
                        @include('frontend::components.brand.logo')
                    </div>
                    <div>{{ __('frontendfooter.email_us') }} <span
                            class="text-white">{{ __('frontendfooter.customer@stremit') }}</span></div>
                    <p class="mt-0 mb-2">
                        {{ __('streamMovies.helpline_number') }}</p>
                    <a href="tel:+4805550103" class="helpline">+(480) 555-0103</a>
                </div>
                <div class="col-lg-2 col-sm-6">
                    <h4 class="footer-link-title text-capitalize">{{ __('frontendfooter.movies_to_watch') }} </h4>
                    <ul class="list-unstyled footer-menu mb-0">
                        <li>
                            <a href="{{ route('frontend.view_all') }}"
                                class="text-capitalize">{{ __('frontendfooter.top_trending') }}</a>
                        </li>
                        <li>
                            <a href="{{ route('frontend.view_all') }}"
                                class="text-capitalize">{{ __('frontendfooter.recommended') }}</a>
                        </li>
                        <li>
                            <a href="{{ route('frontend.view_all') }}"
                                class="text-capitalize">{{ __('frontendfooter.popular') }}</a>
                        </li>
                    </ul>
                </div>
                <div class="col-lg-2 col-sm-6">
                    <h4 class="footer-link-title text-capitalize">{{ __('frontendfooter.quick_links') }}</h4>
                    <ul class="list-unstyled footer-menu mb-0">
                        <li>
                            <a href="{{ route('frontend.contact_us') }}"
                                class="text-capitalize">{{ __('frontendheader.contact_us') }}</a>
                        </li>
                        <li>
                            <a href="{{ route('frontend.pricing-page') }}"
                                class="text-capitalize">{{ __('frontendheader.pricing_plan') }}</a>
                        </li>
                        <li>
                            <a href="{{ route('frontend.list_page') }}"
                                class="text-capitalize">{{ __('frontendheader.blog') }}</a>
                        </li>
                        <li>
                            <a href="{{ route('frontend.faq_page') }}"
                                class="text-capitalize">{{ __('frontendheader.faq') }}</a>
                        </li>
                    </ul>
                </div>
                <div class="col-lg-2 col-sm-6">
                    <h4 class="footer-link-title text-capitalize">{{ __('frontendfooter.about_company') }}</h4>
                    <ul class="list-unstyled footer-menu mb-0">
                        <li>
                            <a href="{{ route('frontend.about_us') }}"
                                class="text-capitalize">{{ __('frontendheader.about_us') }}</a>
                        </li>
                        <li>
                            <a href="{{ route('frontend.terms-and-policy') }}"
                                class="">{{ __('frontendfooter.terms_and_use') }}</a>
                        </li>
                        <li>
                            <a href="{{ route('frontend.privacy-policy') }}"
                                class="text-capitalize">{{ __('frontendheader.privacy_policy') }}</a>
                        </li>
                    </ul>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <h4 class="footer-link-title text-capitalize">{{ __('frontendfooter.newsletter') }}</h4>
                    <div class="mailchimp mailchimp-dark">
                        <div class="input-group">
                            <input type="text" class="form-control mb-0"
                                placeholder="{{ __('frontenddetail_page.email') }}" aria-describedby="button-addon2" />
                            <div class="iq-button">
                                <button type="submit" class="btn btn-primary st-subscribe-btn"
                                    id="button-addon2">{{ __('frontendfooter.subscribe') }}</button>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3 widget-streamit-social-media mt-4">
                        <h3 class="font-size-14 widget-streamit-social-media-title">
                            {{ __('frontendfooter.follow_us') }}</h3>
                        <div class="social-footer">
                            <ul class="m-0 d-inline list-unstyled widget_social_media d-flex gap-2 flex-wrap">
                                <li>
                                    <a href="https://www.facebook.com/" class="position-relative">
                                        <i class="icon icon-facebook-share"></i>
                                    </a>
                                </li>
                                <li>
                                    <a href="https://twitter.com/" class="position-relative">
                                        <i class="ph ph-x-logo"></i>
                                    </a>
                                </li>
                                <li>
                                    <a href="https://www.instagram.com/" class="position-relative">
                                        <i class="ph ph-instagram-logo"></i>
                                    </a>
                                </li>
                                <li>
                                    <a href="https://github.com/" class="position-relative">
                                        <i class="ph-fill ph-linkedin-logo"></i>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
        <div class="footer-bottom">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <p class="font-size-14 mb-0">&copy; <span class="currentYear">{{ date('Y') }}</span> <span
                                class="text-primary">JAMBO.</span>
                            {{__('frontendfooter.desc')}}</p>
                    </div>
                    <div class="col-md-6 mt-md-0 mt-5">
                        <div class="d-flex flex-column align-items-start align-items-md-end widget-iq-download-app">
                            <h6 class="mb-3 fw-bold">{{ __('frontendfooter.download_app') }}</h6>
                            <div>
                                <ul class="d-inline-flex flex-wrap align-items-center list-inline m-0 p-0 gap-3">
                                    <li class="m-0 p-0">
                                         <a class="app-image"href="javascript:void(0)">
                                            <img src="{{ asset('frontend/images/footer/play-store.webp') }}" loading="lazy"
                                                alt="play-store" />
                                        </a>
                                    </li>
                                    <li class="m-0 p-0">
                                        <a class="app-image"href="javascript:void(0)">
                                            <img src="{{ asset('frontend/images/footer/app-store.webp') }}" loading="lazy"
                                                alt="app-store" />
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</footer>
