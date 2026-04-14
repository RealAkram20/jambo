@php $sectionPaddingClass = $sectionPaddingClass ?? false; @endphp
<section class="continue-watching-block home-continue-watch {{ $sectionPaddingClass ? 'section-padding-top' : '' }}">
    <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
        <h4 class="main-title text-capitalize mb-0 fw-medium">{{ __('sectionTitle.continue_watching') }}</h4>
    </div>
    <div class="position-relative swiper swiper-card" data-slide="6" data-laptop="3" data-tab="3" data-mobile="2"
        data-mobile-sm="2" data-autoplay="false" data-loop="true" data-navigation="true" data-pagination="false">
        <ul class="p-0 swiper-wrapper m-0 list-inline">
            @forelse ($continueWatching ?? collect() as $movie)
                <li class="swiper-slide">
                    @include('frontend::components.cards.continue-watch-card', [
                        'imagePath' => $movie->backdrop_url ?: $movie->poster_url ?: 'gameofhero.webp',
                        'progressValue' => (25 + (($movie->id * 7) % 70)) . '%',
                        'dataLeftTime' => $movie->runtime_minutes ? max(10, (int) ($movie->runtime_minutes / 2)) : '45',
                        'watchMovieTitle' => $movie->title,
                        'watchMovieDate' => $movie->published_at?->format('M-Y') ?? '',
                        'watchLink' => route('frontend.movie_detail', $movie->slug),
                    ])
                </li>
            @empty
                <li class="swiper-slide"><p class="text-muted">{{ __('streamTag.no_results') ?? 'Nothing to continue yet.' }}</p></li>
            @endforelse
        </ul>
        <div class="d-none d-lg-block">
            <div class="swiper-button swiper-button-next"></div>
            <div class="swiper-button swiper-button-prev"></div>
        </div>
    </div>
</section>
