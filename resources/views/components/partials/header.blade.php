<nav class="nav navbar navbar-expand-xl header-hover-menu navbar-light iq-navbar">
    <div class="container-fluid navbar-inner">
        <a href="{{ route('dashboard') }}" class="navbar-brand">
            @include('components.widget.logo')
        </a>
        <div class="sidebar-toggle" data-toggle="sidebar" data-active="true">
            <i class="icon d-flex">
                <svg class="icon-20" width="20" viewBox="0 0 24 24">
                    <path fill="currentColor"
                        d="M4,11V13H16L10.5,18.5L11.92,19.92L19.84,12L11.92,4.08L10.5,5.5L16,11H4Z" />
                </svg>
            </i>
        </div>
        <div class="d-flex align-items-center justify-content-between product-offcanvas">
            @if (isset($title) && $title)
                <div class="breadcrumb-title pe-3 d-none d-xl-block">
                    <small class="mb-0 text-capitalize">{{ $title }}</small>
                </div>
            @endif
        </div>
        <div class="d-flex align-items-center">
            <button id="navbar-toggle" class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon">
                    <span class="navbar-toggler-bar bar1 mt-1"></span>
                    <span class="navbar-toggler-bar bar2"></span>
                    <span class="navbar-toggler-bar bar3"></span>
                </span>
            </button>
        </div>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="mb-2 navbar-nav ms-auto align-items-center navbar-list mb-lg-0 ">
                <li class="nav-item dropdown">
                    <a href="#" class="nav-link" id="search-drop" data-bs-toggle="dropdown">
                        <i class="ph ph-magnifying-glass fs-4 align-middle"></i>
                    </a>
                    <ul class="p-0 sub-drop dropdown-menu dropdown-menu-end" aria-labelledby="search-drop">
                        <li class="p-0">
                            <div class="p-3 card-header justify-content-between border-bottom rounded-top">
                                <div class="header-title">
                                    <div class="iq-search-bar device-search  position-relative">
                                        <form action="#" class="searchbox">
                                            <input type="text"
                                                class="text search-input form-control bg-primary-subtle w-100"
                                                placeholder="{{ __('header.search_here') }}">
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="p-0 card-body all-notification">
                                <div class="d-flex align-items-center border-bottom search-hover py-2 px-3">
                                    <div class="flex-shrink-0">
                                        <img src="{{ asset('dashboard/images/avatars/01.png') }}" loading="lazy"
                                            class="align-self-center img-fluid avatar-50 rounded-pill" alt="#">
                                    </div>
                                    <div class="d-flex flex-column ms-3 w-100">
                                        <a href="javascript:void(0);" class="h5">{{ __('header.paige_turner') }}</a>
                                        <span>{{ __('header.paige') }}</span>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center border-bottom search-hover py-2 px-3">
                                    <div class="flex-shrink-0">
                                        <img src="{{ asset('dashboard/images/avatars/02.png') }}" loading="lazy"
                                            class="align-self-center img-fluid avatar-50 rounded-pill" alt="#">
                                    </div>
                                    <div class="d-flex flex-column ms-3 w-100">
                                        <a href="javascript:void(0);" class="h5">{{ __('header.monty_carlo') }}</a>
                                        <span>{{ __('header.carlo_m') }}</span>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center search-hover py-2 px-3 border-bottom">
                                    <div class="flex-shrink-0">
                                        <img src="{{ asset('dashboard/images/avatars/03.png') }}" loading="lazy"
                                            class="align-self-center img-fluid avatar-50 rounded-pill" alt="#">
                                    </div>
                                    <div class="d-flex flex-column ms-3 w-100">
                                        <a href="javascript:void(0);" class="h5">{{ __('header.paul_molive') }}</a>
                                        <span>{{ __('header.paul') }}</span>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center border-bottom search-hover py-2 px-3">
                                    <div class="flex-shrink-0">
                                        <img src="{{ asset('dashboard/images/avatars/04.png') }}" loading="lazy"
                                            class="align-self-center img-fluid avatar-50 rounded-pill" alt="#">
                                    </div>
                                    <div class="d-flex flex-column ms-3 w-100">
                                        <a href="javascript:void(0);" class="h5">{{ __('header.monty_carlo') }}</a>
                                        <span>{{ __('header.carlo_m') }}</span>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a href="#" class="nav-link" id="langues-drop" data-bs-toggle="dropdown">
                        <i class="ph ph-translate fs-4 align-middle"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-content">
                        <li data-setting="attribute">
                            <div class="dropdown-item d-flex align-items-center">
                                <input type="radio" value="ltr" class="btn-check" name="theme_scheme_direction"
                                    data-prop="dir" id="theme-scheme-direction-ltr" checked>
                                <label class="d-block" for="theme-scheme-direction-ltr">
                                    <span class="ms-2 mb-0"> LTR </span>
                                </label>

                            </div>
                        </li>
                        <li data-setting="attribute">
                            <div class="dropdown-item d-flex align-items-center">
                                <input type="radio" value="rtl" class="btn-check" name="theme_scheme_direction"
                                    data-prop="dir" id="theme-scheme-direction-rtl">
                                <label class=" d-block" for="theme-scheme-direction-rtl">
                                    <span class="ms-2 mb-0"> RTL </span>
                                </label>
                            </div>
                        </li>
                    </ul>
                </li>

                <li class="nav-item dropdown">
                    <select class="form-select py-1" onchange="window.location.href=this.value;">
                        @foreach (config('app.available_locales') as $locale => $title)
                            <option value="{{ route('language.switch', $locale) }}"
                                {{ App::getLocale() === $locale ? 'selected' : '' }}>
                                {{ $title }}
                            </option>
                        @endforeach
                    </select>
                </li>

                <li class="nav-item dropdown">
                    <a href="#" class="nav-link" id="notification-drop" data-bs-toggle="dropdown">
                        <i class="ph-fill ph-bell fs-4 align-middle"></i>
                        <span class="bg-danger dots"></span>
                    </a>
                    <ul class="p-0 sub-drop dropdown-menu dropdown-menu-end" aria-labelledby="notification-drop">
                        <li class="p-0">
                            <div class="p-3 card-header d-flex justify-content-between bg-primary rounded-top">
                                <div class="header-title">
                                    <h5 class="mb-0 text-white">{{ __('header.all_notifications') }}</h5>
                                </div>
                            </div>
                            <div class="p-0 card-body all-notification">
                                <a href="#" class="iq-sub-card">
                                    <div class="d-flex align-items-center">
                                        <img class="p-1 avatar-40 rounded-pill bg-primary-subtle"
                                            src="{{ asset('dashboard/images/author/01.png') }}" alt=""
                                            loading="lazy">
                                        <div class="ms-3 w-100">
                                            <h6 class="mb-0 ">{{ __('header.emma_watson_bni') }}</h6>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <p class="mb-0">95 MB</p>
                                                <small
                                                    class="float-end font-size-12">{{ __('header.just_now') }}</small>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                                <a href="#" class="iq-sub-card">
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <img class="p-1 avatar-40 rounded-pill bg-primary-subtle"
                                                src="{{ asset('dashboard/images/author/02.png') }}" alt=""
                                                loading="lazy">
                                        </div>
                                        <div class="ms-3 w-100">
                                            <h6 class="mb-0 ">{{ __('header.new_customer_is_join') }}</h6>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <p class="mb-0">{{ __('header.cyst_bni') }}</p>
                                                <small class="float-end font-size-12">5
                                                    {{ __('header.days_ago') }}</small>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                                <a href="#" class="iq-sub-card">
                                    <div class="d-flex align-items-center">
                                        <img class="p-1 avatar-40 rounded-pill bg-primary-subtle"
                                            src="{{ asset('dashboard/images/author/03.png') }}" alt=""
                                            loading="lazy">
                                        <div class="ms-3 w-100">
                                            <h6 class="mb-0 ">{{ __('header.two_customer_is_left') }}</h6>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <p class="mb-0">{{ __('header.cyst_bni') }}</p>
                                                <small class="float-end font-size-12">2
                                                    {{ __('header.days_ago') }}</small>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                                <a href="#" class="iq-sub-card">
                                    <div class="d-flex align-items-center">
                                        <img class="p-1 avatar-40 rounded-pill bg-primary-subtle"
                                            src="{{ asset('dashboard/images/author/04.png') }}" alt=""
                                            loading="lazy">
                                        <div class="w-100 ms-3">
                                            <h6 class="mb-0 ">{{ __('header.new_mail_from_fenny') }}</h6>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <p class="mb-0">{{ __('header.cyst_bni') }}</p>
                                                <small class="float-end font-size-12">3
                                                    {{ __('header.days_ago') }}</small>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </li>
                    </ul>
                </li>
                <li class="nav-item theme-scheme-dropdown dropdown">
                    <a href="#" class="nav-link" id="mode-drop" data-bs-toggle="dropdown">
                        <i class="ph-duotone ph-sun fs-4 align-middle"></i>
                    </a>
                    <ul class="list-unstyled dropdown-menu dropdown-content">
                        <li data-setting="radio">
                            <div class="dropdown-item d-flex align-items-center">
                                <input type="radio" value="light" class="btn-check" name="theme_scheme"
                                    id="color-mode-light">
                                <label class="d-block" for="color-mode-light">
                                    <i class="ph-duotone ph-sun fs-4 align-middle"></i>
                                    <span class="ms-3 mb-0">{{ __('header.light') }}</span>
                                </label>
                            </div>
                        </li>
                        <li data-setting="radio">
                            <div class="dropdown-item d-flex align-items-center">
                                <input type="radio" value="dark" class="btn-check" name="theme_scheme"
                                    id="color-mode-dark">
                                <label class="d-block" for="color-mode-dark">
                                    <i class="ph-duotone ph-moon fs-4 align-middle"></i>
                                    <span class="ms-3 mb-0">{{ __('header.dark') }}</span>
                                </label>
                            </div>
                        </li>
                        <li data-setting="radio">
                            <div class="dropdown-item d-flex align-items-center">
                                <input type="radio" value="auto" class="btn-check" name="theme_scheme"
                                    id="color-mode-auto" checked>
                                <label class="d-block" for="color-mode-auto">
                                    <i class="ph-duotone ph-circle-half fs-4 align-middle"></i>
                                    <span class="ms-3 mb-0">{{ __('header.auto') }}</span>
                                </label>
                            </div>
                        </li>
                    </ul>
                </li>
                <li class="nav-item iq-full-screen d-none  d-xl-block border-end" id="fullscreen-item">
                    <a href="#" class="nav-link pe-3" id="btnFullscreen" data-bs-toggle="dropdown">
                        <span class="btn-inner">
                            <i class="normal-screen ph ph-arrows-out-simple fs-4 align-middle"></i>
                            <i class="full-normal-screen ph ph-arrows-in-simple  d-none align-middle fs-4"></i>
                        </span>
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="py-0 nav-link d-flex align-items-center ps-3" href="#" id="profile-setting"
                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="{{ asset('dashboard/images/user/01.jpg') }}" alt="User-Profile"
                            class="theme-color-default-img img-fluid avatar avatar-50 avatar-rounded" loading="lazy">
                        <img src="{{ asset('dashboard/images/user/01.jpg') }}" alt="User-Profile"
                            class="theme-color-purple-img img-fluid avatar avatar-50 avatar-rounded" loading="lazy">
                        <img src="{{ asset('dashboard/images/user/02.jpg') }}" alt="User-Profile"
                            class="theme-color-blue-img img-fluid avatar avatar-50 avatar-rounded" loading="lazy">
                        <img src="{{ asset('dashboard/images/user/03.jpg') }}" alt="User-Profile"
                            class="theme-color-green-img img-fluid avatar avatar-50 avatar-rounded" loading="lazy">
                        <img src="{{ asset('dashboard/images/user/04.jpg') }}" alt="User-Profile"
                            class="theme-color-yellow-img img-fluid avatar avatar-50 avatar-rounded" loading="lazy">
                        <img src="{{ asset('dashboard/images/user/05.jpg') }}" alt="User-Profile"
                            class="theme-color-pink-img img-fluid avatar avatar-50 avatar-rounded" loading="lazy">
                        <div class="caption ms-3 d-none d-md-block ">
                            <h6 class="mb-0 caption-title">{{ __('header.austin_robertson') }}</h6>
                            <p class="mb-0 caption-sub-title">{{ __('header.marketing_administrator') }}</p>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profile-setting">
                        <li><a class="dropdown-item"
                                href="{{ route('dashboard.profile') }}">{{ __('header.profile') }}</a></li>
                        <li><a class="dropdown-item"
                                href="{{ route('dashboard.privacy') }}">{{ __('header.privacy_setting') }}</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('logout') }}"
                                onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                                class="iq-sub-card iq-logout-2 mt-1 d-flex justify-content-center gap-2">{{ __('header.logout') }}
                            </a>
                        </li>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST"
                            style="display: none;"> @csrf </form>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
