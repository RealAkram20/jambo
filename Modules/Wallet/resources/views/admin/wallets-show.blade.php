@extends('layouts.app', ['module_title' => 'Wallets'])

@php
    use Modules\Wallet\app\Models\LedgerEntry;

    // Ledger entry type → [label, badge class]. Credits green, debits
    // red, adjustments amber — an auditor can scan the colour column.
    $typeMeta = [
        LedgerEntry::TYPE_STATEMENT_CREDIT   => ['Statement credit', 'bg-success-subtle text-success-emphasis'],
        LedgerEntry::TYPE_REFERRAL_REWARD    => ['Referral reward', 'bg-success-subtle text-success-emphasis'],
        LedgerEntry::TYPE_PERFORMANCE_CREDIT => ['Performance credit', 'bg-success-subtle text-success-emphasis'],
        LedgerEntry::TYPE_REFUND             => ['Refund', 'bg-success-subtle text-success-emphasis'],
        LedgerEntry::TYPE_HOLD_RELEASE       => ['Hold release', 'bg-info-subtle text-info-emphasis'],
        LedgerEntry::TYPE_SPEND              => ['Spend', 'bg-danger-subtle text-danger-emphasis'],
        LedgerEntry::TYPE_WITHDRAWAL_HOLD    => ['Withdrawal hold', 'bg-danger-subtle text-danger-emphasis'],
        LedgerEntry::TYPE_ADJUSTMENT         => ['Adjustment', 'bg-warning-subtle text-warning-emphasis'],
    ];
    $kindBadge = [
        'staff'   => ['bg-primary-subtle text-primary-emphasis', 'Staff'],
        'partner' => ['bg-success-subtle text-success-emphasis', 'Partner'],
        'member'  => ['bg-secondary-subtle text-secondary-emphasis', 'Member'],
    ];
