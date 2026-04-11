@extends('frontend::layouts.master', ['isBreadCrumb' => true, 'title' => __('profile.all_products')])

@section('content')
<section class="section-padding">
    <div class="container-fluid">
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-5">
            <div class="col mb-5">
                @include('frontend::components.cards.product-card', [
                'productImg' => asset('frontend/images/shop/product/01.webp'),
                'id' => '01',
                'shopTitle' => __('streamShop.product3'),
                'shopSale' => __('streamShop.sale') !,
                'bgShop' => 'primary',
                'shopDel' => "$48.00",
                'shopPrice' => "$28.00",
                'rating' => '4',
                ])
            </div>
            <div class="col mb-5">
                @include('frontend::components.cards.product-card', [
                'productImg' => asset('frontend/images/shop/product/02.webp'),
                'id' => '02',
                'shopTitle' => __('streamShop.product2'),
                'shopSale' => __('streamShop.sale') !,
                'bgShop' => 'primary',
                'shopDel' => '',
                'shopPrice' => "$13.00",
                'rating' => '5',
                ])
            </div>
            <div class="col mb-5">
                @include('frontend::components.cards.product-card', [
                'productImg' => asset('frontend/images/shop/product/03.webp'),
                'id' => '03',
                'shopTitle' => __('streamShop.product3'),
                'shopSale' => __('streamShop.sale') !,
                'bgShop' => 'primary',
                'shopDel' => '',
                'shopPrice' => "$18.00 - $45.00",
                'rating' => '5',
                ])
            </div>
            <div class="col mb-5">
                @include('frontend::components.cards.product-card', [
                'productImg' => asset('frontend/images/shop/product/04.webp'),
                'id' => '04',
                'shopTitle' => __('streamShop.product4'),
                'shopSale' => __('streamShop.sale') !,
                'bgShop' => 'dark',
                'shopDel' => "$10.00",
                'shopPrice' => "$16.00",
                'rating' => '5',
                ])
            </div>
            <div class="col mb-5">
                @include('frontend::components.cards.product-card', [
                'productImg' => asset('frontend/images/shop/product/05.webp'),
                'id' => '05',
                'shopTitle' => __('streamShop.product5'),
                'shopSale' => __('streamShop.sale') !,
                'bgShop' => 'primary',
                'shopDel' => "$20.00",
                'shopPrice' => "$18.00",
                'rating' => '5',
                ])

            </div>
            <div class="col mb-5">
                @include('frontend::components.cards.product-card', [
                'productImg' => asset('frontend/images/shop/product/06.webp'),
                'id' => '06',
                'shopTitle' => __('streamShop.product6'),
                'shopSale' => __('streamShop.sale') !,
                'bgShop' => 'primary',
                'shopDel' => "$20.00",
                'shopPrice' => "$18.00",
                'rating' => '5',
                ])
            </div>
            <div class="col mb-5">
                @include('frontend::components.cards.product-card', [
                'productImg' => asset('frontend/images/shop/product/07.webp'),
                'id' => '06',
                'shopTitle' => __('streamShop.cartoon'),
                'shopSale' => __('streamShop.sale') !,
                'bgShop' => 'primary',
                'shopDel' => '',
                'shopPrice' => "$25.00",
                'rating' => '5',
                ])
            </div>
            <div class="col mb-5">
                @include('frontend::components.cards.product-card', [
                'productImg' => asset('frontend/images/shop/product/08.webp'),
                'id' => '06',
                'shopTitle' => __('streamShop.product8'),
                'shopSale' => __('streamShop.sale') !,
                'bgShop' => 'primary',
                'shopDel' => "$20.00",
                'shopPrice' => "$18.00",
                'rating' => '5',
                ])
            </div>
            <div class="col mb-5">
                @include('frontend::components.cards.product-card', [
                'productImg' => asset('frontend/images/shop/product/09.webp'),
                'id' => '06',
                'shopTitle' => __('streamShop.product9'),
                'shopSale' => __('streamShop.sale') !,
                'bgShop' => 'primary',
                'shopDel' => "$10.00",
                'shopPrice' => "$7.00",
                'rating' => '5',
                ])
            </div>
            <div class="col mb-5">
                @include('frontend::components.cards.product-card', [
                'productImg' => asset('frontend/images/shop/product/01.webp'),
                'id' => '01',
                'shopTitle' => __('streamShop.product3'),
                'shopSale' => __('streamShop.sale') !,
                'bgShop' => 'primary',
                'shopDel' => "$48.00",
                'shopPrice' => "$28.00",
                'rating' => '4',
                ])
            </div>
            <div class="col mb-5">
                @include('frontend::components.cards.product-card', [
                'productImg' => asset('frontend/images/shop/product/02.webp'),
                'id' => '02',
                'shopTitle' => __('streamShop.product2'),
                'shopSale' => __('streamShop.sale') !,
                'bgShop' => 'primary',
                'shopDel' => '',
                'shopPrice' => "$13.00",
                'rating' => '5',
                ])
            </div>
            <div class="col mb-5">
                @include('frontend::components.cards.product-card', [
                'productImg' => asset('frontend/images/shop/product/03.webp'),
                'id' => '03',
                'shopTitle' => __('streamShop.product3'),
                'shopSale' => __('streamShop.sale') !,
                'bgShop' => 'primary',
                'shopDel' => '',
                'shopPrice' => "$18.00 - $45.00",
                'rating' => '5',
                ])
            </div>
            <div class="col mb-5">
                @include('frontend::components.cards.product-card', [
                'productImg' => asset('frontend/images/shop/product/04.webp'),
                'id' => '04',
                'shopTitle' => __('streamShop.product4'),
                'shopSale' => __('streamShop.sale') !,
                'bgShop' => 'dark',
                'shopDel' => "$10.00",
                'shopPrice' => "$16.00",
                'rating' => '5',
                ])
            </div>
            <div class="col mb-5">
                @include('frontend::components.cards.product-card', [
                'productImg' => asset('frontend/images/shop/product/05.webp'),
                'id' => '05',
                'shopTitle' => __('streamShop.product5'),
                'shopSale' => __('streamShop.sale') !,
                'bgShop' => 'primary',
                'shopDel' => "$20.00",
                'shopPrice' => "$18.00",
                'rating' => '5',
                ])

            </div>
            <div class="col mb-5">
                @include('frontend::components.cards.product-card', [
                'productImg' => asset('frontend/images/shop/product/06.webp'),
                'id' => '06',
                'shopTitle' => __('streamShop.product6'),
                'shopSale' => __('streamShop.sale') !,
                'bgShop' => 'primary',
                'shopDel' => "$20.00",
                'shopPrice' => "$18.00",
                'rating' => '5',
                ])
            </div>
            <div class="col mb-5">
                @include('frontend::components.cards.product-card', [
                'productImg' => asset('frontend/images/shop/product/07.webp'),
                'id' => '06',
                'shopTitle' => __('streamShop.cartoon'),
                'shopSale' => __('streamShop.sale') !,
                'bgShop' => 'primary',
                'shopDel' => '',
                'shopPrice' => "$25.00",
                'rating' => '5',
                ])
            </div>
            <div class="col mb-5">
                @include('frontend::components.cards.product-card', [
                'productImg' => asset('frontend/images/shop/product/08.webp'),
                'id' => '06',
                'shopTitle' => __('streamShop.product8'),
                'shopSale' => __('streamShop.sale') !,
                'bgShop' => 'primary',
                'shopDel' => "$20.00",
                'shopPrice' => "$18.00",
                'rating' => '5',
                ])
            </div>
            <div class="col mb-5">
                @include('frontend::components.cards.product-card', [
                'productImg' => asset('frontend/images/shop/product/09.webp'),
                'id' => '06',
                'shopTitle' => __('streamShop.product9'),
                'shopSale' => __('streamShop.sale') !,
                'bgShop' => 'primary',
                'shopDel' => "$10.00",
                'shopPrice' => "$7.00",
                'rating' => '5',
                ])
            </div>
            <div class="col mb-5">
                @include('frontend::components.cards.product-card', [
                'productImg' => asset('frontend/images/shop/product/03.webp'),
                'id' => '03',
                'shopTitle' => __('streamShop.black_bow'),
                'shopSale' => __('streamShop.sale') !,
                'bgShop' => 'primary',
                'shopDel' => '',
                'shopPrice' => "$18.00 - $45.00",
                'rating' => '5',
                ])
            </div>
            <div class="col mb-5">
                @include('frontend::components.cards.product-card', [
                'productImg' => asset('frontend/images/shop/product/04.webp'),
                'id' => '04',
                'shopTitle' => __('streamShop.product4'),
                'shopSale' => __('streamShop.sale') !,
                'bgShop' => 'dark',
                'shopDel' => "$10.00",
                'shopPrice' => "$16.00",
                'rating' => '5',
                ])
            </div>
        </div>
    </div>
</section>
@endsection
