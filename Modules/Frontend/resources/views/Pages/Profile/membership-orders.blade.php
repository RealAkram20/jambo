@extends('frontend::layouts.master', ['isSwiperSlider' => true, 'IS_MEGA' => true, 'isBreadCrumb' => true, 'title' => __('streamAccount.membership_order')])

@section('content')
    <div class="section-padding">
        <div class="pmpro container">
            <section id="pmpro_order_list" class="pmpro_section">
                <h2 class="pmpro_section_title pmpro_font-x-large">
                    {{ __('streamTag.order_history') }} </h2>
                <div class="pmpro_card">
                    <div class="pmpro_card_content">
                        <table class="pmpro_table pmpro_table_orders">
                            <thead>
                                <tr>
                                    <th class="pmpro_table_order-date">{{ __('streamShop.date') }}</th>
                                    <th class="pmpro_table_order-level">{{ __('streamTag.level') }}</th>
                                    <th class="pmpro_table_order-amount">{{ __('streamTag.total') }}</th>
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
                                    <td class="pmpro_table_order-level" data-title="Level">{{ __('streamPricing.premium_plan') }}
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
                                <tr id="pmpro_table_order-B34AF5AE30">
                                    <th class="pmpro_table_order-date" data-title="Date"><a href="membership-invoice">
                                            January 29, 2025</a></th>
                                    <td class="pmpro_table_order-level" data-title="Level">{{ __('streamPricing.premium_plan') }}
                                    </td>
                                    <td class="pmpro_table_order-amount" data-title="Amount">$180.00</td>
                                    <td class="pmpro_table_order-status" data-title="Status">
                                        <span class="pmpro_tag pmpro_tag-success">{{ __('streamTag.paid') }}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div> <!-- end pmpro_card_content -->
                </div> <!-- end pmpro_card -->
            </section>

            <div class="pmpro_actions_nav">
                <span class="pmpro_actions_nav-right"><a
                        href="membership-account">{{ __('streamAccount.view_your_membership_account') }}</a></span>
            </div>
        </div>
    </div>

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}
@endsection
