@extends('frontend::layouts.master', ['isBreadCrumb' => true, 'title' => __('streamButtons.view_all')])

@section('content')
    <section class="section-padding">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-12 my-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="main-title text-capitalize mb-0">{{__('streamMovies.your_personality')}}</h5>
                    </div>
                </div>
            </div>
            <div class="card-style-grid">
            <div class="row row-cols-xl-5 row-cols-md-2 row-cols-1 personality-card">
                <div class="col mb-4">
                    @include('frontend::components.cards.cast', [
                        'castImg' => asset('frontend/images/cast/ava-monroe.webp'),
                        'id' => '01',
                        'castTime' => '8.4',
                        'castTitle' => 'Ava Monroe',
                        'castSubTitle' => 'actress',
                        'castLink' => route('frontend.cast_details'),
                    ])
                </div>
                <div class="col mb-4">
                    @include('frontend::components.cards.cast', [
                        'castImg' => asset('frontend/images/cast/charles-melton.webp'),
                        'id' => '02',
                        'castTime' => '8.4',
                        'castTitle' => 'Charles Melton',
                        'castSubTitle' => 'actor',
                        'castLink' => route('frontend.cast_details'),
                    ])
                </div>
                <div class="col mb-4">
                    @include('frontend::components.cards.cast', [
                        'castImg' => asset('frontend/images/cast/jack-nicholson.webp'),
                        'id' => '03',
                        'castTime' => '9.9',
                        'castTitle' => 'Jack Nicholson',
                        'castSubTitle' => 'actor',
                        'castLink' => route('frontend.cast_details'),
                    ])
                </div>
                <div class="col mb-4">
                    @include('frontend::components.cards.cast', [
                        'castImg' => asset('frontend/images/cast/james-stewart.webp'),
                        'id' => '04',
                        'castTime' => '8.5',
                        'castTitle' => 'James Stewart',
                        'castSubTitle' => 'actor',
                        'castLink' => route('frontend.cast_details'),
                    ])
                </div>
                <div class="col mb-4">
                    @include('frontend::components.cards.cast', [
                        'castImg' => asset('frontend/images/cast/jeff-bridges.webp'),
                        'id' => '05',
                        'castTime' => '9.4',
                        'castTitle' => 'Jeff Bridges',
                        'castSubTitle' => 'actress',
                        'castLink' => route('frontend.cast_details'),
                    ])
                </div>
                <div class="col mb-4">
                    @include('frontend::components.cards.cast', [
                        'castImg' => asset('frontend/images/cast/jordan-grant.webp'),
                        'id' => '06',
                        'castTime' => '9.4',
                        'castTitle' => 'Jordan Grant',
                        'castSubTitle' => 'actor',
                        'castLink' => route('frontend.cast_details'),
                    ])
                </div>
                <div class="col mb-4">@include('frontend::components.cards.cast', [
                    'castImg' => asset('frontend/images/cast/mark-smith.webp'),
                    'id' => '02',
                    'castTime' => '8.4',
                    'castTitle' => 'Mark Smith',
                    'castSubTitle' => 'producer',
                    'castLink' => route('frontend.cast_details'),
                ])
                </div>
                <div class="col mb-4">
                    @include('frontend::components.cards.cast', [
                        'castImg' => asset('frontend/images/cast/maxwell-carter.webp'),
                        'id' => '08',
                        'castTime' => '9',
                        'castTitle' => 'Maxwell Carter',
                        'castSubTitle' => 'actress',
                        'castLink' => route('frontend.cast_details'),
                    ])
                </div>
                <div class="col mb-4">
                    @include('frontend::components.cards.cast', [
                        'castImg' => asset('frontend/images/cast/ruby-scott.webp'),
                        'id' => '09',
                        'castTime' => '8.1',
                        'castTitle' => 'Ruby Scott',
                        'castSubTitle' => 'actress',
                        'castLink' => route('frontend.cast_details'),
                    ])
                </div>
                <div class="col mb-4">
                    @include('frontend::components.cards.cast', [
                        'castImg' => asset('frontend/images/cast/michael-fox.webp'),
                        'id' => '10',
                        'castTime' => '9.3',
                        'castTitle' => 'Michael Fox',
                        'castSubTitle' => 'actor',
                        'castLink' => route('frontend.cast_details'),
                    ])
                </div>
                <div class="col mb-4">
                    @include('frontend::components.cards.cast', [
                        'castImg' => asset('frontend/images/cast/ryan-pierce.webp'),
                        'id' => '11',
                        'castTime' => '9.7',
                        'castTitle' => 'Ryan Pierce',
                        'castSubTitle' => 'director',
                        'castLink' => route('frontend.cast_details'),
                    ])
                </div>
                <div class="col mb-4">
                    @include('frontend::components.cards.cast', [
                        'castImg' => asset('frontend/images/cast/john-abraham.webp'),
                        'id' => '12',
                        'castTime' => '9.4',
                        'castTitle' => 'John Abraham',
                        'castSubTitle' => 'actor',
                        'castLink' => route('frontend.cast_details'),
                    ])
                </div>
            </div>
            </div>
            {{-- <div class="text-center">
                @include('frontend::components.widgets.custom-button', [
                    'buttonTitle' => 'load more',
                    'buttonUrl' => 'javascript:void(0)',
                ])
            </div> --}}
        </div>
    </section>
@endsection
