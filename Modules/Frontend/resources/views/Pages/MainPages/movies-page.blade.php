@extends('frontend::layouts.master', ['isSwiperSlider' => true, 'isFslightbox' => true, 'bodyClass' => 'custom-header-relative', 'isSweetalert' => true])

@section('content')
    <section class="banner-container">
        <div class="movie-banner">
            <div class="swiper swiper-banner-container" data-swiper="banner-detail-slider">
                <div class="swiper-wrapper">
                    @foreach ($featuredMovies as $i => $movie)
                        @include('frontend::components.cards.movie-slider', [
                            'movieCard' => 'movie-banner-' . ($i + 1),
                            'imagePath' => $movie->backdrop_url ?: $movie->poster_url ?: 'media/rabbit.webp',
                            'movieRating' => true,
                            'movieTitle' => $movie->title,
                            'movieRange' => $movie->rating ?: '4.0',
                            'movieCate' => $movie->tier_required ? strtoupper($movie->tier_required) : 'PG',
                            'movieTime' => $movie->runtime_minutes ? floor($movie->runtime_minutes / 60) . 'hr : ' . ($movie->runtime_minutes % 60) . 'm' : '1hr : 45m',
                            'movieYear' => $movie->year ?: ($movie->published_at?->format('F Y') ?? ''),
                            'calenderIcon' => true,
                            'buttonUrl' => route('frontend.movie_detail', $movie->slug),
                            'movieText' => $movie->synopsis ?: '',
                            'trailerUrl' => $movie->trailer_url ?: null,
                        ])
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

    {{-- No overflow-hidden wrapper: card-hover on .iq-card extends
         ~1.25em outside each card and ~5em below via the ::after
         pseudo, so clipping here cuts off the Play Now + wishlist
         reveal on the outer cards and the last row. Horizontal page
         overflow is handled at the body level in custom.css. --}}
    <div class="container-fluid pb-5 mb-4 px-2 px-md-3">
        <div>
            {{-- VJ carousels: each row is one VJ (narrator / translator).
                 Top 5 are rendered server-side; the rest are fetched by
                 the Load More button below, which appends server-rendered
                 HTML so this partial stays the single source of truth. --}}
            <div id="jambo-vj-list" data-offset="{{ $vjs->count() }}" data-total="{{ $vjsTotal }}">
                @forelse ($vjs as $vj)
                    @include('frontend::components.sections.vj-carousel', [
                        'vj' => $vj,
                        'items' => $vj->movies,
                        'contentKind' => 'movie',
                    ])
                @empty
                    <section class="related-movie-block mt-5">
                        <p class="text-muted">No VJ-tagged movies yet.</p>
                    </section>
                @endforelse
            </div>

            @if ($vjsTotal > $vjs->count())
                <div class="text-center mt-4 mb-5">
                    <button type="button" class="btn btn-outline-primary px-4 py-2"
                            id="jambo-load-more-vjs"
                            data-endpoint="{{ route('frontend.movie_more_vjs') }}">
                        <i class="ph ph-plus-circle me-2"></i>
                        <span class="label">Load More VJs</span>
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

        // Manual Swiper init for dynamically-injected rows. Mirrors the
        // breakpoints used by the template's bootstrap (swiper.js) so
        // late arrivals look identical to the rows rendered on the
        // initial page load.
        function initCardSwipers(root) {
            if (typeof window.Swiper !== 'function') return;
            root.querySelectorAll('.swiper.swiper-card').forEach(function (el) {
                if (el.swiper) return; // already wired
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

                if (parseInt(list.dataset.offset, 10) >= total || res.headers.get('X-Has-More') === '0') {
                    btn.parentElement.style.display = 'none';
                } else {
                    btn.disabled = false;
                    label.textContent = originalLabel;
                }
            } catch (e) {
                console.warn('[load-more-vjs]', e);
                btn.disabled = false;
                label.textContent = originalLabel;
            }
        });
    })();
    </script>
@endsection
