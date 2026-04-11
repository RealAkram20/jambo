@extends('layouts.app', ['module_title' => 'episodes', 'isSweetalert' => true, 'Activelink' => 'Tvshow', 'isFlatpickr' => true, 'isQuillEditor' => true, 'isSelect2' => true])
@section('content')
<div class="row">
    <div class="col-sm-12">
        <div class="streamit-wraper-table">
            <div class="card-header d-flex justify-content-between gap-3 flex-wrap align-items-center mb-4">
                <h2 class="episode-playlist-title wp-heading-inline">
                    <span class="position-relative ">
                        {{ __('movielist.episodes') }} </span>
                </h2>
                <button class="btn btn-primary px-2 px-lg-3" type="button" data-bs-toggle="offcanvas"
                    data-bs-target="#season-offcanvas" aria-controls="season-offcanvas">
                    <i class="fa-solid fa-plus me-2"></i>{{ __('dashboard.Add_Episodes') }}
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
                        'title' => "The Lifting of the Mountain",
                        'thumbnail' => "movie-thumb/krishna-1.webp",
                        'author' => "jenny",
                        'date' => "2024-12-26 10:28:40",
                        'views' => "54 views"
                        ])

                        @include('components.datatable.DataTable', [
                        'title' => "The Escape to Safety",
                        'thumbnail' => "movie-thumb/krishna-2.webp",
                        'author' => "jenny",
                        'date' => "2024-12-26 10:27:23",
                        'views' => "39 views"
                        ])

                        @include('components.datatable.DataTable', [
                        'title' => "The Childhood Adventures",
                        'thumbnail' => "movie-thumb/krishna-3.webp",
                        'author' => "jenny",
                        'date' => "2024-12-26 10:25:52",
                        'views' => "38 views"
                        ])

                        @include('components.datatable.DataTable', [
                        'title' => "The Trials of Faith",
                        'thumbnail' => "movie-thumb/krishna-1.webp",
                        'author' => "jenny",
                        'date' => "2024-12-26 09:39:27",
                        'views' => "110 views"
                        ])

                        @include('components.datatable.DataTable', [
                        'title' => "The Warrior’s Path",
                        'thumbnail' => "movie-thumb/krishna-2.webp",
                        'author' => "jenny",
                        'date' => "2024-12-26 09:36:42",
                        'views' => "53 views"
                        ])

                        @include('components.datatable.DataTable', [
                        'title' => "The Sacred Oath",
                        'thumbnail' => "movie-thumb/krishna-3.webp",
                        'author' => "jenny",
                        'date' => "2024-12-26 09:34:46",
                        'views' => "52 views"
                        ])

                        @include('components.datatable.DataTable', [
                        'title' => "Minion Magic",
                        'thumbnail' => "movie-thumb/minions-1.webp",
                        'author' => "jenny",
                        'date' => "2025-05-23 08:39:43",
                        'views' => "355 views"
                        ])

                        @include('components.datatable.DataTable', [
                        'title' => "The Minions’ Great Escape",
                        'thumbnail' => "movie-thumb/minions-2.webp",
                        'author' => "jenny",
                        'date' => "2025-05-23 08:41:38",
                        'views' => "127 views"
                        ])

                        @include('components.datatable.DataTable', [
                        'title' => "Minions in Space",
                        'thumbnail' => "movie-thumb/minions-3.webp",
                        'author' => "jenny",
                        'date' => "2025-01-21 07:44:55",
                        'views' => "117 views"
                        ])

                        @include('components.datatable.DataTable', [
                        'title' => "The Minion Olympics",
                        'thumbnail' => "movie-thumb/minions-4.webp",
                        'author' => "jenny",
                        'date' => "2025-01-21 07:46:36",
                        'views' => "131 views"
                        ])

                        @include('components.datatable.DataTable', [
                        'title' => "The Divine Birth",
                        'thumbnail' => "movie-thumb/hanuman1.webp",
                        'author' => "jenny",
                        'date' => "2024-12-26 11:53:12",
                        'views' => "146 views"
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
                        {{ __('movielist.Add_New_Episodes') }} </span>
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
                                        {{ __('form.episode-title') }} <span> *</span>
                                    </label>
                                    <input class="form-control" type="text" id="genres"
                                        placeholder="{{ __('form.enter-title-episode') }}" data-id="multiple">
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label class="form-label flex-grow-1" for="author">{{ __('form.author') }}</label>
                                    <select id="author" class="form-control select2-basic-multiple">
                                        <option>admin</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label class="form-label flex-grow-1" for="status">{{ __('form.status') }}</label>
                                    <select id="status" class="form-control select2-basic-multiple">
                                        <option>Published</option>
                                        <option>Draft</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-5">
                            <label class="form-label flex-grow-1">{{ __('form.embed-episode-content') }}</label>
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
                                                    data-bs-target="#sources_tab" role="tab" aria-selected="false">
                                                    <span>{{ __('form.sources') }}</span>
                                                </button>

                                                <button class="nav-link" data-bs-toggle="pill" type="button"
                                                    data-bs-target="#additionData_tab" role="tab"
                                                    aria-selected="true">
                                                    <span>{{ __('form.additional_data') }}</span>
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
                                                                    for="movieMethod">{{ __('form.episode-number') }}</label>
                                                                <input class="form-control" type="text"
                                                                    placeholder="{{ __('form.enter-episode-number') }}"
                                                                    data-id="multiple">
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-6">
                                                            <div class="form-group">
                                                                <label class="form-label flex-grow-1"
                                                                    for="movieMethod">{{ __('dashboard.choose_episode_method') }}</label>
                                                                <select id="movieMethod"
                                                                    class="form-control select2-basic-multiple">
                                                                    <option>Upload Episode</option>
                                                                    <option>Embed Episode</option>
                                                                    <option>Episode URL</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-lg-6"></div>
                                                        <div class="col-lg-6">
                                                            @include(
                                                            'components.widget.UploadImageVideo',
                                                            [
                                                            'upload_image_name' => 'Upload Episode',
                                                            ]
                                                            )
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-lg-6">
                                                            <div class="form-group">
                                                                <label class="form-label flex-grow-1" for="erdate1">
                                                                    {{ __('dashboard.episode_release_date') }}
                                                                </label>
                                                                <input class="form-control flatpickr_humandate"
                                                                    type="text" id="erdate1"
                                                                    placeholder="{{ __('form.enter-the-release-date-movie') }}"
                                                                    data-id="multiple" id="genres">
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-6">
                                                            <div class="row">
                                                                <label
                                                                    class="form-label">{{ __('form.episode-time-duration') }}</label>
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
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="tab-pane fade" id="sources_tab" role="tabpanel">
                                                <div class="p-4 px-2">
                                                    <!-- Add Source Button -->
                                                    <button id="add-movie-source"
                                                        class="btn btn-primary">{{ __('form.add-source') }}</button>
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
                                                                            for="genres1">
                                                                            {{ __('form.name') }}
                                                                        </label>
                                                                        <input type="text" class="form-control"
                                                                            name="name" id="genres1"
                                                                            placeholder="{{ __('form.enter-source-name-movie') }}">
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label class="form-label flex-grow-1"
                                                                            for="genres2">
                                                                            {{ __('form.quality') }}
                                                                        </label>
                                                                        <input type="text" id="genres2"
                                                                            class="form-control" name="quality"
                                                                            placeholder="{{ __('form.enter-source-quality-movie') }}">
                                                                    </div>
                                                                </div>
                                                                <div class="col-lg-6">
                                                                    <div class="form-group">
                                                                        <label class="form-label flex-grow-1">
                                                                            {{ __('form.choose_method') }}
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
                                                                            {{ __('form.embed-movie') }}
                                                                        </label>
                                                                        <textarea id="Description1" class="form-control" rows="7"
                                                                            placeholder="{{ __('form.enter-the-embed-content-movie') }}"></textarea>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-lg-6">
                                                                    <div class="form-group">
                                                                        <label class="form-label flex-grow-1"
                                                                            for="genres3">
                                                                            {{ __('form.language') }}
                                                                        </label>
                                                                        <input type="text" id="genres3"
                                                                            class="form-control" name="Language"
                                                                            placeholder="{{ __('form.enter-source-name-movie') }}">
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label class="form-label flex-grow-1"
                                                                            for="genres4">
                                                                            {{ __('form.date_added') }}
                                                                        </label>
                                                                        <input type="text" id="genres4"
                                                                            class="form-control" name="Date Added"
                                                                            placeholder="{{ __('form.enter-source-quality-movie') }}">
                                                                    </div>
                                                                </div>
                                                                <div class="col-lg-6">
                                                                    <div class="form-group">
                                                                        <label class="form-label flex-grow-1"
                                                                            for="genres5">
                                                                            {{ __('form.player') }}
                                                                        </label>
                                                                        <input type="text" id="genres5"
                                                                            class="form-control" name="Player"
                                                                            placeholder="{{ __('form.enter-source-quality-movie') }}">
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label class="form-label flex-grow-1"
                                                                            for="genres6">
                                                                            {{ __('form.download_url') }}
                                                                        </label>
                                                                        <input type="text" id="genres6"
                                                                            class="form-control" name="Download URL"
                                                                            placeholder="{{ __('form.enter-source-quality-movie') }}">
                                                                    </div>
                                                                </div>

                                                            </div>
                                                        </div>
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
                                                            'upload_image_name' => __(
                                                            'form.portrait-file'),
                                                            ]
                                                            )
                                                        </div>
                                                        <div class="col-lg-6">
                                                            <div class="form-group">
                                                                <label class="form-label flex-grow-1" for="genres7">
                                                                    {{ __('form.trailer-link') }}
                                                                </label>
                                                                <input type="text" id="genres7"
                                                                    class="form-control" name="Download URL"
                                                                    placeholder="{{ __('form.enter-trailer-link') }}">
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-6">
                                                            <label
                                                                class="d-block form-label">{{ __('form.notification') }}</label>
                                                            <div class="checkbox mb-3">
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox"
                                                                        id="flexCheckDefault3">
                                                                    <label class="form-check-label"
                                                                        for="flexCheckDefault3">
                                                                        {{ __('form.notify-user-about-prod') }}</label>
                                                                </div>
                                                            </div>
                                                        </div>
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
                    <button type="button" class="btn btn-outline-primary d-flex align-items-center gap-2"
                        data-bs-dismiss="offcanvas" aria-label="Close">
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
                        {{ __('dashboard.edit_episode') }} </span>
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
                                    <label class="form-label flex-grow-1" for="etitle">
                                        {{ __('form.episode-title') }}</label>
                                    <input class="form-control" id="etitle" type="text"
                                        value="{{ __('dashboard.the_childhood_adventures') }}" data-id="multiple">
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label class="form-label flex-grow-1"
                                        for="author1">{{ __('form.author') }}</label>
                                    <select id="author1" class="form-control select2-basic-multiple">
                                        <option selected="">admin</option>
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
                            <label class="form-label flex-grow-1">{{ __('form.embed-episode-content') }}</label>
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
                                        {{ __('form.excerpt') }}</label>
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
                                                    data-bs-target="#sources_tab1" role="tab"
                                                    aria-selected="false">
                                                    <span>{{ __('form.sources') }}</span>
                                                </button>
                                                <button class="nav-link" data-bs-toggle="pill" type="button"
                                                    data-bs-target="#additionData_tab1" role="tab"
                                                    aria-selected="true">
                                                    <span>{{ __('form.additional_data') }}</span>
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
                                                                    for="movieMethod">{{ __('form.episode-number') }}</label>
                                                                <input class="form-control" type="text"
                                                                    value="E4" data-id="multiple">
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-6">
                                                            <div class="form-group">
                                                                <label class="form-label flex-grow-1"
                                                                    for="movieMethod1">{{ __('dashboard.choose_episode_method') }}</label>
                                                                <select id="movieMethod1"
                                                                    class="form-control select2-basic-multiple">
                                                                    <option selected="">
                                                                        {{ __('form.upload-episode') }}
                                                                    </option>
                                                                    <option>Embed Episode</option>
                                                                    <option>Episode URL</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-lg-6"></div>
                                                        <div class="col-lg-6">
                                                            @include(
                                                            'components.widget.UploadImageVideo',
                                                            [
                                                            'upload_image_name' => __(
                                                            'form.upload-episode'),
                                                            'isUploadImageDefault' => true,
                                                            ]
                                                            )
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-lg-6">
                                                            <div class="form-group">
                                                                <label class="form-label flex-grow-1" for="erdate">
                                                                    {{ __('dashboard.episode_release_date') }}
                                                                </label>
                                                                <input class="form-control flatpickr_humandate"
                                                                    type="text" id="erdate"
                                                                    placeholder="{{ __('form.enter-the-release-date-movie') }}"
                                                                    data-id="multiple" value="2024-02-14">
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-6">
                                                            <div class="row">
                                                                <label
                                                                    class="form-label">{{ __('form.episode-time-duration') }}</label>
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
                                                                            name="minutes" value="35"
                                                                            placeholder="{{ __('form.minutes') }}"
                                                                            min="0" max="59"
                                                                            aria-invalid="false">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="tab-pane fade" id="sources_tab1" role="tabpanel">
                                                <div class="p-4 px-2">
                                                    <!-- Add Source Button -->
                                                    <button id="add-movie-source1"
                                                        class="btn btn-primary">{{ __('form.add-source') }}</button>
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
                                                                            for="name">
                                                                            {{ __('form.name') }}
                                                                        </label>
                                                                        <input type="text" id="name"
                                                                            class="form-control" name="name"
                                                                            placeholder="{{ __('form.enter-source-name-movie') }}">
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label class="form-label flex-grow-1"
                                                                            for="Quality">
                                                                            {{ __('form.quality') }}
                                                                        </label>
                                                                        <input type="text" id="Quality"
                                                                            class="form-control" name="quality"
                                                                            placeholder="{{ __('form.enter-source-quality-movie') }}">
                                                                    </div>
                                                                </div>
                                                                <div class="col-lg-6">
                                                                    <div class="form-group">
                                                                        <label class="form-label flex-grow-1"
                                                                            for="cmethod">
                                                                            {{ __('form.choose_method') }}
                                                                        </label>
                                                                        <select class="form-control" id="cmethod"
                                                                            name="Choose Method">
                                                                            <option>Embed Movie</option>
                                                                            <option>Movie URL</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label class="form-label flex-grow-1"
                                                                            for="Description3">
                                                                            {{ __('form.embed-movie') }}
                                                                        </label>
                                                                        <textarea id="Description3" class="form-control" rows="7"
                                                                            placeholder="{{ __('form.enter-the-embed-content-movie') }}"></textarea>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-lg-6">
                                                                    <div class="form-group">
                                                                        <label class="form-label flex-grow-1"
                                                                            for="Language">
                                                                            {{ __('form.language') }}
                                                                        </label>
                                                                        <input type="text" id="Language"
                                                                            class="form-control" name="Language"
                                                                            placeholder="{{ __('form.enter-source-name-movie') }}">
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label class="form-label flex-grow-1"
                                                                            for="daddede">
                                                                            {{ __('form.date_added') }}
                                                                        </label>
                                                                        <input type="text" id="daddede"
                                                                            class="form-control" name="Date Added"
                                                                            placeholder="{{ __('form.enter-source-quality-movie') }}">
                                                                    </div>
                                                                </div>
                                                                <div class="col-lg-6">
                                                                    <div class="form-group">
                                                                        <label class="form-label flex-grow-1"
                                                                            for="player">
                                                                            {{ __('form.player') }}
                                                                        </label>
                                                                        <input type="text" id="player"
                                                                            class="form-control" name="Player"
                                                                            placeholder="{{ __('form.enter-source-quality-movie') }}">
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label class="form-label flex-grow-1"
                                                                            for="durl">
                                                                            {{ __('form.download_url') }}
                                                                        </label>
                                                                        <input type="text" id="durl"
                                                                            class="form-control" name="Download URL"
                                                                            placeholder="{{ __('form.enter-source-quality-movie') }}">
                                                                    </div>
                                                                </div>

                                                            </div>
                                                        </div>
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
                                                            'upload_image_name' => __(
                                                            'form.portrait-file'),
                                                            'isUploadImageDefault' => true,
                                                            ]
                                                            )
                                                        </div>
                                                        <div class="col-lg-6">
                                                            <div class="form-group">
                                                                <label class="form-label flex-grow-1" for="tlink">
                                                                    {{ __('form.trailer-link') }}
                                                                </label>
                                                                <input type="text" id="tlink"
                                                                    class="form-control" name="Download URL"
                                                                    placeholder="{{ __('form.enter-trailer-link') }}">
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-6">
                                                            <label
                                                                class="d-block form-label">{{ __('form.notification') }}</label>
                                                            <div class="checkbox mb-3">
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox"
                                                                        id="flexCheckDefault31">
                                                                    <label class="form-check-label"
                                                                        for="flexCheckDefault31">
                                                                        {{ __('form.notify-user-about-prod') }}</label>
                                                                </div>
                                                            </div>
                                                        </div>
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
                    <button type="button" class="btn btn-outline-primary d-flex align-items-center gap-2"
                        data-bs-dismiss="offcanvas" aria-label="Close">
                        <i class="ph ph-caret-double-left"></i>{{ __('movielist.Close') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection