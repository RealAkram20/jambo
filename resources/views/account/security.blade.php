@extends('frontend::layouts.master', ['isBreadCrumb' => true, 'title' => 'Security'])

@section('content')
<section class="section-padding">
    <div class="container" style="max-width: 880px;">
        <div class="d-flex flex-column gap-2 mb-4">
            <h3 class="main-title mb-0">Security</h3>
            <p class="text-muted mb-0 small">
                Keep your account safe. Changes here require your current password.
            </p>
        </div>

        @if (session('status'))
            <div class="alert alert-success py-2 small">{{ session('status') }}</div>
        @endif

        {{-- ==================================================================
             Password
             ================================================================== --}}
        <div class="card bg-body-tertiary border-0 mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h5 class="mb-1">Password</h5>
                        <p class="text-muted mb-0 small">Update your account password.</p>
                    </div>
                    <i class="ph ph-lock-key fs-3 text-muted"></i>
                </div>

                @if ($errors->updatePassword->any())
                    <div class="alert alert-danger py-2 small">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->updatePassword->all() as $e) <li>{{ $e }}</li> @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('password.update') }}" class="row g-3">
                    @csrf @method('PUT')
                    <div class="col-md-4">
                        <label class="form-label small text-muted">Current password</label>
                        <input type="password" name="current_password" class="form-control form-control-sm"
                               autocomplete="current-password" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted">New password</label>
                        <input type="password" name="password" class="form-control form-control-sm"
                               autocomplete="new-password" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted">Confirm new password</label>
                        <input type="password" name="password_confirmation" class="form-control form-control-sm"
                               autocomplete="new-password" required>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-sm">Update password</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ==================================================================
             Two-factor authentication
             ================================================================== --}}
        <div class="card bg-body-tertiary border-0 mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h5 class="mb-1">
                            Two-factor authentication
                            @if ($is2faEnabled)
                                <span class="badge bg-success ms-1">On</span>
                            @elseif ($hasPendingSetup)
                                <span class="badge bg-warning ms-1">Pending</span>
                            @else
                                <span class="badge bg-secondary ms-1">Off</span>
                            @endif
                        </h5>
                        <p class="text-muted mb-0 small">
                            Add an authenticator app (Google Authenticator, Authy, 1Password) as a
                            second step at sign-in.
                        </p>
                    </div>
                    <i class="ph ph-shield-check fs-3 text-muted"></i>
                </div>

                @if (!$is2faEnabled && !$hasPendingSetup)
                    {{-- Not enabled — one-click start --}}
                    <form method="POST" action="{{ route('two-factor.enable') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="ph ph-plus-circle me-1"></i> Enable two-factor
                        </button>
                    </form>
                @elseif ($hasPendingSetup)
                    {{-- Setup in progress — show QR + confirmation form --}}
                    <div class="row g-4 align-items-start">
                        <div class="col-md-5 text-center">
                            <div class="p-3 bg-white rounded-3 d-inline-block">
                                {!! $qrSvg !!}
                            </div>
                            <p class="text-muted small mt-2 mb-0">
                                Or enter this key manually:
                                <code class="text-primary">{{ $manualSecret }}</code>
                            </p>
                        </div>
                        <div class="col-md-7">
                            <p class="small text-muted mb-2">
                                1. Scan the QR with your authenticator app<br>
                                2. Enter the 6-digit code it shows to confirm
                            </p>
                            @if ($errors->any())
                                <div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>
                            @endif
                            <form method="POST" action="{{ route('two-factor.confirm') }}" class="d-flex gap-2 align-items-end">
                                @csrf
                                <div class="flex-grow-1">
                                    <label class="form-label small text-muted">Code</label>
                                    <input type="text" name="code" class="form-control form-control-sm"
                                           inputmode="numeric" maxlength="6" pattern="[0-9]{6}"
                                           autocomplete="one-time-code" autofocus required
                                           placeholder="123456">
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm">Confirm</button>
                            </form>
                            <form method="POST" action="{{ route('two-factor.disable') }}" class="mt-2"
                                  onsubmit="return confirm('Cancel 2FA setup?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-link btn-sm text-muted p-0">
                                    Cancel setup
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    {{-- Enabled — recovery codes + disable --}}
                    <div class="mb-3">
                        <label class="form-label small text-muted">Recovery codes</label>
                        <p class="small text-muted mb-2">
                            Use one of these codes if you lose access to your authenticator.
                            Each code works once. Store them somewhere safe.
                        </p>
                        @if (count($recoveryCodes))
                            <div class="p-3 bg-dark border rounded-3 font-monospace small">
                                @foreach ($recoveryCodes as $code)
                                    <div>{{ $code }}</div>
                                @endforeach
                            </div>
                        @else
                            <p class="small text-warning mb-2">
                                You have no recovery codes left. Regenerate a new batch now.
                            </p>
                        @endif
                        <form method="POST" action="{{ route('two-factor.recovery-codes') }}" class="mt-2"
                              onsubmit="return confirm('Regenerate recovery codes? Existing codes will stop working.');">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary btn-sm">
                                Regenerate codes
                            </button>
                        </form>
                    </div>
                    <form method="POST" action="{{ route('two-factor.disable') }}"
                          onsubmit="return confirm('Turn off two-factor authentication?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            Disable two-factor
                        </button>
                    </form>
                @endif
            </div>
        </div>

        {{-- ==================================================================
             Social sign-in (informational)
             ================================================================== --}}
        @if ($googleEnabled)
            <div class="card bg-body-tertiary border-0 mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-1">Connected accounts</h5>
                            <p class="text-muted mb-0 small">
                                Use Google to sign in without a password on any device.
                            </p>
                        </div>
                        <i class="ph ph-plugs fs-3 text-muted"></i>
                    </div>
                </div>
            </div>
        @endif

        {{-- ==================================================================
             Danger zone — deactivate account
             ================================================================== --}}
        <div class="card border-danger border-opacity-25 bg-body-tertiary">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h5 class="mb-1 text-danger">Deactivate account</h5>
                        <p class="text-muted mb-0 small">
                            Sign out permanently and block future logins. Your watch history and
                            subscriptions stay — support can reactivate on request.
                        </p>
                    </div>
                    <i class="ph ph-warning-circle fs-3 text-danger"></i>
                </div>

                @if ($errors->deactivate->any())
                    <div class="alert alert-danger py-2 small">{{ $errors->deactivate->first() }}</div>
                @endif

                <form method="POST" action="{{ route('account.deactivate') }}"
                      onsubmit="return confirm('Deactivate your account? You will be signed out immediately.');">
                    @csrf @method('DELETE')

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Confirm your password</label>
                            <input type="password" name="password" class="form-control form-control-sm"
                                   autocomplete="current-password" required>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <label class="form-check d-flex align-items-center gap-2 m-0 small">
                                <input type="checkbox" class="form-check-input m-0" name="confirm" value="1" required>
                                I understand my account will be deactivated
                            </label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="ph ph-door me-1"></i> Deactivate account
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

@include('frontend::components.widgets.mobile-footer')
@endsection
