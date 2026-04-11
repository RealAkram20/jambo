@extends('layouts.guest')

@section('content')
    <section class="sign-in-page" style="background-image: url('{{ asset('dashboard/images/login/login.webp') }}')">
        <div class="h-100 container">
            <div class="justify-content-center align-items-center h-100 row">
                <div class="col-12  col-md-6 col-sm-12">
                    <div class="sign-user_card">
                        <a href="{{ route('dashboard') }}">
                            <img class="img-fluid logo" src="{{ asset('dashboard/images/logo-full.png') }}" alt="#">
                        </a>
                        <div class="sign-in-page-data">
                            <h3 class="mb-3 text-center">{{ __('authentication.success') }}</h3>
                            <p class="text-body">{{__('authentication.email_send')}} <a role="button" tabindex="0"
                                    href="javascript:void(0);"
                                    class="__cf_email__ bg-dark border-0 p-0 btn btn-primary">[email&nbsp;protected]</a>
                                {{__('authentication.email_check') }}
                            </p>
                            <a class="btn btn-primary w-100 custom-sign-btn mt-2"
                                href="{{ route('dashboard') }}">{{ __('authentication.back_to_home') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </section>
@endsection
