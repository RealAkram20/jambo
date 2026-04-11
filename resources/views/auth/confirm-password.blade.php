@extends('layouts.auth')

@section('content')
    <section class=sign-in-page style="background-image: url('{{ asset('dashboard/images/login/login.webp') }}')">
        <x-auth-card customClassCol="col-lg-7">
            <x-slot name="logo">
                <x-application-logo />
            </x-slot>

            <div class="my-4">
                {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
            </div>

            <!-- Validation Errors -->
            <x-auth-validation-errors class="mb-4" :errors="$errors" />

            <form method="POST" action="{{ route('password.confirm') }}">
                @csrf

                <!-- Password -->
                <div>
                    <x-label for="password" :value="__('Password')" />

                    <x-input id="password" class="block mt-1" type="password" name="password" required
                        autocomplete="current-password" />
                </div>

                <div class="d-flex justify-content-end align-items-center mt-4">
                    <x-button>
                        {{ __('Confirm') }}
                    </x-button>
                </div>
            </form>
        </x-auth-card>
    </section>
@endsection