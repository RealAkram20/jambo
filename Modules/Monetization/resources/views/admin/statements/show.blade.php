@extends('layouts.app', ['module_title' => 'Monetization'])

@section('content')
<div class="container-fluid">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h4 class="card-title mb-1">{{ $period->period_month->format('F Y') }}</h4>
                <p class="text-muted mb-0" style="font-size:13px;">
                    @if ($period->isClosed())
                        Closed {{ optional($period->closed_at)->format('d M Y H:i') }}
                        by {{ $period->closedBy->username ?? 'system' }} — wallets credited, immutable.
                    @else
                        Draft — computed {{ optional($period->computed_at)->format('d M Y H:i') }}.
                        Recompute picks up new qualified views, splits and settings.
                    @endif
                </p>
            </div>
            @role('super-admin')
            @unless ($period->isClosed())
                <div class="d-flex gap-2">
                    <form method="POST" action="{{ route('admin.monetization.statements.recompute', $period) }}">
                        @csrf
                        <button class="btn btn-ghost"><i class="ph ph-arrows-clockwise me-1"></i> Recompute draft</button>
                    </form>
                    <form method="POST" action="{{ route('admin.monetization.statements.close', $period) }}"
                          onsubmit="return confirm('Close {{ $period->period_month->format('F Y') }} and credit UGX {{ number_format((float) $period->partner_pool_amount, 0) }} to partner wallets? This cannot be undone.');">
                        @csrf
                        <button class="btn btn-primary"><i class="ph ph-lock-key me-1"></i> Close &amp; Credit</button>
                    </form>
                </div>
            @endunless
            @endrole
        </div>
        <div class="card-body">
            <div class="row g-3" style="font-size:14px;">
                <div class="col-6 col-md-2">
                    <div class="text-muted" style="font-size:12px;">Gross revenue</div>
                    <strong>UGX {{ number_format((float) $period->gross_revenue, 0) }}</strong>
                </div>
                <div class="col-6 col-md-2">
                    <div class="text-muted" style="font-size:12px;">Gateway fee ({{ $period->settings_snapshot['gateway_fee_percent'] ?? '?' }}%)</div>
                    − UGX {{ number_format((float) $period->gateway_fee_amount, 0) }}
                </div>
                <div class="col-6 col-md-2">
                    <div class="text-muted" style="font-size:12px;">Infra cost</div>
                    − UGX {{ number_format((float) $period->infra_cost_amount, 0) }}
                </div>
                <div class="col-6 col-md-2">
                    <div class="text-muted" style="font-size:12px;">Pool ({{ $period->settings_snapshot['pool_percent'] ?? '?' }}% of net)</div>
                    <strong>UGX {{ number_format((float) $period->pool_amount, 0) }}</strong>
                </div>
                <div class="col-6 col-md-2">
                    <div class="text-muted" style="font-size:12px;">Partner pool</div>
                    <strong class="text-success">UGX {{ number_format((float) $period->partner_pool_amount, 0) }}</strong>
                </div>
                <div class="col-6 col-md-2">
                    <div class="text-muted" style="font-size:12px;">Platform keeps</div>
                    UGX {{ number_format((float) $period->pool_amount - (float) $period->partner_pool_amount, 0) }}
                    <small class="text-muted d-block">unassigned splits + rounding</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Partner lines ({{ $period->statements->count() }})</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table custom-table align-middle mb-0">
                    <thead>
                        <tr class="text-uppercase" style="font-size:11px;letter-spacing:.5px;">
                            <th>Partner</th>
                            <th>Type</th>
                            <th>Minutes</th>
                            <th>Multiplier</th>
                            <th>Share</th>
                            <th class="text-end">Amount</th>
                            <th>Titles</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($period->statements as $statement)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.monetization.partners.show', $statement->partner_id) }}">
                                        <strong>{{ $statement->partner_name }}</strong>
                                    </a>
                                </td>
                                <td><span class="badge bg-secondary-subtle text-secondary-emphasis">{{ str_replace('_', ' ', ucfirst($statement->partner_type)) }}</span></td>
                                <td>{{ number_format((float) $statement->qualified_minutes, 0) }}</td>
                                <td><code>{{ rtrim(rtrim($statement->multiplier_used, '0'), '.') }}×</code></td>
                                <td>{{ number_format((float) $statement->share_ratio * 100, 2) }}%</td>
                                <td class="text-end fw-bold">UGX {{ number_format((float) $statement->amount, 0) }}</td>
                                <td style="font-size:13px;">
                                    @foreach (collect($statement->breakdown ?? [])->take(3) as $line)
                                        <span class="badge bg-info-subtle text-info-emphasis me-1">{{ $line['title'] }} · {{ $line['split_percent'] }}%</span>
                                    @endforeach
                                    @if (count($statement->breakdown ?? []) > 3)
                                        <small class="text-muted">+{{ count($statement->breakdown) - 3 }} more</small>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center py-5 text-muted">No partner earned anything this month.</td></tr>
                        @endforelse
                    </tbody>
                    @if ($period->statements->isNotEmpty())
                        <tfoot>
                            <tr>
                                <th colspan="5" class="text-end">Total</th>
                                <th class="text-end">UGX {{ number_format((float) $period->statements->sum('amount'), 0) }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
