@extends('layouts.app', ['module_title' => 'Genres', 'isSweetalert' => true, 'isSelect2' => true])

@section('content')
<div class="row streamit-wraper-table2">
    <div class="col-lg-4">
        <div class="card pb-3">
            <div class="card-header">
                <h2 class="episode-playlist-title wp-heading-inline">
                    <span class="position-relative ">
                        {{__('form.add_new_genre')}} </span>
                </h2>
            </div>
            <div class="card-body">
                <form>
                    <div class="form-group">
                        <label class="form-label" for="genre-name">{{__('form.name')}}<span> *</span></label>
                        <input type="text" class="form-control" id="genre-name" placeholder="{{__('form.enter-title-genre')}}">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="genre-slug">{{__('form.slug')}}<span> *</span></label>
                        <input type="text" class="form-control" id="genre-slug" placeholder="{{__('form.enter-slug-genre')}}">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="parent-genre">{{__('form.parent-genre')}}</label>
                        <select id="parent-genre" class="form-control select2-basic-multiple">
                            <option>Add Parent Genre</option>
                            <option>Action</option>
                            <option>Adventure</option>
                            <option>Animation</option>
                            <option>Crime</option>
                            <option>Horror</option>
                            <option>Mystery</option>
                            <option>Romance</option>
                        </select>
                    </div>

                    @include('components.widget.UploadImageVideo', ['upload_image_name' => 'Thumbnail'])

                    <div class="form-group">
                        <label class="form-label">{{__('form.description')}}</label>
                        <textarea class="form-control large-text" aria-label="With textarea"></textarea>

                    </div>

                    <div class="d-flex align-items-center justify-content-end mt-4">
                        <button class="btn btn-primary">{{__('form.add-genre')}}</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="pb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="episode-playlist-title wp-heading-inline">
                        <span class="position-relative ">
                            {{__('streamTag.genre')}}</span>
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
                                    <th class="">Parent Category</th>
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
                                'viewsValue' => true,
                                ])

                                @include('components.datatable.DataTable', [
                                'title' => 'Mystery',
                                'thumbnail' => 'genres/mystery.webp',
                                'author' => 'mystery',
                                'date' => 'None',
                                'views' => '14',
                                'viewsValue' => true,
                                ])

                                @include('components.datatable.DataTable', [
                                'title' => 'Horror',
                                'thumbnail' => 'genres/horror.webp',
                                'author' => 'horror',
                                'date' => 'None',
                                'views' => '13',
                                'viewsValue' => true,
                                ])

                                @include('components.datatable.DataTable', [
                                'title' => 'Crime',
                                'thumbnail' => 'genres/Crime.webp',
                                'author' => 'crime',
                                'date' => 'None',
                                'views' => '10',
                                'viewsValue' => true,
                                ])

                                @include('components.datatable.DataTable', [
                                'title' => 'Animation',
                                'thumbnail' => 'genres/animation.webp',
                                'author' => 'animation',
                                'date' => 'None',
                                'views' => '10',
                                'viewsValue' => true,
                                ])

                                @include('components.datatable.DataTable', [
                                'title' => 'Adventure',
                                'thumbnail' => 'genres/adventure.webp',
                                'author' => 'adventure',
                                'date' => 'None',
                                'views' => '18',
                                'viewsValue' => true,
                                ])

                                @include('components.datatable.DataTable', [
                                'title' => 'Action',
                                'thumbnail' => 'genres/action.webp',
                                'author' => 'action',
                                'date' => 'None',
                                'views' => '22',
                                'viewsValue' => true,
                                ])
                            </tbody>
                        </table>
                    </div>
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
                {{__('form.update_genre')}}</span>
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
                    <label class="form-label" for="Slug">{{__('form.slug')}}<span> *</span></label>
                    <input type="text" class="form-control" id="Slug" value="{{__('form.genremovie')}}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="parent-category">{{__('form.parent-category')}}</label>
                    <select id="parent-category" class="form-control select2-basic-multiple">
                        <option>Add Parent Category</option>
                        <option selected="">Actor</option>
                        <option>Actress</option>
                        <option>Director</option>
                        <option>Producer</option>
                        <option>Singer</option>
                    </select>
                </div>

                @include(
                'components.widget.UploadImageVideo',
                [
                'upload_image_name' => __('form.thumbnail'),
                'isUploadImageDefault' => true,
                ]
                )

                <div class="form-group">
                    <label class="form-label flex-grow-1" for="Description">
                        {{__('form.excerpt')}}</label>
                    <textarea id="Description" class="form-control" rows="7" placeholder="{{__('streamTag.genre')}}"></textarea>
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