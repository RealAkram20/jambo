@extends('frontend::layouts.blank')

@section('content')

    <div class="back-btn">
        <a class="btn btn-link text-white text-decoration-none p-0" href="{{ route('frontend.movie_detail') }}">
            <i class="ph ph-x"></i>
        </a>
    </div>

    <div class="video-container">
        <video
            id="my-video"
            class="video-js vjs-big-play-centered"
            controls
            preload="auto"
            width="960"
            height="540"
            poster="{{ asset('frontend/images/player/player-poster.webp') }}"
            data-setup='{}'>
            <source src="{{ asset('frontend/images/player/demo_videos.mp4') }}" type="video/mp4" />
        </video>
    </div>

@endsection