@extends('layouts.app', ['isBanner' => false, 'isSwiperSlider' => true, 'isTour' => true])

@section('title')
    {{ 'Dashboard' }}
@endsection


@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="row">
            <div class="col-md-4 col-sm-6">
                <div class="card">
                    <div class="card-body">
                        <div class="icon-space mb-5">
                            <i class="ph ph-user fs-1"></i>
                        </div>
                        <div class="card-details">
                            <h1 class="fw-semibold card-details-title">{{ number_format($stats['total_users']) }}</h1>
                            <p class="mb-0 fs-6">{{ __('dashboard.total-users') }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="card">
                    <div class="card-body">
                        <div class="icon-space mb-5">
                            <i class="ph ph-user-gear fs-1"></i>
                        </div>
                        <div class="card-details">
                            <h1 class="fw-semibold card-details-title">{{ number_format($stats['active_users']) }}</h1>
                            <p class="mb-0 fs-6">{{ __('dashboard.active-users') }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="card">
                    <div class="card-body">
                        <div class="icon-space mb-5">
                            <i class="ph ph-currency-circle-dollar fs-1"></i>
                        </div>
                        <div class="card-details">
                            <h1 class="fw-semibold card-details-title">{{ number_format($stats['total_subscribers']) }}</h1>
                            <p class="mb-0 fs-6">{{ __('dashboard.total-subscribers') }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="card">
                    <div class="card-body">
                        <div class="icon-space mb-5">
                            <i class="ph ph-film-strip fs-1"></i>
                        </div>
                        <div class="card-details">
                            <h1 class="fw-semibold card-details-title">{{ number_format($stats['total_movies']) }}</h1>
                            <p class="mb-0 fs-6">{{ __('dashboard.total-movie') }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="card">
                    <div class="card-body">
                        <div class="icon-space mb-5">
                            <i class="ph ph-television-simple fs-1"></i>
                        </div>
                        <div class="card-details">
                            <h1 class="fw-semibold card-details-title">{{ number_format($stats['total_shows']) }}</h1>
                            <p class="mb-0 fs-6">{{ __('dashboard.total-tvshow') }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="card">
                    <div class="card-body">
                        <div class="icon-space mb-5">
                            <i class="ph ph-monitor-play fs-1"></i>
                        </div>
                        <div class="card-details">
                            <h1 class="fw-semibold card-details-title">{{ number_format($stats['total_episodes']) }}</h1>
                            <p class="mb-0 fs-6">Total Episodes</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card card-block card-height card-dashboard">
            <div class="card-header">
                <div class="iq-header-title">
                    <h3 class="card-title">{{ __('dashboard.top-genres') }}</h3>
                </div>
            </div>
            <div class="card-body">
                <div id="jambo-genre-chart" class="d-flex justify-content-center">
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card card-block card-height card-dashboard">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div class="iq-header-title">
                    <h3 class="card-title">{{ __('dashboard.total-revenue-subscriptions') }}</h3>
                </div>
                <div class="dropdown">
                    <button class="btn custom-btn-dark-dropdown dropdown-toggle total-revenue" type="button"
                        id="dropdownTotalRevenue" data-bs-toggle="dropdown" aria-expanded="false">Year</button>
                    <ul class="dropdown-menu sub-dropdown" aria-labelledby="dropdownTotalRevenue">
                        <li><a class="revenue-dropdown-item dropdown-item" data-type="Year">Year</a></li>
                        <li><a class="revenue-dropdown-item dropdown-item" data-type="Month">Month</a></li>
                        <li><a class="revenue-dropdown-item dropdown-item" data-type="Week">Week</a></li>
                    </ul>
                </div>
            </div>
            <div class="card-body">
                <div id="jambo-revenue-chart"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card card-dashboard">
            <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
                <div class="iq-header-title">
                    <h3 class="card-title">{{ __('dashboard.new-subscribers') }}</h3>
                </div>
                <div class="dropdown">
                    <button class="btn custom-btn-dark-dropdown dropdown-toggle total-revenue" type="button"
                        id="dropdownTotalRevenue1" data-bs-toggle="dropdown" aria-expanded="false">Year</button>
                    <ul class="dropdown-menu sub-dropdown" aria-labelledby="dropdownTotalRevenue1">
                        <li><a class="revenue-dropdown-item dropdown-item" data-type="Year">Year</a></li>
                        <li><a class="revenue-dropdown-item dropdown-item" data-type="Month">Month</a></li>
                        <li><a class="revenue-dropdown-item dropdown-item" data-type="Week">Week</a></li>
                    </ul>
                </div>
            </div>
            <div class="card-body">
                <div id="jambo-new-subscribers-chart"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card card-block card-height card-dashboard">
            <div class="card-header d-flex justify-content-between">
                <div class="iq-header-title">
                    <h3 class="card-title">{{ __('dashboard.most-watched') }}</h3>
                </div>
                <div class="dropdown">
                    <button class="btn custom-btn-dark-dropdown dropdown-toggle total-revenue" type="button"
                        id="dropdownTotalRevenue2" data-bs-toggle="dropdown" aria-expanded="false">Year</button>
                    <ul class="dropdown-menu sub-dropdown" aria-labelledby="dropdownTotalRevenue2">
                        <li><a class="revenue-dropdown-item dropdown-item" data-type="Year">Year</a></li>
                        <li><a class="revenue-dropdown-item dropdown-item" data-type="Month">Month</a></li>
                        <li><a class="revenue-dropdown-item dropdown-item" data-type="Week">Week</a></li>
                    </ul>
                </div>
            </div>
            <div class="card-body">
                <div id="jambo-most-watched-chart"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card card-block card-height card-dashboard">
            <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
                <div class="iq-header-title">
                    <h3 class="card-title">{{ __('dashboard.user-rating-and-reviews') }}</h3>
                </div>
                <div class="card-header-toolbar d-flex align-items-center ">
                    {{-- "View all" goes to the real review moderation page
                         instead of the original demo dropdown with dead
                         View/Delete/Edit/Print/Download links that went
                         to href="#". --}}
                    <a href="{{ route('dashboard.review') }}" class="text-primary text-decoration-none">
                        {{ __('dashboard.view-all') }} <i class="ri-arrow-right-line"></i>
                    </a>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="mt-4 table-responsive">
                    <table id="basic-table" class="table mb-0" role="grid">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Rating</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentReviews as $review)
                                <tr>
                                    <td>
                                        <div class="d-flex gap-3 align-items-center">
                                            <img class="avatar avatar-40 rounded-pill"
                                                src="{{ asset('dashboard/images/author/0' . ((($loop->index) % 6) + 1) . '.png') }}" alt="profile">
                                            <div class="text-start">
                                                <h6 class="m-0">{{ $review->user?->name ?? '—' }}</h6>
                                                <small>{{ $review->user?->email ?? '' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $review->created_at->format('jS M Y') }}</td>
                                    <td>{{ class_basename($review->reviewable_type) }}</td>
                                    <td>
                                        <div class="d-flex gap-3 align-items-center">
                                            <div class="star-rating">
                                                @for ($i = 1; $i <= 5; $i++)
                                                    <span class="star {{ $i <= ($review->stars ?? 0) ? 'filled text-warning' : '' }}">
                                                        <i class="ph ph-fill ph-star"></i>
                                                    </span>
                                                @endfor
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No reviews yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="row">
            <div class="col-lg-4 col-md-6">
                <div class="card card-block card-height card-dashboard">
                    <div class="card-header">
                        <div class="iq-header-title">
                            <h3 class="card-title">{{ __('dashboard.top-rated') }}</h3>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="jambo-top-rated-chart" class="d-flex align-items-center justify-content-center"></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8 col-md-6">
                <div class="card card-dashboard">
                    <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
                        <div class="iq-header-title">
                            <h3 class="card-title">{{ __('dashboard.transaction-history') }}</h3>
                        </div>
                        <div class="card-header-toolbar d-flex align-items-center ">
                            {{-- "View all" goes to the real payment orders
                                 admin page instead of a dead dropdown. --}}
                            <a href="{{ route('admin.payments.orders') }}" class="text-primary text-decoration-none">
                                {{ __('dashboard.view-all') }} <i class="ri-arrow-right-line"></i>
                            </a>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="mt-4 table-responsive">
                            <table id="basic-table1" class="table mb-0" role="grid">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Date</th>
                                        <th>Plan</th>
                                        <th>Amount</th>
                                        <th>Duration</th>
                                        <th>Payment Method</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($recentPayments as $payment)
                                        <tr>
                                            <td>
                                                <div class="d-flex gap-3 align-items-center">
                                                    <img class="avatar avatar-40 rounded-pill"
                                                        src="{{ asset('dashboard/images/author/0' . ((($loop->index) % 6) + 1) . '.png') }}" alt="profile">
                                                    <div class="text-start">
                                                        <h6 class="m-0">{{ $payment->user?->name ?? '—' }}</h6>
                                                        <small>{{ $payment->user?->email ?? '' }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ $payment->created_at->format('jS M Y') }}</td>
                                            <td>{{ $payment->metadata['tier_name'] ?? '—' }}</td>
                                            <td>{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</td>
                                            <td>{{ $payment->metadata['billing_period'] ?? '—' }}</td>
                                            <td>{{ ucfirst($payment->payment_gateway) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">No completed payments yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Live-data ApexCharts — every chart below reads from $chartData
     (built off the DB in DashboardController::index). The element
     IDs (#jambo-*-chart) are intentionally different from the
     Streamit template's chart IDs so chart-custom.js (loaded by
     components/partials/scripts/script.blade.php) skips them.

     The Streamit template pushes ApexCharts via `@push('after-scripts')`
     but `layouts.app` never renders `@stack('after-scripts')` — so
     the template's push stack is effectively dead. We load the
     library + init inline inside the content section here so it
     actually reaches the browser. --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/apexcharts/3.40.0/apexcharts.min.js"
    integrity="sha512-Kr1p/vGF2i84dZQTkoYZ2do8xHRaiqIa7ysnDugwoOcG0SbIx98erNekP/qms/hBDiBxj336//77d0dv53Jmew=="
    crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
    (function () {
        var data = @json($chartData);
        var primary = 'var(--bs-primary)';
        var tints = [
            'var(--bs-primary)',
            'var(--bs-primary-tint-20)',
            'var(--bs-primary-tint-40)',
            'var(--bs-primary-tint-60)',
            'var(--bs-primary-tint-80)',
        ];

        function init(id, options) {
            var el = document.querySelector(id);
            if (!el || typeof ApexCharts !== 'function') return;
            new ApexCharts(el, options).render();
        }

        // 1. Top genres — donut (hidden if no genres have movies yet)
        if (data.genres && data.genres.series.length > 0) {
            init('#jambo-genre-chart', {
                series: data.genres.series,
                labels: data.genres.labels,
                chart: { type: 'donut', height: 255 },
                colors: tints,
                stroke: { width: 0 },
                dataLabels: { enabled: false },
                legend: { position: 'bottom' },
                plotOptions: { pie: { donut: { size: '70%' } } },
            });
        } else {
            var g = document.querySelector('#jambo-genre-chart');
            if (g) g.innerHTML = '<div class="text-muted py-5" style="font-size:13px;">No genres with movies yet.</div>';
        }

        // 2. Monthly revenue — line
        init('#jambo-revenue-chart', {
            series: [{ name: 'Revenue (' + data.revenue.currency + ')', data: data.revenue.series }],
            chart: { type: 'line', height: 350, zoom: { enabled: false } },
            colors: [primary],
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 3 },
            grid: { borderColor: '#1f2738' },
            xaxis: { categories: data.revenue.labels, labels: { style: { colors: '#9aa0aa' } } },
            yaxis: {
                labels: {
                    formatter: function (v) { return Number(v).toLocaleString(); },
                    style: { colors: '#9aa0aa' },
                },
            },
        });

        // 3. New subscribers per tier — bar, one series per tier
        if (data.newSubs && data.newSubs.series.length > 0) {
            init('#jambo-new-subscribers-chart', {
                series: data.newSubs.series,
                chart: { type: 'bar', height: 350, stacked: true, toolbar: { show: false } },
                colors: tints,
                plotOptions: { bar: { borderRadius: 3, columnWidth: '55%' } },
                dataLabels: { enabled: false },
                grid: { borderColor: '#1f2738' },
                xaxis: { categories: data.newSubs.labels, labels: { style: { colors: '#9aa0aa' } } },
                yaxis: { labels: { style: { colors: '#9aa0aa' } } },
                legend: { position: 'bottom' },
            });
        }

        // 4. Most watched — stacked bar (Movies vs Series)
        init('#jambo-most-watched-chart', {
            series: data.mostWatched.series,
            chart: { type: 'bar', height: 350, stacked: true, toolbar: { show: false } },
            colors: [primary, tints[2]],
            plotOptions: { bar: { borderRadius: 3, columnWidth: '50%' } },
            dataLabels: { enabled: false },
            grid: { borderColor: '#1f2738' },
            xaxis: { categories: data.mostWatched.labels, labels: { style: { colors: '#9aa0aa' } } },
            yaxis: { labels: { style: { colors: '#9aa0aa' } } },
            legend: { position: 'bottom' },
        });

        // 5. Top rated — horizontal bar chart of average star rating
        if (data.topRated && data.topRated.series.length > 0) {
            init('#jambo-top-rated-chart', {
                series: [{ name: 'Avg rating', data: data.topRated.series }],
                chart: { type: 'bar', height: 255, toolbar: { show: false } },
                colors: [primary],
                plotOptions: { bar: { horizontal: true, borderRadius: 3, barHeight: '55%' } },
                dataLabels: { enabled: true, formatter: function (v) { return v.toFixed(2); } },
                grid: { borderColor: '#1f2738' },
                xaxis: { max: 5, labels: { style: { colors: '#9aa0aa' } } },
                yaxis: { labels: { style: { colors: '#9aa0aa' } }, categories: data.topRated.labels },
            });
        } else {
            var t = document.querySelector('#jambo-top-rated-chart');
            if (t) t.innerHTML = '<div class="text-muted py-5 text-center" style="font-size:13px;">Not enough ratings yet.<br><small>Needs 3+ ratings per title.</small></div>';
        }
    })();
</script>
@endsection
