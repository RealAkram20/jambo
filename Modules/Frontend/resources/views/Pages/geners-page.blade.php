@extends('frontend::layouts.master', ['isBreadCrumb' => true, 'title' => __('frontendheader.geners')])

@section('content')
<section class="section-padding">
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h4 class="main-title text-capitalize mb-0">{{__('sectionTitle.movie_genres')}}</h4>
        </div>
        <div class="genres-grid gy-4 row row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 gy-4">
                <div class="col">
                    @include('frontend::components.cards.card-genres-grid', [
                        'image' => 'genre/action.webp',
                        'title' => __('streamTag.action'),
                        'genersUrl' => 'view-all'
                    ])
                </div>
                <div class="col">
                    @include('frontend::components.cards.card-genres-grid', [
                        'image' => 'genre/adventure.webp',
                        'title' => __('streamTag.adventure'),
                        'genersUrl' => 'view-all'
                    ])
                </div>
                <div class="col">
                    @include('frontend::components.cards.card-genres-grid', [
                        'image' => 'genre/animation.webp',
                        'title' => __('streamTag.animation'),
                        'genersUrl' => 'view-all'
                    ])
                </div>
                <div class="col">
                    @include('frontend::components.cards.card-genres-grid', [
                        'image' => 'genre/Crime.webp',
                        'title' => __('streamTag.crime'),
                        'genersUrl' => 'view-all'
                    ])
                </div>
                <div class="col">
                    @include('frontend::components.cards.card-genres-grid', [
                        'image' => 'genre/horror.webp',
                        'title' => __('streamTag.horror'),
                        'genersUrl' => 'view-all'
                    ])
                </div>
                <div class="col">
                    @include('frontend::components.cards.card-genres-grid', [
                        'image' => 'genre/mystery.webp',
                        'title' => __('streamTag.mystery'),
                        'genersUrl' => 'view-all'
                    ])
                </div>
                <div class="col">
                    @include('frontend::components.cards.card-genres-grid', [
                        'image' => 'genre/romance.webp',
                        'title' => __('streamTag.romance'),
                        'genersUrl' => 'view-all'
                    ])
                </div>
        </div>
    </div>
</section>

<section class="section-padding-bottom">
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h4 class="main-title text-capitalize mb-0">{{__('sectionTitle.tv_genres')}}</h4>
        </div>
        <div class="genres-grid gy-4 row row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 gy-4">
                <div class="col">
                    @include('frontend::components.cards.card-genres-grid', [
                        'image' => 'genre/action.webp',
                        'title' => __('streamTag.action'),
                        'genersUrl' => 'view-all'
                    ])
                </div>
                <div class="col">
                    @include('frontend::components.cards.card-genres-grid', [
                        'image' => 'genre/adventure.webp',
                        'title' => __('streamTag.adventure'),
                        'genersUrl' => 'view-all'
                    ])
                </div>
                <div class="col">
                    @include('frontend::components.cards.card-genres-grid', [
                        'image' => 'genre/animation.webp',
                        'title' => __('streamTag.animation'),
                        'genersUrl' => 'view-all'
                    ])
                </div>
                <div class="col">
                    @include('frontend::components.cards.card-genres-grid', [
                        'image' => 'genre/Crime.webp',
                        'title' => __('streamTag.crime'),
                        'genersUrl' => 'view-all'
                    ])
                </div>
                <div class="col">
                    @include('frontend::components.cards.card-genres-grid', [
                        'image' => 'genre/horror.webp',
                        'title' => __('streamTag.horror'),
                        'genersUrl' => 'view-all'
                    ])
                </div>
                <div class="col">
                    @include('frontend::components.cards.card-genres-grid', [
                        'image' => 'genre/mystery.webp',
                        'title' => __('streamTag.mystery'),
                        'genersUrl' => 'view-all'
                    ])
                </div>
                <div class="col">
                    @include('frontend::components.cards.card-genres-grid', [
                        'image' => 'genre/romance.webp',
                        'title' => __('streamTag.romance'),
                        'genersUrl' => 'view-all'
                    ])
                </div>
        </div>
    </div>
</section>


<section class="section-padding-bottom">
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h4 class="main-title text-capitalize mb-0">{{__('sectionTitle.video_genres')}}</h4>
        </div>
        <div class="genres-grid gy-4 row row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 gy-4">
                <div class="col">
                    @include('frontend::components.cards.card-genres-grid', [
                        'image' => 'genre/action.webp',
                        'title' => __('streamTag.action'),
                        'genersUrl' => 'view-all'
                    ])
                </div>
                <div class="col">
                    @include('frontend::components.cards.card-genres-grid', [
                        'image' => 'genre/adventure.webp',
                        'title' => __('streamTag.adventure'),
                        'genersUrl' => 'view-all'
                    ])
                </div>
                <div class="col">
                    @include('frontend::components.cards.card-genres-grid', [
                        'image' => 'genre/animation.webp',
                        'title' => __('streamTag.animation'),
                        'genersUrl' => 'view-all'
                    ])
                </div>
                <div class="col">
                    @include('frontend::components.cards.card-genres-grid', [
                        'image' => 'genre/Crime.webp',
                        'title' => __('streamTag.crime'),
                        'genersUrl' => 'view-all'
                    ])
                </div>
                <div class="col">
                    @include('frontend::components.cards.card-genres-grid', [
                        'image' => 'genre/horror.webp',
                        'title' => __('streamTag.horror'),
                        'genersUrl' => 'view-all'
                    ])
                </div>
                <div class="col">
                    @include('frontend::components.cards.card-genres-grid', [
                        'image' => 'genre/mystery.webp',
                        'title' => __('streamTag.mystery'),
                        'genersUrl' => 'view-all'
                    ])
                </div>
                <div class="col">
                    @include('frontend::components.cards.card-genres-grid', [
                        'image' => 'genre/romance.webp',
                        'title' => __('streamTag.romance'),
                        'genersUrl' => 'view-all'
                    ])
                </div>
        </div>
    </div>
</section>

{{-- Mobile Footer --}}
@include('frontend::components.widgets.mobile-footer')
{{-- Mobile Footer End --}}
@endsection