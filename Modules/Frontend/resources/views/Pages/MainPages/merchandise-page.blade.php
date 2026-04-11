@extends('frontend::layouts.merchandise', ['isSwiperSlider' => true, 'isFslightbox' => true, 'isFooter' => true, 'isSweetalert' => true])

@section('content')
    <div class="position-relative swiper swiper-slide-space-0" data-swiper="home-banner-slider">
        <div class="slider m-0 p-0 swiper-wrapper home-slider">
            <a class="swiper-slide slide" href="product-detail">
                <img src="{{ asset('frontend/images/shop/slider-01.webp') }}" class="img-fluid" alt="slider-img"
                    loading="lazy" />
            </a>
            <a class="swiper-slide slide" href="product-detail">
                <img src="{{ asset('frontend/images/shop/slider-02.webp') }}" class="img-fluid" alt="slider-img"
                    loading="lazy" />
            </a>
            <a class="swiper-slide slide" href="product-detail">
                <img src="{{ asset('frontend/images/shop/slider-03.webp') }}" class="img-fluid" alt="slider-img"
                    loading="lazy" />
            </a>
        </div>
        <div class="swiper-banner-button-prev swiper-nav" id="home-banner-slider-prev">
            <i></i>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 44 44" width="44" height="44" fill="none"
                stroke="currentColor">
                <circle r="20" cy="22" cx="22"></circle>
            </svg>
        </div>
        <div class="swiper-banner-button-next swiper-nav" id="home-banner-slider-next">
            <i></i>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 44 44" width="44" height="44" fill="none"
                stroke="currentColor">
                <circle r="20" cy="22" cx="22"></circle>
            </svg>
        </div>
    </div>


    <section class="category-slider-block">
        <div class="container-fluid">
            <div class="ms-lg-1">
                <h4 class="my-4 mt-md-3 mt-sm-3">{{ __('sectionTitle.best_selling_categories') }}</h4>
            </div>
            <div class="position-relative swiper swiper-card" data-slide="4" data-laptop="4" data-tab="3" data-mobile="2"
                data-mobile-sm="1" data-autoplay="false" data-loop="false" data-navigation="true" data-pagination="true">
                <ul class="list-inline swiper-wrapper p-0 m-0">
                    <li class="swiper-slide slide-item">
                        @include('frontend::components.cards.shop-selling-category', [
                            'shopsellingImg' => '01.webp',
                            'id' => '01',
                            'shopsellingText' => __('streamShop.activeman'),
                        ])

                    </li>
                    <li class="swiper-slide slide-item">
                        @include('frontend::components.cards.shop-selling-category', [
                            'shopsellingImg' => '02.webp',
                            'id' => '03',
                            'shopsellingText' => __('streamShop.avengers'),
                        ])

                    </li>
                    <li class="swiper-slide slide-item">
                        @include('frontend::components.cards.shop-selling-category', [
                            'shopsellingImg' => '03.webp',
                            'id' => '03',
                            'shopsellingText' => __('streamShop.disney_world'),
                        ])

                    </li>
                    <li class="swiper-slide slide-item">
                        @include('frontend::components.cards.shop-selling-category', [
                            'shopsellingImg' => '04.webp',
                            'id' => '04',
                            'shopsellingText' => __('streamShop.fantasia'),
                        ])

                    </li>
                    <li class="swiper-slide slide-item">
                        @include('frontend::components.cards.shop-selling-category', [
                            'shopsellingImg' => '05.webp',
                            'id' => '05',
                            'shopsellingText' => __('streamShop.galaxy_heaven'),
                        ])

                    </li>
                    <li class="swiper-slide slide-item">
                        @include('frontend::components.cards.shop-selling-category', [
                            'shopsellingImg' => '06.webp',
                            'id' => '06',
                            'shopsellingText' => __('streamShop.ghost_spider'),
                        ])

                    </li>
                    <li class="swiper-slide slide-item">
                        @include('frontend::components.cards.shop-selling-category', [
                            'shopsellingImg' => '07.webp',
                            'id' => '07',
                            'shopsellingText' => __('streamShop.harry_david'),
                        ])

                    </li>
                    <li class="swiper-slide slide-item">
                        @include('frontend::components.cards.shop-selling-category', [
                            'shopsellingImg' => '08.webp',
                            'id' => '08',
                            'shopsellingText' => __('streamShop.haunted_halloween'),
                        ])

                    </li>
                    <li class="swiper-slide slide-item">
                        @include('frontend::components.cards.shop-selling-category', [
                            'shopsellingImg' => '09.webp',
                            'id' => '09',
                            'shopsellingText' => __('streamShop.studios'),
                        ])

                    </li>
                    <li class="swiper-slide slide-item">
                        @include('frontend::components.cards.shop-selling-category', [
                            'shopsellingImg' => '11.webp',
                            'id' => '10',
                            'shopsellingText' => __('streamShop.monster_house'),
                        ])

                    </li>
                    <li class="swiper-slide slide-item">
                        @include('frontend::components.cards.shop-selling-category', [
                            'shopsellingImg' => '12.webp',
                            'id' => '11',
                            'shopsellingText' => __('streamShop.quid_game'),
                        ])

                    </li>
                    <li class="swiper-slide slide-item">
                        @include('frontend::components.cards.shop-selling-category', [
                            'shopsellingImg' => '14.webp',
                            'id' => '12',
                            'shopsellingText' => __('streamShop.the_madrid'),
                        ])

                    </li>
                    <li class="swiper-slide slide-item">
                        @include('frontend::components.cards.shop-selling-category', [
                            'shopsellingImg' => '15.webp',
                            'id' => '15',
                            'shopsellingText' => __('streamShop.the_chapion'),
                        ])

                    </li>
                </ul>
            </div>
        </div>
    </section>


    <section class="">
        <div class="container-fluid p-xl-0 overflow-hidden">
            <div class="row">
                <div class="col-lg-4 col-md-6 d-flex flex-column gap-4">
                    <div class="product-box left-1-productbox ">
                        <a href="shop" class="card-link"></a>
                        <div class="card-img-overlay product-card-img-overlay"
                            style="background-image: url('{{ asset('frontend/images/shop/card/01.webp') }}');">
                        </div>
                        <div class="card-body w-100">
                            <h4 class="parent-heading">{{ __('streamShop.monster_house') }}</h4>
                            <div>
                                <a href="shop" class="btn btn-link p-0 text-capitalize position-relative">
                                    <span class="button-text">{{ __('streamButtons.shop_now') }}</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="product-box left-2-productbox">
                        <a href="shop" class="card-link"></a>
                        <div class="card-img-overlay product-card-img-overlay mt-lg-0 mt-md-4 mt-4"
                            style="background-image: url('{{ asset('frontend/images/shop/card/02.webp') }}');">
                        </div>
                        <div class="card-body w-100">
                            <h4 class="parent-heading">{{ __('streamShop.warner_bro_films') }}</h4>
                            <div>
                                <a href="shop" class="btn btn-link p-0 text-capitalize position-relative">
                                    <span class="button-text">{{ __('streamButtons.shop_now') }}</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mt-md-0 mt-4">
                    <div class="center-productbox product-box">
                        <a href="shop" class="card-link"></a>
                        <div class="card-img-overlay product-card-img-overlay"
                            style="background-image: url('{{ asset('frontend/images/shop/card/03.webp') }}');">
                        </div>
                        <div class="card-body w-100">
                            <h4 class="parent-heading">{{ __('streamShop.marvel_studios') }}</h4>
                            <div>
                                <a href="shop" class="btn btn-link p-0 text-capitalize position-relative">
                                    <span class="button-text">{{ __('streamButtons.shop_now') }}</span>
                                </a>
                            </div>

                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-12 d-flex flex-column mt-lg-0 mt-md-4 mt-4 gap-4">
                    <div class="product-box right-1-productbox">
                        <a href="shop" class="card-link"></a>
                        <div class="card-img-overlay product-card-img-overlay md-mt-5"
                            style="background-image: url('{{ asset('frontend/images/shop/card/04.webp') }}');">
                        </div>
                        <div class="card-body w-100">
                            <h4 class="parent-heading">{{ __('streamShop.warner_bro_films') }}</h4>
                            <div>
                                <a href="shop" class="btn btn-link p-0 text-capitalize position-relative">
                                    <span class="button-text">{{ __('streamButtons.shop_now') }}</span>
                                </a>
                            </div>

                        </div>
                    </div>
                    <div class="product-box right-2-productbox">
                        <a href="shop" class="card-link"></a>
                        <div class="card-img-overlay product-card-img-overlay mt-lg-0 mt-md-4 mt-4"
                            style="background-image: url('{{ asset('frontend/images/shop/card/05.webp') }}');">
                        </div>
                        <div class="card-body w-100">
                            <h4 class="parent-heading">{{ __('streamShop.disney_world') }}</h4>
                            <div>
                                <a href="shop" class="btn btn-link p-0 text-capitalize position-relative">
                                    <span class="button-text">{{ __('streamButtons.shop_now') }}</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>



    <div class="newest-product-block section-padding-top">
        <div class="container-fluid">
            <div class="overflow-hidden">
                <div class="d-flex align-items-center justify-content-between my-4">
                    <h4 class="main-title text-capitalize mb-0">{{ __('sectionTitle.newest_products') }}</h4>
                </div>
                <div class="card-style-slider">
                    <div class="position-relative swiper swiper-card" data-slide="5" data-laptop="5" data-tab="3"
                        data-mobile="2" data-mobile-sm="2" data-autoplay="false" data-loop="true"
                        data-navigation="true" data-pagination="true">
                        <ul class="p-0 swiper-wrapper m-0  list-inline">
                            <li class="swiper-slide">
                                @include('frontend::components.cards.product-card', [
                                    'productImg' => '01.webp',
                                    'id' => '01',
                                    'shopTitle' => __('streamShop.product1'),
                                    'shopSale' => __('streamShop.sale'),
                                    'bgShop' => 'primary',
                                    'shopDel' => "$28.00",
                                    'shopPrice' => "$25.00",
                                    'rating' => '4',
                                ])

                            </li>
                            <li class="swiper-slide">
                                @include('frontend::components.cards.product-card', [
                                    'productImg' => '02.webp',
                                    'id' => '02',
                                    'shopTitle' => __('streamShop.product2'),
                                    'shopSale' => '',
                                    'bgShop' => 'primary',
                                    'shopDel' => '',
                                    'shopPrice' => "$13.00",
                                    'rating' => '5',
                                ])

                            </li>
                            <li class="swiper-slide">
                                @include('frontend::components.cards.product-card', [
                                    'productImg' => '03.webp',
                                    'id' => '03',
                                    'shopTitle' => __('streamShop.product3'),
                                    'shopSale' => __('streamShop.sale'),
                                    'bgShop' => 'primary',
                                    'shopDel' => '',
                                    'shopPrice' => "$18.00 – $45.00",
                                    'rating' => '5',
                                ])

                            </li>
                            <li class="swiper-slide">
                                @include('frontend::components.cards.product-card', [
                                    'productImg' => '04.webp',
                                    'id' => '04',
                                    'shopTitle' => __('streamShop.product4'),
                                    'shopSale' => __('streamShop.new'),
                                    'bgShop' => 'dark',
                                    'shopDel' => "$20.00",
                                    'shopPrice' => "$18.00",
                                    'rating' => '5',
                                ])

                            </li>
                            <li class="swiper-slide">
                                @include('frontend::components.cards.product-card', [
                                    'productImg' => '05.webp',
                                    'id' => '05',
                                    'shopTitle' => __('streamShop.product5'),
                                    'shopSale' => '',
                                    'bgShop' => '',
                                    'shopDel' => '',
                                    'shopPrice' => "$17.00",
                                    'rating' => '5',
                                ])

                            </li>
                            <li class="swiper-slide">
                                @include('frontend::components.cards.product-card', [
                                    'productImg' => '06.webp',
                                    'id' => '06',
                                    'shopTitle' => __('streamShop.product6'),
                                    'shopSale' => '',
                                    'bgShop' => '',
                                    'shopDel' => '',
                                    'shopPrice' => "$18.00",
                                    'rating' => '5',
                                ])

                            </li>
                        </ul>
                        <div class="swiper-button swiper-button-next"></div>
                        <div class="swiper-button swiper-button-prev"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="section-padding-bottom">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 col-md-6">
                    <div class="categorie-about-left">
                        <div class="categorie-about-image">
                            <img src="{{ asset('frontend/images/shop/card/capton-america.webp') }}" class="img-fluid"
                                alt="shop" loading="lazy">
                        </div>
                        <div class=" text-left mb-4 pt-3">
                            <h4 class="mb-2 line-count-1">{{ __('streamShop.captain_america') }}</h4>
                            <p class="mb-4 mt-2 line-count-3">{{ __('streamShop.lorem_ipsum') }}</p>
                            @include('frontend::components.widgets.custom-button', [
                                'buttonTitle' => __('streamShop.view_collection'),
                                'buttonUrl' => route('frontend.shop'),
                                'notBtncaretIcon' => true,
                            ])

                        </div>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6">
                    <div class="categorie-about-right">
                        <div class="categorie-about-image">
                            <img src="{{ asset('frontend/images/shop/card/deadpool.webp') }}" class="img-fluid"
                                alt="shop" loading="lazy">
                        </div>
                        <div class=" text-left mb-4 pt-3">
                            <h4 class="mb-2 line-count-1">{{ __('streamShop.deadpool') }}</h4>
                            <p class="mb-4 mt-2 line-count-3">{{ __('streamShop.lorem_ipsum') }}</p>
                            @include('frontend::components.widgets.custom-button', [
                                'buttonTitle' => __('streamShop.view_collection'),
                                'buttonUrl' => route('frontend.shop'),
                                'notBtncaretIcon' => true,
                            ])

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-padding merchandise-section"
        style="background:url('{{ asset('frontend/images/shop/card/bg-img.webp') }}') ; background-position: center center; background-repeat: no-repeat; background-size: cover;">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-lg-6 col-md-6 col-12">
                    <img src="{{ asset('frontend/images/shop/card/multipal.webp') }}" class="img-fluid" alt="shop"
                        loading="lazy">
                </div>
                <div class="col-lg-6 col-md-6 col-12 mt-4 mt-md-0">
                    @include('frontend::components.widgets.logo')
                    <h2 class="mt-3 mb-4 pb-2"> {{ __('streamShop.wear_streamit') }} </h2>
                    <p class="mb-4 line-count-3">{{ __('streamShop.buy_exclusive') }}
                    </p>
                    @include('frontend::components.widgets.custom-button', [
                        'buttonTitle' => __('streamButtons.shop_now'),
                        'buttonUrl' => route('frontend.product_detail'),
                        'notBtncaretIcon' => true,
                    ])
                </div>
            </div>
        </div>
    </section>



    <div class="best-product-block section-padding-top">
        <div class="container-fluid">
            <div class="overflow-hidden">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h4 class="main-title text-capitalize mb-0">{{ __('sectionTitle.best_sellers') }}</h4>
                </div>
                <div class="card-style-slider">
                    <div class="position-relative swiper swiper-card" data-slide="5" data-laptop="5" data-tab="3"
                        data-mobile="2" data-mobile-sm="2" data-autoplay="false" data-loop="true"
                        data-navigation="true" data-pagination="true">
                        <ul class="p-0 swiper-wrapper m-0  list-inline">
                            <li class="swiper-slide">
                                @include('frontend::components.cards.product-card', [
                                    'productImg' => '01.webp',
                                    'id' => '01',
                                    'shopTitle' => __('streamShop.product1'),
                                    'shopSale' => __('streamShop.sale'),
                                    'bgShop' => 'primary',
                                    'shopDel' => "$28.00",
                                    'shopPrice' => "$25.00",
                                    'rating' => '4',
                                ])

                            </li>
                            <li class="swiper-slide">
                                @include('frontend::components.cards.product-card', [
                                    'productImg' => '02.webp',
                                    'id' => '02',
                                    'shopTitle' => __('streamShop.product2'),
                                    'shopSale' => '',
                                    'bgShop' => '',
                                    'shopDel' => '',
                                    'shopPrice' => "$13.00",
                                    'rating' => '5',
                                ])

                            </li>
                            <li class="swiper-slide">
                                @include('frontend::components.cards.product-card', [
                                    'productImg' => '03.webp',
                                    'id' => '03',
                                    'shopTitle' => __('streamShop.product3'),
                                    'shopSale' => __('streamShop.sale'),
                                    'bgShop' => '',
                                    'shopDel' => '',
                                    'shopPrice' => "$18.00 – $45.00",
                                    'rating' => '5',
                                ])

                            </li>
                            <li class="swiper-slide">
                                @include('frontend::components.cards.product-card', [
                                    'productImg' => '04.webp',
                                    'id' => '04',
                                    'shopTitle' => __('streamShop.product4'),
                                    'shopSale' => __('streamShop.new'),
                                    'bgShop' => 'dark',
                                    'shopDel' => "$20.00",
                                    'shopPrice' => "$18.00",
                                    'rating' => '5',
                                ])

                            </li>
                            <li class="swiper-slide">
                                @include('frontend::components.cards.product-card', [
                                    'productImg' => '05.webp',
                                    'id' => '05',
                                    'shopTitle' => __('streamShop.product5'),
                                    'shopSale' => '',
                                    'bgShop' => '',
                                    'shopDel' => '',
                                    'shopPrice' => "$17.00",
                                    'rating' => '5',
                                ])

                            </li>
                            <li class="swiper-slide">
                                @include('frontend::components.cards.product-card', [
                                    'productImg' => '06.webp',
                                    'id' => '06',
                                    'shopTitle' => __('streamShop.product6'),
                                    'shopSale' => '',
                                    'bgShop' => '',
                                    'shopDel' => '',
                                    'shopPrice' => "$18.00",
                                    'rating' => '5',
                                ])

                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="product_container">
        <div class="row gx-0">
            <div class="col-lg-6 shop-contact-one">
                <a href="javascript:void(0);">
                    <div class="channel-logo">
                        <h3 class="mb-0">{{ __('streamShop.our_multiple') }}</h3>
                    </div>
                </a>
                <div class="row my-4">
                    <div class="col-lg-6 col-md-6">
                        <h5 class="mb-2">{{ __('streamAccount.australia') }}:</h5>
                        <p>24 {{ __('streamShop.thornridge_cir') }} 7140</p>
                    </div>
                    <div class="col-lg-6 col-md-6">
                        <h5 class="mb-2">{{ __('streamAccount.australia') }}: </h5>
                        <p>2118 {{ __('streamShop.thornridge_cir') }}</p>
                    </div>
                </div>
                <div class="d-flex align-items-md-center flex-column flex-md-row gap-3">
                    <h5 class="m-0">{{ __('streamShop.working_hours') }}: </h5>
                    <i class="text-primary shop_store_time"> Mon – Fri : 09:00 AM to 06:00 PM</i>
                </div>
            </div>
            <div class="shop-contact col-lg-6">
                <img class="img-fluid h-100 w-100" src="{{ asset('frontend/images/shop/card/location.webp') }}"
                    alt="shop" loading="lazy">
            </div>
        </div>
    </div>

    @include('frontend::components.widgets.quick-view')
@endsection
