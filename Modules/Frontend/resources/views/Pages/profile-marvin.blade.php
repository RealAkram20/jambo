@extends('frontend::layouts.master', ['isSelect2' => true])

@section('content')
    <section class="section-padding profile-section-padding">
        <div class="container-fluid">
            <!-- user profile start -->
            <div class="px-sm-5 px-3 py-4 rounded-3 profile-user-info">
                <div class="row align-items-center">
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center flex-wrap gap-3">
                            <div class="profile-image flex-shrink-0">
                                <img src="{{ asset('frontend/images/user/user6.jpg') }}" alt="Marvin McKinney"
                                    class="user-image user-profile-image">
                            </div>
                            <div class="profile-info">
                                <h5 class="mt-0 info-title">{{__('frontendheader.user_name')}}</h5>
                                <p class="mb-1 mt-0">marvin@demo.com</p>
                                <p class="m-0">{{__('streamShop.marvin')}}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 mt-sm-0 mt-3">
                        <div class="d-flex align-items-center justify-content-sm-end gap-3">
                            <button type="button" class="btn btn-sm custom-btn-sm btn-primary text-nowrap fw-semibold"
                                data-bs-toggle="modal" data-bs-target="#edit-profile-modal">
                                <i class="icon-edit-icon"></i> {{__('streamButtons.edit_profile')}} </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-5">
                <div class="col-xl-2 col-lg-3">
                    <ul class="profile-page-list list-inline p-0 mx-0 nav-tabs" role="tablist">
                        <li class="profile-page-list-item">
                            <a href="javascript:void(0)" class="profile-page-list-link active" data-bs-toggle="tab"
                                data-bs-target="#playlist-tab" role="tab" aria-selected="true">
                                {{__('frontendheader.playlist')}} </a>
                        </li>
                        <li class="profile-page-list-item">
                            <a href="javascript:void(0)" class="profile-page-list-link" data-bs-toggle="tab"
                                data-bs-target="#watchlist-tab" role="tab" aria-selected="true">
                                {{__('frontendheader.watch_list')}} </a>
                        </li>
                        <li class="profile-page-list-item">
                            <a href="javascript:void(0)" class="profile-page-list-link" data-bs-toggle="tab"
                                data-bs-target="#notification-tab" role="tab" aria-selected="true">
                                {{__('frontendheader.notification')}} </a>
                        </li>
                        <li class="profile-page-list-item">
                            <a href="javascript:void(0)" class="profile-page-list-link" data-bs-toggle="tab"
                                data-bs-target="#membership-tab" role="tab" aria-selected="true">
                                {{__('sectionTitle.membership')}} </a>
                        </li>
                    </ul>
                </div>

                <div class="col-xl-10 col-lg-9 mt-5 mt-lg-0">
                    <div class="tab-content" id="profile-menu-content">
                        <div class="tab-pane fade show active" id="playlist-tab" role="tabpanel">
                            <div class="play-lists">
                                <div
                                    class="row g-2 column-reverce align-items-center border-bottom playlist-bottom-margin">
                                    <div class="col-8 col-sm-9 col-lg-10">
                                        <div id="item-nav">
                                            <div class="item-list-tabs no-ajax css_prefix-tab-lists" id="object-nav">

                                                <!-- Playlist Tabs -->
                                                <ul class="nav nav-underline data-search-tab" id="pills-tab" role="tablist">
                                                    <li class="nav-item" role="presentation">
                                                        <button class="nav-link active" id="pills-movie-tab"
                                                            data-bs-toggle="pill" data-bs-target="#pills-movie"
                                                            type="button" role="tab" aria-controls="pills-movie"
                                                            aria-selected="true">
                                                            {{__('frontendheader.movie')}} </button>
                                                    </li>
                                                    <li class="nav-item" role="presentation">
                                                        <button class="nav-link " id="pills-video-tab" data-bs-toggle="pill"
                                                            data-bs-target="#pills-video" type="button" role="tab"
                                                            aria-controls="pills-video" aria-selected="false"
                                                            tabindex="-1">
                                                            {{__('frontendheader.video')}} </button>
                                                    </li>
                                                    <li class="nav-item" role="presentation">
                                                        <button class="nav-link " id="pills-episode-tab"
                                                            data-bs-toggle="pill" data-bs-target="#pills-episode"
                                                            type="button" role="tab" aria-controls="pills-episode"
                                                            aria-selected="false" tabindex="-1">
                                                            {{__('frontenddetail_page.Episode')}} </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4 col-sm-3 col-lg-2">
                                        <div class="d-flex justify-content-md-end mb-md-0 mb-1">
                                            <button type="button" class="manage_playlist  btn btn-link"
                                                data-bs-toggle="modal" data-bs-target="#creatplaylistModal">
                                                <span class="h-100 w-100 d-block" data-bs-toggle="tooltip"
                                                    data-bs-placement="top" data-bs-title="Playlist">
                                                    {{__('streamButtons.add_playlist')}} </span>
                                            </button>

                                            <div class="modal fade view-more-data-modal edit-profile-modal"
                                                id="creatplaylistModal" tabindex="-1" aria-modal="true" role="dialog">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="exampleModalLabelplaylist">
                                                                {{__('streamPlaylist.create_playlist')}}
                                                            </h5>
                                                            <button type="button" class="btn-close me-0"
                                                                data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body pt-0">
                                                            <form id="st_creat_playlist">
                                                                <input id="st_playlist_id" type="hidden" value="">
                                                                <div class="form-group mb-4">
                                                                    <label class="form-label">{{__('streamPlaylist.playlist_title')}}</label>
                                                                    <span> *</span>
                                                                    <input class="form-control" type="text"
                                                                        id="st_playlist_title" value="">
                                                                </div>
                                                                <div class="form-group playlist-select mb-4">
                                                                    <label class="form-label">{{__('streamPlaylist.select_playlist')}}</label>
                                                                    <span> *</span>
                                                                    <select  required="required"
                                                                        class="select2-basic-single js-states form-control">
                                                                        <option value="1">{{__('frontendheader.movie')}}</option>
                                                                        <option value="2">{{__('frontendheader.video')}}</option>
                                                                        <option value="3">{{__('frontenddetail_page.Episode')}}</option>
                                                                    </select>
                                                                </div>
                                                                <div class="iq-button">
                                                                    <button type="button"
                                                                        class="btn btn-primary text-capitalize position-relative rounded-3"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#addNewPlaylist">
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
                                    <div class="tab-pane fade active show" id="pills-movie" role="tabpanel"
                                        tabindex="0" aria-labelledby="pills-movie-tab">
                                        <div
                                            class="row gy-4 row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-3 row-cols-xl-4 row-cols-xxl-5 data-listing">
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
                                                    'movieType' => __('streamPlaylist.playlist_movie_blockbuster_trt'),
                                                    'profileCardImage' => 'media/chosfies.webp',
                                                ])

                                            </div>
                                            <div class="col">
                                                @include('frontend::components.widgets.profile-card', [
                                                    'movieCategory' => '2 Movies',
                                                    'movieType' => __('streamTag.drama'),
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
                                            <div class="col hidden-tags" style="display: none;">
                                                @include('frontend::components.widgets.profile-card', [
                                                    'movieCategory' => '1 Movies',
                                                    'movieType' => __('streamPlaylist.playlist_movie_road_trip'),
                                                    'profileCardImage' => 'media/rabbit.webp',
                                                ])

                                            </div>
                                            <div class="col hidden-tags" style="display: none;">
                                                @include('frontend::components.widgets.profile-card', [
                                                    'movieCategory' => '2 Movies',
                                                    'movieType' => __('streamPlaylist.playlist_movie_romantic'),
                                                    'profileCardImage' => 'media/yoshi.webp',
                                                ])

                                            </div>
                                        </div>
                                        <div class="text-center mt-4">
                                            <div class="iq-button">
                                                <a href="javascript:void(0)"
                                                    class="btn btn-primary text-capitalize position-relative load-more-btn rounded-3">
                                                    <span class="button-text">{{ __('streamButtons.load_more') }}</span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="tab-pane fade" id="pills-tvshow" role="tabpanel" tabindex="0">
                                        <div
                                            class="row gy-4 row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-3 row-cols-xl-4 row-cols-xxl-5 ">
                                            <p class="text-center w-100">{{__('streamMovies.no_playlists_available')}}</p>
                                        </div>
                                    </div>

                                    <div class="tab-pane fade" id="pills-video" role="tabpanel" tabindex="0"
                                        aria-labelledby="pills-video-tab">
                                        <div class="row gy-4 row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-3 row-cols-xl-4 row-cols-xxl-5 ">
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
                        <div class="tab-pane fade" id="watchlist-tab" role="tabpanel">
                            <div class="col-md-12">
                                <div class="border-bottom mb-5 watchlist-tab">
                                    <div id="item-nav">
                                        <div class="item-list-tabs no-ajax css_prefix-tab-lists" id="object-nav">

                                            <!-- Watchlist Tabs -->
                                            <ul class="nav nav-underline data-search-tab" id="pills-tab" role="tablist">
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link active" id="pills-movie-tab"
                                                        data-bs-toggle="pill" data-bs-target="#pills-movie1"
                                                        type="button" role="tab" aria-controls="pills-movie1"
                                                        aria-selected="true">
                                                        {{ __('frontendheader.movie') }} </button>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link " id="pills-video-tab" data-bs-toggle="pill"
                                                        data-bs-target="#pills-video1" type="button" role="tab"
                                                        aria-controls="pills-video1" aria-selected="false"
                                                        tabindex="-1">
                                                        {{ __('frontendheader.video') }} </button>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link " id="pills-tvshow-tab" data-bs-toggle="pill"
                                                        data-bs-target="#pills-tvshow1" type="button" role="tab"
                                                        aria-controls="pills-tvshow1" aria-selected="false"
                                                        tabindex="-1">
                                                        {{ __('frontendheader.tv_show') }} </button>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link " id="pills-episode-tab"
                                                        data-bs-toggle="pill" data-bs-target="#pills-episode1"
                                                        type="button" role="tab" aria-controls="pills-episode1"
                                                        aria-selected="false" tabindex="-1">
                                                        {{ __('frontenddetail_page.Episode') }} </button>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <!-- Playlist Tab Content -->
                                <div class="tab-content" id="pills-tabContent-watch">
                                    <div class="tab-pane fade active show" id="pills-movie1" role="tabpanel"
                                        tabindex="0" aria-labelledby="pills-movie-tab">
                                        <div
                                            class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-3 row-cols-xl-4 row-cols-xxl-5 data-listing">
                                            <div class="col">

                                                @include(
                                                    'frontend::components.widgets.watchlist-detail-card',
                                                    [
                                                        'cardImage' => 'media/gameofhero.webp',
                                                        'movieName' => __('streamMovies.game_of_heros'),
                                                    ]
                                                )
                                            </div>
                                            <div class="col">

                                                @include(
                                                    'frontend::components.widgets.watchlist-detail-card',
                                                    [
                                                        'cardImage' => 'media/rabbit.webp',
                                                        'movieName' => __('streamMovies.rabbit'),
                                                    ]
                                                )
                                            </div>
                                            <div class="col">

                                                @include(
                                                    'frontend::components.widgets.watchlist-detail-card',
                                                    [
                                                        'cardImage' => 'media/migration.webp',
                                                        'movieName' => __('streamMovies.migration'),
                                                    ]
                                                )
                                            </div>
                                            <div class="col">

                                                @include(
                                                    'frontend::components.widgets.watchlist-detail-card',
                                                    [
                                                        'cardImage' => 'media/krishna.webp',
                                                        'movieName' => __('streamMovies.krishna'),
                                                    ]
                                                )
                                            </div>
                                            <div class="col">

                                                @include(
                                                    'frontend::components.widgets.watchlist-detail-card',
                                                    [
                                                        'cardImage' => 'media/jumanjj.webp',
                                                        'movieName' => __('streamMovies.jumanji'),
                                                    ]
                                                )
                                            </div>
                                            <div class="col">

                                                @include(
                                                    'frontend::components.widgets.watchlist-detail-card',
                                                    [
                                                        'cardImage' => 'media/yoshi.webp',
                                                        'movieName' => __('streamMovies.yoshi'),
                                                    ]
                                                )
                                            </div>
                                            <div class="col">

                                                @include(
                                                    'frontend::components.widgets.watchlist-detail-card',
                                                    [
                                                        'cardImage' => 'media/kung-fu-panda.webp',
                                                        'movieName' => __('streamMovies.kung_fu_panda'),
                                                    ]
                                                )
                                            </div>
                                            <div class="col">

                                                @include(
                                                    'frontend::components.widgets.watchlist-detail-card',
                                                    [
                                                        'cardImage' => 'media/the-hunter.webp',
                                                        'movieName' => __('streamMovies.the_hunter'),
                                                    ]
                                                )
                                            </div>
                                        </div>
                                    </div>

                                    <div class="tab-pane fade" id="pills-video1" role="tabpanel" tabindex="0"
                                        aria-labelledby="pills-video-tab">
                                        <div
                                            class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-3 row-cols-xl-4 row-cols-xxl-5 data-listing">
                                            <div class="col">

                                                @include(
                                                    'frontend::components.widgets.watchlist-detail-card',
                                                    [
                                                        'cardImage' => 'media/toddler.webp',
                                                        'movieName' => __('streamMovies.toddler'),
                                                    ]
                                                )
                                            </div>
                                        </div>
                                    </div>

                                    <div class="tab-pane fade" id="pills-tvshow1" role="tabpanel" tabindex="0">
                                        <div
                                            class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-3 row-cols-xl-4 row-cols-xxl-5 data-listing">
                                            <div class="col">

                                                @include(
                                                    'frontend::components.widgets.watchlist-detail-card',
                                                    [
                                                        'cardImage' => 'media/the-first-of-us.webp',
                                                        'movieName' => __('streamMovies.the_first_of_us'),
                                                    ]
                                                )
                                            </div>
                                            <div class="col">

                                                @include(
                                                    'frontend::components.widgets.watchlist-detail-card',
                                                    [
                                                        'cardImage' => 'media/migration.webp',
                                                        'movieName' => __('streamEpisode.episode7'),
                                                    ]
                                                )
                                            </div>
                                            <div class="col">

                                                @include(
                                                    'frontend::components.widgets.watchlist-detail-card',
                                                    [
                                                        'cardImage' => 'media/spiderman.webp',
                                                        'movieName' => __('streamEpisode.episode39'),
                                                    ]
                                                )
                                            </div>
                                            <div class="col">

                                                @include(
                                                    'frontend::components.widgets.watchlist-detail-card',
                                                    [
                                                        'cardImage' => 'media/minions.webp',
                                                        'movieName' => __('streamMovies.minions'),
                                                    ]
                                                )
                                            </div>
                                            <div class="col">

                                                @include(
                                                    'frontend::components.widgets.watchlist-detail-card',
                                                    [
                                                        'cardImage' =>
                                                            'media/pirates-ofdayones-orignal.webp',
                                                        'movieName' => __('streamMovies.pirates_of_day_one'),
                                                    ]
                                                )
                                            </div>
                                            <div class="col">

                                                @include(
                                                    'frontend::components.widgets.watchlist-detail-card',
                                                    [
                                                        'cardImage' => 'media/assassins-creed.webp',
                                                        'movieName' => __('streamMovies.assassins_creed'),
                                                    ]
                                                )
                                            </div>
                                        </div>
                                    </div>

                                    <div class="tab-pane fade" id="pills-episode1" role="tabpanel" tabindex="0"
                                        aria-labelledby="pills-episode-tab">
                                        <div
                                            class="row gy-4 row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-3 row-cols-xl-4 row-cols-xxl-5 ">
                                            <p class="text-center w-100">{{ __('streamPlaylist.no_watchlist_available') }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="notification-tab" role="tabpanel">
                            <div
                                class="d-flex align-items-center gap-3 justify-content-between flex-sm-row flex-column-reverse border-bottom mb-5">
                                <div id="item-nav1">
                                    <div class="item-list-tabs no-ajax css_prefix-tab-lists" id="object-nav1">
                                        <ul class="nav nav-underline data-search-tab" id="notification-tab1"
                                            role="tablist">
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link active" id="unread-tab" data-bs-toggle="tab"
                                                    data-bs-target="#unread" type="button" role="tab"
                                                    aria-controls="unread" aria-selected="true">
                                                    {{__('streamTag.unread')}} </button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link" id="read-tab" data-bs-toggle="tab"
                                                    data-bs-target="#read" type="button" role="tab"
                                                    aria-controls="read" aria-selected="false" tabindex="-1">
                                                    {{__('streamTag.read')}} </button>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-content" id="notification-tabContent">
                                <!-- Unread Notifications Tab -->
                                <div class="tab-pane fade show active" id="unread" role="tabpanel" tabindex="0"
                                    aria-labelledby="unread-tab">
                                    <ul class="notification-list m-0 p-0">
                                        @include('frontend::components.widgets.notification-item', [
                                            'notificationImage' => 'krishna.webp',
                                            'title' => __('streamMovies.krishna'),
                                            'days' => __('streamMonth.4_week'),
                                        ])
                                        @include('frontend::components.widgets.notification-item', [
                                            'notificationImage' => 'the-crew.webp',
                                            'title' => __('streamMovies.the_crew'),
                                            'days' => __('streamMonth.2_week'),
                                        ])
                                        @include('frontend::components.widgets.notification-item', [
                                            'notificationImage' => 'the-first-of-us.webp',
                                            'title' => __('streamMovies.the_first_of_us'),
                                            'days' => __('streamMonth.4_week'),
                                        ])
                                        @include('frontend::components.widgets.notification-item', [
                                            'notificationImage' => 'episode/s1e1-trust.webp',
                                            'title' => __('streamEpisode.episode26'),
                                            'days' => __('streamMonth.3_hours'),
                                        ])
                                        @include('frontend::components.widgets.notification-item', [
                                            'notificationImage' => 'episode/s1e2-the-new-guy.webp',
                                            'title' => __('streamEpisode.episode25'),
                                            'days' => __('streamMonth.3_hours'),
                                        ])
                                        @include('frontend::components.widgets.notification-item', [
                                            'notificationImage' => 'episode/s1e4-island-of-secrets.webp',
                                            'title' => __('streamEpisode.episode5'),
                                            'days' => __('streamMonth.3_hours'),
                                        ])
                                        @include('frontend::components.widgets.notification-item', [
                                            'notificationImage' => 'episode/s2e1-stuck.webp',
                                            'title' => __('streamEpisode.episode29'),
                                            'days' => __('streamMonth.3_hours'),
                                        ])
                                        @include('frontend::components.widgets.notification-item', [
                                            'notificationImage' => 'episode/s2e2-forged-alliances.webp',
                                            'title' => __('streamEpisode.episode15'),
                                            'days' => __('streamMonth.2_hours'),
                                        ])
                                        @include('frontend::components.widgets.notification-item', [
                                            'notificationImage' => 'episode/s2e3-the-queen.webp',
                                            'title' => __('streamEpisode.episode34'),
                                            'days' => __('streamMonth.2_hours'),
                                        ])
                                        @include('frontend::components.widgets.notification-item', [
                                            'notificationImage' => 'episode/s2e4-restore-hope.webp',
                                            'title' => __('streamEpisode.episode28'),
                                            'days' => __('streamMonth.2_hours'),
                                        ])
                                    </ul>
                                </div>

                                <!-- Read Notifications Tab -->
                                <div class="tab-pane fade" id="read" role="tabpanel" tabindex="0"
                                    aria-labelledby="read-tab">
                                    <form>
                                        <ul class="notification-list p-0 m-0">
                                            @include('frontend::components.widgets.notification-item', [
                                                'notificationImage' => 'gameofhero.webp',
                                                'title' => __('streamMovies.game_of_heros'),
                                                'days' => __('streamMonth.2_week'),
                                            ])
                                        </ul>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="membership-tab" role="tabpanel">
                            <div class="pmpro">
                                <p class="error-message">{{__('streamPlaylist.no_active_membership')}}</p>
                                <section class="st-pmp-section mt-5">
                                    <h4 class="pmpro_section_title">{{__('streamShop.order_history')}}</h4>
                                    <div class="pmpro_card">
                                        <table class="pmpro_table pmpro_table_orders">
                                            <thead>
                                                <tr>
                                                    <th class="st-pmp-table-order">{{ __('streamTag.date') }}</th>
                                                    <th class="st-pmp-table-order">{{ __('streamTag.level') }}</th>
                                                    <th class="st-pmp-table-order">{{ __('streamShop.total') }}</th>
                                                    <th class="st-pmp-table-order">{{ __('streamTag.status') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr id="pmpro_table_order-51311C6265">
                                                    <th class="pmpro_table_order-date" data-title="Date">
                                                        <a href="membership-invoice">
                                                            February 18, 2025 </a>
                                                    </th>
                                                    <td class="pmpro_table_order-level" data-title="Level">
                                                        {{__('streamTag.basic_plan')}} </td>
                                                    <td class="pmpro_table_order-amount" data-title="Amount">
                                                        $10.00 </td>
                                                    <td class="pmpro_table_order-status" data-title="Status">
                                                        <span class="pmpro_tag pmpro_tag-success">
                                                            {{__('streamTag.paid')}} </span>
                                                    </td>
                                                </tr>
                                                <tr id="pmpro_table_order-A467E41A35">
                                                    <th class="pmpro_table_order-date" data-title="Date">
                                                        <a href="membership-invoice">
                                                            February 10, 2025 </a>
                                                    </th>
                                                    <td class="pmpro_table_order-level" data-title="Level">
                                                        {{__('streamTag.premium_plan')}} </td>
                                                    <td class="pmpro_table_order-amount" data-title="Amount">
                                                        $180.00 </td>
                                                    <td class="pmpro_table_order-status" data-title="Status">
                                                        <span class="pmpro_tag pmpro_tag-success">
                                                            {{__('streamTag.paid')}} </span>
                                                    </td>
                                                </tr>
                                                <tr id="pmpro_table_order-3B9A37110A">
                                                    <th class="pmpro_table_order-date" data-title="Date">
                                                        <a href="membership-invoice">
                                                            February 6, 2025 </a>
                                                    </th>
                                                    <td class="pmpro_table_order-level" data-title="Level">
                                                        {{__('streamTag.basic_plan')}} </td>
                                                    <td class="pmpro_table_order-amount" data-title="Amount">
                                                        $10.00 </td>
                                                    <td class="pmpro_table_order-status" data-title="Status">
                                                        <span class="pmpro_tag pmpro_tag-success">
                                                            {{__('streamTag.paid')}} </span>
                                                    </td>
                                                </tr>
                                                <tr id="pmpro_table_order-68B3C8559C">
                                                    <th class="pmpro_table_order-date" data-title="Date">
                                                        <a href="membership-invoice">
                                                            February 6, 2025 </a>
                                                    </th>
                                                    <td class="pmpro_table_order-level" data-title="Level">
                                                        {{__('streamTag.basic_plan')}} </td>
                                                    <td class="pmpro_table_order-amount" data-title="Amount">
                                                        $10.00 </td>
                                                    <td class="pmpro_table_order-status" data-title="Status">
                                                        <span class="pmpro_tag pmpro_tag-success">
                                                            {{__('streamTag.paid')}} </span>
                                                    </td>
                                                </tr>
                                                <tr id="pmpro_table_order-FB6969474C">
                                                    <th class="pmpro_table_order-date" data-title="Date">
                                                        <a href="membership-invoice">
                                                            January 29, 2025 </a>
                                                    </th>
                                                    <td class="pmpro_table_order-level" data-title="Level">
                                                        {{__('streamTag.standard_plan')}} </td>
                                                    <td class="pmpro_table_order-amount" data-title="Amount">
                                                        $79.00 </td>
                                                    <td class="pmpro_table_order-status" data-title="Status">
                                                        <span class="pmpro_tag pmpro_tag-success">
                                                            {{__('streamTag.paid')}} </span>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div> <!-- end st-pmp-card-content -->
                                </section>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- edit profile modal -->
        </div>
    </section>

    <div class="modal fade view-more-data-modal edit-profile-modal" id="edit-profile-modal" tabindex="-1"
        aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header pb-0">
                    <h5 class="modal-title" id="exampleModalLabelEdit1">{{__('streamButtons.edit_profile')}}</h5>
                    <button type="button" class="btn-close me-0" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="st-result-msg">
                        <div class="st-status-message">

                        </div>
                    </div>
                    <form>
                        <input type="hidden" name="action" value="edit_profile">
                        <input type="hidden" name="user_id" value="12">
                        <input type="hidden" id="is_remove_avtar" name="is_remove_avtar" value="">

                        <input type="hidden" id="edit_profile_nonce" name="edit_profile_nonce"
                            value="ec49b112d5"><input type="hidden" name="_wp_http_referer"
                            value="/product/wp/streamit/profile/marvin/">
                        <div class="row">
                            <!-- Avatar Upload Section -->
                            <div class="col-12 text-center mb-5">
                                <div class="position-relative d-inline-block avtar_image">
                                    <img src="{{ asset('frontend/images/user/user6.jpg') }}" alt="User Avatar"
                                        class="user-image user-profile-image" id="profile-picture-preview">

                                    <div class="avtar_action">
                                        <a class="avtar_action-btn" id="edit-profile-picture-btn">
                                            <i class="icon-edit-icon"></i>
                                        </a>
                                        <a class="avtar_action-btn" id="remove-profile-picture-btn">
                                            <i class="icon-trash-icon"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- First Name Field -->
                            <div class="col-sm-6">
                                <div class="form-group mb-3">
                                    <label for="first_name" class="form-label">{{__('streamAccount.first_name')}}</label>
                                    <input type="text" class="form-control" name="first_name" id="first_name"
                                        value="Marvin" required="">
                                </div>
                            </div>

                            <!-- Last Name Field -->
                            <div class="col-sm-6">
                                <div class="form-group mb-3">
                                    <label for="last_name" class="form-label">{{__('streamAccount.last_name')}}</label>
                                    <input type="text" class="form-control" name="last_name" id="last_name"
                                        value="McKinney" required="">
                                </div>
                            </div>

                            <!-- Email Field -->
                            <div class="col-12">
                                <div class="form-group mb-0">
                                    <label for="user_email" class="form-label">{{__('streamAccount.email')}}</label>
                                    <input type="email" class="form-control" name="user_email" id="user_email"
                                        value="marvin@demo.com" required="">
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="iq-button mt-4 text-end">
                            <button class="btn btn-primary text-capitalize position-relative rounded-3">
                                <span class="button-text">{{__('streamTag.save')}}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}
@endsection
