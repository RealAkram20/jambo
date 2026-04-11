@extends('layouts.app', ['module_title' => 'video-category', 'isSweetalert' => true, 'Activelink' => 'video-category', 'isFlatpickr' => true, 'isQuillEditor' => true, 'isSelect2' => true])

@section('content')
    <div class="row streamit-wraper-table2">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h2 class="episode-playlist-title wp-heading-inline">
                        <span class="position-relative ">
                            {{__('form.add-new-video-category')}}</span>
                    </h2>
                </div>
                <div class="card-body">
                    <form>
                        <div class="form-group">
                            <label class="form-label" for="category-name1">{{__('form.category-name')}} <span> *</span></label>
                            <input type="text" class="form-control" id="category-name1"
                                placeholder="{{__('form.enter-title-genre')}}">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="category-slug1">{{__('form.category-slug')}}</label>
                            <input type="text" class="form-control" id="category-slug1"
                                placeholder="{{__('form.enter-slug-genre')}}">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="parent-category">{{__('form.parent-category')}}</label>
                            <select id="parent-category" class="form-control select2-basic-multiple">
                                <option>Add Parent Genre</option>
                                <option>Action</option>
                                <option>Adventure</option>
                                <option>Animation</option>
                                <option>Crime</option>
                                <option>Horror</option>
                                <option>Mystery</option>
                                <option>Romance</option>
                                <option>TEST GENRES</option>
                                <option>TEST GENRES</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">{{__('form.category-description')}}</label>
                            <textarea class="form-control large-text" aria-label="With textarea"></textarea>

                        </div>
                        @include('components.widget.UploadImageVideo', [
                            'upload_image_name' => __('form.thumbnail'),
                        ])

                        <div class="d-flex align-items-center justify-content-end mt-4">
                            <button class="btn btn-primary">{{__('form.add-category')}}</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
        <div class="col-lg-8">
            <div class="streamit-wraper-table card pb-3">
                <div class="card-header d-flex justify-content-between gap-3 flex-wrap align-items-center mb-4">
                    <h2 class="episode-playlist-title wp-heading-inline">
                        <span class="position-relative ">
                            {{__('form.video-categories')}} </span>
                    </h2>
                </div>
                <div class="card-body">
                    <div class="table-view table-space">
                        <table id="seasonTable" class="data-tables table custom-table movie_table data-table-one"
                            data-toggle="data-table1">
                            <thead>
                                <tr class="text-uppercase">
                                    <th class="">
                                        <input type="checkbox" class="form-check-input" />
                                    </th>
                                    <th class="">Thumbnail</th>
                                    <th class="">Name</th>
                                    <th class="">Slug</th>
                                    <th class="">{{__('form.parent-genre')}}</th>
                                    <th class="">Count</th>
                                    <th class="">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @include('components.datatable.DataTable', [
                                    'title' => 'Romance',
                                    'thumbnail' => 'genres/romance.webp',
                                    'author' => 'romance',
                                    'date' => 'None',
                                    'views' => '2',
                                    'viewsValue' => 'true',
                                ])

                                @include('components.datatable.DataTable', [
                                    'title' => 'Mystery',
                                    'thumbnail' => 'genres/mystery.webp',
                                    'author' => 'mystery',
                                    'date' => 'None',
                                    'views' => '14',
                                    'viewsValue' => 'true',
                                ])

                                @include('components.datatable.DataTable', [
                                    'title' => 'Horror',
                                    'thumbnail' => 'genres/horror.webp',
                                    'author' => 'horror',
                                    'date' => 'None',
                                    'views' => '13',
                                    'viewsValue' => 'true',
                                ])

                                @include('components.datatable.DataTable', [
                                    'title' => 'Crime',
                                    'thumbnail' => 'genres/Crime.webp',
                                    'author' => 'crime',
                                    'date' => 'None',
                                    'views' => '10',
                                    'viewsValue' => 'true',
                                ])

                                @include('components.datatable.DataTable', [
                                    'title' => 'Animation',
                                    'thumbnail' => 'genres/animation.webp',
                                    'author' => 'animation',
                                    'date' => 'None',
                                    'views' => '10',
                                    'viewsValue' => 'true',
                                ])

                                @include('components.datatable.DataTable', [
                                    'title' => 'Adventure',
                                    'thumbnail' => 'genres/adventure.webp',
                                    'author' => 'adventure',
                                    'date' => 'None',
                                    'views' => '18',
                                    'viewsValue' => 'true',
                                ])

                                @include('components.datatable.DataTable', [
                                    'title' => 'Action',
                                    'thumbnail' => 'genres/action.webp',
                                    'author' => 'action',
                                    'date' => 'None',
                                    'views' => '22',
                                    'viewsValue' => 'true',
                                ])

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="offcanvas offcanvas-end offcanvas-width-80" tabindex="-1" id="season-offcanvas-edit">
        <div class="offcanvas-header">
            <h2 class="episode-playlist-title wp-heading-inline">
                <span class="position-relative ">
                    {{__('form.update-video-category')}} </span>
            </h2>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <form>
                <div class="section-form">
                    <div class="form-group">
                        <label class="form-label" for="category-name">{{__('form.category-name')}} *</label>
                        <input type="text" class="form-control" id="category-name"
                            placeholder="{{__('form.enter-title-genre')}}">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="category-slug">{{__('form.category-slug')}} *</label>
                        <input type="text" class="form-control" id="category-slug"
                            placeholder="{{__('form.enter-slug-genre')}}">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="parent-category1">{{__('form.parent-category')}} *</label>
                        <select id="parent-category1" class="form-control select2-basic-multiple">
                            <option>Add Parent Genre</option>
                            <option>Action</option>
                            <option>Adventure</option>
                            <option>Animation</option>
                            <option>Crime</option>
                            <option>Horror</option>
                            <option>Mystery</option>
                            <option>Romance</option>
                            <option>TEST GENRES</option>
                            <option>TEST GENRES</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{__('form.category-description')}}</label>
                        <textarea class="form-control large-text" aria-label="With textarea"></textarea>

                    </div>
                    @include('components.widget.UploadImageVideo', [
                        'upload_image_name' => __('form.thumbnail'),
                        'isUploadImageDefault' => true,
                    ])
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
