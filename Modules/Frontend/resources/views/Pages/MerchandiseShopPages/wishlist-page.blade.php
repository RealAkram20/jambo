@extends('frontend::layouts.master', ['isSweetalert' => true], ['isBreadCrumb' => true, 'title' => __('frontendheader.wishlist_page')])

@section('content')
    <div class="wishlist-page section-padding">
        <div class="container">
            <div class="wishlist-section px-2">
                <h5 class="mb-5 custom-wishlist-title">{{ __('streamShop.my_wishlist') }}</h5>
                <div class="table-responsive-sm">
                    <table class="table cart-table">
                        <thead class="border-bottom">
                            <tr>
                                <th class="fw-500 font-size-18"></th>
                                <th class="fw-500 font-size-18"></th>
                                <th class="fw-500 font-size-18">{{ __('streamShop.product_name') }}</th>
                                <th class="fw-500 font-size-18">{{ __('streamShop.unit_price') }}</th>
                                <th class="fw-500 font-size-18">{{ __('streamShop.stock_status') }}</th>
                                <th class="fw-500 font-size-18"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr data-item="list">
                                <td class="product-remove">
                                    <button
                                        class="btn btn-icon btn-danger delete-btn text-end  bg-transparent text-body border-0">
                                        <span class="btn-inner">
                                            <i class="ph ph-x fw-bold text-primary"></i>
                                        </span>
                                    </button>
                                </td>
                                <td class="product-img">
                                    <div class="product-thumbnail">
                                        <a class="mb-2 me-3" href="product-detail">
                                            <img class="avatar-80"
                                                src="{{ asset('frontend/images/shop/product/01.webp') }} " alt="01"
                                                loading="lazy" />
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <a href="product-detail" class="product-name">{{ __('streamShop.product1') }}</a>
                                </td>
                                <td>
                                    <span class="fw-500">$28.00</span>
                                </td>
                                <td>
                                    <span>{{ __('streamShop.in_stock') }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="text-primary">{{ __('streamShop.add_to_cart') }}</span>
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
                                    <div class="product-thumbnail">
                                        <a class="mb-2 me-3" href="product-detail">
                                            <img class="avatar-80"
                                                src="{{ asset('frontend/images/shop/product/02.webp') }}  " alt="01"
                                                loading="lazy" />
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <a href="product-detail" class="product-name">{{ __('streamShop.product2') }}</a>
                                </td>
                                <td>
                                    <span class="fw-500">$13.00</span>
                                </td>
                                <td>
                                    <span>{{ __('streamShop.in_stock') }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="text-primary">{{ __('streamShop.add_to_cart') }}</span>
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
                                    <div class="product-thumbnail">
                                        <a class="mb-2 me-3" href="product-detail">
                                            <img class="avatar-80"
                                                src="{{ asset('frontend/images/shop/product/03.webp') }} " alt="01"
                                                loading="lazy" />
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <a href="product-detail" class="product-name">{{ __('streamShop.product3') }}</a>
                                </td>
                                <td>
                                    <span class="fw-500">$18.00 – $45.00</span>
                                </td>
                                <td>
                                    <span>{{ __('streamShop.out_of_stock') }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="text-primary">{{ __('streamShop.add_to_cart') }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="product-social-share mt-5">
                    <h5 class="fs-4">{{ __('streamShop.share_on') }}:</h5>
                    <ul class="list-inline m-0 p-0 d-flex flex-wrap align-items-center gap-3">
                        <li class="flex-shrink-0">
                            <a href="https://www.facebook.com/"
                                class="d-inline-block border-radius rounded-circle bg-primary text-white text-center"
                                target="_blank">
                                <i class="icon icon-facebook-share"></i>
                            </a>
                        </li>
                        <li class="flex-shrink-0">
                            <a href="https://twitter.com/"
                                class="d-inline-block border-radius rounded-circle bg-info text-white text-center"
                                target="_blank">
                                <i class="ph ph-x-logo"></i>
                            </a>
                        </li>
                        <li class="flex-shrink-0">
                            <a href="https://in.pinterest.com/"
                                class="d-inline-block border-radius rounded-circle bg-danger text-white text-center"
                                target="_blank">
                                <i class="icon icon-pinterest-share"></i>
                            </a>
                        </li>
                        <li class="flex-shrink-0">
                            <a href="#"
                                class="d-flex align-items-center justify-content-center border-radius rounded-circle bg-warning text-white text-center">
                                <i class="ph ph-envelope-simple fs-5"></i>
                            </a>
                        </li>
                        <li class="flex-shrink-0">
                            <a href="https://www.whatsapp.com/"
                                class="d-flex align-items-center justify-content-center border-radius rounded-circle bg-success text-white text-center"
                                target="_blank">
                                <i class="ph ph-whatsapp-logo fs-5"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}
@endsection
