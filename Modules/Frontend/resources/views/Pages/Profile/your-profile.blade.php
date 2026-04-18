@extends('frontend::layouts.master', ['isSwiperSlider' => true, 'IS_MEGA' => true, 'isBreadCrumb' => true, 'title' => __('header.your_profile')])

@section('content')
    <div class="section-padding">
        <div class="pmpro container">
            <section id="pmpro_member_profile_edit" class="pmpro_section">
                <div class="pmpro_section_content">
                    @if (session('status'))
                        <div class="alert alert-success mb-3">{{ session('status') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger mb-3">
                            <ul class="m-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form id="member-profile-edit" class="pmpro_form" action="{{ route('frontend.your-profile.update') }}" method="post">
                        @csrf
                        <div class="pmpro_card">
                            <div class="pmpro_card_content">
                                <fieldset id="pmpro_member_profile_edit-account-information" class="pmpro_form_fieldset">
                                    <legend class="pmpro_form_legend">
                                        <h2 class="pmpro_form_heading pmpro_font-large">
                                            {{ __('streamTag.account_information') }}</h2>
                                    </legend>
                                    <div class="pmpro_form_fields pmpro_cols-2">
                                        <div class="pmpro_form_field pmpro_form_field-first_name">
                                            <label for="first_name"
                                                class="pmpro_form_label">{{ __('streamAccount.first_name') }}</label>
                                            <input type="text" name="first_name" id="first_name"
                                                value="{{ old('first_name', $user->first_name) }}"
                                                class="pmpro_form_input pmpro_form_input-text" autocomplete="given-name">
                                        </div>
                                        <div class="pmpro_form_field pmpro_form_field-last_name">
                                            <label for="last_name"
                                                class="pmpro_form_label">{{ __('streamAccount.last_name') }}</label>
                                            <input type="text" name="last_name" id="last_name"
                                                value="{{ old('last_name', $user->last_name) }}"
                                                class="pmpro_form_input pmpro_form_input-text" autocomplete="family-name">
                                        </div>
                                        <div class="pmpro_form_field pmpro_form_field-display_name">
                                            <label for="display_name"
                                                class="pmpro_form_label">{{ __('streamAccount.display_name_publicly_as') }}</label>
                                            <input type="text" name="display_name" id="display_name"
                                                value="{{ $user->full_name }}"
                                                class="pmpro_form_input pmpro_form_input-text" readonly>
                                        </div>
                                        <div class="pmpro_form_field pmpro_form_field-user_email">
                                            <label for="email"
                                                class="pmpro_form_label">{{ __('streamAccount.email') }}</label>
                                            <input type="email" name="email" id="email"
                                                value="{{ old('email', $user->email) }}"
                                                class="pmpro_form_input pmpro_form_input-email" autocomplete="email">
                                        </div>
                                    </div>
                                </fieldset>

                                <div class="pmpro_form_submit">
                                    <button type="submit" name="submit"
                                        class="pmpro_btn pmpro_btn-submit-update-profile">{{ __('streamButtons.update_profile') }}</button>
                                    <a href="{{ route('frontend.your-profile') }}" class="pmpro_btn pmpro_btn-cancel">{{ __('streamButtons.cancel') }}</a>
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
