{{--
    Persistent "verify your email" banner shown to authenticated users
    who haven't clicked the link yet. Includes an inline resend so the
    user never has to navigate to /verify-email to get a fresh email —
    common cause of stuck signups was simply not knowing where the
    resend lives.

    Disappears automatically once email_verified_at is set.
--}}
@auth
    @if (! auth()->user()->hasVerifiedEmail())
        @if (session('status') === 'verification-link-sent')
            <div class="alert alert-success rounded-0 mb-0 text-center" role="alert"
                 style="padding:10px 16px;font-size:13px;">
                A new verification link has been sent to <strong>{{ auth()->user()->email }}</strong>.
            </div>
        @endif

        <div class="alert alert-warning rounded-0 mb-0 d-flex align-items-center justify-content-between flex-wrap gap-2"
             role="alert"
             style="padding:10px 16px;font-size:13px;">
            <div>
                <i class="ph-fill ph-warning-circle me-2"></i>
                <strong>Verify your email.</strong>
                <span class="ms-1">
                    We sent a link to <strong>{{ auth()->user()->email }}</strong>.
                    Didn't get it? Check spam or resend.
                </span>
            </div>
            <form method="POST" action="{{ route('verification.send') }}" class="m-0">
                @csrf
                <button type="submit" class="btn btn-sm btn-warning">
                    <i class="ph ph-paper-plane-tilt me-1"></i> Resend
                </button>
            </form>
        </div>
    @endif
@endauth
