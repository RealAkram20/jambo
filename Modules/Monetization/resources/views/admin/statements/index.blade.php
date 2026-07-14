@extends('layouts.app', ['module_title' => 'Monetization'])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-1">Monthly Statements</h4>
                    <p class="text-muted mb-0" style="font-size:13px;">
                        Drafts compute automatically on the 1st. Review a draft, then Close &amp; Credit to release
                        earnings into partner wallets — nothing is owed until then.
                    </p>
                </div>

                @if (session('success'))
                    <div class="alert alert-success mx-4 mt-3 mb-0">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger mx-4 mt-3 mb-0">{{ session('error') }}</div>
                @endif

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table custom-table align-middle mb-0">
                            <thead>
                                <tr class="text-uppercase" style="font-size:11px;letter-spacing:.5px;">
                                    <th>Month</th>
                                    <th>Gross revenue</th>
                                    <th>Pool</th>
                                    <th>Partner pool</th>
                                    <th>Partners</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($periods as $period)
                                    <tr>
                                        <td><strong>{{ $period->period_month->format('F Y') }}</strong></td>
                                        <td>UGX {{ number_format((float) $period->gross_revenue, 0) }}</td>
                                        <td>UGX {{ number_format((float) $period->pool_amount, 0) }}</td>
                                        <td>UGX {{ number_format((float) $period->partner_pool_amount, 0) }}</td>
                                        <td><span class="badge bg-info-subtle text-info-emphasis">{{ $period->statements_count }}</span></td>
                                        <td>
                                            @if ($period->isClosed())
                                                <span class="badge bg-success">Closed &amp; credited</span>
                                            @else
                                                <span class="badge bg-warning">Draft</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.monetization.statements.show', $period) }}"
                                               class="btn btn-sm btn-info-subtle" title="Review">
                                                <i class="ph ph-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted" style="font-size:14px;">
                                            No statement periods yet. The first draft computes on the 1st of next month, or run
                                            <code>php artisan monetization:compute-draft --month=YYYY-MM</code>.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($periods->hasPages())
                        <div class="mt-3 d-flex justify-content-center">{{ $periods->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
