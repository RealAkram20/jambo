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
                                <label class="form-label">Current password <span class="text-danger">*</span></label>
                                <input type="password" name="current_password" class="form-control"
                                    autocomplete="current-password" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">New password <span class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control"
                                    autocomplete="new-password" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Confirm new password <span class="text-danger">*</span></label>
                                <input type="password" name="password_confirmation" class="form-control"
                                    autocomplete="new-password" required>
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
                 ============================================================ --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Two-factor authentication</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3 flex-wrap">
                        <i class="ph {{ $is2faEnabled ? 'ph-shield-check' : 'ph-shield-warning' }}"
                           style="font-size:36px;color:{{ $is2faEnabled ? 'var(--bs-success, #2dd47a)' : 'var(--bs-warning, #f59e0b)' }};"></i>
                        <div class="flex-grow-1" style="min-width:200px;">
                            <div class="mb-1">
                                @if ($is2faEnabled)
                                    <span class="badge bg-success-subtle text-success-emphasis">Enabled</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning-emphasis">Not set up</span>
                                @endif
                            </div>
                            <p class="mb-0 text-muted" style="font-size:13px;">
                                @if ($is2faEnabled)
                                    Authenticator app required on sign-in. Manage recovery codes or disable from your security page.
                                @else
                                    Add an authenticator app to protect the admin account.
                                @endif
                            </p>
                        </div>
                        <a href="{{ route('profile.security', ['username' => $user->username]) }}"
                            class="btn btn-outline-primary">
                            <i class="ph ph-key me-1"></i>
                            {{ $is2faEnabled ? 'Manage' : 'Set up' }}
                        </a>
                    </div>
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
