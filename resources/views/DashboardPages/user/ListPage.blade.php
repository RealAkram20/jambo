@extends('layouts.app', ['module_title' => 'User List', 'title' => 'User List', 'active' => 'user-list-mini', 'isSweetalert' => true])

@section('content')
<div>
    <div class="row">
        <div class="col-sm-12">
            <div class="streamit-wraper-table">
                <div class="card-header d-flex justify-content-between gap-3 flex-wrap align-items-center mb-4">
                    <h2 class="episode-playlist-title wp-heading-inline">
                        <span class="position-relative ">
                            {{__('dashboard.user_list')}} </span>
                    </h2>
                </div>
                <div class="table-view table-space">
                    <table id="seasonTable" class="data-tables table custom-table data-table-one custom-table-height"
                        role="grid" data-toggle="data-table1">
                        <thead>
                            <tr class="ligth">
                                <th>Profile</th>
                                <th>Name</th>
                                <th>Contact</th>
                                <th>Email</th>
                                <th>Country</th>
                                <th>Status</th>
                                <th>Company</th>
                                <th>Join Date</th>
                                <th style="min-width: 100px">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><img class="bg-soft-primary rounded img-fluid avatar-40 me-3"
                                        src="{{ asset('dashboard/images/user/01.jpg') }}" alt="profile" loading="lazy">
                                </td>
                                <td>Anna Sthesia</td>
                                <td>(760) 756 7568</td>
                                <td>annasthesia@gmail.com</td>
                                <td>USA</td>
                                <td><span class="badge bg-primary">Active</span></td>
                                <td>Acme Corporation</td>
                                <td>2019/12/01</td>
                                <td>
                                    <div class="flex align-items-center list-user-action">
                                        <a class="btn btn-sm btn-icon btn-success-subtle rounded" data-bs-toggle="tooltip"
                                            data-placement="top" title="" data-bs-original-title="Add" href="javascript:void(0);">
                                            <span class="btn-inner">
                                                <i class="ph ph-pencil-simple fs-6"></i>
                                            </span>
                                        </a>
                                        <a class="btn btn-sm btn-icon btn-danger-subtle rounded delete-btn" data-bs-toggle="tooltip"
                                            data-placement="top" title="" data-bs-original-title="Delete" href="javascript:void(0);">
                                            <span class="btn-inner">
                                                <i class="ph ph-trash-simple fs-6"></i>
                                            </span>
                                        </a>
                                        <a class="btn btn-sm btn-icon btn-info-subtle rounded" data-bs-toggle="tooltip"
                                            data-placement="top" title="" data-bs-original-title="View" href="javascript:void(0);">
                                            <span class="btn-inner">
                                                <i class="ph ph-eye fs-6"></i>
                                            </span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><img class="bg-soft-primary rounded img-fluid avatar-40 me-3"
                                        src="{{ asset('dashboard/images/user/02.jpg') }} " alt="profile"
                                        loading="lazy"></td>
                                <td>Brock Lee</td>
                                <td>+62 5689 458 658</td>
                                <td>brocklee@gmail.com</td>
                                <td>Indonesia</td>
                                <td><span class="badge bg-primary">Active</span></td>
                                <td>Soylent Corp</td>
                                <td>2019/12/01</td>
                                <td>
                                    <div class="flex align-items-center list-user-action">
                                        <a class="btn btn-sm btn-icon btn-success-subtle rounded" data-bs-toggle="tooltip"
                                            data-placement="top" title="" data-bs-original-title="Add" href="javascript:void(0);">
                                            <span class="btn-inner">
                                                <i class="ph ph-pencil-simple fs-6"></i>
                                            </span>
                                        </a>
                                        <a class="btn btn-sm btn-icon btn-danger-subtle rounded delete-btn" data-bs-toggle="tooltip"
                                            data-placement="top" title="" data-bs-original-title="Delete" href="javascript:void(0);">
                                            <span class="btn-inner">
                                                <i class="ph ph-trash-simple fs-6"></i>
                                            </span>
                                        </a>
                                        <a class="btn btn-sm btn-icon btn-info-subtle rounded" data-bs-toggle="tooltip"
                                            data-placement="top" title="" data-bs-original-title="View" href="javascript:void(0);">
                                            <span class="btn-inner">
                                                <i class="ph ph-eye fs-6"></i>
                                            </span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><img class="bg-soft-primary rounded img-fluid avatar-40 me-3"
                                        src="{{ asset('dashboard/images/user/03.jpg') }}" alt="profile" loading="lazy">
                                </td>
                                <td>Dan Druff</td>
                                <td>+55 6523 456 856</td>
                                <td>dandruff@gmail.com</td>
                                <td>Brazil</td>
                                <td><span class="badge bg-warning">Pending</span></td>
                                <td>Umbrella Corporation</td>
                                <td>2019/12/01</td>
                                <td>
                                    <div class="flex align-items-center list-user-action">
                                        <a class="btn btn-sm btn-icon btn-success-subtle rounded" data-bs-toggle="tooltip"
                                            data-placement="top" title="" data-bs-original-title="Add" href="javascript:void(0);">
                                            <span class="btn-inner">
                                                <i class="ph ph-pencil-simple fs-6"></i>
                                            </span>
                                        </a>
                                        <a class="btn btn-sm btn-icon btn-danger-subtle rounded delete-btn" data-bs-toggle="tooltip"
                                            data-placement="top" title="" data-bs-original-title="Delete" href="javascript:void(0);">
                                            <span class="btn-inner">
                                                <i class="ph ph-trash-simple fs-6"></i>
                                            </span>
                                        </a>
                                        <a class="btn btn-sm btn-icon btn-info-subtle rounded" data-bs-toggle="tooltip"
                                            data-placement="top" title="" data-bs-original-title="View" href="javascript:void(0);">
                                            <span class="btn-inner">
                                                <i class="ph ph-eye fs-6"></i>
                                            </span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><img class="bg-soft-primary rounded img-fluid avatar-40 me-3"
                                        src="{{ asset('dashboard/images/user/04.jpg') }}" alt="profile" loading="lazy">
                                </td>
                                <td>Hans Olo</td>
                                <td>+91 2586 253 125</td>
                                <td>hansolo@gmail.com</td>
                                <td>India</td>
                                <td><span class="badge bg-danger">Inactive</span></td>
                                <td>Vehement Capital</td>
                                <td>2019/12/01</td>
                                <td>
                                    <div class="flex align-items-center list-user-action">
                                        <a class="btn btn-sm btn-icon btn-success-subtle rounded" data-bs-toggle="tooltip"
                                            data-placement="top" title="" data-bs-original-title="Add" href="javascript:void(0);">
                                            <span class="btn-inner">
                                                <i class="ph ph-pencil-simple fs-6"></i>
                                            </span>
                                        </a>
                                        <a class="btn btn-sm btn-icon btn-danger-subtle rounded delete-btn" data-bs-toggle="tooltip"
                                            data-placement="top" title="" data-bs-original-title="Delete" href="javascript:void(0);">
                                            <span class="btn-inner">
                                                <i class="ph ph-trash-simple fs-6"></i>
                                            </span>
                                        </a>
                                        <a class="btn btn-sm btn-icon btn-info-subtle rounded" data-bs-toggle="tooltip"
                                            data-placement="top" title="" data-bs-original-title="View" href="javascript:void(0);">
                                            <span class="btn-inner">
                                                <i class="ph ph-eye fs-6"></i>
                                            </span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><img class="bg-soft-primary rounded img-fluid avatar-40 me-3"
                                        src="{{ asset('dashboard/images/user/05.jpg') }}" alt="profile"
                                        loading="lazy"></td>
                                <td>Lynn Guini</td>
                                <td>+27 2563 456 589</td>
                                <td>lynnguini@gmail.com</td>
                                <td>Africa</td>
                                <td><span class="badge bg-primary">Active</span></td>
                                <td>Massive Dynamic</td>
                                <td>2019/12/01</td>
                                <td>
                                    <div class="flex align-items-center list-user-action">
                                        <a class="btn btn-sm btn-icon btn-success-subtle rounded" data-bs-toggle="tooltip"
                                            data-placement="top" title="" data-bs-original-title="Add" href="javascript:void(0);">
                                            <span class="btn-inner">
                                                <i class="ph ph-pencil-simple fs-6"></i>
                                            </span>
                                        </a>
                                        <a class="btn btn-sm btn-icon btn-danger-subtle rounded delete-btn" data-bs-toggle="tooltip"
                                            data-placement="top" title="" data-bs-original-title="Delete" href="javascript:void(0);">
                                            <span class="btn-inner">
                                                <i class="ph ph-trash-simple fs-6"></i>
                                            </span>
                                        </a>
                                        <a class="btn btn-sm btn-icon btn-info-subtle rounded" data-bs-toggle="tooltip"
                                            data-placement="top" title="" data-bs-original-title="View" href="javascript:void(0);">
                                            <span class="btn-inner">
                                                <i class="ph ph-eye fs-6"></i>
                                            </span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><img class="bg-soft-primary rounded img-fluid avatar-40 me-3"
                                        src="{{ asset('dashboard/images/user/06.jpg') }}" alt="profile"
                                        loading="lazy"></td>
                                <td>Eric Shun</td>
                                <td>+55 25685 256 589</td>
                                <td>ericshun@gmail.com</td>
                                <td>Brazil</td>
                                <td><span class="badge bg-warning">Pending</span></td>
                                <td>Globex Corporation</td>
                                <td>2019/12/01</td>
                                <td>
                                    <div class="flex align-items-center list-user-action">
                                        <a class="btn btn-sm btn-icon btn-success-subtle rounded" data-bs-toggle="tooltip"
                                            data-placement="top" title="" data-bs-original-title="Add" href="javascript:void(0);">
                                            <span class="btn-inner">
                                                <i class="ph ph-pencil-simple fs-6"></i>
                                            </span>
                                        </a>
                                        <a class="btn btn-sm btn-icon btn-danger-subtle rounded delete-btn" data-bs-toggle="tooltip"
                                            data-placement="top" title="" data-bs-original-title="Delete" href="javascript:void(0);">
                                            <span class="btn-inner">
                                                <i class="ph ph-trash-simple fs-6"></i>
                                            </span>
                                        </a>
                                        <a class="btn btn-sm btn-icon btn-info-subtle rounded" data-bs-toggle="tooltip"
                                            data-placement="top" title="" data-bs-original-title="View" href="javascript:void(0);">
                                            <span class="btn-inner">
                                                <i class="ph ph-eye fs-6"></i>
                                            </span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><img class="bg-soft-primary rounded img-fluid avatar-40 me-3"
                                        src="{{ asset('dashboard/images/user/03.jpg') }}" alt="profile"
                                        loading="lazy"></td>
                                <td>aaronottix</td>
                                <td>(760) 765 2658</td>
                                <td>budwiser@ymail.com</td>
                                <td>USA</td>
                                <td><span class="badge bg-info">Hold</span></td>
                                <td>Acme Corporation</td>
                                <td>2019/12/01</td>
                                <td>
                                    <div class="flex align-items-center list-user-action">
                                        <a class="btn btn-sm btn-icon btn-success-subtle rounded" data-bs-toggle="tooltip"
                                            data-placement="top" title="" data-bs-original-title="Add" href="javascript:void(0);">
                                            <span class="btn-inner">
                                                <i class="ph ph-pencil-simple fs-6"></i>
                                            </span>
                                        </a>
                                        <a class="btn btn-sm btn-icon btn-danger-subtle rounded delete-btn" data-bs-toggle="tooltip"
                                            data-placement="top" title="" data-bs-original-title="Delete" href="javascript:void(0);">
                                            <span class="btn-inner">
                                                <i class="ph ph-trash-simple fs-6"></i>
                                            </span>
                                        </a>
                                        <a class="btn btn-sm btn-icon btn-info-subtle rounded" data-bs-toggle="tooltip"
                                            data-placement="top" title="" data-bs-original-title="View" href="javascript:void(0);">
                                            <span class="btn-inner">
                                                <i class="ph ph-eye fs-6"></i>
                                            </span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><img class="bg-soft-primary rounded img-fluid avatar-40 me-3"
                                        src="{{ asset('dashboard/images/user/05.jpg') }}" alt="profile"
                                        loading="lazy"></td>
                                <td>Marge Arita</td>
                                <td>+27 5625 456 589</td>
                                <td>margearita@gmail.com</td>
                                <td>Africa</td>
                                <td><span class="badge bg-success">Complete</span></td>
                                <td>Vehement Capital</td>
                                <td>2019/12/01</td>
                                <td>
                                    <div class="flex align-items-center list-user-action">
                                        <a class="btn btn-sm btn-icon btn-success-subtle rounded" data-bs-toggle="tooltip"
                                            data-placement="top" title="" data-bs-original-title="Add" href="javascript:void(0);">
                                            <span class="btn-inner">
                                                <i class="ph ph-pencil-simple fs-6"></i>
                                            </span>
                                        </a>
                                        <a class="btn btn-sm btn-icon btn-danger-subtle rounded delete-btn" data-bs-toggle="tooltip"
                                            data-placement="top" title="" data-bs-original-title="Delete" href="javascript:void(0);">
                                            <span class="btn-inner">
                                                <i class="ph ph-trash-simple fs-6"></i>
                                            </span>
                                        </a>
                                        <a class="btn btn-sm btn-icon btn-info-subtle rounded" data-bs-toggle="tooltip"
                                            data-placement="top" title="" data-bs-original-title="View" href="javascript:void(0);">
                                            <span class="btn-inner">
                                                <i class="ph ph-eye fs-6"></i>
                                            </span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><img class="bg-soft-primary rounded img-fluid avatar-40 me-3"
                                        src="{{ asset('dashboard/images/user/02.jpg') }}" alt="profile"
                                        loading="lazy"></td>
                                <td>Bill Dabear</td>
                                <td>+55 2563 456 589</td>
                                <td>billdabear@gmail.com</td>
                                <td>Brazil</td>
                                <td><span class="badge bg-primary">active</span></td>
                                <td>Massive Dynamic</td>
                                <td>2019/12/01</td>
                                <td>
                                    <div class="flex align-items-center list-user-action">
                                        <a class="btn btn-sm btn-icon btn-success-subtle rounded" data-bs-toggle="tooltip"
                                            data-placement="top" title="" data-bs-original-title="Add" href="javascript:void(0);">
                                            <span class="btn-inner">
                                                <i class="ph ph-pencil-simple fs-6"></i>
                                            </span>
                                        </a>
                                        <a class="btn btn-sm btn-icon btn-danger-subtle rounded delete-btn" data-bs-toggle="tooltip"
                                            data-placement="top" title="" data-bs-original-title="Delete" href="javascript:void(0);">
                                            <span class="btn-inner">
                                                <i class="ph ph-trash-simple fs-6"></i>
                                            </span>
                                        </a>
                                        <a class="btn btn-sm btn-icon btn-info-subtle rounded" data-bs-toggle="tooltip"
                                            data-placement="top" title="" data-bs-original-title="View" href="javascript:void(0);">
                                            <span class="btn-inner">
                                                <i class="ph ph-eye fs-6"></i>
                                            </span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection