@extends('frontend::layouts.master', ['isSwiperSlider' => true, 'isFslightbox' => true, 'bodyClass' => 'custom-header-relative'])

@section('content')
    <section class="banner-container">
        <div class="movie-banner">
            <div class="swiper swiper-banner-container iq-rtl-direction" data-swiper="banner-detail-slider">
                <div class="swiper-wrapper">
                    @foreach ($featuredShows as $i => $show)
                        @include('frontend::components.cards.movie-slider', [
                            'movieCard' => 'tv-show-' . ($i + 1),
                            'imagePath' => $show->backdrop_url ?: $show->poster_url ?: 'media/vikings.webp',
                            'movieTitle' => $show->title,
                            'movieRating' => true,
                            'movieRange' => $show->rating ?: '4.0',
                            'NoOfSeasons' => $show->seasons()->count(),
                            'movieYear' => $show->year ?: ($show->published_at?->format('F Y') ?? ''),
                            'calenderIcon' => true,
                            'buttonUrl' => route('frontend.tvshow_detail', $show->slug),
                            'movieText' => $show->synopsis ?: '',
                        ])
                    @endforeach
                </div>
                <div class="swiper-pagination d-block d-lg-none"></div>

                <div class="swiper-banner-button-next d-none d-lg-block">
                    <i class="ph ph-caret-right arrow-icon"></i>
                </div>
                <div class="swiper-banner-button-prev d-none d-lg-block">
                    <i class="ph ph-caret-left icli arrow-icon"></i>
                </div>
            </div>
        </div>
    </section>

    <div class="container-fluid">
        <div class="overflow-hidden">
            <section class="related-movie-block mt-5">
                <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
                    <h4 class="main-title text-capitalize mb-0">{{ __('sectionTitle.popular_show') ?? 'Popular Shows' }}</h4>
                    <span class="text-muted">{{ $shows->count() }} {{ __('streamTag.shows') ?? 'Shows' }}</span>
                </div>
                <div class="card-style-slider">
                    <div class="position-relative swiper swiper-card" data-slide="6" data-laptop="6" data-tab="3"
                        data-mobile="2" data-mobile-sm="2" data-autoplay="false" data-loop="false"
                        data-navigation="true" data-pagination="true">
                        <ul class="p-0 swiper-wrapper m-0 list-inline">
                            @forelse ($shows as $show)
                                <li class="swiper-slide">
                                    @include('frontend::components.cards.card-style', [
                                        'cardImage' => $show->poster_url ?: 'media/vikings-portrait.webp',
                                        'cardTitle' => $show->title,
                                        'movietime' => null,
                                        'cardLang' => 'English',
                                        'cardPath' => route('frontend.tvshow_detail', $show->slug),
                                        'cardGenres' => $show->genres->take(2)->pluck('name')->all(),
                                        'productPremium' => (bool) $show->tier_required,
                                    ])
                                </li>
                            @empty
                                <li class="swiper-slide"><p class="text-muted">No shows published yet.</p></li>
                            @endforelse
                        </ul>
                        <div class="swiper-button swiper-button-next d-none d-lg-block"></div>
                        <div class="swiper-button swiper-button-prev d-none d-lg-block"></div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}
@endsection
