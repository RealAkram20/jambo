@extends('layouts.guest')
@section('content')
    <section class="sign-in-page" style="background-image: url('{{ asset('dashboard/images/login/login.webp') }}')">
        <div class="container">
            <div class="row justify-content-center align-items-center height-self-center">
                <div lg="5" md="12" class="col col-lg-5 col-md-12 align-self-center form-padding">
                    <div class="sign-user_card">
                        <a href="{{ route('dashboard') }}">
                            <img class="img-fluid logo" src="{{ asset('dashboard/images/logo-full.png') }}" alt="#">
                        </a>
                        <div class="sign-in-page-data">
                            <div class="sign-in-from w-100 m-auto">
                                <h3 class="mb-3 text-center">{{ __('authentication.verification') }}</h3>
                                <p class="text-body">{{ __('authentication.instructions_to_reset') }}
                                </p>

                                <form class="mt-4" action="">
                                    <div class="form-group floating-label">
                                        <label for="email"
                                            class="form-label">{{ __('authentication.phone_number') }}</label>
                                        <input id="email" type="email" class="form-control" aria-describedby="email"
                                            placeholder="+1 123456789"></input>
                                    </div>
                                    <button
                                        class="btn btn-primary w-100 custom-sign-btn mt-2">{{ __('authentication.reset') }}</button>

                                    <div class="form-group mt-3">
                                        <label for="email" class="form-label">{{ __('authentication.OTP') }}</label>
                                        <input type="number" class="form-control" aria-describedby="email"
                                            placeholder="0000"></input>
                                    </div>
                                        <button
                                            class="btn btn-primary w-100 custom-sign-btn mt-2">{{ __('authentication.verify') }}</button>
                                </form>
                            </div>
                        </div>
                        <div class="mt-2">
                            <div class="d-flex justify-content-center links">
                                {{ __('authentication.dont_have_an_account') }}<a href="{{ route('dashboard.register') }}"
                                    class="text-primary ms-2">{{ __('authentication.sign_up') }}</a></div>
                            <div class="d-flex justify-content-center links">
                                <a href="{{ route('dashboard.reset-password') }}"
                                    class="f-link">{{ __('authentication.forgot_your_password') }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
