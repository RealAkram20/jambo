@extends('frontend::layouts.master', ['isBreadCrumb' => true, 'title' => __('frontendheader.wishlist_page'), 'isSwiperSlider' => true, 'isVideoJs' => true, 'active' => 'playlist', 'bodyClass' => 'custom-header-relative'])

@section('content')
    <section class="section-padding">
        <div class="container-fluid">
            <div class="col-md-12">
                <div class="border-bottom mb-5 watchlist-tab">
                    <div id="item-nav">
                        <div class="item-list-tabs no-ajax css_prefix-tab-lists" id="object-nav">
                            <ul class="nav nav-underline data-search-tab" id="pills-tab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="pills-movie-tab" data-bs-toggle="pill"
                                        data-bs-target="#pills-movie1" type="button" role="tab"
                                        aria-controls="pills-movie1" aria-selected="true">
                                        {{ __('frontendheader.movie') }}
                                        <span class="text-muted small">({{ $movies->count() }})</span>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="pills-tvshow-tab" data-bs-toggle="pill"
                                        data-bs-target="#pills-tvshow1" type="button" role="tab"
                                        aria-controls="pills-tvshow1" aria-selected="false" tabindex="-1">
                                        {{ __('frontendheader.tv_show') }}
                                        <span class="text-muted small">({{ $shows->count() }})</span>
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="tab-content" id="pills-tabContent-watch">
                    {{-- Movies --}}
                    <div class="tab-pane fade active show" id="pills-movie1" role="tabpanel" tabindex="0"
                        aria-labelledby="pills-movie-tab">
                        <div class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-3 row-cols-xl-4 row-cols-xxl-5 data-listing">
                            @forelse ($movies as $item)
                                <div class="col" data-watchlist-row="{{ $item->id }}">
                                    @include('frontend::components.widgets.watchlist-detail-card', ['item' => $item, 'kind' => 'movie'])
                                </div>
                            @empty
                                <p class="text-center w-100 text-muted">{{ __('streamPlaylist.no_watchlist_available') }}</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- TV shows --}}
                    <div class="tab-pane fade" id="pills-tvshow1" role="tabpanel" tabindex="0"
                        aria-labelledby="pills-tvshow-tab">
                        <div class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-3 row-cols-xl-4 row-cols-xxl-5 data-listing">
                            @forelse ($shows as $item)
                                <div class="col" data-watchlist-row="{{ $item->id }}">
                                    @include('frontend::components.widgets.watchlist-detail-card', ['item' => $item, 'kind' => 'show'])
                                </div>
                            @empty
                                <p class="text-center w-100 text-muted">{{ __('streamPlaylist.no_watchlist_available') }}</p>
                            @endforelse
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End —
         remove-from-watchlist is handled by the global .jambo-watchlist-remove-btn
         delegate in layouts/master.blade.php, so nothing page-local needed. --}}
@endsection
