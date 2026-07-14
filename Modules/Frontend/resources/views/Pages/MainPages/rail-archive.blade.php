@extends('frontend::layouts.master', ['title' => $title])

@section('content')
    <section class="section-padding">
        <div class="container-fluid px-2 px-md-3">
            {{-- Header: rail title + catalogue size, mirroring the
                 search-page header so rail archives feel native. --}}
            <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mt-3 mb-4">
                <div>
                    <h4 class="main-title text-capitalize mb-1 fw-medium">{{ $title }}</h4>
                </div>
            </div>

            @if ($items->count())
                <div class="row row-cols-3 row-cols-md-4 row-cols-lg-6 row-cols-xl-7 g-3">
                    @foreach ($items as $item)
                        @php $isShow = $type === 'show'; @endphp
                        <div class="col">
                            @include('frontend::components.cards.card-style', [
                                'cardImage' => $item->poster_url ?: ($isShow ? 'media/vikings-portrait.webp' : 'media/rabbit-portrait.webp'),
                                'cardTitle' => $item->title,
                                'movietime' => ! $isShow && $item->runtime_minutes
                                    ? floor($item->runtime_minutes / 60) . 'hr : ' . ($item->runtime_minutes % 60) . 'mins'
                                    : null,
                                'cardLang' => 'English',
                                'cardPath' => $isShow
                                    ? route('frontend.series_detail', $item->slug)
                                    : route('frontend.movie_detail', $item->slug),
                                'cardGenres' => $item->genres->take(2)->pluck('name')->all(),
                                'productPremium' => (bool) $item->tier_required,
                                'watchableType' => $isShow ? 'show' : 'movie',
                                'watchableId'   => $item->id,
                            ])
                        </div>
                    @endforeach
                </div>

                @if ($items->hasPages())
                    <div class="d-flex justify-content-center mt-5">
                        {{ $items->links() }}
                    </div>
                @endif
            @else
                <div class="text-center py-5 my-4">
                    <i class="ph ph-film-strip text-muted" style="font-size: 56px;"></i>
                    <h5 class="mt-3 mb-2">Nothing here yet</h5>
                    <p class="text-muted mb-4">This collection is still filling up — check back soon.</p>
                    <a href="{{ route($type === 'show' ? 'frontend.series' : 'frontend.movie') }}" class="btn btn-primary">
                        {{ $type === 'show' ? 'Browse all series' : 'Browse all movies' }}
                    </a>
                </div>
            @endif
        </div>
    </section>

    @include('frontend::components.widgets.mobile-footer')
@endsection
