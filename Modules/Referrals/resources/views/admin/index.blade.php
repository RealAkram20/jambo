@extends('layouts.app', ['module_title' => 'Referrals'])

@section('content')
<div class="container-fluid">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activePane === 'refer' ? 'active' : '' }}" data-bs-toggle="tab"
                    data-bs-target="#refer-pane" type="button" role="tab">Refer &amp; Earn</button>
        </li>
        @if ($canClerk)
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activePane === 'payouts' ? 'active' : '' }}" data-bs-toggle="tab"
                        data-bs-target="#payouts-pane" type="button" role="tab">
                    Payouts
                    @if (!empty($pendingPayouts))
                        <span class="badge bg-warning text-dark ms-1">{{ $pendingPayouts }}</span>
                    @endif
                </button>
            </li>
        @endif
        @if ($isSuper)
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activePane === 'overview' ? 'active' : '' }}" data-bs-toggle="tab"
                        data-bs-target="#overview-pane" type="button" role="tab">Overview</button>
            </li>
        @endif
    </ul>

    <div class="tab-content">
        {{-- ============================ Refer & Earn (own participation) ============================ --}}
        <div class="tab-pane fade {{ $activePane === 'refer' ? 'show active' : '' }}" id="refer-pane" role="tabpanel">
            <div class="row g-3 mb-4">
                <div class="col-6 col-xl-3">
                    <div class="card mb-0">
                        <div class="card-body text-center py-3">
                            <h4 class="mb-0">{{ $currency }} {{ number_format((float) $refer['balance'], 0) }}</h4>
                            <span class="text-muted small">Wallet balance</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="card mb-0">
                        <div class="card-body text-center py-3">
                            <h4 class="mb-0">{{ $currency }} {{ number_format((float) bcadd((string) $refer['totalEarned'], (string) $refer['partnerEarned'], 2), 0) }}</h4>
                            <span class="text-muted small">Total earned</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="card mb-0">
                        <div class="card-body text-center py-3">
                            <h4 class="mb-0">{{ number_format($refer['totalReferrals']) }}</h4>
                            <span class="text-muted small">Referrals</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="card mb-0">
                        <div class="card-body text-center py-3">
                            <h4 class="mb-0">{{ number_format($refer['qualifiedCount']) }}</h4>
                            <span class="text-muted small">Subscribed</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Your referral link</h4>
                </div>
                <div class="card-body">
                    <div class="input-group mb-4" style="max-width: 520px;">
                        <input type="text" class="form-control" id="jambo-admin-ref-link" value="{{ $refer['link'] }}" readonly>
                        <button type="button" class="btn btn-primary" id="jambo-admin-ref-copy">
                            <i class="ph ph-copy me-1"></i>Copy link
                        </button>
                    </div>

                    <div class="d-flex align-items-center justify-content-between">
                        <span class="small text-muted">Referral rewards are paid into your wallet, together with your performance earnings.</span>
                        <a class="btn btn-outline-primary flex-shrink-0" href="{{ route('admin.wallet.index') }}">
                            <i class="ph ph-wallet me-1"></i>Open My Wallet
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================ Payouts (finance | super-admin) ============================ --}}
        @if ($canClerk)
            <div class="tab-pane fade {{ $activePane === 'payouts' ? 'show active' : '' }}" id="payouts-pane" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h4 class="card-title mb-0">Payouts</h4>
                        <a class="small" href="{{ route('admin.wallet.withdrawals.index') }}">Full queue</a>
                    </div>
                    <div class="card-body p-0">
                        @include('wallet::admin._queue', ['withdrawals' => $withdrawals])
                    </div>
                    @if ($withdrawals->hasPages())
                        <div class="card-footer">{{ $withdrawals->appends(['tab' => 'payouts'])->links() }}</div>
                    @endif
                </div>
            </div>
        @endif

        {{-- ============================ Overview (super-admin) ============================ --}}
        @if ($isSuper)
            <div class="tab-pane fade {{ $activePane === 'overview' ? 'show active' : '' }}" id="overview-pane" role="tabpanel">
                <div class="row g-3 mb-4">
                    <div class="col-6 col-xl-3">
                        <div class="card mb-0">
                            <div class="card-body text-center py-3">
                                <h4 class="mb-0">{{ number_format($stats['total']) }}</h4>
                                <span class="text-muted small">Total referrals</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-xl-3">
                        <div class="card mb-0">
                            <div class="card-body text-center py-3">
                                <h4 class="mb-0">{{ number_format($stats['qualified']) }}</h4>
                                <span class="text-muted small">Subscribed</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-xl-3">
                        <div class="card mb-0">
                            <div class="card-body text-center py-3">
                                <h4 class="mb-0">{{ $currency }} {{ number_format((float) $stats['discounts_given'], 0) }}</h4>
                                <span class="text-muted small">Discounts given</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-xl-3">
                        <div class="card mb-0">
                            <div class="card-body text-center py-3">
                                <h4 class="mb-0">{{ $currency }} {{ number_format((float) $stats['rewards_paid'], 0) }}</h4>
                                <span class="text-muted small">Rewards earned</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h4 class="card-title mb-0">Referrals</h4>
                        <form method="GET" class="d-flex gap-2">
                            <input type="hidden" name="tab" value="overview">
                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">All statuses</option>
                                <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                                <option value="qualified" @selected(request('status') === 'qualified')>Subscribed</option>
                            </select>
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search code / email / username"
                                   value="{{ request('search') }}">
                            <button type="submit" class="btn btn-sm btn-primary">Search</button>
                        </form>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Referrer</th>
                                        <th>Referred user</th>
                                        <th>Code</th>
                                        <th>Source</th>
                                        <th>Status</th>
                                        <th>Joined</th>
                                        <th class="text-end">Discount</th>
                                        <th class="text-end">Reward</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($referrals as $referral)
                                        <tr>
                                            <td>
                                                {{ $referral->referrer?->username ?? '—' }}
                                                <span class="text-muted d-block small">{{ $referral->referrer?->email }}</span>
                                            </td>
                                            <td>
                                                {{ $referral->referredUser?->username ?? '—' }}
                                                <span class="text-muted d-block small">{{ $referral->referredUser?->email }}</span>
                                            </td>
                                            <td><code>{{ $referral->code_used }}</code></td>
                                            <td>{{ ucfirst($referral->source) }}</td>
                                            <td>
                                                @if ($referral->status === \Modules\Referrals\app\Models\Referral::STATUS_QUALIFIED)
                                                    <span class="badge bg-success">Subscribed</span>
                                                @else
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                @endif
                                            </td>
                                            <td>{{ $referral->created_at?->format('M j, Y') }}</td>
                                            <td class="text-end">
                                                {{ $referral->discount_amount !== null ? number_format((float) $referral->discount_amount, 0) : '—' }}
                                            </td>
                                            <td class="text-end">
                                                {{ $referral->reward_amount !== null ? number_format((float) $referral->reward_amount, 0) : '—' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">No referrals yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @if ($referrals->hasPages())
                        <div class="card-footer">
                            {{ $referrals->appends(['tab' => 'overview'])->links() }}
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <script>
        (function () {
            var btn = document.getElementById('jambo-admin-ref-copy');
            var input = document.getElementById('jambo-admin-ref-link');
            if (!btn || !input) return;
            btn.addEventListener('click', function () {
                navigator.clipboard.writeText(input.value).then(function () {
                    btn.innerHTML = '<i class="ph ph-check me-1"></i>Copied';
                    setTimeout(function () {
                        btn.innerHTML = '<i class="ph ph-copy me-1"></i>Copy link';
                    }, 1500);
                });
            });
        })();
    </script>
</div>
@endsection
