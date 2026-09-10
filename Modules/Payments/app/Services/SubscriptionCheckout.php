<?php

namespace Modules\Payments\app\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Payments\app\Contracts\PaymentGateway;
use Modules\Payments\app\Models\PaymentOrder;
use Modules\Referrals\app\Services\ReferralCheckoutService;
use Modules\Subscriptions\app\Models\SubscriptionTier;

/**
 * Starting a subscription payment, for every surface that can start one.
 *
 * **Extracted from `PaymentController::createOrder` on 2026-09-10, and the
 * extraction is the whole point.** ADR-0004 gives the direct APK a PesaPal
 * checkout; `docs/api/coverage.md` §4 recorded it as deliberately not built
 * because reusing the website's order creation safely meant pulling this out
 * into something both callers share. Rio asked for it after finding he could
 * not subscribe from the app. A second implementation of a money path is the
 * one thing that must not appear, so the website's tier branch now calls this
 * too — there is one way to buy a plan, not two that drift.
 *
 * **Only the tier path lives here, on purpose.** The controller also accepts
 * an arbitrary `amount` + `description` for one-off payments, and that branch
 * stays where it is: it is a web-only legacy call style, and the guard that
 * stops it naming a `SubscriptionTier` as its payable belongs beside it. This
 * service cannot express that attack at all — it takes a tier and reads the
 * price off the tier, so there is no amount to tamper with.
 *
 * Four things it guarantees, each of which was a decision in the original:
 *
 * 1. **The price is the server's.** `amount` and `currency` are copied off the
 *    tier row. A client naming a plan cannot also name what it costs.
 * 2. **The price is frozen at checkout.** `metadata.tier_snapshot` records what
 *    was charged, and the activation backstop validates the paid amount against
 *    that snapshot rather than the live tier — so an admin raising the price
 *    between checkout and confirmation cannot cause a genuinely paid order to
 *    be refused.
 * 3. **The referral discount is computed here, never accepted.** The caller's
 *    metadata cannot carry a `referral` block; the activation backstop and the
 *    referrer-credit listener trust that block, so a client-supplied copy must
 *    never survive into an order.
 * 4. **The order exists before the gateway is called**, in a transaction, so a
 *    gateway that answers slowly or not at all still leaves a row to reconcile
 *    against rather than a payment with no record.
 */
class SubscriptionCheckout
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly ReferralCheckoutService $referrals,
    ) {
    }

    /**
     * Whether a payment can be started at all.
     *
     * Asked separately so a caller can refuse with its own error shape — the
     * website redirects with a flash, the API answers a code — rather than
     * this throwing an exception that each has to translate.
     */
    public function isConfigured(): bool
    {
        return $this->gateway->isConfigured();
    }

    /**
     * An active tier by slug, or null.
     *
     * `is_active` is part of the lookup rather than a check afterwards: a
     * withdrawn plan must be unbuyable, and a caller holding an old slug is
     * exactly how it would otherwise be bought.
     */
    public function tierBySlug(string $slug): ?SubscriptionTier
    {
        return SubscriptionTier::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Create the order and hand back where to send the payer.
     *
     * `$referralCookieCode` is the website's captured cookie and is null from
     * the app, which has no cookie jar. That is not a gap: the service reads
     * the account's own referral state as well, so a viewer referred at signup
     * is credited either way.
     *
     * @return array{order: PaymentOrder, redirect_url: string, merchant_reference: string}
     */
    public function start(
        $user,
        SubscriptionTier $tier,
        ?string $referralCookieCode,
        string $callbackUrl,
        string $cancellationUrl,
    ): array {
        $amount = (float) $tier->price;
        $currency = $tier->currency ?: config('payments.currency', 'UGX');
        $description = "Jambo — {$tier->name}";

        $metadata = [
            'tier_slug' => $tier->slug,
            'tier_name' => $tier->name,
            'billing_period' => $tier->billing_period,
            'tier_snapshot' => [
                'tier_id' => $tier->id,
                'price' => number_format((float) $tier->price, 2, '.', ''),
                'currency' => $currency,
            ],
        ];

        $referralBlock = $this->referrals->apply(
            $user,
            number_format($amount, 2, '.', ''),
            $currency,
            $referralCookieCode,
        );

        if ($referralBlock !== null) {
            $amount = (float) $referralBlock['final_amount'];
            $metadata['referral'] = $referralBlock;
        }

        $merchantReference = sprintf('JAM-%d-%s', $user->id, strtoupper(Str::random(10)));

        $order = DB::transaction(fn () => PaymentOrder::create([
            'user_id' => $user->id,
            'payable_type' => SubscriptionTier::class,
            'payable_id' => $tier->id,
            'merchant_reference' => $merchantReference,
            'amount' => $amount,
            'currency' => $currency,
            'status' => PaymentOrder::STATUS_PENDING,
            'payment_gateway' => $this->gateway->slug(),
            'metadata' => $metadata,
        ]));

        event(new \Modules\Notifications\app\Events\OrderPlaced($order));

        $response = $this->gateway->submitOrder(
            merchantReference: $merchantReference,
            amount: $amount,
            currency: $currency,
            description: $description,
            callbackUrl: $callbackUrl,
            billingAddress: $this->billingAddress($user),
            cancellationUrl: $cancellationUrl,
        );

        $order->update([
            'order_tracking_id' => $response['order_tracking_id'],
            'raw_response' => $response['raw'] ?? null,
        ]);

        return [
            'order' => $order->fresh(),
            'redirect_url' => $response['redirect_url'],
            'merchant_reference' => $merchantReference,
        ];
    }

    /**
     * Mark an order failed after the gateway refused or never answered.
     *
     * Here rather than in each caller so a failed start looks the same in the
     * table whichever surface began it — reconciliation reads the row, not the
     * log line beside it.
     */
    public function markFailed(?PaymentOrder $order): void
    {
        $order?->update(['status' => PaymentOrder::STATUS_FAILED]);
    }

    /**
     * What PesaPal requires about the payer.
     *
     * Uganda is the platform's home market and PesaPal validates the country
     * as ISO 3166-1 alpha-2. It is a setting rather than a literal so opening
     * a second market is a settings change, not a deploy.
     */
    private function billingAddress($user): array
    {
        $first = $user->first_name ?? '';
        $last = $user->last_name ?? '';

        if ($first === '' && $last === '' && ! empty($user->name)) {
            [$first, $last] = array_pad(explode(' ', $user->name, 2), 2, '');
        }

        return [
            'email_address' => $user->email ?? '',
            'first_name' => $first ?: 'Customer',
            'last_name' => $last ?: 'Jambo',
            'country_code' => setting('payments.billing_country_code', 'UG'),
            'phone_number' => $user->phone ?? null,
        ];
    }
}
