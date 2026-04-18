@extends('layouts.jambo-auth', ['title' => 'Two-factor verification'])

@section('content')
    <div class="jambo-auth-card">
        <h1>Two-factor verification</h1>
        <p class="jambo-auth-card__subtitle">
            Enter the 6-digit code from your authenticator app. Lost your phone?
            Use a recovery code instead.
        </p>

        @if ($errors->any())
            <div class="jambo-auth-alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('two-factor.verify') }}" novalidate>
            @csrf

            <div class="jambo-field">
                <label for="code">Authenticator code</label>
                <input id="code" type="text" name="code" inputmode="numeric" pattern="[0-9]{6}"
                       maxlength="6" autocomplete="one-time-code" autofocus
                       placeholder="123456">
            </div>

            <details class="mt-3">
                <summary class="small text-muted" style="cursor:pointer;">Use a recovery code instead</summary>
                <div class="jambo-field mt-2">
                    <input type="text" name="recovery_code" placeholder="XXXXXXXX-XXXXXXXX"
                           autocomplete="off">
                </div>
            </details>

            <button type="submit" class="jambo-auth-btn mt-3">Verify</button>
        </form>

        <div class="jambo-auth-footer">
            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-link p-0 text-muted small">
                    Cancel and sign out
                </button>
            </form>
        </div>
    </div>
@endsection
