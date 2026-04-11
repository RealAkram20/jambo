@extends('layouts.guest')

@section('content')
    <section class="sign-in-page" style="background-image: url('{{ asset('dashboard/images/login/login.webp') }}')">
        <div class="container">
            <div class="justify-content-center align-items-center height-self-center row">
                <div class="align-self-center col-lg-5 col-md-12">
                    <div class="sign-user_card">
                        <a href="{{ route('dashboard') }}">
                            <img class="img-fluid logo" src="{{ asset('dashboard/images/logo-full.png') }}" alt="#">
                        </a>
                        <div class="sign-in-page-data">
                            <div class="sign-in-from w-100 m-auto">
                                <h3 class="mb-3 text-center">{{ __('authentication.reset_password') }}</h3>
                                <p class="text-body">{{ __('authentication.email_address') }}</p>
                                <form action="/" class="mt-4">
                                    <div class="input-group custom-input-group mb-3">
                                        <input placeholder="{{__('authentication.enter_password')}}" autocomplete="off"
                                            required="" type="email" id="exampleInputEmail2" class="mb-0 form-control">
                                        <span class="input-group-text"><i class="ph ph-eye-slash"
                                                id="togglePassword"></i></span>
                                    </div>
                                </form>
                                <div class="sign-info">
                                    <button type="button"
                                        class="btn btn-btn btn-primary w-100 custom-sign-btn mt-2">{{ __('authentication.reset') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
