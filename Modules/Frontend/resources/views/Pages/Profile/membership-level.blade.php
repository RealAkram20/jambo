@extends('frontend::layouts.master', ['isSwiperSlider' => true, 'IS_MEGA' => true, 'isBreadCrumb' => true, 'title' => __('profile.membership_levels')])

@section('content')
    <div class="section-padding">
        <div class="pmpro container">
            <section id="pmpro_levels" class="pmpro_section">
                <div class="pmpro_section_content">
                    <div id="pmpro_level_group-1" class="pmpro_card pmpro_level_group">
                        <div class="pmpro_card_content">
                            <table class="pmpro_table pmpro_levels_table">
                                <thead>
                                    <tr>
                                        <th>{{ __('streamTag.level') }}</th>
                                        <th>{{ __('streamMovies.Price') }}</th>
                                        <th><span class="screen-reader-text">{{ __('streamTag.action') }}</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr id="pmpro_level-2" class="pmpro_level">
                                        <th data-title="Level">{{ __('streamPricing.basic_plan') }}</th>
                                        <td data-title="Price">
                                            <p class="pmpro_level-price"><strong>$10.00</strong> {{ __('streamMovies.now') }}. </p>
                                        </td>
                                        <td>

                                            <a aria-label="Select the Basic Plan membership level"
                                                class="pmpro_btn pmpro_btn-select w-100"
                                                href="membership-comfirmation">{{ __('form.select') }}</a>
                                        </td>
                                    </tr>
                                    <tr id="pmpro_level-3" class="pmpro_level">
                                        <th data-title="Level">{{ __('streamTag.standard_plan') }}</th>
                                        <td data-title="Price">
                                            <p class="pmpro_level-price"><strong>$79.00</strong> {{ __('streamMovies.now_and_then') }} <strong>$89.00
                                                    {{ __('streamMovies.per_month') }}</strong>. </p>
                                        </td>
                                        <td>

                                            <a aria-label="Select the Standard Plan membership level"
                                                class="pmpro_btn pmpro_btn-select w-100"
                                                href="membership-comfirmation">{{ __('form.select') }}</a>
                                        </td>
                                    </tr>
                                    <tr id="pmpro_level-4" class="pmpro_level">
                                        <th data-title="Level">{{__('streamPricing.premium_plan')}}</th>
                                        <td data-title="Price">
                                            <p class="pmpro_level-price"><strong>$180.00 {{ __('streamMovies.every') }} 3 {{ __('streamMovies.Months') }}</strong>. </p>
                                        </td>
                                        <td>

                                            <a aria-label="Select the Premium Plan membership level"
                                                class="pmpro_btn pmpro_btn-select w-100"
                                                href="membership-comfirmation">{{ __('form.select') }}</a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div> <!-- end pmpro_card_content -->
                    </div> <!-- end pmpro_card -->
                </div> <!-- end pmpro_section_content -->
            </section> <!-- end pmpro_section -->
        </div>
    </div>

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}
@endsection
