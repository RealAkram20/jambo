@extends('layouts.auth')

@section('content')
    <section class="sign-in-page" style="background-image: url('{{ asset('dashboard/images/login/login.webp') }}')">
        <x-auth-card customClassCol="col-lg-5">
            <!-- style="background-image: url('{{ asset('dashboard/images/login/login.webp') }}')" -->

            <!-- Session Status -->


            <!-- Social Login -->


            <!-- Validation Errors -->

            <x-slot name="logo">
                <a href="/">
                    <x-application-logo />
                </a>
            </x-slot>

            <div class="sign-in-page-data">
                <div class="sign-in-from w-100 m-auto">
                    <h3 class="mb-3 text-center">{{ __('auth.sign_in') }}</h3>
                    <x-auth-session-status class="mb-4" :status="session('status')" />
                    <x-auth-social-login />
                    <x-auth-validation-errors class="mb-4" :errors="$errors" />
                    <form method="POST" action="{{ $url ?? route('login') }}">
                        @csrf

                        <!-- Email Address -->
                        <div class="mb-3">
                            <label for="exampleInputEmail1" class="mb-2">
                                {{ __('pages_sections.username_or_email') }} </label>
                            <x-input id="email" type="email" class="mb-0" name="email" :value="old('email', 'admin@demo.com')" required
                                autofocus placeholder="{{ __('Enter email') }}" />
                        </div>

                        <!-- Password -->
                        <div class="mb-3">
                            <label for="exampleInputPassword2" class="mb-2">
                                {{ __('form.Password') }} </label>
                            <div class="input-group custom-input-group mb-3">
                                <x-input id="password" type="password" class="mb-0" name="password" required
                                    autocomplete="current-password" for="password" value="12345678"
                                    placeholder="password" />
                                <span class="input-group-text"><i class="ph ph-eye-slash" id="togglePassword"></i></span>
                            </div>

                        </div>
                        <div class="forgot-password">
                            <a href="reset-password">
                                Forgot your password? </a>
                        </div>

                        <x-button tag="inputType">

                        </x-button>
                        <div class="login-form-bottom">
                            <div class="d-flex justify-content-center align-items-center gap-2 links my-3">
                                {{ __('authentication.if_you_are_new') }}
                                <a href="{{ route('register') }}" class="st-sub-card setting-dropdown">
                                    <h6 class="text-primary m-0">
                                        {{ __('authentication.sign_up') }} </h6>
                                </a>

                            </div>
                        </div>
                        <div class="css_prefix-separator">
                            <span class="or-section"> {{ __('authentication.or') }} </span>
                        </div>
                        <div class="css_prefix-social-login-section">
                            <div>{{ __('authentication.No_apps_configured') }}</div>
                        </div>
                    </form>
                </div>
            </div>
            <x-slot name="extra">
                @if (Route::has('register'))
                    <p class="text-center text-gray-600 mt-4">
                        Do not have an account? <a href="{{ route('register') }}"
                            class="underline hover:text-gray-900">Register</a>.
                    </p>
                @endif
            </x-slot>

        </x-auth-card>
    </section>
    <script type="text/javascript">
        function domId(name) {
            return document.getElementById(name)
        }

        function setLoginCredentials(type) {
            domId('email').value = domId(type + '_email').textContent
            domId('password').value = domId(type + '_password').textContent
        }
    </script>
@endsection
