<div class="section-padding blog-section">
    <div class="container">
        <div class="row">
            <div class="col-12">
                @if (!isset($isblogdetail) || !$isblogdetail)
                    <div class="iq-blogs-meta">
                        <ul class="iq-blogtag list-inline d-flex align-items-center flex-wrap mb-0">
                            <li><i class="ph ph-user" aria-hidden="true"></i>
                                <a href="{{ route('frontend.blog_author') }}">{{__('streamAccount.jenny')}}</a>
                            </li>
                            <li>
                                <i class="icon icon-calendar-2" aria-hidden="true"></i>
                                <a href="{{ route('frontend.blog_date') }}">January 30, 2019</a>
                            </li>
                            <li><a href="{{ route('frontend.blog_tag') }}">{{__('streamTag.drama')}}S</a>,</li>
                            <li><a href="{{ route('frontend.blog_tag') }}">{{__('frontendblogs.historical')}}</a>,</li>
                            <li><a href="{{ route('frontend.blog_tag') }}">{{__('frontendblogs.Uncategorized')}}</a></li>
                        </ul>
                    </div>
                    <h3 class="mb-3">{{ $blogTitle ?? '' }}</h3>
                @endif

                <div class="iq-blog blog-detail">
                    @if (isset($isImg) && $isImg)
                        <a href="{{ route('frontend.detail_page') }}" class="blog-image d-block overflow-hidden">
                            <img src="{{ asset('frontend/images/' . ($blogImage ?? 'default.jpg')) }}" alt="blog-image"
                                class="img-fluid w-100 mb-5">
                        </a>
                    @endif

                    @if (isset($isVideo) && $isVideo)
                        <div>
                            <iframe class="w-100" height="490" src="https://www.youtube.com/embed/QCGq1epI9pQ"
                                title="Jambo Intro"
                                style="border: none;"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                allowfullscreen></iframe>
                        </div>
                    @endif

                    @if (isset($isAudio) && $isAudio)
                        <div>
                            <iframe height="200" class="w-100" style="border: none;"
                                src="https://w.soundcloud.com/player/?visual=false&amp;url=https%3A%2F%2Fapi.soundcloud.com%2Ftracks%2F36701924&amp;show_artwork=true&amp;maxheight=750&amp;maxwidth=720&amp;auto_play=false&amp;buying=true&amp;liking=true&amp;download=true&amp;sharing=true&amp;show_comments=true&amp;show_playcount=true&amp;show_user=true&amp;color"></iframe>
                        </div>
                    @endif

                    @if (isset($isGallery) && $isGallery)
                        <div class="row row-cols-xl-3 row-cols-md-2 row-cols-1">
                            <div class="col mb-4">
                                <a data-fslightbox="gallery"
                                    href=" url('{{ asset('frontend/images/blog/blog1.webp') }}'); ">
                                    <img class="img-fluid" src="{{ asset('frontend/images/blog/blog1.webp') }}"
                                        alt="blog-gallery" loading="lazy">
                                </a>
                            </div>
                            <div class="col mb-4">
                                <a data-fslightbox="gallery"
                                    href=" url('{{ asset('frontend/images/blog/blog2.webp') }}'); ">
                                    <img class="img-fluid" src="{{ asset('frontend/images/blog/blog2.webp') }}"
                                        alt="blog-gallery" loading="lazy">
                                </a>
                            </div>
                            <div class="col mb-4">
                                <a data-fslightbox="gallery"
                                    href=" url('{{ asset('frontend/images/blog/blog3.webp') }}'); ">
                                    <img class="img-fluid" src="{{ asset('frontend/images/blog/blog3.webp') }}"
                                        alt="blog-gallery" loading="lazy">
                                </a>
                            </div>
                            <div class="col mb-4 mb-md-0">
                                <a data-fslightbox="gallery"
                                    href=" url('{{ asset('frontend/images/blog/blog4.webp') }}'); ">
                                    <img class="img-fluid" src="{{ asset('frontend/images/blog/blog4.webp') }}"
                                        alt="blog-gallery" loading="lazy">
                                </a>
                            </div>
                            <div class="col mb-4 mb-md-0">
                                <a data-fslightbox="gallery"
                                    href=" url('{{ asset('frontend/images/blog/blog5.webp') }}'); ">
                                    <img class="img-fluid" src="{{ asset('frontend/images/blog/blog5.webp') }}"
                                        alt="blog-gallery" loading="lazy">
                                </a>
                            </div>
                            <div class="col">
                                <a data-fslightbox="gallery"
                                    href=" url('{{ asset('frontend/images/blog/blog6.webp') }}'); ">
                                    <img class="img-fluid" src="{{ asset('frontend/images/blog/blog6.webp') }}"
                                        alt="blog-gallery" loading="lazy">
                                </a>
                            </div>
                        </div>
                    @endif

                    <div class="iq-blog-box">
                        @if (isset($isLink) && $isLink)
                            <div class="mb-4">
                                <a href="#"><i class="ph ph-link text-primary quote-icon"></i></a>
                            </div>
                        @endif

                        @if (isset($isQuote) && $isQuote)
                            <blockquote class="block-quote py-5 mb-3">
                                <div class="row align-items-start">
                                    <div class="col-lg-1 col-md-2">
                                        <div class="blockquote-icon">
                                            <i class="ph-fill ph-quotes text-primary" aria-hidden="true"></i>
                                        </div>
                                    </div>
                                    <div class="col-lg-11 col-md-10">
                                        <div class="p-2">
                                            <p class="mb-0 blog-gallery-desc">{{__('streamBlog.quotes')}}</p>
                                            <cite><a href="javascript:void(0)">{{__('streamBlog.quotes_author')}}</a></cite>
                                        </div>
                                    </div>
                                </div>
                            </blockquote>
                        @endif

                        @if (isset($isblogdetail) && $isblogdetail)
                            <div class="iq-blogs-meta">
                                <ul class="iq-blogtag list-inline d-flex align-items-center flex-wrap mb-0">
                                    <li class="d-flex align-items-center gap-1"><i class="ph ph-user"
                                            aria-hidden="true"></i>
                                        <a href="{{ route('frontend.blog_author') }}">{{__('streamAccount.jenny')}}</a>
                                    </li>
                                    <li class="d-flex align-items-center gap-1">
                                        <i class="icon icon-calendar-2" aria-hidden="true"></i>
                                        <a href="{{ route('frontend.blog_date') }}">January 30, 2019</a>
                                    </li>
                                    <li><a href="{{ route('frontend.blog_tag') }}">{{__('streamTag.drama')}}</a>,</li>
                                    <li><a href="{{ route('frontend.blog_tag') }}">{{__('streamBlog.history')}}</a>,</li>
                                    <li><a href="{{ route('frontend.blog_tag') }}">{{__('streamBlog.uncategorized')}}</a></li>
                                </ul>
                            </div>
                            <h3 class="mb-3">{{ $blogTitle ?? '' }}</h3>
                        @endif

                        <h6 class="text-white my-3">{{__('streamBlog.blog2_desc')}}</h6>
                        <p class="mb-0 blog-gallery-desc">{{__('streamBlog.blog3_desc')}}</p>
                        <p class="blog-gallery-desc">{{__('streamBlog.blog4_desc')}}</p>

                        <blockquote class="block-quote text-white py-2">
                            <p class="mb-0 blog-gallery-desc">{{__('streamBlog.quotes')}}</p>
                            <cite><a href="javascript:void(0)">{{__('streamBlog.quotes_author')}}</a></cite>
                        </blockquote>
                        <p class="mb-0 blog-gallery-desc">{{__('streamBlog.blog7_desc')}}</p>
                        <p class="mb-0 blog-gallery-desc">{{__('streamBlog.blog8_desc')}}</p>
                        <p class="blog-gallery-desc">{{__('streamBlog.blog7_desc')}}</p>

                        <ul class="iq-blogtag p-0 m-0 list-unstyled gap-2 widget_tags widget-tags-product">
                            <li><a href="{{ route('frontend.blog_tag') }}" class="position-relative">{{__('streamTag.comedy')}}</a></li>
                            <li><a href="{{ route('frontend.blog_tag') }}" class="position-relative">{{__('streamTag.drama')}}</a></li>
                            <li><a href="{{ route('frontend.blog_tag') }}" class="position-relative">{{__('streamBlog.movy')}}</a></li>
                            <li><a href="{{ route('frontend.blog_tag') }}" class="position-relative">{{__('streamBlog.trailer')}}</a>
                            </li>
                        </ul>

                        <div class="iq-blog-tag">
                            <div class="blog-nav row mt-3 mt-md-0 mb-5 mb-lg-0">
                                <div class="blog-prev-post col-6 ">
                                    <a href="{{ route('frontend.detail_page') }}">
                                        <span class="fw-semibold d-none d-md-block">{{__('streamTag.amy_admas')}} </span>
                                        <div class="blog-arrow mt-3 d-flex align-items-center">
                                            <i class="ph ph-arrow-left"></i>
                                            <span class="previous fw-semibold fst-italic"> {{__('streamTag.previous_post')}}</span>
                                        </div>
                                    </a>
                                </div>
                                <div class="blog-next-post col-6 text-end">
                                    <a href="{{ route('frontend.blog_audio') }}">
                                        <span class="fw-semibold d-none d-md-block">{{__('streamTag.gillian_shares')}}</span>
                                        <div class="blog-arrow mt-3 d-flex align-items-center justify-content-end">
                                            <span class="next fw-semibold fst-italic"> {{__('streamTag.next_post')}}</span>
                                            <i class="ph ph-arrow-right"></i>
                                        </div>
                                    </a>
                                </div>
                            </div>
                            <h3 class="comments-title mb-4 pb-2">
                                <span>0 {{__('streamBlog.comment')}}</span>
                            </h3>
                            <h3 class="mb-4">{{__('streamTag.leave_reply')}} </h3>
                            <form>
                                <p class="mb-5">{{__('streamAccount.logged_jenny')}} <a
                                        href="#">{{__('streamTag.edit_profile')}}</a><a
                                       href="javascript:void(0)"> {{__('streamTag.log_out')}}?</a> *</p>
                                <div class="pt-3">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <textarea class="form-control rounded-3" name="comment" cols="5" rows="8" required
                                                placeholder="{{__('streamBlog.comment')}}"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-submit mt-5">
                                            <button name="submit" type="submit" id="submit"
                                                class="btn btn-primary position-relative rounded-3" value="Submit">
                                                <span class="button-text">{{__('streamTag.post_comment')}}</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>