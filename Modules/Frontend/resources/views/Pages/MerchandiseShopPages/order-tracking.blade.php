@extends('frontend::layouts.master', ['isSwiperSlider' => true], ['isBreadCrumb' => true, 'title' => __('frontendheader.order_tracking')])

@section('content')
    <section class="section-padding">
        <div class="container">
            <div class="px-3">
                <div class="main-cart mb-3 mb-md-5 pb-0 pb-md-5">
                    <ul
                        class="cart-page-items d-flex justify-content-center list-inline align-items-center gap-3 gap-md-5 flex-wrap">
                        <li class="cart-page-item">
                            <span class=" cart-pre-number  border-radius rounded-circle me-1"> 1 </span>
                            <span class="cart-page-link ">
                                {{ __('streamShop.shopping_cart') }} </span>
                        </li>
                        <li class="cart-page-item d-flex justify-content-center">
                            <i class="ph ph-caret-circle-right fs-4"></i>
                        </li>
                        <li class="cart-page-item">
                            <span class=" cart-pre-number  border-radius rounded-circle me-1"> 2 </span>
                            <span class="cart-page-link ">
                                {{ __('streamShop.checkout') }}</span>
                        </li>
                        <li class="cart-page-item d-flex justify-content-center">
                            <i class="ph ph-caret-circle-right fs-4"></i>
                        </li>
                        <li class="cart-page-item active">
                            <span
                                class="cart-pre-heading badge bg-primary cart-pre-number border-radius rounded-circle me-1">
                                3
                            </span>
                            <span class="cart-page-link ">
                                {{ __('streamShop.order_summary') }} </span>
                        </li>
                    </ul>
                </div>
                <div class="order">
                    <p class="thank">{{ __('streamShop.thankyou') }} </p>
                    <ul class="details list-inline">
                        <li class="detail text-uppercase">{{ __('streamShop.order_number') }}<strong>15823</strong></li>
                        <li class="detail text-uppercase">{{ __('streamTag.date') }}<strong>June 22, 2022</strong></li>
                        <li class="detail text-uppercase">{{ __('streamAccount.email') }}<strong>jondoe@gmail.com</strong></li>
                        <li class="detail text-uppercase">{{ __('streamShop.total') }}<strong>$25.00</strong></li>
                        <li class="detail">
                            {{ __('streamShop.payment_method') }}<strong>{{ __('streamShop.bank_transfer') }}</strong>
                        </li>
                    </ul>
                </div>
                <h5 class="order_details mb-5">{{ __('streamShop.order_details') }}</h5>
                <div class="row">
                    <div class="col-lg-12">
                        <section class="maintable table-responsive">
                            <table class="table table-border mb-0">
                                <thead>
                                    <tr>
                                        <th class="fw-bold text-white">{{ __('streamShop.product') }}</th>
                                        <th class="fw-bold text-white">{{ __('streamShop.total') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="order_item">
                                        <td>
                                            <a href="product-detail">{{ __('streamShop.product1') }}</a>
                                            <strong class="product-quantity">×&nbsp;1</strong>
                                        </td>
                                        <td>
                                            <span class="amount"><bdi><span class="">$</span>25.00</bdi></span>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="order_item">
                                        <th class="fw-bold text-white">{{ __('streamShop.subtotal') }}</th>
                                        <td><span class="amount fw-bold text-white"><span
                                                    class="">$</span>25.00</span></td>
                                    </tr>
                                    <tr class="order_item">
                                        <th class="fw-bold text-white">{{ __('streamShop.payment_method') }}</th>
                                        <td class="fw-bold text-white">{{ __('streamShop.bank_transfer') }}</td>
                                    </tr>
                                    <tr>
                                        <th class="fw-bold text-white">{{ __('streamShop.total') }}</th>
                                        <td><span class="amount fw-bold text-white"><span
                                                    class="">$</span>25.00</span></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </section>
                    </div>
                    <div class="col-lg-12">
                        <h5 class="order_details mb-5">{{ __('streamShop.billing_address') }}</h5>
                        <div class="bill_section">
                            <address>
                                Ginger Gaines<br>Chase Hawkins Traders<br>24 White Fabien Lane<br>Quasi sint ad sit<br>Et
                                voluptatem ut
                                se<br>India<br>FUGAULLAMCOCONSEC<br>Virgin Islands (British)
                                <p class="d-flex align-items-center my-3"><i
                                        class="ph ph-phone me-2"></i><span>+13496874424</span>
                                </p>

                                <p class="d-flex align-items-center mb-0"><i
                                        class="ph ph-envelope-simple me-2"></i><span>palawojewa@mailinator.com</span></p>

                            </address>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}
@endsection
