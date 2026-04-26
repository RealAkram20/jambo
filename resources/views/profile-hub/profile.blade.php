@extends('profile-hub._layout', ['pageTitle' => 'Profile', 'user' => $user, 'activeTab' => $activeTab])

@section('hub-content')
    <div class="jambo-hub-card">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h5 class="mb-1">Profile</h5>
                <p class="jambo-hub-card__subtitle">Your public-facing name and sign-in email.</p>
            </div>
            <i class="ph ph-user-circle fs-2 text-muted"></i>
        </div>

        @if (session('status-profile'))
            <div class="alert alert-success py-2 small">{{ session('status-profile') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger py-2 small">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                </ul>
            </div>
        @endif

        @php
            $hasAvatar = $user->getFirstMediaUrl('profile_image') !== '';
            $initial = strtoupper(substr($user->first_name ?? $user->username ?? '?', 0, 1));
        @endphp
        <div class="d-flex align-items-center gap-3 mb-4">
            <div class="d-flex align-items-center justify-content-center position-relative flex-shrink-0"
                 style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#2b3141 0%,#141923 100%);border:1px solid #1f2738;font-size:24px;font-weight:600;color:#f5f6f8;overflow:hidden;">
                @if ($hasAvatar)
                    <img src="{{ $user->getFirstMediaUrl('profile_image') }}" alt=""
                         style="width:100%;height:100%;object-fit:cover;">
                @else
                    {{ $initial }}
                @endif
            </div>
            <div class="d-flex flex-column gap-1">
                <form method="POST" action="{{ route('profile.avatar.upload', ['username' => $user->username]) }}"
                      enctype="multipart/form-data" class="m-0">
                    @csrf
                    <label class="btn btn-sm btn-outline-primary mb-0" style="cursor:pointer;">
                        <i class="ph ph-camera me-1"></i>
                        {{ $hasAvatar ? 'Change photo' : 'Upload photo' }}
                        <input type="file" name="profile_image" accept="image/*"
                               onchange="this.form.submit()" hidden>
                    </label>
                </form>
                @if ($hasAvatar)
                    <form method="POST" action="{{ route('profile.avatar.destroy', ['username' => $user->username]) }}"
                          class="m-0" onsubmit="return confirm('Remove your profile photo?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-link text-danger p-0">Remove photo</button>
                    </form>
                @endif
            </div>
        </div>

        <form method="POST" action="{{ route('profile.update', ['username' => $user->username]) }}">
            @csrf @method('PUT')

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small text-muted">First name</label>
                    <input type="text" name="first_name" class="form-control form-control-sm"
                           value="{{ old('first_name', $user->first_name) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small text-muted">Last name</label>
                    <input type="text" name="last_name" class="form-control form-control-sm"
                           value="{{ old('last_name', $user->last_name) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small text-muted">Username</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">@</span>
                        <input type="text" name="username" class="form-control form-control-sm"
                               value="{{ old('username', $user->username) }}" required
                               pattern="[a-zA-Z0-9_.\-]+"
                               minlength="3" maxlength="50">
                    </div>
                    <small class="text-muted">Letters, numbers, dot, hyphen, underscore. Your profile URL changes with this.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label small text-muted">Email</label>
                    <input type="email" name="email" class="form-control form-control-sm"
                           value="{{ old('email', $user->email) }}" required>
                    @if ($user->email_verified_at)
                        <small class="text-success"><i class="ph ph-check-circle"></i> Verified</small>
                    @else
                        <small class="text-warning"><i class="ph ph-warning-circle"></i> Not verified</small>
                    @endif
                </div>
                <div class="col-md-6 mt-3">
                    <label class="form-label small text-muted">Phone <span class="text-muted">(optional)</span></label>
                    <input type="tel" name="phone" class="form-control form-control-sm"
                           value="{{ old('phone', $user->phone) }}"
                           placeholder="+256 700 123 456"
                           maxlength="32"
                           autocomplete="tel">
                    <small class="text-muted">Used to prefill payments with M-Pesa, MTN MoMo, Airtel Money.</small>
                </div>
            </div>

            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">Save changes</button>
            </div>
        </form>
    </div>

    <div class="jambo-hub-card">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h5 class="mb-1">Profile URL</h5>
                <p class="jambo-hub-card__subtitle mb-0">
                    <code class="text-primary">{{ url('/' . $user->username) }}</code>
                </p>
            </div>
            <i class="ph ph-link fs-2 text-muted"></i>
        </div>
    </div>
@endsection
