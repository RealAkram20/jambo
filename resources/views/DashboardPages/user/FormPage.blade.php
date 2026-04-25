@extends('layouts.app', ['module_title' => $title, 'title' => $title])

@php
    $isEdit = $user->exists;
    $formAction = $isEdit
        ? route('dashboard.user-list.update', $user)
        : route('dashboard.user-list.store');

    // Super-admin protection. The controller enforces the same rules
    // server-side; the view just stops admins wasting a click. Two
    // gates: (a) the target IS a super-admin, OR (b) the actor isn't
    // editing their own super-admin row (only super-admins can edit
    // their own profile from this UI; everyone else's super-admin
    // edits go through the console).
    $targetIsSuperAdmin = $isEdit && $user->hasRole('super-admin');
    $isSelfEdit = $isEdit && $user->id === auth()->id();
    $lockSuperAdmin = $targetIsSuperAdmin && !$isSelfEdit;
@endphp

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12 mb-3">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="{{ route('dashboard.user-list') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="ph ph-arrow-left me-1"></i> Back to users
                </a>
                <h4 class="m-0 ms-2">{{ $isEdit ? 'Edit user' : 'Create user' }}</h4>
                @if ($isEdit)
                    <code class="ms-2 text-muted">{{ $user->username }}</code>
                @endif
            </div>
        </div>

        @if ($errors->any())
            <div class="col-12 mb-3">
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @if ($lockSuperAdmin)
            <div class="col-12 mb-3">
                <div class="alert alert-warning d-flex align-items-start gap-2">
                    <i class="ph ph-crown-simple-fill mt-1"></i>
                    <div>
                        <strong>Super-admin account.</strong>
                        This user owns the platform. Profile fields and roles can only be changed
                        from the console. To remove super-admin status:
                        <code style="font-size:12px;">php artisan tinker --execute='App\\Models\\User::where("email", "{{ $user->email }}")->first()->removeRole("super-admin");'</code>
                    </div>
                </div>
            </div>
        @endif

        <div class="col-lg-10 mx-auto">
            <form method="POST" action="{{ $formAction }}">
                @csrf
                {{-- One fieldset wraps everything so $lockSuperAdmin can
                     disable every input + the submit button in a single
                     line. The controller would reject the POST anyway,
                     but blocking it from the browser is a nicer UX. --}}
                <fieldset @disabled($lockSuperAdmin)>
                @if ($isEdit)
                    @method('PATCH')
                @endif

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Profile</h5>
                    </div>
                    <div class="card-body">
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
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="email_verified" value="0">
                                    <input type="checkbox" class="form-check-input" id="email_verified"
                                        name="email_verified" value="1"
                                        @checked(old('email_verified', $isEdit ? (bool) $user->email_verified_at : true))>
                                    <label class="form-check-label" for="email_verified">Mark email as verified</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            {{ $isEdit ? 'Change password' : 'Password' }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="password">
                                    Password
                                    @if (!$isEdit) <span class="text-danger">*</span> @endif
                                </label>
                                <x-password-input
                                    name="password"
                                    autocomplete="new-password"
                                    :required="!$isEdit"
                                    placeholder="{{ $isEdit ? 'Leave blank to keep current' : '' }}"
                                />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="password_confirmation">
                                    Confirm password
                                    @if (!$isEdit) <span class="text-danger">*</span> @endif
                                </label>
                                <x-password-input
                                    name="password_confirmation"
                                    autocomplete="new-password"
                                    :required="!$isEdit"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Roles</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @foreach ($roles as $role)
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="role-{{ $role }}"
                                            name="roles[]" value="{{ $role }}"
                                            @checked(in_array($role, old('roles', $assignedRoles), true))
                                            @disabled($lockSuperAdmin)>
                                        <label class="form-check-label" for="role-{{ $role }}">
                                            {{ ucfirst($role) }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                            @if ($targetIsSuperAdmin)
                                {{-- Super-admin role isn't in $roles (the controller filters
                                     it out of the picker), but the badge still appears so
                                     the admin sees it's set. --}}
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="role-super-admin"
                                            checked disabled>
                                        <label class="form-check-label" for="role-super-admin">
                                            <i class="ph ph-crown-simple-fill text-warning"></i>
                                            Super-admin
                                            <small class="text-muted d-block" style="font-size:11px;">console-managed</small>
                                        </label>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="ph ph-check-circle me-1"></i>
                        {{ $isEdit ? 'Save changes' : 'Create user' }}
                    </button>
                    <a href="{{ route('dashboard.user-list') }}" class="btn btn-ghost">Cancel</a>
                </div>
                </fieldset>
            </form>
        </div>
    </div>
</div>
@endsection
