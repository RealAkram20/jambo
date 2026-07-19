@extends('layouts.app', ['module_title' => 'My Wallet'])

@section('content')
<div class="container-fluid">
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="card mb-0">
                <div class="card-body text-center py-3">
                    <h4 class="mb-0">{{ $currency }} {{ number_format((float) $balance, 0) }}</h4>
                    <span class="text-muted small">Available balance</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card mb-0">
                <div class="card-body text-center py-3">
                    <h4 class="mb-0">{{ $currency }} {{ number_format((float) $earnedThisMonth, 0) }}</h4>
                    <span class="text-muted small">Earned this month</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card mb-0">
                <div class="card-body text-center py-3">
                    @if ($lastPayout)
                        <h4 class="mb-0">{{ $currency }} {{ number_format((float) $earnedSinceLastPayout, 0) }}</h4>
                        <span class="text-muted small">Earned since last payout
                            ({{ $currency }} {{ number_format((float) $lastPayout->amount, 0) }}
                            · {{ ($lastPayout->paid_at ?? $lastPayout->requested_at)?->format('M j, Y') }})</span>
                    @else
                        <h4 class="mb-0">—</h4>
                        <span class="text-muted small">No payouts yet</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card mb-0">
                <div class="card-body text-center py-3">
                    <h4 class="mb-0">{{ $currency }} {{ number_format((float) bcadd((string) $performanceEarned, (string) $referralEarned, 2), 0) }}</h4>
                    <span class="text-muted small">Total earned</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Monthly earnings — credit entries only, so withdrawing never
         changes what a month shows as earned. --}}
    @if (count($monthlyEarnings))
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="card-title mb-0">Monthly earnings</h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle small mb-0">
                        <thead class="text-muted">
                            <tr>
                                <th>Month</th>
                                <th class="text-end">Uploads</th>
                                <th class="text-end">Referrals</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($monthlyEarnings as $ym => $m)
                                @php
                                    $perf = $m['performance'] ?? '0';
                                    $ref = $m['referral'] ?? '0';
                                @endphp
                                <tr>
                                    <td>{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $ym)->format('F Y') }}</td>
                                    <td class="text-end">{{ $currency }} {{ number_format((float) $perf, 0) }}</td>
                                    <td class="text-end">{{ $currency }} {{ number_format((float) $ref, 0) }}</td>
                                    <td class="text-end fw-semibold">{{ $currency }} {{ number_format((float) bcadd($perf, $ref, 2), 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- Withdraw --}}
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h4 class="card-title mb-0">Withdraw</h4>
            <span class="small text-muted">Min {{ $currency }} {{ number_format((float) $minWithdrawal, 0) }}</span>
        </div>
        <div class="card-body">
            @if ($hasOpenWithdrawal)
                <span class="badge bg-warning text-dark">Withdrawal in progress</span>
            @elseif (bccomp((string) $balance, (string) $minWithdrawal, 2) >= 0)
                <form method="POST" action="{{ route('referrals.wallet.withdraw') }}" class="row g-2 align-items-end" style="max-width: 900px;">
                    @csrf
                    <div class="col-12 col-md-3">
                        <label class="form-label small mb-1" for="jambo-mywallet-amount">Amount</label>
                        <input type="number" step="1" min="1" max="{{ (float) $balance }}" name="amount" id="jambo-mywallet-amount"
                               class="form-control" value="{{ old('amount') }}" required>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label small mb-1" for="jambo-mywallet-name">Recipient name</label>
                        <input type="text" name="payee_name" id="jambo-mywallet-name" maxlength="100"
                               class="form-control" value="{{ old('payee_name') }}" required>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label small mb-1" for="jambo-mywallet-msisdn">Mobile money number</label>
                        <input type="text" name="payee_msisdn" id="jambo-mywallet-msisdn" maxlength="30"
                               class="form-control" placeholder="07XX XXX XXX" value="{{ old('payee_msisdn') }}" required>
                    </div>
                    <div class="col-12 col-md-3">
                        <button type="submit" class="btn btn-primary w-100">Request withdrawal</button>
                    </div>
                    @error('amount')<div class="col-12 small text-danger">{{ $message }}</div>@enderror
                    @error('payee_name')<div class="col-12 small text-danger">{{ $message }}</div>@enderror
                    @error('payee_msisdn')<div class="col-12 small text-danger">{{ $message }}</div>@enderror
                </form>
            @else
                <p class="small text-muted mb-0">Earn at least {{ $currency }} {{ number_format((float) $minWithdrawal, 0) }} to withdraw.</p>
            @endif

            @if ($withdrawals->count())
                <div class="table-responsive mt-3">
                    <table class="table table-borderless align-middle small mb-0">
                        <thead class="text-muted">
                            <tr>
                                <th>Requested</th>
                                <th class="text-end">Amount</th>
                                <th>Status</th>
                                <th>Reference</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($withdrawals as $w)
                                <tr>
                                    <td>{{ $w->requested_at?->format('M j, Y') }}</td>
                                    <td class="text-end">{{ $w->currency }} {{ number_format((float) $w->amount, 0) }}</td>
                                    <td>
                                        @switch($w->status)
                                            @case(\Modules\Wallet\app\Models\WithdrawalRequest::STATUS_PAID)
                                                <span class="badge bg-success">Paid</span>
                                                @break
                                            @case(\Modules\Wallet\app\Models\WithdrawalRequest::STATUS_APPROVED)
                                                <span class="badge bg-primary">Approved</span>
                                                @break
                                            @case(\Modules\Wallet\app\Models\WithdrawalRequest::STATUS_REJECTED)
                                                <span class="badge bg-danger" @if($w->rejection_reason) title="{{ $w->rejection_reason }}" @endif>Rejected</span>
                                                @break
                                            @default
                                                <span class="badge bg-warning text-dark">Requested</span>
                                        @endswitch
                                    </td>
                                    <td class="text-muted">{{ $w->transaction_reference ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Activity --}}
    <div class="card">
        <div class="card-header">
            <h4 class="card-title mb-0">Activity</h4>
        </div>
        <div class="card-body p-0">
            @if ($entries->count())
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="text-muted">
                            <tr>
                                <th>Date</th>
                                <th>Detail</th>
                                <th class="text-end">Amount</th>
                                <th class="text-end">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($entries as $entry)
                                <tr>
                                    <td>{{ $entry->created_at?->format('M j, Y H:i') }}</td>
                                    <td>
                                        @switch($entry->type)
                                            @case(\Modules\Wallet\app\Models\LedgerEntry::TYPE_PERFORMANCE_CREDIT)
                                                <span class="badge bg-primary">Performance</span>
                                                @break
                                            @case(\Modules\Wallet\app\Models\LedgerEntry::TYPE_REFERRAL_REWARD)
                                                <span class="badge bg-success">Referral reward</span>
                                                @break
                                            @case(\Modules\Wallet\app\Models\LedgerEntry::TYPE_REFUND)
                                                <span class="badge bg-info text-dark">Refund</span>
                                                @break
                                            @case(\Modules\Wallet\app\Models\LedgerEntry::TYPE_SPEND)
                                                <span class="badge bg-secondary">Subscription</span>
                                                @break
                                            @case(\Modules\Wallet\app\Models\LedgerEntry::TYPE_WITHDRAWAL_HOLD)
                                                <span class="badge bg-warning text-dark">Withdrawal</span>
                                                @break
                                            @case(\Modules\Wallet\app\Models\LedgerEntry::TYPE_HOLD_RELEASE)
                                                <span class="badge bg-primary">Returned</span>
                                                @break
                                            @default
                                                <span class="badge bg-light text-dark">{{ ucfirst(str_replace('_', ' ', $entry->type)) }}</span>
                                        @endswitch
                                        @if ($entry->memo)
                                            <span class="text-muted d-block small">{{ $entry->memo }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end {{ bccomp((string) $entry->amount, '0', 2) >= 0 ? 'text-success' : '' }}">
                                        {{ bccomp((string) $entry->amount, '0', 2) >= 0 ? '+' : '' }}{{ number_format((float) $entry->amount, 0) }}
                                    </td>
                                    <td class="text-end text-muted">{{ number_format((float) $entry->balance_after, 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center text-muted py-4">No wallet activity yet.</div>
            @endif
        </div>
        @if ($entries->hasPages())
            <div class="card-footer">{{ $entries->links() }}</div>
        @endif
    </div>
</div>
@endsection
