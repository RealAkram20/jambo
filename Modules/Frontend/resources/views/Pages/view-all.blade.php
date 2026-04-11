@extends('frontend::layouts.master', ['isBreadCrumb' => true, 'title' => __('streamButtons.view_all')])

@section('content')
    <div class="section-padding view-all-movies">
        <div class="container-fluid">
            <div class="card-style-grid">
                <div class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 data-listing">
                    <div class="col">
                        @include('frontend::components.cards.card-style', [
                            'cardImage' => 'media/the-first-of-us-portrait.webp',
                            'cardTitle' => __('streamMovies.the_first_of_us'),
                            'movietime' => '2:59',
                            'cardLang' => 'English',
                        ])
                    </div>
                    <div class="col">
                        @include('frontend::components.cards.card-style', [
                            'cardImage' => 'media/gameofhero-portrait.webp',
                            'cardTitle' => __('streamMovies.game_of_heros'),
                            'movietime' => '2:30',
                            'cardLang' => 'English',
                        ])
                    </div>
                    <div class="col">
                        @include('frontend::components.cards.card-style', [
                            'cardImage' => 'media/venom-portrait.webp',
                            'cardTitle' => __('streamMovies.venom'),
                            'movietime' => '2:03',
                            'cardLang' => 'English',
                        ])
                    </div>
                    <div class="col">
                        @include('frontend::components.cards.card-style', [
                            'cardImage' => 'media/rabbit-portrait.webp',
                            'cardTitle' => __('streamMovies.rabbit'),
                            'movietime' => '2:30',
                            'cardLang' => 'English',
                            'productPremium' => true,
                        ])
                    </div>
                    <div class="col">
                        @include('frontend::components.cards.card-style', [
                            'cardImage' => 'media/migration-portrait.webp',
                            'cardTitle' => __('streamMovies.migration'),
                            'movietime' => '2:04',
                            'cardLang' => 'English',
                        ])
                    </div>
                    <div class="col">
                        @include('frontend::components.cards.card-style', [
                            'cardImage' => 'media/krishna-portrait.webp',
                            'cardTitle' => __('streamMovies.krishna'),
                            'movietime' => '2:45',
                            'cardLang' => 'English',
                        ])
                    </div>
                    <div class="col">
                        @include('frontend::components.cards.card-style', [
                            'cardImage' => 'media/kali-portrait.webp',
                            'cardTitle' => __('streamMovies.kali'),
                            'movietime' => '2:00',
                            'cardLang' => 'English',
                        ])
                    </div>
                    <div class="col">
                        @include('frontend::components.cards.card-style', [
                            'cardImage' => 'media/joker-portrait.webp',
                            'cardTitle' => __('streamMovies.joker'),
                            'movietime' => '2:45',
                            'cardLang' => 'English',
                        ])
                    </div>
                    <div class="col">
                        @include('frontend::components.cards.card-style', [
                            'cardImage' => 'media/deadpool-portrait.webp',
                            'cardTitle' => __('streamMovies.deadpool'),
                            'movietime' => '2:14',
                            'cardLang' => 'English',
                        ])
                    </div>
                    <div class="col">
                        @include('frontend::components.cards.card-style', [
                            'cardImage' => 'media/any-name-portrait.webp',
                            'cardTitle' => __('streamMovies.any_name'),
                            'movietime' => '2:20',
                            'cardLang' => 'English',
                        ])
                    </div>

                    <div class="col hidden-tags" style="display: none;">
                        @include('frontend::components.cards.card-style', [
                            'cardImage' => 'media/batter-caill-portrait.webp',
                            'cardTitle' => __('streamMovies.better_call_saul'),
                            'movietime' => '2:30',
                            'cardLang' => 'English',
                        ])
                    </div>
                    <div class="col hidden-tags" style="display: none;">
                        @include('frontend::components.cards.card-style', [
                            'cardImage' => 'media/jumanji-portrait.webp',
                            'cardTitle' => __('streamMovies.jumanji'),
                            'movietime' => '2:03',
                            'cardLang' => 'English',
                        ])
                    </div>
                    <div class="col hidden-tags" style="display: none;">
                        @include('frontend::components.cards.card-style', [
                            'cardImage' => 'media/yoshi-portrait.webp',
                            'cardTitle' => __('streamMovies.yoshi'),
                            'movietime' => '2:15',
                            'cardLang' => 'English',
                        ])
                    </div>
                    <div class="col hidden-tags" style="display: none;">
                        @include('frontend::components.cards.card-style', [
                            'cardImage' => 'media/the-co-noueriing-portrait.webp',
                            'cardTitle' => __('streamMovies.the_co_noueriing'),
                            'movietime' => '2:07',
                            'cardLang' => 'English (Canada)',
                            'productPremium' => true,
                        ])
                    </div>
                    <div class="col hidden-tags" style="display: none;">
                        @include('frontend::components.cards.card-style', [
                            'cardImage' => 'media/giirek-portrait.webp',
                            'cardTitle' => __('streamMovies.giikre'),
                            'movietime' => '2:31',
                            'cardLang' => 'English',
                            'productPremium' => true,
                        ])
                    </div>

                    <div class="col hidden-tags" style="display: none;">
                        @include('frontend::components.cards.card-style', [
                            'cardImage' => 'media/another-danger-portrait.webp',
                            'cardTitle' => __('streamMovies.another_danger'),
                            'movietime' => '2:15',
                            'cardLang' => 'English',
                            'productPremium' => true,
                        ])
                    </div>
                    <div class="col hidden-tags" style="display: none;">
                        @include('frontend::components.cards.card-style', [
                            'cardImage' => 'media/te-oaler-portrait.webp',
                            'cardTitle' => __('streamMovies.tf_oaler'),
                            'movietime' => '2:09',
                            'cardLang' => 'English',
                        ])
                    </div>
                    <div class="col hidden-tags" style="display: none;">
                        @include('frontend::components.cards.card-style', [
                            'cardImage' => 'media/kung-fu-panda-portrait.webp',
                            'cardTitle' => __('streamMovies.kung_fu_panda'),
                            'movietime' => '2:30',
                            'cardLang' => 'English',
                            'productPremium' => true,
                        ])
                    </div>
                    <div class="col hidden-tags" style="display: none;">
                        @include('frontend::components.cards.card-style', [
                            'cardImage' => 'media/john-wick-portrait.webp',
                            'cardTitle' => __('streamMovies.john_wick'),
                            'movietime' => '1:44',
                            'cardLang' => 'English (New Zealand)',
                            'productPremium' => true,
                        ])
                    </div>
                    <div class="col hidden-tags" style="display: none;">
                        @include('frontend::components.cards.card-style', [
                            'cardImage' => 'media/revenge-of-the-sith-portrait.webp',
                            'cardTitle' => __('streamMovies.revenge_of_sith'),
                            'movietime' => '2:16',
                            'productPremium' => true,
                            'productPremium' => true,
                        ])
                    </div>

                    <div class="col hidden-tags" style="display: none;">
                        @include('frontend::components.cards.card-style', [
                            'cardImage' => 'media/spiderman-portrait.webp',
                            'cardTitle' => __('streamMovies.spiderman'),
                            'movietime' => '2:30',
                            'productPremium' => true,
                            'isnotlangCard' => true,
                        ])
                    </div>
                    <div class="col hidden-tags" style="display: none;">
                        @include('frontend::components.cards.card-style', [
                            'cardImage' => 'media/war-for-the-planet-portrait.webp',
                            'cardTitle' => __('streamMovies.war_for_the_planet'),
                            'movietime' => '2:25',
                        ])
                    </div>
                    <div class="col hidden-tags" style="display: none;">
                        @include('frontend::components.cards.card-style', [
                            'cardImage' => 'media/fast-furious-portrait.webp',
                            'cardTitle' => __('streamMovies.fast_&_furious'),
                            'movietime' => '2:09',
                            'productPremium' => true,
                        ])
                    </div>
                    <div class="col hidden-tags" style="display: none;">
                        @include('frontend::components.cards.card-style', [
                            'cardImage' => 'media/krishna-portrait.webp',
                            'cardTitle' => __('streamMovies.krishna'),
                            'movietime' => '2:17',
                        ])
                    </div>
                    <div class="col hidden-tags" style="display: none;">
                        @include('frontend::components.cards.card-style', [
                            'cardImage' => 'media/dinoosaur-portrait.webp',
                            'cardTitle' => __('streamMovies.dinoosaur'),
                            'movietime' => '2:06',
                        ])
                    </div>

                    <div class="col hidden-tags" style="display: none;">
                        @include('frontend::components.cards.card-style', [
                            'cardImage' => 'media/chosfies-portrait.webp',
                            'cardTitle' => __('streamMovies.chosfies'),
                            'movietime' => '2:06',
                            'cardLang' => 'English',
                        ])
                    </div>
                    <div class="col hidden-tags" style="display: none;">
                        @include('frontend::components.cards.card-style', [
                            'cardImage' => 'media/bumblebee-portrait.webp',
                            'cardTitle' => __('streamMovies.bumblebee'),
                            'movietime' => '2:06',
                            'cardLang' => 'English (South Africa)',
                        ])
                    </div>
                    <div class="col hidden-tags" style="display: none;">
                        @include('frontend::components.cards.card-style', [
                            'cardImage' => 'media/batter-caill-portrait.webp',
                            'cardTitle' => __('streamMovies.better_call_saul'),
                            'movietime' => '2:30',
                            'cardLang' => 'English',
                        ])
                    </div>
                    <div class="col hidden-tags" style="display: none;">
                        @include('frontend::components.cards.card-style', [
                            'cardImage' => 'media/arrival-portrait.webp',
                            'cardTitle' => __('streamMovies.arrival'),
                            'movietime' => '2:15',
                            'cardLang' => 'English',
                        ])
                    </div>
                    <div class="col hidden-tags" style="display: none;">
                        @include('frontend::components.cards.card-style', [
                            'cardImage' => 'media//the-hunter-portrait.webp',
                            'cardTitle' => __('streamMovies.the_hunter'),
                            'movietime' => '2:30',
                            'cardLang' => 'English (Canada)',
                        ])
                    </div>
                </div>
                <div class="text-center mt-4">
                    <button type="button" class="btn btn-primary position-relative load-more-btn">
                        <span class="button-text">{{ __('streamButtons.load_more') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}
@endsection