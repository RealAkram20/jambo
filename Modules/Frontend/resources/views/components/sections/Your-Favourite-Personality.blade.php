{{-- `--home` is the hook the round-avatar override hangs on. The movie and
     TV detail pages wrap their cast and crew rows in `favourite-person-block`
     too, so that class alone does not mean "the home rail". --}}
<div class="favourite-person-block favourite-person-block--home section-wraper">
    <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
        <h4 class="main-title text-capitalize mb-0 fw-medium">{{ $sectionHeading ?? __('sectionTitle.your_favourite_personality') }}</h4>
        <a href="{{ route('frontend.cast_list') }}" class="text-primary iq-view-all text-decoration-none">{{ __('streamButtons.view_all') }}</a>
    </div>
    {{--
        Four across on a phone, not two, and the avatars are round — Rio's
        instruction of 2026-09-10, with the app matching. The shape is a
        scoped override in `jambo-header.css` (ADR-0001) rather than an edit
        to `cards/personality-card`, which four cast rows on the detail pages
        also include.
    --}}
    <div class="position-relative swiper swiper-card" data-slide="11" data-laptop="11" data-tab="6" data-mobile="4"
        data-mobile-sm="4" data-autoplay="false" data-loop="true" data-navigation="true" data-pagination="true">
        <ul class="p-0 swiper-wrapper m-0 list-inline personality-card">
            @forelse ($favoritePersonalities ?? collect() as $person)
                <li class="swiper-slide">
                    @include('frontend::components.cards.personality-card', [
                        'castImage' => $person->photo_url ?: 'olivia-foster.webp',
                        'castTitle' => trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? '')),
                        'castCategory' => null,
                        'castLink' => route('frontend.cast_details', $person->slug),
                    ])
                </li>
            @empty
                <li class="swiper-slide"><p class="text-muted">{{ __('streamTag.no_results') ?? 'No personalities yet.' }}</p></li>
            @endforelse
        </ul>
        <div class="d-none d-lg-block">
            <div class="swiper-button swiper-button-next"></div>
            <div class="swiper-button swiper-button-prev"></div>
        </div>
    </div>
</div>
