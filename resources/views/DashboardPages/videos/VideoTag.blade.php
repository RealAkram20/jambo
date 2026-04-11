@extends('layouts.app', ['module_title' => 'videos-tags', 'isSweetalert' => true, 'Activelink' => 'videos-tags', 'isFlatpickr' => true, 'isQuillEditor' => true, 'isSelect2' => true])

@section('content')
<div class="row streamit-wraper-table2">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h2 class="episode-playlist-title wp-heading-inline">
                    <span class="position-relative ">
                        {{__('form.add-new-video-tag')}}</span>
                </h2>
            </div>
            <div class="card-body">
                <form>
                    <div class="form-group">
                        <label class="form-label" for="category-name">{{__('form.tag-name')}} <span> *</span></label>
                        <input type="text" class="form-control" id="category-name"
                            placeholder="{{__('form.enter-title-tag')}}">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="category-slug">{{__('form.tag-slug')}} <span> *</span></label>
                        <input type="text" class="form-control" id="category-slug"
                            placeholder="{{__('form.enter-slug-tag')}}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{__('form.tag-description')}}</label>
                        <textarea class="form-control large-text" aria-label="With textarea"></textarea>
                    </div>
                    <div class="d-flex align-items-center justify-content-end mt-4">
                        <button class="btn btn-primary">{{__('form.add-video-tag')}}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="pb-3">
                <div class="card-header d-flex justify-content-between gap-3 flex-wrap align-items-center mb-4">
                    <h2 class="episode-playlist-title wp-heading-inline">
                        <span class="position-relative ">
                            {{__('sidebar.video-tags')}} </span>
                    </h2>
                </div>
                <div class="card-body pt-0">
                    <div class="table-view table-space">
                        <table id="seasonTable"
                            class="data-tables table custom-table movie_table data-table-one custom-table-height"
                            data-toggle="data-table1">
                            <thead>
                                <tr class=" text-uppercase">
                                    <th class="">
                                        <input type="checkbox" class="form-check-input" />
                                    </th>
                                    <th class="">Tag Name</th>
                                    <th class="">Tag Slug</th>
                                    <th class="">Count</th>
                                    <th class="">Actions</th>
                                </tr>
                            </thead>
                            <tbody>

                                @include('components.datatable.DataTable', [
                                'isTags' => true,
                                'term_name' => 'Universe',
                                'term_slug' => 'universe',
                                'count' => '1',
                                'viewsValue' => true
                                ])

                                @include('components.datatable.DataTable', [
                                'isTags' => true,
                                'term_name' => 'Thriller',
                                'term_slug' => 'Thriller',
                                'count' => '1',
                                'viewsValue' => true
                                ])

                                @include('components.datatable.DataTable', [
                                'isTags' => true,
                                'term_name' => 'Romance',
                                'term_slug' => 'Romance',
                                'count' => '3',
                                'viewsValue' => true
                                ])

                                @include('components.datatable.DataTable', [
                                'isTags' => true,
                                'term_name' => 'Melodrama',
                                'term_slug' => 'Melodrama',
                                'count' => '1',
                                'viewsValue' => true
                                ])


                                @include('components.datatable.DataTable', [
                                'isTags' => true,
                                'term_name' => 'Magic',
                                'term_slug' => 'Magic',
                                'count' => '1',
                                'viewsValue' => true
                                ])

                                @include('components.datatable.DataTable', [
                                'isTags' => true,
                                'term_name' => 'Horror',
                                'term_slug' => 'horror',
                                'count' => '0',
                                'viewsValue' => true
                                ])

                                @include('components.datatable.DataTable', [
                                'isTags' => true,
                                'term_name' => 'Fantasy',
                                'term_slug' => 'fantasy',
                                'count' => '0',
                                'viewsValue' => true
                                ])

                                @include('components.datatable.DataTable', [
                                'isTags' => true,
                                'term_name' => 'Family',
                                'term_slug' => 'family',
                                'count' => '0',
                                'viewsValue' => true
                                ])

                                @include('components.datatable.DataTable', [
                                'isTags' => true,
                                'term_name' => 'Drama',
                                'term_slug' => 'drama',
                                'count' => '1',
                                'viewsValue' => true
                                ])

                                @include('components.datatable.DataTable', [
                                'isTags' => true,
                                'term_name' => 'Dragons',
                                'term_slug' => 'dragons',
                                'count' => '3',
                                'viewsValue' => true
                                ])
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="offcanvas offcanvas-end offcanvas-width-80" tabindex="-1" id="season-offcanvas-edit">
    <div class="offcanvas-header">
        <h2 class="episode-playlist-title wp-heading-inline">
            <span class="position-relative ">
                {{__('form.update-video-tag')}} </span>
        </h2>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <form>
            <div class="section-form">
                <div class="form-group">
                    <label class="form-label" for="category-name1">{{__('form.tag-name')}} <span> *</span></label>
                    <input type="text" class="form-control" id="category-name1"
                        placeholder="{{__('form.enter-title-genre')}}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="category-slug1">{{__('form.tag-slug')}} <span> *</span></label>
                    <input type="text" class="form-control" id="category-slug1"
                        placeholder="{{__('form.enter-slug-genre')}}">
                </div>
                <div class="form-group">
                    <label class="form-label">{{__('form.tag-description')}}</label>
                    <textarea class="form-control large-text" aria-label="With textarea"></textarea>

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