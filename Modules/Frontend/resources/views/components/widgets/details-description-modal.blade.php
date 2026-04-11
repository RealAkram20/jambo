@if (!empty($isEpisodepage))
    <div class="modal fade view-more-data-modal" id="viewMoreDataModal" tabindex="-1" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header pb-0">
                    @if (!empty($istextureTitle))
                        <h3 class="text-uppercase m-0 texture-text texture-text-modal fw-bold">{{ $movieName ?? '' }}
                        </h3>
                    @else
                        <h5 class="css_prefix-title text-capitalize line-count-1">
                            <a href="javascript:void(0)" class="color-inherit">
                                {{ $movieName ?? '' }}
                            </a>
                        </h5>
                    @endif
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-1">
                    <ul class="list-inline d-flex align-items-center flex-wrap gap-3 mt-4">
                        <li>
                            <span class="fw-medium">{{ $year ?? '' }}</span>
                        </li>
                    @empty($isNotClockduration)
                        <li>
                            <span class="d-flex align-items-center gap-1">
                                <span class="d-flex align-items-center justify-content-center"><i
                                        class="ph ph-clock"></i></span>
                                {{ $movieDuration ?? '' }}
                            </span>
                        </li>
                    @endempty
                    <li>
                        <span class="d-flex align-items-center gap-1">
                            <i class="icon-eye-2"></i> {{ $views ?? '' }}
                        </span>
                    </li>
                @empty($isnotimdbRating)
                    <li>
                        <span class="d-flex align-items-center gap-1">
                            <span class="fw-medium">{{ $ratingCount ?? '' }}</span>
                            <span class="imdb-logo ms-1">
                                <img src="{{ asset('frontend/images/pages/imdb-logo.svg') }}" loading="lazy"
                                    decoding="async" alt="imdb logo" class="img-fluid imdb-logo1">
                            </span>
                        </span>
                    </li>
                @endempty
            </ul>

            @if (!empty($isCategory))
                <div class="d-flex align-items-baseline flex-wrap gap-2 mt-md-1 mt-2">
                    <h6 class="m-0">{{ __('widgets.Category') }}:</h6>
                    <ul class="p-0 mb-0 list-inline d-flex flex-wrap movie-tag">
                        <li class="trending-list"><a href="javascript:void(0)">{{ __('streamTag.adventure') }}</a></li>
                        <li class="trending-list"><a href="javascript:void(0)">{{ __('streamTag.animation') }}</a></li>
                        <li class="trending-list"><a href="javascript:void(0)">{{ __('streamTag.crime') }}</a></li>
                    </ul>
                </div>
            @endif

            @if (!empty($isTag))
                <div class="d-flex align-items-baseline flex-wrap gap-2 mt-3">
                    <h6 class="m-0">{{ __('frontendheader.tags') }}:</h6>
                    <ul
                        class="iq-blog-meta-cat-tag iq-blogtag mb-0 list-inline d-flex flex-wrap align-items-center gap-1 gap-md-3 mt-2 mt-md-3 tvshow-tags">
                        <li><a href="javascript:void(0)" class="position-relative">{{ __('streamTag.action') }}</a>
                        </li>
                        <li><a href="javascript:void(0)" class="position-relative">{{ __('streamTag.adventure') }}</a></li>
                        <li><a href="javascript:void(0)" class="position-relative">{{ __('streamTag.astronomy') }}</a></li>
                        <li><a href="javascript:void(0)" class="position-relative">{{ __('streamTag.hitman') }}</a></li>
                        <li><a href="javascript:void(0)" class="position-relative">{{ __('streamTag.crime') }}</a></li>
                    </ul>
                </div>
            @endif

            @if (!empty($isLanguadge))
                <div class="mt-4">
                    <div class="d-flex align-items-center gap-1">
                        <i class="icon-translate"></i>
                        <ul class="list-inline m-0 d-inline-flex align-items-center gap-2">
                            <li><small>English</small></li>
                        </ul>
                    </div>
                </div>
            @endif

            <p class="mt-4 mb-0">
                {{ $description ?? __('streamMovies.game_of_heros_desc') }}
            </p>
        </div>
    </div>
