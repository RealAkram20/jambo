@if (isset($isProductList) && $isProductList)
    <div class="product-block product-list p-0">
        <div class="row">
            <div class="col-xl-3 col-md-4">
                @if (isset($shopSale) && $shopSale)
                    <span class="onsale bg-primary">
                        {{ $shopSale }}
                    </span>
                @endif
                <div class="image-wrap">
                    <a href="{{ url('product-detail') }}">
                        <div class="product-image">
                            <img src="{{asset('frontend/images/shop/product/'. $productImg) }}" class="img-fluid w-100 rounded-3" alt="productImg-{{ $id }}" loading="lazy" />
                        </div>
                    </a>
                    <div class="buttons-holder">
                        <ul class="list-unstyled m-0 p-0">
                            <li>
                                <a class="cursor-pointer" data-bs-toggle="modal" data-bs-target="#woosq-popup">
                                    <i class="ph-fill ph-eye"></i>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="product-caption">
                    <h4 class="product__title mb-2">
                        <a href="{{ url('product-detail') }}" class="title-link">
                            {{ $shopTitle }}
                        </a>
                    </h4>
                    <div class="price-detail">
                        <div class="price">
                            <del>{{ $shopDel }}</del>{{ $shopPrice }}
                        </div>
                    </div>
                    <div class="container-rating">
                        <div class="star-rating text-primary">
                            @include('frontend::components.cards.filter-rating')
                        </div>
                    </div>
                    <ul class="iq-button-holder list-inline d-flex align-items-center flex-wrap gap-3">
                        <li>
                            <div>
                                <a href="javascript:void(0)" class="btn btn-primary cart-btn position-relative rounded-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="button-text">{{ __('streamShop.add_to_cart') }}</span>
                                        <i class="ph ph-shopping-cart fs-5"></i>
                                    </div>
                                </a>
                            </div>
                        </li>
                        <li>
                            <a href="javascript:void(0)" class="add_to_wishlist wishlist-btn d-flex align-items-center justify-content-center border-0 rounded-3">
                                <i class="ph ph-heart fs-5"></i>
                            </a>
                        </li>
                    </ul>
                    <p class="blog-desc line-count-2">{{ $shopDesc }}</p>
                </div>
            </div>
        </div>
    </div>
@else
    <div class="product-block">
        @if (isset($shopSale) && $shopSale)
            <span class="onsale bg-primary">
                {{ $shopSale }}
            </span>
        @endif
        <div class="image-wrap">
            <a href="{{ url('product-detail') }}">
                <div class="product-image">
                    <img src="{{asset('frontend/images/shop/product/'. $productImg) }}" class="img-fluid w-100 rounded-3" alt="productImg-{{ $id }}" loading="lazy" />
                </div>
            </a>
            <div class="buttons-holder">
                <ul class="list-unstyled m-0 p-0">
                    <li>
                        <a class="cursor-pointer" data-bs-toggle="modal" data-bs-target="#woosq-popup">
                            <i class="ph ph-eye fs-5"></i>
                        </a>
                    </li>
                    <li>
                        <a href="javascript:void(0)" class="add_to_wishlist wishlist-btn border-0 rounded-3">
                            <i class="ph ph-heart fs-5"></i>
                        </a>
                    </li>
                    <li>
                        <a href="javascript:void(0)" class="added_to_cart cart-btn d-flex align-items-center">
                            <i class="ph ph-shopping-cart fs-5"></i>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="product-caption">
            <a href="{{ url('product-detail') }}" class="title-link text-primary">
                {{ $shopTitle }}
            </a>
            <div class="price-detail">
                <div class="price fw-medium">
                    <del>{{ $shopDel }}</del>{{ $shopPrice }}
                </div>
            </div>

            @if (!empty($ratingStars))

            @else
            <div class="container-rating">
                <div class="star-rating text-primary">
                    @include('frontend::components.cards.filter-rating')
                </div>
            </div>
            @endif
        </div>
    </div>
@endif
