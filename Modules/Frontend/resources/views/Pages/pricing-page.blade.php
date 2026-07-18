@extends('frontend::layouts.master', ['isBreadCrumb' => true, 'title' => __('frontendheader.pricing_plan')])

@php
    use Modules\Subscriptions\app\Models\SubscriptionTier;

    $tiers = $tiers ?? collect();
    $currentSub = $currentSub ?? null;
    $currentTierId = $currentSub?->subscription_tier_id;

    /**
     * Free tiers never render as cards any more: every signed-in viewer
     * without a paid subscription already holds Free (pre-subscribed at
     * signup), so it shows as the "Current plan" strip instead. Guests
     * get a compact signup strip below the paid grid. Only paid tiers
     * populate the period tabs.
     */
    $freeTier = $tiers->first(fn ($t) => (float) $t->price <= 0);
    $paidTiers = $tiers->filter(fn ($t) => (float) $t->price > 0);

    $periodLabels = [
        SubscriptionTier::PERIOD_DAILY => __('Daily'),
        SubscriptionTier::PERIOD_WEEKLY => __('Weekly'),
        SubscriptionTier::PERIOD_MONTHLY => __('Monthly'),
        SubscriptionTier::PERIOD_YEARLY => __('Yearly'),
    ];
    // Tab bar only shows periods that actually have paid tiers.
    $periodGroups = collect(array_keys($periodLabels))
        ->mapWithKeys(fn ($p) => [$p => $paidTiers->where('billing_period', $p)->values()])
        ->filter(fn ($group) => $group->isNotEmpty());

    /**
     * Highlight rule for the "most popular" pill: whichever monthly tier
     * has the highest access level, falling back to the single highest-
     * access paid tier so even all-yearly catalogs get a visual winner.
     */
    $highlightedId = $paidTiers
        ->where('billing_period', SubscriptionTier::PERIOD_MONTHLY)
        ->sortByDesc('access_level')
        ->first()
        ?->id
        ?? $paidTiers->sortByDesc('access_level')->first()?->id;

    /**
     * Query-param highlight. The device-limit picker appends
     * ?highlight=<slug> when suggesting an upgrade; the matching card
     * gets the attention-pulse class and is the scroll anchor for the
     * JS at the bottom of the page. Its billing period also wins the
     * default tab so the card is actually visible on first paint.
     */
    $pulseSlug = request()->query('highlight');
    $pulseTier = $pulseSlug ? $paidTiers->firstWhere('slug', $pulseSlug) : null;

    // Default tab: pulsed tier's period → the viewer's own period → monthly → first.
    $activePeriod = $pulseTier?->billing_period
        ?? (($p = $currentSub?->tier?->billing_period) && $periodGroups->has($p) ? $p : null)
        ?? ($periodGroups->has(SubscriptionTier::PERIOD_MONTHLY) ? SubscriptionTier::PERIOD_MONTHLY : $periodGroups->keys()->first());

    /**
     * Referral offer state (set by FrontendController::pricing_page for
     * logged-in viewers). `eligible` → first payment gets the percent
     * off, so paid tiers render struck-through original + discounted
     * price. `can_apply_code` → show the code-entry strip instead.
     */
    $referralOffer = $referralOffer ?? null;
    $referralEligible = (bool) ($referralOffer['eligible'] ?? false);
    $referralCanApply = (bool) ($referralOffer['can_apply_code'] ?? false);
    $referralPercent = (string) ($referralOffer['discount_percent'] ?? '0');
    // Strip decimal zeros only ("10.50" → "10.5") — a plain "10" has no
    // fraction to trim and must not lose its integer zero.
    $referralPercent = str_contains($referralPercent, '.') ? rtrim(rtrim($referralPercent, '0'), '.') : $referralPercent;

    $freeFeatures = is_array($freeTier?->features) ? implode(' · ', $freeTier->features) : '';
@endphp

