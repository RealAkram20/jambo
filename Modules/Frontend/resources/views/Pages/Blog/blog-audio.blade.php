@extends('frontend::layouts.master', ['isBreadCrumb' => true, 'isFslightbox' => true, 'bodyClass' => 'blog-single', 'title' => __('streamBlog.blog8')])

@section('content')
    @include('frontend::components.widgets.blog-detail ', [
        'isAudio' => true,
        'blogTitle' => __('streamBlog.blog15'),
    ])

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}
@endsection
