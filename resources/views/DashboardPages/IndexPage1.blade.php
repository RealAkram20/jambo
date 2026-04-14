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
                <div id="genre-chart" class="d-flex justify-content-center">
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
                <div id="total-revenue-subscription"></div>
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
                <div id="new-subcriber"></div>
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
                <div id="d-activity"></div>
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
                    <div class="dropdown">
                        <span class="text-primary" id="dropdownMenuButton5" data-bs-toggle="dropdown">
                            {{ __('dashboard.view-all') }}
                        </span>
                        <div class="dropdown-menu dropdown-menu-end iq-dropdown toggle"
                            aria-labelledby="dropdownMenuButton5">
                            <a class="dropdown-item" href="#"><i class="ri-eye-fill me-2"></i>View</a>
                            <a class="dropdown-item" href="#"><i class="ri-delete-bin-6-fill me-2"></i>Delete</a>
                            <a class="dropdown-item" href="#"><i class="ri-pencil-fill me-2"></i>Edit</a>
                            <a class="dropdown-item" href="#"><i class="ri-printer-fill me-2"></i>Print</a>
                            <a class="dropdown-item" href="#"><i class="ri-file-download-fill me-2"></i>Download</a>
                        </div>
                    </div>
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
                        <div id="top-rated-chart" class="d-flex align-items-center justify-content-center"></div>
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
                            <div class="dropdown">
                                <span class="text-primary" id="6" data-bs-toggle="dropdown">
                                    {{ __('dashboard.view-all') }}
                                </span>
                                <div class="dropdown-menu dropdown-menu-end iq-dropdown toggle">
                                    <a class="dropdown-item" href="#"><i class="ri-eye-fill me-2"></i>View</a>
                                    <a class="dropdown-item" href="#"><i
                                            class="ri-delete-bin-6-fill me-2"></i>Delete</a>
                                    <a class="dropdown-item" href="#"><i class="ri-pencil-fill me-2"></i>Edit</a>
                                    <a class="dropdown-item" href="#"><i class="ri-printer-fill me-2"></i>Print</a>
                                    <a class="dropdown-item" href="#"><i
                                            class="ri-file-download-fill me-2"></i>Download</a>
                                </div>
                            </div>
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
@endsection

@push('after-scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/apexcharts/3.40.0/apexcharts.min.js"
        integrity="sha512-Kr1p/vGF2i84dZQTkoYZ2do8xHRaiqIa7ysnDugwoOcG0SbIx98erNekP/qms/hBDiBxj336//77d0dv53Jmew=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="{{ asset('Dashboard/js/apexcharts.js') }}"></script>
    <script src="{{ asset('Dashboard/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('Dashboard/js/chart-custom.js') }}"></script>
    <script src="{{ asset('Dashboard/js/countdown.min.js') }}"></script>
    <script src="{{ asset('Dashboard/js/custom.js') }}"></script>
    <script src="{{ asset('Dashboard/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('Dashboard/js/flatpickr.js') }}"></script>
    <script src="{{ asset('Dashboard/js/jquery.appear.js') }}"></script>
    <script src="{{ asset('Dashboard/js/jquery.counterup.min.js') }}"></script>
    <script src="{{ asset('Dashboard/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('Dashboard/js/jquery.min.js') }}"></script>
    <script src="{{ asset('Dashboard/js/masonry.pkgd.min.js') }}"></script>
    <script src="{{ asset('Dashboard/js/owl.carousel.js') }}"></script>
    <script src="{{ asset('Dashboard/js/popper.min.js') }}"></script>
    <script src="{{ asset('Dashboard/js/rtl.js') }}"></script>
    <script src="{{ asset('Dashboard/js/smooth-scrollbae.js') }}"></script>
    <script src="{{ asset('Dashboard/js/waypoints.min.js') }}"></script>
    <script src="{{ asset('Dashboard/js/wow.min.js') }}"></script>
@endpush
