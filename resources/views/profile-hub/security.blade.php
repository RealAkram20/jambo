@extends('profile-hub._layout', ['pageTitle' => 'Security', 'user' => $user, 'activeTab' => $activeTab])

@section('hub-content')
    {{-- Password ================================================== --}}
    <div class="jambo-hub-card">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h5 class="mb-1">Password</h5>
                <p class="jambo-hub-card__subtitle">Update your account password.</p>
            </div>
            <i class="ph ph-lock-key fs-2 text-muted"></i>
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

    {{-- 2FA ======================================================= --}}
    <div class="jambo-hub-card">
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
                <p class="jambo-hub-card__subtitle">
                    Add an authenticator app as a second step at sign-in.
                </p>
            </div>
            <i class="ph ph-shield-check fs-2 text-muted"></i>
        </div>

        @if (!$is2faEnabled && !$hasPendingSetup)
            <form method="POST" action="{{ route('two-factor.enable') }}">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="ph ph-plus-circle me-1"></i> Enable two-factor
                </button>
            </form>
        @elseif ($hasPendingSetup)
            <div class="row g-4 align-items-start">
                <div class="col-md-5 text-center">
                    <div class="p-3 bg-white rounded-3 d-inline-block">{!! $qrSvg !!}</div>
                    <p class="text-muted small mt-2 mb-0">
                        Or enter this key manually:<br>
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
                        <button type="submit" class="btn btn-link btn-sm text-muted p-0">Cancel setup</button>
                    </form>
                </div>
            </div>
        @else
            <div class="mb-3">
                <label class="form-label small text-muted">Recovery codes</label>
                <p class="small text-muted mb-2">
                    Use one of these if you lose your authenticator. Each code works once.
                </p>
                @if (count($recoveryCodes))
                    <div class="p-3 bg-dark border rounded-3 font-monospace small">
                        @foreach ($recoveryCodes as $code)
                            <div>{{ $code }}</div>
                        @endforeach
                    </div>
                @else
                    <p class="small text-warning mb-2">No recovery codes left — regenerate a new batch.</p>
                @endif
                <form method="POST" action="{{ route('two-factor.recovery-codes') }}" class="mt-2"
                      onsubmit="return confirm('Regenerate recovery codes? Existing codes will stop working.');">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary btn-sm">Regenerate codes</button>
                </form>
            </div>
            <form method="POST" action="{{ route('two-factor.disable') }}"
                  onsubmit="return confirm('Turn off two-factor authentication?');">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm">Disable two-factor</button>
            </form>
        @endif
    </div>

    {{-- Danger zone ============================================== --}}
    <div class="jambo-hub-card" style="border-color: rgba(220,53,69,0.25);">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h5 class="mb-1 text-danger">Deactivate account</h5>
                <p class="jambo-hub-card__subtitle">
                    Sign out permanently and block future logins. Data stays — support can reactivate.
                </p>
            </div>
            <i class="ph ph-warning-circle fs-2 text-danger"></i>
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
@endsection
