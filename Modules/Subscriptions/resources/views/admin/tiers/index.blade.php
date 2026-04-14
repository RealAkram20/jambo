@extends('layouts.app', ['module_title' => 'Subscription Tiers'])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h4 class="card-title mb-1">Subscription Tiers</h4>
                        <p class="text-muted mb-0" style="font-size:13px;">
                            {{ $totalCount }} total · drives the public /pricing-page and payment activation
                        </p>
                    </div>
                    <a href="{{ route('admin.subscription-tiers.create') }}" class="btn btn-primary">
                        <i class="ph ph-plus me-1"></i> Add tier
                    </a>
                </div>

                @if (session('success'))
                    <div class="alert alert-success mx-4 mt-3 mb-0">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger mx-4 mt-3 mb-0">{{ session('error') }}</div>
                @endif

                <div class="card-body">
                    <form method="GET" action="{{ route('admin.subscription-tiers.index') }}" class="row g-2 align-items-end mb-4">
                        <div class="col-md-6">
                            <label class="form-label" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:var(--bs-secondary);">
                                Search
                            </label>
                            <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Name, slug, description…">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:var(--bs-secondary);">
                                Billing Period
                            </label>
                            <select name="period" class="form-select">
                                <option value="">All periods</option>
                                <option value="daily"   @selected($period === 'daily')>Daily</option>
                                <option value="weekly"  @selected($period === 'weekly')>Weekly</option>
                                <option value="monthly" @selected($period === 'monthly')>Monthly</option>
                                <option value="yearly"  @selected($period === 'yearly')>Yearly</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-fill">Filter</button>
                            @if ($search || $period)
                                <a href="{{ route('admin.subscription-tiers.index') }}" class="btn btn-ghost">Clear</a>
                            @endif
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table custom-table align-middle mb-0">
                            <thead>
                                <tr class="text-uppercase" style="font-size:11px;letter-spacing:.5px;">
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Slug</th>
                                    <th>Price</th>
                                    <th>Period</th>
                                    <th>Access</th>
                                    <th>Subscribers</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($tiers as $tier)
                                    <tr>
                                        <td>{{ $tier->sort_order }}</td>
                                        <td>
                                            <strong>{{ $tier->name }}</strong>
                                            @if ($tier->description)
                                                <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($tier->description, 60) }}</small>
                                            @endif
                                        </td>
                                        <td><code>{{ $tier->slug }}</code></td>
                                        <td>
                                            {{ $tier->currency }} {{ number_format((float) $tier->price, 2) }}
                                            <br><small class="text-muted">{{ $tier->periodLabel() }}</small>
                                        </td>
                                        <td><span class="badge bg-secondary-subtle text-secondary-emphasis">{{ ucfirst($tier->billing_period) }}</span></td>
                                        <td>
                                            @switch($tier->access_level)
                                                @case(0) <span class="badge bg-secondary">Free</span> @break
                                                @case(1) <span class="badge bg-info-subtle text-info-emphasis">Basic</span> @break
                                                @case(2) <span class="badge bg-primary">Premium</span> @break
                                                @case(3) <span class="badge bg-warning">Ultra</span> @break
                                            @endswitch
                                        </td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info-emphasis">{{ $tier->user_subscriptions_count }}</span>
                                        </td>
                                        <td>
                                            @if ($tier->is_active)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-warning">Inactive</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <a href="{{ route('admin.subscription-tiers.edit', $tier) }}"
                                                    class="btn btn-sm btn-success-subtle" title="Edit">
                                                    <i class="ph ph-pencil-simple"></i>
                                                </a>
                                                <form method="POST" action="{{ route('admin.subscription-tiers.destroy', $tier) }}"
                                                    class="d-inline"
                                                    onsubmit="return confirm('Delete tier &quot;{{ $tier->name }}&quot;?');">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-sm btn-danger-subtle" title="Delete">
                                                        <i class="ph ph-trash-simple"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-5 text-muted" style="font-size:14px;">
                                            No tiers yet.
                                            <a href="{{ route('admin.subscription-tiers.create') }}">Add your first tier →</a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($tiers->hasPages())
                        <div class="mt-3 d-flex justify-content-center">
                            {{ $tiers->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
