@extends('layouts.jambo-auth', ['title' => 'Forgot password'])

@section('header-cta')
    <a href="{{ route('login') }}" class="text-decoration-none">
        <i class="ph ph-arrow-left me-1"></i> Back to sign in
    </a>
@endsection

@section('content')
    <div class="jambo-auth-card">
        <h1>Forgot your password?</h1>
        <p class="jambo-auth-card__subtitle">
            We'll email you a reset link. No password is changed until you click it.
        </p>

        @if (session('status'))
            <div class="jambo-auth-alert jambo-auth-alert--success">{{ session('status') }}</div>

            {{-- Spam-folder reminder for launch phase. See the note in
                 auth/verify-email.blade.php for why this is here and
                 when to remove it. Only renders alongside the success
                 flash so we don't intrude on the empty initial state. --}}
            <div class="jambo-auth-notice" style="margin: 0.75rem 0 1rem; padding: 0.75rem 1rem; border-radius: 8px; background: rgba(255, 193, 7, 0.1); border: 1px solid rgba(255, 193, 7, 0.35); color: #b88600; font-size: 0.9rem; line-height: 1.4;">
                <strong>Heads up:</strong> if the email doesn't arrive in a minute or two, check your
                <strong>Spam</strong> or <strong>Junk</strong> folder — we're a new sender and Gmail
                sometimes filters us. Mark it "Not spam" so future emails reach your inbox.
            </div>
        @endif

        @if ($errors->any())
            <div class="jambo-auth-alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" novalidate>
            @csrf

            <div class="jambo-field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                       autocomplete="email" autofocus required placeholder="you@example.com">
            </div>

            <x-auth.bot-defence action="forgot_password" />

            <button type="submit" class="jambo-auth-btn mt-3">Send reset link</button>
        </form>

        <div class="jambo-auth-footer">
            Remembered it? <a href="{{ route('login') }}">Sign in</a>
        </div>
    </div>
@endsection
