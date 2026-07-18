{{-- Reusable payout-queue table. Expects: $withdrawals (paginator). --}}
<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead>
            <tr>
                <th>Recipient</th>
                <th>Source</th>
                <th>Pay to</th>
                <th class="text-end">Amount</th>
                <th>Status</th>
                <th>Requested</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($withdrawals as $w)
                <tr>
                    <td>{{ $w->ownerLabel() }}</td>
                    <td>
                        @if ($w->owner_type === 'Modules\\Monetization\\app\\Models\\MonetizationPartner')
                            <span class="badge bg-info text-dark">Partner earnings</span>
                        @else
                            <span class="badge bg-secondary">Referral wallet</span>
                        @endif
                    </td>
                    <td>
                        {{ $w->payee_name }}
                        <code class="d-block">{{ $w->payee_msisdn }}</code>
                        @if ($w->payee_network)
                            <span class="text-muted small">{{ strtoupper($w->payee_network) }}</span>
                        @endif
                    </td>
                    <td class="text-end fw-bold">{{ $w->currency }} {{ number_format((float) $w->amount, 0) }}</td>
                    <td>
                        @switch($w->status)
                            @case(\Modules\Wallet\app\Models\WithdrawalRequest::STATUS_PAID)
                                <span class="badge bg-success">Paid</span>
                                <span class="text-muted small d-block">{{ $w->transaction_reference }}</span>
                                @break
                            @case(\Modules\Wallet\app\Models\WithdrawalRequest::STATUS_APPROVED)
                                <span class="badge bg-primary">Approved</span>
                                @break
                            @case(\Modules\Wallet\app\Models\WithdrawalRequest::STATUS_REJECTED)
                                <span class="badge bg-danger">Rejected</span>
                                <span class="text-muted small d-block">{{ $w->rejection_reason }}</span>
                                @break
                            @default
                                <span class="badge bg-warning text-dark">Requested</span>
                        @endswitch
                    </td>
                    <td>{{ $w->requested_at?->format('M j, Y H:i') }}</td>
                    <td class="text-end">
                        @if ($w->status === \Modules\Wallet\app\Models\WithdrawalRequest::STATUS_REQUESTED)
                            <form method="POST" action="{{ route('admin.wallet.withdrawals.approve', $w) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-primary">Approve</button>
                            </form>
                        @endif

                        @if ($w->status === \Modules\Wallet\app\Models\WithdrawalRequest::STATUS_APPROVED)
                            <form method="POST" action="{{ route('admin.wallet.withdrawals.mark-paid', $w) }}"
                                  class="d-inline-flex gap-1 align-items-center">
                                @csrf
                                <input type="text" name="transaction_reference" class="form-control form-control-sm"
                                       placeholder="Transaction ref" required style="max-width: 160px;">
                                <button type="submit" class="btn btn-sm btn-success">Mark paid</button>
                            </form>
                        @endif

                        @if ($w->isOpen())
                            <form method="POST" action="{{ route('admin.wallet.withdrawals.reject', $w) }}"
                                  class="d-inline-flex gap-1 align-items-center mt-1">
                                @csrf
                                <input type="text" name="rejection_reason" class="form-control form-control-sm"
                                       placeholder="Reason" required style="max-width: 160px;">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Reject</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">No withdrawal requests.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
