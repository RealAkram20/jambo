<div class="section-padding-top product-detail">
    <div class="container">
        <div class="row">
            <div class="col-lg-6">
                @include('frontend::components.cards.product-tab-slider', [
                    'productSliderImage1' => 'shop/product/01.webp',
                    'productSliderImage2' => 'shop/product/02.webp',
                    'productSliderImage3' => 'shop/product/03.webp',
                    'productSliderImage4' => 'shop/product/04.webp',
                ])

            </div>
            <div class="col-lg-6 mt-lg-0 mt-5 ps-lg-5">
                <h3>{{ $productTitle }}</h3>
                <div class="mt-2 d-flex align-items-center gap-2">
                    <div>
                        @include('frontend::components.cards.filter-rating', ['rating' => '5'])
                    </div>
                    <p class="text-primary mb-0">(2 {{ __('streamTag.customer_reviews') }})</p>
                </div>
                <h4 class="product-detail-price">{{ $productPrice }}</h4>
                <p class="producti-detail-description">{{ $productDescription }}</p>
                <div class="add-product-wrapper pb-5">
                    <ul class="list-inline m-0 p-0 d-flex align-items-center gap-3 flex-wrap">
                        <li>
                            <div class="btn-group iq-qty-btn custom-qty-btn bg-secondary rounded-3" data-qty="btn"
                                role="group">
                                <button type="button"
                                    class="btn btn-sm btn-outline-light iq-quantity-minus text-white border-0">
                                    <i class="ph ph-minus fw-bold"></i>
                                </button>
                                <input type="text" class="btn btn-sm btn-outline-light input-display border-0"
                                    data-qty="input" pattern="^(0|[1-9][0-9]*)$" minlength="1" maxlength="2"
                                    value="2" title="Qty">
                                <button type="button"
                                    class="btn btn-sm btn-outline-light iq-quantity-plus text-white border-0">
                                    <i class="ph ph-plus fw-bold"></i>
                                </button>
                            </div>
                        </li>
                        <li class="wish-list">
                            <a href="wishlist"
                                class="d-inline-block bg-secondary rounded-3  custom-wishlist-btn link-wishlist-btn">
                                <i class="ph-fill ph-heart-straight fs-5"></i>
                            </a>
                        </li>
                        <li>
                            <div class="iq-button">
                                <a href="javascript:void(0)" class="btn btn-primary cart-btn  position-relative rounded-3">
                                    <span class="button-text">{{ __('streamShop.add_to_cart') }}</span>
                                </a>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="product-meta-wrapper">
                    <ul class="list-inline m-0 p-0">
                        <li class="mb-2">
                            <span class="text-white fw-medium">{{ __('streamShop.sku') }}:</span>
                            <h6 class="d-inline text-body ms-2">{{ $productSku }}</h6>
                        </li>
                        <li class="mb-2">
                            <span class="text-white fw-medium">{{ __('streamTag.category') }}:</span>
                            <h6 class="d-inline product-category-list ms-2">{{ $productCategories }}</h6>
                        </li>
                        <li>
                            <span class="text-white fw-medium">{{ __('streamTag.tags') }}:</span>
                            <h6 class="d-inline product-tag-list ms-2">{{ $productTag }}</h6>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="section-padding-top px-0">
            <div class="product-detail-tabs">
                <ul class="list-inline nav nav-pills justify-content-center iq-custom-tab tab-bg-gredient-center flex-md-row flex-column gap-md-5 gap-3 mb-5"
                    id="product-tab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a href="javascript:void(0)" class="nav-link active bg-transparent" data-bs-toggle="tab"
                            data-bs-target="#description" role="tab"
                            aria-selected="true">{{ __('streamShop.description') }}</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a href="javascript:void(0)" class="nav-link bg-transparent" data-bs-toggle="tab"
                            data-bs-target="#additional-information" role="tab"
                            aria-selected="false">{{ __('streamShop.additional_information') }}</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a href="javascript:void(0)" class="nav-link bg-transparent" data-bs-toggle="tab"
                            data-bs-target="#reviews" role="tab"
                            aria-selected="false">{{ __('streamShop.reviews') }}</a>
                    </li>
                </ul>
                <div class="tab-content" id="product-tab-content">
                    <div class="tab-pane fade show active" id="description" role="tabpanel">
                        <p class="m-0">{{ __('streamShop.desc1') }}
                        </p>
                    </div>
                    <div class="tab-pane fade" id="additional-information" role="tabpanel">
                        <h2 class="add-info-title">{{ __('streamShop.additional_information') }}</h2>
                        <div class="table-responsive additional-info-table">
                            <table class="table table-additional">
                                <tbody>
                                    <tr>
                                        <th scope="row" class="text-center">{{ __('streamShop.color') }}</th>
                                        <td>
                                            <p class="mb-0">
                                                <a href="javascript:void(0)"
                                                    class="text-primary fst-italic fw-semibold">Gray</a>
                                            </p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row" class="text-center">{{ __('streamShop.size') }}</th>
                                        <td>
                                            <p class="mb-0">
                                                <a href="javascript:void(0)"  class="text-primary fst-italic fw-semibold">L</a>,
                                                <a href="javascript:void(0)" class="text-primary fst-italic fw-semibold">M</a>
                                            </p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="reviews" role="tabpanel">
                        <h4 class="mb-4">{{ __('streamShop.reviews_believe_mask') }} {{ $productTitle }}</h4>
                        <div class="product-review-list">
                            <ul class="list-inline m-0 p-0">
                                <li class="mb-5 reviews-bg p-4">
                                    <div class="d-flex flex-sm-row flex-column  align-items-start gap-4">
                                        <div class="user-image flex-shrink-0">
                                            <img src="{{ asset('frontend/images/shop/avatar.webp') }}"
                                                alt="user-image" class="img-img-fluid rounded-circle"
                                                loading="lazy" />
                                        </div>
                                        <div class="about-user flex-grow-1">
                                            <div
                                                class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                                <p class="mb-0">{{ __('streamShop.jack_stark') }}</p>
                                                <div class="lh-1 ratting">
                                                    @include('frontend::components.cards.filter-rating', [
                                                        'rating' => '4',
                                                    ])
                                                </div>
                                            </div>

                                            <span class="text-capitalize  published-date">
                                                march 2,
                                                2022</span>
                                            <div class="mt-3 border-top">
                                                <p class="mt-3 mb-0">
                                                    {{ __('streamShop.desc1') }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li class="mb-3 reviews-bg p-4">
                                    <div class="d-flex flex-sm-row flex-column align-items-start gap-4">
                                        <div class="user-image flex-shrink-0">
                                            <img src="{{ asset('frontend/images/shop/avatar.webp') }}"
                                                alt="user-image" class="img-fluid rounded-circle" loading="lazy" />
                                        </div>
                                        <div class="about-user flex-grow-1">
                                            <div
                                                class="d-flex align-items-center  justify-content-between flex-wrap gap-2">
                                                <p class="mb-0">{{ __('streamAccount.john_deo') }}</p>
                                                <div class="lh-1 ratting">
                                                    @include('frontend::components.cards.filter-rating', [
                                                        'rating' => '4',
                                                    ])
                                                </div>
                                            </div>
                                            <span class="text-capitalize published-date">
                                                march 2,
                                                2022</span>
                                            <div class="mt-3 border-top">
                                                <p class="mb-0 mt-3 ">
                                                    {{ __('streamShop.desc1') }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="mt-4 review-form">
                            <h4>{{ __('streamButtons.add_review') }}</h4>
                            <p class="mb-4">{{ __('streamShop.published_email') }}  *
                            </p>
                            <p class="mb-2">{{ __('streamShop.your_rating') }} {{ __('streamShop.required_fields') }} *</p>
                            <form class="needs-validation" novalidate>
                                <div class="ratting">
                                    @include('frontend::components.cards.filter-rating', ['rating' => '5'])
                                </div>
                                <div class="mb-5 mt-4">
                                    <label class="form-label">{{ __('streamShop.your_review') }} *</label>
                                    <textarea class="form-control" required></textarea>
                                </div>
                                <div class="mb-5">
                                    <label class="form-label">{{ __('streamAccount.name') }}*</label>
                                    <input type="text" class="form-control" required>
                                </div>
                                <div class="mb-5">
                                    <label class="form-label">{{ __('streamAccount.email_address') }}*</label>
                                    <input type="email" class="form-control" required>
                                </div>
                                <div class="mb-5 form-check">
                                    <input type="checkbox" class="form-check-input" id="exampleCheck1" required>
                                    <label class="form-check-label"
                                        for="exampleCheck1">{{ __('streamAccount.save_name') }}</label>
                                </div>
                                @include('frontend::components.widgets.custom-button', [
                                    'buttonTitle' => __('streamButtons.submit'),
                                    'buttonUrl' => 'javascript:void(0)',
                                    'notBtncaretIcon' => true,
                                ])

                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="related-product-block section-padding-top">
        <div class="container p-0">
            <div class="overflow-hidden">
                <div class="d-flex align-items-center justify-content-between px-3 my-4">
                    <h5 class="main-title text-capitalize mb-0">{{ __('sectionTitle.related_products') }}</h5>
                    <a href="{{ route('frontend.all-products') }}"
                        class="text-primary iq-view-all text-decoration-none flex-none">{{ __('streamButtons.want_more') }}</a>
                </div>
                <div class="card-style-slider">
                    <div class="position-relative swiper swiper-card" data-slide="4" data-laptop="4" data-tab="3"
                        data-mobile="2" data-mobile-sm="2" data-autoplay="true" data-loop="true"
                        data-navigation="true" data-pagination="true">
                        <ul class="p-0 swiper-wrapper m-0  list-inline">
                            <li class="swiper-slide">
                                @include('frontend::components.cards.product-card', [
                                    'productImg' => '01.webp',
                                    'id' => '01',
                                    'shopTitle' => __('streamShop.product1'),
                                    'shopSale' => __('streamTag.sale!'),
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
                                    'shopSale' => __('streamTag.sale!'),
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

</div>
