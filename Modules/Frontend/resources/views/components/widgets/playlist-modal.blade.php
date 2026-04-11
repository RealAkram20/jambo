<div class="modal fade view-more-data-modal" id="playlistModal" tabindex="-1" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered share-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">{{__('streamPlaylist.select_playlist')}}</h5>
                <button type="button" class="btn-close me-0" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-0">
                <div class="playlist-modal-content">
                    <div class="form-check"><input id="26" class="st_manage_playlist form-check-input" type="checkbox"
                            data-playlist_id="26" data-post_id="32" data-post_type="movie"><label for="26">222</label>
                    </div>
                    <div class="form-check"><input id="14" class="st_manage_playlist form-check-input" type="checkbox"
                            data-playlist_id="14" data-post_id="32" data-post_type="movie"><label for="14">{{__('streamPlaylist.90s_throwback')}}</label></div>
                    <div class="form-check"><input id="11" class="st_manage_playlist form-check-input" type="checkbox"
                            data-playlist_id="11" data-post_id="32" data-post_type="movie" checked=""><label
                            for="11">{{__('streamTag.action')}}</label>
                    </div>
                    <div class="form-check"><input id="10" class="st_manage_playlist form-check-input" type="checkbox"
                            data-playlist_id="10" data-post_id="32" data-post_type="movie" checked=""><label
                            for="10">{{__('streamPlaylist.blockbuster')}}</label>
                    </div>
                    <div class="form-check"><input id="9" class="st_manage_playlist form-check-input" type="checkbox"
                            data-playlist_id="9" data-post_id="32" data-post_type="movie"><label for="9">{{__('streamTag.drama')}}</label>
                    </div>
                    <div class="form-check"><input id="13" class="st_manage_playlist form-check-input" type="checkbox"
                            data-playlist_id="13" data-post_id="32" data-post_type="movie"><label
                            for="13">{{__('streamTag.horror')}}</label></div>
                    <div class="form-check"><input id="15" class="st_manage_playlist form-check-input" type="checkbox"
                            data-playlist_id="15" data-post_id="32" data-post_type="movie"><label for="15">{{__('streamPlaylist.road_trip_movies')}}</label>
                    </div>
                    <div class="form-check"><input id="12" class="st_manage_playlist form-check-input" type="checkbox"
                            data-playlist_id="12" data-post_id="32" data-post_type="movie"><label for="12">{{__('streamPlaylist.playlist_movie_romantic')}}</label></div>
                </div>
            </div>
            <div class="modal-footer border-0 p-4">
                <button type="button" class="playlist-action-btn btn btn-secondary border" data-bs-toggle="modal"
                    data-bs-target="#creatplaylistModal">
                    {{__('streamPlaylist.create_playlist')}} </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade view-more-data-modal" id="creatplaylistModal" tabindex="-1" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered share-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel1">{{__('streamPlaylist.create_playlist')}}</h5>
                <button type="button" class="btn-close me-0" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <form id="st_creat_playlist" action="#" method="post">
                    <input type="hidden" id="st_playlist_post_type" value="movie">
                    <div class="form-group mb-4">
                        <label class="form-label">{{__('streamPlaylist.playlist_title')}}</label>
                        <span> *</span>
                        <input class="form-control" type="text" id="st_playlist_title" value="">
                    </div>
                    <div class="iq-button d-flex justify-content-end">
                        <button type="button" class="btn btn-secondary position-relative" data-bs-toggle="modal"
                            data-bs-target="#addNewPlaylist">
                            <span class="button-text">{{__('streamPlaylist.create_playlist')}}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
