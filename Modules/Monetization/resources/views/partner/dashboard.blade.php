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


<div class="row g-3">
    <div class="col-12 col-lg-7">
        <div class="card h-100">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h5 class="card-title mb-0">Earnings</h5>
                {{-- Same filter chrome as the admin dashboard charts. --}}
                <div class="dropdown">
                    <button class="btn custom-btn-dark-dropdown dropdown-toggle" type="button"
                            id="earnings-period" data-bs-toggle="dropdown" aria-expanded="false">Month</button>
                    <ul class="dropdown-menu sub-dropdown" aria-labelledby="earnings-period">
                        <li><a class="dropdown-item" href="javascript:void(0)" data-earnings-period="Week">Week</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0)" data-earnings-period="Month">Month</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0)" data-earnings-period="Year">Year</a></li>
                    </ul>
                </div>
            </div>
            <div class="card-body">
                {{-- Headline mirrors the graph's window; estimates move
                     until month close credits the statement. --}}
                <div class="mb-3">
                    <div class="fw-bold" style="font-size:26px;" id="estimate-amount">
                        {{ $estimate['currency'] }} {{ number_format($estimate['amount']) }}
                    </div>
                    <span class="text-muted" style="font-size:13px;" id="estimate-detail">
                        estimated from {{ number_format($estimate['minutes'], 0) }} weighted minutes this month
                    </span>
                    <span class="badge bg-warning-subtle text-warning-emphasis ms-1" style="font-size:10px;">ESTIMATES SETTLE AT MONTH CLOSE</span>
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
    function render(elId, url, opts) {
        fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
            .then(r => r.json())
            .then(data => {
                new ApexCharts(document.querySelector(elId), {
                    chart: {type: opts.type, height: opts.height || 280, toolbar: {show: false}, foreColor: '#8A92A6'},
                    series: data.series,
                    xaxis: {categories: data.labels},
                    colors: opts.colours,
                    dataLabels: {enabled: false},
                    stroke: {curve: 'smooth', width: opts.type === 'line' ? 3 : 0},
                    plotOptions: {bar: {borderRadius: 4, columnWidth: '45%'}},
                    grid: {borderColor: 'rgba(138,146,166,.15)'},
                }).render();
            })
            .catch(() => {});
    }
    render('#chart-minutes', '{{ route('partner.charts', ['chart' => 'minutes']) }}', {type: 'line', colours: ['#89F425']});

    // Earnings: dynamic Week/Month/Year graph + synced headline.
    // Daily estimate bars for Week/Month; settled statements (current
    // month at its live estimate) for Year.
    (function () {
        var chart = null;
        var amountEl = document.getElementById('estimate-amount');
        var detailEl = document.getElementById('estimate-detail');
        var toggleEl = document.getElementById('earnings-period');
        var currency = @json($estimate['currency']);
        var baseUrl = '{{ route('partner.charts', ['chart' => 'earnings']) }}';

        function load(period) {
            fetch(baseUrl + '?period=' + period, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
                .then(r => r.json())
                .then(data => {
                    if (toggleEl) toggleEl.textContent = period;
                    if (amountEl && data.headline) {
                        amountEl.textContent = currency + ' ' + Number(data.headline.amount).toLocaleString();
                        detailEl.textContent = data.headline.detail;
                    }
                    if (chart) {
                        chart.updateOptions({series: data.series, xaxis: {categories: data.labels}});
                        return;
                    }
                    chart = new ApexCharts(document.querySelector('#chart-earnings'), {
                        chart: {type: 'bar', height: 240, toolbar: {show: false}, foreColor: '#8A92A6'},
                        series: data.series,
                        xaxis: {categories: data.labels},
                        colors: ['#1A98FF'],
                        dataLabels: {enabled: false},
                        plotOptions: {bar: {borderRadius: 4, columnWidth: '55%'}},
                        grid: {borderColor: 'rgba(138,146,166,.15)'},
                        yaxis: {labels: {formatter: function (v) { return Number(v).toLocaleString(); }}},
                        tooltip: {y: {formatter: function (v) { return currency + ' ' + Number(v).toLocaleString(); }}},
                    });
                    chart.render();
                })
                .catch(() => {});
        }

        document.querySelectorAll('[data-earnings-period]').forEach(function (item) {
            item.addEventListener('click', function () { load(item.dataset.earningsPeriod); });
        });

        load('Month');
    })();
})();
</script>
@endpush
@endsection
