@extends('frontend::layouts.master', ['isBreadCrumb' => true, 'title' => __('frontendheader.faq')])

@section('content')
    <div class="section-padding">
        <div class="container">
            <div class="iq-accordian iq-accordian-square">
                <div class="iq-accordian-block iq-active 1">
                    <div class="iq-accordian-title text-capitalize d-flex justify-content-between align-items-center d-flex justify-content-between align-items-center">
                        <div class="iq-icon-right">
                            <i aria-hidden="true" class="ph ph-minus active"></i>
                            <i aria-hidden="true" class="ph ph-plus inactive"></i>
                        </div>
                        <span class="mb-0 accordian-title">{{__('streamTag.what_is_streamit')}}</span>
                    </div>
                    <div class="iq-accordian-details" style="display: block;">
                        <p class="mb-0"> {{__('streamTag.desc')}}</p>
                    </div>
                </div>
                <div class="iq-accordian-block 2">
                    <div class="iq-accordian-title text-capitalize d-flex justify-content-between align-items-center">
                        <div class="iq-icon-right">
                            <i aria-hidden="true" class="ph ph-minus active"></i>
                            <i aria-hidden="true" class="ph ph-plus inactive"></i>
                        </div>
                        <span class="mb-0 accordian-title">{{__('streamTag.will_account')}}</span>
                    </div>
                    <div class="iq-accordian-details" style="display: none;">
                        <p class="mb-0"> {{__('streamTag.desc')}}</p>
                    </div>
                </div>
                <div class="iq-accordian-block 3">
                    <div class="iq-accordian-title text-capitalize d-flex justify-content-between align-items-center">
                        <div class="iq-icon-right">
                            <i aria-hidden="true" class="ph ph-minus active"></i>
                            <i aria-hidden="true" class="ph ph-plus inactive"></i>
                        </div>
                        <span class="mb-0 accordian-title text-capitalize">{{__('streamTag.video_issue')}}
                        </span>
                    </div>
                    <div class="iq-accordian-details" style="display: none;">
                        <p class="mb-0"> {{__('streamTag.desc')}}</p>
                    </div>
                </div>
                <div class="iq-accordian-block 4">
                    <div class="iq-accordian-title text-capitalize d-flex justify-content-between align-items-center">
                        <div class="iq-icon-right">
                            <i aria-hidden="true" class="ph ph-minus active"></i>
                            <i aria-hidden="true" class="ph ph-plus inactive"></i>
                        </div>
                        <span class="mb-0 accordian-title text-capitalize">{{__('streamTag.manage_notification')}}</span>
                    </div>
                    <div class="iq-accordian-details" style="display: none;">
                        <p class="mb-0"> {{__('streamTag.desc')}}</p>
                    </div>
                </div>
                <div class="iq-accordian-block 5">
                    <div class="iq-accordian-title text-capitalize d-flex justify-content-between align-items-center">
                        <div class="iq-icon-right">
                            <i aria-hidden="true" class="ph ph-minus active"></i>
                            <i aria-hidden="true" class="ph ph-plus inactive"></i>
                        </div>
                        <span class="mb-0 accordian-title text-capitalize"> {{__('streamTag.what_benefits')}}</span>
                    </div>
                    <div class="iq-accordian-details" style="display: none;">
                        <p class="mb-0"> {{__('streamTag.desc')}}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}
@endsection
