@extends('layouts.guest')

@section('content')
    <div class="wrapper">
        <section class="sign-in-page" style="background-image: url('{{ asset('dashboard/images/login/login.webp') }}')">
            <div class="container">
                <div class="justify-content-center align-items-center height-self-center row">
                    <div class="align-self-center col-lg-5 col-md-12">
                        <div class="sign-user_card">
                            <a href="{{ route('dashboard') }}">
                                <img class="img-fluid logo" src="{{ asset('dashboard/images/logo-full.png') }}"
                                    alt="#">
                            </a>
                            <div class="sign-in-page-data">
                                <div class="sign-in-from w-100 m-auto">
                                    <h3 class="mb-3 text-center">{{ __('authentication.sign_in') }}</h3>
                                    <form action="/">
                                        <div class="mb-3">
                                            <label for="exampleInputEmail1" class="mb-2">
                                                {{__('frontendform.username_or_email')}} </label>
                                            <input placeholder="{{ __('authentication.enter_email') }}" autocomplete="off"
                                                required type="email" id="exampleInputEmail1" class="mb-0 form-control" />
                                        </div>
                                        <div class="mb-3">
                                            <label for="exampleInputPassword2" class="mb-2">
                                                {{__('form.Password')}} </label>
                                            <div class="input-group custom-input-group mb-3">
                                                <input placeholder="{{ __('authentication.password') }}" required
                                                    type="password" id="exampleInputPassword2" class="mb-0 form-control" />
                                                <span class="input-group-text"><i class="ph ph-eye-slash"
                                                        id="togglePassword"></i></span>
                                            </div>
                                        </div>
                                        <div class="forgot-password">
                                            <a href="">
                                                {{__('authentication.forgot_your_password')}} </a>
                                        </div>
                                        <div class="submit">
                                            <input type="hidden" name="action" value="streamit_login">
                                            <input type="submit" value="{{__('frontendheader.login')}}"
                                                class="btn btn-primary w-100 custom-sign-btn">
                                        </div>
                                        <div class="login-form-bottom">
                                            <div class="d-flex justify-content-center align-items-center gap-2 links my-3">
                                                {{__('authentication.if_you_are_new')}}
                                                <a href="{{ route('dashboard.register') }}"
                                                    class="st-sub-card setting-dropdown">
                                                    <h6 class="text-primary m-0">
                                                        {{__('authentication.sign_up')}} </h6>
                                                </a>

                                            </div>
                                        </div>
                                        <div class="css_prefix-separator">
                                            <span class="or-section"> {{__('authentication.or')}} </span>
                                        </div>
                                        <div class="css_prefix-social-login-section">
                                            <div>{{__('authentication.No_apps_configured')}}</div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
