@extends('frontend::layouts.master', ['isBreadCrumb' => true, 'title' => __('frontendheader.about_us')])

@section('content')
    <section class="custom-site-main">
        <div class="e-con-inner">
            <div class="container px-3 px-sm-0">
                <div class="row justify-content-center">
                    <div class="col-md-12 text-center">
                        <h3 class="about-title">{{ __('pages_sections.about_streamit_ott_platform') }}</h3>
                        <p>{{ __('pages_sections.desc21') }}</p>
                    </div>
                </div>

                <div class="row justify-content-center">
                    <div class="col-md-12 text-center">
                        <p class="custom-about-decs">{{ __('pages_sections.desc22') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}
@endsection
