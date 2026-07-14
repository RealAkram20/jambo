<!-- Movie Description Start-->
@php
    // Where the action row (Start watching / Watch List / Like / Share)
    // renders. Detail pages: above the title — the CTA has to be the
    // first thing a visitor sees. Watch pages: below the synopsis — the
    // player already IS the CTA, so these are secondary actions and
    // shouldn't sit between the video and the title.
    $actionsBelowDescription = $actionsBelowDescription ?? false;
@endphp

@unless($actionsBelowDescription)
    @include('frontend::components.cards.movie-description-actions')
@endunless

{{-- Genre chips. Render as plain spans until callers start passing
     genre slugs alongside names — there's no slug here to link to. --}}
@if (isset($movieGenres) && count($movieGenres))
    <ul class="p-0 mb-2 list-inline d-flex flex-wrap movie-tag">
        @foreach ($movieGenres as $g)
            <li class="trending-list"><span>{{ $g }}</span></li>
        @endforeach
    </ul>
@endif
<div class="d-block d-lg-flex align-items-center">
    @if(isset($isnotmovieTitle) && $isnotmovieTitle)
        <h5 class="css_prefix-title text-capitalize line-count-1">
            {{-- Plain heading: the surrounding card already links the
                 poster to the detail page, wrapping the title in a
                 dummy anchor added nothing. --}}
            {{ $moveName }}
        </h5>
    @else
        <h3 class="trending-text fw-bold texture-text text-uppercase my-0 fadeInLeft animated d-inline-block"
            data-animation-in="fadeInLeft" data-delay-in="0.6" style="opacity: 1; animation-delay: 0.6s">
            {{ $moveName }}
        </h3>
    @endif
</div>
@if(isset($isVideoPageData) && $isVideoPageData)
    <ul class="list-inline mx-0 p-0 d-flex align-items-center flex-wrap gap-3 movie-metalist">
        <li>
            <div class="d-flex align-items-center gap-1">
                <i class="ph ph-eye"></i>
                <span>{{ $movieViews }}</span>
            </div>
        </li>
        <li>
            <span class="d-flex align-items-center gap-1">
                <span class="d-flex align-items-center justify-content-center"><i class="ph ph-clock"></i></span>
                {{ $movieDuration }}
            </span>
        </li>
        <li>
            <span class="d-flex align-items-center gap-1">
                <span class="fw-medium">{{ $movieReleased }}</span>
            </span>
        </li>
    </ul>
@else
    <ul class="list-inline mb-0 mx-0 p-0 d-flex align-items-center flex-wrap gap-3 movie-metalist">
        <li>
            <span class="d-flex align-items-center gap-1">
                <span class="fw-medium">{{ $movieReleased }}</span>
            </span>
        </li>
        @if(empty($isNotClockduration))
            <li>
                <span class="d-flex align-items-center gap-1">
                    <span class="d-flex align-items-center justify-content-center"><i class="ph ph-clock"></i></span>
                    {{ $movieDuration }}
                </span>
            </li>
        @endif
        <li>
            <div class="d-flex align-items-center gap-1">
                <i class="ph ph-eye"></i>
                <span>{{ $movieViews }}</span>
            </div>
        </li>
        @if(empty($isNotimdbRating))
            <li>
                <span class="d-flex align-items-center gap-1">
                    <span class="fw-medium">
                        <span>{{ $imdbRating }}</span>
                        <span class="imdb-logo ms-1">
                            <img src="{{ asset('frontend/images/pages/imdb-logo.svg') }}" loading="lazy" decoding="async" alt="imdb logo" class="img-fluid imdb-logo1">
                        </span>
                    </span>
                </span>
            </li>
        @endif
        @if(empty($isNotTVShowbadge))
            <li>
                <span class="badge bg-secondary d-flex align-items-center gap-2 fw-bold font-size-12 movie-type-tag">
                    <span>{{ $movieType }}</span>
                </span>
            </li>
        @endif
    </ul>
@endif

@if(!empty($movieLanguage))
    <div class="video-language d-flex align-items-center gap-1 mt-2">
        <i class="ph ph-translate"></i>
        <ul class="list-inline m-0 p-0 d-inline-flex align-items-center gap-3 flex-wrap">
            <li>
                <small class="text-capitalize">{{ $movieLanguage }}</small>
            </li>
            {{-- Add more languages if needed --}}
        </ul>
    </div>
@endif

<div class="movie-description mt-3 mb-4" id="readmore-wrapper">
    <p class="line-count-3 RightAnimate-two mb-0">
        {{ $movieDescription ?? __('streamMovies.game_of_heros_desc') }}
    </p>
    <div class="iq-blog-meta-cat-tag iq-blogtag readmore-tags">
        <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#viewMoreDataModal" class="position-relative">{{__('frontendform.read_more')}}</a>
    </div>
</div>

@if($actionsBelowDescription)
    @include('frontend::components.cards.movie-description-actions')
@endif
<!-- Movie Description End -->

<!-- Modals -->
@include('frontend::components.widgets.share-modal')
<!-- Modals End -->
