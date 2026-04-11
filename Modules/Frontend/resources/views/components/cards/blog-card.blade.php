<div class="iq-blog-box">
    <div class="iq-blog-image clearfix">
        <a href="{{ route('frontend.detail_page') }}">
            <img src="{{ asset('frontend/images/blog/' . $blogImg) }}" alt="blogImg-{{ $id }}"
                class="img-fluid w-100 rounded-3" loading="lazy">
        </a>
    </div>
    <div class="iq-blog-detail">
        <div class="iq-blog-meta d-flex align-items-center flex-wrap">
            <ul class="list-inline mb-0">
                <li class="posted-by">
                    <span class="post-date d-flex align-items-center gap-1">
                        @if (isset($blogTagIcon) && $blogTagIcon)
                            <i class="icon icon-tag"></i>
                        @else
                            <i class="ph ph-user"></i>
                        @endif
                        <a href="{{ route('frontend.blog_author') }}">{{ $blogTag }}</a>
                    </span>
                </li>
                <li class="posted-on">
                    <span class="post-author d-flex align-items-center gap-1">
                        <i class="icon icon-calendar-2"></i>
                        <a href="{{ route('frontend.blog_date') }}">
                            <span>{{ $blogDate }}</span>
                        </a>
                    </span>
                </li>
                @if (isset($blogcategorytext))
                    <li class="blog-category">
                        <a href="{{ route('frontend.blog_category') }}" class="pe-1">{{ $blogCategorytext1 }}</a>
                    </li>
                    <li class="blog-category">
                        <a href="{{ route('frontend.blog_category') }}" class="pe-1">{{ $blogCategorytext2 }}</a>
                    </li>
                    <li class="blog-category">
                        <a href="{{ route('frontend.blog_category') }}" class="pe-1">{{ $blogCategorytext3 }}</a>
                    </li>
                    <li class="blog-category">
                        <a href="{{ route('frontend.blog_category') }}" class="pe-1">{{ $blogCategorytext4 }}</a>
                    </li>
                @else
                @endif
            </ul>
        </div>

        <h4 class="mb-3 blog-heading">
            <a href="{{ route('frontend.detail_page') }}">
                {{ $blogTitle }}
            </a>
        </h4>
        <p class="line-count-2">{{ $blogText }}</p>

        @if (isset($blogtag) && $blogtag)
            <ul class="list-inline d-flex align-items-center gap-2 flex-wrap blog-tag">
                @if (isset($blogtag1))
                    <li><a class="font-size-14 rounded-3 fw-medium" href="{{ route('frontend.blog_tag') }}">{{ $blogtag1 }}</a></li>
                @endif
                @if (isset($blogtag2))
                    <li><a class="font-size-14 rounded-3 fw-medium" href="{{ route('frontend.blog_tag') }}">{{ $blogtag2 }}</a></li>
                @endif
                @if (isset($blogtag3))
                    <li><a class="font-size-14 rounded-3 fw-medium" href="{{ route('frontend.blog_tag') }}">{{ $blogtag3 }}</a></li>
                @endif
            </ul>
        @endif

        @if (isset($linkButton) && $linkButton)
            <div>
                <a href="{{ route('frontend.detail_page') }}"
                    class="btn btn-link blog-readMore p-0 text-capitalize position-relative font-size-14">
                    <span>{{__('streamButtons.read_more')}}</span>
                    @if (isset($rightIcon) && $rightIcon)
                        <span><i class="ph ph-caret-right align-middle"></i></span>
                    @endif
                </a>
            </div>
        @else
            <div class="iq-button">
                <a href="{{ route('frontend.detail_page') }}" class="btn btn-primary text-uppercase position-relative">
                    <span class="button-text">{{__('streamButtons.read_more')}} </span>
                    <i class="ph-fill ph-play"></i>
                </a>
            </div>
        @endif

        @if (isset($isCategory) && $isCategory)
            <ul class="iq-blog-meta-cat-tag iq-blogcat mt-4">
                <li class="iq-tag-title">
                    <i class="ph ph-film-strip" aria-hidden="true"></i>
                    {{ __('frontendform.categories') }}:
                </li>
                <li>
                    <a href="{{ route('frontend.blog_category') }}">
                        {{ $categoriesText1 }}
                    </a>
                </li>
            </ul>
        @endif

        @if (isset($isTag) && $isTag)
            <ul class="iq-blog-meta-cat-tag iq-blogtag">
                <li class="iq-tag-title">
                    <i class="ph ph-tag" aria-hidden="true"></i>
                    {{ __('frontendform.tags') }}:
                </li>
                @if (isset($tagText1))
                    <li><a href="{{ route('frontend.blog_tag') }}">{{ $tagText1 }}</a></li>
                @endif
                @if (isset($tagText2))
                    <li><a href="{{ route('frontend.blog_tag') }}">{{ $tagText2 }}</a></li>
                @endif
                @if (isset($tagText3))
                    <li><a href="{{ route('frontend.blog_tag') }}">{{ $tagText3 }}</a></li>
                @endif
            </ul>
        @endif

    </div>
</div>
