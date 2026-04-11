@extends('layouts.guest')
@section('content')
    <section class="sign-in-page" style="background-image: url('{{ asset('dashboard/images/login/login.webp') }}')">
        <div class="h-100 container">
            <div class="justify-content-center align-items-center h-100 row">
                <div class="col-12 col-md-6 col-sm-12">
                    <div class="sign-user_card">
                        <a href="{{ route('dashboard') }}">
                            <img class="img-fluid logo" src="{{ asset('dashboard/images/logo-full.png') }}" alt="#">
                        </a>
                        <div class="sign-in-page-data">
                            <div class="sign-in-from w-100 m-auto">
                                <h3 class="mb-3 text-center">{{ __('authentication.michael_smith') }}</h3>
                                <p class="text-body text-center">{{ __('authentication.admin_password') }}</p>
                                <form class="mt-4">
                                    <div class="mb-3"><input placeholder="{{ __('authentication.password') }}"
                                            autocomplete="off" required="" type="email" id="exampleInputEmail2"
                                            class="mb-0 form-control"></div>
                                </form>
                                <a class="btn btn-primary w-100 custom-sign-btn mt-2" href="{{ route('login') }}">
                                    {{ __('authentication.log_in') }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
