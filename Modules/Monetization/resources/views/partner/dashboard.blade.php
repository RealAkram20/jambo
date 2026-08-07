@extends('monetization::layouts.partner')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="mb-1">Welcome, {{ $partner->display_name }}</h4>
        <p class="text-muted mb-0" style="font-size:13px;">
            {{ str_replace('_', ' ', ucfirst($partner->type)) }}
            @if ($partner->status !== 'enrolled')
                · <span class="text-danger">enrollment suspended — earnings paused</span>
            @endif
        </p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted" style="font-size:12px;">Wallet balance</div>
            <div class="fw-bold" style="font-size:22px;">UGX {{ number_format((float) $balance, 0) }}</div>
            <a href="{{ route('partner.withdrawals.index') }}" style="font-size:13px;">Withdraw →</a>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted" style="font-size:12px;">Qualified minutes this month</div>
            <div class="fw-bold" style="font-size:22px;">{{ number_format($monthMinutes, 0) }}</div>
            <span class="text-muted" style="font-size:13px;">across {{ $titleCount }} title(s)</span>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted" style="font-size:12px;">Last statement</div>
            @if ($lastStatement)
                <div class="fw-bold" style="font-size:22px;">UGX {{ number_format((float) $lastStatement->amount, 0) }}</div>
                <span class="text-muted" style="font-size:13px;">{{ $lastStatement->period->period_month->format('F Y') }}</span>
            @else
                <div class="fw-bold" style="font-size:22px;">—</div>
                <span class="text-muted" style="font-size:13px;">no closed month yet</span>
            @endif
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted" style="font-size:12px;">Withdrawal status</div>
            @if ($openWithdrawal)
                <div class="fw-bold" style="font-size:22px;">UGX {{ number_format((float) $openWithdrawal->amount, 0) }}</div>
                <span class="badge bg-warning">{{ ucfirst($openWithdrawal->status) }}</span>
            @else
                <div class="fw-bold" style="font-size:22px;">—</div>
                <span class="text-muted" style="font-size:13px;">none in progress</span>
            @endif
        </div></div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var group = document.getElementById('estimate-window');
    if (!group) return;
    var amountEl = document.getElementById('estimate-amount');
    var detailEl = document.getElementById('estimate-detail');
    var url = @json(route('partner.estimate'));
    var labels = { day: 'today', week: 'in the last 7 days', month: 'this month' };

    group.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-window]');
        if (!btn) return;
        group.querySelectorAll('[data-window]').forEach(function (b) {
            b.classList.toggle('active', b === btn);
        });
        amountEl.style.opacity = '.45';
        fetch(url + '?window=' + btn.dataset.window, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                amountEl.textContent = d.currency + ' ' + Number(d.amount).toLocaleString();
                detailEl.textContent = 'from ' + Number(d.minutes).toLocaleString() + ' weighted minutes ' + (labels[d.window] || '');
                amountEl.style.opacity = '';
            })
            .catch(function () { amountEl.style.opacity = ''; });
    });
})();
</script>
@endpush

<div class="row g-3">
    <div class="col-12 col-lg-7">
        <div class="card h-100">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h5 class="card-title mb-0">Earnings</h5>
                <div class="btn-group" role="group" aria-label="Estimate window" id="estimate-window">
                    <button type="button" class="btn btn-sm btn-outline-primary" data-window="day">Today</button>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-window="week">7 days</button>
                    <button type="button" class="btn btn-sm btn-outline-primary active" data-window="month">This month</button>
                </div>
            </div>
            <div class="card-body">
                {{-- Live estimate for the selected window. Moves until
                     month close credits the statement — the chart below
                     shows those settled months. --}}
                <div class="mb-3">
                    <div class="fw-bold" style="font-size:26px;" id="estimate-amount">
                        {{ $estimate['currency'] }} {{ number_format($estimate['amount']) }}
                    </div>
                    <span class="text-muted" style="font-size:13px;" id="estimate-detail">
                        from {{ number_format($estimate['minutes'], 0) }} weighted minutes this month
                    </span>
                    <span class="badge bg-warning-subtle text-warning-emphasis ms-1" style="font-size:10px;">ESTIMATE — settles at month close</span>
                </div>
                <div id="chart-earnings" style="min-height:240px;"></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-5">
        <div class="card h-100">
            <div class="card-header"><h5 class="card-title mb-0">Qualified watch-minutes</h5></div>
            <div class="card-body"><div id="chart-minutes" style="min-height:280px;"></div></div>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('dashboard/vendor/apexcharts/apexcharts.min.js') }}"></script>
<script>
(function () {
    function render(elId, url, type, colour) {
        fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
            .then(r => r.json())
            .then(data => {
                new ApexCharts(document.querySelector(elId), {
                    chart: {type: type, height: 280, toolbar: {show: false}, foreColor: '#8A92A6'},
                    series: data.series,
                    xaxis: {categories: data.labels},
                    colors: [colour],
                    dataLabels: {enabled: false},
                    stroke: {curve: 'smooth', width: type === 'line' ? 3 : 0},
                    plotOptions: {bar: {borderRadius: 4, columnWidth: '45%'}},
                    grid: {borderColor: 'rgba(138,146,166,.15)'},
                }).render();
            })
            .catch(() => {});
    }
    render('#chart-earnings', '{{ route('partner.charts', ['chart' => 'earnings']) }}', 'bar', '#1A98FF');
    render('#chart-minutes', '{{ route('partner.charts', ['chart' => 'minutes']) }}', 'line', '#89F425');
})();
</script>
@endpush
@endsection
