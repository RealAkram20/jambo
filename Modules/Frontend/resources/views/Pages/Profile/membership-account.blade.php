@extends('frontend::layouts.master', ['isSwiperSlider' => true, 'IS_MEGA' => true, 'isBreadCrumb' => true, 'title' => __('profile.membership_account')])

@section('content')
    <section class="section-padding">
        <div class="pmpro container">
            <section id="pmpro_account-profile" class="pmpro_section">
                <h2 class="pmpro_section_title pmpro_font-x-large">{{ __('frontendheader.my_account_page') }}</h2>
                <div class="pmpro_card">
                    <h3 class="pmpro_card_title pmpro_font-large pmpro_heading-with-avatar">
                        <img alt="img" src="{{ asset('frontend/images/user/userblank.jpg') }}"
                            class="avatar avatar-48 photo" height="48" width="48"> {{ __('streamTag.welcome') }},
                        {{ __('frontendheader.user_name') }}
                    </h3>
                    <div class="pmpro_card_content">
                        <ul class="pmpro_list pmpro_list-plain">
                            <li class="pmpro_list_item"><strong>{{ __('streamAccount.username') }}:</strong>
                                {{ __('streamShop.marvin') }}</li>
                            <li class="pmpro_list_item"><strong>{{ __('form.Email') }}:</strong> marvin@demo.com</li>
                        </ul>
                    </div> <!-- end pmpro_card_content -->
                    <div class="pmpro_card_actions">
                        <span class="pmpro_card_action"><a id="pmpro_actionlink-profile"
                                href="your-profile">{{ __('streamTag.edit_profile') }}</a></span><span
                            class="pmpro_card_action_separator"></span><span class="pmpro_card_action"><a
                                id="pmpro_actionlink-change-password"
                                href="change-password">{{ __('streamAccount.change_password') }}</a></span><span
                            class="pmpro_card_action_separator"></span><span class="pmpro_card_action">
                            <a id="pmpro_actionlink-logout" href="{{ route('logout') }}"
                                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">{{ __('streamAccount.logout') }}
                            </a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                @csrf</form>
                        </span>
                    </div> <!-- end pmpro_card_actions -->
                </div> <!-- end pmpro_card -->
            </section> <!-- end pmpro_account-profile -->

            <section id="pmpro_account-membership" class="pmpro_section">
                <h2 class="pmpro_section_title pmpro_font-x-large">{{ __('streamTag.my_memberships') }}</h2>
                <div class="pmpro_section_content">
                    <div id="pmpro_account-membership-none" class="pmpro_card">
                        <div class="pmpro_card_content">
                            <p>{{ __('streamTag.you_do_not_have_membership') }} <a
                                    href="membership-level">{{ __('streamTag.choose_a_membership_level') }}</a>
                            </p>
                        </div> <!-- end pmpro_card_content -->
                    </div> <!-- end pmpro_card -->
                </div> <!-- end pmpro_section_content -->
            </section> <!-- end pmpro_account-membership -->

            <section id="pmpro_account-orders" class="pmpro_section">
                <h2 class="pmpro_section_title pmpro_font-x-large">{{ __('streamTag.order_history') }}</h2>
                <div class="pmpro_card">
                    <div class="pmpro_card_content">
                        <table class="pmpro_table pmpro_table_orders">
                            <thead>
                                <tr>
                                    <th class="pmpro_table_order-date">{{ __('streamShop.date') }}</th>
                                    <th class="pmpro_table_order-level">{{ __('streamTag.level') }}</th>
                                    <th class="pmpro_table_order-total">{{ __('streamTag.total') }}</th>
                                    <th class="pmpro_table_order-status">{{ __('streamShop.status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr id="pmpro_table_order-51311C6265">
                                    <th class="pmpro_table_order-date" data-title="Date"><a href="membership-invoice">
                                            February 18, 2025</a></th>
                                    <td class="pmpro_table_order-level" data-title="Level">{{ __('streamPricing.basic_plan') }}
                                    </td>
                                    <td class="pmpro_table_order-amount" data-title="Amount">$10.00</td>
                                    <td class="pmpro_table_order-status" data-title="Status">
                                        <span class="pmpro_tag pmpro_tag-success">{{ __('streamTag.paid') }}</span>
                                    </td>
                                </tr>
                                <tr id="pmpro_table_order-A467E41A35">
                                    <th class="pmpro_table_order-date" data-title="Date"><a href="membership-invoice">
                                            February 10, 2025</a></th>
                                    <td class="pmpro_table_order-level" data-title="Level">
                                        {{ __('streamPricing.premium_plan') }}
                                    </td>
                                    <td class="pmpro_table_order-amount" data-title="Amount">$180.00</td>
                                    <td class="pmpro_table_order-status" data-title="Status">
                                        <span class="pmpro_tag pmpro_tag-success">{{ __('streamTag.paid') }}</span>
                                    </td>
                                </tr>
                                <tr id="pmpro_table_order-3B9A37110A">
                                    <th class="pmpro_table_order-date" data-title="Date"><a href="membership-invoice">
                                            February 6, 2025</a></th>
                                    <td class="pmpro_table_order-level" data-title="Level">{{ __('streamPricing.basic_plan') }}
                                    </td>
                                    <td class="pmpro_table_order-amount" data-title="Amount">$10.00</td>
                                    <td class="pmpro_table_order-status" data-title="Status">
                                        <span class="pmpro_tag pmpro_tag-success">{{ __('streamTag.paid') }}</span>
                                    </td>
                                </tr>
                                <tr id="pmpro_table_order-68B3C8559C">
                                    <th class="pmpro_table_order-date" data-title="Date"><a href="membership-invoice">
                                            February 6, 2025</a></th>
                                    <td class="pmpro_table_order-level" data-title="Level">{{ __('streamPricing.basic_plan') }}
                                    </td>
                                    <td class="pmpro_table_order-amount" data-title="Amount">$10.00</td>
                                    <td class="pmpro_table_order-status" data-title="Status">
                                        <span class="pmpro_tag pmpro_tag-success">{{ __('streamTag.paid') }}</span>
                                    </td>
                                </tr>
                                <tr id="pmpro_table_order-FB6969474C">
                                    <th class="pmpro_table_order-date" data-title="Date"><a href="membership-invoice">
                                            January 29, 2025</a></th>
                                    <td class="pmpro_table_order-level" data-title="Level">
                                        {{ __('streamTag.standard_plan') }}</td>
                                    <td class="pmpro_table_order-amount" data-title="Amount">$79.00</td>
                                    <td class="pmpro_table_order-status" data-title="Status">
                                        <span class="pmpro_tag pmpro_tag-success">{{ __('streamTag.paid') }}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div> <!-- end pmpro_card_content -->
                </div> <!-- end pmpro_card -->
            </section> <!-- end pmpro_account-orders -->
            <div class="pmpro_actions_nav">
                <span class="pmpro_actions_nav-left"><a href="membership-orders">{{ __('streamShop.view_all_orders') }}</a>
                </span>
            </div>

        </div>
    </section>

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}
@endsection
