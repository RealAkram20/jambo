@php
    /**
     * Genre-scoped "by VJ" page. Powers both:
     *   $contentKind === 'movie'  → /geners/{slug}/vjs
     *   $contentKind === 'show'   → /geners/{slug}/series-vjs
     *
     * The two flavours share layout, hero swiper, and VJ-carousel
     * rendering; only the route names, relations used, and a couple
     * of labels differ. Keeping them in one template so the hero /
     * grid structure stays in lock-step with /movie and /series.
     */
    $contentKind = $contentKind ?? 'movie';
    $isShow = $contentKind === 'show';
    $loadMoreRoute = $isShow ? 'frontend.genre_vjs_shows_more' : 'frontend.genre_vjs_more';
    $kindLabel = $isShow ? __('frontendheader.tvshow') : __('frontendheader.movies');
@endphp

@extends('frontend::layouts.master', [
    'isSwiperSlider' => true,
    'isFslightbox' => true,
    'bodyClass' => 'custom-header-relative',
    'isSweetalert' => true,
    'title' => $genre->name . ' — ' . $kindLabel,
])

@section('content')
    @if ($featured->isNotEmpty())
        <section class="banner-container">
            <div class="movie-banner">
                <div class="swiper swiper-banner-container" data-swiper="banner-detail-slider">
                    <div class="swiper-wrapper">
                        @foreach ($featured as $i => $item)
                            @php
                                $fallback = $isShow ? 'media/vikings.webp' : 'media/rabbit.webp';
                                $detailUrl = $isShow
                                    ? route('frontend.series_detail', $item->slug)
                                    : route('frontend.movie_detail', $item->slug);
                            @endphp
                            @include('frontend::components.cards.movie-slider', array_filter([
                                'movieCard' => 'movie-banner-' . ($i + 1),
                                'imagePath' => $item->backdrop_url ?: $item->poster_url ?: $fallback,
                                'movieRating' => true,
                                'movieTitle' => $item->title,
                                'movieRange' => $item->rating ?: '4.0',
                                'movieCate' => $item->tier_required ? strtoupper($item->tier_required) : 'PG',
                                'movieTime' => !$isShow && $item->runtime_minutes
                                    ? floor($item->runtime_minutes / 60) . 'hr : ' . ($item->runtime_minutes % 60) . 'm'
                                    : null,
                                'NoOfSeasons' => $isShow && $item->relationLoaded('seasons')
                                    ? $item->seasons->count()
                                    : null,
                                'movieYear' => $item->year ?: ($item->published_at?->format('F Y') ?? ''),
                                'calenderIcon' => true,
                                'buttonUrl' => $detailUrl,
                                'movieText' => $item->synopsis ?: '',
                                'trailerUrl' => $item->trailer_url ?: null,
                            ], fn ($v) => $v !== null))
                        @endforeach
                    </div>
                    <div class="swiper-banner-button-next d-none d-lg-block">
                        <i class="ph ph-caret-right arrow-icon"></i>
                    </div>
                    <div class="swiper-banner-button-prev d-none d-lg-block">
                        <i class="ph ph-caret-left icli arrow-icon"></i>
                    </div>
                    <div class="swiper-pagination d-block d-lg-none"></div>
                </div>
            </div>
        </section>
    @endif

    <div class="container-fluid pb-5 mb-4 px-2 px-md-3">
        <div class="d-flex align-items-center justify-content-between pt-4 pb-2">
            <div>
                <h2 class="main-title text-capitalize mb-0">{{ $genre->name }} {{ $kindLabel }}</h2>
                <p class="text-muted mb-0 mt-1" style="font-size:13px;">
                    {{ __('streamTag.genre') }}: {{ $genre->name }}
                </p>
            </div>
            <a href="{{ route('frontend.genres', $genre->slug) }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="ph ph-arrow-left me-1"></i>
                {{ __('sectionTitle.recommended') }}
            </a>
        </div>

        <div>
            <div id="jambo-vj-list"
                 data-offset="{{ $vjs->count() }}"
                 data-total="{{ $vjsTotal }}">
                @forelse ($vjs as $vj)
                    @include('frontend::components.sections.vj-carousel', [
                        'vj' => $vj,
                        'items' => $isShow ? $vj->shows : $vj->movies,
                        'contentKind' => $contentKind,
                    ])
                @empty
                    <section class="related-movie-block mt-5">
                        <p class="text-muted">{{ __('streamTag.no_results') }}</p>
                    </section>
                @endforelse
            </div>

            @if ($vjsTotal > $vjs->count())
                <div class="text-center mt-4 mb-5">
                    <button type="button" class="btn btn-outline-primary px-4 py-2"
                            id="jambo-load-more-vjs"
                            data-endpoint="{{ route($loadMoreRoute, $genre->slug) }}">
                        <i class="ph ph-plus-circle me-2"></i>
                        <span class="label">{{ __('streamButtons.load_more') }}</span>
                    </button>
                </div>
            @endif
        </div>
    </div>

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}

    <script>
    (function () {
        var btn = document.getElementById('jambo-load-more-vjs');
        if (!btn) return;
        var list = document.getElementById('jambo-vj-list');
        var label = btn.querySelector('.label');

        function initCardSwipers(root) {
            if (typeof window.Swiper !== 'function') return;
            root.querySelectorAll('.swiper.swiper-card').forEach(function (el) {
                if (el.swiper) return;
                var d = function (k) { return el.getAttribute('data-' + k); };
                var num = function (v, fb) { var n = parseFloat(v); return isFinite(n) ? n : fb; };
                new Swiper(el, {
                    slidesPerView: num(d('slide'), 4),
                    spaceBetween: 0,
                    loop: d('loop') === 'true',
                    navigation: {
                        nextEl: el.querySelector('.swiper-button-next'),
                        prevEl: el.querySelector('.swiper-button-prev'),
                    },
                    pagination: d('pagination') === 'true'
                        ? { el: el.querySelector('.swiper-pagination'), clickable: true }
                        : false,
                    breakpoints: {
                        0:    { slidesPerView: num(d('mobile-sm'), 2) },
                        576:  { slidesPerView: num(d('mobile'),    2) },
                        768:  { slidesPerView: num(d('tab'),       3) },
                        1025: { slidesPerView: num(d('laptop'),    5) },
                        1500: { slidesPerView: num(d('slide'),     7) },
                    },
                });
            });
        }

        btn.addEventListener('click', async function () {
            var offset = parseInt(list.dataset.offset || '0', 10);
            var total  = parseInt(list.dataset.total  || '0', 10);
            if (offset >= total) return;

            btn.disabled = true;
            var originalLabel = label.textContent;
            label.textContent = 'Loading…';

            try {
                var res = await fetch(btn.dataset.endpoint + '?offset=' + offset + '&limit=5', {
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);

                var html = await res.text();
                var holder = document.createElement('div');
                holder.innerHTML = html;
                var appended = [];
                while (holder.firstChild) {
                    var node = holder.firstChild;
                    list.appendChild(node);
                    if (node.nodeType === 1) appended.push(node);
                }

                var newCount = appended.filter(function (n) {
                    return n.classList && n.classList.contains('jambo-vj-row');
                }).length;
                list.dataset.offset = (offset + newCount).toString();

                appended.forEach(initCardSwipers);

                if (parseInt(list.dataset.offset, 10) >= total
                    || res.headers.get('X-Has-More') === '0') {
                    btn.parentElement.style.display = 'none';
                } else {
                    btn.disabled = false;
                    label.textContent = originalLabel;
                }
            } catch (e) {
                console.warn('[genre-vjs-load-more]', e);
                btn.disabled = false;
                label.textContent = originalLabel;
            }
        });
    })();
    </script>
@endsection
