<div class="playlist-card">
    <!-- Playlist Image -->
    <div class="image-box">
        <a href="playlist-detail">
            <img src="{{asset('frontend/images/'. $profileCardImage)}}" alt="90s Throwback"
                class="img-fluid object-cover w-100 border-0">
        </a>
        <a href="playlist-detail" class="play-icon">
            <i class="icon-play-button"></i>
        </a>
    </div>
    <!-- Playlist Content -->
    <div class="content-part">
        <div class="d-flex justify-content-between gap-2 mb-1">
            <h5 class="my-0 text-capitalize">{{$movieType}}</h5>
            <div class="dropdown">
                <button class="btn p-0 border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="icon-three-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu border">
                    <li><a data-playlist-name="90s Throwback" data-playlist-id="14" data-post-type="movie"
                            class="manage_playlist dropdown-item update_user_playlist">Update</a>
                    </li>
                    <li><a data-playlist_id="14" data-post-type="movie"
                            class="dropdown-item delete_user_playlist">Delete</a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="small mb-1">
            <small>
                {{$movieCategory}}
            </small>
        </div>
        <a href="playlist-detail" class="btn btn-link btn-playlist p-0 border-radius-0">{{__('streamPlaylist.view_playlist')}}</a>
    </div>
</div>
