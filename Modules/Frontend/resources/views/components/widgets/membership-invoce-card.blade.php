<section id="pmpro_order_single" class="pmpro_order_single pmpro_section mt-0">
    <div class="pmpro_card">
        <div class="pmpro_card_actions text-end">
            <button class="pmpro_btn-plain pmpro_btn-print"
                onclick="if (!window.__cfRLUnblockHandlers) return false; window.print()">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="feather feather-printer">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                {{__('profile.print_or_save_as_pdf')}} </button>
        </div> <!-- end pmpro_card_actions -->
        <h2 class="pmpro_card_title pmpro_font-x-large d-flex align-items-center justify-content-between flex-wrap">
            {{__('streamShop.orders')}} #51311C6265 <span class="pmpro_list_item_value pmpro_tag pmpro_tag-success">{{__('profile.paid')}}</span>
        </h2>
        <div class="pmpro_card_content">

            <div class="pmpro_spacer"></div>

            <div id="pmpro_order_single-meta">
                <ul class="pmpro_list pmpro_list-plain pmpro_list-with-labels pmpro_cols-2">
                    <li id="pmpro_order_single-meta-order_date" class="pmpro_list_item">
                        <span class="pmpro_list_item_label">{{__('profile.order_date')}}</span>
                        February 18, 2025
                    </li>
                    <li id="pmpro_order_single-meta-payment_method" class="pmpro_list_item">
                        <span class="pmpro_list_item_label">{{__('streamShop.payment_method')}}</span>
                        {{__('streamMovies.visa_ending_in')}}
                    </li>
                    <li id="pmpro_order_single-meta-pay_to" class="pmpro_list_item">
                        <span class="pmpro_list_item_label">{{__('profile.pay_to')}}</span>
                        Jambo
                    </li>
                    <li id="pmpro_order_single-meta-bill_to" class="pmpro_list_item">
                        <span class="pmpro_list_item_label">{{__('profile.bill_to')}}</span>
                        PO Box 16122<br>
                        Collins Street West<br>
                        Victoria 8007<br>
                        US<br>
                        (123) 456-7899
                    </li>
                </ul>
            </div> <!-- end pmpro_order_single-meta -->



            <div class="pmpro_spacer"></div>


            <div id="pmpro_order_single-items">
                <h3 class="pmpro_font-large">
                    $10.00 paid on February 18, 2025 </h3>
                <div class="table-responsive">
                    <table class="pmpro_table">
                        <thead>
                            <tr>
                                <th>{{__('streamShop.description')}}</th>
                                <th>{{__('profile.amount')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th data-title="Description">
                                    {{__('streamAccount.basic_plan_for_order')}} #51311C6265 <p>{{__('form.account')}}: {{__('frontendheader.user_name')}} (marvin@demo.com)
                                    </p>
                                </th>
                                <td data-title="Amount">
                                    $10.00 </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td>{{__('streamShop.total')}}</td>
                                <td data-title="Total">
                                    $10.00 </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div> <!-- end pmpro_order_single-payment -->
        </div> <!-- end pmpro_card_content -->
    </div> <!-- end pmpro_card -->
</section> <!-- end pmpro_order_single -->
<div class="pmpro_actions_nav">
    <span class="pmpro_actions_nav-right"><a href="membership-account">{{__('profile.view_your_membership_account')}}</a></span>
    <span class="pmpro_actions_nav-left"><a href="membership-orders">
            {{__('profile.view_all_orders')}}</a></span>
</div> <!-- end pmpro_actions_nav -->
