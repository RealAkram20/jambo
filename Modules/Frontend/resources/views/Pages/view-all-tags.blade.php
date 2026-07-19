@extends('frontend::layouts.master', ['isBreadCrumb' => true, 'title' => __('frontendheader.tags')])

@section('content')
    <section class="section-padding tag-section" id="allTags">
        <div class="container-fluid">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h1 class="main-title text-capitalize mb-0 h4 fw-medium">{{ __('frontendheader.tags') ?? 'Tags' }}</h1>
            </div>

            @if ($tags->count())
                <div class="row gy-3 row-cols-3 row-cols-sm-3 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 data-listing">
                    @foreach ($tags as $tag)
                        <div class="col">
                            @include('frontend::components.cards.tags-card', [
                                'title'  => $tag->name,
                                'tagUrl' => route('frontend.tag', $tag->slug),
                            ])
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-muted">No tags have been created yet.</p>
            @endif
        </div>
    </section>

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
@endsection
