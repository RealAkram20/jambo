@extends('layouts.jambo-auth', ['title' => 'Verify your email'])

@section('content')
    <div class="jambo-auth-card">
        <h1>Verify your email</h1>
        <p class="jambo-auth-card__subtitle">
            Thanks for signing up. We've sent a verification link to your inbox —
            click it to activate your account.
        </p>

        {{-- Gmail/Outlook spam-folder notice. We're a freshly-launched
             sender on a brand-new VPS IP, so the big inbox providers
             aggressively spam-fold our verification mail until our
             reputation builds. Surfacing this here so users actually
             find the email instead of giving up. Remove this banner
             once we've been live ~6 weeks and the bounce-to-spam rate
             has dropped (check Postfix logs / inbox tests). --}}
        <div class="jambo-auth-notice" style="margin: 0.75rem 0 1rem; padding: 0.75rem 1rem; border-radius: 8px; background: rgba(255, 193, 7, 0.1); border: 1px solid rgba(255, 193, 7, 0.35); color: #b88600; font-size: 0.9rem; line-height: 1.4;">
            <strong>Don't see it within a minute?</strong>
            Check your <strong>Spam</strong> or <strong>Junk</strong> folder.
            We're a new sender, so Gmail and Outlook may filter the first email.
            Marking it "Not spam" tells your provider to deliver future emails to your inbox.
        </div>

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
