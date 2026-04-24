@extends('frontend::layouts.master', [
    'isSwiperSlider' => true,
    'bodyClass' => 'custom-header-relative',
    'title' => __('sectionTitle.upcoming'),
])

@section('content')
    @if ($featured->isNotEmpty())
        <section class="banner-container">
            <div class="movie-banner">
                <div class="swiper swiper-banner-container" data-swiper="banner-detail-slider">
                    <div class="swiper-wrapper">
                        @foreach ($featured as $i => $item)
                            @php
                                $isShow = ($item->_kind ?? 'movie') === 'show';
                                $fallbackImg = $isShow ? 'media/vikings.webp' : 'media/rabbit.webp';
                                $heroReleaseLabel = $item->published_at
                                    ? $item->published_at->format('M j, Y')
                                    : __('streamTag.release_tbd');
                            @endphp
                            @include('frontend::components.cards.movie-slider', [
                                'movieCard' => 'movie-banner-' . ($i + 1),
                                'imagePath' => $item->backdrop_url ?: $item->poster_url ?: $fallbackImg,
                                'movieRating' => true,
                                'movieTitle' => $item->title,
                                'movieRange' => $item->rating ?: '—',
                                'movieCate' => $item->tier_required ? strtoupper($item->tier_required) : 'PG',
                                'movieTime' => $heroReleaseLabel,
                                'movieYear' => $item->year ?: ($item->published_at?->format('F Y') ?? ''),
                                'calenderIcon' => true,
                                // Detail pages for upcoming titles aren't wired yet —
                                // anchor into the grid below so the CTA has somewhere
                                // to go instead of a dead #.
                                'buttonUrl' => '#jambo-upcoming-list',
                                'buttonLabel' => __('streamButtons.view_all'),
                                'movieText' => $item->synopsis ?: '',
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
    @endif

    <div class="section-padding view-all-movies upcoming-listing">
        <div class="container-fluid">
            @if ($total === 0)
                <div class="text-center py-5">
                    <h4 class="mb-2">{{ __('sectionTitle.upcoming') }}</h4>
                    <p class="text-muted">{{ __('streamTag.no_upcoming') }}</p>
                </div>
            @else
                <div class="card-style-grid">
                    <div class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 data-listing"
                         id="jambo-upcoming-list"
                         data-offset="{{ $items->count() }}"
                         data-total="{{ $total }}">
                        @include('frontend::components.partials.upcoming-cards', ['items' => $items])
                    </div>

                    @if ($hasMore)
                        <div class="text-center mt-4">
                            <button type="button" class="btn btn-primary position-relative"
                                    id="jambo-upcoming-load-more"
                                    data-endpoint="{{ route('frontend.upcoming_load_more') }}"
                                    data-page-size="{{ $pageSize }}">
                                <span class="button-text">{{ __('streamButtons.load_more') }}</span>
                            </button>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}

    <script>
    (function () {
        var btn = document.getElementById('jambo-upcoming-load-more');
        if (!btn) return;
        var list = document.getElementById('jambo-upcoming-list');
        var label = btn.querySelector('.button-text');
        var originalLabel = label.textContent;

        btn.addEventListener('click', async function () {
            var offset = parseInt(list.dataset.offset || '0', 10);
            var total  = parseInt(list.dataset.total  || '0', 10);
            if (offset >= total) {
                btn.parentElement.style.display = 'none';
                return;
            }

            btn.disabled = true;
            label.textContent = 'Loading…';

            try {
                var url = btn.dataset.endpoint
                    + '?offset=' + offset
                    + '&limit=' + (btn.dataset.pageSize || 20);

                var res = await fetch(url, {
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);

                var html = await res.text();
                var holder = document.createElement('div');
                holder.innerHTML = html;
                var appended = 0;
                while (holder.firstChild) {
                    var node = holder.firstChild;
                    list.appendChild(node);
                    // Count real element columns so the offset stays
                    // accurate even when whitespace text nodes sneak in
                    // from the server-rendered template.
                    if (node.nodeType === 1 && node.classList && node.classList.contains('col')) {
                        appended++;
                    }
                }

                list.dataset.offset = (offset + appended).toString();

                if (res.headers.get('X-Has-More') === '0'
                    || parseInt(list.dataset.offset, 10) >= total) {
                    btn.parentElement.style.display = 'none';
                } else {
                    btn.disabled = false;
                    label.textContent = originalLabel;
                }
            } catch (e) {
                console.warn('[upcoming-load-more]', e);
                btn.disabled = false;
                label.textContent = originalLabel;
            }
        });
    })();
    </script>
@endsection
