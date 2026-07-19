{{--
    Genre-scoped VJ catalogue — one page per VJ × genre with content,
    shared by /vj-movie/{slug}/{genre} and /vj-series/{slug}/{genre}.

    This page exists to rank for the "VJ Junior Action Movies" query
    family. The keyword lives in the <title>, <h1> and meta description
    in spoken word order; the URL stays a plain slug pair.

    Expects: $vj, $genre, $items (paginator), $contentKind 'movie'|'show'
--}}
@php
    $isShow    = $contentKind === 'show';
    $kindLabel = $isShow ? 'Series' : 'Movies';

    // "VJ Junior Action Movies" — display_name normalises Vj → VJ.
    $pageTitle = $vj->display_name . ' ' . $genre->name . ' ' . $kindLabel;

    // Count + genre + VJ makes every one of these descriptions unique —
    // 36 VJs × N genres sharing one templated string is the thin-content
    // trap the VJ bio handling already avoids.
    $pageDescription = 'Watch ' . $items->total() . ' ' . $genre->name . ' '
        . ($isShow ? 'series' : 'movies') . ' translated by ' . $vj->display_name
        . ' free on ' . app_name() . '.';

    $parentRoute = $isShow
        ? route('frontend.vj_series_detail', $vj->slug)
        : route('frontend.vj_movie_detail', $vj->slug);
    $pageUrl = $isShow
        ? route('frontend.vj_series_genre', [$vj->slug, $genre->slug])
        : route('frontend.vj_movie_genre', [$vj->slug, $genre->slug]);
@endphp

@extends('frontend::layouts.master', [
    'isSweetalert' => true,
    'title' => $pageTitle,
])

@section('seo:description', $pageDescription)
@section('seo:type', 'profile')

@if ($vj->featured_image_url)
    @section('seo:image', media_url($vj->featured_image_url))
@endif

{{-- CollectionPage pointing at the same Person @id the hub declares, so
     this page reads as another shelf of the same entity's work. --}}
@push('seo:head')
    @include('seo::partials.json-ld', [
        'schemas' => [
            \Modules\Seo\app\Support\StructuredData::vjCollection(
                $vj,
                $pageUrl,
                $pageTitle,
                $items->items(),
            ),
            \Modules\Seo\app\Support\StructuredData::breadcrumbs([
                ['name' => 'Home', 'url' => route('frontend.ott')],
                ['name' => $vj->display_name, 'url' => route('frontend.vj_detail', $vj->slug)],
                ['name' => $kindLabel, 'url' => $parentRoute],
                ['name' => $genre->name, 'url' => $pageUrl],
            ]),
        ],
    ])
@endpush

@section('content')
    <section class="section-padding">
        <div class="container-fluid px-3 px-md-4">
            <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mt-3 mb-4 pb-2 border-bottom border-dark">
                <div>
                    {{-- h1 for the crawler, h4 scale for the eye — the tag
                         carries the keyword; the size is design's call. --}}
                    <h1 class="main-title text-capitalize mb-1 h4 fw-medium">{{ $pageTitle }}</h1>
                    <p class="text-muted mb-0 small">
                        {{ $items->total() }} {{ Str::plural('title', $items->total()) }}
                    </p>
                </div>
                <a href="{{ $parentRoute }}" class="text-primary iq-view-all text-decoration-none flex-none">
                    All {{ $vj->display_name }} {{ $kindLabel }}
                </a>
            </div>

            <div id="archive-grid" class="row row-cols-3 row-cols-md-4 row-cols-lg-6 row-cols-xl-8 g-3">
                @foreach ($items as $item)
                    @include('frontend::components.partials.vj-grid-card', [
                        'item' => $item,
                        'contentKind' => $contentKind,
                    ])
                @endforeach
            </div>

            @include('frontend::components.partials.load-more-pagination', [
                'paginator'    => $items,
                'gridSelector' => '#archive-grid',
            ])

            {{-- The VJ's About card — same block as the hub and the parent
                 catalogue, so every VJ-scoped page carries the entity's
                 prose and social links. --}}
            @include('frontend::components.sections.vj-bio-card', ['vj' => $vj])
        </div>
    </section>

    @include('frontend::components.widgets.mobile-footer')
@endsection
