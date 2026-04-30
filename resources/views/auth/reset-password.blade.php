@extends('layouts.jambo-auth', ['title' => 'Reset password'])

@section('content')
    <div class="jambo-auth-card">
        <h1>Choose a new password</h1>
        <p class="jambo-auth-card__subtitle">Pick something you'll remember — 8 characters minimum.</p>

        @if ($errors->any())
            <div class="jambo-auth-alert">
                @if ($errors->count() === 1)
                    {{ $errors->first() }}
                @else
                    <ul>
                        @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                    </ul>
                @endif
            </div>
        @endif

        {{-- password.store handles the reset token + new-password POST.
             password.update is a different route (PUT) used by signed-in
             users updating their own password from the security tab —
             posting here used to 405. --}}
        <form method="POST" action="{{ route('password.store') }}" novalidate>
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div class="jambo-field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email"
                       value="{{ old('email', $request->email) }}"
                       autocomplete="email" required>
            </div>

            <div class="jambo-field jambo-field--with-toggle">
                <label for="password">New password</label>
                <input id="password" type="password" name="password"
                       autocomplete="new-password" required placeholder="New password">
                <button type="button" class="jambo-field__toggle" aria-label="Show password">
                    <i class="ph ph-eye-slash"></i>
                </button>
            </div>

            <div class="jambo-field jambo-field--with-toggle">
                <label for="password_confirmation">Confirm password</label>
                <input id="password_confirmation" type="password" name="password_confirmation"
                       autocomplete="new-password" required placeholder="Repeat new password">
                <button type="button" class="jambo-field__toggle" aria-label="Show password">
                    <i class="ph ph-eye-slash"></i>
                </button>
            </div>

            <button type="submit" class="jambo-auth-btn mt-3">Reset password</button>
        </form>

        <div class="jambo-auth-footer">
            <a href="{{ route('login') }}">Back to sign in</a>
        </div>
    </div>
@endsection
