@if (isset($isProductImage) && $isProductImage)
    <div class="position-relative">
        <a data-fslightbox="product" href="{{ asset('frontend/images/' . $productImage) }}">
            <img src="{{ asset('frontend/images/' . $productImage) }}" class="img-fluid w-100 product-detail-image"
                alt="product-image" loading="lazy">
        </a>
        @if (isset($isSale) && $isSale)
            <span class="iq-on-sale badge bg-dark">Sale!</span>
        @endif
        @if (isset($isNew) && $isNew)
            <span class="iq-on-new badge bg-primary">New</span>
        @endif
    </div>
@else
    <div class="product-tab-slider">
        <div class="swiper product-tab-slider-thumb" data-swiper="slider-prodcut-images">
            <div class="swiper-wrapper m-0">
                <div class="swiper-slide p-0">
                    <a data-fslightbox="product" href="{{ asset('frontend/images/' . $productSliderImage1) }}">
                        <img src="{{ asset('frontend/images/' . $productSliderImage1) }}"
                            class="img-fluid product-detail-image" alt="product-slide-image">
                    </a>
                </div>
                <div class="swiper-slide p-0">
                    <a data-fslightbox="product" href="{{ asset('frontend/images/' . $productSliderImage2) }}">
                        <img src="{{ asset('frontend/images/' . $productSliderImage2) }}"
                            class="img-fluid product-detail-image" alt="product-slide-image">
                    </a>
                </div>
                <div class="swiper-slide p-0">
                    <a data-fslightbox="product" href="{{ asset('frontend/images/' . $productSliderImage3) }}">
                        <img src="{{ asset('frontend/images/' . $productSliderImage3) }}"
                            class="img-fluid product-detail-image" alt="product-slide-image">
                    </a>
                </div>
                <div class="swiper-slide p-0">
                    <a data-fslightbox="product" href="{{ asset('frontend/images/' . $productSliderImage4) }}">
                        <img src="{{ asset('frontend/images/' . $productSliderImage4) }}"
                            class="img-fluid product-detail-image" alt="product-slide-image">
                    </a>
                </div>
            </div>
        </div>
        <div class="swiper product-tab-slider-images" data-swiper="slider-thumbs-product">
            <div class="swiper-wrapper m-0">
                <div class="swiper-slide p-0">
                    <img src="{{ asset('frontend/images/' . $productSliderImage1) }}" class="img-fluid"
                        alt="product-slide-image">
                </div>
                <div class="swiper-slide p-0">
                    <img src="{{ asset('frontend/images/' . $productSliderImage2) }}" class="img-fluid"
                        alt="product-slide-image">
                </div>
                <div class="swiper-slide p-0">
                    <img src="{{ asset('frontend/images/' . $productSliderImage3) }}" class="img-fluid"
                        alt="product-slide-image">
                </div>
                <div class="swiper-slide p-0">
                    <img src="{{ asset('frontend/images/' . $productSliderImage4) }}" class="img-fluid"
                        alt="product-slide-image">
                </div>
            </div>
        </div>
    </div>
@endif
