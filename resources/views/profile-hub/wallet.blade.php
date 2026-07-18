@extends('profile-hub._layout', ['pageTitle' => 'Wallet', 'user' => $user, 'activeTab' => $activeTab])

@section('hub-content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <div class="jambo-hub-card mb-0 text-center py-4">
                <i class="ph-fill ph-wallet" style="font-size: 2rem; color: var(--bs-primary);"></i>
                <div class="fs-3 fw-bold mt-1">{{ $currency }} {{ number_format((float) $balance, 0) }}</div>
                <div class="small text-muted">Balance</div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="jambo-hub-card mb-0 py-4 d-flex flex-column justify-content-center align-items-center gap-2">
                <a class="btn btn-outline-primary" href="{{ route('frontend.pricing-page') }}">
                    <i class="ph ph-crown me-1"></i>Spend on a subscription
                </a>
                <a class="btn btn-outline-primary" href="{{ route('profile.refer', ['username' => $user->username]) }}">
                    <i class="ph ph-gift me-1"></i>Earn more — Refer & Earn
                </a>
            </div>
        </div>
    </div>

    {{-- Withdraw --}}
    <div class="jambo-hub-card">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h5 class="mb-0">Withdraw</h5>
            <span class="small text-muted">Min {{ $currency }} {{ number_format((float) $minWithdrawal, 0) }}</span>
        </div>

        @if ($hasOpenWithdrawal)
            <span class="badge bg-warning text-dark">Withdrawal in progress</span>
        @elseif (bccomp((string) $balance, (string) $minWithdrawal, 2) >= 0)
            <form method="POST" action="{{ route('referrals.wallet.withdraw') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-12 col-md-3">
                    <label class="form-label small mb-1" for="jambo-wallet-wd-amount">Amount</label>
                    <input type="number" step="1" min="1" max="{{ (float) $balance }}" name="amount" id="jambo-wallet-wd-amount"
                           class="form-control" value="{{ old('amount') }}" required>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small mb-1" for="jambo-wallet-wd-name">Recipient name</label>
                    <input type="text" name="payee_name" id="jambo-wallet-wd-name" maxlength="100"
                           class="form-control" value="{{ old('payee_name') }}" required>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small mb-1" for="jambo-wallet-wd-msisdn">Mobile money number</label>
                    <input type="text" name="payee_msisdn" id="jambo-wallet-wd-msisdn" maxlength="30"
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

    {{-- Activity --}}
    <div class="jambo-hub-card">
        <h5 class="mb-3">Activity</h5>

        @if ($entries->count())
            <div class="table-responsive">
                <table class="table table-borderless table-hover align-middle small mb-0">
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
                                        <span class="text-muted d-block">{{ $entry->memo }}</span>
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

            <div class="mt-3">
                {{ $entries->links() }}
            </div>
        @else
            <div class="text-center py-4">
                <i class="ph ph-wallet fs-1 text-muted"></i>
                <p class="text-muted mb-0 mt-2">Nothing here yet — refer friends to start earning.</p>
            </div>
        @endif
    </div>
@endsection
