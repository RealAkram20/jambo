@extends('layouts.app', ['module_title' => 'Users', 'title' => $title])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-6 mx-auto mt-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ph ph-crown-simple-fill text-warning me-1"></i> Super admin
                    </h5>
                </div>
                <div class="card-body">
                    @php
                        $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                    @endphp
                    <p class="mb-1 fw-semibold">
                        {{ $fullName ?: $user->username }}
                        <span class="text-muted fw-normal">&commat;{{ $user->username }}</span>
                    </p>
                    <p class="text-muted mb-4" style="font-size:13px;">{{ $user->email }}</p>

                    @if ($isSelf && $isSuperAdmin)
                        <p class="mb-4">You can't remove your own super-admin role. Ask another super admin.</p>
                        <a href="{{ route('dashboard.user-list.edit', $user) }}" class="btn btn-ghost">Back</a>
                    @elseif ($isSuperAdmin)
                        <p class="mb-4">Removing super admin keeps their admin role and all other roles.</p>
                        <form method="POST" action="{{ route('backend.users.super-admin.revoke', $user) }}" class="d-flex gap-2">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-warning">
                                <i class="ph ph-crown-simple me-1"></i> Remove super admin
                            </button>
                            <a href="{{ route('dashboard.user-list.edit', $user) }}" class="btn btn-ghost">Cancel</a>
                        </form>
                    @else
                        <p class="mb-4">Super admins have full, unrestricted access to the entire platform, including Access Control and this page.</p>
                        <form method="POST" action="{{ route('backend.users.super-admin.grant', $user) }}" class="d-flex gap-2">
                            @csrf
                            <button type="submit" class="btn btn-warning">
                                <i class="ph ph-crown-simple-fill me-1"></i> Make super admin
                            </button>
                            <a href="{{ route('dashboard.user-list.edit', $user) }}" class="btn btn-ghost">Cancel</a>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
