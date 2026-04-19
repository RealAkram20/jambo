@extends('profile-hub._layout', ['pageTitle' => 'Invoice', 'user' => $user, 'activeTab' => $activeTab])

@section('hub-content')
    @php
        $tierName = $order->payable?->tier?->name ?? '—';
        $billingPeriod = $order->payable?->tier?->billing_period ?? null;
        $statusCls = $order->status === 'completed' ? 'bg-success' : 'bg-warning';
    @endphp

    <div class="mb-3">
        <a href="{{ route('profile.billing', ['username' => $user->username]) }}"
           class="text-muted text-decoration-none small">
            <i class="ph ph-arrow-left me-1"></i> Back to order history
        </a>
    </div>

    <div class="jambo-hub-card">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h5 class="mb-0">Invoice #{{ $order->merchant_reference ?: $order->id }}</h5>
            <button onclick="window.print()" class="btn btn-outline-primary btn-sm">
                <i class="ph ph-printer me-1"></i> Print
            </button>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="text-muted small">Billed to</div>
                <div class="fw-semibold">{{ $user->full_name ?: $user->username }}</div>
                <div class="small text-muted">{{ $user->email }}</div>
            </div>
            <div class="col-md-6 text-md-end">
                <div class="text-muted small">Order details</div>
                <div><strong>Date:</strong> {{ $order->created_at?->format('F j, Y') }}</div>
                <div><strong>Status:</strong> <span class="badge {{ $statusCls }}">{{ ucfirst($order->status) }}</span></div>
                @if ($order->payment_method)
                    <div class="small text-muted"><strong>Method:</strong> {{ $order->payment_method }}</div>
                @endif
                @if ($order->order_tracking_id)
                    <div class="small text-muted"><strong>Tracking:</strong> {{ $order->order_tracking_id }}</div>
                @endif
            </div>
        </div>

        <table class="table table-dark align-middle mb-0">
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
                            <div class="small text-muted">{{ ucfirst($billingPeriod) }} subscription</div>
                        @endif
                    </td>
                    <td class="text-end">
                        {{ $order->currency ?? 'USD' }} {{ number_format($order->amount, 2) }}
                    </td>
                </tr>
                <tr class="fw-bold">
                    <td class="text-end">Total</td>
                    <td class="text-end">
                        {{ $order->currency ?? 'USD' }} {{ number_format($order->amount, 2) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
@endsection
