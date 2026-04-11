@extends('layouts.app', ['module_title' => 'episodeplaylist', 'isSweetalert' => true, 'Activelink' => 'episodeplaylist', 'isFlatpickr' => true, 'isQuillEditor' => true, 'isSelect2' => true])


@section('content')
    <div class="row streamit-wraper-table2">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h2 class="episode-playlist-title wp-heading-inline">
                        <span class="position-relative ">
                            {{__('form.add-new-playlist')}} </span>
                    </h2>
                </div>
                <div class="card-body">
                    <form>
                        <div class="form-group">
                            <label class="form-label" for="playlist-name">{{__('form.playlist_name')}} <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="playlist-name"
                                placeholder="{{__('form.enter-name-playlist')}}">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="playlist-slug">{{__('form.playlist_slug')}} <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="playlist-slug"
                                placeholder="{{__('form.enter-slug-playlist')}}">
                        </div>
                        <div class="d-flex align-items-center justify-content-end mt-4">
                            <button class="btn btn-primary">{{__('streamButtons.add_playlist')}}</button>
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
                                {{__('sidebar.playlist')}} </span>
                        </h2>
                    </div>
                    <div class="card-body">
                        <div class="table-view table-space">
                            <table id="seasonTable"
                                class="data-tables table custom-table movie_table data-table-one custom-table-height"
                                data-toggle="data-table1">
                                <thead>
                                    <tr class=" text-uppercase">
                                        <th class="">
                                            <input type="checkbox" class="form-check-input" />
                                        </th>
                                        <th class="">Playlist Name</th>
                                        <th class="">Playlist Slug</th>
                                        <th class="">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>

                                @include('components.datatable.DataTable', [
                                  'isPlaylist' => true,
                                    'playlist_name' => "Radhe Krishna Stories",
                                    'playlist_slug' => "radhe-krishna-stories"
                                ])

                                @include('components.datatable.DataTable', [
                                    'isPlaylist' => true,
                                    'playlist_name' => "episodes of pius",
                                    'playlist_slug' => "episodes-of-pius"
                                ])

                                @include('components.datatable.DataTable', [
                                    'isPlaylist' => true,
                                    'playlist_name' => "horror",
                                    'playlist_slug' => "horror"
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
                   {{__('form.update_playlist')}} </span>
            </h2>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <form>
                <div class="section-form">
                    <div class="form-group">
                        <label class="form-label" for="name">{{__('form.playlist_name')}} <span
                                class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" value="{{__('form.admin')}}">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="Slug">{{__('form.playlist_slug')}} <span
                                class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="Slug" value="{{__('form.admin')}}">
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
