<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title>{{ isset($title) ? $title . ' - ' : '' }}{{ config('app.name', 'Jambo') }}</title>

    @include('frontend::components.partials.head.plugins')
    {{-- Vite CSS --}}
    {{ module_vite('build-frontend', 'resources/assets/sass/app.scss') }}
    <style>
        :root {
            --bs-primary: #1A98FF;
            --bs-primary-rgb: 26, 152, 255;
            --bs-link-color: #1A98FF;
            --bs-link-color-rgb: 26, 152, 255;
            --bs-link-hover-color: #147acc;
        }
    </style>

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,300;0,400;0,500;0,700;0,900;1,300&display=swap"
        rel="stylesheet">

    <script>
        (function () {
            try {
                var serverDir = "{{ session('locale_dir') ?? language_direction() }}" || 'ltr';
                
                window.IQ_SERVER_DIR = serverDir;
                
                if (document && document.documentElement) {
                    document.documentElement.setAttribute('dir', serverDir);
                }

                var storageKey = 'streamit';
                var storageUsed = null;
                var raw = sessionStorage.getItem(storageKey);
                if (raw === null) {
                    raw = localStorage.getItem(storageKey);
                    storageUsed = raw === null ? null : 'localStorage';
                } else {
                    storageUsed = 'sessionStorage';
                }

                var parsed = null;
                if (raw !== null) {
                    try {
                        parsed = JSON.parse(raw);
                    } catch (e) {
                        parsed = null;
                    }
                }

                var newValue = serverDir === 'rtl' ? 'rtl' : 'ltr';

                if (parsed && parsed.setting && parsed.setting.theme_scheme_direction) {
                    parsed.setting.theme_scheme_direction.value = newValue;
                   
                    if (storageUsed === 'localStorage') localStorage.setItem(storageKey, JSON.stringify(parsed));
                    else sessionStorage.setItem(storageKey, JSON.stringify(parsed));
                } else if (!parsed) {
                    
                    var init = {
                        saveLocal: 'sessionStorage',
                        storeKey: storageKey,
                        setting: {
                            theme_scheme_direction: {
                                target: 'html',
                                choices: ['ltr', 'rtl'],
                                value: newValue
                            }
                        }
                    };
                    sessionStorage.setItem(storageKey, JSON.stringify(init));
                }
            } catch (e) {
                // Fail silently if storage is unavailable
                console && console.debug && console.debug('locale-dir-sync error', e);
            }
        })();
    </script>
</head>

