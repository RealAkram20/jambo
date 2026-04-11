@extends('frontend::layouts.master', ['isSwiperSlider' => true, 'IS_MEGA' => true, 'isBreadCrumb' => true, 'title' => __('header.your_profile')])

@section('content')
    <div class="section-padding">
        <div class="pmpro container">
            <section id="pmpro_member_profile_edit" class="pmpro_section">
                <div class="pmpro_section_content">
                    <form id="member-profile-edit" class="pmpro_form" action="" method="post">
                        <div class="pmpro_card">
                            <div class="pmpro_card_content">
                                <input type="hidden" id="update_user_nonce" name="update_user_nonce" value="52ffbc7b9c">
                                <input type="hidden" name="_wp_http_referer"
                                    value="/product/wp/streamit/membership-account/your-profile/">
                                <fieldset id="pmpro_member_profile_edit-account-information" class="pmpro_form_fieldset">
                                    <legend class="pmpro_form_legend">
                                        <h2 class="pmpro_form_heading pmpro_font-large">
                                            {{ __('streamTag.account_information') }}</h2>
                                    </legend>
                                    <div class="pmpro_form_fields pmpro_cols-2">
                                        <div class="pmpro_form_field pmpro_form_field-first_name">
                                            <label for="first_name"
                                                class="pmpro_form_label">{{ __('streamAccount.first_name') }}</label>
                                            <input type="text" name="first_name" id="first_name" value="Marvin"
                                                class="pmpro_form_input pmpro_form_input-text" autocomplete="given-name">
                                        </div>
                                        <div class="pmpro_form_field pmpro_form_field-last_name">
                                            <label for="last_name"
                                                class="pmpro_form_label">{{ __('streamAccount.last_name') }}</label>
                                            <input type="text" name="last_name" id="last_name" value="McKinney"
                                                class="pmpro_form_input pmpro_form_input-text" autocomplete="family-name">
                                        </div>
                                        <div class="pmpro_form_field pmpro_form_field-display_name">
                                            <label for="display_name"
                                                class="pmpro_form_label">{{ __('streamAccount.display_name_publicly_as') }}</label>
                                            <input type="text" name="display_name" id="display_name"
                                                value="{{__('streamMovies.marvin_mcKinney')}}" class="pmpro_form_input pmpro_form_input-text">
                                        </div>
                                        <div class="pmpro_form_field pmpro_form_field-user_email">
                                            <label for="user_email"
                                                class="pmpro_form_label">{{ __('streamAccount.email') }}</label>
                                            <input type="email" name="user_email" id="user_email" value="marvin@demo.com"
                                                class="pmpro_form_input pmpro_form_input-email" autocomplete="email">
                                        </div>
                                    </div>
                                </fieldset>

                                <input type="hidden" name="action" value="update-profile">
                                <input type="hidden" name="user_id" value="12">
                                <div class="pmpro_form_submit">
                                    <button type="button" name="submit"
                                        class="pmpro_btn pmpro_btn-submit-update-profile">{{ __('streamButtons.update_profile') }}</button>
                                    <button type="button" name="cancel"
                                        class="pmpro_btn pmpro_btn-cancel">{{ __('streamButtons.cancel') }}</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </div>
    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}
@endsection
