@extends('layouts.app', ['module_title' => 'videos', 'isSweetalert' => true, 'Activelink' => 'videos', 'isFlatpickr' => true, 'isQuillEditor' => true, 'isSelect2' => true])

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="streamit-wraper-table">
                <div class="card-header d-flex justify-content-between gap-3 flex-wrap align-items-center mb-4">
                    <h2 class="episode-playlist-title wp-heading-inline">
                        <span class="position-relative ">
                            {{__('form.videos-list')}} </span>
                    </h2>
                    <button class="btn btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#season-offcanvas"
                        aria-controls="season-offcanvas">
                        <i class="fa-solid fa-plus me-2"></i>{{__('dashboard.add-new')}}
                    </button>
                </div>

                <div class="table-view table-space">
                    <table id="seasonTable" class="data-tables table custom-table movie_table data-table-one"
                        data-toggle="data-table1">
                        <thead>
                            <tr class="text-uppercase">
                                <th class="">
                                    <input type="checkbox" class="form-check-input" />
                                </th>
                                <th class="">Image</th>
                                <th class="">Title</th>
                                <th class="">Author</th>
                                <th class="">Date and Time</th>
                                <th class="">Views</th>
                                <th class="">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @include('components.datatable.DataTable', [
                                'title' => 'Game of Heros',
                                'thumbnail' => 'movie-thumb/gameofheros.webp',
                                'author' => 'Jenny',
                                'date' => '2025-01-08 04:43:37',
                                'views' => '622 views',
                                'viewsValue' => 'true',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'Little Krishna',
                                'thumbnail' => 'movie-thumb/krishna.webp',
                                'author' => 'Jenny',
                                'date' => '2025-02-05 06:55:19',
                                'views' => '614 views',
                                'viewsValue' => 'true',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'Red Dog',
                                'thumbnail' => 'movie-thumb/reed-dog.webp',
                                'author' => 'Jenny',
                                'date' => '2025-01-22 11:29:40',
                                'views' => '538 views',
                                'viewsValue' => 'true',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'Frozen',
                                'thumbnail' => 'movie-thumb/frzzen.webp',
                                'author' => 'Jenny',
                                'date' => '2025-02-05 06:53:50',
                                'views' => '859 views',
                                'viewsValue' => 'true',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'Titanic',
                                'thumbnail' => 'movie-thumb/tianic.webp',
                                'author' => 'Jenny',
                                'date' => '2025-01-22 11:31:52',
                                'views' => '591 views',
                                'viewsValue' => 'true',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'Adventure',
                                'thumbnail' => 'movie-thumb/Adventure-1.webp',
                                'author' => 'Jenny',
                                'date' => '2024-12-27 05:57:56',
                                'views' => '382 views',
                                'viewsValue' => 'true',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'Kung Fu Panda',
                                'thumbnail' => 'movie-thumb/kung-fu-panda.webp',
                                'author' => 'Jenny',
                                'date' => '2025-01-22 11:31:06',
                                'views' => '737 views',
                                'viewsValue' => 'true',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'Red Dog',
                                'thumbnail' => 'movie-thumb/red.webp',
                                'author' => 'Jenny',
                                'date' => '2025-01-24 11:52:45',
                                'views' => '477 views',
                                'viewsValue' => 'true',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'The Crew',
                                'thumbnail' => 'movie-thumb/the-crew.webp',
                                'author' => 'Jenny',
                                'date' => '2025-01-22 11:24:08',
                                'views' => '236 views',
                                'viewsValue' => 'true',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'Synchronic',
                                'thumbnail' => 'movie-thumb/synchronic.webp',
                                'author' => 'Jenny',
                                'date' => '2024-12-27 05:58:23',
                                'views' => '206 views',
                                'viewsValue' => 'true',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'Lost In Space',
                                'thumbnail' => 'movie-thumb/lost-inst-space.webp',
                                'author' => 'Jenny',
                                'date' => '2025-01-28 10:51:12',
                                'views' => '330 views',
                                'viewsValue' => 'true',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'Toddler',
                                'thumbnail' => 'movie-thumb/toddler.webp',
                                'author' => 'Jenny',
                                'date' => '2025-01-22 11:30:12',
                                'views' => '742 views',
                                'viewsValue' => 'true',
                            ])
                        </tbody>
                    </table>
                </div>

            </div>
            {{-- add --}}
            <div class="offcanvas offcanvas-end offcanvas-width-80" tabindex="-1" id="season-offcanvas">
                <div class="offcanvas-header">
                    <h2 class="episode-playlist-title wp-heading-inline">
                        <span class="position-relative ">
                            {{__('form.add-new-video')}} </span>
                    </h2>
                    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body">
                    <form>
                        <div class="section-form">
                            <div class="row">
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label class="form-label flex-grow-1" for="genres">
                                            {{__('form.title')}}</label>
                                        <input class="form-control" type="text" placeholder="{{__('form.enter-title-of-video')}}"
                                            data-id="multiple">
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label class="form-label flex-grow-1" for="author">{{__('form.author')}}</label>
                                        <select id="author" class="form-control select2-basic-single">
                                            <option>admin</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label class="form-label flex-grow-1" for="status">{{__('form.status')}}</label>
                                        <select id="status" class="form-control select2-basic-single">
                                            <option>Published</option>
                                            <option>Draft</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-5">
                                <div class="col-lg-12">
                                    <div id="editor" style="height:200px"></div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-3">
                                    @include('components.widget.UploadImageVideo', [
                                        'upload_image_name' => __('form.thumbnail'),
                                    ])
                                </div>
                                <div class="col-lg-9">
                                    <div class="form-group">
                                        <label class="form-label flex-grow-1" for="Description">
                                            {{__('form.excerpt')}}
                                        </label>
                                        <textarea id="Description" class="form-control" rows="7" placeholder="{{__('form.enter-encerpt-movie')}}"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="card streamit-tabs-card">
                                <div class="card-body">
                                    <div class="row gy-4">
                                        <div class="col-lg-3">
                                            <div class="streamit-verticle-tab mb-3 mb-md-0">
                                                <div class="nav flex-column nav-pills me-0 me-lg-3 list-inline streamit-tabs"
                                                    role="tablist" aria-orientation="vertical">

                                                    <button class="nav-link active" data-bs-toggle="pill" type="button"
                                                        data-bs-target="#general_tab" role="tab" aria-selected="true">
                                                        <span>{{__('form.general')}}</span>
                                                    </button>
                                                    <button class="nav-link" data-bs-toggle="pill" type="button"
                                                        data-bs-target="#CategoryVideos_tab" role="tab"
                                                        aria-selected="false">
                                                        <span>{{__('dashboard.category')}}</span>
                                                    </button>
                                                    <button class="nav-link" data-bs-toggle="pill" type="button"
                                                        data-bs-target="#tags_tab" role="tab" aria-selected="false">
                                                        <span>{{__('form.tags')}}</span>
                                                    </button>
                                                    <button class="nav-link" data-bs-toggle="pill" type="button"
                                                        data-bs-target="#additionData_tab" role="tab"
                                                        aria-selected="true">
                                                        <span>{{__('form.additional_data')}}</span>
                                                    </button>
                                                    <button class="nav-link" data-bs-toggle="pill" type="button"
                                                        data-bs-target="#membershipLevel_tab" role="tab"
                                                        aria-selected="false">
                                                        <span>{{__('form.membership-level')}}</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-9 edit-tab-content">
                                            <div class="tab-content" id="streamit-tabs-content">
                                                <div class="tab-pane fade show active" id="general_tab" role="tabpanel">
                                                    <div class="p-4 px-2">
                                                        <div class="row">
                                                            <div class="col-lg-6">
                                                                <div class="form-group">
                                                                    <label class="form-label flex-grow-1">{{__('form.choose-movie-method')}}</label>
                                                                    <select class="form-control select2-basic-single"
                                                                        id="ott-select">
                                                                        <option>Upload movie</option>
                                                                        <option>Embed movie</option>
                                                                        <option>Movie URL</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-6">
                                                                @include(
                                                                    'components.widget.UploadImageVideo',
                                                                    [
                                                                        'upload_image_name' => __('form.upload-movie'),
                                                                    ]
                                                                )
                                                            </div>
                                                        </div>
                                                        <div class="row">

                                                            <div class="col-lg-6">
                                                                <label class="d-block form-label">{{__('form.catalog-visibility')}}</label>
                                                                <div class="form-check d-block">
                                                                    <input class="form-check-input" type="radio"
                                                                        name="flexRadioDefault" id="flexRadioDefault2">
                                                                    <label class="form-check-label"
                                                                        for="flexRadioDefault2">
                                                                        {{__('form.catalog-and-search-Results')}}
                                                                    </label>
                                                                </div>
                                                                <div class="form-check d-block">
                                                                    <input class="form-check-input" type="radio"
                                                                        name="flexRadioDefault" id="flexRadioDefault3">
                                                                    <label class="form-check-label"
                                                                        for="flexRadioDefault3">
                                                                        {{__('form.catalog-only')}}
                                                                    </label>
                                                                </div>
                                                                <div class="form-check d-block">
                                                                    <input class="form-check-input" type="radio"
                                                                        name="flexRadioDefault" id="flexRadioDefault4">
                                                                    <label class="form-check-label"
                                                                        for="flexRadioDefault4">
                                                                        {{__('form.search-results-only')}}
                                                                    </label>
                                                                </div>
                                                                <div class="form-check d-block">
                                                                    <input class="form-check-input" type="radio"
                                                                        name="flexRadioDefault" id="flexRadioDefault5">
                                                                    <label class="form-check-label"
                                                                        for="flexRadioDefault5">
                                                                        {{__('form.hidden')}}
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-6">
                                                                <div class="form-group">
                                                                    <label class="form-label flex-grow-1"
                                                                        for="genres">{{__('form.language')}}</label>
                                                                    <select id="genres"
                                                                        class="form-control select2-basic-single"
                                                                        id="ott-select">
                                                                        <option>Upload movie</option>
                                                                        <option>Embed movie</option>
                                                                        <option>Movie URL</option>

                                                                        <option>
                                                                            Afrikaans </option>
                                                                        <option>
                                                                            አማርኛ </option>
                                                                        <option>
                                                                            Aragonés </option>
                                                                        <option>
                                                                            العربية </option>
                                                                        <option>
                                                                            العربية المغربية </option>
                                                                        <option>
                                                                            অসমীয়া </option>
                                                                        <option>
                                                                            گؤنئی آذربایجان </option>
                                                                        <option>
                                                                            Azərbaycan dili </option>
                                                                        <option>
                                                                            Беларуская мова </option>
                                                                        <option>
                                                                            Български </option>
                                                                        <option>
                                                                            বাংলা </option>
                                                                        <option>
                                                                            བོད་ཡིག </option>
                                                                        <option>
                                                                            Bosanski </option>
                                                                        <option>
                                                                            Català </option>
                                                                        <option>
                                                                            Cebuano </option>
                                                                        <option>
                                                                            Čeština </option>
                                                                        <option>
                                                                            Cymraeg </option>
                                                                        <option>
                                                                            Dansk </option>
                                                                        <option>
                                                                            Deutsch (Schweiz) </option>
                                                                        <option>
                                                                            Deutsch </option>
                                                                        <option>
                                                                            Deutsch (Sie) </option>
                                                                        <option>
                                                                            Deutsch (Österreich) </option>
                                                                        <option>
                                                                            Deutsch (Schweiz, Du) </option>
                                                                        <option>
                                                                            Dolnoserbšćina </option>
                                                                        <option>
                                                                            རྫོང་ཁ </option>
                                                                        <option>
                                                                            Ελληνικά </option>
                                                                        <option>
                                                                            English (South Africa) </option>
                                                                        <option>
                                                                            English (Australia) </option>
                                                                        <option>
                                                                            English (UK) </option>
                                                                        <option>
                                                                            English (New Zealand) </option>
                                                                        <option>
                                                                            English (Canada) </option>
                                                                        <option>
                                                                            Esperanto </option>
                                                                        <option>
                                                                            Español de Perú </option>
                                                                        <option>
                                                                            Español de México </option>
                                                                        <option>
                                                                            Español de Colombia </option>
                                                                        <option>
                                                                            Español </option>
                                                                        <option>
                                                                            Español de Venezuela </option>
                                                                        <option>
                                                                            Español de Ecuador </option>
                                                                        <option>
                                                                            Español de República Dominicana </option>
                                                                        <option>
                                                                            Español de Uruguay </option>
                                                                        <option>
                                                                            Español de Puerto Rico </option>
                                                                        <option>
                                                                            Español de Guatemala </option>
                                                                        <option>
                                                                            Español de Costa Rica </option>
                                                                        <option>
                                                                            Español de Chile </option>
                                                                        <option>
                                                                            Español de Argentina </option>
                                                                        <option>
                                                                            Eesti </option>
                                                                        <option>
                                                                            Euskara </option>
                                                                        <option>
                                                                            (فارسی (افغانستان </option>
                                                                        <option>
                                                                            فارسی </option>
                                                                        <option>
                                                                            Suomi </option>
                                                                        <option>
                                                                            Français du Canada </option>
                                                                        <option>
                                                                            Français </option>
                                                                        <option>
                                                                            Français de Belgique </option>
                                                                        <option>
                                                                            Friulian </option>
                                                                        <option>
                                                                            Frysk </option>
                                                                        <option>
                                                                            Gàidhlig </option>
                                                                        <option>
                                                                            Galego </option>
                                                                        <option>
                                                                            ગુજરાતી </option>
                                                                        <option>
                                                                            هزاره گی </option>
                                                                        <option>
                                                                            עִבְרִית </option>
                                                                        <option>
                                                                            हिन्दी </option>
                                                                        <option>
                                                                            Hrvatski </option>
                                                                        <option>
                                                                            Hornjoserbšćina </option>
                                                                        <option>
                                                                            Magyar </option>
                                                                        <option>
                                                                            Հայերեն </option>
                                                                        <option>
                                                                            Bahasa Indonesia </option>
                                                                        <option>
                                                                            Íslenska </option>
                                                                        <option>
                                                                            Italiano </option>
                                                                        <option>
                                                                            日本語 </option>
                                                                        <option>
                                                                            Basa Jawa </option>
                                                                        <option>
                                                                            ქართული </option>
                                                                        <option>
                                                                            Taqbaylit </option>
                                                                        <option>
                                                                            Қазақ тілі </option>
                                                                        <option>
                                                                            ភាសាខ្មែរ </option>
                                                                        <option>
                                                                            ಕನ್ನಡ </option>
                                                                        <option>
                                                                            한국어 </option>
                                                                        <option>
                                                                            كوردی‎ </option>
                                                                        <option>
                                                                            Кыргызча </option>
                                                                        <option>
                                                                            ພາສາລາວ </option>
                                                                        <option>
                                                                            Lietuvių kalba </option>
                                                                        <option>
                                                                            Latviešu valoda </option>
                                                                        <option>
                                                                            Македонски јазик </option>
                                                                        <option>
                                                                            മലയാളം </option>
                                                                        <option>
                                                                            Монгол </option>
                                                                        <option>
                                                                            मराठी </option>
                                                                        <option>
                                                                            Bahasa Melayu </option>
                                                                        <option>
                                                                            ဗမာစာ </option>
                                                                        <option>
                                                                            Norsk bokmål </option>
                                                                        <option>
                                                                            नेपाली </option>
                                                                        <option>
                                                                            Nederlands (Formeel) </option>
                                                                        <option>
                                                                            Nederlands (België) </option>
                                                                        <option>
                                                                            Nederlands </option>
                                                                        <option>
                                                                            Norsk nynorsk </option>
                                                                        <option>
                                                                            Occitan </option>
                                                                        <option>
                                                                            ਪੰਜਾਬੀ </option>
                                                                        <option>
                                                                            Polski </option>
                                                                        <option>
                                                                            پښتو </option>
                                                                        <option>
                                                                            Português (AO90) </option>
                                                                        <option>
                                                                            Português do Brasil </option>
                                                                        <option>
                                                                            Português de Angola </option>
                                                                        <option>
                                                                            Português </option>
                                                                        <option>
                                                                            Ruáinga </option>
                                                                        <option>
                                                                            Română </option>
                                                                        <option>
                                                                            Русский </option>
                                                                        <option>
                                                                            Сахалыы </option>
                                                                        <option>
                                                                            سنڌي </option>
                                                                        <option>
                                                                            සිංහල </option>
                                                                        <option>
                                                                            Slovenčina </option>
                                                                        <option>
                                                                            سرائیکی </option>
                                                                        <option>
                                                                            Slovenščina </option>
                                                                        <option>
                                                                            Shqip </option>
                                                                        <option>
                                                                            Српски језик </option>
                                                                        <option>
                                                                            Svenska </option>
                                                                        <option>
                                                                            Kiswahili </option>
                                                                        <option>
                                                                            Ślōnskŏ gŏdka </option>
                                                                        <option>
                                                                            தமிழ் </option>
                                                                        <option>
                                                                            தமிழ் </option>
                                                                        <option>
                                                                            తెలుగు </option>
                                                                        <option>
                                                                            ไทย </option>
                                                                        <option>
                                                                            Tagalog </option>
                                                                        <option>
                                                                            Türkçe </option>
                                                                        <option>
                                                                            Татар теле </option>
                                                                        <option>
                                                                            Reo Tahiti </option>
                                                                        <option>
                                                                            ئۇيغۇرچە </option>
                                                                        <option>
                                                                            Українська </option>
                                                                        <option>
                                                                            اردو </option>
                                                                        <option>
                                                                            O‘zbekcha </option>
                                                                        <option>
                                                                            Tiếng Việt </option>
                                                                        <option>
                                                                            简体中文 </option>
                                                                        <option>
                                                                            繁體中文 </option>
                                                                        <option>
                                                                            香港中文 </option>

                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-6">
                                                                <div class="mt-3">
                                                                    <label class="d-block form-label">{{__('form.featured')}}</label>
                                                                    <div class="checkbox mb-3">
                                                                        <div class="form-check">
                                                                            <input class="form-check-input"
                                                                                type="checkbox" id="flexCheckDefault3">
                                                                            <label class="form-check-label"
                                                                                for="flexCheckDefault3">
                                                                                {{__('form.this-a-featured-movie')}}
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="tab-pane fade" id="CategoryVideos_tab" role="tabpanel">
                                                    <div class="p-4 px-2">
                                                        <div class="form-group">
                                                            <label class="form-label flex-grow-1"
                                                                for="video-category">{{__('form.choose-video-category')}}</label>
                                                            <select id="video-category"
                                                                class="form-control select2-basic-multiple" multiple>
                                                                <option>Action</option>
                                                                <option>Adventure</option>
                                                                <option>Horror</option>
                                                                <option>Circus</option>
                                                                <option>Comedy</option>
                                                                <option>Crime</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="tab-pane fade" id="tags_tab" role="tabpanel">
                                                    <div class="p-4 px-2">

                                                        <div class="form-group">
                                                            <label class="form-label flex-grow-1" for="video-tages">{{__('form.choose-video-tags')}}</label>
                                                            <select id="video-tages"
                                                                class="form-control select2-basic-multiple" multiple>
                                                                <option>Action</option>
                                                                <option>Adventure</option>
                                                                <option>Animation</option>
                                                                <option>Circus</option>
                                                                <option>Comedy</option>
                                                                <option>Crime</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="tab-pane fade" id="additionData_tab" role="tabpanel">
                                                    <div class="p-4 px-2">
                                                        <div class="row">
                                                            <div class="col-lg-6">
                                                                @include(
                                                                    'components.widget.UploadImageVideo',
                                                                    [
                                                                        'upload_image_name' => __('form.trailer-logo'),
                                                                    ]
                                                                )
                                                            </div>
                                                            <div class="col-lg-6">
                                                                @include(
                                                                    'components.widget.UploadImageVideo',
                                                                    [
                                                                        'upload_image_name' => __('form.trailer-image'),
                                                                    ]
                                                                )
                                                            </div>
                                                            <div class="col-lg-6">
                                                                <div class="form-group">
                                                                    <label class="form-label flex-grow-1"
                                                                        for="related-product">{{__('form.select-related-product')}}</label>
                                                                    <select id="related-product"
                                                                        class="form-control select2-basic-multiple"
                                                                        multiple>
                                                                        <option>Polo</option>
                                                                        <option>T-shirt</option>
                                                                        <option>Cap</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-6">
                                                                <div class="form-group">
                                                                    <label class="form-label" for="parent-genre1">{{__('form.show-download-button')}}</label>
                                                                    <select class="form-control select2-basic-multiple"
                                                                        id="parent-genre1" onchange="getchange(event)">
                                                                        <option>No</option>
                                                                        <option>Link</option>
                                                                        <option>Upload</option>
                                                                    </select>
                                                                </div>

                                                                <div class="form-group d-none" id="link-field">
                                                                    <label class="form-label flex-grow-1" for="genres">
                                                                        {{__('form.trailer-link')}}
                                                                            </label>
                                                                    <input type="text" class="form-control"
                                                                        name="Download URL"
                                                                        placeholder="{{__('form.enter-trailer-link')}}">
                                                                </div>

                                                                <div class="d-none" id="upload-field">
                                                                    @include(
                                                                        'components.widget.UploadImageVideo',
                                                                        [
                                                                            'upload_image_name' =>
                                                                                __('form.trailer-image'),
                                                                        ]
                                                                    )
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-6">
                                                                <label class="d-block form-label">{{__('form.upcoming')}}</label>
                                                                <div class="checkbox mb-3">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox"
                                                                            id="flexCheckDefault31">
                                                                        <label class="form-check-label"
                                                                            for="flexCheckDefault31">
                                                                            {{__('form.upcoming')}}
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-6">
                                                                <div class="row">
                                                                    <label class="form-label">{{__('form.video-time-duration')}}</label>
                                                                    <div class="col-lg-6">
                                                                        <div class="form-group">
                                                                            <input type="number" class="form-control"
                                                                                name="hours" value="0"
                                                                                placeholder="{{__('form.hours')}}" min="0"
                                                                                aria-invalid="false">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-6">
                                                                        <div class="form-group">
                                                                            <input type="number" class="form-control"
                                                                                name="minutes" value="0"
                                                                                placeholder="{{__('form.minutes')}}" min="0"
                                                                                max="59" aria-invalid="false">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-6">
                                                                <label class="d-block form-label">{{__('form.notification')}}</label>
                                                                <div class="checkbox mb-3">
                                                                    <div class="form-check">
                                                                        <label class="form-check-label"
                                                                            for="flexCheckNotify">
                                                                            <input class="form-check-input"
                                                                                type="checkbox" id="flexCheckNotify">
                                                                            {{__('form.notify-user-about-prod')}}</label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="tab-pane fade" id="membershipLevel_tab" role="tabpanel">
                                                    <div class="p-4 px-2">
                                                        <div class="form-group">
                                                            <label class="form-label flex-grow-1" for="pmp_levels">{{__('form.choose-pmp-levels')}}</label>
                                                            <select id="pmp_levels"
                                                                class="form-control select2-basic-multiple" multiple>
                                                                <option>free </option>
                                                                <option>Monthly level </option>
                                                                <option>Annual </option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="offcanvas-footer border-top">
                    <div class="d-flex gap-3 p-3">
                        <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                            <i class="ph-fill ph-floppy-disk-back"></i>{{ __('movielist.Save') }}
                        </button>
                        <button type="button" class="btn btn-outline-primary d-flex align-items-center gap-2" data-bs-dismiss="offcanvas"
                            aria-label="Close">
                            <i class="ph ph-caret-double-left"></i>{{ __('movielist.Close') }}
                        </button>
                    </div>
                </div>
            </div>


            {{-- edit --}}
            <div class="offcanvas offcanvas-end offcanvas-width-80" tabindex="-1" id="season-offcanvas-edit">
                <div class="offcanvas-header">
                    <h2 class="episode-playlist-title wp-heading-inline">
                        <span class="position-relative ">
                            {{__('movielist.Edit_Video')}} </span>
                    </h2>
                    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"
                        aria-label="Close"></button>
                </div>
                <div class="offcanvas-body">
                    <form>
                        <div class="section-form">
                            <div class="row">
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label class="form-label flex-grow-1" for="genres">
                                            {{__('form.title')}}</label>
                                        <input class="form-control" type="text"
                                            placeholder="{{__('form.enter-title-movie')}}" data-id="multiple">
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label class="form-label flex-grow-1" for="author1">{{__('form.author')}}</label>
                                        <select id="author1" class="form-control select2-basic-single">
                                            <option>admin</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label class="form-label flex-grow-1" for="status1">{{__('form.status')}}</label>
                                        <select id="status1" class="form-control select2-basic-single">
                                            <option>Published</option>
                                            <option>Draft</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-5">
                                <label class="form-label flex-grow-1">{{__('form.content')}}</label>
                                <div class="col-lg-12">
                                    <div id="editor1" style="height:200px"></div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-3">
                                    @include('components.widget.UploadImageVideo', [
                                        'upload_image_name' => __('form.thumbnail'),
                                        'isUploadImageDefault' => true,
                                    ])
                                </div>
                                <div class="col-lg-9">
                                    <div class="form-group">
                                        <label class="form-label flex-grow-1" for="Description1">
                                            {{__('form.excerpt')}}</label>
                                        <textarea id="Description1" class="form-control" rows="7" placeholder="{{__('form.enter-encerpt-movie')}}"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="card streamit-tabs-card">
                                <div class="card-body">
                                    <div class="row gy-4">
                                        <div class="col-lg-3">
                                            <div class="streamit-verticle-tab mb-3 mb-md-0">
                                                <div class="nav flex-column nav-pills me-0 me-lg-3  list-inline streamit-tabs"
                                                    role="tablist" aria-orientation="vertical">
                                                    <button class="nav-link active" data-bs-toggle="pill" type="button"
                                                        data-bs-target="#general_tab1" role="tab"
                                                        aria-selected="true">
                                                        <span>{{__('form.general')}}</span>
                                                    </button>
                                                    <button class="nav-link" data-bs-toggle="pill" type="button"
                                                        data-bs-target="#recommendedMovies_tab1" role="tab"
                                                        aria-selected="false">
                                                        <span>{{__('dashboard.category')}}</span>
                                                    </button>
                                                    <button class="nav-link" data-bs-toggle="pill" type="button"
                                                        data-bs-target="#tag_tab1" role="tab" aria-selected="false">
                                                        <span>{{__('form.tags')}}</span>
                                                    </button>
                                                    <button class="nav-link" data-bs-toggle="pill" type="button"
                                                        data-bs-target="#additionData_tab1" role="tab"
                                                        aria-selected="true">
                                                        <span>{{__('form.additional_data')}}</span>
                                                    </button>
                                                    <button class="nav-link" data-bs-toggle="pill" type="button"
                                                        data-bs-target="#membershipLevel_tab1" role="tab"
                                                        aria-selected="false">
                                                        <span>{{__('form.membership-level')}}</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-9 edit-tab-content">
                                            <div class="tab-content" id="streamit-tabs-content">
                                                <div class="tab-pane fade show active" id="general_tab1" role="tabpanel">
                                                    <div class="p-4 px-2">
                                                        <div class="row">
                                                            <div class="col-lg-6">
                                                                <div class="form-group">
                                                                    <label class="form-label flex-grow-1"
                                                                        for="movieMethod1">{{__('form.choose-movie-method')}}</label>
                                                                    <select id="movieMethod1"
                                                                        class="form-control select2-basic-single">
                                                                        <option>Upload movie</option>
                                                                        <option>Embed movie</option>
                                                                        <option>Movie URL</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-6">
                                                                @include(
                                                                    'components.widget.UploadImageVideo',
                                                                    [
                                                                        'upload_image_name' => __('form.upload-movie'),
                                                                    ]
                                                                )
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-lg-6">
                                                                <div class="form-group">
                                                                    <label class="form-label flex-grow-1"
                                                                        for="genres1">{{__('form.language')}}</label>
                                                                    <select id="genres1"
                                                                        class="form-control select2-basic-multiple"
                                                                        multiple>
                                                                        <option>
                                                                            Afrikaans </option>
                                                                        <option>
                                                                            አማርኛ </option>
                                                                        <option>
                                                                            Aragonés </option>
                                                                        <option>
                                                                            العربية </option>
                                                                        <option>
                                                                            العربية المغربية </option>
                                                                        <option>
                                                                            অসমীয়া </option>
                                                                        <option>
                                                                            گؤنئی آذربایجان </option>
                                                                        <option>
                                                                            Azərbaycan dili </option>
                                                                        <option>
                                                                            Беларуская мова </option>
                                                                        <option>
                                                                            Български </option>
                                                                        <option>
                                                                            বাংলা </option>
                                                                        <option>
                                                                            བོད་ཡིག </option>
                                                                        <option>
                                                                            Bosanski </option>
                                                                        <option>
                                                                            Català </option>
                                                                        <option>
                                                                            Cebuano </option>
                                                                        <option>
                                                                            Čeština </option>
                                                                        <option>
                                                                            Cymraeg </option>
                                                                        <option>
                                                                            Dansk </option>
                                                                        <option>
                                                                            Deutsch (Schweiz) </option>
                                                                        <option>
                                                                            Deutsch </option>
                                                                        <option>
                                                                            Deutsch (Sie) </option>
                                                                        <option>
                                                                            Deutsch (Österreich) </option>
                                                                        <option>
                                                                            Deutsch (Schweiz, Du) </option>
                                                                        <option>
                                                                            Dolnoserbšćina </option>
                                                                        <option>
                                                                            རྫོང་ཁ </option>
                                                                        <option>
                                                                            Ελληνικά </option>
                                                                        <option>
                                                                            English (South Africa) </option>
                                                                        <option>
                                                                            English (Australia) </option>
                                                                        <option>
                                                                            English (UK) </option>
                                                                        <option>
                                                                            English (New Zealand) </option>
                                                                        <option>
                                                                            English (Canada) </option>
                                                                        <option>
                                                                            Esperanto </option>
                                                                        <option>
                                                                            Español de Perú </option>
                                                                        <option>
                                                                            Español de México </option>
                                                                        <option>
                                                                            Español de Colombia </option>
                                                                        <option>
                                                                            Español </option>
                                                                        <option>
                                                                            Español de Venezuela </option>
                                                                        <option>
                                                                            Español de Ecuador </option>
                                                                        <option>
                                                                            Español de República Dominicana </option>
                                                                        <option>
                                                                            Español de Uruguay </option>
                                                                        <option>
                                                                            Español de Puerto Rico </option>
                                                                        <option>
                                                                            Español de Guatemala </option>
                                                                        <option>
                                                                            Español de Costa Rica </option>
                                                                        <option>
                                                                            Español de Chile </option>
                                                                        <option>
                                                                            Español de Argentina </option>
                                                                        <option>
                                                                            Eesti </option>
                                                                        <option>
                                                                            Euskara </option>
                                                                        <option>
                                                                            (فارسی (افغانستان </option>
                                                                        <option>
                                                                            فارسی </option>
                                                                        <option>
                                                                            Suomi </option>
                                                                        <option>
                                                                            Français du Canada </option>
                                                                        <option>
                                                                            Français </option>
                                                                        <option>
                                                                            Français de Belgique </option>
                                                                        <option>
                                                                            Friulian </option>
                                                                        <option>
                                                                            Frysk </option>
                                                                        <option>
                                                                            Gàidhlig </option>
                                                                        <option>
                                                                            Galego </option>
                                                                        <option selected="">
                                                                            ગુજરાતી </option>
                                                                        <option>
                                                                            هزاره گی </option>
                                                                        <option>
                                                                            עִבְרִית </option>
                                                                        <option>
                                                                            हिन्दी </option>
                                                                        <option>
                                                                            Hrvatski </option>
                                                                        <option>
                                                                            Hornjoserbšćina </option>
                                                                        <option>
                                                                            Magyar </option>
                                                                        <option>
                                                                            Հայերեն </option>
                                                                        <option>
                                                                            Bahasa Indonesia </option>
                                                                        <option>
                                                                            Íslenska </option>
                                                                        <option>
                                                                            Italiano </option>
                                                                        <option>
                                                                            日本語 </option>
                                                                        <option>
                                                                            Basa Jawa </option>
                                                                        <option>
                                                                            ქართული </option>
                                                                        <option>
                                                                            Taqbaylit </option>
                                                                        <option>
                                                                            Қазақ тілі </option>
                                                                        <option>
                                                                            ភាសាខ្មែរ </option>
                                                                        <option>
                                                                            ಕನ್ನಡ </option>
                                                                        <option>
                                                                            한국어 </option>
                                                                        <option>
                                                                            كوردی‎ </option>
                                                                        <option>
                                                                            Кыргызча </option>
                                                                        <option>
                                                                            ພາສາລາວ </option>
                                                                        <option>
                                                                            Lietuvių kalba </option>
                                                                        <option>
                                                                            Latviešu valoda </option>
                                                                        <option>
                                                                            Македонски јазик </option>
                                                                        <option>
                                                                            മലയാളം </option>
                                                                        <option>
                                                                            Монгол </option>
                                                                        <option>
                                                                            मराठी </option>
                                                                        <option>
                                                                            Bahasa Melayu </option>
                                                                        <option>
                                                                            ဗမာစာ </option>
                                                                        <option>
                                                                            Norsk bokmål </option>
                                                                        <option>
                                                                            नेपाली </option>
                                                                        <option>
                                                                            Nederlands (Formeel) </option>
                                                                        <option>
                                                                            Nederlands (België) </option>
                                                                        <option>
                                                                            Nederlands </option>
                                                                        <option>
                                                                            Norsk nynorsk </option>
                                                                        <option>
                                                                            Occitan </option>
                                                                        <option>
                                                                            ਪੰਜਾਬੀ </option>
                                                                        <option>
                                                                            Polski </option>
                                                                        <option>
                                                                            پښتو </option>
                                                                        <option>
                                                                            Português (AO90) </option>
                                                                        <option>
                                                                            Português do Brasil </option>
                                                                        <option>
                                                                            Português de Angola </option>
                                                                        <option>
                                                                            Português </option>
                                                                        <option>
                                                                            Ruáinga </option>
                                                                        <option>
                                                                            Română </option>
                                                                        <option>
                                                                            Русский </option>
                                                                        <option>
                                                                            Сахалыы </option>
                                                                        <option>
                                                                            سنڌي </option>
                                                                        <option>
                                                                            සිංහල </option>
                                                                        <option>
                                                                            Slovenčina </option>
                                                                        <option>
                                                                            سرائیکی </option>
                                                                        <option>
                                                                            Slovenščina </option>
                                                                        <option>
                                                                            Shqip </option>
                                                                        <option>
                                                                            Српски језик </option>
                                                                        <option>
                                                                            Svenska </option>
                                                                        <option>
                                                                            Kiswahili </option>
                                                                        <option>
                                                                            Ślōnskŏ gŏdka </option>
                                                                        <option>
                                                                            தமிழ் </option>
                                                                        <option>
                                                                            தமிழ் </option>
                                                                        <option>
                                                                            తెలుగు </option>
                                                                        <option>
                                                                            ไทย </option>
                                                                        <option>
                                                                            Tagalog </option>
                                                                        <option>
                                                                            Türkçe </option>
                                                                        <option>
                                                                            Татар теле </option>
                                                                        <option>
                                                                            Reo Tahiti </option>
                                                                        <option>
                                                                            ئۇيغۇرچە </option>
                                                                        <option>
                                                                            Українська </option>
                                                                        <option>
                                                                            اردو </option>
                                                                        <option>
                                                                            O‘zbekcha </option>
                                                                        <option>
                                                                            Tiếng Việt </option>
                                                                        <option>
                                                                            简体中文 </option>
                                                                        <option>
                                                                            繁體中文 </option>
                                                                        <option>
                                                                            香港中文 </option>

                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="tab-pane fade" id="recommendedMovies_tab1" role="tabpanel">
                                                    <div class="p-4 px-2">
                                                        <div class="form-group mb-0">
                                                            <label class="form-label flex-grow-1"
                                                                for="recommended_movies1">{{__('form.recommended_movies')}}</label>
                                                            <select id="recommended_movies1"
                                                                class="form-control select2-basic-multiple" multiple>
                                                                <option>Action </option>
                                                                <option>Advanture </option>
                                                                <option>Animation </option>
                                                                <option>Crime </option>
                                                                <option selected="">Documentary </option>
                                                                <option>Fantasy </option>
                                                                <option>Horror </option>
                                                                <option>Mystery </option>
                                                                <option>Romance </option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="tab-pane fade" id="tag_tab1" role="tabpanel">
                                                    <div class="p-4 px-2">
                                                        <div class="form-group mb-0">
                                                            <label class="form-label flex-grow-1"
                                                                for="related_videos2">{{__('form.choose-video-category')}}</label>
                                                            <select id="related_videos2"
                                                                class="form-control select2-basic-multiple" multiple>
                                                                <option>Action </option>
                                                                <option>Advanture </option>
                                                                <option>Animation </option>
                                                                <option>Crime </option>
                                                                <option>Documentary </option>
                                                                <option>Fantasy </option>
                                                                <option>Horror </option>
                                                                <option>Mystery </option>
                                                                <option>Romance </option>
                                                                <option>Thriller</option>
                                                                <option>Universe </option>
                                                                <option selected="">Vtag</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="tab-pane fade" id="additionData_tab1" role="tabpanel">
                                                    <div class="p-4 px-2">
                                                        <div class="row">
                                                            <div class="col-lg-6">
                                                                @include(
                                                                    'components.widget.UploadImageVideo',
                                                                    [
                                                                        'upload_image_name' => __('form.trailer-logo'),
                                                                        'isUploadImageDefault' => true,
                                                                    ]
                                                                )
                                                            </div>
                                                            <div class="col-lg-6">
                                                                @include(
                                                                    'components.widget.UploadImageVideo',
                                                                    [
                                                                        'upload_image_name' => __('form.trailer-image'),
                                                                        'isUploadImageDefault' => true,
                                                                    ]
                                                                )
                                                            </div>
                                                            <div class="col-lg-6">
                                                                <div class="form-group">
                                                                    <label class="form-label flex-grow-1"
                                                                        for="related-product">{{__('form.select-related-product')}}</label>
                                                                    <select id="related-product"
                                                                        class="form-control select2-basic-multiple"
                                                                        multiple>
                                                                        <option>Polo</option>
                                                                        <option>T-shirt</option>
                                                                        <option>Cap</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-6">
                                                                <div class="form-group">
                                                                    <label class="form-label" for="parent-genre1">{{__('form.show-download-button')}}</label>
                                                                    <select class="form-control select2-basic-multiple"
                                                                        id="parent-genre1" onchange="getchange(event)">
                                                                        <option>No</option>
                                                                        <option>Link</option>
                                                                        <option>Upload</option>
                                                                    </select>
                                                                </div>

                                                                <div class="form-group d-none" id="link-field">
                                                                    <label class="form-label flex-grow-1" for="genres">
                                                                        {{__('form.trailer-link')}}
                                                                            </label>
                                                                    <input type="text" class="form-control"
                                                                        name="Download URL"
                                                                        placeholder="{{__('form.enter-trailer-link')}}">
                                                                </div>

                                                                <div class="d-none" id="upload-field">
                                                                    @include(
                                                                        'components.widget.UploadImageVideo',
                                                                        [
                                                                            'upload_image_name' =>
                                                                                __('form.trailer-image'),
                                                                            'isUploadImageDefault' => true,
                                                                        ]
                                                                    )
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-6">
                                                                <label class="d-block form-label">{{__('form.upcoming')}}</label>
                                                                <div class="checkbox mb-3">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox"
                                                                            id="flexCheckDefault31">
                                                                        <label class="form-check-label"
                                                                            for="flexCheckDefault31">
                                                                            {{__('form.upcoming')}}
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-6">
                                                                <div class="row">
                                                                    <label class="form-label">{{__('form.video-time-duration')}}</label>
                                                                    <div class="col-lg-6">
                                                                        <div class="form-group">
                                                                            <input type="number" class="form-control"
                                                                                name="hours" value="0"
                                                                                placeholder="{{__('form.hours')}}" min="0"
                                                                                aria-invalid="false">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-6">
                                                                        <div class="form-group">
                                                                            <input type="number" class="form-control"
                                                                                name="minutes" value="0"
                                                                                placeholder="{{__('form.minutes')}}" min="0"
                                                                                max="59" aria-invalid="false">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-6">
                                                                <label class="d-block form-label">{{__('form.notification')}}</label>
                                                                <div class="checkbox mb-3">
                                                                    <div class="form-check">
                                                                        <label class="form-check-label"
                                                                            for="flexCheckNotify">
                                                                            <input class="form-check-input"
                                                                                type="checkbox" id="flexCheckNotify">
                                                                            {{__('form.notify-user-about-prod')}}</label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="tab-pane fade" id="membershipLevel_tab1" role="tabpanel">
                                                    <div class="p-4 px-2">
                                                        <div class="form-group">
                                                            <label class="form-label flex-grow-1" for="pmp_levels1">{{__('form.choose-pmp-levels')}}</label>
                                                            <select id="pmp_levels1"
                                                                class="form-control select2-basic-multiple" multiple>
                                                                <option>free </option>
                                                                <option>Monthly level </option>
                                                                <option>Annual </option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="offcanvas-footer border-top">
                    <div class="d-flex gap-3 p-3">
                        <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                            <i class="ph-fill ph-floppy-disk-back"></i>{{ __('movielist.Save') }}
                        </button>
                        <button type="button" class="btn btn-outline-primary d-flex align-items-center gap-2" data-bs-dismiss="offcanvas"
                            aria-label="Close">
                            <i class="ph ph-caret-double-left"></i>{{ __('movielist.Close') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
