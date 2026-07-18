@extends('monetization::layouts.partner')

@section('content')
<div class="row g-3">
    <div class="col-12 col-lg-5">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-1">Request a withdrawal</h5>
                <p class="text-muted mb-0" style="font-size:13px;">
                    Paid manually to your verified mobile money number. Minimum UGX {{ number_format((float) $minWithdrawal, 0) }}.
                </p>
            </div>
            <div class="card-body">
                <div class="mb-3" style="font-size:14px;">
                    <span class="text-muted">Available:</span>
                    <strong>UGX {{ number_format((float) $balance, 0) }}</strong>
                </div>

                @if (!$partner->payoutVerified())
                    <div class="alert alert-warning" style="font-size:14px;">
                        Your payout details must be verified before you can withdraw —
                        <a href="{{ route('partner.payout-profile') }}">submit them here</a>.
                    </div>
                @elseif ($partner->payoutLocked())
                    <div class="alert alert-warning" style="font-size:14px;">
                        Withdrawals are paused until {{ $partner->payout_locked_until->format('d M Y H:i') }}
                        because your payout details changed recently.
                    </div>
                @elseif ($hasOpen)
                    <div class="alert alert-info" style="font-size:14px;">
                        You already have a withdrawal in progress — it must be paid or rejected before requesting another.
                    </div>
                @else
                    <form method="POST" action="{{ route('partner.withdrawals.store') }}"
                          onsubmit="return confirm('Request this withdrawal to {{ $partner->payout_msisdn }} ({{ strtoupper($partner->payout_network) }})?');">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Amount (UGX)</label>
                            <input type="number" name="amount" class="form-control" min="{{ (int) $minWithdrawal }}"
                                   max="{{ (int) (float) $balance }}" step="1" required>
                        </div>
                        <div class="mb-3 text-muted" style="font-size:13px;">
                            Pays to: <strong>{{ $partner->payout_msisdn }}</strong>
                            ({{ strtoupper($partner->payout_network) }} · {{ $partner->payout_name }})
                        </div>
                        <button class="btn btn-primary w-100"><i class="ph ph-hand-coins me-1"></i> Request withdrawal</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-7">
        <div class="card h-100">
            <div class="card-header"><h5 class="card-title mb-0">Withdrawal history</h5></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table custom-table align-middle mb-0">
                        <thead>
                            <tr class="text-uppercase" style="font-size:11px;letter-spacing:.5px;">
                                <th>Requested</th><th>Amount</th><th>To</th><th>Status</th><th>Reference</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($withdrawals as $withdrawal)
                                <tr>
                                    <td>{{ $withdrawal->requested_at->format('d M Y') }}</td>
                                    <td class="fw-bold">UGX {{ number_format((float) $withdrawal->amount, 0) }}</td>
                                    <td style="font-size:13px;">{{ $withdrawal->payee_msisdn }}</td>
                                    <td>
                                        @switch($withdrawal->status)
                                            @case('requested') <span class="badge bg-warning">Pending review</span> @break
                                            @case('approved') <span class="badge bg-info-subtle text-info-emphasis">Approved — sending</span> @break
                                            @case('paid') <span class="badge bg-success">Paid</span> @break
                                            @case('rejected')
                                                <span class="badge bg-danger">Rejected</span>
                                                @if ($withdrawal->rejection_reason)
                                                    <small class="d-block text-muted">{{ $withdrawal->rejection_reason }}</small>
                                                @endif
                                                @break
                                        @endswitch
                                    </td>
                                    <td style="font-size:13px;"><code>{{ $withdrawal->transaction_reference ?? '—' }}</code></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center py-5 text-muted">No withdrawals yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($withdrawals->hasPages())
                    <div class="mt-3 d-flex justify-content-center">{{ $withdrawals->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
