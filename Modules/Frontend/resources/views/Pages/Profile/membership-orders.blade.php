@extends('frontend::layouts.master', ['isBreadCrumb' => true, 'title' => __('streamAccount.membership_order') ?? 'Order History'])

@section('content')
    <div class="section-padding">
        <div class="pmpro container">
            <section id="pmpro_order_list" class="pmpro_section">
                <h2 class="pmpro_section_title pmpro_font-x-large">
                    {{ __('streamTag.order_history') ?? 'Order History' }}
                </h2>
                <div class="pmpro_card">
                    <div class="pmpro_card_content">
                        @if ($orders->count())
                            <table class="pmpro_table pmpro_table_orders">
                                <thead>
                                    <tr>
                                        <th class="pmpro_table_order-date">{{ __('streamShop.date') ?? 'Date' }}</th>
                                        <th class="pmpro_table_order-level">{{ __('streamTag.level') ?? 'Plan' }}</th>
                                        <th class="pmpro_table_order-amount">{{ __('streamTag.total') ?? 'Amount' }}</th>
                                        <th class="pmpro_table_order-status">{{ __('streamShop.status') ?? 'Status' }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($orders as $order)
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
                                            <td class="pmpro_table_order-level" data-title="Level">{{ $tierName }}</td>
                                            <td class="pmpro_table_order-amount" data-title="Amount">
                                                {{ $order->currency ?? 'USD' }} {{ number_format($order->amount, 2) }}
                                            </td>
                                            <td class="pmpro_table_order-status" data-title="Status">
                                                <span class="pmpro_tag {{ $statusCls }}">{{ ucfirst($order->status) }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <div class="mt-4">
                                {{ $orders->links() }}
                            </div>
                        @else
                            <p class="text-muted mb-0">You haven't placed any orders yet.</p>
                            <div class="pmpro_card_actions mt-3">
                                <a href="{{ route('frontend.membership-level') }}" class="btn btn-primary">
                                    Browse plans
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </section>

            <div class="pmpro_actions_nav">
                <span class="pmpro_actions_nav-left">
                    <a href="{{ route('frontend.membership-account') }}">
                        ← Back to My Account
                    </a>
                </span>
            </div>
        </div>
    </div>

    @include('frontend::components.widgets.mobile-footer')
@endsection
