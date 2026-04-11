@extends('frontend::layouts.master', ['isSwiperSlider' => true, 'IS_MEGA' => true, 'isBreadCrumb' => true, 'title' => __('streamAccount.membership_order')])

@section('content')
    <section class="section-padding">
        <div class="pmpro container">
            @include('frontend::components.widgets.membership-invoce-card')
        </div>
    </section>

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}
@endsection
