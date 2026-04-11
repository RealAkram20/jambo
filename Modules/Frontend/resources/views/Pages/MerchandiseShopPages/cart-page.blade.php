@extends('frontend::layouts.master', ['isSweetalert' => true], ['isBreadCrumb' => true, 'title' => __('frontendheader.cart_page')])

@section('content')
    <div class="cart-page  section-padding">
        <div class="container">
            <div class="main-cart mb-3 mb-md-5 pb-0 pb-md-5">
                <ul
                    class="cart-page-items d-flex justify-content-center list-inline align-items-center gap-3 gap-md-5 flex-wrap">
                    <li class="cart-page-item active">
                        <span class="cart-pre-heading badge cart-pre-number bg-primary border-radius rounded-circle me-1"> 1
                        </span>
                        <span class="cart-page-link ">
                            {{ __('streamShop.shopping_cart') }} </span>
                    </li>
                    <li class=" d-flex justify-content-center">
                        <i class="ph ph-caret-circle-right fs-4"></i>
                    </li>
                    <li>
                        <span class=" cart-pre-number border-radius rounded-circle me-1">
                            2 </span>
                        <span class="cart-page-link ">
                            {{ __('streamShop.checkout') }} </span>
                    </li>
                    <li class="d-flex justify-content-center">
                        <i class="ph ph-caret-circle-right fs-4"></i>
                    </li>
                    <li>
                        <span class=" cart-pre-number border-radius rounded-circle me-1"> 3 </span>
                        <span class="cart-page-link ">
                            {{ __('streamShop.order_summary') }} </span>
                    </li>
                </ul>
            </div>
            <div class="row">
                <div class="col-lg-8">
                    <div class="table-responsive">
                        <table class="table cart-table">
                            <thead class="border-bottom">
                                <tr>
                                    <th scope="col" class="font-size-18 fw-medium"></th>
                                    <th scope="col" class="font-size-18 fw-medium"></th>
                                    <th scope="col" class="font-size-18 fw-500">{{ __('streamShop.product') }}</th>
                                    <th scope="col" class="font-size-18 fw-500">{{ __('streamShop.price') }}</th>
                                    <th scope="col" class="font-size-18 fw-500">{{ __('streamShop.quantity') }}</th>
                                    <th scope="col" class="font-size-18 fw-500">{{ __('streamShop.subtotal') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr data-item="list">
                                    <td>
                                        <button
                                            class="btn btn-icon btn-danger delete-btn text-end  bg-transparent text-body border-0">
                                            <span class="btn-inner">
                                                <i class="ph ph-x fw-bold text-primary"></i>
                                            </span>
                                        </button>
                                    </td>
                                    <td>
                                        <div class="product-thumbnail d-flex align-items-center gap-3">
                                            <a class="d-block" href="">
                                                <img class="avatar-80"
                                                    src="{{ asset('frontend/images/shop/product/08.webp') }}" alt="cart"
                                                    loading="lazy">
                                            </a>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="product-detail"
                                            class="product-name">{{ __('streamShop.product8') }}</a>
                                    </td>
                                    <td>
                                        <span class="fw-500">$15.00</span>
                                    </td>
                                    <td>
                                        <div class="btn-group iq-qty-btn custom-qty-btn rounded-3" data-qty="btn"
                                            role="group">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-light iq-quantity-minus text-white border-0">
                                                <i class="ph ph-minus fw-bold"></i>
                                            </button>
                                            <input type="text"
                                                class="btn btn-sm btn-outline-light input-display border-0" data-qty="input"
                                                pattern="^(0|[1-9][0-9]*)$" minlength="1" maxlength="2" value="2"
                                                title="Qty">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-light iq-quantity-plus text-white border-0">
                                                <i class="ph ph-plus fw-bold"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-500">$123.00</span>
                                    </td>
                                </tr>
                                <tr data-item="list">
                                    <td>
                                        <button
                                            class="btn btn-icon btn-danger delete-btn text-end  bg-transparent text-body border-0">
                                            <span class="btn-inner">
                                                <i class="ph ph-x fw-bold text-primary"></i>
                                            </span>
                                        </button>

                                    </td>
                                    <td>
                                        <div class="product-thumbnail d-flex align-items-center gap-3">
                                            <a class="d-block" href="">
                                                <img class="avatar-80"
                                                    src="{{ asset('frontend/images/shop/product/09.webp') }} "
                                                    alt="cart" loading="lazy">
                                            </a>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="product-detail"
                                            class="product-name">{{ __('streamShop.product9') }}</a>
                                    </td>
                                    <td>
                                        <span class="fw-500">$92.00</span>
                                    </td>
                                    <td>
                                        <div class="btn-group iq-qty-btn custom-qty-btn rounded-3" data-qty="btn"
                                            role="group">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-light iq-quantity-minus text-white border-0">
                                                <i class="ph ph-minus fw-bold"></i>
                                            </button>
                                            <input type="text"
                                                class="btn btn-sm btn-outline-light input-display border-0" data-qty="input"
                                                pattern="^(0|[1-9][0-9]*)$" minlength="1" maxlength="2" value="2"
                                                title="Qty">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-light iq-quantity-plus text-white border-0">
                                                <i class="ph ph-plus fw-bold"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-500">$92.00</span>
                                    </td>
                                </tr>
                                <tr data-item="list">
                                    <td>
                                        <button
                                            class="btn btn-icon btn-danger delete-btn text-end  bg-transparent text-body border-0">
                                            <span class="btn-inner">
                                                <i class="ph ph-x fw-bold text-primary"></i>
                                            </span>
                                        </button>

                                    </td>
                                    <td>
                                        <div class="product-thumbnail d-flex align-items-center gap-3">
                                            <a class="d-block" href="">
                                                <img class="avatar-80"
                                                    src="{{ asset('frontend/images/shop/product/10.webp') }} "
                                                    alt="cart" loading="lazy">
                                            </a>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="product-detail"
                                            class="product-name">{{ __('streamShop.product10') }}</a>
                                    </td>
                                    <td>
                                        <span class="fw-500">$90.00</span>
                                    </td>
                                    <td>
                                        <div class="btn-group iq-qty-btn custom-qty-btn rounded-3" data-qty="btn"
                                            role="group">
                                            <button type="button"   
                                                class="btn btn-sm btn-outline-light iq-quantity-minus text-white border-0">
                                                <i class="ph ph-minus fw-bold"></i>
                                            </button>
                                            <input type="text"
                                                class="btn btn-sm btn-outline-light input-display border-0"
                                                data-qty="input" pattern="^(0|[1-9][0-9]*)$" minlength="1"
                                                maxlength="2" value="2" title="Qty">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-light iq-quantity-plus text-white border-0">
                                                <i class="ph ph-plus fw-bold"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-500">$90.00</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div
                        class="coupon-main d-flex justify-content-between  gap-5 flex-wrap align-items-center pt-4 pb-5 border-bottom">
                        <div class="wrap-coupon d-flex align-items-center gap-2 flex-wrap">
                            <input class="form-control d-inline-block w-auto" name="coupon_code" type="text"
                                placeholder="{{ __('streamShop.coupon_code') }}">
                            <div class=" d-inline-block">
                                <a href="javascript:void(0)" class="btn btn-primary">{{ __('streamButtons.apply_coupon') }}
                                </a>
                            </div>
                        </div>
                        <div class="button-primary">
                            <a href="javascript:void(0)" class="btn btn-primary">{{ __('streamButtons.update_cart') }}
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="cart_totals p-4">
                        <h2 class="cart-total-title">{{ __('streamShop.cart_total') }}</h2>
                        <div class="css_prefix-woocommerce-cart-box table-responsive">
                            <table class="table">
                                <tbody>
                                    <tr class="cart-subtotal">
                                        <th class="pb-3"><span class="fw-bold">{{ __('streamShop.subtotal') }}</span>
                                        </th>
                                        <td class="pb-3 text-end">
                                            <span>$305.00</span>
                                        </td>
                                    </tr>
                                    <tr class="order-total">
                                        <th class="border-0">
                                            <span class="fw-bold"> {{ __('streamShop.total') }}</span>
                                        </th>
                                        <td class="border-0 text-end">
                                            <span class="fw-bold">$305.00</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="button-primary pt-3">
                                @include('frontend::components.widgets.custom-button', [
                                    'buttonTitle' => __('streamShop.proceed_to_checkout'),
                                    'buttonUrl' => 'chekout',
                                    'notBtncaretIcon' => true,
                                ])
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('frontend::components.widgets.quick-view')
    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}
@endsection
