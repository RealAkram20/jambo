@extends('frontend::layouts.master', ['isBreadCrumb' => true, 'title' => __('frontendform.terms_conditions')])

@section('content')
    <div class="terms-of-use section-padding">
        <div class="container">
            <div class="title-box privacy-title-gap">
                <h4 class="head-title fw-500">1. {{ __('streamPrivacypolicy.description_service') }}</h4>
                <p class="terms-desc">
                    {{ __('streamPrivacypolicy.desc1') }}
                </p>
                <p>
                    {{ __('streamPrivacypolicy.desc2') }}
                </p>
                <p>
                    {{ __('streamPrivacypolicy.desc3') }}
                </p>
            </div>
            <div class="title-box privacy-title-gap">
                <h4 class="head-title fw-500 mb-3">{{ __('streamPrivacypolicy.works_best_with') }}</h4>
                <ul class="ps-3">
                    <li class="d-flex">{{ __('streamPrivacypolicy.chrome') }}</li>
                    <li class="d-flex">{{ __('streamPrivacypolicy.firefox') }}</li>
                    <li class="d-flex">{{ __('streamPrivacypolicy.safari') }}</li>
                    <li class="d-flex">{{ __('streamPrivacypolicy.js_cookies') }}</li>
                </ul>
            </div>
            <div class="title-box privacy-title-gap">
                <h4 class="head-title fw-500">2. {{ __('streamPrivacypolicy.subscription_services') }}</h4>
                <p class="terms-desc">
                    {{ __('streamPrivacypolicy.subscription_desc1') }}
                </p>
                <p>
                    {{ __('streamPrivacypolicy.subscription_desc2') }}
                </p>
                <p>
                    {{ __('streamPrivacypolicy.subscription_desc3') }}
                </p>
            </div>
            <div class="title-box privacy-title-gap">
                <h4 class="mb-4 fw-500">3. {{ __('streamPrivacypolicy.payment_gateway') }}</h4>
                <p>{{ __('streamPrivacypolicy.payment_desc1') }}</p>
                <p>{{ __('streamPrivacypolicy.payment_desc2') }}</p>
                <p>{{ __('streamPrivacypolicy.payment_desc3') }}</p>
            </div>
            <div class="title-box privacy-title-gap">
                <h4 class="mb-4 fw-500">4. {{ __('streamPrivacypolicy.children_privacy') }}</h4>
                <p>{{ __('streamPrivacypolicy.children_desc1') }}</p>
                <p>{{ __('streamPrivacypolicy.children_desc2') }}</p>
                <p>{{ __('streamPrivacypolicy.children_desc3') }}</p>
            </div>
            <div class="title-box privacy-title-gap">
                <h4 class="mb-4 fw-500">5. {{ __('streamPrivacypolicy.data_transfer') }}</h4>
                <p>{{ __('streamPrivacypolicy.data_desc1') }}</p>
                <p>{{ __('streamPrivacypolicy.data_desc2') }}</p>
                <p class="mb-0">{{ __('streamPrivacypolicy.data_desc3') }}</p>
            </div>
        </div>
    </div>

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}
@endsection
