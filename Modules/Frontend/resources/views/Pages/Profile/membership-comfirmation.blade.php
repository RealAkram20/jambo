@extends('frontend::layouts.master', ['isBreadCrumb' => true, 'title' => __('profile.membership_comfirmation') ?? 'Confirmation'])

@section('content')
    <section class="section-padding">
        <div class="pmpro container">
            @if ($order)
                @php
                    $tierName = $order->payable?->tier?->name ?? '—';
                    $billingPeriod = $order->payable?->tier?->billing_period ?? null;
                @endphp
                <section id="pmpro_confirmation" class="pmpro_section">
                    <div class="text-center mb-4">
                        <i class="ph-fill ph-check-circle" style="font-size: 64px; color: oklch(0.72 0.19 145);"></i>
                        <h2 class="mt-3 mb-2">{{ __('profile.thank_you_for_your_membership') ?? 'Thanks for your order!' }}</h2>
                        <p class="text-muted">
                            {{ __('profile.below_are_details_about_your_membership') ?? 'Your payment has been received. Details below.' }}
                        </p>
                    </div>

                    <div class="pmpro_card">
                        <h3 class="pmpro_card_title pmpro_font-large">Order #{{ $order->merchant_reference ?: $order->id }}</h3>
                        <div class="pmpro_card_content">
                            <ul class="pmpro_list pmpro_list-plain">
                                <li class="pmpro_list_item"><strong>Plan:</strong> {{ $tierName }}
                                    @if ($billingPeriod) <span class="text-muted">({{ ucfirst($billingPeriod) }})</span> @endif
                                </li>
                                <li class="pmpro_list_item">
                                    <strong>Amount:</strong>
                                    {{ $order->currency ?? 'USD' }} {{ number_format($order->amount, 2) }}
                                </li>
                                <li class="pmpro_list_item">
                                    <strong>Paid on:</strong> {{ $order->created_at?->format('F j, Y \a\t H:i') }}
                                </li>
                                @if ($order->payment_method)
                                    <li class="pmpro_list_item"><strong>Method:</strong> {{ $order->payment_method }}</li>
                                @endif
                            </ul>
                        </div>
                        <div class="pmpro_card_actions">
                            <a href="{{ route('frontend.membership-invoice', $order->id) }}" class="btn btn-outline-primary btn-sm">
                                View full invoice
                            </a>
                            <a href="{{ route('frontend.ott') }}" class="btn btn-primary btn-sm ms-2">Start watching</a>
                        </div>
                    </div>
                </section>
            @else
                <div class="pmpro_card">
                    <div class="pmpro_card_content text-center py-5">
                        <p class="text-muted mb-3">We couldn't find a recent successful order.</p>
                        <a href="{{ route('frontend.membership-level') }}" class="btn btn-primary">Browse plans</a>
                    </div>
                </div>
            @endif
        </div>
    </section>

    @include('frontend::components.widgets.mobile-footer')
@endsection