@section('content')
    <div class="section-padding">
        <div class="container">
            {{-- Flash messages (error / info) are rendered by the frontend
                 master layout, so the page body doesn't need its own alert. --}}

            @if ($referralEligible)
                <div class="alert d-flex align-items-center gap-2 mb-4"
                     style="background: rgba(var(--bs-primary-rgb), .12); border: 1px solid rgba(var(--bs-primary-rgb), .35); color: inherit;">
                    <i class="ph-fill ph-gift" style="color: var(--bs-primary); font-size: 1.25rem;"></i>
                    <span>{{ __('Referral discount') }}: <strong>−{{ $referralPercent }}%</strong> {{ __('on your first payment') }}</span>
                </div>
            @endif
            {{-- Code entry moved into the checkout modal (coupon step) —
                 see payment-modal.blade.php + the JamboCoupon config at
                 the bottom of this page. --}}

            @auth
                {{-- The viewer's plan, stated once up top — Free is
                     pre-subscribed at signup, so it is never a card
                     to "select"; the grid below is purely upgrades. --}}
                <div class="d-flex flex-wrap align-items-center gap-3 mb-4 p-3 rounded-3 jambo-strip">
                    <i class="ph-fill {{ $currentSub ? 'ph-crown-simple' : 'ph-user-circle' }}"
                       style="color: var(--bs-primary); font-size: 1.75rem;"></i>
                    <div>
                        <div class="fw-semibold">
                            {{ __('Current plan') }}: {{ $currentSub?->tier?->name ?? $freeTier?->name ?? __('Free') }}
                        </div>
                        <div class="small text-muted">
                            @if ($currentSub)
                                {{ __('Active until :date', ['date' => $currentSub->ends_at->format('M j, Y')]) }}
                            @elseif ($freeFeatures)
                                {{ $freeFeatures }}
                            @endif
                        </div>
                    </div>
                </div>
            @endauth

            @if ($periodGroups->isNotEmpty())
                {{-- Billing-period tabs. flex-nowrap + overflow-auto keeps
                     them on one swipeable line on narrow phones. --}}
                <div class="jambo-period-tabs-scroll mb-4">
                    <ul class="nav nav-pills jambo-period-tabs justify-content-md-center flex-nowrap gap-2"
                        id="pricing-period-tabs" role="tablist">
                        @foreach ($periodGroups as $period => $group)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link @if ($period === $activePeriod) active @endif"
                                        id="tab-{{ $period }}" data-bs-toggle="pill" data-bs-target="#pane-{{ $period }}"
                                        type="button" role="tab" aria-controls="pane-{{ $period }}"
                                        aria-selected="{{ $period === $activePeriod ? 'true' : 'false' }}">
                                    {{ $periodLabels[$period] }}
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="tab-content" id="pricing-period-panes">
                    @foreach ($periodGroups as $period => $group)
                        <div class="tab-pane fade @if ($period === $activePeriod) show active @endif"
                             id="pane-{{ $period }}" role="tabpanel" aria-labelledby="tab-{{ $period }}">
                            <div class="row justify-content-center">
                                @foreach ($group as $tier)
                                    @php
                                        $isCurrent = $tier->id === $currentTierId;
                                        $isHighlighted = !$isCurrent && $tier->id === $highlightedId;
                                        $isPulsed = $pulseSlug && $pulseSlug === $tier->slug;
                                        $features = is_array($tier->features) ? $tier->features : [];
                                        $discountedPrice = $referralEligible
                                            ? round((float) $tier->price * (1 - (float) $referralPercent / 100), 2)
                                            : null;
                                    @endphp
                                    <div class="col-xl-4 col-md-6 mb-4">
                                        <div class="pricing-plan-wrapper @if ($isPulsed) jambo-tier-pulse @endif"
                                             id="tier-{{ $tier->slug }}"
                                             data-tier-slug="{{ $tier->slug }}">
                                            @if ($isHighlighted)
                                                <div class="pricing-plan-discount bg-primary p-2 text-center">
                                                    <span class="text-white">{{ __('streamTag.most_popular') ?? 'Most popular' }}</span>
                                                </div>
                                            @endif
                                            <div class="pricing-plan-header">
                                                <div class="plan-wrapper @if ($isHighlighted || $isCurrent) d-flex align-items-center justify-content-between @endif">
                                                    <h4 class="plan-name">{{ $tier->name }}</h4>
                                                    @if ($isCurrent)
                                                        <span class="badge bg-success rounded-2">
                                                            <small>{{ __('Current Plan') }}</small>
                                                        </span>
                                                    @elseif ($isHighlighted)
                                                        <span class="badge bg-primary rounded-2">
                                                            <small>{{ __('streamTag.active') ?? 'Featured' }}</small>
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="pricing-plan-details">
                                                    @if ($discountedPrice !== null)
                                                        <span class="text-muted text-decoration-line-through me-1">{{ $tier->currency }} {{ number_format((float) $tier->price, 0) }}</span>
                                                        <span class="plan-main-price">{{ $tier->currency }} {{ number_format($discountedPrice, 0) }}</span>
                                                    @else
                                                        <span class="plan-main-price">{{ $tier->currency }} {{ number_format((float) $tier->price, 0) }}</span>
                                                    @endif
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
                                                        @if ($discountedPrice !== null)
                                                            {{-- Referral tag is on this viewer — surface the
                                                                 discount inside the card, blue like the banner,
                                                                 with the concrete amount saved. --}}
                                                            <li>
                                                                <i class="ph-fill ph-seal-percent fw-bold" style="color: var(--bs-primary);"></i>
                                                                <span class="font-size-18 fw-500" style="color: var(--bs-primary);">{{ __('Discount') }}: −{{ $referralPercent }}% — {{ __('save') }} {{ $tier->currency }} {{ number_format((float) $tier->price - $discountedPrice, 0) }}</span>
                                                            </li>
                                                        @endif
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
                                                        @if (!auth()->check())
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
                                                                 (UGX 30,000)" without a second server request.
                                                                 Paying the tier you're already on extends it
                                                                 (same-tier renewal in the activation listener),
                                                                 so the current tier's CTA reads "Renew". --}}
                                                            <form method="POST" action="{{ route('payment.create-order') }}"
                                                                  class="jambo-subscribe-form m-0"
                                                                  data-tier-name="{{ $tier->name }}"
                                                                  data-tier-price="{{ $tier->currency }} {{ number_format($discountedPrice ?? (float) $tier->price, 0) }} / {{ $tier->periodLabel() }}"
                                                                  data-tier-amount="{{ (float) $tier->price }}"
                                                                  data-tier-currency="{{ $tier->currency }}"
                                                                  data-tier-period="{{ $tier->periodLabel() }}">
                                                                @csrf
                                                                <input type="hidden" name="subscription_tier_id" value="{{ $tier->id }}">
                                                                <button type="submit" class="btn {{ $isCurrent ? 'btn-outline-primary' : 'btn-primary' }} fw-semibold rounded-3 w-100 jambo-subscribe-btn">
                                                                    <span class="label">{{ $isCurrent ? __('Renew') : __('streamShop.checkout') }}</span>
                                                                    <span class="spinner spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                                                                </button>
                                                            </form>

                                                            @php
                                                                // Full-cover only: the wallet button appears when the
                                                                // referral balance covers the ENTIRE tier price in the
                                                                // platform currency (no partial top-ups).
                                                                $tierCurrency = $tier->currency ?: config('payments.currency', 'UGX');
                                                                $walletCovers = ($referralWalletBalance ?? null) !== null
                                                                    && strcasecmp($tierCurrency, config('payments.currency', 'UGX')) === 0
                                                                    && bccomp((string) $referralWalletBalance, number_format((float) $tier->price, 2, '.', ''), 2) >= 0;
                                                            @endphp
                                                            @if ($walletCovers)
                                                                <form method="POST" action="{{ route('referrals.wallet.subscribe') }}" class="m-0 mt-2"
                                                                      onsubmit="return confirm('{{ __('Pay :price from your referral wallet?', ['price' => $tierCurrency . ' ' . number_format((float) $tier->price, 0)]) }}');">
                                                                    @csrf
                                                                    <input type="hidden" name="tier_slug" value="{{ $tier->slug }}">
                                                                    <button type="submit" class="btn btn-outline-primary fw-semibold rounded-3 w-100">
                                                                        <i class="ph ph-wallet me-1"></i>{{ __('Pay with wallet') }}
                                                                    </button>
                                                                </form>
                                                            @endif
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="col-12 text-center py-5 text-muted">
                    No subscription plans are active right now. Please check back soon.
                </div>
            @endif

            @guest
                @if ($freeTier)
                    {{-- Guests can still start on Free — signup pre-subscribes it. --}}
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mt-2 p-3 rounded-3 jambo-strip">
                        <div class="d-flex align-items-center gap-3">
                            <i class="ph ph-user-plus" style="color: var(--bs-primary); font-size: 1.75rem;"></i>
                            <div>
                                <div class="fw-semibold">{{ $freeTier->name }} — {{ $freeTier->currency }} 0</div>
                                @if ($freeFeatures)
                                    <div class="small text-muted">{{ $freeFeatures }}</div>
                                @endif
                            </div>
                        </div>
                        <a href="{{ route('register') }}" class="btn btn-outline-primary fw-semibold rounded-3">
                            {{ __('streamShop.get_started') }}
                        </a>
                    </div>
                @endif
            @endguest
        </div>
    </div>

    <style>
        .jambo-strip {
            background: rgba(255, 255, 255, .04);
            border: 1px solid rgba(255, 255, 255, .1);
        }

        .jambo-period-tabs-scroll {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }

        .jambo-period-tabs-scroll::-webkit-scrollbar { display: none; }

        .jambo-period-tabs .nav-link {
            color: var(--bs-body-color);
            background: rgba(255, 255, 255, .06);
            border-radius: 2rem;
            padding: .45rem 1.5rem;
            font-weight: 500;
            white-space: nowrap;
        }

        .jambo-period-tabs .nav-link.active {
            background: var(--bs-primary);
            color: #fff;
        }
    </style>

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

    @if ($referralCanApply)
        {{-- Enables the coupon step inside the checkout modal: the
             viewer has no code attached yet and hasn't made their first
             payment, so a code can still change the price. The modal
             reads this at click time — absent config = straight to
             payment. --}}
        <script>
            window.JamboCoupon = {
                percent: {{ (float) $referralPercent }},
                applyUrl: @json(route('referrals.apply-code')),
            };
        </script>
    @endif

    @if ($pulseSlug)
        {{-- Attention pulse + auto-scroll for ?highlight=<slug>. Only
             emits when the query param is present so regular pricing
             visits are unchanged. Three short pulses then settles —
             enough to catch the eye without being a loop animation.
             Honors prefers-reduced-motion by disabling the keyframe. --}}
        <style>
            @keyframes jambo-tier-pulse-kf {
                0%   { box-shadow: 0 0 0 0 rgba(var(--bs-primary-rgb), 0.55); }
                70%  { box-shadow: 0 0 0 16px rgba(var(--bs-primary-rgb), 0); }
                100% { box-shadow: 0 0 0 0 rgba(var(--bs-primary-rgb), 0); }
            }

            .jambo-tier-pulse {
                border-radius: 12px;
                animation: jambo-tier-pulse-kf 1.4s ease-out 3;
            }

            @media (prefers-reduced-motion: reduce) {
                .jambo-tier-pulse { animation: none; }
            }
        </style>

        <script>
            (function () {
                var target = document.getElementById('tier-{{ $pulseSlug }}');
                if (!target) return;
                // The server already defaults the tab to the pulsed tier's
                // period; this is a backstop in case the pane isn't active.
                var pane = target.closest('.tab-pane');
                if (pane && !pane.classList.contains('active') && window.bootstrap) {
                    var tabBtn = document.querySelector('[data-bs-target="#' + pane.id + '"]');
                    if (tabBtn) bootstrap.Tab.getOrCreateInstance(tabBtn).show();
                }
                // Defer one frame so layout settles before we measure.
                requestAnimationFrame(function () {
                    target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                });
            })();
        </script>
    @endif
@endsection
