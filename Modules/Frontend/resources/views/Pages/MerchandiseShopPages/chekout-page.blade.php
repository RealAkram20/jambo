@extends('frontend::layouts.master', ['isSelect2' => true], ['isBreadCrumb' => true, 'title' => __('frontendheader.checkout_page')])

@section('content')
    <div class="checkout-page  section-padding">
        <div class="container">
            <div class="main-cart mb-3 mb-md-5 pb-0 pb-md-5">
                <ul
                    class="cart-page-items d-flex justify-content-center list-inline align-items-center gap-3 gap-md-5 flex-wrap">
                    <li class="cart-page-item">
                        <span class=" cart-pre-number  border-radius rounded-circle me-1"> 1 </span>
                        <span class="cart-page-link ">
                            {{ __('streamShop.shopping_cart') }}</span>
                    </li>
                    <li class="cart-page-item d-flex justify-content-center">
                        <i class="ph ph-caret-circle-right fs-4"></i>
                    </li>
                    <li class="cart-page-item  active">
                        <span class="cart-pre-heading badge cart-pre-number bg-primary border-radius rounded-circle me-1">
                            2 </span>
                        <span class="cart-page-link ">{{ __('streamShop.checkout') }}</span>
                    </li>
                    <li class="cart-page-item d-flex justify-content-center">
                        <i class="ph ph-caret-circle-right fs-4"></i>
                    </li>
                    <li class="cart-page-item ">
                        <span class=" cart-pre-number  border-radius rounded-circle me-1"> 3 </span>
                        <span class="cart-page-link ">
                            {{ __('streamShop.order_summary') }} </span>
                    </li>
                </ul>
            </div>
            <div class="mb-5 woocommerce-info-coupon">
                <div class="d-flex align-items-center justify-content-center gap-3 flex-wrap">
                    <div class="woocommerce-info">
                        <span class="text-body ps-2"> {{ __('streamShop.have_a_coupon') }}</span>
                        <a href="javascript:void(0)" data-bs-toggle="collapse"
                            data-bs-target="#apply-coupon">{{ __('streamShop.enter_code') }}</a>
                    </div>
                </div>
                <div id="apply-coupon" class="collapse mt-5">
                    <form class="checkout-coupon">
                        <p class="mt-0">{{ __('streamShop.apply_coupon_code') }}</p>
                        <div class="iq-checkout-coupon">
                            <input name="coupon-code" type="text" required="required"
                                placeholder="{{ __('streamShop.coupon_code') }}" class="form-control">
                            <div class="mt-4">
                                @include('frontend::components.widgets.custom-button', [
                                    'buttonTitle' => __('streamButtons.apply_coupon'),
                                    'buttonUrl' => '#',
                                    'notBtncaretIcon' => true,
                                ])
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6">
                    <form action="{{ route('frontend.order_tracking') }}">
                        <h3 class="mb-4">{{ __('streamShop.billing_details') }}</h3>
                        <div class="form-group">
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="first-name" class="form-label">{{ __('streamAccount.first_name') }} <span
                                            class="text-primary">*</span></label>
                                    <input name="first-name" type="text" required="required" class="form-control"
                                        id="first-name">
                                </div>
                                <div class="col-md-6">
                                    <label for="last-name" class="form-label">{{ __('streamAccount.last_name') }} <span
                                            class="text-primary">*</span></label>
                                    <input name="last-name" type="text" required="required" class="form-control"
                                        id="last-name">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="company-name" class="form-label">{{ __('streamAccount.company_name') }}</span></label>
                            <input name="billing-company" type="text" required="required" class="form-control"
                                id="company-name">
                        </div>
                        <div class="form-group">
                            <label for="first-name" class="form-label">{{ __('streamAccount.city_region') }} <span
                                    class="text-primary">*</span></label>
                            <select class="select2-basic-single form-control" aria-label="Default select example">
                                <option selected>{{ __('streamAccount.india') }}</option>
                                <option value="1">{{ __('streamAccount.united_kindom') }}</option>
                                <option value="2">{{ __('streamAccount.united_states') }}</option>
                                <option value="3">{{ __('streamAccount.australia') }}</option>
                                <option value="1">{{ __('streamAccount.north_corea') }}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="street-address" class="form-label">{{ __('streamAccount.street_address') }} <span
                                    class="text-primary">*</span></label>
                            <input name="billing-address" type="text" required="required"
                                placeholder="{{ __('frontendform.billing_address') }}" class="form-control mb-2">
                            <input name="billing-address2" type="text" required="required"
                                placeholder="{{ __('frontendform.apartment') }}" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="town-city" class="form-label">{{ __('streamAccount.town_city') }} <span
                                    class="text-primary">*</span></label>
                            <input name="city" type="text" required="required" class="form-control" id="town-city">
                        </div>
                        <div class="form-group">
                            <label for="state" class="form-label">{{ __('streamAccount.state') }} <span
                                    class="text-primary">*</span></label>
                            <select class="select2-basic-single js-states form-control"
                                aria-label="Default select example">
                                <option selected>{{ __('streamAccount.colorado') }}</option>
                                <option value="2">{{ __('streamAccount.alaska') }}</option>
                                <option value="1">{{ __('streamAccount.hawai') }}</option>
                                <option value="3">{{ __('streamAccount.texas') }}</option>
                                <option value="1">{{ __('streamAccount.washington_manchester') }}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="zip-code" class="form-label">{{ __('streamAccount.pin_code') }} <span
                                    class="text-primary">*</span></label>
                            <input name="postcode" type="text" required="required" class="form-control"
                                id="zip-code">
                        </div>
                        <div class="form-group">
                            <label for="phone-group" class="form-label">{{ __('streamAccount.phone') }} <span
                                    class="text-primary">*</span></label>
                            <input name="phone" type="tel" required="required" class="form-control"
                                id="phone-group">
                        </div>
                        <div class="form-group">
                            <label for="email-address" class="form-label">{{ __('streamAccount.email_address') }} <span
                                    class="text-primary">*</span></label>
                            <input name="billing-company" type="email" required="required"
                                class="form-control rounded-0 mb-5" id="email-address">
                        </div>
                    </form>
                </div>
                <div class="col-lg-6">
                    <h3 class="mb-4">{{ __('streamShop.additional_information') }}</h3>
                    <div class="form-group">
                        <label class="mb-2">{{ __('streamShop.order_notes') }}</label>
                        <textarea name="your-message" placeholder="{{ __('streamShop.order_notes_eg') }}" class="form-control mb-5"
                            required></textarea>
                    </div>
                </div>
                <div>
                    <h3>{{ __('streamShop.your_order') }}</h3>
                    <div class="order_review-box">
                        <h5 class="mb-3 mt-0 mt-md-2">{{ __('streamShop.product') }}</h5>
                        <div class="checkout-review-order">
                            <div class="table-responsive">
                                <table class="table cart-table">
                                    <thead class="border-bottom">
                                        <tr>
                                            <th scope="col" class="font-size-18 fw-bold">
                                                {{ __('streamShop.product') }}</th>
                                            <th scope="col" class="font-size-18 fw-bold">
                                                {{ __('streamShop.subtotal') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="cart_item">
                                            <td class="product-name">
                                                <div class="text d-flex align-items-center gap-1">
                                                    <span class="text-body">{{ __('streamShop.product1') }}</span>
                                                    <span class="font-size-12">&nbsp;x</span>
                                                    <strong class="text-white  fw-bold">&nbsp;1</strong>
                                                </div>
                                            </td>
                                            <td class="product-total">
                                                <span class="Price-amount"><bdi
                                                        class="text-body"><span>$</span>28.00</bdi></span>
                                            </td>
                                        </tr>
                                        <tr class="cart_item">
                                            <td class="product-name">
                                                <div class="text d-flex align-items-center gap-1">
                                                    <span
                                                        class="text-body">{{ __('streamShop.product7') }}</span>
                                                    <span class="font-size-12">&nbsp;x</span>
                                                    <strong class="text-white  fw-bold">&nbsp;1</strong>
                                                </div>
                                            </td>
                                            <td class="product-total">
                                                <span class="Price-amount"><bdi
                                                        class="text-body"><span>$</span>25.00</bdi></span>
                                            </td>
                                        </tr>
                                        <tr class="cart_item">
                                            <td class="product-name">
                                                <div class="text  d-flex align-items-center gap-1">
                                                    <span class="text-body">{{ __('streamShop.product5') }}</span>
                                                    <span class="font-size-12">&nbsp;x</span>
                                                    <strong class="text-white  fw-bold">&nbsp;1</strong>
                                                </div>
                                            </td>
                                            <td class="product-total">
                                                <span class="Price-amount"><bdi
                                                        class="text-body"><span>$</span>18.00</bdi></span>
                                            </td>
                                        </tr>

                                    </tbody>
                                    <tfoot>
                                        <tr class="">
                                            <td class="p-3 fw-bold font-size-18 border-0">
                                                {{ __('streamShop.subtotal') }}
                                            </td>
                                            <td class="p-3 fw-bold border-0">
                                                <span class="mb-0 text-body">$71.00</span>
                                            </td>
                                        </tr>
                                        <tr class="">
                                            <td class="p-3 fw-bold font-size-18 border-0">{{ __('streamShop.total') }}
                                            </td>
                                            <td class="p-3 fw-bold border-0">
                                                <span class="mb-0">$71.00</span>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                                <div class="checkout-payment mt-4">
                                    <div class="payment-box border-bottom mb-4 pt-4 p-3">
                                        <div class="accordion" id="accordionPayment">
                                            <div class="accordion-item-payment">
                                                <h6 class="accordion-header" id="payment-1">
                                                    <div class="accordion-button-payment" data-bs-toggle="collapse"
                                                        data-bs-target="#collapseOnepayment" aria-expanded="true"
                                                        aria-controls="collapseOnepayment">
                                                        <span class="form-check">
                                                            <input class="form-check-input" type="radio"
                                                                name="flexRadioDefault" id="flexRadioDefault1"
                                                                checked="checked">
                                                            <label class="form-check-label" for="flexRadioDefault1">
                                                                {{ __('streamShop.bank_transfer') }}
                                                            </label>
                                                        </span>
                                                    </div>
                                                </h6>
                                                <div id="collapseOnepayment" class="accordion-collapse collapse show"
                                                    data-bs-parent="#accordionPayment">
                                                    <div class="accordion-body">
                                                        {{ __('streamShop.make_direct_payment') }}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="accordion-item-payment">
                                                <h6 class="accordion-header" id="payment-2">
                                                    <div class="accordion-button-payment collapsed"
                                                        data-bs-toggle="collapse" data-bs-target="#collapseTwopayment"
                                                        aria-expanded="false" aria-controls="collapseTwopayment">
                                                        <span class="form-check">
                                                            <input class="form-check-input" type="radio"
                                                                name="flexRadioDefault" id="flexRadioDefault2">
                                                            <label class="form-check-label" for="flexRadioDefault2">
                                                                {{ __('streamShop.cheque_payments') }}
                                                            </label>
                                                        </span>
                                                    </div>
                                                </h6>
                                                <div id="collapseTwopayment" class="accordion-collapse collapse"
                                                    aria-labelledby="payment-2" data-bs-parent="#accordionPayment">
                                                    <div class="accordion-body">
                                                       Please send a check to Store Name, Store Street, Store Town, Store State / County, Store Postcode.
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="accordion-item-payment">
                                                <h6 class="accordion-header" id="payment-3">
                                                    <div class="accordion-button-payment collapsed"
                                                        data-bs-toggle="collapse" data-bs-target="#collapseThreepayment"
                                                        aria-expanded="false" aria-controls="collapseThreepayment">
                                                        <span class="form-check">
                                                            <input class="form-check-input" type="radio"
                                                                name="flexRadioDefault" id="flexRadioDefault3">
                                                            <label class="form-check-label" for="flexRadioDefault3">
                                                                {{ __('streamShop.cod') }}
                                                            </label>
                                                        </span>
                                                    </div>
                                                </h6>
                                                <div id="collapseThreepayment" class="accordion-collapse collapse"
                                                    aria-labelledby="payment-3" data-bs-parent="#accordionPayment">
                                                    <div class="accordion-body">
                                                        Pay with cash upon delivery.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="mb-3 p-3 pb-0">
                                        {{ __('streamShop.your_personal_data') }} <a
                                            href="{{ route('frontend.privacy-policy') }}">
                                            {{ __('streamShop.privacy_policy') }}</a>
                                        .</p>
                                    <div class="text-end pb-3 pe-3">
                                        <div class="iq-button">
                                            @include('frontend::components.widgets.custom-button', [
                                                'buttonTitle' => __('streamShop.place_order'),
                                                'buttonUrl' => 'order-tracking',
                                                'notBtncaretIcon' => true,
                                            ])
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
