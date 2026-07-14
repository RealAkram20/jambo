@extends('monetization::layouts.partner')

@section('content')
<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-1">Monthly statements</h4>
        <p class="text-muted mb-0" style="font-size:13px;">
            Each closed month's settled earnings. Amounts here have been credited to your wallet.
        </p>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table custom-table align-middle mb-0">
                <thead>
                    <tr class="text-uppercase" style="font-size:11px;letter-spacing:.5px;">
                        <th>Month</th>
                        <th>Qualified minutes</th>
                        <th>Share of pool</th>
                        <th class="text-end">Earned</th>
                        <th class="text-end"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($statements as $statement)
                        <tr>
                            <td><strong>{{ $statement->period->period_month->format('F Y') }}</strong></td>
                            <td>{{ number_format((float) $statement->qualified_minutes, 0) }}</td>
                            <td>{{ number_format((float) $statement->share_ratio * 100, 2) }}%</td>
                            <td class="text-end fw-bold">UGX {{ number_format((float) $statement->amount, 0) }}</td>
                            <td class="text-end">
                                <a href="{{ route('partner.statements.show', $statement->period_id) }}" class="btn btn-sm btn-info-subtle">
                                    Details
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-5 text-muted">No settled statements yet — your first one appears after the month closes.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
