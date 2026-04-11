@extends('frontend::layouts.master', ['isBreadCrumb' => true, 'title' => __('frontendheader.tags')])

@section('content')
    {{-- Movie Tag Section --}}
    <section class="section-padding tag-section" id="movieTag">
        <div class="container-fluid">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h4 class="main-title text-capitalize mb-0">{{ __('sectionTitle.movie_tag') }}</h4>
            </div>
            <div class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 data-listing">
                <div class="col">
                    @include('frontend::components.cards.tags-card', ['title' => __('streamTag.action'), 'tagUrl' => 'view-all-movie'])
                </div>
                <div class="col">
                    @include('frontend::components.cards.tags-card', ['title' => __('streamTag.adventure'), 'tagUrl' => 'view-all-movie'])
                </div>
                <div class="col">
                    @include('frontend::components.cards.tags-card', ['title' => __('streamTag.animation'), 'tagUrl' => 'view-all-movie'])
                </div>
                <div class="col">
                    @include('frontend::components.cards.tags-card', ['title' => __('streamTag.comedy'), 'tagUrl' => 'view-all-movie'])
                </div>
                <div class="col">
                    @include('frontend::components.cards.tags-card', ['title' => __('streamTag.drama'), 'tagUrl' => 'view-all-movie'])
                </div>
                {{-- Hidden tags for "Load More" --}}
                <div class="col hidden-tags" style="display: none;">
                    @include('frontend::components.cards.tags-card', ['title' => __('streamTag.family'), 'tagUrl' => 'view-all-movie'])
                </div>
                <div class="col hidden-tags" style="display: none;">
                    @include('frontend::components.cards.tags-card', ['title' => __('streamTag.fantasy'), 'tagUrl' => 'view-all-movie'])
                </div>
                <div class="col hidden-tags" style="display: none;">
                    @include('frontend::components.cards.tags-card', ['title' => __('streamTag.horror'), 'tagUrl' => 'view-all-movie'])
                </div>
                <div class="col hidden-tags" style="display: none;">
                    @include('frontend::components.cards.tags-card', ['title' => __('streamTag.romance'), 'tagUrl' => 'view-all-movie'])
                </div>
                <div class="col hidden-tags" style="display: none;">
                    @include('frontend::components.cards.tags-card', ['title' => __('streamTag.thriller'), 'tagUrl' => 'view-all-movie'])
                </div>
            </div>
            <div class="text-center mt-4">
                <div class="iq-button">
                    <a href="javascript:void(0)" class="btn text-uppercase position-relative custom-button load-more-btn">
                        <span class="button-text">{{__('streamButtons.load_more')}}</span>
                        <i class="ph-fill ph-play"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- TV Show Tag Section --}}
    <section class="section-padding tag-section" id="tvshowTag">
        <div class="container-fluid">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h4 class="main-title text-capitalize mb-0">{{ __('sectionTitle.tv_show_tag') }}</h4>
            </div>
            <div class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 data-listing">
                <div class="col">
                    @include('frontend::components.cards.tags-card', ['title' => __('streamTag.action'), 'tagUrl' => 'view-all-tvshow'])
                </div>
                <div class="col">
                    @include('frontend::components.cards.tags-card', ['title' => __('streamTag.adventure'), 'tagUrl' => 'view-all-tvshow'])
                </div>
                <div class="col">
                    @include('frontend::components.cards.tags-card', ['title' => __('streamTag.animation'), 'tagUrl' => 'view-all-tvshow'])
                </div>
                <div class="col">
                    @include('frontend::components.cards.tags-card', ['title' => __('streamTag.comedy'), 'tagUrl' => 'view-all-tvshow'])
                </div>
                <div class="col">
                    @include('frontend::components.cards.tags-card', ['title' => __('streamTag.drama'), 'tagUrl' => 'view-all-tvshow'])
                </div>
                {{-- Hidden tags for "Load More" --}}
                <div class="col hidden-tags" style="display: none;">
                    @include('frontend::components.cards.tags-card', ['title' => __('streamTag.family'), 'tagUrl' => 'view-all-tvshow'])
                </div>
                <div class="col hidden-tags" style="display: none;">
                    @include('frontend::components.cards.tags-card', ['title' => __('streamTag.fantasy'), 'tagUrl' => 'view-all-tvshow'])
                </div>
                <div class="col hidden-tags" style="display: none;">
                    @include('frontend::components.cards.tags-card', ['title' => __('streamTag.horror'), 'tagUrl' => 'view-all-tvshow'])
                </div>
                <div class="col hidden-tags" style="display: none;">
                    @include('frontend::components.cards.tags-card', ['title' => __('streamTag.romance'), 'tagUrl' => 'view-all-tvshow'])
                </div>
                <div class="col hidden-tags" style="display: none;">
                    @include('frontend::components.cards.tags-card', ['title' => __('streamTag.thriller'), 'tagUrl' => 'view-all-tvshow'])
                </div>
            </div>
            <div class="text-center mt-4">
                <div class="iq-button">
                    <a href="javascript:void(0)" class="btn text-uppercase position-relative custom-button load-more-btn">
                        <span class="button-text">{{__('streamButtons.load_more')}}</span>
                        <i class="ph-fill ph-play"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- Video Tag Section --}}
    <section class="section-padding tag-section" id="videoTag">
        <div class="container-fluid">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h4 class="main-title text-capitalize mb-0">{{ __('sectionTitle.video_tag') }}</h4>
            </div>
            <div class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 data-listing">
                <div class="col">
                    @include('frontend::components.cards.tags-card', ['title' => __('streamTag.action'), 'tagUrl' => 'view-all-video'])
                </div>
                <div class="col">
                    @include('frontend::components.cards.tags-card', ['title' => __('streamTag.adventure'), 'tagUrl' => 'view-all-video'])
                </div>
                <div class="col">
                    @include('frontend::components.cards.tags-card', ['title' => __('streamTag.animation'), 'tagUrl' => 'view-all-video'])
                </div>
                <div class="col">
                    @include('frontend::components.cards.tags-card', ['title' => __('streamTag.comedy'), 'tagUrl' => 'view-all-video'])
                </div>
                <div class="col">
                    @include('frontend::components.cards.tags-card', ['title' => __('streamTag.crime'), 'tagUrl' => 'view-all-video'])
                </div>
                <div class="col">
                    @include('frontend::components.cards.tags-card', ['title' => __('streamTag.drama'), 'tagUrl' => 'view-all-video'])
                </div>
                <div class="col">
                    @include('frontend::components.cards.tags-card', ['title' => __('streamTag.family'), 'tagUrl' => 'view-all-video'])
                </div>
                <div class="col">
                    @include('frontend::components.cards.tags-card', ['title' => __('streamTag.fantasy'), 'tagUrl' => 'view-all-video'])
                </div>
                {{-- Hidden tags for "Load More" --}}
                <div class="col hidden-tags" style="display: none;">
                    @include('frontend::components.cards.tags-card', ['title' => __('streamTag.horror'), 'tagUrl' => 'view-all-video'])
                </div>
                <div class="col hidden-tags" style="display: none;">
                    @include('frontend::components.cards.tags-card', ['title' => __('streamTag.romance'), 'tagUrl' => 'view-all-video'])
                </div>
                <div class="col hidden-tags" style="display: none;">
                    @include('frontend::components.cards.tags-card', ['title' => __('streamTag.thriller'), 'tagUrl' => 'view-all-video'])
                </div>
            </div>
            <div class="text-center mt-4">
                <div class="iq-button">
                    <a href="javascript:void(0)" class="btn text-uppercase position-relative custom-button load-more-btn">
                        <span class="button-text">{{__('streamButtons.load_more')}}</span>
                        <i class="ph-fill ph-play"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}
@endsection
