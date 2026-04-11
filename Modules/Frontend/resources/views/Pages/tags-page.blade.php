@extends('frontend::layouts.master', ['isBreadCrumb' => true, 'title' => __('frontendheader.tags')])

@section('content')
    <section class="section-padding">
        <div class="container-fluid">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h4 class="main-title text-capitalize mb-0">{{ __('sectionTitle.movie_tag') }}</h4>
                <a href="view-all-tags?type=movie">{{__('streamButtons.view_all')}}</a>
            </div>
            <div class="row g-3 g-lg-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 row-cols-xxl-6">
                <div class="col">
                    @include('frontend::components.cards.tags-card', [
                        'title' => __('streamTag.action'),
                        'tagUrl' => 'view-all',
                    ])

                </div>
                <div class="col">
                    @include('frontend::components.cards.tags-card', [
                        'title' => __('streamTag.adventure'),
                        'tagUrl' => 'view-all',
                    ])

                </div>
                <div class="col">
                    @include('frontend::components.cards.tags-card', [
                        'title' => __('streamTag.animation'),
                        'tagUrl' => 'view-all',
                    ])

                </div>
                <div class="col">
                    @include('frontend::components.cards.tags-card', [
                        'title' => __('streamTag.comedy'),
                        'tagUrl' => 'view-all',
                    ])

                </div>
                <div class="col">
                    @include('frontend::components.cards.tags-card', [
                        'title' => __('streamTag.drama'),
                        'tagUrl' => 'view-all',
                    ])

                </div>
                <div class="col">
                    @include('frontend::components.cards.tags-card', [
                        'title' => __('streamTag.family'),
                        'tagUrl' => 'view-all',
                    ])

                </div>
                <div class="col">
                    @include('frontend::components.cards.tags-card', [
                        'title' => __('streamTag.fantasy'),
                        'tagUrl' => 'view-all',
                    ])

                </div>
                <div class="col">
                    @include('frontend::components.cards.tags-card', [
                        'title' => __('streamTag.history'),
                        'tagUrl' => 'view-all',
                    ])

                </div>
                <div class="col">
                    @include('frontend::components.cards.tags-card', [
                        'title' => __('streamTag.horror'),
                        'tagUrl' => 'view-all',
                    ])

                </div>
                <div class="col">
                    @include('frontend::components.cards.tags-card', [
                        'title' => __('streamTag.mystery'),
                        'tagUrl' => 'view-all',
                    ])

                </div>
                <div class="col">
                    @include('frontend::components.cards.tags-card', [
                        'title' => __('streamTag.sci_fi'),
                        'tagUrl' => 'view-all',
                    ])

                </div>
            </div>
        </div>
    </section>

    <section class="section-padding-bottom">
        <div class="container-fluid">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h4 class="main-title text-capitalize mb-0">{{ __('sectionTitle.tv_show_tag') }}</h4>
                <a href="view-all-tags?type=tvshow">{{__('streamButtons.view_all')}}</a>
            </div>
            <div class="row g-3 g-lg-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 row-cols-xxl-6">
                <div class="col">
                    @include('frontend::components.cards.tags-card', [
                        'title' => __('streamTag.brother'),
                        'tagUrl' => 'view-all',
                    ])

                </div>
                <div class="col">
                    @include('frontend::components.cards.tags-card', [
                        'title' => __('streamTag.brother_relationship'),
                        'tagUrl' => 'view-all',
                    ])

                </div>
                <div class="col">
                    @include('frontend::components.cards.tags-card', [
                        'title' => __('streamTag.kings'),
                        'tagUrl' => 'view-all',
                    ])

                </div>
                <div class="col">
                    @include('frontend::components.cards.tags-card', [
                        'title' => __('streamTag.music'),
                        'tagUrl' => 'view-all',
                    ])

                </div>
                <div class="col">
                    @include('frontend::components.cards.tags-card', [
                        'title' => __('streamTag.mythology'),
                        'tagUrl' => 'view-all',
                    ])

                </div>
                <div class="col">
                    @include('frontend::components.cards.tags-card', [
                        'title' => __('streamTag.premieres'),
                        'tagUrl' => 'view-all',
                    ])

                </div>
            </div>
        </div>
    </section>


    <section class="section-padding-bottom">
        <div class="container-fluid">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h4 class="main-title text-capitalize mb-0">{{ __('sectionTitle.video_tag') }}</h4>
                <a href="view-all-tags?type=video">{{__('streamButtons.view_all')}}</a>
            </div>
            <div class="row g-3 g-lg-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 row-cols-xxl-6">
                <div class="col">
                    @include('frontend::components.cards.tags-card', [
                        'title' => __('streamTag.drama'),
                        'tagUrl' => 'view-all',
                    ])

                </div>
                <div class="col">
                    @include('frontend::components.cards.tags-card', [
                        'title' => __('streamTag.thriller'),
                        'tagUrl' => 'view-all',
                    ])

                </div>
                <div class="col">
                    @include('frontend::components.cards.tags-card', [
                        'title' => __('streamTag.comedy'),
                        'tagUrl' => 'view-all',
                    ])

                </div>
                <div class="col">
                    @include('frontend::components.cards.tags-card', [
                        'title' => __('streamTag.animation'),
                        'tagUrl' => 'view-all',
                    ])

                </div>
                <div class="col">
                    @include('frontend::components.cards.tags-card', [
                        'title' => __('streamTag.romance'),
                        'tagUrl' => 'view-all',
                    ])

                </div>
                <div class="col">
                    @include('frontend::components.cards.tags-card', [
                        'title' => __('streamTag.family'),
                        'tagUrl' => 'view-all',
                    ])

                </div>
                <div class="col">
                    @include('frontend::components.cards.tags-card', [
                        'title' => __('streamTag.action'),
                        'tagUrl' => 'view-all',
                    ])

                </div>
                <div class="col">
                    @include('frontend::components.cards.tags-card', [
                        'title' => __('streamTag.fantasy'),
                        'tagUrl' => 'view-all',
                    ])

                </div>
                <div class="col">
                    @include('frontend::components.cards.tags-card', [
                        'title' => __('streamTag.horror'),
                        'tagUrl' => 'view-all',
                    ])

                </div>
                <div class="col">
                    @include('frontend::components.cards.tags-card', [
                        'title' => __('streamTag.crime'),
                        'tagUrl' => 'view-all',
                    ])

                </div>
            </div>
        </div>
    </section>

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}
@endsection
