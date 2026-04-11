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
                            <h1 class="fw-semibold card-details-title">15k+</h1>
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
                            <h1 class="fw-semibold card-details-title">05k+</h1>
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
                            <h1 class="fw-semibold card-details-title">10k+</h1>
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
                            <h1 class="fw-semibold card-details-title">15k+</h1>
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
                            <h1 class="fw-semibold card-details-title">05k+</h1>
                            <p class="mb-0 fs-6">{{ __('dashboard.total-tvshow') }}</p>
                        </div>

                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="card">
                    <div class="card-body">
                        <div class="icon-space mb-5">
                            <i class="ph ph-video fs-1"></i>
                        </div>
                        <div class="card-details">
                            <h1 class="fw-semibold card-details-title">10k+</h1>
                            <p class="mb-0 fs-6">{{ __('dashboard.total-video') }}</p>
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
                            <tr>
                                <td>
                                    <div class="d-flex gap-3 align-items-center">
                                        <img class="avatar avatar-40 rounded-pill"
                                            src="{{asset('dashboard/images/author/01.png')}}" alt="profile">
                                        <div class="text-start">
                                            <h6 class="m-0">John Doe</h6>
                                            <small>john@gmail.com</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    9th March 2025
                                </td>
                                <td>
                                    Movies
                                </td>
                                <td>
                                    <div class="d-flex gap-3 align-items-center">
                                        <div class="star-rating">
                                            <span class="star filled text-warning">
                                                <i class="ph ph-fill ph-star"></i>
                                            </span>
                                            <span class="star filled text-warning">
                                                <i class="ph ph-fill ph-star"></i>
                                            </span>
                                            <span class="star filled text-warning">
                                                <i class="ph ph-fill ph-star"></i>
                                            </span>
                                            <span class="star filled text-warning">
                                                <i class="ph ph-fill ph-star"></i>
                                            </span>
                                            <span class="star filled text-warning">
                                                <i class="ph ph-fill ph-star"></i>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="d-flex gap-3 align-items-center">
                                        <img class="avatar avatar-40 rounded-pill"
                                            src="{{asset('dashboard/images/author/02.png')}}" alt="profile">
                                        <div class="text-start">
                                            <h6 class="m-0">Mega Links</h6>
                                            <small>megalink156@gmail.com</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    7th March 2025
                                </td>
                                <td>
                                    Movies
                                </td>
                                <td>
                                    <div class="d-flex gap-3 align-items-center">
                                        <div class="star-rating">
                                            <span class="star filled text-warning">
                                                <i class="ph ph-fill ph-star"></i>
                                            </span>
                                            <span class="star filled text-warning">
                                                <i class="ph ph-fill ph-star"></i>
                                            </span>
                                            <span class="star filled text-warning">
                                                <i class="ph ph-fill ph-star"></i>
                                            </span>
                                            <span class="star filled text-warning">
                                                <i class="ph ph-fill ph-star"></i>
                                            </span>
                                            <span class="star filled text-warning">
                                                <i class="ph ph-fill ph-star"></i>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="d-flex gap-3 align-items-center">
                                        <img class="avatar avatar-40 rounded-pill"
                                            src="{{asset('dashboard/images/author/03.png')}}" alt="profile">
                                        <div class="text-start">
                                            <h6 class="m-0">Felix Harris</h6>
                                            <small>felix@gmail.com</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    21st September 2024
                                </td>
                                <td>
                                    Tvshow
                                </td>
                                <td>
                                    <div class="d-flex gap-3 align-items-center">
                                        <div class="star-rating">
                                            <span class="star filled text-warning">
                                                <i class="ph ph-fill ph-star"></i>
                                            </span>
                                            <span class="star filled text-warning">
                                                <i class="ph ph-fill ph-star"></i>
                                            </span>
                                            <span class="star filled text-warning">
                                                <i class="ph ph-fill ph-star"></i>
                                            </span>
                                            <span class="star filled text-warning">
                                                <i class="ph ph-fill ph-star"></i>
                                            </span>
                                            <span class="star filled text-warning">
                                                <i class="ph ph-fill ph-star"></i>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="d-flex gap-3 align-items-center">
                                        <img class="avatar avatar-40 rounded-pill"
                                            src="{{asset('dashboard/images/author/04.png')}}" alt="profile">
                                        <div class="text-start">
                                            <h6 class="m-0">Stella Green</h6>
                                            <small>stella@gmail.com</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    9th March 2025
                                </td>
                                <td>
                                    Tvshow
                                </td>
                                <td>
                                    <div class="d-flex gap-3 align-items-center">
                                        <div class="star-rating">
                                            <span class="star filled text-warning">
                                                <i class="ph ph-fill ph-star"></i>
                                            </span>
                                            <span class="star filled text-warning">
                                                <i class="ph ph-fill ph-star"></i>
                                            </span>
                                            <span class="star filled text-warning">
                                                <i class="ph ph-fill ph-star"></i>
                                            </span>
                                            <span class="star filled text-warning">
                                                <i class="ph ph-fill ph-star"></i>
                                            </span>
                                            <span class="star filled text-warning">
                                                <i class="ph ph-fill ph-star"></i>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="d-flex gap-3 align-items-center">
                                        <img class="avatar avatar-40 rounded-pill"
                                            src="{{asset('dashboard/images/author/05.png')}}" alt="profile">
                                        <div class="text-start">
                                            <h6 class="m-0">Deborah Thomas</h6>
                                            <small>deborah@gmail.com</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    12th March 2024
                                </td>
                                <td>
                                    Tvshow
                                </td>
                                <td>
                                    <div class="d-flex gap-3 align-items-center">
                                        <div class="star-rating">
                                            <span class="star filled text-warning">
                                                <i class="ph ph-fill ph-star"></i>
                                            </span>
                                            <span class="star filled text-warning">
                                                <i class="ph ph-fill ph-star"></i>
                                            </span>
                                            <span class="star filled text-warning">
                                                <i class="ph ph-fill ph-star"></i>
                                            </span>
                                            <span class="star filled text-warning">
                                                <i class="ph ph-fill ph-star"></i>
                                            </span>
                                            <span class="star filled text-warning">
                                                <i class="ph ph-fill ph-star"></i>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="d-flex gap-3 align-items-center">
                                        <img class="avatar avatar-40 rounded-pill"
                                            src="{{asset('dashboard/images/author/06.png')}}" alt="profile">
                                        <div class="text-start">
                                            <h6 class="m-0">Jorge Perez</h6>
                                            <small>jorge@gmail.com</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    9th September 2024
                                </td>
                                <td>
                                    Tvshow
                                </td>
                                <td>
                                    <div class="d-flex gap-3 align-items-center">
                                        <div class="star-rating">
                                            <span class="star filled text-warning">
                                                <i class="ph ph-fill ph-star"></i>
                                            </span>
                                            <span class="star filled text-warning">
                                                <i class="ph ph-fill ph-star"></i>
                                            </span>
                                            <span class="star filled text-warning">
                                                <i class="ph ph-fill ph-star"></i>
                                            </span>
                                            <span class="star filled text-warning">
                                                <i class="ph ph-fill ph-star"></i>
                                            </span>
                                            <span class="star filled text-warning">
                                                <i class="ph ph-fill ph-star"></i>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
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
                                    <tr>
                                        <td>
                                            <div class="d-flex gap-3 align-items-center">
                                                <img class="avatar avatar-40 rounded-pill"
                                                    src="{{asset('dashboard/images/author/01.png')}}" alt="profile">
                                                <div class="text-start">
                                                    <h6 class="m-0">John Doe</h6>
                                                    <small>john@gmail.com</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            12th March 2025
                                        </td>
                                        <td>
                                            Basic
                                        </td>

                                        <td>
                                            $5.00
                                        </td>
                                        <td>
                                            1 week
                                        </td>
                                        <td>
                                            Stripe
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="d-flex gap-3 align-items-center">
                                                <img class="avatar avatar-40 rounded-pill"
                                                    src="{{asset('dashboard/images/author/02.png')}}" alt="profile">
                                                <div class="text-start">
                                                    <h6 class="m-0">Mega Links</h6>
                                                    <small>megalink156@gmail.com</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            10th March 2025
                                        </td>
                                        <td>
                                            Basic
                                        </td>

                                        <td>
                                            $5.00
                                        </td>
                                        <td>
                                            1 week
                                        </td>
                                        <td>
                                            Stripe
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="d-flex gap-3 align-items-center">
                                                <img class="avatar avatar-40 rounded-pill"
                                                    src="{{asset('dashboard/images/author/03.png')}}" alt="profile">
                                                <div class="text-start">
                                                    <h6 class="m-0">Felix Harris</h6>
                                                    <small>felix@gmail.com</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            10th March 2025
                                        </td>
                                        <td>
                                            Basic
                                        </td>

                                        <td>
                                            $5.00
                                        </td>
                                        <td>
                                            1 week
                                        </td>
                                        <td>
                                            Stripe
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="d-flex gap-3 align-items-center">
                                                <img class="avatar avatar-40 rounded-pill"
                                                    src="{{asset('dashboard/images/author/04.png')}}" alt="profile">
                                                <div class="text-start">
                                                    <h6 class="m-0">Stella Green</h6>
                                                    <small>stella@gmail.com</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            10th March 2025
                                        </td>
                                        <td>
                                            Basic
                                        </td>

                                        <td>
                                            $5.00
                                        </td>
                                        <td>
                                            1 week
                                        </td>
                                        <td>
                                            Stripe
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="d-flex gap-3 align-items-center">
                                                <img class="avatar avatar-40 rounded-pill"
                                                    src="{{asset('dashboard/images/author/05.png')}}" alt="profile">
                                                <div class="text-start">
                                                    <h6 class="m-0">Deborah Thomas</h6>
                                                    <small>deborah@gmail.com</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            10th March 2025
                                        </td>
                                        <td>
                                            Basic
                                        </td>

                                        <td>
                                            $5.00
                                        </td>
                                        <td>
                                            1 week
                                        </td>
                                        <td>
                                            Stripe
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="d-flex gap-3 align-items-center">
                                                <img class="avatar avatar-40 rounded-pill"
                                                    src="{{asset('dashboard/images/author/06.png')}}" alt="profile">
                                                <div class="text-start">
                                                    <h6 class="m-0">Jorge Perez</h6>
                                                    <small>jorge@gmail.com</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            10th March 2025
                                        </td>
                                        <td>
                                            Elite Plan
                                        </td>

                                        <td>
                                            $80.00
                                        </td>
                                        <td>
                                            1 year
                                        </td>
                                        <td>
                                            Stripe
                                        </td>
                                    </tr>
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
