@extends('layouts.app', ['module_title' => 'Monetization'])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h4 class="card-title mb-1">Monetization Partners</h4>
                        <p class="text-muted mb-0" style="font-size:13px;">
                            {{ $partners->total() }} enrolled earners — VJs, production companies and creators
                        </p>
                    </div>
                    @role('super-admin')
                    <a href="{{ route('admin.monetization.partners.create') }}" class="btn btn-primary">
                        <i class="ph ph-plus me-1"></i> Enroll partner
                    </a>
                    @endrole
                </div>

                @if (session('success'))
                    <div class="alert alert-success mx-4 mt-3 mb-0">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger mx-4 mt-3 mb-0">{{ session('error') }}</div>
                @endif

                <div class="card-body">
                    <form method="GET" action="{{ route('admin.monetization.partners.index') }}" class="row g-2 align-items-end mb-4">
                        <div class="col-md-5">
                            <label class="form-label" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:var(--bs-secondary);">Search</label>
                            <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Name or payout number…">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:var(--bs-secondary);">Type</label>
                            <select name="type" class="form-select">
                                <option value="">All types</option>
                                <option value="vj" @selected($type === 'vj')>VJ</option>
                                <option value="production_company" @selected($type === 'production_company')>Production company</option>
                                <option value="creator" @selected($type === 'creator')>Creator</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:var(--bs-secondary);">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All</option>
                                <option value="enrolled" @selected($status === 'enrolled')>Enrolled</option>
                                <option value="suspended" @selected($status === 'suspended')>Suspended</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-fill">Filter</button>
                            @if ($search || $status || $type)
                                <a href="{{ route('admin.monetization.partners.index') }}" class="btn btn-ghost">Clear</a>
                            @endif
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table custom-table align-middle mb-0">
                            <thead>
                                <tr class="text-uppercase" style="font-size:11px;letter-spacing:.5px;">
                                    <th>Partner</th>
                                    <th>Type</th>
                                    <th>Linked account</th>
                                    <th>Multiplier</th>
                                    <th>Titles</th>
                                    <th>Payout profile</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($partners as $partner)
                                    <tr>
                                        <td>
                                            <strong>{{ $partner->display_name }}</strong>
                                            @if ($partner->vj)
                                                <br><small class="text-muted">VJ: {{ $partner->vj->name }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary-subtle text-secondary-emphasis">
                                                {{ str_replace('_', ' ', ucfirst($partner->type)) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if ($partner->user)
                                                {{ $partner->user->username }}
                                                <br><small class="text-muted">{{ $partner->user->email }}</small>
                                            @else
                                                <span class="text-muted">No login linked</span>
                                            @endif
                                        </td>
                                        <td><code>{{ rtrim(rtrim($partner->multiplier, '0'), '.') }}×</code></td>
                                        <td><span class="badge bg-info-subtle text-info-emphasis">{{ $partner->splits_count }}</span></td>
                                        <td>
                                            @switch($partner->payout_status)
                                                @case('verified') <span class="badge bg-success">Verified</span> @break
                                                @case('pending_review') <span class="badge bg-warning">Pending review</span> @break
                                                @default <span class="badge bg-secondary">None</span>
                                            @endswitch
                                        </td>
                                        <td>
                                            @if ($partner->status === 'enrolled')
                                                <span class="badge bg-success">Enrolled</span>
                                            @else
                                                <span class="badge bg-danger">Suspended</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <a href="{{ route('admin.monetization.partners.show', $partner) }}"
                                                   class="btn btn-sm btn-info-subtle" title="View">
                                                    <i class="ph ph-eye"></i>
                                                </a>
                                                @role('super-admin')
                                                <a href="{{ route('admin.monetization.partners.edit', $partner) }}"
                                                   class="btn btn-sm btn-success-subtle" title="Edit">
                                                    <i class="ph ph-pencil-simple"></i>
                                                </a>
                                                @endrole
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted" style="font-size:14px;">
                                            No partners yet.
                                            @role('super-admin')
                                                <a href="{{ route('admin.monetization.partners.create') }}">Enroll your first partner →</a>
                                            @endrole
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($partners->hasPages())
                        <div class="mt-3 d-flex justify-content-center">
                            {{ $partners->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
