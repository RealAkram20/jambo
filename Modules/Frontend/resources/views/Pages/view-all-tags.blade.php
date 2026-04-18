@extends('frontend::layouts.master', ['isBreadCrumb' => true, 'title' => __('frontendheader.tags')])

@section('content')
    <section class="section-padding tag-section" id="allTags">
        <div class="container-fluid">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h4 class="main-title text-capitalize mb-0">{{ __('frontendheader.tags') ?? 'Tags' }}</h4>
                @if ($tags->count())
                    <span class="text-muted">{{ $tags->count() }} tags</span>
                @endif
            </div>

            @if ($tags->count())
                <div class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 data-listing">
                    @foreach ($tags as $tag)
                        @php
                            $usage = ($tag->movies_count ?? 0) + ($tag->shows_count ?? 0);
                            $label = $usage > 0 ? $tag->name . ' · ' . $usage : $tag->name;
                        @endphp
                        <div class="col">
                            @include('frontend::components.cards.tags-card', [
                                'title'  => $label,
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
