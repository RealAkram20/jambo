@extends('frontend::layouts.master', ['isBreadCrumb' => true, 'title' => __('frontendheader.pricing_plan')])

@php
    /**
     * Highlight rule for the "most popular" pill + active badge:
     * flag whichever monthly tier has the highest access level. Falls
     * back to the single highest-access tier so even all-yearly
     * catalogs still get a visual winner.
     */
    $tiers = $tiers ?? collect();
    $highlightedId = $tiers
        ->where('billing_period', \Modules\Subscriptions\app\Models\SubscriptionTier::PERIOD_MONTHLY)
        ->sortByDesc('access_level')
        ->first()
        ?->id
        ?? $tiers->sortByDesc('access_level')->first()?->id;
@endphp

@section('content')
    <div class="section-padding">
        <div class="container">
            {{-- Surface server-side errors from the payment flow (gateway
                 misconfigured, tier inactive, etc.). Same look as the
                 rest of the site's flash messages. --}}
            @if (session('error'))
                <div class="alert alert-danger mb-4 text-center">{{ session('error') }}</div>
            @endif

            <div class="row">
                @forelse ($tiers as $tier)
                    @php
                        $isHighlighted = $tier->id === $highlightedId;
                        $features = is_array($tier->features) ? $tier->features : [];
                    @endphp
                    <div class="col-xl-4 col-md-6 mb-3 mb-lg-0">
                        <div class="pricing-plan-wrapper">
                            @if ($isHighlighted)
                                <div class="pricing-plan-discount bg-primary p-2 text-center">
                                    <span class="text-white">{{ __('streamTag.most_popular') ?? 'Most popular' }}</span>
                                </div>
                            @endif
                            <div class="pricing-plan-header">
                                <div class="plan-wrapper @if ($isHighlighted) d-flex align-items-center justify-content-between @endif">
                                    <h4 class="plan-name">{{ $tier->name }}</h4>
                                    @if ($isHighlighted)
                                        <span class="badge bg-primary rounded-2">
                                            <small>{{ __('streamTag.active') ?? 'Featured' }}</small>
                                        </span>
                                    @endif
                                </div>
                                <div class="pricing-plan-details">
                                    <span class="plan-main-price">{{ $tier->currency }} {{ number_format((float) $tier->price, 0) }}</span>
                                    <span class="plan-period-time">/ {{ $tier->periodLabel() }}</span>
                                </div>
                            </div>
                            <div class="pricing-details">
                                @if ($tier->description)
                                    <div class="description">
                                        <p class="m-0">{{ $tier->description }}</p>
                                    </div>
                                @endif
                                <div class="pricing-plan-description">
                                    <ul class="list-inline p-0">
                                        @forelse ($features as $feature)
                                            <li>
                                                <i class="ph ph-check fw-bold"></i>
                                                <span class="font-size-18 fw-500">{{ $feature }}</span>
                                            </li>
                                        @empty
                                            <li>
                                                <i class="ph ph-check fw-bold"></i>
                                                <span class="font-size-18 fw-500">{{ $tier->name }} access</span>
                                            </li>
                                        @endforelse
                                    </ul>
                                </div>
                                <div class="pricing-plan-footer">
                                    <div class="iq-button">
                                        @php
                                            // Free tiers have no checkout flow — CTA becomes
                                            // "Get Started" and drops users onto the homepage
                                            // so they can browse. Paid tiers POST to the
                                            // payment gateway directly.
                                            $isFree = (float) $tier->price <= 0;
                                        @endphp

                                        @if ($isFree)
                                            <a href="{{ route('frontend.ott') }}"
                                                class="btn btn-primary fw-semibold rounded-3 w-100">
                                                {{ __('streamShop.get_started') }}
                                            </a>
                                        @elseif (!auth()->check())
                                            {{-- Guests: send them to login with an `intended`
                                                 that bounces back to /pricing so they land on
                                                 the same tier after signing in. --}}
                                            <a href="{{ route('login') }}?redirect={{ urlencode(route('frontend.pricing-page')) }}"
                                                class="btn btn-primary fw-semibold rounded-3 w-100">
                                                {{ __('streamShop.checkout') }}
                                            </a>
                                        @else
                                            {{-- Tier metadata on data-* attrs so the modal header
                                                 can show "Complete your payment — Premium Monthly
                                                 (UGX 30,000)" without a second server request. --}}
                                            <form method="POST" action="{{ route('payment.create-order') }}"
                                                  class="jambo-subscribe-form m-0"
                                                  data-tier-name="{{ $tier->name }}"
                                                  data-tier-price="{{ $tier->currency }} {{ number_format((float) $tier->price, 0) }} / {{ $tier->periodLabel() }}">
                                                @csrf
                                                <input type="hidden" name="subscription_tier_id" value="{{ $tier->id }}">
                                                <button type="submit" class="btn btn-primary fw-semibold rounded-3 w-100 jambo-subscribe-btn">
                                                    <span class="label">{{ __('streamShop.checkout') }}</span>
                                                    <span class="spinner spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center py-5 text-muted">
                        No subscription plans are active right now. Please check back soon.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}

    {{-- Iframe checkout modal. Intercepts every Subscribe form on this
         page, POSTs to /payment/create-order via fetch, and hosts
         PesaPal's redirect_url inside a dark-themed dialog with X-only
         dismissal. See the partial for the full UX rules. --}}
    @auth
        @include('frontend::components.partials.payment-modal')
    @endauth
@endsection
