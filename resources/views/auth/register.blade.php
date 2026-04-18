@extends('layouts.jambo-auth', ['title' => 'Sign up'])

@section('header-cta')
    Already have an account?
    <a href="{{ route('login') }}" class="text-primary fw-semibold text-decoration-none ms-1">
        Sign in
    </a>
@endsection

@section('content')
    <div class="jambo-auth-card jambo-auth-card--wide">
        <h1>Create your account</h1>
        <p class="jambo-auth-card__subtitle">It's free to start — pick a plan once you're in.</p>

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

        <form method="POST" action="{{ route('register') }}" novalidate>
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="jambo-field">
                        <label for="first_name">First name</label>
                        <input id="first_name" type="text" name="first_name"
                               value="{{ old('first_name') }}" required autofocus
                               placeholder="First name">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="jambo-field">
                        <label for="last_name">Last name</label>
                        <input id="last_name" type="text" name="last_name"
                               value="{{ old('last_name') }}" required
                               placeholder="Last name">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="jambo-field">
                        <label for="username">Display name</label>
                        <input id="username" type="text" name="username"
                               value="{{ old('username') }}" required
                               placeholder="How you'll show up"
                               autocomplete="username">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="jambo-field">
                        <label for="email">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}"
                               required autocomplete="email" placeholder="you@example.com">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="jambo-field jambo-field--with-toggle">
                        <label for="password">Password</label>
                        <input id="password" type="password" name="password" required
                               autocomplete="new-password" placeholder="At least 8 characters">
                        <button type="button" class="jambo-field__toggle" aria-label="Show password">
                            <i class="ph ph-eye-slash"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="jambo-field jambo-field--with-toggle">
                        <label for="password_confirmation">Confirm password</label>
                        <input id="password_confirmation" type="password" name="password_confirmation"
                               required autocomplete="new-password" placeholder="Repeat your password">
                        <button type="button" class="jambo-field__toggle" aria-label="Show password">
                            <i class="ph ph-eye-slash"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Terms acceptance. Not persisted on the User row — the
                 fact of account creation is the implicit consent record,
                 and the registration event logs IP+UA server-side which
                 satisfies most audit needs. --}}
            <div class="mt-3 small text-muted">
                By creating an account you agree to our
                <a href="{{ route('frontend.terms-and-policy') }}" class="text-primary text-decoration-none">Terms</a>
                and
                <a href="{{ route('frontend.privacy-policy') }}" class="text-primary text-decoration-none">Privacy Policy</a>.
            </div>

            <button type="submit" class="jambo-auth-btn mt-4">Create account</button>
        </form>

        @if (config('services.google.client_id'))
            <div class="jambo-auth-divider">or continue with</div>
            <a href="{{ route('auth.social', 'google') }}" class="jambo-social-btn">
                <svg viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.9 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3 0 5.8 1.1 7.9 3l5.7-5.7C34.1 6.1 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20c11 0 19.8-8 19.8-20 0-1.3-.1-2.3-.2-3.5z"/><path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.6 16 18.9 13 24 13c3 0 5.8 1.1 7.9 3l5.7-5.7C34.1 7.1 29.3 5 24 5c-7.7 0-14.3 4.4-17.7 10.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 10-2 13.6-5.2l-6.3-5.2c-2 1.4-4.5 2.4-7.3 2.4-5.2 0-9.6-3.3-11.2-7.9l-6.5 5C9.4 39.5 16.1 44 24 44z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.1-4 5.6l6.3 5.2C41 35.5 44 30.2 44 24c0-1.3-.1-2.3-.4-3.5z"/></svg>
                Continue with Google
            </a>
        @endif

        <div class="jambo-auth-footer">
            Already have an account? <a href="{{ route('login') }}">Sign in</a>
        </div>
    </div>
@endsection
