@extends('frontend::layouts.master', ['isBreadCrumb' => true, 'isFslightbox' => true,'bodyClass'=>'blog-single' ,'title' => __('streamBlog.blog14')])

@section('content')
    @include('frontend::components.cards.blog-details ', [
        'isImg' => true,
        'simon_doe'=>true,
        'previous'=>"frontend.detail_page",
        'next'=>"frontend.blog_audio",
        'blogImage' => asset('frontend/images/blog/blog1.webp'),
    ])
@endsection