@endphp

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center gap-2 mb-3">
        <a href="{{ route('admin.wallet.wallets.index') }}" class="btn btn-sm btn-ghost">
            <i class="ph ph-arrow-left me-1"></i> All wallets
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <div class="d-flex align-items-center gap-2">
                    <h4 class="mb-0">{{ $title }}</h4>
                    @php [$cls, $label] = $kindBadge[$kind] ?? ['bg-secondary', ucfirst($kind)]; @endphp
                    <span class="badge {{ $cls }}">{{ $label }}</span>
                </div>
                <span class="text-muted" style="font-size:13px;">{{ $subtitle }}</span>
            </div>
            <div class="text-end">
                <div class="text-muted small text-uppercase" style="letter-spacing:.5px;font-size:11px;">Current balance</div>
                <h3 class="mb-0">{{ number_format((float) $balance) }} <small class="text-muted fs-6">{{ $currency }}</small></h3>
            </div>
        </div>
    </div>

    {{-- Lifetime totals per entry type --}}
    <div class="row g-3 mb-4">
        @foreach ($typeMeta as $type => [$label, $badgeCls])
            @if (isset($byType[$type]))
                <div class="col-6 col-md-4 col-xl-3">
                    <div class="card mb-0">
                        <div class="card-body py-3">
                            <span class="badge {{ $badgeCls }} mb-1">{{ $label }}</span>
                            <h5 class="mb-0">{{ number_format((float) $byType[$type]->total) }} <small class="text-muted" style="font-size:11px;">{{ $currency }}</small></h5>
                            <span class="text-muted" style="font-size:11px;">{{ number_format($byType[$type]->n) }} {{ \Illuminate\Support\Str::plural('entry', $byType[$type]->n) }}</span>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
        @if ($totalPaidOut > 0)
            <div class="col-6 col-md-4 col-xl-3">
                <div class="card mb-0">
                    <div class="card-body py-3">
                        <span class="badge bg-dark-subtle text-body mb-1">Cash paid out</span>
                        <h5 class="mb-0">{{ number_format($totalPaidOut) }} <small class="text-muted" style="font-size:11px;">{{ $currency }}</small></h5>
                        <span class="text-muted" style="font-size:11px;">settled withdrawals</span>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h4 class="card-title mb-0">Statement</h4>
            <form method="GET" class="d-flex flex-wrap gap-2">
                <select name="type" class="form-select form-select-sm" onchange="this.form.submit()" style="width:auto;">
                    <option value="">All types</option>
                    @foreach ($typeMeta as $type => [$label, $badgeCls])
                        <option value="{{ $type }}" @selected($filters['type'] === $type)>{{ $label }}</option>
                    @endforeach
                </select>
                <input type="date" name="from" value="{{ $filters['from'] }}" class="form-control form-control-sm" style="width:auto;">
                <input type="date" name="to" value="{{ $filters['to'] }}" class="form-control form-control-sm" style="width:auto;">
                <button class="btn btn-sm btn-primary">Filter</button>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table custom-table align-middle mb-0">
                    <thead>
                        <tr class="text-uppercase" style="font-size:11px;letter-spacing:.5px;">
                            <th>Date</th>
                            <th>Type</th>
                            <th>Memo</th>
                            <th>Reference</th>
                            <th>By</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Balance after</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($entries as $e)
                            @php
                                [$label, $badgeCls] = $typeMeta[$e->type] ?? [ucfirst(str_replace('_', ' ', $e->type)), 'bg-secondary'];
                                $isCredit = (float) $e->amount >= 0;
                            @endphp
                            <tr>
                                <td style="font-size:12px;white-space:nowrap;">
                                    {{ $e->created_at->format('d M Y H:i') }}
                                    <div class="text-muted" style="font-size:10px;">#{{ $e->id }}</div>
                                </td>
                                <td><span class="badge {{ $badgeCls }}">{{ $label }}</span></td>
                                <td style="font-size:12px;max-width:280px;">{{ $e->memo ?: '—' }}</td>
                                <td style="font-size:11px;">
                                    @if ($e->reference_type)
                                        <code>{{ class_basename($e->reference_type) }}#{{ $e->reference_id }}</code>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td style="font-size:12px;">
                                    {{ $e->createdBy?->username ?? 'system' }}
                                </td>
                                <td class="text-end {{ $isCredit ? 'text-success' : 'text-danger' }}">
                                    <strong>{{ $isCredit ? '+' : '' }}{{ number_format((float) $e->amount) }}</strong>
                                </td>
                                <td class="text-end">{{ number_format((float) $e->balance_after) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">No ledger entries match.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($entries->hasPages())
                <div class="d-flex justify-content-center pt-3">{{ $entries->links() }}</div>
            @endif
        </div>
    </div>

    @if ($withdrawals->isNotEmpty())
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Recent withdrawal requests</h4></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table custom-table align-middle mb-0">
                        <thead>
                            <tr class="text-uppercase" style="font-size:11px;letter-spacing:.5px;">
                                <th>Requested</th>
                                <th class="text-end">Amount</th>
                                <th>Payee</th>
                                <th>Status</th>
                                <th>Settled</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($withdrawals as $wd)
                                <tr>
                                    <td style="font-size:12px;">{{ $wd->requested_at?->format('d M Y H:i') }}</td>
                                    <td class="text-end"><strong>{{ number_format((float) $wd->amount) }}</strong> <span class="text-muted" style="font-size:11px;">{{ $wd->currency }}</span></td>
                                    <td style="font-size:12px;">{{ $wd->payee_name }} <span class="text-muted">{{ $wd->payee_msisdn }}</span></td>
                                    <td>
                                        <span class="badge @class([
                                            'bg-success' => $wd->status === 'paid',
                                            'bg-info' => $wd->status === 'approved',
                                            'bg-warning' => $wd->status === 'requested',
                                            'bg-danger' => $wd->status === 'rejected',
                                            'bg-secondary' => !in_array($wd->status, ['paid', 'approved', 'requested', 'rejected']),
                                        ])">{{ ucfirst($wd->status) }}</span>
                                    </td>
                                    <td style="font-size:12px;">{{ $wd->paid_at?->format('d M Y H:i') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
