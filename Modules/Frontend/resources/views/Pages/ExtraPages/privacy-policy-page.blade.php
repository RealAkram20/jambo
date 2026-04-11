@extends('frontend::layouts.master', ['isBreadCrumb' => true, 'title' => __('frontendheader.privacy_policy')])

@section('content')
    <div class="privacy-policy section-padding">
        <div class="container">
            <div class="title-box privacy-title-gap">
                <h4 class="mb-3 fw-500">1. {{ __('streamPrivacypolicy.waht_personal') }}</h4>
                <p class="mb-0">{{ __('streamPrivacypolicy.desc4') }}</p>
            </div>
            <div class="title-box privacy-title-gap">
                <h4 class="mb-3 fw-500">2. {{ __('streamPrivacypolicy.cookies_web') }}</h4>
                <p class="mb-0">{{ __('streamPrivacypolicy.desc5') }}<br><br>{{ __('streamPrivacypolicy.desc6') }}
                </p>
            </div>
            <div class="title-box privacy-title-gap">
                <h4 class="mb-3 fw-500">3. {{ __('streamPrivacypolicy.third_party') }}</h4>
                <p class="mb-0">{{ __('streamPrivacypolicy.desc4') }}</p>
            </div>
            <div class="title-box privacy-title-gap">
                <h4 class="mb-3 fw-500">4. {{ __('streamPrivacypolicy.children_privacy') }}</h4>
                <p class="mb-0">{{ __('streamPrivacypolicy.desc4') }}</p>
            </div>
            <div class="title-box privacy-title-gap">
                <h4 class="mb-3 fw-500">5. {{ __('streamPrivacypolicy.data_transfer') }}</h4>
                <p class="mb-0">{{ __('streamPrivacypolicy.desc4') }}</p>
            </div>
        </div>
    </div>

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}
@endsection