</div>
</div>
@else
<div class="modal fade view-more-data-modal trending-info" id="viewMoreDataModal" tabindex="-1" aria-modal="true"
role="dialog">
<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
        <div class="modal-header pb-0">
            <h3 class="text-uppercase m-0 texture-text texture-text-modal fw-bold">{{ $movieName ?? '' }}</h3>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body pt-1">
            <ul class="list-inline d-flex align-items-center flex-wrap gap-3 mt-4">
                <li>
                    <span class="fw-medium">{{ $year ?? '' }}</span>
                </li>
                <li>
                    <span class="d-flex align-items-center gap-1">
                        <i class="icon-eye-2"></i> {{ $views ?? '' }}
                    </span>
                </li>
                @if (!empty($moviedurettion))
                    <li>
                        <span class="d-flex align-items-center gap-1">
                            <i class="ph ph-clock"></i> {{ $time ?? '' }}
                        </span>
                    </li>
                @endif
                @if (empty($isNotimdbRating))
                    <li>
                        <span class="d-flex align-items-center gap-1">
                            <span class="fw-medium">{{ $ratingCount ?? '' }}</span>
                            <span class="imdb-logo ms-1">
                                <img src="{{ asset('frontend/images/pages/imdb-logo.svg') }}" loading="lazy"
                                    decoding="async" alt="imdb logo" class="img-fluid imdb-logo1">
                            </span>
                        </span>
                    </li>
                @endif
            </ul>

            <div class="d-flex align-items-baseline flex-wrap gap-2 mt-md-1 mt-2">
                <h6 class="m-0">{{ __('streamTag.genre') }}:</h6>
                <ul class="p-0 mb-0 list-inline d-flex flex-wrap movie-tag">
                    <li class="trending-list"><a href="javascript:void(0)">{{ __('streamTag.adventure') }}</a></li>
                    <li class="trending-list"><a href="javascript:void(0)">{{ __('streamTag.animation') }}</a></li>
                    <li class="trending-list"><a href="javascript:void(0)">{{ __('streamTag.crime') }}</a></li>
                    <li class="trending-list"><a href="javascript:void(0)">{{ __('streamTag.horror') }}</a></li>
                    <li class="trending-list"><a href="javascript:void(0)">{{ __('streamTag.romance') }}</a></li>
                </ul>
            </div>

            <div class="d-flex align-items-baseline flex-wrap gap-2 mt-3">
                <h6 class="m-0">{{ __('frontendheader.tags') }}:</h6>
                <ul
                    class="iq-blog-meta-cat-tag iq-blogtag mb-0 list-inline d-flex flex-wrap align-items-center gap-1 gap-md-3 mt-2 mt-md-3 tvshow-tags">
                    <li><a href="javascript:void(0)" class="position-relative">{{ __('streamTag.agents') }}</a></li>
                    <li><a href="javascript:void(0)" class="position-relative">{{ __('streamTag.brother') }}</a></li>
                    <li><a href="javascript:void(0)" class="position-relative">{{ __('streamTag.cricket') }}</a></li>
                    <li><a href="javascript:void(0)" class="position-relative">{{ __('streamTag.drama') }}</a></li>
                    <li><a href="javascript:void(0)" class="position-relative">{{ __('streamTag.hitman') }}</a></li>
                    <li><a href="javascript:void(0)" class="position-relative">{{ __('streamTag.kings') }}</a></li>
                </ul>
            </div>

            <div class="mt-4">
                <div class="d-flex align-items-center gap-1">
                    <i class="icon-translate"></i>
                    <ul class="list-inline m-0 d-inline-flex align-items-center gap-2">
                        <li><small>English (UK)</small></li>
                    </ul>
                </div>
            </div>

            <p class="mt-4 mb-0">
                {{ $description ?? __('streamMovies.game_of_heros_desc') }}
            </p>

            <div class="d-flex align-items-baseline row-gap-1 column-gap-2 mt-4">
                <h6 class="m-0">{{ __('form.cast') }}:</h6>
                <ul
                    class="list-inline m-0 p-0 d-flex align-items-center flex-wrap row-gap-1 column-gap-2 cast-crew-list">
                    <li><a href="javascript:void(0)" class="color-inherit">{{__('favouritePersonalities.olivia_foster')}}</a></li>
                    <li><a href="javascript:void(0)" class="color-inherit">{{__('favouritePersonalities.Leena_Burton')}}</a></li>
                    <li><a href="javascript:void(0)" class="color-inherit">{{__('favouritePersonalities.ryan_pierce')}}</a></li>
                    <li><a href="javascript:void(0)" class="color-inherit">{{__('favouritePersonalities.michael_fox')}}</a></li>
                    <li><a href="javascript:void(0)" class="color-inherit">{{__('favouritePersonalities.ruby_scott')}}</a></li>
                    <li><a href="javascript:void(0)" class="color-inherit">{{__('favouritePersonalities.maxwell_carter')}}</a></li>
                    <li><a href="javascript:void(0)" class="color-inherit">{{__('favouritePersonalities.mark_smith')}}</a></li>
                    <li><a href="javascript:void(0)" class="color-inherit">{{__('favouritePersonalities.ava_monroe')}}</a></li>
                    <li><a href="javascript:void(0)" class="color-inherit">{{__('favouritePersonalities.charles_melton')}}</a></li>
                    <li><a href="javascript:void(0)" class="color-inherit">{{__('favouritePersonalities.jack_nicholson')}}</a></li>
                    <li><a href="javascript:void(0)" class="color-inherit">{{__('favouritePersonalities.james_stewart')}}</a></li>
                    <li><a href="javascript:void(0)" class="color-inherit">{{__('favouritePersonalities.jeff_bridges')}}</a></li>
                    <li><a href="javascript:void(0)" class="color-inherit">{{__('favouritePersonalities.jordan_grant')}}</a></li>
                </ul>
            </div>

            <div class="d-flex align-items-baseline row-gap-1 column-gap-2 mt-4">
                <h6 class="m-0">{{ __('form.crew') }}:</h6>
                <ul
                    class="list-inline m-0 p-0 d-flex align-items-center flex-wrap row-gap-1 column-gap-2 cast-crew-list">
                    <li><a href="javascript:void(0)" class="color-inherit">{{__('favouritePersonalities.maria_rodriguez')}}</a></li>
                    <li><a href="javascript:void(0)" class="color-inherit">{{__('favouritePersonalities.oliver_turner')}}</a></li>
                    <li><a href="javascript:void(0)" class="color-inherit">{{__('favouritePersonalities.ethan_crawford')}}</a></li>
                    <li><a href="javascript:void(0)" class="color-inherit">{{__('favouritePersonalities.benjamin_hayes')}}</a></li>
                    <li><a href="javascript:void(0)" class="color-inherit">{{__('favouritePersonalities.jack_walker')}}</a></li>
                    <li><a href="javascript:void(0)" class="color-inherit">{{__('favouritePersonalities.mark_smith')}}</a></li>
                    <li><a href="javascript:void(0)" class="color-inherit">{{__('favouritePersonalities.millar_joseph')}}</a></li>
                    <li><a href="javascript:void(0)" class="color-inherit">{{__('favouritePersonalities.angel_louis')}}</a></li>
                    <li><a href="javascript:void(0)" class="color-inherit">{{__('favouritePersonalities.smith_jonas')}}</a></li>
                    <li><a href="javascript:void(0)" class="color-inherit">{{__('favouritePersonalities.kelly_reilly')}}</a></li>
                    <li><a href="javascript:void(0)" class="color-inherit">{{__('favouritePersonalities.thomas_bailey')}}</a></li>
                    <li><a href="javascript:void(0)" class="color-inherit">{{__('favouritePersonalities.davina_decorous')}}</a></li>
                    <li><a href="javascript:void(0)" class="color-inherit">{{__('favouritePersonalities.john_abraham')}}</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>
</div>
@endif
