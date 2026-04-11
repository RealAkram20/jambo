@extends('frontend::layouts.master', ['isSelect2' => true], ['isBreadCrumb' => true, 'title' => __('frontendheader.my_account')])

@section('content')
    <div class="section-padding service-details">
        <div class="container">
            <div class="px-3">
                <div class="row">
                    <div class="col-lg-4 col-md-4">
                        <div class="acc-left-menu mb-5 mb-lg-0 mb-md-0">
                            <div class="product-menu">
                                <ul class="list-inline m-0 nav nav-tabs flex-column bg-transparent border-0" role="tablist">
                                    <li class="pb-3 nav-item">
                                        <button class="nav-link active p-0 bg-transparent" data-bs-toggle="tab"
                                            data-bs-target="#dashboard" type="button" role="tab" aria-selected="true">
                                            <span>{{ __('streamShop.dashboard') }}</span>
                                        </button>
                                    </li>
                                    <li class="py-3 nav-item">
                                        <button class="nav-link p-0 bg-transparent" data-bs-toggle="tab"
                                            data-bs-target="#orders" type="button" role="tab" aria-selected="true">
                                            <span>{{ __('streamShop.orders') }}</span>
                                        </button>
                                    </li>
                                    <li class="py-3 nav-item">
                                        <button class="nav-link p-0 bg-transparent" data-bs-toggle="tab"
                                            data-bs-target="#downloads" type="button" role="tab" aria-selected="true">
                                            <span>{{ __('streamShop.downloads') }}</span>
                                        </button>
                                    </li>
                                    <li class="py-3 nav-item">
                                        <button class="nav-link p-0 bg-transparent" data-bs-toggle="tab"
                                            data-bs-target="#address" type="button" role="tab" aria-selected="true">
                                            <span>{{ __('streamShop.addresses') }}</span>
                                        </button>
                                    </li>
                                    <li class="py-3 nav-item">
                                        <button class="nav-link p-0 bg-transparent" data-bs-toggle="tab"
                                            data-bs-target="#account-details" type="button" role="tab"
                                            aria-selected="true">
                                            <span>{{ __('streamShop.account_details') }}</span>
                                        </button>
                                    </li>
                                    <li class="pt-3 nav-item">
                                        <button class="nav-link p-0 bg-transparent" data-bs-toggle="tab"
                                            data-bs-target="#logout" type="button" role="tab" aria-selected="true">
                                            <span>{{ __('streamAccount.logout') }}</span>
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-8 col-md-8">
                        <div class="tab-content product-menu-content" id="product-menu-content">
                            <div class="tab-pane fade show active" id="dashboard" role="tabpanel">
                                <div class="myaccount-content text-body p-4">
                                    <p class="my-3">{{ __('streamShop.greeting_hello') }}
                                        <strong>{{ __('streamShop.greeting_name') }}</strong> (not
                                        <strong>{{ __('streamShop.greeting_name') }}</strong>? <a
                                            href="login">{{ __('streamShop.greeting_logout') }}</a>)
                                    </p>
                                    <p>{{ __('streamShop.dashboard_intro') }} <a
                                            href="javascript:void(0)">{{ __('streamShop.dashboard_orders') }}</a>,
                                        {{ __('streamShop.dashboard_manageYour') }} <a
                                            href="javascript:void(0)">{{ __('streamShop.dashboard_addresses') }}</a>,
                                        {{ __('streamShop.and') }}<a href="javascript:void(0)">
                                            {{ __('streamShop.dashboard_edit_details') }}</a>.
                                    </p>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="orders" role="tabpanel">
                                <div class="orders-table text-body p-4">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr class="border-bottom">
                                                    <th class="fw-medium p-3 text-white">{{ __('streamTag.orders') }}
                                                    </th>
                                                    <th class="fw-medium p-3 text-white">{{ __('streamTag.date') }}</th>
                                                    <th class="fw-medium p-3 text-white">{{ __('streamTag.status') }}
                                                    </th>
                                                    <th class="fw-medium p-3 text-white">{{ __('streamTag.total') }}
                                                    </th>
                                                    <th class="fw-medium p-3 text-white">{{ __('streamTag.action') }}
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr class="border-bottom">
                                                    <th class="text-primary">#32604</th>
                                                    <td>October 28, 2022</td>
                                                    <td>{{ __('streamTag.cancelled') }}</td>
                                                    <td>$215.00 For 0 Items</td>
                                                    <td>
                                                        <a href="javascript:void(0)"
                                                            class="btn btn-primary px-2 text-capitalize position-relative view-btn rounded-3">
                                                            <span class="button-text">{{ __('streamTag.view') }}</span>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <tr class="border-bottom">
                                                    <th class="text-primary">#32584</th>
                                                    <td>October 27, 2022</td>
                                                    <td>{{ __('streamTag.on_hold') }}</td>
                                                    <td>$522.00 For 0 Items</td>
                                                    <td>
                                                        <a href="javascript:void(0)"
                                                            class="btn btn-primary px-2 text-capitalize position-relative view-btn rounded-3">
                                                            <span class="button-text">{{ __('streamTag.view') }}</span>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <tr class="border-bottom">
                                                    <th class="text-primary">#31756</th>
                                                    <td>October 19, 2022</td>
                                                    <td>{{ __('streamTag.processing') }}</td>
                                                    <td>$243.00 For 0 Items</td>
                                                    <td>
                                                        <a href="javascript:void(0)"
                                                            class="btn btn-primary px-2 text-capitalize position-relative view-btn rounded-3">
                                                            <span class="button-text">{{ __('streamTag.view') }}</span>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <tr class="border-bottom">
                                                    <th class="text-primary">#23663</th>
                                                    <td>October 7, 2022</td>
                                                    <td>{{ __('streamTag.completed') }}</td>
                                                    <td>$123.00 For 0 Items</td>
                                                    <td>
                                                        <a href="javascript:void(0)"
                                                            class="btn btn-primary px-2 text-capitalize position-relative view-btn rounded-3">
                                                            <span class="button-text">{{ __('streamTag.view') }}</span>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <tr class="border-bottom">
                                                    <th class="text-primary">#23612</th>
                                                    <td>October 7, 2022</td>
                                                    <td>{{ __('streamTag.completed') }}</td>
                                                    <td>$64.00 For 0 Items</td>
                                                    <td>
                                                        <a href="javascript:void(0)"
                                                            class="btn btn-primary px-2 text-capitalize position-relative view-btn rounded-3">
                                                            <span class="button-text">{{ __('streamTag.view') }}</span>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="text-primary">#19243</th>
                                                    <td>April 1, 2022</td>
                                                    <td>{{ __('streamTag.completed') }}</td>
                                                    <td>$159.00 For 0 Items</td>
                                                    <td>
                                                        <a href="javascript:void(0)"
                                                            class="btn btn-primary px-2 text-capitalize position-relative view-btn rounded-3">
                                                            <span class="button-text">{{ __('streamTag.view') }}</span>
                                                        </a>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="downloads" role="tabpanel">
                                <div class="orders-table text-body p-4">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr class="border-bottom">
                                                    <th class="fw-bolder p-3">{{ __('streamShop.product') }}</th>
                                                    <th class="fw-bolder p-3">{{ __('streamShop.download_remaining') }}
                                                    </th>
                                                    <th class="fw-bolder p-3">{{ __('streamShop.expires') }}</th>
                                                    <th class="fw-bolder p-3">{{ __('streamShop.download') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>{{ __('streamShop.electric_toothbrush') }}</td>
                                                    <td>∞</td>
                                                    <td>{{ __('streamShop.never') }}</td>
                                                    <td><a href="javascript:void(0)" class="px-2 btn btn-primary view-btn text-white"
                                                            download>{{ __('streamShop.product_demo') }}</a>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="address" role="tabpanel">
                                <div class="text-body p-4">
                                    <p class="my-3">{{ __('streamShop.default_addresses') }}</p>
                                    <div class="d-flex align-items-center justify-content-between my-5 gap-2 flex-wrap">
                                        <h4 class="mb-0">{{ __('streamShop.billing_address') }}</h4>
                                        <a href="javascript:void(0)" class="btn btn-primary position-relative rounded-3"
                                            data-bs-toggle="collapse" data-bs-target="#edit-address-1"
                                            aria-expanded="false">
                                            <span class="button-text">{{ __('streamAccount.edit') }}</span>
                                        </a>
                                    </div>
                                    <div id="edit-address-1" class="collapse">
                                        <div class="text-body mb-4">
                                            <form>
                                                <div class="form-group mb-5">
                                                    <label class="mb-2">{{ __('streamAccount.first_name') }}&nbsp; <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" name="first-name"
                                                        value="John" class="form-control"
                                                        required="required">
                                                </div>
                                                <div class="form-group mb-5">
                                                    <label class="mb-2">{{ __('streamAccount.last_name') }}&nbsp; <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" name="last-name"
                                                        value="Deo" class="form-control"
                                                        required="required">
                                                </div>
                                                <div class="form-group mb-5">
                                                    <label class="mb-2">{{ __('streamAccount.company_name') }}</label>
                                                    <input type="text" name="last-name" value=""
                                                        class="form-control">
                                                </div>
                                                <div class="form-group mb-5 my-account-select">
                                                    <label class="mb-2">{{ __('streamAccount.country_region') }}&nbsp;
                                                        <span> *</span></label>
                                                    <div class="mb-5">
                                                        <select class="select2-basic-single js-states form-control"
                                                            aria-label="select country" required="required">
                                                            <option value="" selected>
                                                                {{ __('streamAccount.chooes_country') }}</option>
                                                            <option value="1">{{ __('streamAccount.india') }}</option>
                                                            <option value="2">{{ __('streamAccount.united_kindom') }}
                                                            </option>
                                                            <option value="3">{{ __('streamAccount.united_states') }}
                                                            </option>
                                                            <option value="4">{{ __('streamAccount.australia') }}
                                                            </option>
                                                            <option value="5">{{ __('streamAccount.north_corea') }}
                                                            </option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="form-group mb-5">
                                                    <label class="mb-2">{{ __('streamAccount.street_address') }}&nbsp;
                                                        <span> *</span></label>
                                                    <input type="text" name="address"
                                                        placeholder="House number and street name" value="4517 Kentucky"
                                                        class="form-control mb-3 rounded-0" required="required">
                                                    <input type="text" name="address"
                                                        placeholder="{{ __('frontendform.apartment') }}"
                                                        class="form-control">
                                                </div>
                                                <div class="form-group mb-5">
                                                    <label class="mb-2">{{ __('streamAccount.town_city') }}&nbsp; <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" name="city" value="Navsari"
                                                        class="form-control" required="required">
                                                </div>
                                                <div class="form-group mb-5 my-account-select">
                                                    <label class="mb-2">{{ __('streamAccount.state') }}&nbsp; <span
                                                            class="text-danger">*</span></label>
                                                    <div class="mb-5">
                                                        <select class="select2-basic-single js-states form-control"
                                                            aria-label="select state">
                                                            <option value="" selected>
                                                                {{ __('streamAccount.choose') }}</option>
                                                            <option value="1">{{ __('streamAccount.gujrat') }}
                                                            </option>
                                                            <option value="2">{{ __('streamAccount.dehli') }}</option>
                                                            <option value="3">{{ __('streamAccount.goa') }}</option>
                                                            <option value="4">{{ __('streamAccount.haryana') }}
                                                            </option>
                                                            <option value="5">{{ __('streamAccount.ladakh') }}
                                                            </option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="form-group mb-5">
                                                    <label class="mb-2">{{ __('streamAccount.pin_code') }}&nbsp; <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" name="pin code" value="396321"
                                                        class="form-control" required="required">
                                                </div>
                                                <div class="form-group mb-5">
                                                    <label class="mb-2">{{ __('streamAccount.phone') }}&nbsp; <span
                                                            class="text-danger">*</span></label>
                                                    <input type="tel" name="number" value="1234567890"
                                                        class="form-control" required="required">
                                                </div>
                                                <div class="form-group mb-5">
                                                    <label class="mb-2">{{ __('streamAccount.email_address') }}&nbsp;
                                                        <span> *</span></label>
                                                    <input type="email" name="email" value="johndeo@gmail.com"
                                                        class="form-control" required="required">
                                                </div>
                                                <div class="form-group mb-5">
                                                    <a href="javascript:void(0)" class="btn btn-primary rounded-3 position-relative">
                                                        <span
                                                            class="button-text">{{ __('streamButtons.save_address') }}</span>
                                                    </a>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="edit-address w-100">
                                            <tr>
                                                <td class="label-name p-2">{{ __('streamAccount.name') }}</td>
                                                <td class="seprator p-2"><span>:</span></td>
                                                <td class="p-2">{{ __('streamAccount.john_deo') }}</td>
                                            </tr>
                                            <tr>
                                                <td class="label-name p-2">{{ __('streamAccount.company') }}</td>
                                                <td class="seprator p-2"><span>:</span></td>
                                                <td class="p-2">{{ __('streamTag.iqonic_design') }}</td>
                                            </tr>
                                            <tr>
                                                <td class="label-name p-2">{{ __('streamAccount.country') }}</td>
                                                <td class="seprator p-2"><span>:</span></td>
                                                <td class="p-2">{{ __('streamAccount.india') }}</td>
                                            </tr>
                                            <tr>
                                                <td class="label-name p-2">{{ __('streamAccount.address') }}</td>
                                                <td class="seprator p-2"><span>:</span></td>
                                                <td class="p-2">4517 {{ __('streamAccount.washington_manchester') }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="label-name p-2">{{ __('streamAccount.e_mail') }}</td>
                                                <td class="seprator p-2"><span>:</span></td>
                                                <td class="p-2">johndeo@gmail.com</td>
                                            </tr>
                                            <tr>
                                                <td class="label-name p-2">{{ __('streamAccount.phone') }}</td>
                                                <td class="seprator p-2"><span>:</span></td>
                                                <td class="p-2">1234567890</td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between my-5 gap-2 flex-wrap">
                                        <h4 class="mb-0">{{ __('streamShop.shipping_address') }}</h4>
                                        <a href="javascript:void(0)" class="btn btn-primary position-relative rounded-3"
                                            data-bs-toggle="collapse" data-bs-target="#edit-address-2"
                                            aria-expanded="false">
                                            <span class="button-text">{{ __('streamAccount.edit') }}</span>
                                        </a>
                                    </div>
                                    <div id="edit-address-2" class="collapse">
                                        <div class="text-body mb-4">
                                            <form>
                                                <div class="form-group mb-5">
                                                    <label class="mb-2">{{ __('streamAccount.first_name') }}&nbsp; <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" name="first-name"
                                                        value="John" class="form-control"
                                                        required="required">
                                                </div>
                                                <div class="form-group mb-5">
                                                    <label class="mb-2">{{ __('streamAccount.last_name') }}&nbsp; <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" name="last-name"
                                                        value="Deo" class="form-control"
                                                        required="required">
                                                </div>
                                                <div class="form-group mb-5">
                                                    <label class="mb-2">{{ __('streamAccount.company_name') }}</label>
                                                    <input type="text" name="last-name" value=""
                                                        class="form-control">
                                                </div>
                                                <div class="form-group mb-5 my-account-select">
                                                    <label class="mb-2">{{ __('streamAccount.country_region') }}&nbsp;
                                                        <span> *</span></label>
                                                    <div class="mb-5">
                                                        <select class="select2-basic-single js-states form-control"
                                                            aria-label="select country" required="required">
                                                            <option value="" selected>
                                                                {{ __('streamAccount.chooes_country') }}</option>
                                                            <option value="1">{{ __('streamAccount.india') }}</option>
                                                            <option value="2">{{ __('streamAccount.united_kindom') }}
                                                            </option>
                                                            <option value="3">{{ __('streamAccount.united_states') }}
                                                            </option>
                                                            <option value="4">{{ __('streamAccount.australia') }}
                                                            </option>
                                                            <option value="5">{{ __('streamAccount.north_corea') }}
                                                            </option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="form-group mb-5">
                                                    <label class="mb-2">{{ __('streamAccount.street_address') }}&nbsp;
                                                        <span> *</span></label>
                                                    <input type="text" name="address"
                                                        placeholder="House number and street name" value="4517 Kentucky"
                                                        class="form-control mb-3 rounded-0" required="required">
                                                    <input type="text" name="address"
                                                        placeholder="{{ __('frontendform.apartment') }}"
                                                        class="form-control">
                                                </div>
                                                <div class="form-group mb-5">
                                                    <label class="mb-2">{{ __('streamAccount.town_city') }}&nbsp; <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" name="city" value="Navsari"
                                                        class="form-control" required="required">
                                                </div>
                                                <div class="form-group mb-5 my-account-select">
                                                    <label class="mb-2">{{ __('streamAccount.state') }}&nbsp; <span
                                                            class="text-danger">*</span></label>
                                                    <div class="mb-5">
                                                        <select class="select2-basic-single js-states form-control"
                                                            aria-label="select state">
                                                            <option value="" selected>
                                                                {{ __('streamAccount.choose') }}</option>
                                                            <option value="1">{{ __('streamAccount.gujrat') }}
                                                            </option>
                                                            <option value="2">{{ __('streamAccount.dehli') }}
                                                            </option>
                                                            <option value="3">{{ __('streamAccount.goa') }}</option>
                                                            <option value="4">{{ __('streamAccount.haryana') }}
                                                            </option>
                                                            <option value="5">{{ __('streamAccount.ladakh') }}
                                                            </option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="form-group mb-5">
                                                    <label class="mb-2">{{ __('streamAccount.PIN_code') }}&nbsp; <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" name="pin code" value="396321"
                                                        class="form-control" required="required">
                                                </div>
                                                <div class="form-group mb-5">
                                                    <label class="mb-2">{{ __('streamAccount.phone') }}&nbsp; <span
                                                            class="text-danger">*</span></label>
                                                    <input type="tel" name="number" value="1234567890"
                                                        class="form-control" required="required">
                                                </div>
                                                <div class="form-group mb-5">
                                                    <label
                                                        class="mb-2">{{ __('streamAccount.email_address') }}&nbsp;<span
                                                            class="text-danger">*</span></label>
                                                    <input type="email" name="email" value="johndeo@gmail.com"
                                                        class="form-control" required="required">
                                                </div>
                                                <div class="form-group mb-5">
                                                    <a href="javascript:void(0)" class="btn btn-primary rounded-3 position-relative">
                                                        <span
                                                            class="button-text">{{ __('streamButtons.save_address') }}</span>
                                                    </a>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="edit-address w-100">
                                            <tr>
                                                <td class="label-name p-2">{{ __('streamAccount.name') }}</td>
                                                <td class="seprator p-2"><span>:</span></td>
                                                <td class="p-2">{{ __('streamAccount.john_deo') }}</td>
                                            </tr>
                                            <tr>
                                                <td class="label-name p-2">{{ __('streamAccount.company') }}</td>
                                                <td class="seprator p-2"><span>:</span></td>
                                                <td class="p-2">{{ __('streamTag.iqonic_design') }}</td>
                                            </tr>
                                            <tr>
                                                <td class="label-name p-2">{{ __('streamAccount.country') }}</td>
                                                <td class="seprator p-2"><span>:</span></td>
                                                <td class="p-2">{{ __('streamAccount.india') }}</td>
                                            </tr>
                                            <tr>
                                                <td class="label-name p-2">{{ __('streamShop.addresses') }}</td>
                                                <td class="seprator p-2"><span>:</span></td>
                                                <td class="p-2">4517 {{ __('streamAccount.washington_manchester') }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="label-name p-2">{{ __('streamAccount.e_mail') }}</td>
                                                <td class="seprator p-2"><span>:</span></td>
                                                <td class="p-2">johndeo@gmail.com</td>
                                            </tr>
                                            <tr>
                                                <td class="label-name p-2">{{ __('streamAccount.phone') }}</td>
                                                <td class="seprator p-2"><span>:</span></td>
                                                <td class="p-2">1234567890</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="account-details" role="tabpanel">
                                <div class=" p-4 text-body">
                                    <form>
                                        <div class="row">
                                            <div class="col-lg-6">
                                                <div class="form-group mb-5">
                                                    <label class="mb-2">{{ __('streamAccount.first_name') }}&nbsp; <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" name="first-name"
                                                        value="John" class="form-control"
                                                        required="required">
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="form-group mb-5">
                                                    <label class="mb-2">{{ __('streamAccount.last_name') }}&nbsp; <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" name="last-name"
                                                        value="Deo" class="form-control"
                                                        required="required">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group mb-5">
                                            <label class="mb-2">{{ __('streamAccount.display_name') }}&nbsp; <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="display-name"
                                                value="John" class="form-control"
                                                required="required">
                                        </div>
                                        <em class="d-block mb-5">This will be how your name will be displayed in the account section and in reviews</em>
                                        <div class="form-group mb-5">
                                            <label class="mb-2">{{ __('streamAccount.email_address') }}&nbsp;<span
                                                    class="text-danger">*</span></label>
                                            <input type="email" name="email" value="johndeo@gmail.com"
                                                class="form-control" required="required">
                                        </div>
                                        <h4 class="fw-normal mb-5">{{ __('streamAccount.password_change') }}</h4>
                                        <div class="form-group mb-5">
                                            <label class="mb-2">{{ __('streamAccount.current_password') }}</label>
                                            <input type="password" name="password" class="form-control">
                                        </div>
                                        <div class="form-group mb-5">
                                            <label class="mb-2">{{ __('streamAccount.new_password') }}</label>
                                            <input type="password" name="password" class="form-control">
                                        </div>
                                        <div class="form-group mb-5">
                                            <label class="mb-2">{{ __('streamAccount.confirm_password') }}</label>
                                            <input type="password" name="password" class="form-control">
                                        </div>
                                        <div class="form-group">
                                            @include('frontend::components.widgets.custom-button', [
                                                'buttonTitle' => __('streamButtons.save_changes'),
                                                'buttonUrl' => 'javascript:void(0)',
                                                'notBtncaretIcon' => true,
                                            ])
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="logout" role="tabpanel">
                                <div class="p-4">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h4 class="mb-5 text-primary">{{ __('streamButtons.login') }}</h4>
                                            <form method="post">
                                                <div class="mb-4">
                                                    <input type="text" name="user-name" class="form-control"
                                                        placeholder="{{ __('streamAccount.username_or_email') }}"
                                                        required>
                                                </div>
                                                <div class="mb-4">
                                                    <input type="password" name="pwd" class="form-control"
                                                        placeholder="{{ __('streamAccount.password') }}" required>
                                                </div>
                                                <label class="form-check mb-4">
                                                    <input class="form-check-input me-2" type="checkbox" value=""
                                                        id="flexCheckDefault">
                                                    <label class="form-check-label" for="flexCheckDefault">
                                                        {{ __('streamShop.remember_me') }}
                                                    </label>
                                                </label>
                                                @include('frontend::components.widgets.custom-button', [
                                                    'buttonTitle' => __('streamButtons.login'),
                                                    'buttonUrl' => 'javascript:void(0)',
                                                    'notBtncaretIcon' => true,
                                                ])

                                            </form>
                                            <div class="mt-3">
                                                <div>
                                                    <a href="javascript:void(0)"
                                                        class="btn text-primary p-0 text-capitalize position-relative">
                                                        <span
                                                            class="button-text">{{ __('streamAccount.lost_password') }}</span>
                                                    </a>
                                                </div>

                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <h4 class="mb-5 mt-5 mt-lg-0 mt-md-0 text-primary">
                                                {{ __('streamButtons.register') }}
                                            </h4>
                                            <form method="post">
                                                <div class="mb-4">
                                                    <input type="text" name="user-name"
                                                        placeholder="{{ __('streamAccount.username') }}"
                                                        class="form-control" required>
                                                </div>
                                                <div class="mb-4">
                                                    <input type="email" name="email-address"
                                                        placeholder="{{ __('streamAccount.email_address') }}*"
                                                        class="form-control" required>
                                                </div>
                                                <div class="mb-4">
                                                    <input type="password" name="password"
                                                        placeholder="{{ __('streamAccount.password') }}"
                                                        class="form-control" required>
                                                </div>
                                                <p class="mb-5">{{ __('streamShop.user_personal_data') }}<a
                                                        href="privacy-policy">
                                                        {{ __('streamShop.privacy_policy') }}</a>.
                                                </p>
                                                @include('frontend::components.widgets.custom-button', [
                                                    'buttonTitle' => __('streamButtons.register'),
                                                    'buttonUrl' => 'javascript:void(0)',
                                                    'notBtncaretIcon' => true,
                                                ])
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}
@endsection
