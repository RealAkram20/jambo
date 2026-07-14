@extends('monetization::layouts.partner')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4 class="card-title mb-1">Statement — {{ $statement->period->period_month->format('F Y') }}</h4>
            <p class="text-muted mb-0" style="font-size:13px;">
                Settled {{ optional($statement->period->closed_at)->format('d M Y') }} · credited to your wallet
            </p>
        </div>
        <div class="text-end">
            <div class="text-muted" style="font-size:12px;">Total earned</div>
            <div class="fw-bold" style="font-size:24px;">UGX {{ number_format((float) $statement->amount, 0) }}</div>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-4" style="font-size:14px;">
            <div class="col-6 col-md-3">
                <div class="text-muted" style="font-size:12px;">Qualified minutes</div>
                {{ number_format((float) $statement->qualified_minutes, 0) }}
            </div>
            <div class="col-6 col-md-3">
                <div class="text-muted" style="font-size:12px;">Multiplier applied</div>
                {{ rtrim(rtrim($statement->multiplier_used, '0'), '.') }}×
            </div>
            <div class="col-6 col-md-3">
                <div class="text-muted" style="font-size:12px;">Share of partner pool</div>
                {{ number_format((float) $statement->share_ratio * 100, 2) }}%
            </div>
            <div class="col-6 col-md-3">
                <div class="text-muted" style="font-size:12px;">Partner pool that month</div>
                UGX {{ number_format((float) $statement->period->partner_pool_amount, 0) }}
            </div>
        </div>

        <h6 class="text-uppercase text-muted mb-2" style="font-size:11px;letter-spacing:.5px;">Per-title breakdown</h6>
        <div class="table-responsive">
            <table class="table custom-table align-middle mb-0">
                <thead>
                    <tr class="text-uppercase" style="font-size:11px;letter-spacing:.5px;">
                        <th>Title</th><th>Type</th><th>Watched minutes</th><th>Your split</th><th class="text-end">Your minutes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($statement->breakdown ?? [] as $line)
                        <tr>
                            <td>{{ $line['title'] }}</td>
                            <td><span class="badge bg-secondary-subtle text-secondary-emphasis">{{ ucfirst($line['type']) }}</span></td>
                            <td>{{ number_format($line['minutes'], 0) }}</td>
                            <td><code>{{ $line['split_percent'] }}%</code></td>
                            <td class="text-end">{{ number_format($line['credited_minutes'], 1) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-4 text-muted">No breakdown recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
