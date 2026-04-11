@extends('frontend::layouts.master', [
'isBreadCrumb' => true,
'isFslightbox' => true,
'title' => 'Blog Infinite Scroll',
])

@section('content')
<div class="blog-pagination section-padding">
    <div class="container">
        <div id="content">
            <div class="row gy-5 row-cols-1 row-cols-md-2 row-cols-lg-3 blog-post-container gy-5">
                <div class="col">
                    @include('frontend::components.cards.blog-card', [
                    'blogImg' => 'rebirth-of-the-realm.webp',
                    'id' => '01',
                    'blogDate' => 'January 10, 2025',
                    'blogTag' => __('streamAccount.jenny'),
                    'blogTitle' => __('streamBlog.blog1'),
                    'blogText' => __('streamBlog.blog1_desc'),
                    'linkButton' => true,
                    'rightIcon' => true,
                    ])
                </div>

                <div class="col">
                    @include('frontend::components.cards.blog-card', [
                    'blogImg' => 'Special-Effects.webp',
                    'id' => '02',
                    'blogDate' => 'January 10, 2025',
                    'blogTag' => __('streamAccount.jenny'),
                    'blogTitle' => __('streamBlog.blog2'),
                    'blogText' => __('streamBlog.blog2_desc'),
                    'linkButton' => true,
                    'rightIcon' => true,
                    ])
                </div>

                <div class="col">
                    @include('frontend::components.cards.blog-card', [
                    'blogImg' => 'to-be-continue.webp',
                    'id' => '03',
                    'blogDate' => 'January 10, 2025',
                    'blogTag' => __('streamAccount.jenny'),
                    'blogTitle' => __('streamBlog.blog3'),
                    'blogText' => __('streamBlog.blog3_desc'),
                    'linkButton' => true,
                    'rightIcon' => true,
                    ])
                </div>

                <div class="col">
                    @include('frontend::components.cards.blog-card', [
                    'blogImg' => 'duos-standing.webp',
                    'id' => '04',
                    'blogDate' => 'January 10, 2025',
                    'blogTag' => __('streamAccount.jenny'),
                    'blogTitle' => __('streamBlog.blog4'),
                    'blogText' => __('streamBlog.blog4_desc'),
                    'linkButton' => true,
                    'rightIcon' => true,
                    ])
                </div>

                <div class="col">
                    @include('frontend::components.cards.blog-card', [
                    'blogImg' => 'short-movie.webp',
                    'id' => '05',
                    'blogDate' => 'January 10, 2025',
                    'blogTag' => __('streamAccount.jenny'),
                    'blogTitle' => __('streamBlog.blog5'),
                    'blogText' => __('streamBlog.blog5_desc'),
                    'linkButton' => true,
                    'rightIcon' => true,
                    ])
                </div>

                <div class="col">
                    @include('frontend::components.cards.blog-card', [
                    'blogImg' => 'beloved.webp',
                    'id' => '06',
                    'blogDate' => 'January 10, 2025',
                    'blogTag' => __('streamAccount.jenny'),
                    'blogTitle' => __('streamBlog.blog6'),
                    'blogText' => __('streamBlog.blog6_desc'),
                    'linkButton' => true,
                    'rightIcon' => true,
                    ])
                </div>

                <div class="col">
                    @include('frontend::components.cards.blog-card', [
                    'blogImg' => 'blog9.webp',
                    'id' => '07',
                    'blogDate' => 'January 10, 2025',
                    'blogTag' => __('streamAccount.jenny'),
                    'blogTitle' => __('streamBlog.blog7'),
                    'blogText' => __('streamBlog.blog7_desc'),
                    'linkButton' => true,
                    'rightIcon' => true,
                    ])
                </div>

                <div class="col">
                    @include('frontend::components.cards.blog-card', [
                    'blogImg' => 'blog8.webp',
                    'id' => '08',
                    'blogDate' => 'January 10, 2025',
                    'blogTag' => __('streamAccount.jenny'),
                    'blogTitle' => __('streamBlog.blog8'),
                    'blogText' => __('streamBlog.blog8_desc'),
                    'linkButton' => true,
                    'rightIcon' => true,
                    ])
                </div>

                <div class="col">
                    @include('frontend::components.cards.blog-card', [
                    'blogImg' => 'Independent.webp',
                    'id' => '09',
                    'blogDate' => 'January 10, 2025',
                    'blogTag' => __('streamAccount.jenny'),
                    'blogTitle' => __('streamBlog.blog9'),
                    'blogText' => __('streamBlog.blog9_desc'),
                    'linkButton' => true,
                    'rightIcon' => true,
                    ])
                </div>

                <div class="col">
                    @include('frontend::components.cards.blog-card', [
                    'blogImg' => 'blog5.webp',
                    'id' => '10',
                    'blogDate' => 'January 10, 2025',
                    'blogTag' => __('streamAccount.jenny'),
                    'blogTitle' => __('streamBlog.blog10'),
                    'blogText' => __('streamBlog.blog10_desc'),
                    'linkButton' => true,
                    'rightIcon' => true,
                    ])
                </div>

                <div class="col">
                    @include('frontend::components.cards.blog-card', [
                    'blogImg' => 'blog4.webp',
                    'id' => '11',
                    'blogDate' => 'January 10, 2025',
                    'blogTag' => __('streamAccount.jenny'),
                    'blogTitle' => __('streamBlog.blog11'),
                    'blogText' => __('streamBlog.blog11_desc'),
                    'linkButton' => true,
                    'rightIcon' => true,
                    ])
                </div>

                <div class="col">
                    @include('frontend::components.cards.blog-card', [
                    'blogImg' => 'comedy-movie.webp',
                    'id' => '12',
                    'blogDate' => 'January 10, 2025',
                    'blogTag' => __('streamAccount.jenny'),
                    'blogTitle' => __('streamBlog.blog12'),
                    'blogText' => __('streamBlog.blog12_desc'),
                    'linkButton' => true,
                    'rightIcon' => true,
                    ])
                </div>

                <div class="col">
                    @include('frontend::components.cards.blog-card', [
                    'blogImg' => 'kisds-movie.webp',
                    'id' => '13',
                    'blogDate' => 'January 10, 2025',
                    'blogTag' => __('streamAccount.jenny'),
                    'blogTitle' => __('streamBlog.blog13'),
                    'blogText' => __('streamBlog.blog13_desc'),
                    'linkButton' => true,
                    'rightIcon' => true,
                    ])
                </div>

                <div class="col">
                    @include('frontend::components.cards.blog-card', [
                    'blogImg' => 'blog1.webp',
                    'id' => '14',
                    'blogDate' => 'January 10, 2025',
                    'blogTag' => __('streamAccount.jenny'),
                    'blogTitle' => __('streamBlog.blog14'),
                    'blogText' => __('streamBlog.blog14_desc'),
                    'linkButton' => true,
                    'rightIcon' => true,
                    ])
                </div>
            </div>
            <!-- Static Pagination Controls -->

            <div id="load-copy-content-1" class="d-none">
                <div class="col">
                    @include('frontend::components.cards.blog-card', [
                    'blogImg' => 'blog9.webp',
                    'id' => '07',
                    'blogDate' => 'January 10, 2025',
                    'blogTag' => __('streamAccount.jenny'),
                    'blogTitle' => __('streamBlog.blog7'),
                    'blogText' => __('streamBlog.blog7_desc'),
                    'linkButton' => true,
                    'rightIcon' => true,
                    ])
                </div>

                <div class="col">
                    @include('frontend::components.cards.blog-card', [
                    'blogImg' => 'blog8.webp',
                    'id' => '08',
                    'blogDate' => 'January 10, 2025',
                    'blogTag' => __('streamAccount.jenny'),
                    'blogTitle' => __('streamBlog.blog8'),
                    'blogText' => __('streamBlog.blog8_desc'),
                    'linkButton' => true,
                    'rightIcon' => true,
                    ])
                </div>

                <div class="col">
                    @include('frontend::components.cards.blog-card', [
                    'blogImg' => 'Independent.webp',
                    'id' => '09',
                    'blogDate' => 'January 10, 2025',
                    'blogTag' => __('streamAccount.jenny'),
                    'blogTitle' => __('streamBlog.blog9'),
                    'blogText' => __('streamBlog.blog9_desc'),
                    'linkButton' => true,
                    'rightIcon' => true,
                    ])
                </div>

                <div class="col">
                    @include('frontend::components.cards.blog-card', [
                    'blogImg' => 'blog5.webp',
                    'id' => '10',
                    'blogDate' => 'January 10, 2025',
                    'blogTag' => __('streamAccount.jenny'),
                    'blogTitle' => __('streamBlog.blog10'),
                    'blogText' => __('streamBlog.blog10_desc'),
                    'linkButton' => true,
                    'rightIcon' => true,
                    ])
                </div>

                <div class="col">
                    @include('frontend::components.cards.blog-card', [
                    'blogImg' => 'blog4.webp',
                    'id' => '11',
                    'blogDate' => 'January 10, 2025',
                    'blogTag' => __('streamAccount.jenny'),
                    'blogTitle' => __('streamBlog.blog11'),
                    'blogText' => __('streamBlog.blog11_desc'),
                    'linkButton' => true,
                    'rightIcon' => true,
                    ])
                </div>

                <div class="col">
                    @include('frontend::components.cards.blog-card', [
                    'blogImg' => 'comedy-movie.webp',
                    'id' => '12',
                    'blogDate' => 'January 10, 2025',
                    'blogTag' => __('streamAccount.jenny'),
                    'blogTitle' => __('streamBlog.blog12'),
                    'blogText' => __('streamBlog.blog12_desc'),
                    'linkButton' => true,
                    'rightIcon' => true,
                    ])
                </div>

                <div class="col">
                    @include('frontend::components.cards.blog-card', [
                    'blogImg' => 'kisds-movie.webp',
                    'id' => '13',
                    'blogDate' => 'January 10, 2025',
                    'blogTag' => __('streamAccount.jenny'),
                    'blogTitle' => __('streamBlog.blog13'),
                    'blogText' => __('streamBlog.blog13_desc'),
                    'linkButton' => true,
                    'rightIcon' => true,
                    ])
                </div>

                <div class="col">
                    @include('frontend::components.cards.blog-card', [
                    'blogImg' => 'blog1.webp',
                    'id' => '14',
                    'blogDate' => 'January 10, 2025',
                    'blogTag' => __('streamAccount.jenny'),
                    'blogTitle' => __('streamBlog.blog14'),
                    'blogText' => __('streamBlog.blog14_desc'),
                    'linkButton' => true,
                    'rightIcon' => true,
                    ])
                </div>
            </div>
        </div>
        <div class="row">
            <div class="scroller-status col-sm-12 text-center">
                <div class="infinite-scroll-request loader-ellips">
                    <div class="loader-wheel-container"></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Mobile Footer --}}
@include('frontend::components.widgets.mobile-footer')
{{-- Mobile Footer End --}}
@endsection