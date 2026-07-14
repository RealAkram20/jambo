@extends('monetization::layouts.partner')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4 class="card-title mb-1">Wallet</h4>
            <p class="text-muted mb-0" style="font-size:13px;">
                Every credit and hold, oldest to newest — this ledger is append-only and never edited.
            </p>
        </div>
        <div class="text-end">
            <div class="text-muted" style="font-size:12px;">Available balance</div>
            <div class="fw-bold" style="font-size:24px;">UGX {{ number_format((float) $balance, 0) }}</div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table custom-table align-middle mb-0">
                <thead>
                    <tr class="text-uppercase" style="font-size:11px;letter-spacing:.5px;">
                        <th>Date</th>
                        <th>Type</th>
                        <th>Note</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Balance after</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($entries as $entry)
                        <tr>
                            <td>{{ $entry->created_at->format('d M Y H:i') }}</td>
                            <td>
                                @switch($entry->type)
                                    @case('statement_credit') <span class="badge bg-success">Earnings</span> @break
                                    @case('withdrawal_hold') <span class="badge bg-warning">Withdrawal hold</span> @break
                                    @case('hold_release') <span class="badge bg-info-subtle text-info-emphasis">Hold released</span> @break
                                    @default <span class="badge bg-secondary">Adjustment</span>
                                @endswitch
                            </td>
                            <td class="text-muted" style="font-size:13px;">{{ $entry->memo }}</td>
                            <td class="text-end {{ (float) $entry->amount < 0 ? 'text-danger' : 'text-success' }}">
                                {{ (float) $entry->amount < 0 ? '−' : '+' }} UGX {{ number_format(abs((float) $entry->amount), 0) }}
                            </td>
                            <td class="text-end">UGX {{ number_format((float) $entry->balance_after, 0) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-5 text-muted">Nothing here yet — earnings appear when a month closes.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($entries->hasPages())
            <div class="mt-3 d-flex justify-content-center">{{ $entries->links() }}</div>
        @endif
    </div>
</div>
@endsection
