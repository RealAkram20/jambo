@extends('frontend::layouts.master', ['isSwiperSlider' => true, 'isVideoJs' => true, 'isSelect2' => 'true'])

@section('content')
    <div class="position-relative">
        <div class="iq-main-slider site-video position-relative">
            <video id="my-video" poster="{{ asset('frontend/images/media/minions.webp') }}"
                class="my-video video-js vjs-big-play-centered w-100" loop autoplay muted preload="auto"
                data-setup='{
                  "techOrder": ["youtube"],
                  "sources": [{
                      "type": "video/youtube",
                      "src": " https://www.youtube.com/watch?v=eq_aKPXuOTg"
                  }],
                  "youtube": {
                      "modestbranding": 1,
                      "rel": 0,
                      "showinfo": 0,
                      "autoplay": 1
                  },
                  "fullscreen": true
              }'>
            </video>
        </div>

        <div class="movie-detail-part position-relative">
            <div class="trending-info pt-0 pb-0">
                <div class="details-parts">
                    @include('frontend::components.cards.movie-description', [
                        'moveName' => __('streamMovies.minions'),
                        'movieDuration' => '50mins',
                        'movieReleased' => 'Feb 2017',
                        'movieViews' => '84' . __('frontendplaylist.views'),
                        'isVideoPageData' => true,
                        'movieLanguage' => 'English',
                        'videoUrl' => route("frontend.video_player"),
                    ])
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="overflow-hidden">
            @include('frontend::components.sections.upcoming', ['videodetailUpcoming' => true])

            <section class="mb-5">
                @include('frontend::components.widgets.details-review')
            </section>
        </div>
    </div>

    @include('frontend::components.widgets.details-description-modal', [
        'isEpisodepage' => true,
        'isnotmovieTitle' => true,
        'movieName' => __('streamMovies.zombie_island'),
        'movieDuration' => '50mins',
        'year' => '2017',
        'isnotimdbRating' => true,
        'views' => '84' . __('frontendplaylist.views'),
        'isCategory' => true,
        'isTag' => true,
        'istextureTitle' => true,
        'isLanguadge' => true,
    ])

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}
@endsection
