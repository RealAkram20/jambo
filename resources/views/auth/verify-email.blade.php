@extends('layouts.jambo-auth', ['title' => 'Verify your email'])

@section('content')
    <div class="jambo-auth-card">
        <h1>Verify your email</h1>
        <p class="jambo-auth-card__subtitle">
            Thanks for signing up. We've sent a verification link to your inbox —
            click it to activate your account. Can't find it? Check your spam folder.
        </p>

        @if (session('status') == 'verification-link-sent')
            <div class="jambo-auth-alert jambo-auth-alert--success">
                A new verification link has been sent.
            </div>
        @endif

        <div class="d-flex flex-column gap-2 mt-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="jambo-auth-btn">Resend verification link</button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="jambo-social-btn">
                    <i class="ph ph-sign-out"></i> Log out
                </button>
            </form>
        </div>
    </div>
@endsection
