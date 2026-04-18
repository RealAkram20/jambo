@extends('layouts.jambo-auth', ['title' => 'Confirm your password'])

@section('content')
    <div class="jambo-auth-card">
        <h1>Confirm your password</h1>
        <p class="jambo-auth-card__subtitle">
            This is a protected area. Please confirm your password to continue.
        </p>

        @if ($errors->any())
            <div class="jambo-auth-alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('password.confirm') }}">
            @csrf

            <div class="jambo-field jambo-field--with-toggle">
                <label for="password">Password</label>
                <input id="password" type="password" name="password"
                       autocomplete="current-password" required autofocus
                       placeholder="Your password">
                <button type="button" class="jambo-field__toggle" aria-label="Show password">
                    <i class="ph ph-eye-slash"></i>
                </button>
            </div>

            <button type="submit" class="jambo-auth-btn mt-3">Confirm</button>
        </form>
    </div>
@endsection
