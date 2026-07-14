{{--
    /geners — the grid of every genre. A single genre (/geners/{slug})
    renders through Pages/MainPages/taxonomy-archive instead, the same
    VJ-grouped layout /categories/{slug} and /tag/{slug} use.
--}}
@extends('frontend::layouts.master', [
    'isBreadCrumb' => true,
    'title' => __('frontendheader.geners'),
])

@section('content')
    <section class="section-padding">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-12 my-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="main-title text-capitalize mb-0">{{ __('frontendheader.geners') }}</h5>
                    </div>
                </div>
            </div>
            <div class="row row-cols-xl-5 row-cols-md-2 row-cols-1 geners-card geners-style-grid">
                @forelse ($genres as $g)
                    <div class="col slide-items">
                        @include('frontend::components.cards.genres-card', [
                            'genersTitle' => $g->name,
                            'genersImage' => $g->featured_image_url ?: 'media/rabbit.webp',
                            'genersUrl' => route('frontend.genres', $g->slug),
                        ])
                    </div>
                @empty
                    <div class="col-12 text-center py-5 text-muted">{{ __('streamTag.no_results') ?? 'No genres yet.' }}</div>
                @endforelse
            </div>
        </div>
    </section>

    @include('frontend::components.widgets.mobile-footer')
@endsection
