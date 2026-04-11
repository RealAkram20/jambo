@extends('frontend::layouts.master', ['isSwiperSlider' => true, 'IS_MEGA' => true, 'isBreadCrumb' => true, 'title' => __('profile.membership_comfirmation')])

@section('content')
<section class="section-padding">
    <div class="pmpro container">
        <section id="pmpro_confirmation-2" class="pmpro_section">
            <p>{{__('profile.thank_you_for_your_membership')}}</p>
            <p>{{__('profile.below_are_details_about_your_membership')}}</p>
        </section>
        @include('frontend::components.widgets.membership-invoce-card')
    </div>
</section>

{{-- Mobile Footer --}}
@include('frontend::components.widgets.mobile-footer')
{{-- Mobile Footer End --}}
@endsection
