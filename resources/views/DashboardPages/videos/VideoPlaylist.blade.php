@extends('layouts.app', ['module_title' => 'videoplaylist', 'isSweetalert' => true, 'Activelink' => 'videoplaylist', 'isFlatpickr' => true, 'isQuillEditor' => true, 'isSelect2' => true])

@section('content')
<div class="row streamit-wraper-table2">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h2 class="episode-playlist-title wp-heading-inline">
                    <span class="position-relative ">
                        {{__('form.add-new-video-playlist')}} </span>
                </h2>
            </div>
            <div class="card-body">
                <form>
                    <div class="form-group">
                        <label class="form-label" for="playlist-name">{{__('form.playlist_name')}}<span> *</span></label>
                        <input type="text" class="form-control" id="playlist-name"
                            placeholder="{{__('form.enter-name-video-playlist')}}">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="playlist-slug">{{__('form.playlist_slug')}}<span> *</span></label>
                        <input type="text" class="form-control" id="playlist-slug"
                            placeholder="Enter the slug of the video playlist.">
                    </div>
                    <div class="d-flex align-items-center justify-content-end mt-4">
                        <button class="btn btn-primary">{{__('form.add-new-playlist')}}</button>
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
                            {{__('form.video-playlists')}} </span>
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
                                    <th class="">{{__('form.playlist_name')}} </th>
                                    <th class="">Playlist Slug</th>
                                    <th class="">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @include('components.datatable.DataTable', [
                                    'isPlaylist' => true,
                                    'playlist_name' => 'My Favorite Videos',
                                    'playlist_slug' => 'myfavoritevideos',
                                ])

                                @include('components.datatable.DataTable', [
                                    'isPlaylist' => true,
                                    'playlist_name' => 'Knowledge Hub',
                                    'playlist_slug' => 'knowledgehub',
                                ])

                                @include('components.datatable.DataTable', [
                                    'isPlaylist' => true,
                                    'playlist_name' => 'Love Stories',
                                    'playlist_slug' => 'lovestories',
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
                {{__('form.update-video-playlist')}}</span>
        </h2>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <form>
            <div class="section-form">
                <div class="form-group">
                    <label class="form-label" for="playlist-name1">{{__('form.playlist_name')}} *</label>
                    <input type="text" class="form-control" id="playlist-name1"
                        placeholder="{{__('form.enter-title-genre')}}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="playlist-slug1">{{__('form.playlist_slug')}} *</label>
                    <input type="text" class="form-control" id="playlist-slug1"
                        placeholder="{{__('form.enter-slug-genre')}}">
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
