@extends('layouts.app', ['module_title' => 'movie tags', 'isSweetalert' => true, 'isSelect2' => false, 'isFlatpickr' => true])

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
                                        {{__('form.name')}} <span> *</span>
                                    </label>
                                    <input id="Name" type="text" class="form-control " placeholder="{{__('form.enter-name-tag')}}"
                                        required>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label class="form-label flex-grow-1" for="Slug">
                                        {{__('form.slug')}} <span> *</span>
                                    </label>
                                    <input id="Slug" type="text" class="form-control " placeholder="{{__('form.enter-slug-tag')}}"
                                        required>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label class="form-label flex-grow-1" for="Description">
                                        {{__('form.description')}}
                                    </label>
                                    <textarea id="Description" class="form-control" rows="7"
                                        placeholder="{{__('form.enter-tag-description')}}"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#video-modal">{{__('form.add-tag')}}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card pb-3">
                    <div class="card-header d-flex justify-content-between gap-3 flex-wrap align-items-center">
                        <h2 class="episode-playlist-title wp-heading-inline">
                            <span class="position-relative ">
                                {{__('form.tags')}} </span>
                        </h2>
                    </div>
                    <div class="card-body">
                        <div class="table-view table-space">
                            <table id="seasonTable" class="data-tables table custom-table movie_table data-table-one custom-table-height"
                            data-toggle="data-table1">
                            <thead>
                                <tr class="text-uppercase">
                                    <th class="">
                                        <input type="checkbox" class="form-check-input" />
                                    </th>
                                    <th>Term Name</th>
                                    <th>Term Slug</th>
                                    <th>Count</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @include('components.datatable.DataTable', [
                                    'term_name' => 'Action',
                                    'term_slug' => 'action',
                                    'count' => 19,
                                    'isTags' => true,
                                ])
                                @include('components.datatable.DataTable', [
                                    'term_name' => 'Thriller',
                                    'term_slug' => 'thriller',
                                    'count' => 2,
                                    'isTags' => true,
                                ])
                                @include('components.datatable.DataTable', [
                                    'term_name' => 'Sci-Fi',
                                    'term_slug' => 'sci-fi',
                                    'count' => 2,
                                    'isTags' => true,
                                ])
                                @include('components.datatable.DataTable', [
                                    'term_name' => 'Revenge',
                                    'term_slug' => 'revenge',
                                    'count' => 2,
                                    'isTags' => true,
                                ])
                                @include('components.datatable.DataTable', [
                                    'term_name' => 'Recommended',
                                    'term_slug' => 'recommended',
                                    'count' => 1,
                                    'isTags' => true,
                                ])
                                @include('components.datatable.DataTable', [
                                    'term_name' => 'Mystery',
                                    'term_slug' => 'mystery',
                                    'count' => 10,
                                    'isTags' => true,
                                ])
                                @include('components.datatable.DataTable', [
                                    'term_name' => 'Horror',
                                    'term_slug' => 'horror',
                                    'count' => 8,
                                    'isTags' => true,
                                ])
                                @include('components.datatable.DataTable', [
                                    'term_name' => 'Hitman',
                                    'term_slug' => 'hitman',
                                    'count' => 3,
                                    'isTags' => true,
                                ])
                                @include('components.datatable.DataTable', [
                                    'term_name' => 'History',
                                    'term_slug' => 'history',
                                    'count' => 2,
                                    'isTags' => true,
                                ])
                                @include('components.datatable.DataTable', [
                                    'term_name' => 'Fantasy',
                                    'term_slug' => 'fantasy',
                                    'count' => 3,
                                    'isTags' => true,
                                ])
                                @include('components.datatable.DataTable', [
                                    'term_name' => 'Family',
                                    'term_slug' => 'family',
                                    'count' => 3,
                                    'isTags' => true,
                                ])
                            </tbody>
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
                    <label class="form-label" for="name">{{__('form.name')}}<span> *</span></label>
                    <input type="text" class="form-control" id="name" value="{{__('form.name')}}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="Slug1">{{__('form.slug')}}<span> *</span></label>
                    <input type="text" class="form-control" id="Slug1" value="{{__('form.genremovie')}}">
                </div>
                <div class="form-group">
                    <label class="form-label flex-grow-1" for="Description1">
                        {{__('form.excerpt')}}
                    </label>
                    <textarea id="Description1" class="form-control" rows="7" placeholder="{{__('streamTag.genre')}}"></textarea>
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
