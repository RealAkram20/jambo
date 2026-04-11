@extends('frontend::layouts.blank')

@section('content')
    <div class="section-padding  error-404-section">
        <div class="container">
            <div class="error-404-position">
                <div class="row  align-items-center">
                    <div class="col-lg-2"></div>
                    <div class="col-lg-8">
                        <div class="text-center">
                            <img src="{{ asset('frontend/images/pages/new-404.png') }}" alt="404" loading="lazy"
                                class="error-404-img mb-4 pb-2">
                        </div>
                        <h4 class="fw-semibold mb-0 text-center">{{ __('frontenderror.something_wrong') }}</h4>
                        <p class="text-center mb-3">{{ __('authentication.requested_page') }}</p>
                        <div class="text-center mt-4 pt-3 ">
                            <a href="{{ route('frontend.ott') }}"
                                class="btn btn-link error-404-btn">{{ __('frontenderror.back_home') }}</a>
                        </div>
                    </div>
                    <div class="col-lg-2"></div>
                </div>
            </div>
        </div>
    </div>
@endsection
