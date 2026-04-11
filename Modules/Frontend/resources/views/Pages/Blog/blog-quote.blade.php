@extends('frontend::layouts.master', ['isBreadCrumb' => true, 'isFslightbox' => true, 'bodyClass' => 'blog-single', 'title' => __('streamBlog.blog9')])

@section('content')
    @include('frontend::components.widgets.blog-detail', [
        'isQuote' => true,
        'blogTitle' => __('streamBlog.blog9'),
    ])

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}
@endsection
