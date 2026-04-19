@extends('profile-hub._layout', ['pageTitle' => 'Devices', 'user' => $user, 'activeTab' => $activeTab])

@section('hub-content')
    <div class="jambo-hub-card">
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
            <div>
                <h5 class="mb-1">Signed-in devices</h5>
                <p class="jambo-hub-card__subtitle mb-0">
                    Active browser sessions on your account. Sign out of any device you don't recognise.
                </p>
            </div>
            @if ($sessions->where('is_current', false)->count() > 0)
                <form method="POST" action="{{ route('profile.devices.logout-others', ['username' => $user->username]) }}"
                      onsubmit="return confirm('Sign out of all other devices?');">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="ph ph-sign-out me-1"></i> Sign out everywhere else
                    </button>
                </form>
            @endif
        </div>

        @if ($sessions->isEmpty())
            <div class="text-center py-4">
                <i class="ph ph-devices fs-1 text-muted d-block mb-2"></i>
                <p class="text-muted mb-0">
                    No active sessions recorded yet. Sign in from another browser to see it here.
                </p>
            </div>
        @else
            <ul class="list-unstyled mb-0 jambo-device-list">
                @foreach ($sessions as $s)
                    <li class="jambo-device-row {{ $s['is_current'] ? 'is-current' : '' }}">
                        <div class="jambo-device-row__icon bg-primary-subtle text-primary-emphasis">
                            <i class="ph {{ $s['agent']['icon'] }}"></i>
                        </div>
                        <div class="flex-grow-1 min-width-0">
                            <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                                <div class="min-width-0">
                                    <div class="fw-semibold">
                                        {{ $s['agent']['browser'] }} on {{ $s['agent']['os'] }}
                                        @if ($s['is_current'])
                                            <span class="badge bg-success ms-1" style="font-size:10px;">This device</span>
                                        @endif
                                    </div>
                                    <div class="text-muted small mt-1 d-flex flex-wrap gap-3">
                                        <span><i class="ph ph-globe me-1"></i>{{ $s['ip_address'] }}</span>
                                        @if ($s['last_activity'])
                                            <span><i class="ph ph-clock me-1"></i>{{ $s['last_activity']->diffForHumans() }}</span>
                                        @endif
                                    </div>
                                </div>
                                @unless ($s['is_current'])
                                    <form method="POST" action="{{ route('profile.devices.destroy', ['username' => $user->username, 'session_id' => $s['id']]) }}"
                                          class="m-0"
                                          onsubmit="return confirm('Sign out of this device?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger-subtle">
                                            <i class="ph ph-sign-out me-1"></i> Sign out
                                        </button>
                                    </form>
                                @endunless
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="jambo-hub-card">
        <div class="d-flex align-items-start gap-3">
            <div class="jambo-device-row__icon bg-warning-subtle text-warning-emphasis flex-shrink-0">
                <i class="ph ph-warning-circle"></i>
            </div>
            <div>
                <h6 class="mb-1">See something you don't recognise?</h6>
                <p class="text-muted small mb-2">
                    Sign out of the device above, then change your password on the
                    <a href="{{ route('profile.security', ['username' => $user->username]) }}">Security tab</a>.
                    If you had 2FA enabled, the device also had to pass that check.
                </p>
            </div>
        </div>
    </div>

    <style>
        .jambo-device-list { display: flex; flex-direction: column; gap: 0.5rem; }

        .jambo-device-row {
            display: flex;
            gap: 0.75rem;
            padding: 0.85rem;
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 10px;
            transition: background 0.15s;
        }
        .jambo-device-row:hover { background: rgba(255,255,255,0.03); }
        .jambo-device-row.is-current {
            background: rgba(40, 199, 111, 0.06);
            border-color: rgba(40, 199, 111, 0.2);
        }

        .jambo-device-row__icon {
            flex-shrink: 0;
            width: 40px; height: 40px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.15rem;
        }

        .min-width-0 { min-width: 0; }
    </style>
@endsection
