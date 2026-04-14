@php $series = $tabSeries ?? collect(); @endphp
<div class="tab-slider otthome-tab-slider">
    <div class="slider">
        <div class="position-relative swiper swiper-card" data-slide="1" data-laptop="1" data-tab="1" data-mobile="1"
            data-mobile-sm="1" data-autoplay="false" data-loop="false" data-navigation="true" data-pagination="true"
            data-effect="fade">
            <ul class="p-0 swiper-wrapper m-0 list-inline">
                @foreach ($series as $i => $item)
                    @include('frontend::components.partials.tab-series-slide', [
                        'item' => $item,
                        'rank' => $i + 1,
                    ])
                @endforeach
            </ul>
            <div class="joint-arrows d-none d-lg-block">
                <div class="swiper-button swiper-button-next"></div>
                <div class="swiper-button swiper-button-prev"></div>
            </div>
            <div class="swiper-pagination d-block d-lg-none"></div>
        </div>
    </div>
</div>
