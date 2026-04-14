@php $items = $verticalFeatured ?? collect(); @endphp
<div class="verticle-slider section-padding-bottom">
    <div class="slider">
        <div class="slider-flex position-relative">
            <div class="slider--col position-relative">
                <div class="vertical-slider-prev swiper-button"><i class="iconly-Arrow-Up-2 icli"></i></div>
                <div class="slider-thumbs" data-swiper="slider-thumbs">
                    <div class="swiper-container" data-swiper="slider-thumbs-inner">
                        <div class="swiper-wrapper top-ten-slider-nav">
                            @foreach ($items as $item)
                                @include('frontend::components.partials.vertical-thumb', ['item' => $item])
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="vertical-slider-next swiper-button"><i class="iconly-Arrow-Down-2 icli"></i></div>
            </div>
            <div class="slider-images" data-swiper="slider-images">
                <div class="swiper-container" data-swiper="slider-images-inner">
                    <div class="swiper-wrapper">
                        @foreach ($items as $item)
                            @include('frontend::components.partials.vertical-banner', ['item' => $item])
                        @endforeach
                        <div class="swiper-button swiper-button-next"></div>
                        <div class="swiper-button swiper-button-prev"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
