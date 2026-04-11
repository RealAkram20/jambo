@extends('frontend::layouts.master', ['isBreadCrumb' => true, 'isFslightbox' => true, 'title' => __('frontendblogs.Day_September')])

@section('content')
    <div class="section-padding">
        <div class="container">
            <div class="row gy-5">
                <div class="col-lg-8 col-sm-12">
                    
                    <div class="blog-custom-box">
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

                    <div class="blog-custom-box">
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

                    <div class="blog-custom-box">
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

                    <div class="blog-custom-box">
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

                    <div class="blog-custom-box">
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
                    
                    <div class="blog-custom-box">
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
                    
                    <div class="blog-custom-box">
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
                    
                    <div class="blog-custom-box">
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
                    
                    <div class="blog-custom-box">
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
                    
                    <div class="blog-custom-box">
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
                    
                    <div class="blog-custom-box">
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
                    
                    <div class="blog-custom-box">
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
                    
                    <div class="blog-custom-box">
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
                    
                    <div class="blog-custom-box">
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
                <div class="col-lg-4 col-sm-12">
                    @include('frontend::components.cards.blog-sidebar', [])

                </div>
            </div>
        </div>
    </div>

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}
@endsection
