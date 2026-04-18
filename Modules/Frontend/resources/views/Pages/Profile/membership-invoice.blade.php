@extends('frontend::layouts.master', ['isBreadCrumb' => true, 'title' => 'Invoice'])

@section('content')
    <section class="section-padding">
        <div class="pmpro container">
            @if ($order)
                @php
                    $tierName = $order->payable?->tier?->name ?? '—';
                    $billingPeriod = $order->payable?->tier?->billing_period ?? null;
                    $statusCls = $order->status === 'completed' ? 'pmpro_tag-success' : 'pmpro_tag-warning';
                @endphp
                <div class="pmpro_card">
                    <h3 class="pmpro_card_title pmpro_font-large">
                        Invoice #{{ $order->merchant_reference ?: $order->id }}
                    </h3>
                    <div class="pmpro_card_content">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="text-muted mb-2">Billed to</h6>
                                <div>{{ $user->full_name ?: $user->username }}</div>
                                <div class="text-muted small">{{ $user->email }}</div>
                            </div>
                            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                                <h6 class="text-muted mb-2">Order details</h6>
                                <div><strong>Date:</strong> {{ $order->created_at?->format('F j, Y') }}</div>
                                <div><strong>Status:</strong>
                                    <span class="pmpro_tag {{ $statusCls }}">{{ ucfirst($order->status) }}</span>
                                </div>
                                @if ($order->payment_method)
                                    <div class="text-muted small"><strong>Method:</strong> {{ $order->payment_method }}</div>
                                @endif
                                @if ($order->order_tracking_id)
                                    <div class="text-muted small"><strong>Tracking:</strong> {{ $order->order_tracking_id }}</div>
                                @endif
                            </div>
                        </div>

                        <table class="pmpro_table">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <strong>{{ $tierName }}</strong>
                                        @if ($billingPeriod)
                                            <div class="text-muted small">{{ ucfirst($billingPeriod) }} subscription</div>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        {{ $order->currency ?? 'USD' }} {{ number_format($order->amount, 2) }}
                                    </td>
                                </tr>
                                <tr>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">
                                        {{ $order->currency ?? 'USD' }} {{ number_format($order->amount, 2) }}
                                    </th>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="pmpro_card_actions">
                        <a href="{{ route('frontend.membership-orders') }}" class="btn btn-outline-primary btn-sm">
                            ← All orders
                        </a>
                        <a href="javascript:window.print()" class="btn btn-primary btn-sm ms-2">
                            <i class="ph ph-printer me-1"></i> Print
                        </a>
                    </div>
                </div>
            @else
                <div class="pmpro_card">
                    <div class="pmpro_card_content text-center py-5">
                        <p class="text-muted mb-3">No invoice found.</p>
                        <a href="{{ route('frontend.membership-level') }}" class="btn btn-primary">Browse plans</a>
                    </div>
                </div>
            @endif
        </div>
    </section>

    @include('frontend::components.widgets.mobile-footer')
@endsection
