<header class="header-center-home header-merchandise">
    <div class="header-top d-xl-block d-none">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <div class="d-flex align-items-center gap-2 gap-md-3">
                        @include('frontend::components.brand.logo')
                        <div>
                            <a href="{{ route('frontend.pricing-page') }}"
                                class="subscribe-btn btn btn-warning-subtle py-1 py-md-2 px-2 px-ms-3">
                                <span class="d-flex align-items-center gap-2 text-warning">
                                    <i class="ph-fill ph-crown align-middle fs-6"></i>
                                    <span>{{ __('frontendfooter.subscribe') }}</span>
                                </span>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-9">
                    <ul class="d-flex align-items-center justify-content-end gap-3 list-inline mb-0">
                        <li>
                            <div class="search-box-2">
                                <form>
                                    <input type="text" class="form-control" placeholder="Search...">
                                    <button type="submit" class="search-btn d-flex align-items-center">
                                        <i class="ph ph-magnifying-glass"></i>
                                    </button>
                                </form>
                            </div>
                        </li>
                        <li>
                            <a class="shoping-cart-button text-white"href="javascript:void(0)" role="button"
                                id="languageDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="btn-icon rounded-pill user-icons">
                                    <i class="ph ph-translate fs-5"></i>
                                </span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end border-0 p-0 m-0"
                                aria-labelledby="languageDropdown">
                                @foreach (config('app.available_locales') as $locale => $title)
                                    <li>
                                        <a href="{{ route('language.switch', $locale) }}"
                                            class="dropdown-item {{ App::getLocale() === $locale ? 'active' : '' }}">
                                            {{ $title }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                        <li>
                            <a href="#offcanvasCart" class="text-white" data-bs-toggle="offcanvas">
                                <span class="btn-icon rounded-pill user-icons">
                                    <i class="ph ph-handbag fs-5"></i>
                                </span>
                            </a>
                        </li>
                        @if (auth()->user())
                            <li class="nav-item dropdown" id="itemdropdown1">
                                <a class="nav-link d-flex align-items-center"href="javascript:void(0)" id="navbarDropdown"
                                    role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <div class="btn-icon rounded-pill user-icons">
                                        <span class="btn-inner d-flex align-items-center justify-content-center">
                                            <i class="ph ph-user-plus fs-5"></i>
                                        </span>
                                    </div>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end dropdown-user border-0 p-0 m-0"
                                    aria-labelledby="navbarDropdown">
                                    <li class="user-info mb-3">
                                        <a href="{{ route('frontend.profile-marvin') }}"
                                            class="d-flex align-items-center gap-3">
                                        <img src="{{ asset('/frontend/images/user/user1.webp') }}" class="img-fluid"
                                            alt="img" loading="lazy" />
                                        <span class="font-size-14 fw-500 text-capitalize text-white">Jenny</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('frontend.my_account') }}"
                                            class="iq-sub-card d-flex align-items-center gap-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                viewBox="0 0 16 22" fill="none">
                                                <path fill-rule="evenodd" clip-rule="evenodd"
                                                    d="M7.84455 20.6621C4.15273 20.6621 1 20.0876 1 17.7868C1 15.486 4.13273 13.3621 7.84455 13.3621C11.5364 13.3621 14.6891 15.4654 14.6891 17.7662C14.6891 20.066 11.5564 20.6621 7.84455 20.6621Z"
                                                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                                    stroke-linejoin="round"></path>
                                                <path fill-rule="evenodd" clip-rule="evenodd"
                                                    d="M7.83725 10.1738C10.26 10.1738 12.2236 8.21015 12.2236 5.78742C12.2236 3.36469 10.26 1.40015 7.83725 1.40015C5.41452 1.40015 3.44998 3.36469 3.44998 5.78742C3.4418 8.20196 5.3918 10.1656 7.80634 10.1738C7.81725 10.1738 7.82725 10.1738 7.83725 10.1738Z"
                                                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                                    stroke-linejoin="round"></path>
                                            </svg>
                                            <h6 class="mb-0 font-size-14 fw-normal">My Account</h6>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('frontend.watchlist_detail') }}"
                                            class="iq-sub-card d-flex align-items-center gap-3">
                                            <svg width="16" height="16" viewBox="0 0 24 24"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path d="m0 0h24v24h-24z" fill="#fff" opacity="0"
                                                    transform="matrix(-1 0 0 -1 24 24)"></path>
                                                <path
                                                    d="m19 11h-6v-6a1 1 0 0 0 -2 0v6h-6a1 1 0 0 0 0 2h6v6a1 1 0 0 0 2 0v-6h6a1 1 0 0 0 0-2z"
                                                    fill="currentColor"></path>
                                            </svg>
                                            <h6 class="mb-0 font-size-14 fw-normal">Watchlist</h6>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('frontend.pricing-page') }}"
                                            class="iq-sub-card d-flex align-items-center gap-3">
                                            <svg width="16" height="16" stroke-width="1.5" viewBox="0 0 24 24"
                                                fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M8.58737 8.23597L11.1849 3.00376C11.5183 2.33208 12.4817 2.33208 12.8151 3.00376L15.4126 8.23597L21.2215 9.08017C21.9668 9.18848 22.2638 10.0994 21.7243 10.6219L17.5217 14.6918L18.5135 20.4414C18.6409 21.1798 17.8614 21.7428 17.1945 21.3941L12 18.678L6.80547 21.3941C6.1386 21.7428 5.35909 21.1798 5.48645 20.4414L6.47825 14.6918L2.27575 10.6219C1.73617 10.0994 2.03322 9.18848 2.77852 9.08017L8.58737 8.23597Z"
                                                    stroke="currentColor" stroke-linecap="round"
                                                    stroke-linejoin="round"></path>
                                            </svg>
                                            <h6 class="mb-0 font-size-14 fw-normal">Subscription</h6>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('logout') }}"
                                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                                            class="iq-sub-card iq-logout-2 mt-1 d-flex justify-content-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                viewBox="0 0 16 16" fill="none">
                                                <path
                                                    d="M1.82209 15.9999C1.46654 15.9999 1.16283 15.874 0.910981 15.6221C0.659129 15.3703 0.533203 15.0666 0.533203 14.711V1.73322C0.533203 1.37767 0.659129 1.07397 0.910981 0.822114C1.16283 0.570262 1.46654 0.444336 1.82209 0.444336H7.95543V1.44434H1.82209C1.74802 1.44434 1.68135 1.47397 1.62209 1.53322C1.56283 1.59248 1.5332 1.65915 1.5332 1.73322V14.711C1.5332 14.7851 1.56283 14.8517 1.62209 14.911C1.68135 14.9703 1.74802 14.9999 1.82209 14.9999H7.95543V15.9999H1.82209ZM12.0888 11.5999L11.3554 10.8888L13.5332 8.73322H5.68876V7.711H13.511L11.3332 5.55545L12.0665 4.82211L15.4665 8.24434L12.0888 11.5999Z"
                                                    fill="currentColor"></path>
                                            </svg>
                                            <h6 class="mb-0 font-size-14 fw-normal">Logout</h6>
                                        </a>
                                    </li>
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                        style="display: none;"> @csrf </form>
                                </ul>
                            </li>
                        @else
                            <li class="nav-item dropdown" id="itemdropdown1">
                                <a href="{{ route('login') }}" class="nav-link">Login</a>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="header-bottom header-sticky">
        <nav class="nav navbar navbar-expand-xl navbar-light iq-navbar header-hover-menu py-xl-0 py-3">
            <div class="container-fluid navbar-inner">
                <div class="d-flex align-items-center justify-content-between w-100 landing-header">
                    <div class="d-xl-none d-flex gap-3 gap-xl-0 align-items-center">
                        <div>
                            <button type="button" data-bs-toggle="offcanvas" data-bs-target="#navbar_main"
                                aria-controls="navbar_main"
                                class="d-xl-none bg-primary rounded-pill p-1 toggle-rounded-btn d-flex align-items-center">
                                <i class="ph ph-arrow-right fs-5 fw-bold"></i>
                            </button>
                        </div>
                        <div class="d-flex align-items-center gap-2 gap-md-3">
                            <!--Logo -->
                            @include('frontend::components.brand.logo')
                            <div>
                                <a href="pricing-plan"
                                    class="subscribe-btn btn btn-warning-subtle py-1 py-md-2 px-2 px-ms-3">
                                    <span class="d-flex align-items-center gap-2 text-warning">
                                        <i class="ph-fill ph-crown align-middle fs-6"></i>
                                    </span>
                                </a>
                            </div>
                        </div>
                        <!--Logo -->
                    </div>
                    <!-- Horizontal Menu Start -->
                    <nav id="navbar_main"
                        class="offcanvas mobile-offcanvas nav navbar navbar-expand-xl hover-nav horizontal-nav py-xl-0">
                        <div class="container-fluid p-lg-0">
                            <div class="offcanvas-header px-0">
                                <div class="navbar-brand ms-3">
                                    <!--Logo -->
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
                                    </a>
                                    <ul class="sub-nav collapse justify-content-center list-unstyled {{ isset($IS_MEGA) && $IS_MEGA ? 'mega-menu-item' : '' }}"
                                        id="homePages">
                                        <li class="nav-item">
                                            <a class="nav-link {{ isset($IS_MEGA) && $IS_MEGA ? 'p-0' : '' }} {{ activeRoute(route('frontend.ott')) }}"
                                                href="{{ route('frontend.ott') }}">
                                                @if (isset($IS_MEGA))
                                                    <img src="{{ asset('frontend/images/mega-menu/new-home.webp') }}"
                                                        alt="img" class="img-fluid d-xl-block d-none"
                                                        loading="lazy" />
                                                @endif

                                                <span
                                                    class="d-inline-block">{{ __('frontendheader.ott_home') }}</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link {{ isset($IS_MEGA) && $IS_MEGA ? 'p-0' : '' }} {{ activeRoute(route('frontend.index')) }}"
                                                href="{{ route('frontend.index') }}">
                                                @if (isset($IS_MEGA))
                                                    <img src="{{ asset('frontend/images/mega-menu/home.webp') }}"
                                                        alt="img" class="img-fluid d-xl-block d-none"
                                                        loading="lazy" />
                                                @endif
                                                <span
                                                    class="d-inline-block">{{ __('frontendheader.main_home') }}</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link {{ isset($IS_MEGA) && $IS_MEGA ? 'p-0' : '' }} {{ activeRoute(route('frontend.movie')) }}"
                                                href="{{ route('frontend.movie') }}">
                                                @if (isset($IS_MEGA))
                                                    <img src="{{ asset('frontend/images/mega-menu/movie.webp') }}"
                                                        alt="img" class="img-fluid d-xl-block d-none"
                                                        loading="lazy" />
                                                @endif
                                                <span class="d-inline-block">{{ __('frontendheader.movie') }}</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link {{ isset($IS_MEGA) && $IS_MEGA ? 'p-0' : '' }} {{ activeRoute(route('frontend.tv-show')) }}"
                                                href="{{ route('frontend.tv-show') }}">
                                                @if (isset($IS_MEGA))
                                                    <img src="{{ asset('frontend/images/mega-menu/tv-show.webp') }}"
                                                        alt="img" class="img-fluid d-xl-block d-none"
                                                        loading="lazy" />
                                                @endif
                                                <span class="d-inline-block">{{ __('frontendheader.tv_show') }}</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link {{ isset($IS_MEGA) && $IS_MEGA ? 'p-0' : '' }} {{ activeRoute(route('frontend.video')) }}"
                                                href="{{ route('frontend.video') }}">
                                                @if (isset($IS_MEGA))
                                                    <img src="{{ asset('frontend/images/mega-menu/video.webp') }}"
                                                        alt="img" class="img-fluid d-xl-block d-none"
                                                        loading="lazy" />
                                                @endif
                                                <span class="d-inline-block">{{ __('frontendheader.video') }}</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link {{ isset($IS_MEGA) && $IS_MEGA ? 'p-0' : '' }} {{ activeRoute(route('frontend.merchandise')) }}"
                                                href="{{ route('frontend.merchandise') }}">
                                                @if (isset($IS_MEGA))
                                                    <img src="{{ asset('frontend/images/mega-menu/shop-home.webp') }}"
                                                        alt="img" class="img-fluid d-xl-block d-none"
                                                        loading="lazy" />
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
                                                {{ __('frontendheader.download_movie') }} </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link {{ activeRoute(route('frontend.resticted')) }}"
                                                href="{{ route('frontend.resticted') }}">
                                                {{ __('frontendheader.restricted_content') }} </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link {{ activeRoute(route('frontend.releted_merchandies')) }}"
                                                href="{{ route('frontend.releted_merchandies') }}">
                                                {{ __('frontendheader.related_merchandise') }}
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link {{ activeRoute(route('frontend.genres')) }}"
                                                href="{{ route('frontend.genres') }}">
                                                {{ __('frontendheader.geners') }} </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link {{ activeRoute(route('frontend.tag')) }}"
                                                href="{{ route('frontend.tag') }}"> {{ __('frontendheader.tags') }}
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link {{ activeRoute(route('frontend.cast_list')) }}"
                                                href="{{ route('frontend.cast_list') }}">
                                                {{ __('frontendheader.cast') }} </a>
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
                                                {{ __('frontendheader.about_us') }} </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link {{ activeRoute(route('frontend.contact_us')) }}"
                                                href="{{ route('frontend.contact_us') }}">
                                                {{ __('frontendheader.contact_us') }}
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link {{ activeRoute(route('frontend.faq_page')) }}"
                                                href="{{ route('frontend.faq_page') }}">
                                                {{ __('frontendheader.faq') }} </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link {{ activeRoute(route('frontend.pricing-page')) }}"
                                                href="{{ route('frontend.pricing-page') }}">
                                                {{ __('frontendheader.pricing_plan') }}
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link {{ activeRoute(route('frontend.privacy-policy')) }}"
                                                href="{{ route('frontend.privacy-policy') }}">
                                                {{ __('frontendheader.privacy_policy') }} </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link {{ activeRoute(route('frontend.terms-and-policy')) }}"
                                                href="{{ route('frontend.terms-and-policy') }}">
                                                {{ __('pages_sections.terms_and_use') }}
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link {{ activeRoute(route('frontend.comming-soon')) }}"
                                                href="{{ route('frontend.comming-soon') }}">
                                                {{ __('frontendheader.coming_soon') }}
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link {{ activeRoute(route('frontend.error_page1')) }}"
                                                href="{{ route('frontend.error_page1') }}">
                                                {{ __('sidebar.error_404') }}
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
                                        <li class="nav-item">
                                            <a class="nav-link {{ activeRoute(route('frontend.list_page')) }}"
                                                href="{{ route('frontend.list_page') }}">
                                                {{ __('frontendheader.listing') }}
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-bs-toggle="collapse" href="#blog-grid"
                                                role="button" aria-expanded="false" aria-controls="blog-grid">
                                                <div class="d-flex justify-content-between">
                                                    <span
                                                        class="item-name">{{ __('frontendheader.blog_grid') }}</span>
                                                    <span class="menu-icon">
                                                        <i class="ph ph-caret-down align-middle down-to-right"></i>
                                                    </span>
                                                </div>
                                            </a>
                                            <ul class="sub-nav collapse list-unstyled" id="blog-grid">
                                                <li class="nav-item">
                                                    <a class="nav-link {{ activeRoute(route('frontend.one_column_grid')) }}"
                                                        href="{{ route('frontend.one_column_grid') }}">
                                                        {{ __('frontendheader.one_column_blog') }} </a>
                                                </li>
                                                <li class="nav-item">
                                                    <a class="nav-link {{ activeRoute(route('frontend.two_column_grid')) }}"
                                                        href="{{ route('frontend.two_column_grid') }}">
                                                        {{ __('frontendheader.two_column_blog') }} </a>
                                                </li>
                                                <li class="nav-item">
                                                    <a class="nav-link {{ activeRoute(route('frontend.three_column_grid')) }}"
                                                        href="{{ route('frontend.three_column_grid') }}">
                                                        {{ __('frontendheader.three_column_blog') }} </a>
                                                </li>
                                                <li class="nav-item">
                                                    <a class="nav-link {{ activeRoute(route('frontend.four_column_grid')) }}"
                                                        href="{{ route('frontend.four_column_grid') }}">
                                                        {{ __('frontendheader.four_column_blog') }} </a>
                                                </li>
                                            </ul>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-bs-toggle="collapse"
                                                href="{{ route('frontend.sidebar_list_page') }}" role="button"
                                                aria-expanded="false" aria-controls="blog-sidebar">
                                                <div class="d-flex justify-content-between">
                                                    <span
                                                        class="item-name">{{ __('frontendheader.blog_sidebar') }}</span>
                                                    <span class="menu-icon">
                                                        <i class="ph ph-caret-down align-middle down-to-right"></i>
                                                    </span>
                                                </div>
                                            </a>
                                            <ul class="sub-nav collapse list-unstyled" id="blog-sidebar">
                                                <li class="nav-item">
                                                    <a class="nav-link {{ activeRoute(route('frontend.left_sidebar')) }}"
                                                        href="{{ route('frontend.left_sidebar') }}">
                                                        {{ __('frontendheader.left_sidebar') }} </a>
                                                </li>
                                                <li class="nav-item">
                                                    <a class="nav-link {{ activeRoute(route('frontend.right_sidebar')) }}"
                                                        href="{{ route('frontend.right_sidebar') }}">
                                                        {{ __('frontendheader.right_sidebar') }}
                                                    </a>
                                                </li>
                                            </ul>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-bs-toggle="collapse" href="#blog-single"
                                                role="button" aria-expanded="false" aria-controls="blog-single">
                                                <div class="d-flex justify-content-between">
                                                    <span
                                                        class="item-name">{{ __('frontendheader.blog_single') }}</span>
                                                    <span class="menu-icon">
                                                        <i class="ph ph-caret-down align-middle down-to-right"></i>
                                                    </span>
                                                </div>
                                            </a>
                                            <ul class="sub-nav collapse list-unstyled" id="blog-single">
                                                <li class="nav-item">
                                                    <a class="nav-link {{ activeRoute(route('frontend.detail_page')) }}"
                                                        href="{{ route('frontend.detail_page') }}">
                                                        {{ __('frontendheader.standard') }} </a>
                                                </li>
                                                <li class="nav-item">
                                                    <a class="nav-link {{ activeRoute(route('frontend.blog_audio')) }}"
                                                        href="{{ route('frontend.blog_audio') }}">
                                                        {{ __('frontendheader.audio') }}
                                                    </a>
                                                </li>
                                                <li class="nav-item">
                                                    <a class="nav-link {{ activeRoute(route('frontend.blog_video')) }}"
                                                        href="{{ route('frontend.blog_video') }}">
                                                        {{ __('frontendheader.video') }}
                                                    </a>
                                                </li>
                                                <li class="nav-item">
                                                    <a class="nav-link {{ activeRoute(route('frontend.blog_link')) }}"
                                                        href="{{ route('frontend.blog_link') }}">
                                                        {{ __('frontendheader.link') }}
                                                    </a>
                                                </li>
                                                <li class="nav-item">
                                                    <a class="nav-link {{ activeRoute(route('frontend.blog_quote')) }}"
                                                        href="{{ route('frontend.blog_quote') }}">
                                                        {{ __('frontendheader.quote') }}
                                                    </a>
                                                </li>
                                                <li class="nav-item">
                                                    <a class="nav-link {{ activeRoute(route('frontend.blog_gallery')) }}"
                                                        href="{{ route('frontend.blog_gallery') }}">
                                                        {{ __('frontendheader.gallery') }} </a>
                                                </li>
                                            </ul>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-bs-toggle="collapse" href="#style-pagination"
                                                role="button" aria-expanded="false"
                                                aria-controls="style-pagination">
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
                                                        {{ __('frontendblogs.blog_pagination') }} </a>
                                                </li>
                                                <li class="nav-item">
                                                    <a class="nav-link {{ activeRoute(route('frontend.blog_loadmore')) }}"
                                                        href="{{ route('frontend.blog_loadmore') }}">
                                                        {{ __('frontendblogs.blog_load_more') }}
                                                    </a>
                                                </li>
                                                <li class="nav-item">
                                                    <a class="nav-link {{ activeRoute(route('frontend.blog_infinite_scroll')) }}"
                                                        href="{{ route('frontend.blog_infinite_scroll') }}">
                                                        {{ __('frontendblogs.blog_infinite_scroll') }}
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
                                            <a class="nav-link {{ activeRoute(route('frontend.shop')) }}"
                                                href="{{ route('frontend.shop') }}">
                                                {{ __('frontendheader.shop') }} </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="{{ route('frontend.my_account') }}">
                                                {{ __('frontendheader.my_account_page') }} </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="{{ route('frontend.cart') }}">
                                                {{ __('frontendheader.cart_page') }} </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="{{ route('frontend.wishlist') }}">
                                                {{ __('frontendheader.wishlist_page') }}
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="{{ route('frontend.chekout') }}">
                                                {{ __('frontendheader.checkout_page') }}
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="{{ route('frontend.order_tracking') }}">
                                                {{ __('frontendheader.order_tracking') }}</a>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                        </div>
                        <!-- container-fluid.// -->
                    </nav>
                    <!-- Horizontal Menu End -->
                    <div class="right-panel">
                        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                            data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                            aria-expanded="false" aria-label="Toggle navigation">
                            <span class="navbar-toggler-btn">
                                <span class="navbar-toggler-icon"></span>
                            </span>
                        </button>
                        <div class="collapse navbar-collapse p-3" id="navbarSupportedContent">
                            <div class="iq-meta-menu-container d-xl-block d-none">
                                <ul class="d-flex align-items-center m-0 list-inline iq-meta-menu">
                                    <li>
                                        <a
                                            href="{{ route('frontend.my_account') }}">{{ __('frontendheader.my_account') }}</a>
                                    </li>
                                    <li>
                                        <a
                                            href="https://iqonic.desky.support/">{{ __('frontendheader.customer_support') }}</a>
                                    </li>
                                    <li>
                                        <a
                                            href="{{ route('frontend.contact_us') }}">{{ __('frontendheader.help') }}</a>
                                    </li>
                                </ul>
                            </div>
                            <div class="d-xl-none">
                                <ul class="d-flex align-items-center justify-content-end gap-3 list-inline mb-0">
                                    <li>
                                        <div class="search-box-2">
                                            <form>
                                                <input type="text" class="form-control" placeholder="Search...">
                                                <button type="submit" class="search-btn d-flex align-items-center">
                                                    <i class="ph ph-magnifying-glass"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </li>
                                    <li>
                                        <a href="#offcanvasCart" class="text-white" data-bs-toggle="offcanvas">
                                            <span class="btn-icon rounded-pill user-icons">
                                                <i class="ph ph-handbag fs-5"></i>
                                            </span>
                                        </a>
                                    </li>
                                    @if (auth()->user())
                                        <li class="dropdown" id="itemdropdown1">
                                            <a class="text-white d-flex align-items-center"href="javascript:void(0)"
                                                id="navbarDropdown" role="button" data-bs-toggle="dropdown"
                                                aria-expanded="false">
                                                <div class="btn-icon rounded-pill user-icons">
                                                    <span class="btn-inner d-flex">
                                                        <i class="ph ph-user-plus fs-5"></i>
                                                    </span>
                                                </div>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end dropdown-user border-0 p-0 m-0"
                                                aria-labelledby="navbarDropdown">
                                                <li class="user-info mb-3">
                                                    <a href="{{ route('frontend.profile-marvin') }}"
                                                        class="d-flex align-items-center gap-3">
                                                    <img src="{{ asset('/frontend/images/user/user1.webp') }}"
                                                        class="img-fluid" alt="img" loading="lazy" />
                                                    <span
                                                        class="font-size-14 fw-500 text-capitalize text-white">Jenny</span></a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('frontend.my_account') }}"
                                                        class="iq-sub-card d-flex align-items-center gap-3">
                                                        <i class="ph ph-user fw-bold"></i>
                                                        <h6 class="mb-0 font-size-14 fw-normal">My Account</h6>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('frontend.watchlist_detail') }}"
                                                        class="iq-sub-card d-flex align-items-center gap-3">
                                                        <i class="ph ph-plus fw-bold"></i>
                                                        <h6 class="mb-0 font-size-14 fw-normal">Watchlist</h6>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('frontend.pricing-page') }}"
                                                        class="iq-sub-card d-flex align-items-center gap-3">
                                                        <i class="ph ph-star fw-bold"></i>
                                                        <h6 class="mb-0 font-size-14 fw-normal">Subscription</h6>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('logout') }}"
                                                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                                                        class="iq-sub-card iq-logout-2 mt-1 d-flex justify-content-center gap-2">
                                                        <i class="ph ph-sign-out"></i>
                                                        <h6 class="mb-0 font-size-14 fw-normal">Logout</h6>
                                                    </a>
                                                </li>
                                                <form id="logout-form" action="{{ route('logout') }}"
                                                    method="POST" style="display: none;"> @csrf </form>
                                            </ul>
                                        </li>
                                    @else
                                        <li class="nav-item dropdown" id="itemdropdown1">
                                            <a href="{{ route('login') }}" class="nav-link">Login</a>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </div>
