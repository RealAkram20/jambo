@extends('frontend::layouts.master', ['isBreadCrumb' => true, 'isFslightbox' => true, 'bodyClass' => 'blog-single', 'title' => __('streamBlog.blog7')])

@section('content')
    @include('frontend::components.widgets.blog-detail', [
        'isVideo' => true,
        'blogTitle' => __('streamBlog.blog10'),
    ])

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}
@endsection
