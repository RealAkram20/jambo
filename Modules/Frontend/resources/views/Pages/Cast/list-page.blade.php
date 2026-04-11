@extends('frontend::layouts.master', ['isBreadCrumb' => true, 'title' => __('frontendheader.cast')])

@section('content')
    <section class="section-padding">
        <div class="container-fluid">
            <div class="data-listing row gy-5 row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 row-cols-xl-5">
                <div class="col">
                    @include('frontend::components.cards.cast', [
                        'castImg' => 'ava-monroe.webp',
                        'id' => '01',
                        'castTime' => '8.4',
                        'castTitle' => __('favouritePersonalities.ava_monroe'),
                        'castSubTitle' => __('favouritePersonalities.actress'),
                        'castLink' => route('frontend.cast_details'),
                    ])
                </div>
                <div class="col">
                    @include('frontend::components.cards.cast', [
                        'castImg' => 'charles-melton.webp',
                        'id' => '02',
                        'castTime' => '9',
                        'castTitle' => __('favouritePersonalities.charles_melton'),
                        'castSubTitle' => __('favouritePersonalities.actor'),
                        'castLink' => route('frontend.cast_details'),
                    ])
                </div>
                <div class="col">
                    @include('frontend::components.cards.cast', [
                        'castImg' => 'jack-nicholson.webp',
                        'id' => '03',
                        'castTime' => '9.9',
                        'castTitle' => __('favouritePersonalities.jack_nicholson'),
                        'castSubTitle' => __('favouritePersonalities.actor'),
                        'castLink' => route('frontend.cast_details'),
                    ])
                </div>
                <div class="col">
                    @include('frontend::components.cards.cast', [
                        'castImg' => 'james-stewart.webp',
                        'id' => '04',
                        'castTime' => '8.5',
                        'castTitle' => __('favouritePersonalities.james_stewart'),
                        'castSubTitle' => __('favouritePersonalities.actor'),
                        'castLink' => route('frontend.cast_details'),
                    ])
                </div>
                <div class="col">
                    @include('frontend::components.cards.cast', [
                        'castImg' => 'jeff-bridges.webp',
                        'id' => '05',
                        'castTime' => '9.4',
                        'castTitle' => __('favouritePersonalities.jeff_bridges'),
                        'castSubTitle' => __('favouritePersonalities.actor'),
                        'castLink' => route('frontend.cast_details'),
                    ])
                </div>
                <div class="col">
                    @include('frontend::components.cards.cast', [
                        'castImg' => 'jordan-grant.webp',
                        'id' => '06',
                        'castTime' => '9.4',
                        'castTitle' => __('favouritePersonalities.jordan_grant'),
                        'castSubTitle' => __('favouritePersonalities.actor'),
                        'castLink' => route('frontend.cast_details'),
                    ])
                </div>
                <div class="col">
                    @include('frontend::components.cards.cast', [
                        'castImg' => 'mark-smith.webp',
                        'id' => '07',
                        'castTime' => '10',
                        'castTitle' => __('favouritePersonalities.mark_smith'),
                        'castSubTitle' => __('favouritePersonalities.producer'),
                        'castLink' => route('frontend.cast_details'),
                    ])
                </div>
                <div class="col">
                    @include('frontend::components.cards.cast', [
                        'castImg' => 'maxwell-carter.webp',
                        'id' => '10',
                        'castTime' => '9.3',
                        'castTitle' => __('favouritePersonalities.maxwell_carter'),
                        'castSubTitle' => __('favouritePersonalities.director'),
                        'castLink' => route('frontend.cast_details'),
                    ])
                </div>
                <div class="col">
                    @include('frontend::components.cards.cast', [
                        'castImg' => 'ruby-scott.webp',
                        'id' => '08',
                        'castTime' => '9',
                        'castTitle' => __('favouritePersonalities.ruby_scott'),
                        'castSubTitle' => __('favouritePersonalities.director'),
                        'castLink' => route('frontend.cast_details'),
                    ])
                </div>
                <div class="col">
                    @include('frontend::components.cards.cast', [
                        'castImg' => 'michael-fox.webp',
                        'id' => '09',
                        'castTime' => '8.1',
                        'castTitle' => __('favouritePersonalities.michael_fox'),
                        'castSubTitle' => __('favouritePersonalities.actor'),
                        'castLink' => route('frontend.cast_details'),
                    ])
                </div>
                <div class="col">
                    @include('frontend::components.cards.cast', [
                        'castImg' => 'ryan-pierce.webp',
                        'id' => '11',
                        'castTime' => '9.7',
                        'castTitle' => __('favouritePersonalities.Ryan_Pierce'),
                        'castSubTitle' => __('favouritePersonalities.actor'),
                        'castLink' => route('frontend.cast_details'),
                    ])
                </div>
                <div class="col">
                    @include('frontend::components.cards.cast', [
                        'castImg' => 'john-abraham.webp',
                        'id' => '12',
                        'castTime' => '9.4',
                        'castTitle' => __('favouritePersonalities.john_abraham'),
                        'castSubTitle' => __('favouritePersonalities.director'),
                        'castLink' => route('frontend.cast_details'),
                    ])
                </div>
                <div class="col">
                    @include('frontend::components.cards.cast', [
                        'castImg' => 'leena-burton.webp',
                        'id' => '13',
                        'castTime' => '9.4',
                        'castTitle' => __('favouritePersonalities.Leena_Burton'),
                        'castSubTitle' => __('favouritePersonalities.director'),
                        'castLink' => route('frontend.cast_details'),
                    ])
                </div>
                <div class="col">
                    @include('frontend::components.cards.cast', [
                        'castImg' => 'davina-decorous.webp',
                        'id' => '14',
                        'castTime' => '9.4',
                        'castTitle' => __('favouritePersonalities.davina_decorous'),
                        'castSubTitle' => __('favouritePersonalities.director'),
                        'castLink' => route('frontend.cast_details'),
                    ])
                </div>
                <div class="col">
                    @include('frontend::components.cards.cast', [
                        'castImg' => 'thomas-bailey.webp',
                        'id' => '15',
                        'castTime' => '9.4',
                        'castTitle' => __('favouritePersonalities.thomas_bailey'),
                        'castSubTitle' => __('favouritePersonalities.director'),
                        'castLink' => route('frontend.cast_details'),
                    ])
                </div>
                <div class="col">
                    @include('frontend::components.cards.cast', [
                        'castImg' => 'kelly-reilly.webp',
                        'id' => '16',
                        'castTime' => '9.4',
                        'castTitle' => __('favouritePersonalities.kelly_reilly'),
                        'castSubTitle' => __('favouritePersonalities.director'),
                        'castLink' => route('frontend.cast_details'),
                    ])
                </div>
                <div class="col">
                    @include('frontend::components.cards.cast', [
                        'castImg' => 'mark-smith.webp',
                        'id' => '17',
                        'castTime' => '9.4',
                        'castTitle' => __('favouritePersonalities.mark_smith'),
                        'castSubTitle' => __('favouritePersonalities.director'),
                        'castLink' => route('frontend.cast_details'),
                    ])
                </div>
                <div class="col">
                    @include('frontend::components.cards.cast', [
                        'castImg' => 'angel-louis.webp',
                        'id' => '18',
                        'castTime' => '9.4',
                        'castTitle' => __('favouritePersonalities.angel_louis'),
                        'castSubTitle' => __('favouritePersonalities.director'),
                        'castLink' => route('frontend.cast_details'),
                    ])
                </div>
                <div class="col">
                    @include('frontend::components.cards.cast', [
                        'castImg' => 'millar-joseph.webp',
                        'id' => '19',
                        'castTime' => '9.4',
                        'castTitle' => __('favouritePersonalities.millar_joseph'),
                        'castSubTitle' => __('favouritePersonalities.director'),
                        'castLink' => route('frontend.cast_details'),
                    ])
                </div>
                <div class="col">
                    @include('frontend::components.cards.cast', [
                        'castImg' => 'mark-wilson.webp',
                        'id' => '20',
                        'castTime' => '9.4',
                        'castTitle' => __('favouritePersonalities.mark_wilson'),
                        'castSubTitle' => __('favouritePersonalities.director'),
                        'castLink' => route('frontend.cast_details'),
                    ])
                </div>
                <div class="col">
                    @include('frontend::components.cards.cast', [
                        'castImg' => 'jack-walker.webp',
                        'id' => '21',
                        'castTime' => '9.4',
                        'castTitle' => __('favouritePersonalities.jack_walker'),
                        'castSubTitle' => __('favouritePersonalities.director'),
                        'castLink' => route('frontend.cast_details'),
                    ])
                </div>
                <div class="col">
                    @include('frontend::components.cards.cast', [
                        'castImg' => 'benjamin-hayes.webp',
                        'id' => '22',
                        'castTime' => '9.4',
                        'castTitle' => __('favouritePersonalities.benjamin_hayes'),
                        'castSubTitle' => __('favouritePersonalities.director'),
                        'castLink' => route('frontend.cast_details'),
                    ])
                </div>
                <div class="col">
                    @include('frontend::components.cards.cast', [
                        'castImg' => 'olivia-foster.webp',
                        'id' => '23',
                        'castTime' => '9.4',
                        'castTitle' => __('favouritePersonalities.olivia_foster'),
                        'castSubTitle' => __('favouritePersonalities.director'),
                        'castLink' => route('frontend.cast_details'),
                    ])
                </div>
                <div class="col">
                    @include('frontend::components.cards.cast', [
                        'castImg' => 'ethan-crawford.webp',
                        'id' => '24',
                        'castTime' => '9.4',
                        'castTitle' => __('favouritePersonalities.ethan_crawford'),
                        'castSubTitle' => __('favouritePersonalities.director'),
                        'castLink' => route('frontend.cast_details'),
                    ])
                </div>
                <div class="col">
                    @include('frontend::components.cards.cast', [
                        'castImg' => 'maria-rodriguez.webp',
                        'id' => '25',
                        'castTime' => '9.4',
                        'castTitle' => __('favouritePersonalities.maria_rodriguez'),
                        'castSubTitle' => __('favouritePersonalities.director'),
                        'castLink' => route('frontend.cast_details'),
                    ])
                </div>
            </div>
        </div>
    </section>

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}
@endsection
