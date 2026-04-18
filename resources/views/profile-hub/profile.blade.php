@include('profile-hub._layout', ['pageTitle' => 'Profile', 'user' => $user, 'activeTab' => $activeTab])

@section('hub-content')
    <div class="jambo-hub-card">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h5 class="mb-1">Profile</h5>
                <p class="jambo-hub-card__subtitle">Your public-facing name and sign-in email.</p>
            </div>
            <i class="ph ph-user-circle fs-2 text-muted"></i>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger py-2 small">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                </ul>
            </div>
        @endif

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
