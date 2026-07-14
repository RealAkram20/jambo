@extends('layouts.app', ['module_title' => 'Monetization'])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-1">Withdrawal Queue</h4>
                    <p class="text-muted mb-0" style="font-size:13px;">
                        Approve → send mobile money manually from the business account → mark paid with the transaction reference.
                    </p>
                </div>

                @if (session('success'))
                    <div class="alert alert-success mx-4 mt-3 mb-0">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger mx-4 mt-3 mb-0">{{ session('error') }}</div>
                @endif

                <div class="card-body">
                    <ul class="nav nav-pills gap-2 mb-4" style="font-size:14px;">
                        @foreach (['' => 'All', 'requested' => 'Pending', 'approved' => 'Approved', 'paid' => 'Paid', 'rejected' => 'Rejected'] as $key => $label)
                            <li class="nav-item">
                                <a class="nav-link @if ($status === $key) active @endif"
                                   href="{{ route('admin.monetization.withdrawals.index', $key ? ['status' => $key] : []) }}">
                                    {{ $label }}
                                    @if ($key !== '')
                                        <span class="badge bg-secondary-subtle text-secondary-emphasis ms-1">{{ $counts[$key] ?? 0 }}</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    <div class="table-responsive">
                        <table class="table custom-table align-middle mb-0">
                            <thead>
                                <tr class="text-uppercase" style="font-size:11px;letter-spacing:.5px;">
                                    <th>Requested</th>
                                    <th>Partner</th>
                                    <th>Amount</th>
                                    <th>Pay to</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($withdrawals as $withdrawal)
                                    <tr>
                                        <td>{{ $withdrawal->requested_at->format('d M Y H:i') }}</td>
                                        <td><strong>{{ $withdrawal->partner->display_name ?? '?' }}</strong></td>
                                        <td class="fw-bold">UGX {{ number_format((float) $withdrawal->amount, 0) }}</td>
                                        <td style="font-size:13px;">
                                            {{ $withdrawal->payout_msisdn_snapshot }}
                                            <small class="text-muted d-block">{{ strtoupper($withdrawal->payout_network_snapshot) }} · {{ $withdrawal->payout_name_snapshot }}</small>
                                        </td>
                                        <td>
                                            @switch($withdrawal->status)
                                                @case('requested') <span class="badge bg-warning">Pending</span> @break
                                                @case('approved') <span class="badge bg-info-subtle text-info-emphasis">Approved</span> @break
                                                @case('paid') <span class="badge bg-success">Paid</span> @break
                                                @case('rejected') <span class="badge bg-danger">Rejected</span> @break
                                            @endswitch
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.monetization.withdrawals.show', $withdrawal) }}"
                                               class="btn btn-sm btn-info-subtle" title="Open">
                                                <i class="ph ph-arrow-square-out"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center py-5 text-muted" style="font-size:14px;">No withdrawals{{ $status ? " with status “{$status}”" : '' }}.</td></tr>
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
</div>
@endsection
