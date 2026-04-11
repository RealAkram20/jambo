@extends('layouts.app', ['module_title' => 'tvshow tags', 'isSweetalert' => true, 'Activelink' => 'Tvshow', 'isFlatpickr' => true, 'isQuillEditor' => true, 'isSelect2' => true])

@section('content')
    <div class="row streamit-wraper-table2">
        <div class="col-lg-4">
            <div class="card pb-3">
                <div class="card-header">
                    <h2 class="episode-playlist-title wp-heading-inline">
                        <span class="position-relative ">
                            {{__('form.add_new_tag')}} </span>
                    </h2>
                </div>
                <div class="card-body">
                    <form>
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label class="form-label flex-grow-1" for="Name">
                                        {{__('form.tag-name')}} <span> *</span>
                                    </label>
                                    <input id="Name" type="text" class="form-control "
                                        placeholder="{{__('form.enter-name-tag')}}" required>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label class="form-label flex-grow-1" for="Slug">
                                        {{__('form.tag-slug')}} <span> *</span>
                                    </label>
                                    <input id="Slug" type="text" class="form-control "
                                        placeholder="{{__('form.enter-slug-tag')}}" required>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label class="form-label flex-grow-1" for="Description">
                                        {{__('form.tag-description')}}
                                    </label>
                                    <textarea id="Description" class="form-control" rows="7" placeholder="{{__('form.enter-tag-description')}}"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#video-modal"> {{__('form.add-tag')}}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card pb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="episode-playlist-title wp-heading-inline">
                        <span class="position-relative ">
                            {{__('form.tag-list')}}</span>
                    </h2>
                </div>
                <div class="card-body">
                    <div class="table-view table-space">
                        <table id="seasonTable"
                            class="data-tables table custom-table movie_table data-table-one custom-table-height"
                            data-toggle="data-table1">
                            <thead>
                                <tr class="text-uppercase">
                                    <th class="">
                                        <input type="checkbox" class="form-check-input" />
                                    </th>
                                    <th>Tag Name</th>
                                    <th>Tag Slug</th>
                                    <th>Count</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @include('components.datatable.DataTable', [
                                    'term_name' => 'Vikings',
                                    'term_slug' => 'vikings',
                                    'count' => '0',
                                    'isTags' => true,
                                ])

                                @include('components.datatable.DataTable', [
                                    'term_name' => 'Treasure',
                                    'term_slug' => 'treasure',
                                    'count' => '0',
                                    'isTags' => true,
                                ])
                                @include('components.datatable.DataTable', [
                                    'term_name' => 'Thriller',
                                    'term_slug' => 'thriller',
                                    'count' => '0',
                                    'isTags' => true,
                                ])
                                @include('components.datatable.DataTable', [
                                    'term_name' => 'Ships',
                                    'term_slug' => 'ships',
                                    'count' => '0',
                                    'isTags' => true,
                                ])
                                @include('components.datatable.DataTable', [
                                    'term_name' => 'Romance',
                                    'term_slug' => 'romance',
                                    'count' => '0',
                                    'isTags' => true,
                                ])
                                @include('components.datatable.DataTable', [
                                    'term_name' => 'Pirates',
                                    'term_slug' => 'pirates',
                                    'count' => '0',
                                    'isTags' => true,
                                ])
                                @include('components.datatable.DataTable', [
                                    'term_name' => 'Mythology',
                                    'term_slug' => 'mythology',
                                    'count' => '0',
                                    'isTags' => true,
                                ])
                                @include('components.datatable.DataTable', [
                                    'term_name' => 'Kings',
                                    'term_slug' => 'kings',
                                    'count' => '0',
                                    'isTags' => true,
                                ])
                                @include('components.datatable.DataTable', [
                                    'term_name' => 'Guns',
                                    'term_slug' => 'guns',
                                    'count' => '0',
                                    'isTags' => true,
                                ])
                                @include('components.datatable.DataTable', [
                                    'term_name' => 'Family',
                                    'term_slug' => 'family',
                                    'count' => '0',
                                    'isTags' => true,
                                ])
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
    {{-- edit --}}
    <div class="offcanvas offcanvas-end offcanvas-width-80" tabindex="-1" id="season-offcanvas-edit">
        <div class="offcanvas-header">
            <h2 class="episode-playlist-title wp-heading-inline">
                <span class="position-relative ">
                    {{__('form.update-tag')}}</span>
            </h2>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <form>
                <div class="section-form">
                    <div class="form-group">
                        <label class="form-label" for="name">{{__('form.tag-name')}} <span> *</span></label>
                        <input type="text" class="form-control" id="name" value="{{__('form.vikings')}}">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="Slug1">{{__('form.tag-slug')}} <span> *</span></label>
                        <input type="text" class="form-control" id="Slug1" value="{{__('form.vikings')}}">
                    </div>
                    <div class="form-group">
                        <label class="form-label flex-grow-1" for="Description1">
                            {{__('form.tag-description')}}
                        </label>
                        <textarea id="Description1" class="form-control" rows="7"></textarea>
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
