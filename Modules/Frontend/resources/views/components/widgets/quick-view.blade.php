    <div class="modal fade woosq-popup" id="woosq-popup" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered positon-relative">
            <div class="modal-content rounded-0 border-0">
                <div class="modal-body p-0">
                    <button type="button" class="btn-close position-absolute end-0" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <img src="{{ asset('frontend/images/shop/product/01.webp') }}" class="object-cover"
                                alt="shop-img">
                        </div>
                        <div class="col-md-6">
                            <div class="entry-summary p-md-4">
                                <h3>{{ __('streamShop.product1') }}</h3>
                                <div class="review">
                                    @include('frontend::components.cards.filter-rating', ['rating' => 5])
                                </div>
                                <h4 class="price text-white mt-3"><del
                                        class="text-body fw-normal me-1">$48.00</del>$28.00
                                </h4>
                                <p>{{ __('streamShop.desc1') }}</p>
                                <ul
                                    class="list-inline m-0 p-0 d-flex align-items-center gap-3 flex-wrap pt-0 pt-md-4 pb-5">
                                    <li>
                                        <div class="btn-group iq-qty-btn custom-qty-btn rounded-3" data-qty="btn"
                                            role="group">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-light iq-quantity-minus text-white border-0">
                                                <i class="ph ph-minus fw-bold"></i>
                                            </button>
                                            <input type="text"
                                                class="btn btn-sm btn-outline-light input-display border-0"
                                                data-qty="input" pattern="^(0|[1-9][0-9]*)$" minlength="1"
                                                maxlength="2" value="2" title="Qty">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-light iq-quantity-plus text-white border-0">
                                                <i class="ph ph-plus fw-bold"></i>
                                            </button>
                                        </div>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0)" class="btn btn-primary position-relative cart-btn rounded-3">
                                            <span class="button-text">{{ __('streamShop.add_to_cart') }}</span>
                                        </a>
                                    </li>
                                </ul>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fw-semibold text-white">{{ __('streamShop.sku') }}</span>
                                    <span>{{ __('streamShop.product1') }}</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fw-semibold text-white">{{ __('streamTag.category') }}</span>
                                    <span class="text-primary">{{ __('streamShop.uptight_birds') }}</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fw-semibold text-white">{{ __('streamTag.tags') }}</span>
                                    <span class="text-primary">{{ __('streamShop.costume') }}</span>
                                    <span class="text-primary">{{ __('streamShop.lighting') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
