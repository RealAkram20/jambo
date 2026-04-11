@extends('layouts.app', ['module_title' => 'Movie List', 'isSweetalert' => true, 'isFlatpickr' => true, 'isQuillEditor' => true, 'isSelect2' => true])
@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="streamit-wraper-table">
                <div class="card-header d-flex justify-content-between gap-3 flex-wrap align-items-center mb-4">
                    <h2 class="episode-playlist-title wp-heading-inline">
                        <span class="position-relative ">
                            {{ __('sidebar.movies') }} </span>
                    </h2>
                    <button class="btn btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#season-offcanvas"
                        aria-controls="season-offcanvas"><i
                            class="fa-solid fa-plus me-2"></i>{{ __('movielist.add_movie_icon') }}</button>
                </div>
                <div class="table-view table-space">
                    <table id="seasonTable" class="data-tables data-table-one table custom-table movie_table"
                        data-toggle="data-table1">
                        <thead>
                            <tr class="text-uppercase">
                                <th>
                                    <input type="checkbox" class="form-check-input" />
                                </th>
                                <th>Image</th>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Date and Time</th>
                                <th>Views</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @include('components.datatable.DataTable', [
                                'title' =>  'Game of Heros',
                                'thumbnail' => 'movie-thumb/gameofheros.webp',
                                'author' => 'Jenny',
                                'date' => '2025-06-23 12:18:49',
                                'views' => '8099 views',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'Venom',
                                'thumbnail' => 'movie-thumb/venom.webp',
                                'author' => 'Jenny',
                                'date' => '2024-12-28 11:10:29',
                                'views' => '2387 views',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'Rabbit',
                                'thumbnail' => 'movie-thumb/rabbit.webp',
                                'author' => 'Jenny',
                                'date' => '2025-05-30 03:59:11',
                                'views' => '6779 views',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'Migration',
                                'thumbnail' => 'movie-thumb/migration.webp',
                                'author' => 'Jenny',
                                'date' => '2025-05-23 08:31:29',
                                'views' => '977 views',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'Krishna',
                                'thumbnail' => 'movie-thumb/krishna.webp',
                                'author' => 'Jenny',
                                'date' => '2025-05-23 08:15:01',
                                'views' => '3013 views',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'Kali',
                                'thumbnail' => 'movie-thumb/kali.webp',
                                'author' => 'Jenny',
                                'date' => '2025-05-23 08:18:19',
                                'views' => '1742 views',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'Joker',
                                'thumbnail' => 'movie-thumb/joker.webp',
                                'author' => 'Jenny',
                                'date' => '2024-12-23 11:39:21',
                                'views' => '960 views',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'Deadpool',
                                'thumbnail' => 'movie-thumb/deadpool.webp',
                                'author' => 'Jenny',
                                'date' => '2025-01-22 09:35:00',
                                'views' => '760 views',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'Any Name',
                                'thumbnail' => 'movie-thumb/any-name.webp',
                                'author' => 'Jenny',
                                'date' => '2025-01-22 09:37:53',
                                'views' => '853 views',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'YoShi',
                                'thumbnail' => 'movie-thumb/yoShi.webp',
                                'author' => 'Jenny',
                                'date' => '2024-12-23 11:41:16',
                                'views' => '775 views',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'Giikre',
                                'thumbnail' => 'movie-thumb/giirek.webp',
                                'author' => 'Jenny',
                                'date' => '2024-12-23 11:40:57',
                                'views' => '1463 views',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'Kung Fu Panda',
                                'thumbnail' => 'movie-thumb/kung-fu-panda.webp',
                                'author' => 'Jenny',
                                'date' => '2024-12-23 11:40:25',
                                'views' => '758 views',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'War For The Planet',
                                'thumbnail' => 'movie-thumb/war-for-the-planet.webp',
                                'author' => 'Jenny',
                                'date' => '2024-12-23 11:37:27',
                                'views' => '533 views',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'Chosfies',
                                'thumbnail' => 'movie-thumb/chosfies.webp',
                                'author' => 'Jenny',
                                'date' => '2025-05-23 08:25:36',
                                'views' => '5028 views',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'The Hunter',
                                'thumbnail' => 'movie-thumb/the-hunter.webp',
                                'author' => 'Jenny',
                                'date' => '2025-07-09 10:38:07',
                                'views' => '2345 views',
                            ])
                        </tbody>

                    </table>
                </div>
            </div>

            <!-- Add Offcanvas Form -->
            <div class="offcanvas offcanvas-end offcanvas-width-80" tabindex="-1" id="season-offcanvas">
                <div class="offcanvas-header">
                    <h2 class="episode-playlist-title wp-heading-inline">
                        <span class="position-relative ">
                            {{ __('movielist.add_movie') }} </span>
                    </h2>
                    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"
                        aria-label="Close"></button>
                </div>
                <div class="offcanvas-body">
                    <form action="">
                        <div class="section-form">
                            <div class="row">
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label class="form-label flex-grow-1" for="genres">
                                            {{ __('form.title') }}
                                        </label>
                                        <input class="form-control" type="text"
                                            placeholder="{{ __('form.enter-title-movie') }}" data-id="multiple">
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label class="form-label flex-grow-1" for="author">{{ __('form.author') }}</label>
                                        <select id="author" class="form-control select2-basic">
                                            <option>admin</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label class="form-label flex-grow-1" for="status">{{ __('form.status') }}</label>
                                        <select id="status" class="form-control select2-basic">
                                            <option>Published</option>
                                            <option>Draft</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-5">
                                <label class="form-label flex-grow-1">{{ __('form.content') }}</label>
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
                                            {{ __('form.excerpt') }}
                                        </label>
                                        <textarea id="Description" class="form-control" rows="7" placeholder="{{ __('form.enter-encerpt-movie') }}"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="card streamit-tabs-card">
                                <div class="card-body">
                                    <div class="row gy-4">
                                        <div class="col-lg-3">
                                            <div class="streamit-verticle-tab">
                                                <div class="nav flex-column nav-pills me-0 me-lg-3 mb-3 mb-md-0 list-inline streamit-tabs"
                                                    role="tablist" aria-orientation="vertical">

                                                    <button class="nav-link active" data-bs-toggle="pill" type="button"
                                                        data-bs-target="#general_tab" role="tab" aria-selected="true">
                                                        <span>{{ __('form.general') }}</span>
                                                    </button>
                                                    <button class="nav-link" data-bs-toggle="pill" type="button"
                                                        data-bs-target="#recommendedMovies_tab" role="tab"
                                                        aria-selected="false">
                                                        <span>{{ __('form.recommended_movies') }}</span>
                                                    </button>
                                                    <button class="nav-link" data-bs-toggle="pill" type="button"
                                                        data-bs-target="#relatedVideos_tab" role="tab"
                                                        aria-selected="false">
                                                        <span>{{ __('form.related_videos') }}</span>
                                                    </button>
                                                    <button class="nav-link" data-bs-toggle="pill" type="button"
                                                        data-bs-target="#cast_tab" role="tab" aria-selected="false">
                                                        <span>{{ __('form.cast') }}</span>
                                                    </button>
                                                    <button class="nav-link" data-bs-toggle="pill" type="button"
                                                        data-bs-target="#crew_tab" role="tab" aria-selected="false">
                                                        <span>{{ __('form.crew') }}</span>
                                                    </button>
                                                    <button class="nav-link" data-bs-toggle="pill" type="button"
                                                        data-bs-target="#sources_tab" role="tab"
                                                        aria-selected="false">
                                                        <span>{{ __('form.sources') }}</span>
                                                    </button>
                                                    <button class="nav-link" data-bs-toggle="pill" type="button"
                                                        data-bs-target="#genres_tab" role="tab"
                                                        aria-selected="false">
                                                        <span>{{ __('streamTag.genre') }}</span>
                                                    </button>
                                                    <button class="nav-link" data-bs-toggle="pill" type="button"
                                                        data-bs-target="#tags_tab" role="tab" aria-selected="false">
                                                        <span>{{ __('form.tags') }}</span>
                                                    </button>
                                                    <button class="nav-link" data-bs-toggle="pill" type="button"
                                                        data-bs-target="#additionData_tab" role="tab"
                                                        aria-selected="true">
                                                        <span>{{ __('form.additional_data') }}</span>
                                                    </button>
                                                    <button class="nav-link" data-bs-toggle="pill" type="button"
                                                        data-bs-target="#membershipLevel_tab" role="tab"
                                                        aria-selected="false">
                                                        <span>{{ __('form.membership-level') }}</span>
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
                                                                    <label class="form-label flex-grow-1"
                                                                        for="movieMethod">{{ __('form.choose-movie-method') }}</label>
                                                                    <select id="movieMethod"
                                                                        class="form-control select2-basic-multiple">
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
                                                                        'upload_image_name' => __(
                                                                            'form.upload-movie'),
                                                                        'isRequiredStar' => true,
                                                                    ]
                                                                )
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-lg-6">
                                                                <div class="form-group">
                                                                    <label class="form-label flex-grow-1" for="genres">
                                                                        {{ __('form.movie-release-date') }}
                                                                    </label>
                                                                    <input class="form-control flatpickr_humandate"
                                                                        type="text"
                                                                        placeholder="{{ __('form.enter-the-release-date-movie') }}"
                                                                        data-id="multiple">
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-6">
                                                                <div class="row">
                                                                    <label
                                                                        class="form-label">{{ __('form.movie-time-duration') }}</label>
                                                                    <div class="col-lg-6">
                                                                        <div class="form-group">
                                                                            <input type="number" class="form-control"
                                                                                name="hours" value="0"
                                                                                placeholder="{{ __('form.hours') }}"
                                                                                min="0" aria-invalid="false">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-6">
                                                                        <div class="form-group">
                                                                            <input type="number" class="form-control"
                                                                                name="minutes" value="0"
                                                                                placeholder="{{ __('form.minutes') }}"
                                                                                min="0" max="59"
                                                                                aria-invalid="false">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-6">
                                                                <div class="form-group">
                                                                    <label class="form-label">{{__('form.movie-censor-rating')}}</label>
                                                                    <input type="text" name="_movie_censor_rating"
                                                                        id="_movie_censor_rating" class="form-control"
                                                                        placeholder="{{__('form.enter-the-movie-censor-rating')}}">
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-6">
                                                                <div class="form-group">
                                                                    <label class="form-label flex-grow-1"
                                                                        for="genres">{{__('form.language')}}</label>
                                                                    <select id="genres"
                                                                        class="form-control select2-basic-multiple"
                                                                        multiple>
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
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="tab-pane fade" id="recommendedMovies_tab" role="tabpanel">
                                                    <div class="p-4 px-2">
                                                        <div class="form-group mb-0">
                                                            <label class="form-label flex-grow-1"
                                                                for="recommended_movies">{{__('form.recommended_movies')}}</label>
                                                            <select id="recommended_movies"
                                                                class="form-control select2-basic-multiple" multiple>
                                                                <option>Pushpa </option>
                                                                <option>The Conjuring </option>
                                                                <option>Giikre </option>
                                                                <option>Another Danger </option>
                                                                <option>Kung Fu Panda </option>
                                                                <option>John Wick </option>
                                                                <option>Spiderman </option>
                                                                <option>War For The Planet </option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="tab-pane fade" id="relatedVideos_tab" role="tabpanel">
                                                    <div class="p-4 px-2">
                                                        <div class="form-group mb-0">
                                                            <label class="form-label flex-grow-1"
                                                                for="related_videos">{{__('form.related_videos')}}</label>
                                                            <select id="related_videos"
                                                                class="form-control select2-basic-multiple" multiple>
                                                                <option>Little Krishna </option>
                                                                <option>Red Dog </option>
                                                                <option>Frozen </option>
                                                                <option>Titanic </option>
                                                                <option>Adventure </option>
                                                                <option>Kung Fu Panda </option>
                                                                <option>Red </option>
                                                                <option>The Crew </option>
                                                                <option>Synchronic </option>
                                                                <option>Lost In Space </option>
                                                                <option>The Dragon King </option>
                                                                <option>Sonil </option>
                                                                <option>Guilty </option>
                                                                <option>Toddler </option>
                                                                <option>Charlie Chaplin </option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="tab-pane fade" id="cast_tab" role="tabpanel">
                                                    <div class="p-4 px-2">
                                                        <div class="row">
                                                            <div class="col-lg-6">
                                                                <div class="form-group mb-0">
                                                                    <label class="form-label flex-grow-1"
                                                                        for="choose_cast">{{__('form.choose-cast')}}</label>
                                                                    <div class="d-flex gap-2">
                                                                        <select id="choose_cast"
                                                                            class="form-control select2-basic-multiple">
                                                                            <option>Select Cast </option>
                                                                            <option>Ava Monroe </option>
                                                                            <option>Charles Melton </option>
                                                                            <option>Jack Nicholson </option>
                                                                            <option>James Stewart </option>
                                                                            <option>Jeff Bridges </option>
                                                                            <option>Jordan Grant </option>
                                                                            <option>Mark Smith </option>
                                                                            <option>Maxwell Carter </option>
                                                                            <option>Ruby Scott </option>
                                                                            <option>Michael Fox </option>
                                                                            <option>Ryan Pierce </option>
                                                                            <option>John Abraham </option>
                                                                            <option>Leena Burton </option>
                                                                            <option>Davina Decorous </option>
                                                                            <option>Thomas Bailey </option>
                                                                            <option>Kelly Reilly </option>
                                                                            <option>Smith Jonas </option>
                                                                            <option>Angel Louis </option>
                                                                            <option>Millar Joseph </option>
                                                                            <option>Mark Wilson </option>
                                                                            <option>Jack Walker </option>
                                                                            <option>Benjamin Hayes </option>
                                                                            <option>Olivia Foster </option>
                                                                            <option>Ethan Crawford </option>
                                                                            <option>Oliver Turner </option>
                                                                            <option>Maria Rodriguez </option>
                                                                        </select>
                                                                        <button type="button"
                                                                            class="btn btn-primary">{{__('form.add-btn')}}</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="tab-pane fade" id="crew_tab" role="tabpanel">
                                                    <div class="p-4 px-2">
                                                        <div class="row">
                                                            <div class="col-lg-6">
                                                                <div class="form-group mb-0">
                                                                    <label class="form-label flex-grow-1"
                                                                        for="choose_crew">{{__('form.choose-crew-member')}}</label>
                                                                    <div class="d-flex gap-2">
                                                                        <select id="choose_crew"
                                                                            class="form-control select2-basic-multiple">
                                                                            <option>Select Crew </option>
                                                                            <option>Ava Monroe </option>
                                                                            <option>Charles Melton </option>
                                                                            <option>Jack Nicholson </option>
                                                                            <option>James Stewart </option>
                                                                            <option>Jeff Bridges </option>
                                                                            <option>Jordan Grant </option>
                                                                            <option>Mark Smith </option>
                                                                            <option>Maxwell Carter </option>
                                                                            <option>Ruby Scott </option>
                                                                            <option>Michael Fox </option>
                                                                            <option>Ryan Pierce </option>
                                                                            <option>John Abraham </option>
                                                                            <option>Leena Burton </option>
                                                                            <option>Davina Decorous </option>
                                                                            <option>Thomas Bailey </option>
                                                                            <option>Kelly Reilly </option>
                                                                            <option>Smith Jonas </option>
                                                                            <option>Angel Louis </option>
                                                                            <option>Millar Joseph </option>
                                                                            <option>Mark Wilson </option>
                                                                            <option>Jack Walker </option>
                                                                            <option>Benjamin Hayes </option>
                                                                            <option>Olivia Foster </option>
                                                                            <option>Ethan Crawford </option>
                                                                            <option>Oliver Turner </option>
                                                                            <option>Maria Rodriguez </option>
                                                                        </select>
                                                                        <button type="button"
                                                                            class="btn btn-primary">{{__('form.add-btn')}}</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="tab-pane fade" id="sources_tab" role="tabpanel">
                                                    <div class="p-4 px-2">
                                                        <!-- Add Source Button -->
                                                        <button id="add-movie-source" class="btn btn-primary">{{__('form.add-source')}}</button>
                                                        <div id="source-container" class="source-container">
                                                            <!-- Dynamically added sources will appear here -->
                                                        </div>
                                                        <div id="source-data-template" style="display: none;">
                                                            <div id="source-data-container"
                                                                class="streamit_source_data streamit-metabox-content pt-3 mt-3 pb-3 d-none">
                                                                <div class="row">
                                                                    <div class="col-lg-6">
                                                                        <div class="form-group">
                                                                            <label class="form-label flex-grow-1"
                                                                                for="genres">
                                                                                {{__('form.name')}}
                                                                            </label>
                                                                            <input type="text" class="form-control"
                                                                                name="name"
                                                                                placeholder="{{__('form.enter-source-name-movie')}}">
                                                                        </div>
                                                                        <div class="form-group">
                                                                            <label class="form-label flex-grow-1"
                                                                                for="genres">
                                                                                {{__('form.quality')}}
                                                                            </label>
                                                                            <input type="text" class="form-control"
                                                                                name="quality"
                                                                                placeholder="{{__('form.enter-source-quality-movie')}}">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-6">
                                                                        <div class="form-group">
                                                                            <label class="form-label flex-grow-1"
                                                                                for="genres">
                                                                                {{__('form.choose_method')}}
                                                                            </label>
                                                                            <select class="form-control"
                                                                                name="Choose Method">
                                                                                <option>Embed Movie</option>
                                                                                <option>Movie URL</option>
                                                                            </select>
                                                                        </div>
                                                                        <div class="form-group">
                                                                            <label class="form-label flex-grow-1"
                                                                                for="Description1">
                                                                                {{__('form.embed-movie')}}
                                                                            </label>
                                                                            <textarea id="Description1" class="form-control" rows="7" placeholder="{{__('form.enter-the-embed-content-movie')}}"></textarea>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-lg-6">
                                                                        <div class="form-group">
                                                                            <label class="form-label flex-grow-1"
                                                                                for="genres">
                                                                                {{__('form.language')}}
                                                                            </label>
                                                                            <input type="text" class="form-control"
                                                                                name="Language"
                                                                                placeholder="{{__('form.enter-source-name-movie')}}">
                                                                        </div>
                                                                        <div class="form-group">
                                                                            <label class="form-label flex-grow-1"
                                                                                for="genres">
                                                                                {{__('form.date_added')}}
                                                                            </label>
                                                                            <input type="text" class="form-control"
                                                                                name="Date Added"
                                                                                placeholder="{{__('form.enter-source-quality-movie')}}">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-6">
                                                                        <div class="form-group">
                                                                            <label class="form-label flex-grow-1"
                                                                                for="genres">
                                                                                {{__('form.player')}}
                                                                            </label>
                                                                            <input type="text" class="form-control"
                                                                                name="Player"
                                                                                placeholder="{{__('form.enter-source-quality-movie')}}">
                                                                        </div>
                                                                        <div class="form-group">
                                                                            <label class="form-label flex-grow-1"
                                                                                for="genres">
                                                                                {{__('form.download_url')}}
                                                                            </label>
                                                                            <input type="text" class="form-control"
                                                                                name="Download URL"
                                                                                placeholder="{{__('form.enter-source-quality-movie')}}">
                                                                        </div>
                                                                    </div>

                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="tab-pane fade" id="genres_tab" role="tabpanel">
                                                    <div class="p-4 px-2">
                                                        <div class="form-group">
                                                            <label class="form-label flex-grow-1"
                                                                for="choice_genres">{{__('form.choose-movie-genres')}}</label>
                                                            <select id="choice_genres"
                                                                class="form-control select2-basic-multiple" multiple>
                                                                <option>Action </option>
                                                                <option>Adventure </option>
                                                                <option>Animation </option>
                                                                <option>Crime </option>
                                                                <option>Horror </option>
                                                                <option>Mystery </option>
                                                                <option>Romance </option>
                                                                <option>Action moview </option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="tab-pane fade" id="tags_tab" role="tabpanel">
                                                    <div class="p-4 px-2">
                                                        <div class="form-group">
                                                            <label class="form-label flex-grow-1" for="choice_tags">{{__('form.choose-movie-tags')}}</label>
                                                            <select id="choice_tags"
                                                                class="form-control select2-basic-multiple" multiple>
                                                                <option>Action </option>
                                                                <option>Adventure </option>
                                                                <option>Animation </option>
                                                                <option>Assassin </option>
                                                                <option>Comedy </option>
                                                                <option>Drama </option>
                                                                <option>Family </option>
                                                                <option>Fantasy </option>
                                                                <option>History </option>
                                                                <option>Hitman </option>
                                                                <option>Horror </option>
                                                                <option>Mystery </option>
                                                                <option>Recommended </option>
                                                                <option>Revenge </option>
                                                                <option>Sci-Fi </option>
                                                                <option>Thriller </option>
                                                                <option>Animated Tag </option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="tab-pane fade" id="additionData_tab" role="tabpanel">
                                                    <div class="p-4 px-2">
                                                        <div class="row">
                                                            <div class="col-lg-6">
                                                                <div class="form-group">
                                                                    <label class="form-label flex-grow-1" for="genres">
                                                                        {{__('form.trailer-link')}}
                                                                            </label>
                                                                    <input type="text" class="form-control"
                                                                        name="Download URL"
                                                                        placeholder="{{__('form.enter-trailer-link')}}">
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-6">
                                                                <label class="d-block form-label">{{__('form.upcoming')}}</label>
                                                                <div class="checkbox mb-3">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox"
                                                                            id="flexCheckDefault3">
                                                                        <label class="form-check-label"
                                                                            for="flexCheckDefault3">
                                                                            {{__('form.this-is-upcoming-movie')}}</label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-6">
                                                                @include(
                                                                    'components.widget.UploadImageVideo',
                                                                    [
                                                                        'upload_image_name' => __('form.upload-potrait-image'),
                                                                    ]
                                                                )
                                                            </div>
                                                            <div class="col-lg-6">
                                                                <div class="form-group">
                                                                    <label class="form-label" for="imdb_rating">{{__('form.enter_imdb_rating')}}</label>
                                                                    <input type="number" id="imdb_rating"
                                                                        class="form-control" value="0"
                                                                        aria-invalid="false">
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-6">
                                                                <div class="form-group">
                                                                    <label class="form-label flex-grow-1"
                                                                        for="select_related_product">{{__('form.select-related-product')}}</label>
                                                                    <select id="select_related_product"
                                                                        class="form-control select2-basic-multiple"
                                                                        multiple>
                                                                        <option>WordPress Pennant </option>
                                                                        <option>Logo Collection </option>
                                                                        <option>
                                                                            Beanie with Logo </option>
                                                                        <option>T-Shirt with Logo </option>
                                                                        <option>Single </option>
                                                                        <option>Album </option>
                                                                        <option>Polo </option>
                                                                        <option>Long Sleeve Tee </option>
                                                                        <option>Hoodie with Zipper </option>
                                                                        <option>Hoodie with Pocket </option>
                                                                        <option>Sunglasses </option>
                                                                        <option>Cap </option>
                                                                        <option>Belt </option>
                                                                        <option>Beanie </option>
                                                                        <option>T-Shirt </option>
                                                                        <option>Hoodie with Logo </option>
                                                                        <option>Hoodie </option>
                                                                        <option>V-Neck T-Shirt </option>
                                                                        <option>Round Neck T-Shirt </option>
                                                                        <option>Magic Hat </option>
                                                                        <option>Spider Cover </option>
                                                                        <option>Sleeping Mask </option>
                                                                        <option>Phone Cover </option>
                                                                        <option>Black Bow </option>
                                                                        <option>Coffee Cup </option>
                                                                        <option>Boxing Gloves </option>
                                                                        <option>Crown </option>
                                                                        <option>Bag Pack </option>
                                                                        <option>Vehicle Keychain </option>
                                                                        <option>Green Specs </option>
                                                                        <option>Cartoon Character </option>
                                                                        <option>Red &amp; Black T-Shirt </option>
                                                                        <option>Logo T-Shirt </option>
                                                                        <option>Believe Mask </option>
                                                                        <option>Keychain </option>
                                                                        <option>Socks </option>
                                                                        <option>Harry T-Shirt </option>
                                                                        <option>Sunglasses </option>
                                                                        <option>Black Cap </option>
                                                                        <option>Round Badges </option>
                                                                        <option>Carry Bag </option>
                                                                        <option>Glossy Mug </option>
                                                                        <option>Spider Pillow logo </option>
                                                                        <option>Daily Diary </option>
                                                                        <option>Floral Badges </option>
                                                                    </select>
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
                                                                            {{__('form.this-is-upcoming-movie')}}</label>
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

            <!-- edit canvas -->
            <div class="offcanvas offcanvas-end offcanvas-width-80" tabindex="-1" id="season-offcanvas-edit">
                <div class="offcanvas-header">
                    <h2 class="episode-playlist-title wp-heading-inline">
                        <span class="position-relative ">
                           {{__('movielist.edit_movie')}} </span>
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
                                        <input class="form-control" type="text" value="{{__('form.arrival_1999')}}"
                                            data-id="multiple">
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label class="form-label flex-grow-1" for="author1">{{__('form.author')}}</label>
                                        <select id="author1" class="form-control select2-basic-multiple">
                                            <option>admin</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label class="form-label flex-grow-1"
                                            for="status1">{{ __('form.status') }}</label>
                                        <select id="status1" class="form-control select2-basic-multiple">
                                            <option selected="">Published</option>
                                            <option>Draft</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-5">
                                <label class="form-label flex-grow-1">{{ __('form.content') }}</label>
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
                                        <label class="form-label flex-grow-1" for="Description2">
                                            {{__('form.excerpt')}}</label>
                                        <textarea id="Description2" class="form-control" rows="7" placeholder="{{ __('form.enter-encerpt-movie') }}"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="card streamit-tabs-card">
                                <div class="card-body">
                                    <div class="row gy-4">
                                        <div class="col-lg-3">
                                            <div class="streamit-verticle-tab">
                                                <div class="nav flex-column nav-pills me-0 me-lg-3 mb-3 mb-md-0 list-inline streamit-tabs"
                                                    role="tablist" aria-orientation="vertical">

                                                    <button class="nav-link active" data-bs-toggle="pill" type="button"
                                                        data-bs-target="#general_tab1" role="tab"
                                                        aria-selected="true">
                                                        <span>{{ __('form.general') }}</span>
                                                    </button>
                                                    <button class="nav-link" data-bs-toggle="pill" type="button"
                                                        data-bs-target="#recommendedMovies_tab1" role="tab"
                                                        aria-selected="false">
                                                        <span>{{ __('form.recommended_movies') }}</span>
                                                    </button>
                                                    <button class="nav-link" data-bs-toggle="pill" type="button"
                                                        data-bs-target="#relatedVideos_tab1" role="tab"
                                                        aria-selected="false">
                                                        <span>{{ __('form.related_videos') }}</span>
                                                    </button>
                                                    <button class="nav-link" data-bs-toggle="pill" type="button"
                                                        data-bs-target="#cast_tab1" role="tab" aria-selected="false">
                                                        <span>{{ __('form.cast') }}</span>
                                                    </button>
                                                    <button class="nav-link" data-bs-toggle="pill" type="button"
                                                        data-bs-target="#crew_tab1" role="tab" aria-selected="false">
                                                        <span>{{ __('form.crew') }}</span>
                                                    </button>
                                                    <button class="nav-link" data-bs-toggle="pill" type="button"
                                                        data-bs-target="#sources_tab1" role="tab"
                                                        aria-selected="false">
                                                        <span>{{ __('form.sources') }}</span>
                                                    </button>
                                                    <button class="nav-link" data-bs-toggle="pill" type="button"
                                                        data-bs-target="#genres_tab1" role="tab"
                                                        aria-selected="false">
                                                        <span>{{ __('streamTag.genre') }}</span>
                                                    </button>
                                                    <button class="nav-link" data-bs-toggle="pill" type="button"
                                                        data-bs-target="#tags_tab1" role="tab" aria-selected="false">
                                                        <span>{{ __('form.tags') }}</span>
                                                    </button>
                                                    <button class="nav-link" data-bs-toggle="pill" type="button"
                                                        data-bs-target="#additionData_tab1" role="tab"
                                                        aria-selected="true">
                                                        <span>{{ __('form.additional_data') }}</span>
                                                    </button>
                                                    <button class="nav-link" data-bs-toggle="pill" type="button"
                                                        data-bs-target="#membershipLevel_tab1" role="tab"
                                                        aria-selected="false">
                                                        <span>{{ __('form.membership-level') }}</span>
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
                                                                        for="movieMethod1">{{ __('form.choose-movie-method') }}</label>
                                                                    <select id="movieMethod1"
                                                                        class="form-control select2-basic-multiple">
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
                                                                        'upload_image_name' => __(
                                                                            'form.upload-movie'),
                                                                        'isUploadImageDefault' => true,
                                                                    ]
                                                                )
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-lg-6">
                                                                <div class="form-group">
                                                                    <label class="form-label flex-grow-1" for="genres">
                                                                        {{ __('form.movie-release-date') }}
                                                                    </label>
                                                                    <input class="form-control flatpickr_humandate_default"
                                                                        type="text"
                                                                        placeholder="{{ __('form.enter-the-release-date-movie') }}"
                                                                        data-id="multiple">
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-6">
                                                                <div class="row">
                                                                    <label
                                                                        class="form-label">{{ __('form.movie-time-duration') }}</label>
                                                                    <div class="col-lg-6">
                                                                        <div class="form-group">
                                                                            <input type="number" class="form-control"
                                                                                name="hours" value="2"
                                                                                placeholder="{{ __('form.hours') }}"
                                                                                min="0" aria-invalid="false">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-6">
                                                                        <div class="form-group">
                                                                            <input type="number" class="form-control"
                                                                                name="minutes" value="30"
                                                                                placeholder="{{ __('form.minutes') }}"
                                                                                min="0" max="59"
                                                                                aria-invalid="false">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-6">
                                                                <div class="form-group">
                                                                    <label class="form-label">{{__('form.movie-censor-rating')}}</label>
                                                                    <input type="text" name="_movie_censor_rating"
                                                                        id="_movie_censor_rating" value="U"
                                                                        class="form-control"
                                                                        placeholder="{{__('form.enter-the-movie-censor-rating')}}">
                                                                </div>
                                                            </div>
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
                                                                        <option>
                                                                            ગુજરાતી </option>
                                                                        <option>
                                                                            هزاره گی </option>
                                                                        <option>
                                                                            עִבְרִית </option>
                                                                        <option selected="">
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
                                                                <option selected="">Pushpa </option>
                                                                <option>The Conjuring </option>
                                                                <option>Giikre </option>
                                                                <option>Another Danger </option>
                                                                <option>Kung Fu Panda </option>
                                                                <option>John Wick </option>
                                                                <option>Spiderman </option>
                                                                <option>War For The Planet </option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="tab-pane fade" id="relatedVideos_tab1" role="tabpanel">
                                                    <div class="p-4 px-2">
                                                        <div class="form-group mb-0">
                                                            <label class="form-label flex-grow-1"
                                                                for="related_videos1">{{__('form.related_videos')}}</label>
                                                            <select id="related_videos1"
                                                                class="form-control select2-basic-multiple" multiple>
                                                                <option selected="">Little Krishna </option>
                                                                <option>Red Dog </option>
                                                                <option>Frozen </option>
                                                                <option>Titanic </option>
                                                                <option>Adventure </option>
                                                                <option>Kung Fu Panda </option>
                                                                <option>Red </option>
                                                                <option>The Crew </option>
                                                                <option>Synchronic </option>
                                                                <option>Lost In Space </option>
                                                                <option>The Dragon King </option>
                                                                <option>Sonil </option>
                                                                <option>Guilty </option>
                                                                <option>Toddler </option>
                                                                <option>Charlie Chaplin </option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="tab-pane fade" id="cast_tab1" role="tabpanel">
                                                    <div class="p-4 px-2">
                                                        <div class="row">
                                                            <div class="col-lg-6">
                                                                <div class="form-group mb-0">
                                                                    <label class="form-label flex-grow-1"
                                                                        for="choose_cast1">{{__('form.choose-cast')}}</label>
                                                                    <div class="d-flex gap-2">
                                                                        <select id="choose_cast1"
                                                                            class="form-control select2-basic-multiple">
                                                                            <option>Select Cast </option>
                                                                            <option>Ava Monroe </option>
                                                                            <option>Charles Melton </option>
                                                                            <option>Jack Nicholson </option>
                                                                            <option>James Stewart </option>
                                                                            <option>Jeff Bridges </option>
                                                                            <option>Jordan Grant </option>
                                                                            <option>Mark Smith </option>
                                                                            <option>Maxwell Carter </option>
                                                                            <option>Ruby Scott </option>
                                                                            <option>Michael Fox </option>
                                                                            <option>Ryan Pierce </option>
                                                                            <option>John Abraham </option>
                                                                            <option>Leena Burton </option>
                                                                            <option>Davina Decorous </option>
                                                                            <option>Thomas Bailey </option>
                                                                            <option>Kelly Reilly </option>
                                                                            <option>Smith Jonas </option>
                                                                            <option>Angel Louis </option>
                                                                            <option>Millar Joseph </option>
                                                                            <option>Mark Wilson </option>
                                                                            <option>Jack Walker </option>
                                                                            <option>Benjamin Hayes </option>
                                                                            <option>Olivia Foster </option>
                                                                            <option>Ethan Crawford </option>
                                                                            <option>Oliver Turner </option>
                                                                            <option>Maria Rodriguez </option>
                                                                        </select>
                                                                        <button type="button"
                                                                            class="btn btn-primary">{{__('form.add-btn')}}</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="tab-pane fade" id="crew_tab1" role="tabpanel">
                                                    <div class="p-4 px-2">
                                                        <div class="row">
                                                            <div class="col-lg-6">
                                                                <div class="form-group mb-0">
                                                                    <label class="form-label flex-grow-1"
                                                                        for="choose_crew1">{{__('form.choose-crew-member')}}</label>
                                                                    <div class="d-flex gap-2">
                                                                        <select id="choose_crew1"
                                                                            class="form-control select2-basic-multiple">
                                                                            <option>Select Crew </option>
                                                                            <option>Ava Monroe </option>
                                                                            <option>Charles Melton </option>
                                                                            <option>Jack Nicholson </option>
                                                                            <option>James Stewart </option>
                                                                            <option>Jeff Bridges </option>
                                                                            <option>Jordan Grant </option>
                                                                            <option>Mark Smith </option>
                                                                            <option>Maxwell Carter </option>
                                                                            <option>Ruby Scott </option>
                                                                            <option>Michael Fox </option>
                                                                            <option>Ryan Pierce </option>
                                                                            <option>John Abraham </option>
                                                                            <option>Leena Burton </option>
                                                                            <option>Davina Decorous </option>
                                                                            <option>Thomas Bailey </option>
                                                                            <option>Kelly Reilly </option>
                                                                            <option>Smith Jonas </option>
                                                                            <option>Angel Louis </option>
                                                                            <option>Millar Joseph </option>
                                                                            <option>Mark Wilson </option>
                                                                            <option>Jack Walker </option>
                                                                            <option>Benjamin Hayes </option>
                                                                            <option>Olivia Foster </option>
                                                                            <option>Ethan Crawford </option>
                                                                            <option>Oliver Turner </option>
                                                                            <option>Maria Rodriguez </option>
                                                                        </select>
                                                                        <button type="button"
                                                                            class="btn btn-primary">{{__('form.add-btn')}}</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="tab-pane fade" id="sources_tab1" role="tabpanel">
                                                    <div class="p-4 px-2">
                                                        <!-- Add Source Button -->
                                                        <button id="add-movie-source1" class="btn btn-primary">{{__('form.add-source')}}</button>
                                                        <div id="source-container1" class="source-container">
                                                            <!-- Dynamically added sources will appear here -->
                                                        </div>
                                                        <div id="source-data-template1" style="display: none;">
                                                            <div id="source-data-container"
                                                                class="streamit_source_data streamit-metabox-content pt-3 mt-3 pb-3 d-none">
                                                                <div class="row">
                                                                    <div class="col-lg-6">
                                                                        <div class="form-group">
                                                                            <label class="form-label flex-grow-1"
                                                                                for="genres">
                                                                                {{__('form.name')}}
                                                                            </label>
                                                                            <input type="text" class="form-control"
                                                                                name="name"
                                                                                placeholder="{{__('form.enter-source-name-movie')}}">
                                                                        </div>
                                                                        <div class="form-group">
                                                                            <label class="form-label flex-grow-1"
                                                                                for="genres">
                                                                                {{__('form.quality')}}
                                                                            </label>
                                                                            <input type="text" class="form-control"
                                                                                name="quality"
                                                                                placeholder="{{__('form.enter-source-quality-movie')}}">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-6">
                                                                        <div class="form-group">
                                                                            <label class="form-label flex-grow-1"
                                                                                for="genres">
                                                                                {{__('form.choose_method')}}
                                                                            </label>
                                                                            <select class="form-control"
                                                                                name="Choose Method">
                                                                                <option>Embed Movie</option>
                                                                                <option>Movie URL</option>
                                                                            </select>
                                                                        </div>
                                                                        <div class="form-group">
                                                                            <label class="form-label flex-grow-1"
                                                                                for="Description3">
                                                                                {{__('form.embed-movie')}}
                                                                            </label>
                                                                            <textarea id="Description3" class="form-control" rows="7" placeholder="{{__('form.enter-the-embed-content-movie')}}"></textarea>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-lg-6">
                                                                        <div class="form-group">
                                                                            <label class="form-label flex-grow-1"
                                                                                for="genres">
                                                                                {{__('form.language')}}
                                                                            </label>
                                                                            <input type="text" class="form-control"
                                                                                name="Language"
                                                                                placeholder="{{__('form.enter-source-name-movie')}}">
                                                                        </div>
                                                                        <div class="form-group">
                                                                            <label class="form-label flex-grow-1"
                                                                                for="genres">
                                                                                {{__('form.date_added')}}
                                                                            </label>
                                                                            <input type="text" class="form-control"
                                                                                name="Date Added"
                                                                                placeholder="{{__('form.enter-source-quality-movie')}}">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-6">
                                                                        <div class="form-group">
                                                                            <label class="form-label flex-grow-1"
                                                                                for="genres">
                                                                                {{__('form.player')}}
                                                                            </label>
                                                                            <input type="text" class="form-control"
                                                                                name="Player"
                                                                                placeholder="{{__('form.enter-source-quality-movie')}}">
                                                                        </div>
                                                                        <div class="form-group">
                                                                            <label class="form-label flex-grow-1"
                                                                                for="genres">
                                                                                {{__('form.download_url')}}
                                                                            </label>
                                                                            <input type="text" class="form-control"
                                                                                name="Download URL"
                                                                                placeholder="{{__('form.enter-source-quality-movie')}}">
                                                                        </div>
                                                                    </div>

                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="tab-pane fade" id="genres_tab1" role="tabpanel">
                                                    <div class="p-4 px-2">
                                                        <div class="form-group">
                                                            <label class="form-label flex-grow-1"
                                                                for="choice_genres1">{{__('form.choose-movie-genres')}}</label>
                                                            <select id="choice_genres1"
                                                                class="form-control select2-basic-multiple" multiple>
                                                                <option selected="">Action </option>
                                                                <option>Adventure </option>
                                                                <option>Animation </option>
                                                                <option>Crime </option>
                                                                <option>Horror </option>
                                                                <option>Mystery </option>
                                                                <option>Romance </option>
                                                                <option>Action moview </option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="tab-pane fade" id="tags_tab1" role="tabpanel">
                                                    <div class="p-4 px-2">
                                                        <div class="form-group">
                                                            <label class="form-label flex-grow-1"
                                                                for="choice_tags1">{{__('form.choose-movie-tags')}}</label>
                                                            <select id="choice_tags1"
                                                                class="form-control select2-basic-multiple" multiple>
                                                                <option>Action </option>
                                                                <option selected="">Adventure </option>
                                                                <option>Animation </option>
                                                                <option>Assassin </option>
                                                                <option>Comedy </option>
                                                                <option>Drama </option>
                                                                <option>Family </option>
                                                                <option>Fantasy </option>
                                                                <option>History </option>
                                                                <option>Hitman </option>
                                                                <option>Horror </option>
                                                                <option>Mystery </option>
                                                                <option>Recommended </option>
                                                                <option>Revenge </option>
                                                                <option>Sci-Fi </option>
                                                                <option>Thriller </option>
                                                                <option>Animated Tag </option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="tab-pane fade" id="additionData_tab1" role="tabpanel">
                                                    <div class="p-4 px-2">
                                                        <div class="row">
                                                            <div class="col-lg-6">
                                                                <div class="form-group">
                                                                    <label class="form-label flex-grow-1" for="genres">
                                                                        {{__('form.trailer-link')}}
                                                                            </label>
                                                                    <input type="text" class="form-control"
                                                                        name="Download URL"
                                                                        placeholder="{{__('form.enter-trailer-link')}}"
                                                                        value="https://www.youtube.com/">
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-6">
                                                                <label class="d-block form-label">{{__('form.upcoming')}}</label>
                                                                <div class="checkbox mb-3">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox"
                                                                            id="flexCheckDefault32">
                                                                        <label class="form-check-label"
                                                                            for="flexCheckDefault32">
                                                                            {{__('form.this-is-upcoming-movie')}}</label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-6">
                                                                @include(
                                                                    'components.widget.UploadImageVideo',
                                                                    [
                                                                        'upload_image_name' => __(
                                                                            'form.thumbnail'),
                                                                        'isUploadImageDefault' => true,
                                                                    ]
                                                                )
                                                            </div>
                                                            <div class="col-lg-6">
                                                                <div class="form-group">
                                                                    <label class="form-label" for="imdb_rating1">{{__('form.enter_imdb_rating')}}</label>
                                                                    <input type="number" id="imdb_rating1"
                                                                        class="form-control" value="5"
                                                                        aria-invalid="false">
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-6">
                                                                <div class="form-group">
                                                                    <label class="form-label flex-grow-1"
                                                                        for="select_related_product1">{{__('form.select-related-product')}}</label>
                                                                    <select id="select_related_product1"
                                                                        class="form-control select2-basic-multiple"
                                                                        multiple>
                                                                        <option>WordPress Pennant </option>
                                                                        <option>Logo Collection </option>
                                                                        <option>
                                                                            Beanie with Logo </option>
                                                                        <option selected="">T-Shirt with Logo </option>
                                                                        <option>Single </option>
                                                                        <option>Album </option>
                                                                        <option>Polo </option>
                                                                        <option>Long Sleeve Tee </option>
                                                                        <option>Hoodie with Zipper </option>
                                                                        <option>Hoodie with Pocket </option>
                                                                        <option>Sunglasses </option>
                                                                        <option>Cap </option>
                                                                        <option>Belt </option>
                                                                        <option>Beanie </option>
                                                                        <option>T-Shirt </option>
                                                                        <option>Hoodie with Logo </option>
                                                                        <option>Hoodie </option>
                                                                        <option>V-Neck T-Shirt </option>
                                                                        <option>Round Neck T-Shirt </option>
                                                                        <option>Magic Hat </option>
                                                                        <option>Spider Cover </option>
                                                                        <option>Sleeping Mask </option>
                                                                        <option>Phone Cover </option>
                                                                        <option>Black Bow </option>
                                                                        <option>Coffee Cup </option>
                                                                        <option>Boxing Gloves </option>
                                                                        <option>Crown </option>
                                                                        <option>Bag Pack </option>
                                                                        <option>Vehicle Keychain </option>
                                                                        <option>Green Specs </option>
                                                                        <option>Cartoon Character </option>
                                                                        <option>Red &amp; Black T-Shirt </option>
                                                                        <option>Logo T-Shirt </option>
                                                                        <option>Believe Mask </option>
                                                                        <option>Keychain </option>
                                                                        <option>Socks </option>
                                                                        <option>Harry T-Shirt </option>
                                                                        <option>Sunglasses </option>
                                                                        <option>Black Cap </option>
                                                                        <option>Round Badges </option>
                                                                        <option>Carry Bag </option>
                                                                        <option>Glossy Mug </option>
                                                                        <option>Spider Pillow logo </option>
                                                                        <option>Daily Diary </option>
                                                                        <option>Floral Badges </option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-6">
                                                                <label class="d-block form-label">{{__('form.upcoming')}}</label>
                                                                <div class="checkbox mb-3">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox"
                                                                            id="flexCheckDefault33">
                                                                        <label class="form-check-label"
                                                                            for="flexCheckDefault33">
                                                                            {{__('form.this-is-upcoming-movie')}}</label>
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
