{{--
    /tag — the cloud of every tag. A single tag (/tag/{slug}) renders
    through Pages/MainPages/taxonomy-archive instead, the same
    VJ-grouped layout /categories/{slug} and /geners/{slug} use.
--}}
@extends('frontend::layouts.master', [
    'isBreadCrumb' => true,
    'title' => __('frontendheader.tags'),
])

@section('content')
    <section class="section-padding">
        <div class="container-fluid">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h4 class="main-title text-capitalize mb-0">{{ __('frontendheader.tags') }}</h4>
            </div>
            <div class="row g-3 g-lg-4 row-cols-3 row-cols-sm-3 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 row-cols-xxl-6">
                @forelse ($tags as $t)
                    <div class="col">
                        @include('frontend::components.cards.tags-card', [
                            'title' => $t->name,
                            'tagUrl' => route('frontend.tag', $t->slug),
                        ])
                    </div>
                @empty
                    <div class="col-12 text-center py-5 text-muted">{{ __('streamTag.no_results') ?? 'No tags yet.' }}</div>
                @endforelse
            </div>
        </div>
    </section>

    @include('frontend::components.widgets.mobile-footer')
@endsection
