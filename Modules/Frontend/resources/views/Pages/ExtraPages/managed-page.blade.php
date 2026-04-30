@extends('frontend::layouts.master', ['isBreadCrumb' => true, 'title' => $page->title])

{{-- Social-preview metadata. Falls back through the SEO partial's
     defaults if the page has no featured image or no meta description. --}}
@if ($page->featured_image_url)
    @section('seo:image', $page->featured_image_url)
@endif
@if ($page->meta_description)
    @section('seo:description', $page->meta_description)
@endif

@section('content')
    {{-- Scope Quill heading output to the system's smaller heading
         sizes so the rich-text body matches the legacy templates'
         look (h4/fw-500 section headings, etc.). Sizes come from
         the Bootstrap CSS vars (--bs-heading-*) so mobile cascading
         keeps working. --}}
    <style>
        .managed-page-title { font-size: var(--bs-heading-3, 2.369rem); font-weight: 500; }
        .managed-page-body h1 { font-size: var(--bs-heading-3, 2.369rem); font-weight: 500; margin-top: 1.5rem; }
        .managed-page-body h2 { font-size: var(--bs-heading-4, 1.777rem); font-weight: 500; margin-top: 1.5rem; }
        .managed-page-body h3 { font-size: var(--bs-heading-5, 1.333rem); font-weight: 500; margin-top: 1.25rem; }
        .managed-page-body h4 { font-size: var(--bs-heading-5, 1.333rem); font-weight: 500; margin-top: 1.25rem; }
        .managed-page-body p { margin-bottom: 1rem; }
        .managed-page-body ul, .managed-page-body ol { margin-bottom: 1rem; padding-left: 1.5rem; }
    </style>

    <div class="managed-page section-padding">
        <div class="container">
            @if ($page->featured_image_url)
                <div class="mb-4 text-center">
                    <img src="{{ $page->featured_image_url }}" alt="{{ $page->title }}" class="img-fluid rounded">
                </div>
            @endif

            <div class="title-box mb-4 text-center">
                <h3 class="managed-page-title mb-0">{{ $page->title }}</h3>
            </div>

            {{-- Quill stores sanitised HTML; rendered as-is. --}}
            <div class="managed-page-body">
                {!! $page->content !!}
            </div>
        </div>
    </div>

    @include('frontend::components.widgets.mobile-footer')
@endsection
