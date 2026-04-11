@extends('frontend::layouts.master', ['isBreadCrumb' => true, 'title' => __('profile.product_detail'), 'isSwiperSlider' => true, 'isSweetalert' => true, 'isFslightbox' => true])

@section('content')
    @include('frontend::components.cards.product-detail', [
        'productTitle' => __('streamShop.product2'),
        'productPrice' => "$13.00",
        'productDescription' => __('streamShop.product1_desc'),
        'productSku' => __('streamShop.product1'),
        'productCategories' => __('streamShop.uptight_birds'),
        'productTag' => __('streamShop.costume') . __('streamShop.lighting'),
    ])

    @include('frontend::components.widgets.quick-view')
    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}
@endsection
