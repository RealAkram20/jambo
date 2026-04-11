<div class="favourite-person-block section-wraper">
    <div class="d-flex align-items-center justify-content-between  px-1 mb-2 pb-1 mb-md-4 pb-md-0">
        <h4 class="main-title text-capitalize mb-0 fw-medium">{{ __('sectionTitle.your_favourite_personality') }}</h4>
        <a href="{{ route('frontend.all_personality') }}"
            class="text-primary iq-view-all text-decoration-none">{{ __('streamButtons.view_all') }}</a>
    </div>
    <div class="position-relative swiper swiper-card" data-slide="11" data-laptop="11" data-tab="4" data-mobile="2"
        data-mobile-sm="2" data-autoplay="false" data-loop="true" data-navigation="true" data-pagination="true">
        <ul class="p-0 swiper-wrapper m-0  list-inline personality-card">
            <li class="swiper-slide">
                @include('frontend::components.cards.personality-card', [
                    'castImage' => 'olivia-foster.webp',
                    'castTitle' => __('favouritePersonalities.olivia_foster'),
                    'castCategory' => __('favouritePersonalities.actress'),
                ])

            </li>
            <li class="swiper-slide">
                @include('frontend::components.cards.personality-card', [
                    'castImage' => 'leena-burton.webp',
                    'castTitle' => __('favouritePersonalities.Leena_Burton'),
                    'castCategory' => __('favouritePersonalities.actress'),
                ])

            </li>
            <li class="swiper-slide">
                @include('frontend::components.cards.personality-card', [
                    'castImage' => 'ryan-pierce.webp',
                    'castTitle' => __('favouritePersonalities.Ryan_Pierce'),
                    'castCategory' => __('favouritePersonalities.actor'),
                ])

            </li>
            <li class="swiper-slide">
                @include('frontend::components.cards.personality-card', [
                    'castImage' => 'michael-fox.webp',
                    'castTitle' => __('favouritePersonalities.michael_fox'),
                    'castCategory' => __('favouritePersonalities.actor'),
                ])

            </li>
            <li class="swiper-slide">
                @include('frontend::components.cards.personality-card', [
                    'castImage' => 'ruby-scott.webp',
                    'castTitle' => __('favouritePersonalities.ruby_scott'),
                    'castCategory' => __('favouritePersonalities.actor'),
                ])

            </li>
            <li class="swiper-slide">
                @include('frontend::components.cards.personality-card', [
                    'castImage' => 'maxwell-carter.webp',
                    'castTitle' => __('favouritePersonalities.maxwell_carter'),
                    'castCategory' => __('favouritePersonalities.actor'),
                ])

            </li>
            <li class="swiper-slide">
                @include('frontend::components.cards.personality-card', [
                    'castImage' => 'mark-smith.webp',
                    'castTitle' => __('favouritePersonalities.mark_smith'),
                    'castCategory' => __('favouritePersonalities.director'),
                ])

            </li>
            <li class="swiper-slide">
                @include('frontend::components.cards.personality-card', [
                    'castImage' => 'ava-monroe.webp',
                    'castTitle' => __('favouritePersonalities.ava_monroe'),
                    'castCategory' => __('favouritePersonalities.producer'),
                ])

            </li>
            <li class="swiper-slide">
                @include('frontend::components.cards.personality-card', [
                    'castImage' => 'jack-nicholson.webp',
                    'castTitle' => __('favouritePersonalities.jack_nicholson'),
                    'castCategory' => 'actor',
                ])

            </li>
            <li class="swiper-slide">
                @include('frontend::components.cards.personality-card', [
                    'castImage' => 'charles-melton.webp',
                    'castTitle' => __('favouritePersonalities.charles_melton'),
                    'castCategory' => __('favouritePersonalities.actor'),
                ])

            </li>
            <li class="swiper-slide">
                @include('frontend::components.cards.personality-card', [
                    'castImage' => 'jeff-bridges.webp',
                    'castTitle' => __('favouritePersonalities.jeff_bridges'),
                    'castCategory' => __('favouritePersonalities.actor'),
                ])

            </li>
            <li class="swiper-slide">
                @include('frontend::components.cards.personality-card', [
                    'castImage' => 'james-stewart.webp',
                    'castTitle' => __('favouritePersonalities.james_stewart'),
                    'castCategory' => __('favouritePersonalities.actor'),
                ])
            </li>
            <li class="swiper-slide">
                @include('frontend::components.cards.personality-card', [
                    'castImage' => 'jordan-grant.webp',
                    'castTitle' => __('favouritePersonalities.jordan_grant'),
                    'castCategory' => __('favouritePersonalities.actor'),
                ])
            </li>
        </ul>
        <div class="d-none d-lg-block">
               <div class="swiper-button swiper-button-next"></div>
               <div class="swiper-button swiper-button-prev"></div>
            </div>
    </div>
</div>
