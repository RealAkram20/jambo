@extends('frontend::layouts.merchandise', ['isSelect2' => true, 'isNoUISlider' => true, 'isSweetalert' => true], ['isBreadCrumb' => true, 'title' => __('streamShop.product')])

@section('content')
    <div class="section-padding">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-9 col-sm-12">
                    <div class="d-flex align-items-center justify-content-between shop-filter flex-wrap gap-3">
                        <p class="text-white mb-0">{{ __('streamShop.showing') }}</p>
                        <div class="d-flex align-items-center ">
                            <div class="product-view-button rounded-3">
                                <ul class="nav_shop nav d-flex nav-pills mb-0 iq-product-filter d-flex gap-2 bg-transparent align-items-center list-inline"
                                    id="pills-tab" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link btn-sm btn-icon rounded-pill p-0" id="list-view-tab"
                                            data-bs-toggle="pill" data-bs-target="#pills-list-view" type="button"
                                            role="tab" aria-controls="pills-list-view" aria-selected="true">
                                            <span class="btn-inner d-flex align-items-center">
                                                <i class="ph ph-list-bullets grid-4x4 hover_effect active_effect"></i>
                                            </span>
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link btn-sm btn-icon rounded-pill p-0" id="grid-view-tab"
                                            data-bs-toggle="pill" data-bs-target="#pills-grid-view" type="button"
                                            role="tab" aria-controls="pills-grid-view" aria-selected="false">
                                            <span class="btn-inner d-flex">
                                                <i class="icon-grid-2x2 grid-4x4 hover_effect active_effect"></i>
                                            </span>
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link btn-sm btn-icon rounded-pill p-0 active"
                                            id="grid-three-view-tab" data-bs-toggle="pill"
                                            data-bs-target="#pills-grid-three-view-tab" type="button" role="tab"
                                            aria-controls="pills-grid-three-view-tab" aria-selected="false">
                                            <span class="btn-inner d-flex">
                                                <i class="icon icon-grid-3x3 grid-4x4 hover_effect active_effect"></i>
                                            </span>
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link btn-sm btn-icon rounded-pill p-0" id="grid-three-four-tab"
                                            data-bs-toggle="pill" data-bs-target="#pills-grid-three-four-view-tab"
                                            type="button" role="tab" aria-controls="pills-grid-three-four-view-tab"
                                            aria-selected="false">
                                            <span class="btn-inner d-flex">
                                                <i class="icon icon-grid-4x4 grid-4x4"></i>
                                            </span>
                                        </button>
                                    </li>
                                </ul>
                            </div>
                            <div class="iq-custom-select d-inline-block shop-select rounded-3">
                                <select name="cars" class="form-control season-select select2-basic-single js-states">
                                    <option value="season1">{{ __('streamShop.default_sorting') }}</option>
                                    <option>{{ __('streamShop.sort_by_popularity') }}</option>
                                    <option>{{ __('streamShop.sort_by_rating') }}</option>
                                    <option>{{ __('streamShop.sort_by_latest') }}</option>
                                    <option>{{ __('streamShop.price_low_to_high') }}</option>
                                    <option>{{ __('streamShop.price_high_to_low') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="tab-content" id="pills-tabContent">
                        <div class="tab-pane fade " id="pills-list-view" role="tabpanel" aria-labelledby="list-view-tab">
                            <div class="row row-cols-1">
                                <div class="col">
                                    @include('frontend::components.cards.product-card', [
                                        'productImg' => '01.webp',
                                        'isProductList' => true,
                                        'id' => '01',
                                        'shopTitle' => __('streamShop.product1'),
                                        'shopSale' => __('streamTag.sale!'),
                                        'bgShop' => 'primary',
                                        'shopDel' => "$48.00",
                                        'shopPrice' => "$28.00",
                                        'rating' => '4',
                                        'shopDesc' => __('streamShop.desc1'),
                                    ])
                                </div>
                                <div class="col">
                                    @include('frontend::components.cards.product-card', [
                                        'productImg' => '02.webp',
                                        'isProductList' => true,
                                        'id' => '02',
                                        'shopTitle' => __('streamShop.product2'),
                                        'shopSale' => '',
                                        'bgShop' => 'primary',
                                        'shopDel' => '',
                                        'shopPrice' => "$13.00",
                                        'rating' => '5',
                                        'shopDesc' => __('streamShop.desc1'),
                                    ])
                                </div>
                                <div class="col">
                                    @include('frontend::components.cards.product-card', [
                                        'productImg' => '03.webp',
                                        'isProductList' => true,
                                        'id' => '03',
                                        'shopTitle' => __('streamShop.product3'),
                                        'shopSale' => __('streamTag.sale!'),
                                        'bgShop' => 'primary',
                                        'shopDel' => '',
                                        'shopPrice' => "$18.00 - $45.00",
                                        'rating' => '5',
                                        'shopDesc' => __('streamShop.desc1'),
                                    ])
                                </div>
                                <div class="col">
                                    @include('frontend::components.cards.product-card', [
                                        'productImg' => '04.webp',
                                        'isProductList' => true,
                                        'id' => '04',
                                        'shopTitle' => __('streamShop.product4'),
                                       'shopSale' => __('streamShop.new'),
                                        'bgShop' => 'dark',
                                        'shopDel' => "$10.00",
                                        'shopPrice' => "$16.00",
                                        'rating' => '5',
                                        'shopDesc' => __('streamShop.desc1'),
                                    ])
                                </div>
                                <div class="col">
                                    @include('frontend::components.cards.product-card', [
                                        'productImg' => '05.webp',
                                        'isProductList' => true,
                                        'id' => '05',
                                        'shopTitle' => __('streamShop.product5'),
                                        'shopSale' => '',
                                        'bgShop' => 'primary',
                                        'shopDel' => "$20.00",
                                        'shopPrice' => "$18.00",
                                        'rating' => '5',
                                        'shopDesc' => __('streamShop.desc1'),
                                    ])
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="pills-grid-view" role="tabpanel" aria-labelledby="grid-view-tab">
                            <div
                                class="row row-col-data gy-5 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-2">
                                <div class="col">
                                    @include('frontend::components.cards.product-card', [
                                        'productImg' => '01.webp',
                                        'id' => '01',
                                        'shopTitle' => __('streamShop.product1'),
                                        'shopSale' => __('streamTag.sale!'),
                                        'bgShop' => 'primary',
                                        'shopDel' => "$48.00",
                                        'shopPrice' => "$28.00",
                                        'rating' => '4',
                                        'ratingStars' => true,
                                    ])
                                </div>
                                <div class="col">
                                    @include('frontend::components.cards.product-card', [
                                        'productImg' => '02.webp',
                                        'id' => '02',
                                        'shopTitle' => __('streamShop.product2'),
                                        'shopSale' => '',
                                        'bgShop' => 'primary',
                                        'shopDel' => '',
                                        'shopPrice' => "$13.00",
                                        'rating' => '5',
                                        'ratingStars' => true,
                                    ])
                                </div>
                                <div class="col">
                                    @include('frontend::components.cards.product-card', [
                                        'productImg' => '03.webp',
                                        'id' => '03',
                                        'shopTitle' => __('streamShop.product3'),
                                        'shopSale' => __('streamTag.sale!'),
                                        'bgShop' => 'primary',
                                        'shopDel' => '',
                                        'shopPrice' => "$18.00 - $45.00",
                                        'rating' => '5',
                                        'ratingStars' => true,
                                    ])
                                </div>
                                <div class="col">
                                    @include('frontend::components.cards.product-card', [
                                        'productImg' => '04.webp',
                                        'id' => '04',
                                        'shopTitle' => __('streamShop.product4'),
                                       'shopSale' => __('streamShop.new'),
                                        'bgShop' => 'dark',
                                        'shopDel' => "$10.00",
                                        'shopPrice' => "$16.00",
                                        'rating' => '5',
                                        'ratingStars' => true,
                                    ])
                                </div>
                                <div class="col">
                                    @include('frontend::components.cards.product-card', [
                                        'productImg' => '05.webp',
                                        'id' => '05',
                                        'shopTitle' => __('streamShop.product5'),
                                        'shopSale' => '',
                                        'bgShop' => 'primary',
                                        'shopDel' => "$20.00",
                                        'shopPrice' => "$18.00",
                                        'rating' => '5',
                                        'ratingStars' => true,
                                    ])
                                </div>
                                <div class="col">
                                    @include('frontend::components.cards.product-card', [
                                        'productImg' => '06.webp',
                                        'id' => '06',
                                        'shopTitle' => __('streamShop.product6'),
                                        'shopSale' => '',
                                        'bgShop' => 'primary',
                                        'shopDel' => "$20.00",
                                        'shopPrice' => "$18.00",
                                        'rating' => '5',
                                        'ratingStars' => true,
                                    ])

                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade active show" id="pills-grid-three-view-tab" role="tabpanel"
                            aria-labelledby="grid-three-view-tab">
                            <div
                                class="row row-col-data gy-5 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-3">
                                <div class="col">
                                    @include('frontend::components.cards.product-card', [
                                        'productImg' => '01.webp',
                                        'id' => '01',
                                        'shopTitle' => __('streamShop.product1'),
                                        'shopSale' => __('streamTag.sale!'),
                                        'bgShop' => 'primary',
                                        'shopDel' => "$48.00",
                                        'shopPrice' => "$28.00",
                                        'rating' => '4',
                                        'ratingStars' => true,
                                    ])
                                </div>
                                <div class="col">
                                    @include('frontend::components.cards.product-card', [
                                        'productImg' => '02.webp',
                                        'id' => '02',
                                        'shopTitle' => __('streamShop.product2'),
                                        'shopSale' => '',
                                        'bgShop' => 'primary',
                                        'shopDel' => '',
                                        'shopPrice' => "$13.00",
                                        'rating' => '5',
                                        'ratingStars' => true,
                                    ])
                                </div>
                                <div class="col">
                                    @include('frontend::components.cards.product-card', [
                                        'productImg' => '03.webp',
                                        'id' => '03',
                                        'shopTitle' => __('streamShop.product3'),
                                        'shopSale' => __('streamTag.sale!'),
                                        'bgShop' => 'primary',
                                        'shopDel' => '',
                                        'shopPrice' => "$18.00 - $45.00",
                                        'rating' => '5',
                                        'ratingStars' => true,
                                    ])
                                </div>
                                <div class="col">
                                    @include('frontend::components.cards.product-card', [
                                        'productImg' => '04.webp',
                                        'id' => '04',
                                        'shopTitle' => __('streamShop.product4'),
                                       'shopSale' => __('streamShop.new'),
                                        'bgShop' => 'dark',
                                        'shopDel' => "$20.00",
                                        'shopPrice' => "$16.00",
                                        'rating' => '5',
                                        'ratingStars' => true,
                                    ])
                                </div>
                                <div class="col">
                                    @include('frontend::components.cards.product-card', [
                                        'productImg' => '05.webp',
                                        'id' => '05',
                                        'shopTitle' => __('streamShop.product5'),
                                        'shopSale' => '',
                                        'bgShop' => 'primary',
                                        'shopDel' => "$20.00",
                                        'shopPrice' => "$18.00",
                                        'rating' => '5',
                                        'ratingStars' => true,
                                    ])

                                </div>
                                <div class="col">
                                    @include('frontend::components.cards.product-card', [
                                        'productImg' => '06.webp',
                                        'id' => '06',
                                        'shopTitle' => __('streamShop.product6'),
                                        'shopSale' => '',
                                        'bgShop' => 'primary',
                                        'shopDel' => '',
                                        'shopPrice' => "$18.00",
                                        'rating' => '5',
                                        'ratingStars' => true,
                                    ])
                                </div>
                                <div class="col">
                                    @include('frontend::components.cards.product-card', [
                                        'productImg' => '07.webp',
                                        'id' => '06',
                                        'shopTitle' => __('streamShop.product7'),
                                        'shopSale' => '',
                                        'bgShop' => 'primary',
                                        'shopDel' => '',
                                        'shopPrice' => "$25.00",
                                        'rating' => '5',
                                        'ratingStars' => true,
                                    ])
                                </div>
                                <div class="col">
                                    @include('frontend::components.cards.product-card', [
                                        'productImg' => '08.webp',
                                        'id' => '06',
                                        'shopTitle' => __('streamShop.product8'),
                                        'shopSale' => '',
                                        'bgShop' => 'primary',
                                        'shopDel' => "$20.00",
                                        'shopPrice' => "$18.00",
                                        'rating' => '5',
                                        'ratingStars' => true,
                                    ])
                                </div>
                                <div class="col">
                                    @include('frontend::components.cards.product-card', [
                                        'productImg' => '09.webp',
                                        'id' => '06',
                                        'shopTitle' => __('streamShop.product9'),
                                        'shopSale' => '',
                                        'bgShop' => 'primary',
                                        'shopDel' => "$10.00",
                                        'shopPrice' => "$07.00",
                                        'rating' => '5',
                                        'ratingStars' => true,
                                    ])
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="pills-grid-three-four-view-tab" role="tabpanel">
                            <div
                                class="row row-col-data gy-5 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-4">
                                <div class="col">
                                    @include('frontend::components.cards.product-card', [
                                        'productImg' => '01.webp',
                                        'id' => '01',
                                        'shopTitle' => __('streamShop.product1'),
                                        'shopSale' => __('streamTag.sale!'),
                                        'bgShop' => 'primary',
                                        'shopDel' => "$48.00",
                                        'shopPrice' => "$28.00",
                                        'rating' => '4',
                                        'ratingStars' => true,
                                    ])
                                </div>
                                <div class="col">
                                    @include('frontend::components.cards.product-card', [
                                        'productImg' => '02.webp',
                                        'id' => '02',
                                        'shopTitle' => __('streamShop.believe_mask'),
                                        'shopSale' => '',
                                        'bgShop' => 'primary',
                                        'shopDel' => '',
                                        'shopPrice' => "$13.00",
                                        'rating' => '5',
                                        'ratingStars' => true,
                                    ])
                                </div>
                                <div class="col">
                                    @include('frontend::components.cards.product-card', [
                                        'productImg' => '03.webp',
                                        'id' => '03',
                                        'shopTitle' => __('streamShop.product3'),
                                        'shopSale' => __('streamTag.sale!'),
                                        'bgShop' => 'primary',
                                        'shopDel' => '',
                                        'shopPrice' => "$18.00 - $45.00",
                                        'rating' => '5',
                                        'ratingStars' => true,
                                    ])
                                </div>
                                <div class="col">
                                    @include('frontend::components.cards.product-card', [
                                        'productImg' => '04.webp',
                                        'id' => '04',
                                        'shopTitle' => __('streamShop.product4'),
                                       'shopSale' => __('streamShop.new'),
                                        'bgShop' => 'dark',
                                        'shopDel' => "$20.00",
                                        'shopPrice' => "$16.00",
                                        'rating' => '5',
                                        'ratingStars' => true,
                                    ])
                                </div>
                                <div class="col">
                                    @include('frontend::components.cards.product-card', [
                                        'productImg' => '05.webp',
                                        'id' => '05',
                                        'shopTitle' => __('streamShop.product5'),
                                        'shopSale' => '',
                                        'bgShop' => 'primary',
                                        'shopDel' => "$20.00",
                                        'shopPrice' => "$18.00",
                                        'rating' => '5',
                                        'ratingStars' => true,
                                    ])
                                </div>
                                <div class="col">
                                    @include('frontend::components.cards.product-card', [
                                        'productImg' => '06.webp',
                                        'id' => '06',
                                        'shopTitle' => __('streamShop.product6'),
                                        'shopSale' => '',
                                        'bgShop' => 'primary',
                                        'shopDel' => '',
                                        'shopPrice' => "$18.00",
                                        'rating' => '5',
                                        'ratingStars' => true,
                                    ])
                                </div>
                                <div class="col">
                                    @include('frontend::components.cards.product-card', [
                                        'productImg' => '07.webp',
                                        'id' => '06',
                                        'shopTitle' => __('streamShop.product7'),
                                        'shopSale' => '',
                                        'bgShop' => 'primary',
                                        'shopDel' => '',
                                        'shopPrice' => "$25.00",
                                        'rating' => '5',
                                        'ratingStars' => true,
                                    ])
                                </div>
                                <div class="col">
                                    @include('frontend::components.cards.product-card', [
                                        'productImg' => '08.webp',
                                        'id' => '06',
                                        'shopTitle' => __('streamShop.product8'),
                                        'shopSale' => '',
                                        'bgShop' => 'primary',
                                        'shopDel' => "$20.00",
                                        'shopPrice' => "$18.00",
                                        'rating' => '5',
                                        'ratingStars' => true,
                                    ])
                                </div>
                                <div class="col">
                                    @include('frontend::components.cards.product-card', [
                                        'productImg' => '09.webp',
                                        'id' => '06',
                                        'shopTitle' => __('streamShop.product9'),
                                        'shopSale' => '',
                                        'bgShop' => 'primary',
                                        'shopDel' => "$10.00",
                                        'shopPrice' => "$07.00",
                                        'rating' => '5',
                                        'ratingStars' => true,
                                    ])
                                </div>
                                <div class="col">
                                    @include('frontend::components.cards.product-card', [
                                        'productImg' => '10.webp',
                                        'id' => '06',
                                        'shopTitle' => 'Daily Diary',
                                        'shopSale' => __('streamTag.sale!'),
                                        'bgShop' => 'primary',
                                        'shopDel' => "$20.00",
                                        'shopPrice' => "$18.00",
                                        'rating' => '5',
                                        'ratingStars' => true,
                                    ])
                                </div>
                                <div class="col">
                                    @include('frontend::components.cards.product-card', [
                                        'productImg' => '11.webp',
                                        'id' => '06',
                                        'shopTitle' => 'Floral Badges',
                                        'shopSale' => '',
                                        'bgShop' => 'primary',
                                        'shopDel' => '',
                                        'shopPrice' => "$20",
                                        'rating' => '3',
                                        'ratingStars' => true,
                                    ])
                                </div>
                                <div class="col">
                                    @include('frontend::components.cards.product-card', [
                                        'productImg' => '12.webp',
                                        'id' => '06',
                                        'shopTitle' => 'Ghost Cap',
                                        'shopSale' => __('streamTag.sale!'),
                                        'bgShop' => 'primary',
                                        'shopDel' => '',
                                        'shopPrice' => "$90.00",
                                        'rating' => '4',
                                        'ratingStars' => true,
                                    ])
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Static Pagination Controls -->
                    <div class="pagination justify-content-center my-5">
                        <ul class="pagination blog-pagination gap-2" id="pagination">
                            <li class="page-item">
                                <a class="page-link rounded-3"href="javascript:void(0)"><i
                                        class="ph ph-arrow-left pageicon-lineheight"></i></a>
                            </li>
                            <li class="page-item active">
                                <a class="page-link d-flex justify-content-center pageicon-lineheight rounded-3"
                                   href="javascript:void(0)">1</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link d-flex justify-content-center pageicon-lineheight rounded-3"
                                   href="javascript:void(0)">2</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link d-flex justify-content-center pageicon-lineheight rounded-3"
                                   href="javascript:void(0)">3</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link rounded-3"href="javascript:void(0)"><i
                                        class="ph ph-arrow-right pageicon-lineheight"></i></a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-12">
                    <div class="pe-3">
                        @include('frontend::components.widgets.shop-sidebar')

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
