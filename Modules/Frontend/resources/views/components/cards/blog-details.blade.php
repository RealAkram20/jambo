@extends('frontend::layouts.master', [
    'isBreadCrumb' => true,
    'isFslightbox' => true,
    'title' => __('steamBlog.blog14'),
])

@section('content')
    @include('frontend::components.widgets.blog-detail', [
        'isblogdetail' => true,
        'isImg' => true,
        'blogImage' => 'blog/blog1.webp',
        'blogTitle' => __('streamBlog.blog1'),
    ])

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}
@endsection
