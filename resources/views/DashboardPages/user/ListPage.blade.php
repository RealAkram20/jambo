@extends('layouts.app', ['module_title' => 'Users', 'title' => 'Users', 'active' => 'user-list-mini', 'isSweetalert' => true])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h4 class="card-title mb-1">Users</h4>
                        <p class="text-muted mb-0" style="font-size:13px;">
                            {{ $totalCount }} total · {{ $adminCount }} admin{{ $adminCount === 1 ? '' : 's' }}
                        </p>
                    </div>
                    <a href="{{ route('dashboard.user-list.create') }}" class="btn btn-primary">
                        <i class="ph ph-plus me-1"></i> Create user
                    </a>
                </div>

                @if (session('success'))
                    <div class="alert alert-success mx-4 mt-3 mb-0">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger mx-4 mt-3 mb-0">{{ session('error') }}</div>
                @endif

                <div class="card-body">
                    <form method="GET" action="{{ route('dashboard.user-list') }}" class="row g-2 align-items-end mb-4">
                        <div class="col-md-6">
                            <label class="form-label" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:var(--bs-secondary);">Search</label>
                            <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control"
                                placeholder="Name, username, or email…">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:var(--bs-secondary);">Role</label>
                            <select name="role" class="form-select">
                                <option value="">Any</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role }}" @selected($filters['role'] === $role)>{{ ucfirst($role) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:var(--bs-secondary);">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All</option>
                                <option value="active" @selected($filters['status'] === 'active')>Active</option>
                                <option value="deactivated" @selected($filters['status'] === 'deactivated')>Deactivated</option>
                            </select>
                        </div>
                        <div class="col-md-1 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-fill">Filter</button>
                        </div>
                        @if (array_filter($filters))
                            <div class="col-12">
                                <a href="{{ route('dashboard.user-list') }}" class="btn btn-ghost btn-sm">
                                    <i class="ph ph-x me-1"></i> Clear filters
                                </a>
                            </div>
                        @endif
                    </form>

                    <div class="table-responsive">
                        <table class="table custom-table align-middle mb-0">
                            <thead>
                                <tr class="text-uppercase" style="font-size:11px;letter-spacing:.5px;">
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Roles</th>
                                    <th>Verified</th>
                                    <th>Joined</th>
                                    <th class="text-end" style="width:120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($users as $u)
                                    @php
                                        $fullName = trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''));
                                        $isDeactivated = $u->deactivated_at !== null;
                                    @endphp
                                    <tr class="{{ $isDeactivated ? 'opacity-50' : '' }}">
                                        <td>
                                            <div class="fw-semibold">{{ $fullName ?: $u->username }}</div>
                                            <div class="text-muted" style="font-size:11px;">
                                                <i class="ph ph-at"></i>{{ $u->username }}
                                            </div>
                                        </td>
                                        <td style="font-size:12px;">{{ $u->email }}</td>
                                        <td style="font-size:12px;">{{ $u->phone ?: '—' }}</td>
                                        <td>
                                            @forelse ($u->roles as $role)
                                                <span class="badge @class([
                                                    'bg-warning text-dark' => $role->name === 'super-admin',
                                                    'bg-primary' => $role->name === 'admin',
                                                    'bg-secondary' => !in_array($role->name, ['admin', 'super-admin']),
                                                ])">
                                                    @if ($role->name === 'super-admin')
                                                        <i class="ph ph-crown-simple-fill"></i>
                                                    @endif
                                                    {{ $role->name }}
                                                </span>
                                            @empty
                                                <span class="text-muted" style="font-size:11px;">none</span>
                                            @endforelse
                                        </td>
                                        <td>
                                            @if ($u->email_verified_at)
                                                <span class="badge bg-success-subtle text-success-emphasis">
                                                    <i class="ph ph-check-circle"></i> Verified
                                                </span>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning-emphasis">
                                                    <i class="ph ph-warning-circle"></i> Pending
                                                </span>
                                            @endif
                                            @if ($isDeactivated)
                                                <span class="badge bg-danger-subtle text-danger-emphasis ms-1">Deactivated</span>
                                            @endif
                                        </td>
                                        <td style="font-size:12px;color:var(--bs-secondary);">
                                            {{ $u->created_at?->format('d M Y') }}
                                        </td>
                                        <td class="text-end">
                                            @php
                                                // Super-admins are protected by the controller — surface the
                                                // same fact in the UI so admins don't waste a click. The edit
                                                // link is still allowed (the form lets the super-admin update
                                                // their own profile from this same screen) but delete is hard
                                                // off for everyone, themselves included.
                                                $isSuperAdmin = $u->hasRole('super-admin');
                                            @endphp
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('dashboard.user-list.edit', $u) }}"
                                                    class="btn btn-sm btn-outline-primary" title="Edit">
                                                    <i class="ph ph-pencil-simple"></i>
                                                </a>
                                                <form method="POST" action="{{ route('dashboard.user-list.destroy', $u) }}" class="m-0 d-inline"
                                                    onsubmit="return confirm('Delete {{ $u->username }}? This cannot be undone.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                                        title="{{ $isSuperAdmin ? 'Super-admins can only be removed from the console' : 'Delete' }}"
                                                        @disabled($u->id === auth()->id() || $isSuperAdmin)>
                                                        <i class="ph ph-trash-simple"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted" style="font-size:13px;">
                                            No users match these filters.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($users->hasPages())
                        <div class="d-flex justify-content-center pt-3">
                            {{ $users->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
