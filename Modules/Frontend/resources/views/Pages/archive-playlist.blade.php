@extends('frontend::layouts.master', ['isBreadCrumb' => true, 'title' => __('frontendheader.playlist'), 'bodyClass' => 'custom-header-relative', 'isSelect2' => true, 'isSweetalert' => true, 'isFslightbox' => true])

@section('content')
    <section class="section-padding">
        <div class="container-fluid">
            <div class="play-lists">
                <div class="row g-2 column-reverce align-items-center border-bottom playlist-bottom-margin">
                    <div class="col-8 col-sm-9 col-lg-10">
                        <div id="item-nav">
                            <div class="item-list-tabs no-ajax css_prefix-tab-lists" id="object-nav">

                                <!-- Playlist Tabs -->
                                <ul class="nav nav-underline data-search-tab" id="pills-tab" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="pills-movie-tab" data-bs-toggle="pill"
                                            data-bs-target="#pills-movie" type="button" role="tab"
                                            aria-controls="pills-movie" aria-selected="true">
                                            {{ __('frontendheader.movie') }} </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link " id="pills-video-tab" data-bs-toggle="pill"
                                            data-bs-target="#pills-video" type="button" role="tab"
                                            aria-controls="pills-video" aria-selected="false" tabindex="-1">
                                            {{ __('frontendheader.video') }} </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link " id="pills-episode-tab" data-bs-toggle="pill"
                                            data-bs-target="#pills-episode" type="button" role="tab"
                                            aria-controls="pills-episode" aria-selected="false" tabindex="-1">
                                            {{ __('frontendheader.episode') }} </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-4 col-sm-3 col-lg-2">
                        <div class="d-flex justify-content-end">
                            <button type="button" class="manage_playlist action-btn btn btn-link" data-bs-toggle="modal"
                                data-bs-target="#creatplaylistModal">
                                <span class="h-100 w-100 d-block" data-bs-toggle="tooltip" data-bs-placement="top"
                                    data-bs-title="Playlist">
                                    {{ __('streamButtons.add_playlist') }} </span>
                            </button>

                            <div class="modal fade view-more-data-modal edit-profile-modal" id="creatplaylistModal"
                                tabindex="-1" aria-modal="true" role="dialog">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="createPlaylist">
                                                {{ __('streamPlaylist.create_playlist') }}
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body pt-0">
                                            <form id="st_creat_playlist" action="#" method="post">
                                                <input id="st_playlist_id" type="hidden" value="">
                                                <div class="form-group mb-4">
                                                    <label class="form-label">{{ __('streamPlaylist.playlist_title') }}</label>
                                                    <span> *</span>
                                                    <input class="form-control" type="text" id="st_playlist_title"
                                                        value="">
                                                </div>
                                                <div class="form-group playlist-select mb-4">
                                                    <label
                                                        class="form-label">{{ __('streamPlaylist.select_playlist_type') }}</label>
                                                    <span> *</span>
                                                    <select name="movieselect"
                                                        class="form-control movie-select select2-basic-single js-states">
                                                        <option value="1">{{ __('frontendheader.movie') }}</option>
                                                        <option value="2">{{ __('frontendheader.video') }}</option>
                                                        <option value="3">{{ __('frontenddetail_page.Episode') }}
                                                        </option>
                                                    </select>
                                                </div>
                                                <div class="iq-button">
                                                    <button type="button"
                                                        class="btn btn-primary position-relative rounded-3"
                                                        data-bs-toggle="modal" data-bs-target="#addNewPlaylist">
                                                        <span
                                                            class="button-text">{{ __('streamPlaylist.create_playlist') }}</span>
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Playlist Tab Content -->
                <div class="tab-content" id="pills-tabContent">
                    <div class="tab-pane fade active show" id="pills-movie" role="tabpanel" tabindex="0"
                        aria-labelledby="pills-movie-tab">
                        <div
                            class="row gy-4 row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-3 row-cols-xl-4 row-cols-xxl-5 ">
                            <div class="col">
                                @include('frontend::components.widgets.profile-card', [
                                    'movieCategory' => '4 Movies',
                                    'movieType' => __('streamPlaylist.90s_throwback'),
                                    'profileCardImage' => 'media/migration.webp',
                                ])
                            </div>
                            <div class="col">
                                @include('frontend::components.widgets.profile-card', [
                                    'movieCategory' => '3 Movies',
                                    'movieType' => __('streamTag.action'),
                                    'profileCardImage' => 'media/jumanjj.webp',
                                ])
                            </div>
                            <div class="col">
                                @include('frontend::components.widgets.profile-card', [
                                    'movieCategory' => '3 Movies',
                                    'movieType' => __('profile.blockbuster_trt'),
                                    'profileCardImage' => 'media/chosfies.webp',
                                ])
                            </div>
                            <div class="col">
                                @include('frontend::components.widgets.profile-card', [
                                    'movieCategory' => '2 Movies',
                                    'movieType' => __('frontendblogs.Dramas'),
                                    'profileCardImage' => 'media/bumblebee.webp',
                                ])
                            </div>
                            <div class="col">
                                @include('frontend::components.widgets.profile-card', [
                                    'movieCategory' => '1 Movies',
                                    'movieType' => __('streamTag.horror'),
                                    'profileCardImage' => 'media/venom.webp',
                                ])
                            </div>
                            <div class="col">
                                @include('frontend::components.widgets.profile-card', [
                                    'movieCategory' => '1 Movies',
                                    'movieType' => __('streamPlaylist.playlist_movie_road_trip'),
                                    'profileCardImage' => 'media/rabbit.webp',
                                ])
                            </div>
                            <div class="col">
                                @include('frontend::components.widgets.profile-card', [
                                    'movieCategory' => '2 Movies',
                                    'movieType' => __('streamPlaylist.playlist_movie_romantic'),
                                    'profileCardImage' => 'media/yoshi.webp',
                                ])
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="pills-tvshow" role="tabpanel" tabindex="0">
                        <div
                            class="row gy-4 row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-3 row-cols-xl-4 row-cols-xxl-5 ">
                            <p class="text-center w-100">{{ __('streamPlaylist.no_playlist_available') }}</p>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="pills-video" role="tabpanel" tabindex="0"
                        aria-labelledby="pills-video-tab">
                        <div
                            class="row gy-4 row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-3 row-cols-xl-4 row-cols-xxl-5 ">
                            <div class="col">
                                @include('frontend::components.widgets.profile-card', [
                                    'movieCategory' => '3 Videos',
                                    'movieType' => __('streamPlaylist.playlist_video_chill_relax'),
                                    'profileCardImage' => 'media/krishna.webp',
                                ])
                            </div>
                            <div class="col">
                                @include('frontend::components.widgets.profile-card', [
                                    'movieCategory' => '2 Videos',
                                    'movieType' => __('streamPlaylist.playlist_video_love_animals'),
                                    'profileCardImage' => 'media/kung-fu-panda.webp',
                                ])
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="pills-episode" role="tabpanel" tabindex="0"
                        aria-labelledby="pills-episode-tab">
                        <div
                            class="row gy-4 row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-3 row-cols-xl-4 row-cols-xxl-5 ">
                            <div class="col">
                                @include('frontend::components.widgets.profile-card', [
                                    'movieCategory' => '2 Episodes',
                                    'movieType' => __('streamPlaylist.playlist_episode_unlimited'),
                                    'profileCardImage' => 'media/minions-2.webp',
                                ])
                            </div>
                            <div class="col">
                                @include('frontend::components.widgets.profile-card', [
                                    'movieCategory' => '1 Episode',
                                    'movieType' => __('streamPlaylist.playlist_episode_radhe_krishna'),
                                    'profileCardImage' => 'media/krishna.webp',
                                ])
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