<body class="{{ $bodyClass ?? '' }}">

    @include('frontend::components.loader-component')

    <main class="main-content">

        @include('frontend::components.merchandise.header-default')

        @if (isset($isBreadCrumb) && $isBreadCrumb)
        @include('frontend::components.breadcrumb-widget')
        @endif

        @yield('content')
    </main>
    @if (isset($isFooter) && $isFooter)
    @include('frontend::components.merchandise.footer-default')
    @else
    @include('frontend::components.partials.footer-default')
    @endif


    <div class="offcanvas offcanvas-end sidebar-cart border-0 on-rtl end" tabindex="-1" id="offcanvasCart"
        aria-modal="true" role="dialog">
        <div class="offcanvas-header py-4">
            <h5 class="offcanvas-title">{{__('streamShop.shopping_cart')}} (3)</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body py-5">
            <div class="sidebar-cart-content d-flex flex-column justify-content-between">
                <div class="cart-items-container">
                    <ul class="cart-items-list m-0 list-inline">
                        <li class="cart-item mb-4 pb-4 border-bottom">
                            <div class="cart-item-block d-flex gap-3">
                                <div class="image flex-shrink-0">
                                    <img src="{{ asset('/frontend/images/shop/product/02.webp') }}" width="90"
                                        alt="product-image" class="img-fluid object-cover">
                                </div>
                                <div class="cart-block-content position-relative flex-grow-1 pe-5">
                                    <h6 class="mb-36 text-capitalize">{{__('streamShop.believe_mask')}}</h6>
                                    <span class="text-primary small">$13.00</span>
                                    <div class="mt-3">
                                        <div class="btn-group iq-qty-btn border border-white rounded-0" data-qty="btn"
                                            role="group">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-light iq-quantity-minus text-white border-0">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="6" height="3"
                                                    viewBox="0 0 6 3" fill="none">
                                                    <path d="M5.22727 0.886364H0.136364V2.13636H5.22727V0.886364Z"
                                                        fill="currentColor"></path>
                                                </svg>
                                            </button>
                                            <input type="text"
                                                class="btn btn-sm btn-outline-light input-display border-0"
                                                data-qty="input" pattern="^(0|[1-9][0-9]*)$" minlength="1" maxlength="2"
                                                value="2" title="Qty">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-light iq-quantity-plus text-white border-0">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="9" height="8"
                                                    viewBox="0 0 9 8" fill="none">
                                                    <path
                                                        d="M3.63636 7.70455H4.90909V4.59091H8.02273V3.31818H4.90909V0.204545H3.63636V3.31818H0.522727V4.59091H3.63636V7.70455Z"
                                                        fill="currentColor"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="position-absolute top-0 end-0">
                                        <a href="javascript:void();"
                                            class="text-white delete-btn text-capitalize">delete</a>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li class="cart-item mb-4 pb-4 border-bottom">
                            <div class="cart-item-block d-flex gap-3">
                                <div class="image flex-shrink-0">
                                    <img src="{{ asset('/frontend/images/shop/product/04.webp') }}" width="90"
                                        alt="product-image" class="img-fluid object-cover">
                                </div>
                                <div class="cart-block-content position-relative flex-grow-1 pe-5">
                                    <h6 class="mb-36 text-capitalize">{{__('streamShop.product4')}}</h6>
                                    <span class="text-primary small">$18.00</span>
                                    <div class="mt-3">
                                        <div class="btn-group iq-qty-btn border border-white rounded-0" data-qty="btn"
                                            role="group">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-light iq-quantity-minus text-white border-0">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="6" height="3"
                                                    viewBox="0 0 6 3" fill="none">
                                                    <path d="M5.22727 0.886364H0.136364V2.13636H5.22727V0.886364Z"
                                                        fill="currentColor"></path>
                                                </svg>
                                            </button>
                                            <input type="text"
                                                class="btn btn-sm btn-outline-light input-display border-0"
                                                data-qty="input" pattern="^(0|[1-9][0-9]*)$" minlength="1" maxlength="2"
                                                value="2" title="Qty">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-light iq-quantity-plus text-white border-0">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="9" height="8"
                                                    viewBox="0 0 9 8" fill="none">
                                                    <path
                                                        d="M3.63636 7.70455H4.90909V4.59091H8.02273V3.31818H4.90909V0.204545H3.63636V3.31818H0.522727V4.59091H3.63636V7.70455Z"
                                                        fill="currentColor"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="position-absolute top-0 end-0">
                                        <a href="javascript:void();"
                                            class="text-white delete-btn text-capitalize">delete</a>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li class="cart-item">
                            <div class="cart-item-block d-flex gap-3">
                                <div class="image flex-shrink-0">
                                    <img src="{{ asset('/frontend/images/shop/product/05.webp') }}" width="90"
                                        alt="product-image" class="img-fluid object-cover">
                                </div>
                                <div class="cart-block-content position-relative flex-grow-1 pe-5">
                                    <h6 class="mb-36 text-capitalize">{{__('streamShop.product5')}}</h6>
                                    <span class="text-primary small">$18.00</span>
                                    <div class="mt-3">
                                        <div class="btn-group iq-qty-btn border border-white rounded-0" data-qty="btn"
                                            role="group">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-light iq-quantity-minus text-white border-0">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="6" height="3"
                                                    viewBox="0 0 6 3" fill="none">
                                                    <path d="M5.22727 0.886364H0.136364V2.13636H5.22727V0.886364Z"
                                                        fill="currentColor"></path>
                                                </svg>
                                            </button>
                                            <input type="text"
                                                class="btn btn-sm btn-outline-light input-display border-0"
                                                data-qty="input" pattern="^(0|[1-9][0-9]*)$" minlength="1" maxlength="2"
                                                value="2" title="Qty">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-light iq-quantity-plus text-white border-0">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="9" height="8"
                                                    viewBox="0 0 9 8" fill="none">
                                                    <path
                                                        d="M3.63636 7.70455H4.90909V4.59091H8.02273V3.31818H4.90909V0.204545H3.63636V3.31818H0.522727V4.59091H3.63636V7.70455Z"
                                                        fill="currentColor"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="position-absolute top-0 end-0">
                                        <a href="javascript:void();"
                                            class="text-white delete-btn text-capitalize">delete</a>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="cart-progress-container mt-5 pt-5 border-top">
                    <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                        <h5 class="m-0 fw-bold">{{__('streamShop.subtotal')}}</h5>
                        <h5 class="m-0 fw-bold">$49.00</h5>
                    </div>
                    <div class="d-grid gap-3 mt-4">
                        <a href="{{ route('frontend.chekout') }}"
                            class="btn bg-primary text-uppercase fw-medium w-100 text-white">
                            <span class="button-text small">checkout</span>
                        </a>

                        <a href="{{ route('frontend.cart') }}"
                            class="btn bg-light text-uppercase fw-medium w-100 text-dark">
                            <span class="button-text small">view cart</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('frontend::components.partials.setting')

    @include('frontend::components.partials.back-to-top')
    {{-- Vite JS --}}
    {{ module_vite('build-frontend', 'resources/assets/js/app.js') }}

    @include('frontend::components.partials.scripts.plugins')

    @include('frontend::components.partials.scripts.script')
</body>
