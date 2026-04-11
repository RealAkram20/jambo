<header class="header-center-home header-default header-sticky">
    <nav class="nav navbar navbar-expand-xl navbar-light iq-navbar header-hover-menu py-xl-0">
        <div class="container-fluid navbar-inner">
            <div class="d-flex align-items-center justify-content-between w-100 landing-header">
                <div class="d-flex gap-3 gap-xl-0 align-items-center">
                    <div class="d-flex align-items-center gap-2 gap-md-3">
                        @include('frontend::components.brand.logo')
                        <div>
                            <a href="{{ route('frontend.pricing-page') }}"
                                class="subscribe-btn btn btn-warning-subtle py-1 py-md-2 px-2 px-ms-3">
                                <span class="d-flex align-items-center gap-2 text-warning">
                                    <i class="ph-fill ph-crown align-middle fs-6"></i>
                                    <span class="d-xl-block d-none">{{ __('frontendfooter.subscribe') }}</span>
                                </span>
                            </a>
                        </div>
                    </div>
                </div>

                <nav id="navbar_main"
                    class="offcanvas mobile-offcanvas nav navbar navbar-expand-xl hover-nav horizontal-nav py-xl-0 w-100 {{ isset($IS_MEGA) && $IS_MEGA ? 'mega-menu-content' : '' }}">
                    <div class="container-fluid p-lg-0">
                        <div class="offcanvas-header px-0">
                            <div class="navbar-brand ms-3">
                                @include('frontend::components.brand.logo')
                            </div>
                            <button type="button" class="btn-close float-end px-3" data-bs-dismiss="offcanvas"
                                aria-label="Close"></button>
                        </div>
                        <ul class="navbar-nav iq-nav-menu list-unstyled" id="header-menu">
                            <li class="nav-item">

                                <a class="nav-link" data-bs-toggle="collapse" href="#homePages" role="button"
                                    aria-expanded="false" aria-controls="homePages">
                                    <div class="d-flex justify-content-between">
                                        <span class="item-name">{{ __('frontendheader.home') }}</span>
                                        <span class="menu-icon">
                                            <i class="ph ph-caret-down align-middle"></i>
                                        </span>
                                    </div>
                                    </span>
                                </a>
                                <ul class="sub-nav collapse justify-content-center list-unstyled {{ isset($IS_MEGA) && $IS_MEGA ? 'mega-menu-item' : '' }}"
                                    id="homePages">
                                    <li class="nav-item">
                                        <a class="nav-link {{ isset($IS_MEGA) && '' }} {{ activeRoute(route('frontend.ott')) }}"
                                            href="{{ route('frontend.ott') }}">
                                            @if (isset($IS_MEGA))
                                                <img src="{{ asset('frontend/images/mega-menu/new-home.webp') }}"
                                                    alt="img" class="img-fluid d-xl-block d-none" loading="lazy" />
                                            @endif

                                            <span class="d-inline-block">{{ __('frontendheader.ott_home') }}</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ isset($IS_MEGA) && '' }} {{ activeRoute(route('frontend.index')) }}"
                                            href="{{ route('frontend.index') }}">
                                            @if (isset($IS_MEGA))
                                                <img src="{{ asset('frontend/images/mega-menu/home.webp') }}"
                                                    alt="img" class="img-fluid d-xl-block d-none" loading="lazy" />
                                            @endif
                                            <span class="d-inline-block">{{ __('frontendheader.main_home') }}</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ isset($IS_MEGA) && '' }} {{ activeRoute(route('frontend.movie')) }}"
                                            href="{{ route('frontend.movie') }}">
                                            @if (isset($IS_MEGA))
                                                <img src="{{ asset('frontend/images/mega-menu/movie.webp') }}"
                                                    alt="img" class="img-fluid d-xl-block d-none" loading="lazy" />
                                            @endif
                                            <span class="d-inline-block">{{ __('frontendheader.movie') }}</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ isset($IS_MEGA) && '' }} {{ activeRoute(route('frontend.tv-show')) }}"
                                            href="{{ route('frontend.tv-show') }}">
                                            @if (isset($IS_MEGA))
                                                <img src="{{ asset('frontend/images/mega-menu/tv-show.webp') }}"
                                                    alt="img" class="img-fluid d-xl-block d-none" loading="lazy" />
                                            @endif
                                            <span class="d-inline-block">{{ __('frontendheader.tv_show') }}</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ isset($IS_MEGA) && '' }} {{ activeRoute(route('frontend.video')) }}"
                                            href="{{ route('frontend.video') }}">
                                            @if (isset($IS_MEGA))
                                                <img src="{{ asset('frontend/images/mega-menu/video.webp') }}"
                                                    alt="img" class="img-fluid d-xl-block d-none" loading="lazy" />
                                            @endif
                                            <span class="d-inline-block">{{ __('frontendheader.video') }}</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ isset($IS_MEGA) && '' }} {{ activeRoute(route('frontend.merchandise')) }}"
                                            href="{{ route('frontend.merchandise') }}">
                                            @if (isset($IS_MEGA))
                                                <img src="{{ asset('frontend/images/mega-menu/shop-home.webp') }}"
                                                    alt="img" class="img-fluid d-xl-block d-none" loading="lazy" />
                                            @endif
                                            <span
                                                class="d-inline-block">{{ __('frontendheader.merchandise_store') }}</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="collapse" href="#features" role="button"
                                    aria-expanded="false" aria-controls="homePages">
                                    <div class="d-flex justify-content-between">
                                        <span class="item-name">{{ __('frontendheader.features') }}</span>
                                        <span class="menu-icon">
                                            <i class="ph ph-caret-down align-middle"></i>
                                        </span>
                                    </div>
                                </a>
                                <ul class="sub-nav collapse list-unstyled" id="features">
                                    <li class="nav-item">
                                        <a class="nav-link {{ activeRoute(route('frontend.download')) }}"
                                            href="{{ route('frontend.download') }}">
                                            <span> {{ __('frontendheader.download_movie') }} </span></a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ activeRoute(route('frontend.resticted')) }}"
                                            href="{{ route('frontend.resticted') }}">
                                            <span>{{ __('frontendheader.restricted_content') }} </span></a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ activeRoute(route('frontend.releted_merchandies')) }}"
                                            href="{{ route('frontend.releted_merchandies') }}">
                                            <span> {{ __('frontendheader.related_merchandise') }}</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ activeRoute(route('frontend.genres')) }}"
                                            href="{{ route('frontend.genres') }}"> <span>
                                                {{ __('frontendheader.geners') }}</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ activeRoute(route('frontend.tag')) }}"
                                            href="{{ route('frontend.tag') }}">
                                            <span>{{ __('frontendheader.tags') }}</span> </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ activeRoute(route('frontend.cast_list')) }}"
                                            href="{{ route('frontend.cast_list') }}">
                                            <span>{{ __('frontendheader.cast') }}</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="collapse" href="#pages" role="button"
                                    aria-expanded="false" aria-controls="homePages">
                                    <div class="d-flex justify-content-between">
                                        <span class="item-name">{{ __('frontendheader.pages') }}</span>
                                        <span class="menu-icon">
                                            <i class="ph ph-caret-down align-middle"></i>
                                        </span>
                                    </div>
                                </a>
                                <ul class="sub-nav collapse list-unstyled" id="pages">
                                    <li class="nav-item">
                                        <a class="nav-link {{ activeRoute(route('frontend.about_us')) }}"
                                            href="{{ route('frontend.about_us') }}">
                                            <span> {{ __('frontendheader.about_us') }}</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ activeRoute(route('frontend.contact_us')) }}"
                                            href="{{ route('frontend.contact_us') }}">
                                            <span> {{ __('frontendheader.contact_us') }}</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ activeRoute(route('frontend.faq_page')) }}"
                                            href="{{ route('frontend.faq_page') }}">
                                            <span>{{ __('frontendheader.faq') }}</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ activeRoute(route('frontend.pricing-page')) }}"
                                            href="{{ route('frontend.pricing-page') }}">
                                            <span> {{ __('frontendheader.pricing_plan') }}</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ activeRoute(route('frontend.privacy-policy')) }}"
                                            href="{{ route('frontend.privacy-policy') }}">
                                            <span> {{ __('frontendheader.privacy_policy') }}</span></a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ activeRoute(route('frontend.terms-and-policy')) }}"
                                            href="{{ route('frontend.terms-and-policy') }}">
                                            <span> {{ __('pages_sections.terms_and_use') }}</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ activeRoute(route('frontend.comming-soon')) }}"
                                            href="{{ route('frontend.comming-soon') }}">
                                            <span> {{ __('frontendheader.coming_soon') }}</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ activeRoute(route('frontend.error_page1')) }}"
                                            href="{{ route('frontend.error_page1') }}">
                                            <span> {{ __('sidebar.error_404') }}</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="collapse" href="#blog" role="button"
                                    aria-expanded="false" aria-controls="blog">
                                    <div class="d-flex justify-content-between">
                                        <span class="item-name">{{ __('frontendheader.blog') }}</span>
                                        <span class="menu-icon">
                                            <i class="ph ph-caret-down align-middle"></i>
                                        </span>
                                    </div>
                                </a>
                                <ul class="sub-nav collapse list-unstyled" id="blog">
                                    <li class="nav-item {{ activeRoute(route('frontend.list_page')) }}">
                                        <a class="nav-link" href="{{ route('frontend.list_page') }}">
                                            <span> {{ __('frontendheader.listing') }}</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-bs-toggle="collapse" href="#blog-grid"
                                            role="button" aria-expanded="false" aria-controls="blog-grid">
                                            <div class="d-flex justify-content-between">
                                                <span class="item-name"> {{ __('frontendheader.blog_grid') }}</span>
                                                <span class="menu-icon">
                                                    <i class="ph ph-caret-down align-middle down-to-right"></i>
                                                </span>
                                            </div>
                                        </a>
                                        <ul class="sub-nav collapse list-unstyled" id="blog-grid">
                                            <li class="nav-item">
                                                <a class="nav-link {{ activeRoute(route('frontend.one_column_grid')) }}"
                                                    href="{{ route('frontend.one_column_grid') }}">
                                                    <span> {{ __('frontendheader.one_column_blog') }} </span></a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link {{ activeRoute(route('frontend.two_column_grid')) }}"
                                                    href="{{ route('frontend.two_column_grid') }}">
                                                    <span> {{ __('frontendheader.two_column_blog') }} </span></a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link {{ activeRoute(route('frontend.three_column_grid')) }}"
                                                    href="{{ route('frontend.three_column_grid') }}">
                                                    <span> {{ __('frontendheader.three_column_blog') }} </span></a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link {{ activeRoute(route('frontend.four_column_grid')) }}"
                                                    href="{{ route('frontend.four_column_grid') }}">
                                                    <span> {{ __('frontendheader.four_column_blog') }} </span></a>
                                            </li>
                                        </ul>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-bs-toggle="collapse" href="#blog-sidebar"
                                            role="button" aria-expanded="false" aria-controls="blog-sidebar">
                                            <div class="d-flex justify-content-between">
                                                <span class="item-name">{{ __('frontendheader.blog_sidebar') }}</span>
                                                <span class="menu-icon">
                                                    <i class="ph ph-caret-down align-middle down-to-right"></i>
                                                </span>
                                            </div>
                                        </a>
                                        <ul class="sub-nav collapse list-unstyled" id="blog-sidebar">
                                            <li class="nav-item">
                                                <a class="nav-link {{ activeRoute(route('frontend.left_sidebar')) }}"
                                                    href="{{ route('frontend.left_sidebar') }}">
                                                    <span> {{ __('frontendheader.left_sidebar') }} </span>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link {{ activeRoute(route('frontend.right_sidebar')) }}"
                                                    href="{{ route('frontend.right_sidebar') }}">
                                                    <span> {{ __('frontendheader.right_sidebar') }}</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-bs-toggle="collapse" href="#blog-single"
                                            role="button" aria-expanded="false" aria-controls="blog-single">
                                            <div class="d-flex justify-content-between">
                                                <span class="item-name">{{ __('frontendheader.blog_single') }}</span>
                                                <span class="menu-icon">
                                                    <i class="ph ph-caret-down align-middle down-to-right"></i>
                                                </span>
                                            </div>
                                        </a>
                                        <ul class="sub-nav collapse list-unstyled" id="blog-single">
                                            <li class="nav-item">
                                                <a class="nav-link {{ activeRoute(route('frontend.detail_page')) }}"
                                                    href="{{ route('frontend.detail_page') }}">
                                                    <span> {{ __('frontendheader.standard') }} </span>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link {{ activeRoute(route('frontend.blog_audio')) }}"
                                                    href="{{ route('frontend.blog_audio') }}">
                                                    <span> {{ __('frontendheader.audio') }}</span>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link {{ activeRoute(route('frontend.blog_video')) }}"
                                                    href="{{ route('frontend.blog_video') }}">
                                                    <span> {{ __('frontendheader.video') }}</span>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link {{ activeRoute(route('frontend.blog_link')) }}"
                                                    href="{{ route('frontend.blog_link') }}">
                                                    <span> {{ __('frontendheader.link') }}</span>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link {{ activeRoute(route('frontend.blog_quote')) }}"
                                                    href="{{ route('frontend.blog_quote') }}">
                                                    <span> {{ __('frontendheader.quote') }}</span>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link {{ activeRoute(route('frontend.blog_gallery')) }}"
                                                    href="{{ route('frontend.blog_gallery') }}">
                                                    <span> {{ __('frontendheader.gallery') }} </span>
                                                </a>
                                            </li>
                                        </ul>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-bs-toggle="collapse" href="#style-pagination"
                                            role="button" aria-expanded="false" aria-controls="style-pagination">
                                            <div class="d-flex justify-content-between">
                                                <span
                                                    class="item-name">{{ __('frontendblogs.pagination_style') }}</span>
                                                <span class="menu-icon">
                                                    <i class="ph ph-caret-down align-middle down-to-right"></i>
                                                </span>
                                            </div>
                                        </a>
                                        <ul class="sub-nav collapse list-unstyled" id="style-pagination">
                                            <li class="nav-item">
                                                <a class="nav-link {{ activeRoute(route('frontend.blog_pagination')) }}"
                                                    href="{{ route('frontend.blog_pagination') }}">
                                                    <span> {{ __('frontendblogs.blog_pagination') }}</span>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link {{ activeRoute(route('frontend.blog_loadmore')) }}"
                                                    href="{{ route('frontend.blog_loadmore') }}">
                                                    <span> {{ __('frontendblogs.blog_load_more') }}</span>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link {{ activeRoute(route('frontend.blog_infinite_scroll')) }}"
                                                    href="{{ route('frontend.blog_infinite_scroll') }}">
                                                    <span> {{ __('frontendblogs.blog_infinite_scroll') }}</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </li>
                                </ul>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="collapse" href="#shop" role="button"
                                    aria-expanded="false" aria-controls="shop">
                                    <div class="d-flex justify-content-between">
                                        <span class="item-name">{{ __('frontendheader.shop') }}</span>
                                        <span class="menu-icon">
                                            <i class="ph ph-caret-down align-middle"></i>
                                        </span>
                                    </div>
                                </a>
                                <ul class="sub-nav collapse list-unstyled" id="shop">
                                    <li class="nav-item">
                                        <a class="nav-link" href="{{ route('frontend.shop') }}">
                                            <span> {{ __('frontendheader.shop') }} </span> </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ activeRoute(route('frontend.my_account')) }}"
                                            href="{{ route('frontend.my_account') }}">
                                            <span> {{ __('frontendheader.my_account_page') }} </span> </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ activeRoute(route('frontend.cart')) }}"
                                            href="{{ route('frontend.cart') }}">
                                            <span> {{ __('frontendheader.cart_page') }} </span> </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ activeRoute(route('frontend.wishlist')) }}"
                                            href="{{ route('frontend.wishlist') }}">
                                            <span> {{ __('frontendheader.wishlist_page') }}</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ activeRoute(route('frontend.chekout')) }}"
                                            href="{{ route('frontend.chekout') }}">
                                            <span> {{ __('frontendheader.checkout_page') }}</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ activeRoute(route('frontend.order_tracking')) }}"
                                            href="{{ route('frontend.order_tracking') }}">
                                            <span>{{ __('frontendheader.order_tracking') }} </span></a>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                    <!-- container-fluid.// -->
                </nav>
                <div class="css_prefix-header-right d-flex align-items-center gap-2">
                    {{-- <div class="collapse navbar-collapse" id="navbarSupportedContent"> --}}
                    <ul
                        class="list-inline d-flex align-items-center gap-3 gap-md-4 mb-0 ps-0 justify-content-md-end justify-content-between">
                        <li class="nav-item dropdown iq-responsive-menu d-xl-block d-none">
                            <div class="search-box">
                                <a href="javascript:void(0)" class="nav-link p-0 text-white" id="search-drop"
                                    data-bs-toggle="dropdown">
                                    <div class="btn-icon btn-sm rounded-pill btn-action">
                                        <span class="btn-inner">
                                            <i class="ph ph-magnifying-glass p-0"></i>
                                        </span>
                                    </div>
                                </a>
                                <ul class="dropdown-menu p-0 dropdown-search m-0 iq-search-bar" style="width: 20rem;">
                                    <li class="p-0">
                                        <div class="form-group input-group mb-0">
                                            <input type="text" class="form-control border-0"
                                                placeholder="{{ __('frontendheader.search_dot') }}">
                                            <button type="submit" class="search-submit">
                                                <i class="ph ph-magnifying-glass"></i>
                                            </button>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        <li class="nav-items dropdown">
                            <a class="nav-link shoping-cart-button text-white"href="javascript:void(0)" role="button"
                                id="languageDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="ph ph-translate p-0"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end border-0 p-0 m-0"
                                aria-labelledby="languageDropdown">
                                @foreach (config('app.available_locales') as $locale => $title)
                                    <li>
                                        <a href="{{ route('language.switch', $locale) }}"
                                            class="dropdown-item">
                                            {{ $title }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link shoping-cart-button text-white" href=""
                                data-bs-toggle="offcanvas" data-bs-target="#shoping-cart-toggle"
                                aria-controls="shoping-cart-toggle" aria-label="Open shopping cart">
                                <i class="ph ph-bag p-0"></i>
                                <span class="bg-primary text-white shopping-badge">1</span>
                            </a>
                        </li>
                        @if (auth()->user())
                            <li class="nav-item dropdown" id="itemdropdown1">
                                <a class="nav-link d-flex align-items-center p-0"href="javascript:void(0)" id="navbarDropdown"
                                    role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <div class="st-avatar style-1">
                                        <img src="{{ asset('frontend/images/user/user6.jpg') }}"
                                            alt="{{ __('frontendheader.user_avatar_alt') }}"
                                            class="img-fluid rounded-circle dropdown-user-menu-image header-user-image"
                                            loading="lazy">
                                    </div>
                                </a>
                                <div class="dropdown-menu dropdown-user-menu dropdown-menu-end border border-gray-900 rounded-3"
                                    data-popper-placement="bottom-end"
                                    style="position: absolute; inset: 0px 0px auto auto; margin: 0px; transform: translate(0px, 74px);">
                                    <div class="user-dropdown-inner">
                                        <!-- User Info -->
                                        <div class="d-flex align-items-center gap-3 rounded mb-4">
                                            <div class="image flex-shrink-0">
                                                <img src="{{ asset('frontend/images/user/user6.jpg') }}"
                                                    class="img-fluid rounded-3 dropdown-user-menu-image"
                                                    alt="{{ __('frontendheader.user_name') }}">
                                            </div>
                                            <div class="content">
                                                <h6 class="mb-1">{{ __('frontendheader.user_name') }}</h6>
                                            </div>
                                        </div>

                                        <!-- Menu Items -->
                                        <ul class="d-flex flex-column gap-3 list-inline m-0 p-0">
                                            <li>
                                                <a href="{{ route('frontend.profile-marvin') }}"
                                                    class="link-body-emphasis font-size-14 d-flex align-items-center gap-2">
                                                    <i class="ph ph-user"></i>
                                                    <span class="fw-medium">{{ __('frontendheader.profile') }}</span>
                                                </a>
                                            </li>
                                            <li>
                                                <a href="{{ route('frontend.watchlist_detail') }}"
                                                    class="link-body-emphasis font-size-14 d-flex align-items-center gap-2">
                                                    <i class="ph ph-plus"></i>
                                                    <span
                                                        class="fw-medium">{{ __('frontendheader.watch_list') }}</span>
                                                </a>
                                            </li>
                                            <li>
                                                <a href="{{ route('frontend.archive-playlist') }}"
                                                    class="link-body-emphasis font-size-14 d-flex align-items-center gap-2">
                                                    <i class="ph ph-playlist"></i>
                                                    <span
                                                        class="fw-medium">{{ __('frontendheader.playlist') }}</span>
                                                </a>
                                            </li>
                                            <li>
                                                <a href="{{ route('frontend.profile-marvin') }}"
                                                    class="link-body-emphasis font-size-14 d-flex align-items-center gap-2">
                                                    <i class="ph ph-bell"></i>
                                                    <span
                                                        class="fw-medium">{{ __('frontendheader.notification') }}</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>

                                    <!-- Logout -->
                                    <a href="{{ route('logout') }}"
                                        class="btn btn-link p-3 d-block font-size-14 text-center text-decoration-none border-top"
                                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                        <span class="d-flex align-items-center justify-content-center gap-2 fw-medium">
                                            <i class="ph ph-sign-out"></i>
                                            {{ __('frontendheader.logout') }}
                                        </span>
                                    </a>
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                        style="display: none;">@csrf</form>
                                </div>
                            </li>
                        @else
                            <li class="nav-item dropdown" id="itemdropdown1">
                                <a href="{{ route('login') }}"
                                    class="nav-link">{{ __('frontendheader.login') }}</a>
                            </li>
                        @endif
                    </ul>
                    <button class="navbar-toggler d-block d-xl-none text-white" type="button"
                        data-bs-toggle="offcanvas" data-bs-target="#navbar_main" aria-controls="navbar_main">
                        <i class="ph ph-list"></i>
                    </button>
                    {{-- </div> --}}
                </div>
            </div>
        </div>
    </nav>
</header>

<div class="offcanvas overflow-y-auto widget-shopping-cart-content offcanvas-end offcanvas-sidebar sidebar-container on-rtl end border-0"
    tabindex="-1" id="shoping-cart-toggle">
    <div class="offcanvas-header position-relative">
        <h5 class="offcanvas-title fw-500" id="offcanvasExampleLabel">
            {{ __('streamShop.shopping_cart') }} ( <span class="streamit-cart-count" aria-live="polite">1</span> )
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <div class="product-list-content">
            <ul class="list-unstyled mb-0">
                <li class="mini-cart-item d-flex align-items-start gap-3">
                    <div class="cart-img">
                        <a href="{{ route('frontend.cart') }}" aria-label="Bag Pack">
                            <img src="{{ asset('/frontend/images/shop/product/01.webp') }}" class="img-fluid"
                                width="300" height="400" alt="Bag Pack">
                        </a>
                    </div>
                    <div class="cart-content flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center">
                            <a class="d-block" href="product-detail" aria-label="Bag Pack">
                                <h6 class="fw-500">{{ __('streamShop.product1') }}</h6>
                            </a>
                            <a href="javascript:void(0)" class="delete-btn">
                                <i class="ph ph-trash text-primary"></i>
                            </a>
                        </div>
                        <div class="product-price text-muted">
                            <span class="woocommerce-Price-amount amount"><span
                                    class="woocommerce-Price-amount amount"><span
                                        class="woocommerce-Price-currencySymbol">₹</span>11.05</span></span>
                        </div>
                        <div class="btn-group iq-qty-btn custom-qty-btn rounded-3" data-qty="btn" role="group">
                            <button type="button"
                                class="btn btn-sm btn-outline-light iq-quantity-minus text-white border-0">
                                <i class="ph ph-minus"></i>
                            </button>
                            <input type="text" class="btn btn-sm btn-outline-light input-display border-0"
                                data-qty="input" pattern="^(0|[1-9][0-9]*)$" minlength="1" maxlength="2"
                                value="2" title="Qty">
                            <button type="button"
                                class="btn btn-sm btn-outline-light iq-quantity-plus text-white border-0">
                                <i class="ph ph-plus"></i>
                            </button>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </div>
    <div class="offcanvas-footer border-top py-3 px-3">
        <div class="d-flex align-items-center justify-content-between gap-3">
            <strong>{{ __('streamShop.subtotal') }}:</strong>
            <span class="st-woocommerce-Price-amount amount"><span class="woocommerce-Price-amount amount"><span
                        class="woocommerce-Price-currencySymbol">₹</span>11.05</span></span>
        </div>
        <div class="mini-cart-buttons d-flex flex-column align-items-center gap-3 mt-4">
            <div class="iq-button w-100">
                <a href="{{ route('frontend.chekout') }}" class="btn btn-primary text-capitalize w-100 rounded-3">
                    <span class="button-text">{{ __('frontendheader.checkout_page') }}</span>
                </a>
            </div>
            <div class="w-100">
                <a href="{{ route('frontend.cart') }}" class="btn btn-secondary text-capitalize w-100 rounded-3">
                    <span class="button-text">{{ __('streamButtons.view_cart') }}</span>
                </a>
            </div>
        </div>
    </div>
</div>
