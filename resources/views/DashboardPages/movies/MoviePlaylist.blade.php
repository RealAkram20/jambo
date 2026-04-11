@extends('layouts.app', ['module_title' => 'play list', 'isSweetalert' => true, 'isSelect2' => false, 'isFlatpickr' => true])

@section('content')
    <div class="row streamit-wraper-table2">
        <div class="col-lg-4">
            <div class="card pb-3">
                <div class="card-header">
                    <h2 class="episode-playlist-title wp-heading-inline">
                        <span class="position-relative ">
                            {{__('form.add-new-playlist')}} </span>
                    </h2>
                </div>
                <div class="card-body">
                    <form>
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label class="form-label flex-grow-1" for="Name">
                                        {{__('form.playlist_name')}} <span> *</span>
                                    </label>
                                    <input id="Name" type="text" class="form-control "
                                        placeholder="{{__('form.enter-name-playlist')}}" required>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label class="form-label flex-grow-1" for="Slug">
                                        {{__('form.playlist_slug')}} <span> *</span>
                                    </label>
                                    <input id="Slug" type="text" class="form-control "
                                        placeholder="{{__('form.enter-slug-playlist')}}" required>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#video-modal">{{__('streamButtons.add_playlist')}}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card pb-3">
                <div class="card-header">
                    <h2 class="episode-playlist-title wp-heading-inline">
                        <span class="position-relative ">
                            {{__('sidebar.playlist')}} </span>
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
                                    <th>Playlist Name</th>
                                    <th>Playlist Slug</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @include('components.datatable.DataTable', ['playlist_name' => 'Romantic', 'playlist_slug' => 'romantic', 'isPlaylist' => true])
                                @include('components.datatable.DataTable', ['playlist_name' => 'Road Trip Movies', 'playlist_slug' => 'road-trip-movies', 'isPlaylist' => true])
                                @include('components.datatable.DataTable', ['playlist_name' => 'My Movie', 'playlist_slug' => 'my-movie', 'isPlaylist' => true])
                                @include('components.datatable.DataTable', ['playlist_name' => 'My list', 'playlist_slug' => 'my-list', 'isPlaylist' => true])

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- edit  --}}
    <div class="offcanvas offcanvas-end offcanvas-width-80" tabindex="-1" id="season-offcanvas-edit">
        <div class="offcanvas-header">
            <h2 class="episode-playlist-title wp-heading-inline">
                <span class="position-relative ">
                   {{__('form.update_playlist')}} </span>
            </h2>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <form>
                <div class="section-form">
                    <div class="form-group">
                        <label class="form-label" for="name">{{__('form.playlist_name')}} <span> *</span></label>
                        <input type="text" class="form-control" id="name" value="{{__('form.name')}}">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="Slug1">{{__('form.playlist_slug')}} <span> *</span></label>
                        <input type="text" class="form-control" id="Slug1" value="{{__('form.genremovie')}}">
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
