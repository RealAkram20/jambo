@extends('layouts.app', ['module_title' => 'Wallets'])

@php
    $kindBadge = [
        'staff'   => ['bg-primary-subtle text-primary-emphasis', 'Staff'],
        'partner' => ['bg-success-subtle text-success-emphasis', 'Partner'],
        'member'  => ['bg-secondary-subtle text-secondary-emphasis', 'Member'],
    ];
@endphp

@section('content')
<div class="container-fluid">
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="card mb-0"><div class="card-body text-center py-3">
                <h4 class="mb-0">{{ number_format($totals['wallets']) }}</h4>
                <span class="text-muted small">Wallets</span>
            </div></div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card mb-0"><div class="card-body text-center py-3">
                <h4 class="mb-0">{{ number_format($totals['balance']) }} <small class="text-muted fs-6">{{ $currency }}</small></h4>
                <span class="text-muted small">Held balance (platform liability)</span>
            </div></div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card mb-0"><div class="card-body text-center py-3">
                <h4 class="mb-0 text-success">{{ number_format($totals['in']) }}</h4>
                <span class="text-muted small">Lifetime credited</span>
            </div></div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card mb-0"><div class="card-body text-center py-3">
                <h4 class="mb-0 text-danger">{{ number_format($totals['out']) }}</h4>
                <span class="text-muted small">Lifetime debited</span>
            </div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h4 class="card-title mb-1">All wallets</h4>
                <p class="text-muted mb-0" style="font-size:13px;">
                    Staff performance pay, member referral rewards, and partner revenue share — one ledger, browsed here read-only.
                </p>
            </div>
            <form method="GET" class="d-flex flex-wrap gap-2">
                <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm"
                       placeholder="Name or email…" style="width:200px;">
                <select name="kind" class="form-select form-select-sm" onchange="this.form.submit()" style="width:auto;">
                    <option value="">All kinds</option>
                    <option value="staff" @selected($filters['kind'] === 'staff')>Staff</option>
                    <option value="partner" @selected($filters['kind'] === 'partner')>Partners</option>
                    <option value="member" @selected($filters['kind'] === 'member')>Members (referral)</option>
                </select>
                <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()" style="width:auto;">
                    <option value="balance" @selected($filters['sort'] === 'balance')>Highest balance</option>
                    <option value="total_in" @selected($filters['sort'] === 'total_in')>Most earned</option>
                    <option value="last_activity" @selected($filters['sort'] === 'last_activity')>Recent activity</option>
                </select>
                <button class="btn btn-sm btn-primary">Filter</button>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table custom-table align-middle mb-0">
                    <thead>
                        <tr class="text-uppercase" style="font-size:11px;letter-spacing:.5px;">
                            <th>Owner</th>
                            <th>Kind</th>
                            <th class="text-end">Balance</th>
                            <th class="text-end">Lifetime in</th>
                            <th class="text-end">Lifetime out</th>
                            <th class="text-end">Paid out</th>
                            <th>Last activity</th>
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($wallets as $w)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $w->name }}</div>
                                    <div class="text-muted" style="font-size:11px;">{{ $w->sub }}</div>
                                </td>
                                <td>
                                    @php [$cls, $label] = $kindBadge[$w->kind] ?? ['bg-secondary', ucfirst($w->kind)]; @endphp
                                    <span class="badge {{ $cls }}">{{ $label }}</span>
                                </td>
                                <td class="text-end"><strong>{{ number_format($w->balance) }}</strong> <span class="text-muted" style="font-size:11px;">{{ $currency }}</span></td>
                                <td class="text-end text-success">{{ number_format($w->total_in) }}</td>
                                <td class="text-end text-danger">{{ $w->total_out > 0 ? number_format($w->total_out) : '—' }}</td>
                                <td class="text-end">{{ $w->paid_out > 0 ? number_format($w->paid_out) : '—' }}</td>
                                <td style="font-size:12px;color:var(--bs-secondary);">
                                    {{ $w->last_activity ? \Illuminate\Support\Carbon::parse($w->last_activity)->diffForHumans() : '—' }}
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.wallet.wallets.show', [$w->owner_slug, $w->owner_id]) }}"
                                       class="btn btn-sm btn-outline-primary" title="Open statement">
                                        <i class="ph ph-receipt"></i> Statement
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">No wallets match these filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($wallets->hasPages())
                <div class="d-flex justify-content-center pt-3">{{ $wallets->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
