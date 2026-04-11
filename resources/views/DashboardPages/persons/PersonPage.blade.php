@extends('layouts.app', ['module_title' => 'Persons List', 'isselect2' => true, 'isSweetalert' => true, 'Activelink' => 'Persons List', 'isFlatpickr' => true, 'isQuillEditor' => true, 'isSelect2' => true])

@section('content')
<div class="row">
    <div class="col-sm-12">
        <div class="streamit-wraper-table">
            <div class="card-header d-flex justify-content-between gap-3 flex-wrap align-items-center mb-4">
                <h2 class="episode-playlist-title wp-heading-inline">
                    <span class="position-relative ">
                        {{__('form.persons-list')}} </span>
                </h2>
                <button class="btn btn-primary" type="button" data-bs-toggle="offcanvas"
                    data-bs-target="#season-offcanvas" aria-controls="season-offcanvas">
                    <i class="fa-solid fa-plus me-2"></i>{{__('dashboard.add-new')}}
                </button>
            </div>
            <div class="table-view table-space">
                <table id="seasonTable" class="data-tables table custom-table movie_table data-table-one"
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
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>

                        @include('components.datatable.DataTable', [
                        'title' => 'Charles Melton',
                        'thumbnail' => 'author/charles-melton.webp',
                        'author' => 'jenny',
                        'date' => '2024-11-27 08:36:04',
                        'viewsValue' => true,
                        'views' => '0'
                        ])

                        @include('components.datatable.DataTable', [
                        'title' => 'Jack Nicholson',
                        'thumbnail' => 'author/jack-nicholson.webp',
                        'author' => 'jenny',
                        'date' => '2024-11-27 08:35:00',
                        'viewsValue' => true,
                        'views' => '0'
                        ])

                        @include('components.datatable.DataTable', [
                        'title' => 'James Stewart',
                        'thumbnail' => 'author/james-stewart.webp',
                        'author' => 'jenny',
                        'date' => '2024-11-27 08:33:32',
                        'viewsValue' => true,
                        'views' => '0'
                        ])

                        @include('components.datatable.DataTable', [
                        'title' => 'Jeff Bridges',
                        'thumbnail' => 'author/jeff-bridges.webp',
                        'author' => 'jenny',
                        'date' => '2024-11-27 08:32:28',
                        'viewsValue' => true,
                        'views' => '0'
                        ])


                        @include('components.datatable.DataTable', [
                        'title' => 'Jordan Grant',
                        'thumbnail' => 'author/jordan-grant.webp',
                        'author' => 'jenny',
                        'date' => '2024-11-27 08:31:27',
                        'viewsValue' => true,
                        'views' => '0'
                        ])

                        @include('components.datatable.DataTable', [
                        'title' => 'Mark Smith',
                        'thumbnail' => 'author/mark-smith.webp',
                        'author' => 'jenny',
                        'date' => '2024-11-27 08:29:43',
                        'viewsValue' => true,
                        'views' => '0'
                        ])

                        @include('components.datatable.DataTable', [
                        'title' => 'Maxwell Carter',
                        'thumbnail' => 'author/maxwell-carter.webp',
                        'author' => 'jenny',
                        'date' => '2024-11-27 08:27:53',
                        'viewsValue' => true,
                        'views' => '0'
                        ])

                        @include('components.datatable.DataTable', [
                        'title' => 'Ruby Scott',
                        'thumbnail' => 'author/ruby-scott.webp',
                        'author' => 'jenny',
                        'date' => '2024-11-27 08:26:50',
                        'viewsValue' => true,
                        'views' => '0'
                        ])

                        @include('components.datatable.DataTable', [
                        'title' => 'Michael Fox',
                        'thumbnail' => 'author/michael-fox.webp',
                        'author' => 'jenny',
                        'date' => '2024-11-30 06:19:27',
                        'viewsValue' => true,
                        'views' => '0'
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
                        {{__('form.add-new-person')}} </span>
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
                                    <label class="form-label flex-grow-1" for="title1">
                                        {{__('form.person-title')}} <span> *</span>
                                    </label>
                                    <input class="form-control" type="text" id="title1" placeholder="{{__('form.enter-title-person')}}"
                                        data-id="multiple" required="">
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label class="form-label flex-grow-1" for="author">{{__('form.author')}}</label>
                                    <select id="author" class="form-control select2-basic-multiple">
                                        <option>admin</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label class="form-label flex-grow-1" for="status">{{__('form.status')}}</label>
                                    <select id="status" class="form-control select2-basic-multiple">
                                        <option>Published</option>
                                        <option>Draft</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-5">
                            <label class="form-label flex-grow-1">{{__('form.person-title')}}</label>
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
                                        {{__('form.person-excerpt')}}
                                    </label>
                                    <textarea id="Description" class="form-control" rows="7"
                                        placeholder="{{__('form.enter-encerpt-movie')}}"></textarea>
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
                                                    data-bs-target="#category_tab" role="tab" aria-selected="false">
                                                    <span>{{__('dashboard.category')}}</span>
                                                </button>

                                                <button class="nav-link" data-bs-toggle="pill" type="button"
                                                    data-bs-target="#tags_tab" role="tab" aria-selected="false">
                                                    <span>{{__('form.tags')}}</span>
                                                </button>
                                                <button class="nav-link" data-bs-toggle="pill" type="button"
                                                    data-bs-target="#personalData_tab" role="tab" aria-selected="true">
                                                    <span>{{__('form.personal-data')}}</span>
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
                                                                <label class="form-label">{{__('form.also-known-as')}}</label>
                                                                <input type="text" name="also_know_as"
                                                                    id="also_know_as" class="form-control"
                                                                    placeholder="{{__('form.enter-alternate-names-person')}}">
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-6">
                                                            <div class="form-group">
                                                                <label class="form-label">{{__('form.place-of-birth')}}</label>
                                                                <input type="text" name="place_birth1"
                                                                    id="place_birth1" class="form-control"
                                                                    placeholder="{{__('form.enter-the-movie-censor-rating')}}">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-lg-6">
                                                            <div class="form-group">
                                                                <label class="form-label flex-grow-1" for="birth">
                                                                    {{__('form.birth-date')}}
                                                                </label>
                                                                <input class="form-control flatpickr_humandate"
                                                                    type="text"
                                                                    placeholder="{{__('form.enter-birth-date-person')}}"
                                                                    data-id="multiple" id="birth">
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-6">
                                                            <div class="form-group">
                                                                <label class="form-label flex-grow-1" for="death">
                                                                    {{__('form.death-date')}}
                                                                </label>
                                                                <input class="form-control flatpickr_humandate"
                                                                    type="text"
                                                                    placeholder="{{__('form.enter-death-date-person')}}"
                                                                    data-id="multiple" id="death">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="tab-pane fade" id="category_tab" role="tabpanel">
                                                <div class="p-4 px-2">
                                                    <div class="form-group">
                                                        <label class="form-label flex-grow-1">{{__('form.choose-person-category')}}</label>
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
                                                        <label class="form-label flex-grow-1">{{__('form.choose-person-tags')}}</label>
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
                                            <div class="tab-pane fade" id="personalData_tab" role="tabpanel">
                                                <div class="p-4 px-2">
                                                    <div class="row">
                                                        <div class="col-lg-6">
                                                            <div class="form-group">
                                                                <label class="form-label flex-grow-1" for="awards">
                                                                    {{__('form.awards')}}
                                                                </label>
                                                                <input type="text" class="form-control" id="awards" name="Awards">
                                                            </div>
                                                            <div class="form-group">
                                                                <label class="form-label flex-grow-1" for="Height">
                                                                    {{__('form.height')}}
                                                                </label>
                                                                <input type="text" class="form-control" id="Height" name="Height">
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-6">
                                                            <div class="form-group">
                                                                <label class="form-label flex-grow-1" for="OfficalURL">
                                                                    {{__('form.offical-site')}}
                                                                </label>
                                                                <input type="text" id="OfficalURL" class="form-control"
                                                                    name="Offical URL"
                                                                    placeholder="https://www.imdb.com">
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
                    <button type="button" class="btn btn-outline-primary d-flex align-items-center gap-2" data-bs-dismiss="offcanvas"
                        aria-label="Close">
                        <i class="ph ph-caret-double-left"></i>{{ __('movielist.Close') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- edit --}}
    <div class="offcanvas offcanvas-end offcanvas-width-80" tabindex="-1" id="season-offcanvas-edit">
        <div class="offcanvas-header">
            <h5 id="offcanvasRightLabel1">{{__('sidebar.edit-new-person')}}</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <form>
                <div class="section-form">
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label class="form-label flex-grow-1" for="title">
                                    {{__('form.person-title')}} <span> *</span>
                                </label>
                                <input class="form-control" id="title" type="text" value="{{__('form.arrival_1999')}}" data-id="multiple"
                                    required="">
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label class="form-label flex-grow-1" for="author1">{{__('form.author')}}</label>
                                <select id="author1" class="form-control select2-basic-multiple">
                                    <option selected="">admin</option>
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
                        <label class="form-label flex-grow-1">{{__('form.person-title')}}</label>
                        <div class="col-lg-12">
                            <div id="editor1" style="height:200px"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-3">
                            @include('components.widget.UploadImageVideo', [
                            'upload_image_name' => __('form.thumbnail'),
                            'isUploadImageDefault' => true
                            ])
                        </div>
                        <div class="col-lg-9">
                            <div class="form-group">
                                <label class="form-label flex-grow-1" for="Description1">
                                    {{__('form.person-excerpt')}}
                                </label>
                                <textarea id="Description1" class="form-control" rows="7"
                                    placeholder="{{__('form.enter-encerpt-movie')}}"></textarea>
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
                                                data-bs-target="#category_tab1" role="tab" aria-selected="false">
                                                <span>{{__('dashboard.category')}}</span>
                                            </button>

                                            <button class="nav-link" data-bs-toggle="pill" type="button"
                                                data-bs-target="#tags_tab1" role="tab" aria-selected="false">
                                                <span>{{__('form.tags')}}</span>
                                            </button>
                                            <button class="nav-link" data-bs-toggle="pill" type="button"
                                                data-bs-target="#personalData_tab1" role="tab" aria-selected="true">
                                                <span>{{__('form.personal-data')}}</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-9 edit-tab-content">
                                    <div class="tab-content" id="streamit-tabs-contents">
                                        <div class="tab-pane fade show active" id="general_tab1" role="tabpanel">
                                            <div class="p-4 px-2">
                                                <div class="row">
                                                    <div class="col-lg-6">
                                                        <div class="form-group">
                                                            <label class="form-label">{{__('form.also-known-as')}}</label>
                                                            <input type="text" name="_movie_censor_rating"
                                                                id="_movie_censor_rating" value="{{__('form.admin')}}"
                                                                class="form-control">
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6">
                                                        <div class="form-group">
                                                            <label class="form-label">{{__('form.place-of-birth')}}</label>
                                                            <input type="text" name="place_birth"
                                                                id="place_birth" value="{{__('form.place')}}"
                                                                class="form-control">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-lg-6">
                                                        <div class="form-group">
                                                            <label class="form-label flex-grow-1" for="birthdate1">
                                                                {{__('form.birth-date')}}
                                                            </label>
                                                            <input class="form-control flatpickr_humandate" type="text"
                                                                value="2025-01-01" id="birthdate1" data-id="multiple">
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6">
                                                        <div class="form-group">
                                                            <label class="form-label flex-grow-1" for="dathdate1">
                                                                {{__('form.death-date')}}
                                                            </label>
                                                            <input class="form-control flatpickr_humandate" type="text"
                                                                value="2025-01-17" id="dathdate1" data-id="multiple">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="tab-pane fade" id="category_tab1" role="tabpanel">
                                            <div class="p-4 px-2">
                                                <div class="form-group">
                                                    <label class="form-label flex-grow-1">{{__('form.choose-person-category')}}</label>
                                                    <select id="choice_category"
                                                        class="form-control select2-basic-multiple" multiple>
                                                        <option selected="">Actor </option>
                                                        <option>Actress </option>
                                                        <option>Director </option>
                                                        <option>Producer </option>
                                                        <option>Singer </option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="tab-pane fade" id="tags_tab1" role="tabpanel">
                                            <div class="p-4 px-2">
                                                <div class="form-group">
                                                    <label class="form-label flex-grow-1">{{__('form.choose-person-tags')}}</label>
                                                    <select id="pmp_levels" class="form-control select2-basic-multiple"
                                                        multiple>
                                                        <option selected="">Actor </option>
                                                        <option>Actress </option>
                                                        <option>Director </option>
                                                        <option>Producer </option>
                                                        <option>Singer </option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="tab-pane fade" id="personalData_tab1" role="tabpanel">
                                            <div class="p-4 px-2">
                                                <div class="row">
                                                    <div class="col-lg-6">
                                                        <div class="form-group">
                                                            <label class="form-label flex-grow-1" for="Awards1">
                                                                {{__('form.awards')}}
                                                            </label>
                                                            <input type="text" class="form-control" id="Awards1" name="Awards"
                                                                value="IFFA Award">
                                                        </div>
                                                        <div class="form-group">
                                                            <label class="form-label flex-grow-1" for="Height1">
                                                                {{__('form.height')}}
                                                            </label>
                                                            <input type="text" class="form-control" id="Height1" name="Height"
                                                                value="5'2">
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6">
                                                        <div class="form-group">
                                                            <label class="form-label flex-grow-1" for="OfficalURL1">
                                                                {{__('form.offical-site')}}
                                                            </label>
                                                            <input type="text" class="form-control" id="OfficalURL1" name="Offical URL"
                                                                placeholder="https://www.imdb.com"
                                                                value="">
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
                <button type="button" class="btn btn-outline-primary d-flex align-items-center gap-2" data-bs-dismiss="offcanvas"
                    aria-label="Close">
                    <i class="ph ph-caret-double-left"></i>{{ __('movielist.Close') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endsection