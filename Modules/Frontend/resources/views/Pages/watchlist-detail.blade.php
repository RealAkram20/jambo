@extends('frontend::layouts.master', ['isBreadCrumb' => true, 'title' => __('frontendheader.wishlist_page'), 'isSwiperSlider' => true, 'isVideoJs' => true, 'active' => 'playlist', 'bodyClass' => 'custom-header-relative'])

@section('content')
    <section class="section-padding">
        <div class="container-fluid">
            <div class="col-md-12">
                <div class="border-bottom mb-5 watchlist-tab">
                    <div id="item-nav">
                        <div class="item-list-tabs no-ajax css_prefix-tab-lists" id="object-nav">

                            <!-- Watchlist Tabs -->
                            <ul class="nav nav-underline data-search-tab" id="pills-tab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="pills-movie-tab" data-bs-toggle="pill"
                                        data-bs-target="#pills-movie1" type="button" role="tab"
                                        aria-controls="pills-movie1" aria-selected="true">
                                        {{__('frontendheader.movie')}} </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link " id="pills-video-tab" data-bs-toggle="pill"
                                        data-bs-target="#pills-video1" type="button" role="tab"
                                        aria-controls="pills-video1" aria-selected="false" tabindex="-1">
                                        {{__('frontendheader.video')}} </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link " id="pills-tvshow-tab" data-bs-toggle="pill"
                                        data-bs-target="#pills-tvshow1" type="button" role="tab"
                                        aria-controls="pills-tvshow1" aria-selected="false" tabindex="-1">
                                        {{__('frontendheader.tv_show')}} </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link " id="pills-episode-tab" data-bs-toggle="pill"
                                        data-bs-target="#pills-episode1" type="button" role="tab"
                                        aria-controls="pills-episode1" aria-selected="false" tabindex="-1">
                                        {{__('frontenddetail_page.Episode')}} </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <!-- Playlist Tab Content -->
                <div class="tab-content" id="pills-tabContent-watch">
                    <div class="tab-pane fade active show" id="pills-movie1" role="tabpanel" tabindex="0"
                        aria-labelledby="pills-movie-tab">
                        <div
                            class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-3 row-cols-xl-4 row-cols-xxl-5 data-listing">
                            <div class="col">

                                @include('frontend::components.widgets.watchlist-detail-card', [
                                    'cardImage' => 'media/gameofhero.webp',
                                    'movieName' => __('streamMovies.game_of_heros'),
                                ])
                            </div>
                            <div class="col">

                                @include('frontend::components.widgets.watchlist-detail-card', [
                                    'cardImage' => 'media/rabbit.webp',
                                    'movieName' => __('streamMovies.rabbit'),
                                ])
                            </div>
                            <div class="col">

                                @include('frontend::components.widgets.watchlist-detail-card', [
                                    'cardImage' => 'media/migration.webp',
                                    'movieName' => __('streamMovies.migration'),
                                ])
                            </div>
                            <div class="col">

                                @include('frontend::components.widgets.watchlist-detail-card', [
                                    'cardImage' => 'media/krishna.webp',
                                    'movieName' => __('streamMovies.krishna'),
                                ])
                            </div>
                            <div class="col">

                                @include('frontend::components.widgets.watchlist-detail-card', [
                                    'cardImage' => 'media/jumanjj.webp',
                                    'movieName' => __('streamMovies.jumanji'),
                                ])
                            </div>
                            <div class="col">

                                @include('frontend::components.widgets.watchlist-detail-card', [
                                    'cardImage' => 'media/yoshi.webp',
                                    'movieName' => __('streamMovies.yoshi'),
                                ])
                            </div>
                            <div class="col">

                                @include('frontend::components.widgets.watchlist-detail-card', [
                                    'cardImage' => 'media/kung-fu-panda.webp',
                                    'movieName' => __('streamMovies.kung_fu_panda'),
                                ])
                            </div>
                            <div class="col">

                                @include('frontend::components.widgets.watchlist-detail-card', [
                                    'cardImage' => 'media/the-hunter.webp',
                                    'movieName' => __('streamMovies.the_hunter'),
                                ])
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="pills-video1" role="tabpanel" tabindex="0"
                        aria-labelledby="pills-video-tab">
                        <div
                            class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-3 row-cols-xl-4 row-cols-xxl-5 data-listing">
                            <div class="col">

                                @include('frontend::components.widgets.watchlist-detail-card', [
                                    'cardImage' => 'media/toddler.webp',
                                    'movieName' => __('streamMovies.toddler'),
                                ])
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="pills-tvshow1" role="tabpanel" tabindex="0">
                        <div
                            class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-3 row-cols-xl-4 row-cols-xxl-5 data-listing">
                            <div class="col">

                                @include('frontend::components.widgets.watchlist-detail-card', [
                                    'cardImage' => 'media/the-first-of-us.webp',
                                    'movieName' => __('streamMovies.the_first_of_us'),
                                ])
                            </div>
                            <div class="col">

                                @include('frontend::components.widgets.watchlist-detail-card', [
                                    'cardImage' => 'media/migration.webp',
                                    'movieName' => __('streamEpisode.episode7'),
                                ])
                            </div>
                            <div class="col">

                                @include('frontend::components.widgets.watchlist-detail-card', [
                                    'cardImage' => 'media/spiderman.webp',
                                    'movieName' => __('streamEpisode.episode39'),
                                ])
                            </div>
                            <div class="col">

                                @include('frontend::components.widgets.watchlist-detail-card', [
                                    'cardImage' => 'media/minions.webp',
                                    'movieName' => __('streamMovies.minions'),
                                ])
                            </div>
                            <div class="col">

                                @include('frontend::components.widgets.watchlist-detail-card', [
                                    'cardImage' => 'media/pirates-ofdayones-orignal.webp',
                                    'movieName' => __('streamMovies.pirates_of_day_one'),
                                ])
                            </div>
                            <div class="col">

                                @include('frontend::components.widgets.watchlist-detail-card', [
                                    'cardImage' => 'media/assassins-creed.webp',
                                    'movieName' => __('streamMovies.assassins_creed'),
                                ])
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="pills-episode1" role="tabpanel" tabindex="0"
                        aria-labelledby="pills-episode-tab">
                        <div
                            class="row gy-4 row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-3 row-cols-xl-4 row-cols-xxl-5 ">
                            <p class="text-center w-100">{{__('streamPlaylist.no_watchlist_available')}}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}
@endsection