</header>

<div class="offcanvas overflow-y-auto widget-shopping-cart-content offcanvas-end offcanvas-sidebar sidebar-container on-rtl end border-0"
    tabindex="-1" id="offcanvasCart">
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
                        <a href="{{ route('frontend.cart') }}" aria-label="Believe Mask">
                            <img src="{{ asset('frontend/images/shop/product/02.webp') }}"
                                class="img-fluid object-cover" width="90" alt="product-image">
                        </a>
                    </div>
                    <div class="cart-content flex-grow-1 position-relative pe-5">
                        <div class="d-flex justify-content-between align-items-center">
                            <a class="d-block" href="product-detail" aria-label="Believe Mask">
                                <h6 class="mb-36 text-capitalize fw-500">{{ __('streamShop.believe_mask') }}</h6>
                            </a>
                            <a href="javascript:void(0)" class="delete-btn text-white text-capitalize">
                                <i class="ph ph-trash text-primary"></i>
                            </a>
                        </div>
                        <div class="product-price text-primary small">
                            $13.00
                        </div>
                        <div class="btn-group iq-qty-btn custom-qty-btn rounded-3 mt-3" data-qty="btn"
                            role="group">
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

                <li class="mini-cart-item d-flex align-items-start gap-3">
                    <div class="cart-img">
                        <a href="{{ route('frontend.cart') }}" aria-label="Black Cap">
                            <img src="{{ asset('frontend/images/shop/product/04.webp') }}"
                                class="img-fluid object-cover" width="90" alt="product-image">
                        </a>
                    </div>
                    <div class="cart-content flex-grow-1 position-relative pe-5">
                        <div class="d-flex justify-content-between align-items-center">
                            <a class="d-block" href="product-detail" aria-label="Black Cap">
                                <h6 class="mb-36 text-capitalize fw-500">{{ __('streamShop.product4') }}</h6>
                            </a>
                            <a href="javascript:void(0)" class="delete-btn text-white text-capitalize">
                                <i class="ph ph-trash text-primary"></i>
                            </a>
                        </div>
                        <div class="product-price text-primary small">
                            $18.00
                        </div>
                        <div class="btn-group iq-qty-btn custom-qty-btn rounded-3 mt-3" data-qty="btn"
                            role="group">
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

                <li class="mini-cart-item d-flex align-items-start gap-3">
                    <div class="cart-img">
                        <a href="{{ route('frontend.cart') }}" aria-label="Boxing Gloves">
                            <img src="{{ asset('frontend/images/shop/product/05.webp') }}"
                                class="img-fluid object-cover" width="90" alt="product-image">
                        </a>
                    </div>
                    <div class="cart-content flex-grow-1 position-relative pe-5">
                        <div class="d-flex justify-content-between align-items-center">
                            <a class="d-block" href="product-detail" aria-label="Boxing Gloves">
                                <h6 class="mb-36 text-capitalize fw-500">{{ __('streamShop.product5') }}</h6>
                            </a>
                            <a href="javascript:void(0)" class="delete-btn text-white text-capitalize">
                                <i class="ph ph-trash text-primary"></i>
                            </a>
                        </div>
                        <div class="product-price text-primary small">
                            $18.00
                        </div>
                        <div class="btn-group iq-qty-btn custom-qty-btn rounded-3 mt-3" data-qty="btn"
                            role="group">
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
                        class="woocommerce-Price-currencySymbol">₹</span>49.00</span></span>
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
