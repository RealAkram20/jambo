@extends('layouts.app', ['module_title' => 'Monetization'])

@section('content')
<div class="container-fluid">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row justify-content-center">
        <div class="col-12 col-xl-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title mb-1">Withdrawal #{{ $withdrawal->id }}</h4>
                        <p class="text-muted mb-0" style="font-size:13px;">
                            Requested {{ $withdrawal->requested_at->format('d M Y H:i') }} by
                            <a href="{{ route('admin.monetization.partners.show', $withdrawal->partner_id) }}">
                                {{ $withdrawal->partner->display_name }}
                            </a>
                        </p>
                    </div>
                    @switch($withdrawal->status)
                        @case('requested') <span class="badge bg-warning" style="font-size:13px;">Pending review</span> @break
                        @case('approved') <span class="badge bg-info-subtle text-info-emphasis" style="font-size:13px;">Approved — awaiting payment</span> @break
                        @case('paid') <span class="badge bg-success" style="font-size:13px;">Paid</span> @break
                        @case('rejected') <span class="badge bg-danger" style="font-size:13px;">Rejected</span> @break
                    @endswitch
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-4" style="font-size:14px;">
                        <div class="col-6 col-md-3">
                            <div class="text-muted" style="font-size:12px;">Amount</div>
                            <strong style="font-size:20px;">UGX {{ number_format((float) $withdrawal->amount, 0) }}</strong>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-muted" style="font-size:12px;">Pay to</div>
                            <strong>{{ $withdrawal->payout_msisdn_snapshot }}</strong>
                            <small class="text-muted d-block">{{ strtoupper($withdrawal->payout_network_snapshot) }}</small>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-muted" style="font-size:12px;">Registered name</div>
                            {{ $withdrawal->payout_name_snapshot }}
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-muted" style="font-size:12px;">Partner balance (after hold)</div>
                            UGX {{ number_format((float) $balance, 0) }}
                        </div>
                    </div>

                    @if ($withdrawal->partner->payout_msisdn !== $withdrawal->payout_msisdn_snapshot)
                        <div class="alert alert-warning" style="font-size:13px;">
                            <i class="ph ph-warning me-1"></i>
                            The partner's CURRENT payout number differs from this request's snapshot — their details
                            changed after requesting. Verify with the partner before paying.
                        </div>
                    @endif

                    <dl class="row mb-4" style="font-size:13px;">
                        @if ($withdrawal->approved_at)
                            <dt class="col-4 text-muted">Approved</dt>
                            <dd class="col-8">{{ $withdrawal->approved_at->format('d M Y H:i') }} by {{ $withdrawal->approvedBy->username ?? '—' }}</dd>
                        @endif
                        @if ($withdrawal->paid_at)
                            <dt class="col-4 text-muted">Paid</dt>
                            <dd class="col-8">
                                {{ $withdrawal->paid_at->format('d M Y H:i') }} by {{ $withdrawal->paidBy->username ?? '—' }}
                                · ref <code>{{ $withdrawal->transaction_reference }}</code>
                            </dd>
                        @endif
                        @if ($withdrawal->rejected_at)
                            <dt class="col-4 text-muted">Rejected</dt>
                            <dd class="col-8">
                                {{ $withdrawal->rejected_at->format('d M Y H:i') }} by {{ $withdrawal->rejectedBy->username ?? '—' }}
                                — {{ $withdrawal->rejection_reason }}
                            </dd>
                        @endif
                    </dl>

                    @if ($withdrawal->status === 'requested')
                        <form method="POST" action="{{ route('admin.monetization.withdrawals.approve', $withdrawal) }}" class="mb-3">
                            @csrf
                            <button class="btn btn-primary w-100">
                                <i class="ph ph-check-circle me-1"></i> Approve — I will send the money now
                            </button>
                        </form>
                    @endif

                    @if ($withdrawal->status === 'approved')
                        <form method="POST" action="{{ route('admin.monetization.withdrawals.mark-paid', $withdrawal) }}" class="mb-3">
                            @csrf
                            <label class="form-label">Mobile money transaction reference</label>
                            <div class="input-group">
                                <input type="text" name="transaction_reference" class="form-control" required
                                       placeholder="e.g. MP240712.1234.A56789">
                                <button class="btn btn-success"><i class="ph ph-money me-1"></i> Mark paid</button>
                            </div>
                        </form>
                    @endif

                    @if ($withdrawal->isOpen())
                        <form method="POST" action="{{ route('admin.monetization.withdrawals.reject', $withdrawal) }}"
                              onsubmit="return confirm('Reject this withdrawal and return UGX {{ number_format((float) $withdrawal->amount, 0) }} to the partner wallet?');">
                            @csrf
                            <label class="form-label">Rejection reason (shown to the partner)</label>
                            <div class="input-group">
                                <input type="text" name="rejection_reason" class="form-control" required maxlength="190">
                                <button class="btn btn-danger"><i class="ph ph-x-circle me-1"></i> Reject &amp; refund</button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
