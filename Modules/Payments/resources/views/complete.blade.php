@extends('frontend::layouts.master', [
    'isBreadCrumb' => true,
    'title' => match($result) {
        'success' => 'Payment received',
        'error' => 'Payment failed',
        'cancelled' => 'Payment cancelled',
        'pending' => 'Payment pending',
        default => 'Payment status',
    },
])

@section('content')
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
            'success' => 'Thanks — your subscription is active. Enjoy Jambo.',
            'error' => $message ?: 'We could not complete the payment. No charge was made.',
            'cancelled' => 'You cancelled the payment. No charge was made.',
            'pending' => 'Your payment is still processing. We\'ll update your subscription as soon as PesaPal confirms — usually under a minute.',
            default => 'Check your billing history for the latest status.',
        };

        // Post-checkout CTA adapts to the outcome: success → start watching,
        // error/cancelled → back to pricing to try again, pending → billing
        // tab so the user can watch the state change live.
        $cta = match($result) {
            'success' => ['label' => 'Start watching', 'href' => route('frontend.ott')],
            'error', 'cancelled' => ['label' => 'Try again', 'href' => route('frontend.pricing-page')],
            'pending' => auth()->check()
                ? ['label' => 'View billing', 'href' => route('profile.billing', ['username' => auth()->user()->username])]
                : ['label' => 'Return home', 'href' => url('/')],
            default => ['label' => 'Return home', 'href' => url('/')],
        };
    @endphp

    <section class="section-padding">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 col-md-8 mx-auto">
                    <div class="card jambo-payment-result text-center" style="background:#141923;border:1px solid #1f2738;border-radius:12px;">
                        <div class="card-body p-5">
                            <i class="ph-fill ph-{{ $icon }} d-block" style="font-size:72px;color:{{ $colour }};line-height:1;"></i>

                            <h3 class="mt-4 mb-2">{{ $title }}</h3>
                            <p class="text-muted mb-0">{{ $subtitle }}</p>

                            @if ($order)
                                <div class="mt-4 p-3 rounded text-start" style="background:#0b0f17;border:1px solid #1f2738;font-size:13px;">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Reference</span>
                                        <code class="text-body">{{ $order->merchant_reference }}</code>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Amount</span>
                                        <strong>{{ $order->currency }} {{ number_format((float) $order->amount, 0) }}</strong>
                                    </div>
                                    @if ($order->payment_method)
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Method</span>
                                            <span>{{ ucfirst((string) $order->payment_method) }}</span>
                                        </div>
                                    @endif
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Status</span>
                                        <span class="badge @class([
                                            'bg-success' => $order->status === 'completed',
                                            'bg-warning' => $order->status === 'pending',
                                            'bg-danger' => $order->status === 'failed',
                                            'bg-secondary' => $order->status === 'cancelled',
                                        ])">{{ ucfirst($order->status) }}</span>
                                    </div>
                                </div>
                            @endif

                            <div class="mt-4 d-flex gap-2 justify-content-center flex-wrap">
                                <a href="{{ $cta['href'] }}" class="btn btn-primary fw-semibold rounded-3 px-4">
                                    {{ $cta['label'] }}
                                </a>
                                @if ($result === 'pending')
                                    <a href="{{ route('frontend.ott') }}" class="btn btn-outline-secondary rounded-3 px-4">
                                        Browse while you wait
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('frontend::components.widgets.mobile-footer')
@endsection
