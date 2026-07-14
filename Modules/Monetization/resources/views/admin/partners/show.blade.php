@extends('layouts.app', ['module_title' => 'Monetization'])

@section('content')
<div class="container-fluid">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-xl-7">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title mb-1">{{ $partner->display_name }}</h4>
                        <p class="text-muted mb-0" style="font-size:13px;">
                            {{ str_replace('_', ' ', ucfirst($partner->type)) }}
                            @if ($partner->vj) · VJ: {{ $partner->vj->name }} @endif
                            · Enrolled {{ optional($partner->enrolled_at)->format('d M Y') ?? '—' }}
                        </p>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        @if ($partner->status === 'enrolled')
                            <span class="badge bg-success">Enrolled</span>
                        @else
                            <span class="badge bg-danger">Suspended</span>
                        @endif
                        @role('super-admin')
                        <a href="{{ route('admin.monetization.partners.edit', $partner) }}" class="btn btn-sm btn-success-subtle">
                            <i class="ph ph-pencil-simple"></i> Edit
                        </a>
                        @endrole
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-3">
                            <div class="text-muted" style="font-size:12px;">Wallet balance</div>
                            <div class="fw-bold" style="font-size:18px;">UGX {{ number_format((float) $balance, 0) }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-muted" style="font-size:12px;">Multiplier</div>
                            <div class="fw-bold" style="font-size:18px;">{{ rtrim(rtrim($partner->multiplier, '0'), '.') }}×</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-muted" style="font-size:12px;">Login account</div>
                            <div style="font-size:14px;">{{ $partner->user->email ?? '— none —' }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-muted" style="font-size:12px;">Attributed titles</div>
                            <div class="fw-bold" style="font-size:18px;">{{ $partner->splits->count() }}</div>
                        </div>
                        <div class="col-12">
                            <div class="text-muted" style="font-size:12px;">Content rights (own titles)</div>
                            @if ($partner->can_edit_content)
                                <span class="badge bg-success">Can edit</span>
                            @endif
                            @if ($partner->can_delete_content)
                                <span class="badge bg-danger">Can delete</span>
                            @endif
                            @unless ($partner->can_edit_content || $partner->can_delete_content)
                                <span class="badge bg-secondary">View / watch only</span>
                            @endunless
                        </div>
                    </div>

                    <h6 class="text-uppercase text-muted mb-2" style="font-size:11px;letter-spacing:.5px;">Title splits</h6>
                    <div class="table-responsive mb-4">
                        <table class="table custom-table align-middle mb-0">
                            <thead>
                                <tr class="text-uppercase" style="font-size:11px;letter-spacing:.5px;">
                                    <th>Title</th><th>Type</th><th class="text-end">Share</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($partner->splits as $split)
                                    <tr>
                                        <td>{{ $split->splittable->title ?? $split->splittable->name ?? '(deleted)' }}</td>
                                        <td>
                                            <span class="badge bg-secondary-subtle text-secondary-emphasis">
                                                {{ str_contains($split->splittable_type, 'Movie') ? 'Movie' : 'Show' }}
                                            </span>
                                        </td>
                                        <td class="text-end"><code>{{ $split->percent }}%</code></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-3">No titles attributed yet — nothing accrues until splits are set.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <h6 class="text-uppercase text-muted mb-2" style="font-size:11px;letter-spacing:.5px;">Recent statements</h6>
                    <div class="table-responsive">
                        <table class="table custom-table align-middle mb-0">
                            <thead>
                                <tr class="text-uppercase" style="font-size:11px;letter-spacing:.5px;">
                                    <th>Month</th><th>Minutes</th><th class="text-end">Amount</th><th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentStatements as $statement)
                                    <tr>
                                        <td>{{ $statement->period->period_month->format('F Y') }}</td>
                                        <td>{{ number_format((float) $statement->qualified_minutes, 0) }}</td>
                                        <td class="text-end">UGX {{ number_format((float) $statement->amount, 0) }}</td>
                                        <td>
                                            @if ($statement->period->isClosed())
                                                <span class="badge bg-success">Credited</span>
                                            @else
                                                <span class="badge bg-warning">Draft</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-3">No statements yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-1">Payout profile</h5>
                    <p class="text-muted mb-0" style="font-size:13px;">
                        Withdrawals only pay the verified number. Changes re-enter review + cooldown.
                    </p>
                </div>
                <div class="card-body">
                    <dl class="row mb-4" style="font-size:14px;">
                        <dt class="col-5 text-muted">Status</dt>
                        <dd class="col-7">
                            @switch($partner->payout_status)
                                @case('verified')
                                    <span class="badge bg-success">Verified</span>
                                    <small class="text-muted d-block">{{ optional($partner->payout_verified_at)->format('d M Y H:i') }}</small>
                                    @break
                                @case('pending_review') <span class="badge bg-warning">Pending review</span> @break
                                @default <span class="badge bg-secondary">Not submitted</span>
                            @endswitch
                        </dd>
                        <dt class="col-5 text-muted">Mobile money</dt>
                        <dd class="col-7">{{ $partner->payout_msisdn ?? '—' }} <small class="text-muted">{{ strtoupper($partner->payout_network ?? '') }}</small></dd>
                        <dt class="col-5 text-muted">Registered name</dt>
                        <dd class="col-7">{{ $partner->payout_name ?? '—' }}</dd>
                        @if ($partner->payoutLocked())
                            <dt class="col-5 text-muted">Withdrawals frozen until</dt>
                            <dd class="col-7 text-danger">{{ $partner->payout_locked_until->format('d M Y H:i') }}</dd>
                        @endif
                    </dl>

                    @role('super-admin')
                    @if ($partner->payout_status === 'pending_review')
                        <form method="POST" action="{{ route('admin.monetization.partners.verify-payout', $partner) }}"
                              onsubmit="return confirm('Confirm this number belongs to {{ $partner->payout_name }} ({{ $partner->display_name }})? Withdrawals will pay it.');">
                            @csrf
                            <button class="btn btn-success w-100">
                                <i class="ph ph-seal-check me-1"></i> Verify payout profile
                            </button>
                        </form>
                    @endif
                    @endrole

                    @if ($openWithdrawals->isNotEmpty())
                        <hr>
                        <h6 class="text-uppercase text-muted mb-2" style="font-size:11px;letter-spacing:.5px;">Open withdrawals</h6>
                        @foreach ($openWithdrawals as $withdrawal)
                            <div class="d-flex justify-content-between align-items-center py-1" style="font-size:14px;">
                                <span>UGX {{ number_format((float) $withdrawal->amount, 0) }} · {{ ucfirst($withdrawal->status) }}</span>
                                <a href="{{ route('admin.monetization.withdrawals.show', $withdrawal) }}" class="btn btn-sm btn-info-subtle">Open</a>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
