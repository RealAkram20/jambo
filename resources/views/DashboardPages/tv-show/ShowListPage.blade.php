@extends('layouts.app', ['module_title' => 'Show List', 'isSweetalert' => true, 'isFlatpickr' => true, 'isSelect2' => true, 'Activelink' => 'Tvshow', 'isQuillEditor' => true])
@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="streamit-wraper-table">
                <div class="card-header  d-flex justify-content-between gap-3 flex-wrap align-items-center mb-4">
                    <h2 class="episode-playlist-title wp-heading-inline">
                        <span class="position-relative ">
                            {{__('form.tv-show-list')}} </span>
                    </h2>
                    <button class="btn btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#season-offcanvas"
                        aria-controls="season-offcanvas">
                        <i class="fa-solid fa-plus me-2"></i>{{ __('dashboard.Add_Show') }}
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
                                <th class="">Movie</th>
                                <th class="">Title</th>
                                <th class="">Author</th>
                                <th class="">Date and Time</th>
                                <th class="">Views</th>
                                <th class="">Status</th>
                                <th class="">Actions</th>
                            </tr>
                        </thead>
                        <tbody>

                            @include('components.datatable.DataTable', [
                                'title' => 'Awakening: The First Ones',
                                'thumbnail' => 'movie-thumb/the-first-of-us.webp',
                                'author' => 'jenny',
                                'date' => '2025-07-08 09:23:51',
                                'views' => '870 views',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'Beyond Borders',
                                'thumbnail' => 'movie-thumb/migration.webp',
                                'author' => 'jenny',
                                'date' => '2025-02-05 08:37:44',
                                'views' => '749 views',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'Titanic: Lost & Found',
                                'thumbnail' => 'movie-thumb/tianic.webp',
                                'author' => 'jenny',
                                'date' => '2025-04-16 11:42:13',
                                'views' => '729 views',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'The Real Crew',
                                'thumbnail' => 'movie-thumb/the-crew.webp',
                                'author' => 'jenny',
                                'date' => '2025-02-05 08:09:42',
                                'views' => '697 views',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'Midnight Hunter',
                                'thumbnail' => 'movie-thumb/midnightHunter.webp',
                                'author' => 'jenny',
                                'date' => '2025-02-04 10:57:08',
                                'views' => '366 views',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'Spider Sentinel',
                                'thumbnail' => 'movie-thumb/spiderman.webp',
                                'author' => 'jenny',
                                'date' => '2025-02-05 07:01:39',
                                'views' => '484 views',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'Masters of Kung Fu: The Panda Legend',
                                'thumbnail' => 'movie-thumb/kung-fu-panda.webp',
                                'author' => 'jenny',
                                'date' => '2024-12-26 08:14:16',
                                'views' => '508 views',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'The Epic Journey of Hanuman',
                                'thumbnail' => 'movie-thumb/hanuman.webp',
                                'author' => 'jenny',
                                'date' => '2024-12-26 11:54:20',
                                'views' => '318 views',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'Vasudeva: The Eternal Journey',
                                'thumbnail' => 'movie-thumb/krishna.webp',
                                'author' => 'jenny',
                                'date' => '2024-12-26 10:30:30',
                                'views' => '213 views',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'Minions',
                                'thumbnail' => 'movie-thumb/minions.webp',
                                'author' => 'jenny',
                                'date' => '2025-07-08 06:58:43',
                                'views' => '745 views',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'Pirates of Day One',
                                'thumbnail' => 'movie-thumb/pirates-ofdayones-orignal.webp',
                                'author' => 'jenny',
                                'date' => '2025-02-04 10:57:30',
                                'views' => '727 views',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'The Hunter',
                                'thumbnail' => 'movie-thumb/the-hunter.webp',
                                'author' => 'jenny',
                                'date' => '2025-07-09 10:38:07',
                                'views' => '2345 views',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'Lost In Space',
                                'thumbnail' => 'movie-thumb/lost-inst-space.webp',
                                'author' => 'jenny',
                                'date' => '2024-12-25 09:51:54',
                                'views' => '293 views',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'Castle rock',
                                'thumbnail' => 'movie-thumb/castle-rock.webp',
                                'author' => 'jenny',
                                'date' => '2024-12-25 09:52:11',
                                'views' => '199 views',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'Breaking Bad',
                                'thumbnail' => 'movie-thumb/breaking-bad.webp',
                                'author' => 'jenny',
                                'date' => '2025-07-08 07:07:52',
                                'views' => '601 views',
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
                        {{__('form.add-new-tv-show')}} </span>
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
                                            {{__('form.tv-show-title')}} <span> *</span>
                                        </label>
                                        <input class="form-control" type="text" placeholder="Enter title of the TVshow."
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
                                <label class="form-label flex-grow-1">{{__('form.embed-tv-show-content')}}</label>
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
                                        <textarea id="Description" class="form-control" rows="7" placeholder="{{__('form.enter-excerpt-content-tvshow')}}"></textarea>
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
                                                        <span>{{__('form.general')}}</span>
                                                    </button>
                                                    <button class="nav-link" data-bs-toggle="pill" type="button"
                                                        data-bs-target="#cast_tab" role="tab" aria-selected="false">
                                                        <span>{{__('form.cast')}}</span>
                                                    </button>
                                                    <button class="nav-link" data-bs-toggle="pill" type="button"
                                                        data-bs-target="#crew_tab" role="tab" aria-selected="false">
                                                        <span>{{__('form.crew')}}</span>
                                                    </button>
                                                    <button class="nav-link" data-bs-toggle="pill" type="button"
                                                        data-bs-target="#sources_tab" role="tab"
                                                        aria-selected="false">
                                                        <span>{{__('form.seasons&episodes')}}</span>
                                                    </button>
                                                    <button class="nav-link" data-bs-toggle="pill" type="button"
                                                        data-bs-target="#genres_tab" role="tab"
                                                        aria-selected="false">
                                                        <span>{{__('streamTag.genre')}}</span>
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
                                                                    <label class="form-label flex-grow-1"
                                                                        for="genres">{{__('form.language')}}</label>
                                                                    <select id="genres"
                                                                        class="form-control select2-basic-single">
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
                                                <div class="tab-pane fade" id="cast_tab" role="tabpanel">
                                                    <div class="p-4 px-2">
                                                        <div class="row align-items-end gy-2">
                                                            <div class="col-lg-6">
                                                                <div class="form-group mb-0">
                                                                    <label class="form-label flex-grow-1"
                                                                        for="choose_cast">{{__('form.choose-cast')}}</label>
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
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-2">
                                                                <button
                                                                    class="btn btn-primary  add-tvshow-cast">{{__('form.add-btn')}}</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="tab-pane fade" id="crew_tab" role="tabpanel">
                                                    <div class="p-4 px-2">
                                                        <div class="row align-items-end gy-2">
                                                            <div class="col-lg-6">
                                                                <div class="form-group mb-0">
                                                                    <label class="form-label flex-grow-1"
                                                                        for="choose_crew">{{__('form.choose-crew-member')}}</label>
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
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-2">
                                                                <button
                                                                    class="btn btn-primary  add-tvshow-cast">{{__('form.add-btn')}}</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="tab-pane fade" id="sources_tab" role="tabpanel">
                                                    <div class="p-4 px-2">
                                                        <!-- Add Source Button -->
                                                        <button id="add-movie-source" class="btn btn-primary">{{__('dashboard.Add_Seasaon')}}
                                                        </button>
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
                                                <div class="tab-pane fade" id="genres_tab" role="tabpanel">
                                                    <div class="p-4 px-2">
                                                        <div class="form-group">
                                                            <label class="form-label flex-grow-1"
                                                                for="choice_genres">{{__('form.choose-tv-show-genres')}}
                                                                            </label>
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
                                                            <label class="form-label flex-grow-1" for="choice_tags">{{__('form.choose-tv-show-tags')}}
                                                                            </label>
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
                                                                            {{__('form.this-is-upcoming-movie')}}
                                                                            </label>
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
                                                                        for="select-product">{{__('form.select-related-product')}}</label>
                                                                    <select id="select-product"
                                                                        class="form-control select2-basic-multiple"
                                                                        multiple>
                                                                        <option>Logog Collection </option>
                                                                        <option>Comedy </option>
                                                                        <option>Drama </option>
                                                                        <option>T-Shirt with Logo</option>
                                                                        <option>Cap </option>
                                                                        <option>Polo </option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-6">
                                                                <label class="d-block form-label">{{__('form.notification')}}</label>
                                                                <div class="checkbox mb-3">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox"
                                                                            id="flexCheckDefault31">
                                                                        <label class="form-check-label"
                                                                            for="flexCheckDefault31">
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

        </div>
    </div>
    <!-- Edit Form -->
    <div class="offcanvas offcanvas-end offcanvas-width-80" tabindex="-1" id="season-offcanvas-edit">
        <div class="offcanvas-header">
            <h2 class="episode-playlist-title wp-heading-inline">
                <span class="position-relative ">
                    {{__('form.edit-tv-show')}}</span>
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
                                    {{__('form.tv-show-title')}} <span> *</span>
                                </label>
                                <input class="form-control" type="text" value="{{__('form.admin')}}" data-id="multiple">
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
                                <label class="form-label flex-grow-1" for="status1">{{__('form.status')}}</label>
                                <select id="status1" class="form-control select2-basic-multiple">
                                    <option selected="">Published</option>
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
                                    {{__('form.excerpt')}}
                                </label>
                                <textarea id="Description1" class="form-control" rows="7" placeholder="{{__('form.enter-excerpt-content-tvshow')}}"></textarea>
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
                                                data-bs-target="#general_tab1" role="tab" aria-selected="true">
                                                <span>{{__('form.general')}}</span>
                                            </button>

                                            <button class="nav-link" data-bs-toggle="pill" type="button"
                                                data-bs-target="#cast_tab1" role="tab" aria-selected="false">
                                                <span>{{__('form.cast')}}</span>
                                            </button>


                                            <button class="nav-link" data-bs-toggle="pill" type="button"
                                                data-bs-target="#crew_tab1" role="tab" aria-selected="false">
                                                <span>{{__('form.crew')}}</span>
                                            </button>


                                            <button class="nav-link" data-bs-toggle="pill" type="button"
                                                data-bs-target="#sources_tab1" role="tab" aria-selected="false">
                                                <span>{{__('form.seasons&episodes')}}</span>
                                            </button>


                                            <button class="nav-link" data-bs-toggle="pill" type="button"
                                                data-bs-target="#genres_tab1" role="tab" aria-selected="false">
                                                <span>{{__('streamTag.genre')}}</span>
                                            </button>


                                            <button class="nav-link" data-bs-toggle="pill" type="button"
                                                data-bs-target="#tags_tab1" role="tab" aria-selected="false">
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
                                                                for="genres1">{{__('form.language')}}</label>
                                                            <select id="genres1"
                                                                class="form-control select2-basic-multiple" multiple>
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
                                        <div class="tab-pane fade" id="cast_tab1" role="tabpanel">
                                            <div class="p-4 px-2">
                                                <div class="row align-items-end gy-2">
                                                    <div class="col-lg-6">
                                                        <div class="form-group mb-0">
                                                            <label class="form-label flex-grow-1"
                                                                for="choose_cast1">{{__('form.choose-cast')}}</label>
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
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-2">
                                                        <button class="btn btn-primary  add-tvshow-cast">{{__('form.add-btn')}}</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="tab-pane fade" id="crew_tab1" role="tabpanel">
                                            <div class="p-4 px-2">
                                                <div class="row align-items-end gy-2">
                                                    <div class="col-lg-6">
                                                        <div class="form-group mb-0">
                                                            <label class="form-label flex-grow-1"
                                                                for="choose_crew1">{{__('form.choose-crew-member')}}</label>
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
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-2">
                                                        <button class="btn btn-primary  add-tvshow-cast">{{__('form.add-btn')}}</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="tab-pane fade" id="sources_tab1" role="tabpanel">
                                            <div class="p-4 px-2">
                                                <!-- Add Source Button -->
                                                <button id="add-movie-source1" class="btn btn-primary">{{__('dashboard.Add_Seasaon')}}</button>
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
                                                                    <select class="form-control" name="Choose Method">
                                                                        <option>Embed Movie</option>
                                                                        <option>Movie URL</option>
                                                                    </select>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label class="form-label flex-grow-1"
                                                                        for="Description2">
                                                                        {{__('form.embed-movie')}}
                                                                    </label>
                                                                    <textarea id="Description2" class="form-control" rows="7"
                                                                        placeholder="Enter the embed content to the TVshow."></textarea>
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
                                                    <label class="form-label flex-grow-1" for="choice_genres1">{{__('form.choose-tv-show-genres')}}</label>
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
                                                    <label class="form-label flex-grow-1" for="choice_tags1">{{__('form.choose-tv-show-tags')}}</label>
                                                    <select id="choice_tags1"
                                                        class="form-control select2-basic-multiple" multiple>
                                                        <option selected="">4K Ultra </option>
                                                        <option>Agents </option>
                                                        <option>Brother </option>
                                                        <option>Brother Relationship </option>
                                                        <option>Cricket </option>
                                                        <option>Detective </option>
                                                        <option>Dragons </option>
                                                        <option>Drama </option>
                                                        <option>Family </option>
                                                        <option>Guns </option>
                                                        <option>Kings </option>
                                                        <option>Mythology </option>
                                                        <option>Pirates </option>
                                                        <option>romance </option>
                                                        <option>Ships </option>
                                                        <option>thriller </option>
                                                        <option>Treasure </option>
                                                        <option>Vikings </option>
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
                                                                placeholder="{{__('form.enter-trailer-link')}}">
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
                                                                    {{__('dashboard.This_Is_Upcoming_TVshow')}}
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6">
                                                        @include(
                                                            'components.widget.UploadImageVideo',
                                                            [
                                                                'upload_image_name' => __('dashboard.Portrait_Image_File'),
                                                                'isUploadImageDefault' => true,
                                                            ]
                                                        )
                                                    </div>
                                                    <div class="col-lg-6">
                                                        <div class="form-group">
                                                            <label class="form-label" for="imdb_rating1">{{__('form.enter_imdb_rating')}}</label>
                                                            <input type="number" id="imdb_rating1"
                                                                class="form-control" value="0"
                                                                aria-invalid="false">
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6">
                                                        <div class="form-group">
                                                            <label class="form-label flex-grow-1"
                                                                for="select-product">{{__('form.select-related-product')}}</label>
                                                            <select id="select-product1"
                                                                class="form-control select2-basic-multiple" multiple>
                                                                <option selected="">Logo Collection </option>
                                                                <option>Comedy </option>
                                                                <option>Drama </option>
                                                                <option>T-Shirt with Logo</option>
                                                                <option>Cap </option>
                                                                <option>Polo </option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6">
                                                        <label class="d-block form-label">{{__('form.notification')}}</label>
                                                        <div class="checkbox mb-3">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox"
                                                                    id="flexCheckDefault33">
                                                                <label class="form-check-label"
                                                                    for="flexCheckDefault33">
                                                                    {{__('form.notify-user-about-prod')}}
                                                                </label>
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
@endsection
