@extends('layouts.app', ['module_title' => 'My profile', 'title' => 'My profile'])

@section('content')
@php
    $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
    $fullName = $fullName ?: $user->username;
    // First initials for the avatar placeholder — cheap & recognisable
    // without adding an upload flow.
    $initials = strtoupper(
        substr($user->first_name ?? $user->username, 0, 1) .
        substr($user->last_name ?? '', 0, 1)
    );
@endphp

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8 mx-auto">

            {{-- Identity strip — lightweight header that gives the page
                 context without the template's demo banner. --}}
            <div class="card mb-4">
                <div class="card-body d-flex align-items-center gap-3 flex-wrap">
                    <div class="jambo-admin-avatar d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,#2b3141 0%,#141923 100%);border:1px solid #1f2738;font-size:20px;font-weight:600;color:#f5f6f8;letter-spacing:.5px;">
                        {{ $initials }}
                    </div>
                    <div class="flex-grow-1" style="min-width:0;">
                        <h5 class="mb-1" style="font-weight:600;">{{ $fullName }}</h5>
                        <div class="text-muted d-flex align-items-center gap-3 flex-wrap" style="font-size:13px;">
                            <span><i class="ph ph-at"></i> {{ $user->username }}</span>
                            <span><i class="ph ph-envelope-simple"></i> {{ $user->email }}</span>
                        </div>
                    </div>
                    <div class="d-flex gap-1 flex-wrap">
                        @foreach ($user->roles as $role)
                            <span class="badge @class([
                                'bg-primary' => $role->name === 'admin',
                                'bg-secondary' => $role->name !== 'admin',
                            ])">{{ $role->name }}</span>
                        @endforeach
                        @if ($user->email_verified_at)
                            <span class="badge bg-success-subtle text-success-emphasis">
                                <i class="ph ph-check-circle"></i> Verified
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ============================================================
                 Profile settings
                 ============================================================ --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Profile settings</h5>
                </div>
                <div class="card-body">
                    @if (session('status-profile'))
                        <div class="alert alert-success mb-3">{{ session('status-profile') }}</div>
                    @endif

                    @if ($errors->isNotEmpty() && !$errors->hasBag('updatePassword'))
                        <div class="alert alert-danger mb-3">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('dashboard.profile.update') }}">
                        @csrf
                        @method('PATCH')

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">First name <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" class="form-control"
                                    value="{{ old('first_name', $user->first_name) }}" required maxlength="100">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last name <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" class="form-control"
                                    value="{{ old('last_name', $user->last_name) }}" required maxlength="100">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Username <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">@</span>
                                    <input type="text" name="username" class="form-control"
                                        value="{{ old('username', $user->username) }}" required
                                        pattern="[a-zA-Z0-9_.\-]+" minlength="3" maxlength="50">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control"
                                    value="{{ old('email', $user->email) }}" required maxlength="255">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="tel" name="phone" class="form-control"
                                    value="{{ old('phone', $user->phone) }}" maxlength="32"
                                    placeholder="+256 700 123 456">
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary">
                                <i class="ph ph-floppy-disk me-1"></i> Save changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ============================================================
                 Change password
                 ============================================================ --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Change password</h5>
                </div>
                <div class="card-body">
                    @if (session('status-password'))
                        <div class="alert alert-success mb-3">{{ session('status-password') }}</div>
                    @endif

                    @if ($errors->hasBag('updatePassword'))
                        <div class="alert alert-danger mb-3">
                            <ul class="mb-0">
                                @foreach ($errors->updatePassword->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('dashboard.profile.password') }}">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="current_password">Current password <span class="text-danger">*</span></label>
                                <x-password-input name="current_password" autocomplete="current-password" required />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="password">New password <span class="text-danger">*</span></label>
                                <x-password-input name="password" autocomplete="new-password" required />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="password_confirmation">Confirm new password <span class="text-danger">*</span></label>
                                <x-password-input name="password_confirmation" autocomplete="new-password" required />
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary">
                                <i class="ph ph-lock-key me-1"></i> Update password
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ============================================================
                 Two-factor authentication

                 Three states rendered inline so admins never leave the
                 admin chrome:
                   • disabled      → Enable button (starts setup)
                   • pending setup → QR code + manual key + confirm input
                   • enabled       → recovery codes + regenerate + disable

                 All forms post to `two-factor.enable / confirm /
                 disable / recovery-codes`. `TwoFactorController`
                 redirects admins back here after each action.
                 ============================================================ --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        Two-factor authentication
                        @if ($is2faEnabled)
                            <span class="badge bg-success-subtle text-success-emphasis ms-2">Enabled</span>
                        @elseif ($hasPendingSetup)
                            <span class="badge bg-warning-subtle text-warning-emphasis ms-2">Pending</span>
                        @else
                            <span class="badge bg-secondary ms-2">Off</span>
                        @endif
                    </h5>
                </div>
                <div class="card-body">
                    @if (session('status-2fa'))
                        <div class="alert alert-success mb-3">{{ session('status-2fa') }}</div>
                    @endif

                    @if (!$is2faEnabled && !$hasPendingSetup)
                        {{-- State 1: disabled. One-click start. --}}
                        <div class="d-flex align-items-start gap-3 flex-wrap">
                            <i class="ph ph-shield-warning" style="font-size:36px;color:var(--bs-warning, #f59e0b);"></i>
                            <div class="flex-grow-1" style="min-width:200px;">
                                <p class="mb-0 text-muted" style="font-size:13px;">
                                    Add an authenticator app as a second step at sign-in.
                                </p>
                            </div>
                            <form method="POST" action="{{ route('two-factor.enable') }}" class="m-0">
                                @csrf
                                <button type="submit" class="btn btn-primary">
                                    <i class="ph ph-plus-circle me-1"></i> Enable two-factor
                                </button>
                            </form>
                        </div>

                    @elseif ($hasPendingSetup)
                        {{-- State 2: pending. QR + manual key + confirm. --}}
                        <div class="row g-4 align-items-start">
                            <div class="col-md-5 text-center">
                                <div class="p-3 bg-white rounded-3 d-inline-block">{!! $qrSvg !!}</div>
                                <p class="text-muted small mt-2 mb-0">
                                    Or enter this key manually:<br>
                                    <code class="text-primary">{{ $manualSecret }}</code>
                                </p>
                            </div>
                            <div class="col-md-7">
                                <p class="small text-muted mb-3">
                                    1. Scan the QR with your authenticator app<br>
                                    2. Enter the 6-digit code it shows to confirm
                                </p>
                                @if ($errors->has('code'))
                                    <div class="alert alert-danger py-2 small mb-2">{{ $errors->first('code') }}</div>
                                @endif
                                <form method="POST" action="{{ route('two-factor.confirm') }}" class="d-flex gap-2 align-items-end">
                                    @csrf
                                    <div class="flex-grow-1">
                                        <label class="form-label small text-muted">Code</label>
                                        <input type="text" name="code" class="form-control"
                                            inputmode="numeric" maxlength="6" pattern="[0-9]{6}"
                                            autocomplete="one-time-code" autofocus required
                                            placeholder="123456">
                                    </div>
                                    <button type="submit" class="btn btn-primary">Confirm</button>
                                </form>
                                <form method="POST" action="{{ route('two-factor.disable') }}" class="mt-2 m-0"
                                    onsubmit="return confirm('Cancel 2FA setup?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-link btn-sm text-muted p-0">Cancel setup</button>
                                </form>
                            </div>
                        </div>

                    @else
                        {{-- State 3: enabled. Recovery codes + disable. --}}
                        <div class="d-flex align-items-start gap-3 flex-wrap mb-4">
                            <i class="ph ph-shield-check" style="font-size:36px;color:var(--bs-success, #2dd47a);"></i>
                            <div class="flex-grow-1" style="min-width:200px;">
                                <p class="mb-0 text-muted" style="font-size:13px;">
                                    Authenticator app required on every sign-in.
                                </p>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small text-muted d-block mb-2">Recovery codes</label>
                            <p class="small text-muted mb-2">
                                Use one of these if you lose your authenticator. Each code works once.
                            </p>
                            @if (!empty($recoveryCodes))
                                <div class="p-3 rounded"
                                    style="background:#0b0f17;border:1px solid #1f2738;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:13px;">
                                    <div class="row g-2">
                                        @foreach ($recoveryCodes as $code)
                                            <div class="col-md-6">
                                                <code>{{ $code }}</code>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            <form method="POST" action="{{ route('two-factor.recovery-codes') }}" class="mt-2 m-0"
                                onsubmit="return confirm('Generate new codes? The old ones will stop working.');">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary btn-sm">
                                    <i class="ph ph-arrows-clockwise me-1"></i> Regenerate codes
                                </button>
                            </form>
                        </div>

                        <form method="POST" action="{{ route('two-factor.disable') }}" class="pt-3 border-top m-0"
                            onsubmit="return confirm('Disable two-factor authentication? Your account will be less secure.');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                <i class="ph ph-shield-slash me-1"></i> Disable two-factor
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- ============================================================
                 Active sessions
                 ============================================================ --}}
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center justify-content-between gap-3 flex-wrap">
                    <h5 class="card-title mb-0">Active sessions</h5>
                    @if ($sessions->where('is_current', false)->isNotEmpty())
                        <form method="POST" action="{{ route('dashboard.profile.sessions.logout-others') }}" class="m-0"
                            onsubmit="return confirm('Sign out of every other device that\'s currently signed in?');">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                <i class="ph ph-sign-out me-1"></i> Sign out other devices
                            </button>
                        </form>
                    @endif
                </div>
                <div class="card-body p-0">
                    @if (session('status-sessions'))
                        <div class="alert alert-success mb-0 rounded-0">{{ session('status-sessions') }}</div>
                    @endif

                    @if ($sessions->isEmpty())
                        <div class="p-4 text-center text-muted" style="font-size:13px;">
                            No active sessions tracked.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr class="text-uppercase" style="font-size:11px;letter-spacing:.5px;">
                                        <th>Device</th>
                                        <th>IP</th>
                                        <th>Last active</th>
                                        <th class="text-end" style="width:120px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($sessions as $s)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="ph {{ $s['agent']['icon'] }}" style="font-size:20px;"></i>
                                                    <div>
                                                        <div>{{ $s['agent']['browser'] }} <span class="text-muted">on</span> {{ $s['agent']['os'] }}</div>
                                                        @if ($s['is_current'])
                                                            <span class="badge bg-success-subtle text-success-emphasis" style="font-size:10px;">This device</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td><code style="font-size:12px;">{{ $s['ip_address'] }}</code></td>
                                            <td style="font-size:12px;color:var(--bs-secondary);">
                                                {{ $s['last_activity']?->diffForHumans() ?? '—' }}
                                            </td>
                                            <td class="text-end">
                                                @unless ($s['is_current'])
                                                    <form method="POST" action="{{ route('dashboard.profile.sessions.destroy', $s['id']) }}" class="m-0 d-inline"
                                                        onsubmit="return confirm('Sign this device out?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            <i class="ph ph-sign-out"></i> Sign out
                                                        </button>
                                                    </form>
                                                @endunless
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
