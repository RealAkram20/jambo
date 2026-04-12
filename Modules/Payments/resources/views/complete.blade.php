@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-6 mx-auto">
            <div class="card text-center">
                <div class="card-body p-5">
                    @php
                        $icon = match($result) {
                            'success' => 'check-circle',
                            'error' => 'x-circle',
                            'cancelled' => 'prohibit',
                            'pending' => 'hourglass',
                            default => 'question',
                        };
                        $colour = match($result) {
                            'success' => 'var(--bs-success, #2dd47a)',
                            'error', 'cancelled' => 'var(--bs-danger, #ef4444)',
                            'pending' => 'var(--bs-warning, #f59e0b)',
                            default => 'var(--bs-primary)',
                        };
                        $title = match($result) {
                            'success' => 'Payment received',
                            'error' => 'Payment failed',
                            'cancelled' => 'Payment cancelled',
                            'pending' => 'Payment pending',
                            default => 'Payment status',
                        };
                        $subtitle = match($result) {
                            'success' => 'Thanks — your payment was received. You can continue using Jambo.',
                            'error' => $message ?: 'We could not complete the payment. No charge was made.',
                            'cancelled' => 'You cancelled the payment. No charge was made.',
                            'pending' => 'Your payment is still processing. We\'ll update your account once PesaPal confirms.',
                            default => 'Check your order history for the latest status.',
                        };
                    @endphp

                    <i class="ph-fill ph-{{ $icon }}" style="font-size:64px;color:{{ $colour }};"></i>
                    <h3 class="mt-3">{{ $title }}</h3>
                    <p class="text-muted">{{ $subtitle }}</p>

                    @if ($order)
                        <div class="mt-4 p-3 rounded text-start" style="background:#0b0f17;border:1px solid #1f2738;font-size:13px;">
                            <div class="d-flex justify-content-between mb-1"><span class="text-muted">Reference</span><code>{{ $order->merchant_reference }}</code></div>
                            <div class="d-flex justify-content-between mb-1"><span class="text-muted">Amount</span><strong>{{ number_format($order->amount, 2) }} {{ $order->currency }}</strong></div>
                            <div class="d-flex justify-content-between"><span class="text-muted">Status</span><strong>{{ $order->status }}</strong></div>
                        </div>
                    @endif

                    <div class="mt-4">
                        <a href="{{ url('/') }}" class="btn btn-primary">Return home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
