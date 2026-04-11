@extends('frontend::layouts.master', [
    'isSwiperSlider' => true,
    'isVideoJs' => true,
    'bodyClass' => "
custom-header-relative ",
])

@section('content')
    <div class="section-padding personality-detail">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-3">
                    <div class="cast-box position-relative">
                        <img src="{{ asset('frontend/images/cast/charles-melton.webp') }}"
                            class="img-fluid object-cover w-100 rounded-3" alt="person" loading="lazy">
                    </div>
                    <h5 class="mt-5 pt-4 mb-4 text-white fw-500">{{ __('favouritePersonalities.personal_details') }}</h5>
                    <ul class="list-inline p-0 m-0">
                        <li class="mb-3">
                            <h5 class="mt-0 mb-2">{{ __('favouritePersonalities.born') }}</h5>
                            <ul
                                class="person-birth-detail d-flex align-items-center flex-wrap column-gap-5 row-gap-1 p-0 m-0">
                                <li class="list-group-item">{{ __('favouritePersonalities.birthday') }}: 1985-03-22 </li>
                            </ul>
                        </li>

                    </ul>
                </div>
                <div class="col-md-9 mt-5 mt-md-0">
                    <h4 class="mb-3">{{ __('favouritePersonalities.charles_melton') }}</h4>
                    <ul class="person-category d-flex flex-wrap align-items-center gap-5 ps-0">
                        <li class="list-group-item"><a href="">{{ __('favouritePersonalities.actor') }}</a></li>
                    </ul>

                    <p>{{ __('favouritePersonalities.charles_melton_description') }}
                    </p>
                    <div class="actor-history">
                        <div class="">
                            <h4 class="">{{ __('favouritePersonalities.person_history') }}</h4>
                        </div>
                    </div>
                    {{-- tab start --}}
                    <div class="content-details trending-info personal-detail">
                        <ul class="nav nav-underline d-flex nav nav-pills align-items-center text-center my-5"
                            role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active show fw-bold" data-bs-toggle="pill" href="#all" role="tab"
                                    aria-selected="true">
                                    {{ __('favouritePersonalities.all') }}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link fw-bold" data-bs-toggle="pill" href="#movies" role="tab"
                                    aria-selected="false">
                                    {{ __('frontendheader.movie') }}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link fw-bold" data-bs-toggle="pill" href="#tvshows" role="tab"
                                    aria-selected="false">
                                    {{ __('frontendheader.tv_show') }}
                                </a>
                            </li>
                        </ul>
                        <div class="tab-content personal-detail-tab-content">
                            <div id="all" class="tab-pane animated fadeInUp active show" role="tabpanel">
                                <div class="description-content">
                                    <div class="row gy-5">
                                        <div class="col-xl-4 col-sm-6">
                                            @include('frontend::components.widgets.person-detail-card', [
                                                'productImg' => 'the-first-of-us-portrait.webp',
                                                'productTitle' => __('streamMovies.the_first_of_us'),
                                                'productLink' => ' view-all',
                                                'productYear' => '2025',
                                            ])
                                        </div>
                                        <div class="col-xl-4 col-sm-6">
                                            @include('frontend::components.widgets.person-detail-card', [
                                                'productImg' => 'gameofhero-portrait.webp',
                                                'productTitle' => __('streamMovies.game_of_heros'),
                                                'productLink' => ' view-all',
                                                'productYear' => '2014',
                                            ])
                                        </div>
                                        <div class="col-xl-4 col-sm-6">
                                            @include('frontend::components.widgets.person-detail-card', [
                                                'productImg' => 'venom-portrait.webp',
                                                'productTitle' => __('streamMovies.venom'),
                                                'productLink' => ' view-all',
                                                'productYear' => '2022',
                                            ])
                                        </div>
                                        <div class="col-xl-4 col-sm-6">
                                            @include('frontend::components.widgets.person-detail-card', [
                                                'productImg' => 'rabbit-portrait.webp',
                                                'productTitle' => __('streamMovies.rabbit'),
                                                'productLink' => ' view-all',
                                                'productYear' => '2020',
                                            ])
                                        </div>
                                        <div class="col-xl-4 col-sm-6">
                                            @include('frontend::components.widgets.person-detail-card', [
                                                'productImg' => 'migration-portrait.webp',
                                                'productTitle' => __('streamMovies.migration'),
                                                'productLink' => ' view-all',
                                                'productYear' => '2011',
                                            ])
                                        </div>
                                        <div class="col-xl-4 col-sm-6">
                                            @include('frontend::components.widgets.person-detail-card', [
                                                'productImg' => 'krishna-portrait.webp',
                                                'productTitle' => __('streamMovies.krishna'),
                                                'productLink' => ' view-all',
                                                'productYear' => '2024',
                                            ])
                                        </div>
                                        <div class="col-xl-4 col-sm-6">
                                            @include('frontend::components.widgets.person-detail-card', [
                                                'productImg' => 'kali-portrait.webp',
                                                'productTitle' => __('streamMovies.kali'),
                                                'productLink' => ' view-all',
                                                'productYear' => '2017',
                                            ])
                                        </div>
                                        <div class="col-xl-4 col-sm-6">
                                            @include('frontend::components.widgets.person-detail-card', [
                                                'productImg' => 'joker-portrait.webp',
                                                'productTitle' => __('streamMovies.joker'),
                                                'productLink' => ' view-all',
                                                'productYear' => '2022',
                                            ])
                                        </div>
                                        <div class="col-xl-4 col-sm-6">
                                            @include('frontend::components.widgets.person-detail-card', [
                                                'productImg' => 'deadpool-portrait.webp',
                                                'productTitle' => __('streamMovies.deadpool'),
                                                'productLink' => ' view-all',
                                                'productYear' => '2025',
                                            ])
                                        </div>
                                        <div class="col-xl-4 col-sm-6">
                                            @include('frontend::components.widgets.person-detail-card', [
                                                'productImg' => 'any-name-portrait.webp',
                                                'productTitle' => __('streamMovies.any_name'),
                                                'productLink' => ' view-all',
                                                'productYear' => '2015',
                                            ])
                                        </div>
                                        <div class="col-xl-4 col-sm-6">
                                            @include('frontend::components.widgets.person-detail-card', [
                                                'productImg' => 'batter-caill-portrait.webp',
                                                'productTitle' => __('streamMovies.better_call_saul'),
                                                'productLink' => ' view-all',
                                                'productYear' => '2010',
                                            ])
                                        </div>
                                        <div class="col-xl-4 col-sm-6">
                                            @include('frontend::components.widgets.person-detail-card', [
                                                'productImg' => 'jumanji-portrait.webp',
                                                'productTitle' => __('streamMovies.jumanji'),
                                                'productLink' => ' view-all',
                                                'productYear' => '2018',
                                            ])
                                        </div>
                                        <div class="col-xl-4 col-sm-6">
                                            @include('frontend::components.widgets.person-detail-card', [
                                                'productImg' => 'yoshi-portrait.webp',
                                                'productTitle' => __('streamMovies.yoshi'),
                                                'productLink' => ' view-all',
                                                'productYear' => '2020',
                                            ])
                                        </div>                                        
                                    </div>
                                </div>
                            </div>
                            <div id="movies" class="tab-pane animated fadeInUp" role="tabpanel">
                                <div class="description-content">   
                                    <div class="row gy-5">
                                        <div class="col-xl-4 col-sm-6">
                                            @include('frontend::components.widgets.person-detail-card', [
                                                'productImg' => 'gameofhero-portrait.webp',
                                                'productTitle' => __('streamMovies.game_of_heros'),
                                                'productLink' => ' view-all',
                                                'productYear' => '2014',
                                            ])
                                        </div>
                                        <div class="col-xl-4 col-sm-6">
                                            @include('frontend::components.widgets.person-detail-card', [
                                                'productImg' => 'venom-portrait.webp',
                                                'productTitle' => __('streamMovies.venom'),
                                                'productLink' => ' view-all',
                                                'productYear' => '2022',
                                            ])
                                        </div>
                                        <div class="col-xl-4 col-sm-6">
                                            @include('frontend::components.widgets.person-detail-card', [
                                                'productImg' => 'rabbit-portrait.webp',
                                                'productTitle' => __('streamMovies.rabbit'),
                                                'productLink' => ' view-all',
                                                'productYear' => '2020',
                                            ])
                                        </div>
                                        <div class="col-xl-4 col-sm-6">
                                            @include('frontend::components.widgets.person-detail-card', [
                                                'productImg' => 'migration-portrait.webp',
                                                'productTitle' => __('streamMovies.migration'),
                                                'productLink' => ' view-all',
                                                'productYear' => '2011',
                                            ])
                                        </div>
                                        <div class="col-xl-4 col-sm-6">
                                            @include('frontend::components.widgets.person-detail-card', [
                                                'productImg' => 'krishna-portrait.webp',
                                                'productTitle' => __('streamMovies.krishna'),
                                                'productLink' => ' view-all',
                                                'productYear' => '2015',
                                            ])
                                        </div>
                                        <div class="col-xl-4 col-sm-6">
                                            @include('frontend::components.widgets.person-detail-card', [
                                                'productImg' => 'kali-portrait.webp',
                                                'productTitle' => __('streamMovies.kali'),
                                                'productLink' => ' view-all',
                                                'productYear' => '2016',
                                            ])
                                        </div>
                                        <div class="col-xl-4 col-sm-6">
                                            @include('frontend::components.widgets.person-detail-card', [
                                                'productImg' => 'joker-portrait.webp',
                                                'productTitle' => __('streamMovies.joker'),
                                                'productLink' => ' view-all',
                                                'productYear' => '2021',
                                            ])
                                        </div>
                                        <div class="col-xl-4 col-sm-6">
                                            @include('frontend::components.widgets.person-detail-card', [
                                                'productImg' => 'deadpool-portrait.webp',
                                                'productTitle' => __('streamMovies.deadpool'),
                                                'productLink' => ' view-all',
                                                'productYear' => '2013',
                                            ])
                                        </div>
                                        <div class="col-xl-4 col-sm-6">
                                            @include('frontend::components.widgets.person-detail-card', [
                                                'productImg' => 'any-name-portrait.webp',
                                                'productTitle' => __('streamMovies.any_name'),
                                                'productLink' => ' view-all',
                                                'productYear' => '2009',
                                            ])
                                        </div>
                                        <div class="col-xl-4 col-sm-6">
                                            @include('frontend::components.widgets.person-detail-card', [
                                                'productImg' => 'batter-caill-portrait.webp',
                                                'productTitle' => __('streamMovies.better_call_saul'),
                                                'productLink' => ' view-all',
                                                'productYear' => '2023',
                                            ])
                                        </div>
                                        <div class="col-xl-4 col-sm-6">
                                            @include('frontend::components.widgets.person-detail-card', [
                                                'productImg' => 'jumanji-portrait.webp',
                                                'productTitle' => __('streamMovies.jumanji'),
                                                'productLink' => ' view-all',
                                                'productYear' => '2016',
                                            ])
                                        </div>
                                        <div class="col-xl-4 col-sm-6">
                                            @include('frontend::components.widgets.person-detail-card', [
                                                'productImg' => 'yoshi-portrait.webp',
                                                'productTitle' => __('streamMovies.yoshi'),
                                                'productLink' => ' view-all',
                                                'productYear' => '2010',
                                            ])
                                        </div>                                       
                                    </div>
                                </div>
                            </div>

                            <div id="tvshows" class="tab-pane animated fadeInUp" role="tabpanel">
                                <div class="source-list-content ">
                                    <div class="row gy-5">
                                        <div class="col-xl-4 col-sm-6">
                                            @include('frontend::components.widgets.person-detail-card', [
                                                'productImg' => 'titanic-portrait.webp',
                                                'productTitle' => __('streamMovies.titanic'),
                                                'productLink' => ' view-all',
                                                'productYear' => '2000',
                                                ])
                                        </div>
                                        <div class="col-xl-4 col-sm-6">
                                            @include('frontend::components.widgets.person-detail-card', [
                                                'productImg' => 'reed-dog-portrait.webp',
                                                'productTitle' => __('streamMovies.reed_dog'),
                                                'productLink' => ' view-all',
                                                'productYear' => '2010',
                                            ])
                                        </div>
                                        <div class="col-xl-4 col-sm-6">
                                            @include('frontend::components.widgets.person-detail-card', [
                                                'productImg' => 'the-hunter-portrait.webp',
                                                'productTitle' => __('streamMovies.the_hunter'),
                                                'productLink' => ' view-all',
                                                'productYear' => '2012',
                                            ])
                                        </div>
                                        <div class="col-xl-4 col-sm-6">
                                            @include('frontend::components.widgets.person-detail-card', [
                                                'productImg' => 'minions-2.webp',
                                                'productTitle' => __('streamMovies.minions'),
                                                'productLink' => ' view-all',
                                                'productYear' => '2024',
                                            ])
                                        </div>
                                        <div class="col-xl-4 col-sm-6">
                                            @include('frontend::components.widgets.person-detail-card', [
                                                'productImg' => 'charlie-chaplin-portrait.webp',
                                                'productTitle' => __('streamMovies.charliechaplin'),
                                                'productLink' => ' view-all',
                                                'productYear' => '2019',
                                            ])
                                        </div>
                                        <div class="col-xl-4 col-sm-6">
                                            @include('frontend::components.widgets.person-detail-card', [
                                                'productImg' => 'pirates-ofdayones-orignal.webp',
                                                'productTitle' => __('streamMovies.pirates_of_day_one'),
                                                'productLink' => ' view-all',
                                                'productYear' => '2025',
                                            ])
                                        </div>
                                        <div class="col-xl-4 col-sm-6">
                                            @include('frontend::components.widgets.person-detail-card', [
                                                'productImg' => 'dinoosaur-portrait.webp',
                                                'productTitle' => __('streamMovies.dinoosaur'),
                                                'productLink' => ' view-all',
                                                'productYear' => '2018',
                                            ])
                                        </div>
                                        <div class="col-xl-4 col-sm-6">
                                            @include('frontend::components.widgets.person-detail-card', [
                                                'productImg' => 'rabbit-portrait.webp',
                                                'productTitle' => __('streamMovies.rabbit'),
                                                'productLink' => ' view-all',
                                                'productYear' => '2010',
                                            ])
                                        </div>
                                        <div class="col-xl-4 col-sm-6">
                                            @include('frontend::components.widgets.person-detail-card', [
                                                'productImg' => 'hanuman1.webp',
                                                'productTitle' => __('streamMovies.hanuman'),
                                                'productLink' => ' view-all',
                                                'productYear' => '2011',
                                            ])
                                        </div>
                                        <div class="col-xl-4 col-sm-6">
                                            @include('frontend::components.widgets.person-detail-card', [
                                                'productImg' => 'castle-rock-portrait.webp',
                                                'productTitle' => __('streamMovies.castle_rock'),
                                                'productLink' => ' view-all',
                                                'productYear' => '2022',
                                            ])
                                        </div>
                                        <div class="col-xl-4 col-sm-6">
                                            @include('frontend::components.widgets.person-detail-card', [
                                                'productImg' => 'batter-caill-portrait.webp',
                                                'productTitle' => __('streamMovies.better_call_saul'),
                                                'productLink' => ' view-all',
                                                'productYear' => '2015',
                                            ])
                                        </div>
                                        <div class="col-xl-4 col-sm-6">
                                            @include('frontend::components.widgets.person-detail-card', [
                                                'productImg' => 'Adventure-2.webp',
                                                'productTitle' => __('streamMovies.adventure'),
                                                'productLink' => ' view-all',
                                                'productYear' => '2020',
                                            ])
                                        </div>                                     
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    {{-- tab end --}}
                </div>
            </div>
        </div>
    </div>

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}
@endsection
