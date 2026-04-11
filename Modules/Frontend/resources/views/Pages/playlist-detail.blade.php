@extends('frontend::layouts.master', ['isSwiperSlider' => true, 'isVideoJs' => true, 'active' => 'playlist', 'bodyClass' => 'custom-header-relative'])

@section('content')
    <section class="section-padding ">
        <div class="playlist-detail-page">
            <div class="container-fluid">
                <div class="row flex-row-reverse">
                    <div class="col-xxl-3 col-xl-4 col-lg-5">
                        <div class="card border-0">
                            <div
                                class="card-header pb-3 mb-3 border-bottom d-flex align-items-center justify-content-between gap-1">
                                <h5 class="m-0">{{__('streamPlaylist.90s_throwback')}}</h5>
                                <small>
                                    1/4 </small>
                            </div>

                            <div class="card-body px-0 pt-0 pb-3">
                                <div class="playlist-data">
                                    <div class="playlist-data-card active">
                                        <div class="playlist-data-card-image">
                                            <a href="javascript:void(0)">
                                                <img src="{{ asset('frontend/images/media/gameofhero.webp') }}"
                                                    alt="Game of Heros" class="img-fluid object-cover w-100 border-0">
                                            </a>
                                        </div>
                                        <div class="playlist-data-card-content">
                                            <h6 class="mt-0 mb-2 line-count-2 playlist-data-title">
                                                <a href="javascript:void(0)">{{__('streamMovies.game_of_heros')}}</a>
                                            </h6>
                                            <ul
                                                class="playlist-category list-inline d-flex flex-wrap align-items-center m-0 p-0 column-gap-3 row-gap-1">
                                                <li>
                                                    <i class="icon-eye-2 me-1"></i>1225 {{__('frontendplaylist.views')}}
                                                </li>
                                                <li>
                                                    2 months ago </li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="playlist-data-card ">
                                        <div class="playlist-data-card-image">
                                            <a href="javascript:void(0)">
                                                <img src="{{ asset('frontend/images/media/rabbit.webp') }}" alt="Rabbit"
                                                    class="img-fluid object-cover w-100 border-0">
                                            </a>
                                        </div>
                                        <div class="playlist-data-card-content">
                                            <h6 class="mt-0 mb-2 line-count-2 playlist-data-title">
                                                <a href="javascript:void(0)">{{__('streamMovies.rabbit')}}</a>
                                            </h6>
                                            <ul
                                                class="playlist-category list-inline d-flex flex-wrap align-items-center m-0 p-0 column-gap-3 row-gap-1">
                                                <li>
                                                    <i class="icon-eye-2 me-1"></i>902 {{__('frontendplaylist.views')}}
                                                </li>
                                                <li>
                                                    11 years ago </li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="playlist-data-card ">
                                        <div class="playlist-data-card-image">
                                            <a href="javascript:void(0)">
                                                <img src="{{ asset('frontend/images/media/migration.webp') }}"
                                                    alt="Migration" class="img-fluid object-cover w-100 border-0">
                                            </a>
                                        </div>
                                        <div class="playlist-data-card-content">
                                            <h6 class="mt-0 mb-2 line-count-2 playlist-data-title">
                                                <a href="javascript:void(0)">{{__('streamMovies.migration')}}</a>
                                            </h6>
                                            <ul
                                                class="playlist-category list-inline d-flex flex-wrap align-items-center m-0 p-0 column-gap-3 row-gap-1">
                                                <li>
                                                    <i class="icon-eye-2 me-1"></i>234 {{__('frontendplaylist.views')}}
                                                </li>
                                                <li>
                                                    11 years ago </li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="playlist-data-card ">
                                        <div class="playlist-data-card-image">
                                            <a href="javascript:void(0)">
                                                <img src="{{ asset('frontend/images/media/joker.webp') }}" alt="joker"
                                                    class="img-fluid object-cover w-100 border-0">
                                            </a>
                                        </div>
                                        <div class="playlist-data-card-content">
                                            <h6 class="mt-0 mb-2 line-count-2 playlist-data-title">
                                                <a href="javascript:void(0)">{{__('streamMovies.joker')}}</a>
                                            </h6>
                                            <ul
                                                class="playlist-category list-inline d-flex flex-wrap align-items-center m-0 p-0 column-gap-3 row-gap-1">
                                                <li>
                                                    <i class="icon-eye-2 me-1"></i>181 {{__('frontendplaylist.views')}}
                                                </li>
                                                <li>
                                                    11 years ago </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xxl-9 col-xl-8 col-lg-7 mt-lg-0 mt-4">
                        <div class="playlist-main-banner position-relative mb-3">
                            <!-- Video.js Player -->
                            <video id="video-player" class="video-js vjs-default-skin vjs-big-play-centered" controls
                                preload="auto" poster="{{ asset('frontend/images/media/gameofhero.webp') }}" autoplay muted
                                data-setup='{"fluid": true, "autoplay": true, "controls": true}'>
                                <source src="https://www.youtube.com/watch?v=X8c8EXPfqkI" type="video/youtube">
                            </video>
                        </div>
                        <div id="streamit_player_container"
                            class="trending-info d-flex justify-content-between align-items-center gap-4">
                            <a href="movie-detail">
                                <h4 class="my-2 fw-bold">{{__('streamMovies.game_of_heros')}}</h4>
                            </a>
                            <ul class="actions-playlist list-inline my-2 p-0 d-flex gap-2 justify-content-md-end">
                                <li>
                                    <button type="button" class="btn btn-secondary border action-btn"
                                        data-bs-toggle="modal" data-bs-target="#likeModal" id="like-toggle">
                                        <span id="like-movies">
                                            <span class="h-100 w-100 d-block" data-bs-toggle="tooltip"
                                                data-bs-placement="top" data-bs-title="{{__('sectionTitle.like_tooltip')}}">
                                                <i class="ph ph-heart"></i>
                                            </span>
                                        </span>
                                    </button>
                                </li>
                                <li class="position-relative share-button dropend dropdown">
                                    <button type="button" class="btn btn-secondary border action-btn"
                                        data-bs-toggle="modal" data-bs-target="#shareModal">
                                        <span class="h-100 w-100 d-block" data-bs-toggle="tooltip" data-bs-placement="top"
                                            data-bs-title="{{__('streamTag.share')}}">
                                            <i class="ph ph-share-network"></i>
                                        </span>
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('frontend::components.widgets.share-modal')

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}
@endsection
