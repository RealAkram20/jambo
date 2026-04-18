@extends('frontend::layouts.master', ['isBreadCrumb' => true, 'title' => __('frontendheader.my_account_page') ?? 'My Account'])

@section('content')
    <section class="section-padding">
        <div class="pmpro container">

            {{-- Profile summary --}}
            <section id="pmpro_account-profile" class="pmpro_section">
                <h2 class="pmpro_section_title pmpro_font-x-large">
                    {{ __('frontendheader.my_account_page') ?? 'My Account' }}
                </h2>
                <div class="pmpro_card">
                    <h3 class="pmpro_card_title pmpro_font-large pmpro_heading-with-avatar">
                        <img alt="{{ $user->full_name }}"
                            src="{{ $user->profile_image ?? asset('frontend/images/user/userblank.jpg') }}"
                            class="avatar avatar-48 photo" height="48" width="48">
                        {{ __('streamTag.welcome') ?? 'Welcome' }}, {{ $user->full_name ?: $user->username }}
                    </h3>
                    <div class="pmpro_card_content">
                        <ul class="pmpro_list pmpro_list-plain">
                            <li class="pmpro_list_item">
                                <strong>{{ __('streamAccount.username') ?? 'Username' }}:</strong>
                                {{ $user->username }}
                            </li>
                            <li class="pmpro_list_item">
                                <strong>{{ __('form.Email') ?? 'Email' }}:</strong> {{ $user->email }}
                            </li>
                        </ul>
                    </div>
                    <div class="pmpro_card_actions">
                        <span class="pmpro_card_action">
                            <a href="{{ route('frontend.your-profile') }}">{{ __('streamTag.edit_profile') ?? 'Edit Profile' }}</a>
                        </span>
                        <span class="pmpro_card_action_separator"></span>
                        <span class="pmpro_card_action">
                            <a href="{{ route('frontend.change-password') }}">{{ __('streamAccount.change_password') ?? 'Change Password' }}</a>
                        </span>
                        <span class="pmpro_card_action_separator"></span>
                        <span class="pmpro_card_action">
                            <a href="{{ route('logout') }}"
                                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                {{ __('streamAccount.logout') ?? 'Logout' }}
                            </a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">@csrf</form>
                        </span>
                    </div>
                </div>
            </section>

            {{-- Active membership --}}
            <section id="pmpro_account-membership" class="pmpro_section">
                <h2 class="pmpro_section_title pmpro_font-x-large">
                    {{ __('streamTag.my_memberships') ?? 'My Membership' }}
                </h2>
                <div class="pmpro_section_content">
                    @if ($activeSub)
                        <div class="pmpro_card">
                            <h3 class="pmpro_card_title pmpro_font-large">{{ $activeSub->tier->name ?? '—' }}</h3>
                            <div class="pmpro_card_content">
                                <ul class="pmpro_list pmpro_list-plain">
                                    <li class="pmpro_list_item">
                                        <strong>Started:</strong>
                                        {{ $activeSub->starts_at?->format('F j, Y') ?? '—' }}
                                    </li>
                                    <li class="pmpro_list_item">
                                        <strong>Renews:</strong>
                                        {{ $activeSub->ends_at?->format('F j, Y') ?? '—' }}
                                        @if ($activeSub->auto_renew)
                                            <span class="pmpro_tag pmpro_tag-success ms-2">Auto-renew on</span>
                                        @endif
                                    </li>
                                    <li class="pmpro_list_item">
                                        <strong>Status:</strong>
                                        <span class="pmpro_tag pmpro_tag-success">
                                            {{ ucfirst($activeSub->status ?? 'active') }}
                                        </span>
                                    </li>
                                </ul>
                            </div>
                            <div class="pmpro_card_actions">
                                <a href="{{ route('frontend.membership-level') }}" class="btn btn-primary btn-sm">
                                    Change plan
                                </a>
                            </div>
                        </div>
                    @else
                        <div id="pmpro_account-membership-none" class="pmpro_card">
                            <div class="pmpro_card_content">
                                <p>
                                    {{ __('streamTag.you_do_not_have_membership') ?? "You don't have an active membership." }}
                                    <a href="{{ route('frontend.membership-level') }}">
                                        {{ __('streamTag.choose_a_membership_level') ?? 'Choose a membership level' }}
                                    </a>
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            </section>

            {{-- Recent orders --}}
            <section id="pmpro_account-orders" class="pmpro_section">
                <h2 class="pmpro_section_title pmpro_font-x-large">
                    {{ __('streamTag.order_history') ?? 'Order History' }}
                </h2>
                <div class="pmpro_card">
                    <div class="pmpro_card_content">
                        @if ($recentOrders->count())
                            <table class="pmpro_table pmpro_table_orders">
                                <thead>
                                    <tr>
                                        <th class="pmpro_table_order-date">{{ __('streamShop.date') ?? 'Date' }}</th>
                                        <th class="pmpro_table_order-level">{{ __('streamTag.level') ?? 'Plan' }}</th>
                                        <th class="pmpro_table_order-total">{{ __('streamTag.total') ?? 'Amount' }}</th>
                                        <th class="pmpro_table_order-status">{{ __('streamShop.status') ?? 'Status' }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recentOrders as $order)
                                        @php
                                            $tierName = $order->payable?->tier?->name ?? '—';
                                            $statusCls = $order->status === 'completed' ? 'pmpro_tag-success' : 'pmpro_tag-warning';
                                        @endphp
                                        <tr>
                                            <th class="pmpro_table_order-date" data-title="Date">
                                                <a href="{{ route('frontend.membership-invoice', $order->id) }}">
                                                    {{ $order->created_at?->format('F j, Y') }}
                                                </a>
                                            </th>
                                            <td data-title="Level">{{ $tierName }}</td>
                                            <td data-title="Amount">
                                                {{ $order->currency ?? 'USD' }} {{ number_format($order->amount, 2) }}
                                            </td>
                                            <td data-title="Status">
                                                <span class="pmpro_tag {{ $statusCls }}">{{ ucfirst($order->status) }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="text-muted mb-0">No orders yet.</p>
                        @endif
                    </div>
                </div>
            </section>

            @if ($recentOrders->count())
                <div class="pmpro_actions_nav">
                    <span class="pmpro_actions_nav-left">
                        <a href="{{ route('frontend.membership-orders') }}">
                            {{ __('streamShop.view_all_orders') ?? 'View all orders' }}
                        </a>
                    </span>
                </div>
            @endif

        </div>
    </section>

    @include('frontend::components.widgets.mobile-footer')
@endsection
