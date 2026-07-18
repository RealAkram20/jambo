<?php

namespace Modules\Subscriptions\app\Listeners;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Payments\app\Models\PaymentOrder;
use Modules\Subscriptions\app\Models\SubscriptionTier;
use Modules\Subscriptions\app\Models\UserSubscription;

/**
 * Listens on the Payments module's `payment.completed` event. When the
 * completed order was a subscription-tier purchase, this creates (or
 * renews) a UserSubscription row for the buyer.
 *
 * Other payable types (rentals, TVOD, etc.) are ignored here — they'll
 * grow their own listeners when those modules ship.
 *
 * Registered in SubscriptionsServiceProvider::boot(). The event payload
 * shape is `[$order, $source]` per PaymentController::dispatchActivation.
 */
class ActivateSubscriptionFromPayment
{
    public function handle(PaymentOrder $order, string $source = 'unknown'): void
    {
        if ($order->payable_type !== SubscriptionTier::class) {
            return;
        }

        $tier = SubscriptionTier::find($order->payable_id);
        if (!$tier) {
            Log::warning('[subscriptions] activation skipped: tier not found', [
                'order_id' => $order->id,
                'payable_id' => $order->payable_id,
            ]);
            return;
        }

        // Price backstop: the pricing-page flow copies amount + currency
        // straight off the tier, so a completed order for this tier must
        // have paid at least the tier's price in its currency. If it
        // didn't, the order was assembled through some path that bypassed
        // that copy — refuse to grant premium rather than honour an
        // under-payment. `price`/`amount` are decimal:2 (string-cast), so
        // compare as floats. Expected currency mirrors createOrder's
        // `$tier->currency ?: config default` fallback so a tier with a
        // blank currency doesn't wrongly reject a legitimate order.
        //
        // Referral exception: createOrder may legitimately charge less
        // than tier price when it applied a referral discount. That block
        // is server-authored only (createOrder strips client copies), so
        // when it's present AND internally consistent — discount computed
        // off the real tier price, arithmetic checks out, order charged
        // exactly the final amount — the floor drops to the discounted
        // amount. Anything inconsistent keeps the full-price floor.
        // Prefer the price frozen at checkout (createOrder's tier_snapshot)
        // over the live tier price. This is the fix for the "paid but no
        // subscription" trap: if an admin raised the price after the buyer
        // checked out, the live price would exceed what they legitimately
        // paid and the order would be refused. The snapshot is what the
        // order was assembled from, so it's the authoritative floor. Fall
        // back to the live tier only for legacy orders with no snapshot.
        $snapshot = is_array($order->metadata ?? null) ? ($order->metadata['tier_snapshot'] ?? null) : null;
        $snapshotValid = is_array($snapshot) && (int) ($snapshot['tier_id'] ?? 0) === (int) $tier->id;

        $basePrice = $snapshotValid ? (float) $snapshot['price'] : (float) $tier->price;
        $expectedCurrency = $snapshotValid
            ? (string) $snapshot['currency']
            : ($tier->currency ?: config('payments.currency', 'UGX'));

        $expectedFloor = $basePrice;
        $referral = is_array($order->metadata ?? null) ? ($order->metadata['referral'] ?? null) : null;
        if (is_array($referral)) {
            $pct = (float) ($referral['discount_percent'] ?? -1);
            $orig = (float) ($referral['original_amount'] ?? -1);
            $disc = (float) ($referral['discount_amount'] ?? -1);
            $final = (float) ($referral['final_amount'] ?? -1);

            $consistent = $pct >= 0 && $pct <= 100
                && abs($orig - $basePrice) < 0.01
                && abs($disc - $orig * $pct / 100) <= 0.011
                && abs($final - ($orig - $disc)) < 0.01
                && abs((float) $order->amount - $final) < 0.01;

            if ($consistent) {
                $expectedFloor = $final;
            } else {
                Log::warning('[subscriptions] referral block inconsistent — enforcing full price', [
                    'order_id' => $order->id,
                    'referral' => $referral,
                    'base_price' => $basePrice,
                ]);
            }
        }

        if ((float) $order->amount < $expectedFloor - 0.005
            || strcasecmp((string) $order->currency, (string) $expectedCurrency) !== 0) {
            // Never silent: the buyer may have paid. Flag the order on its
            // own metadata AND log at error level so a paid-but-unactivated
            // order surfaces for admin reconciliation instead of vanishing.
            // We deliberately do NOT claim the order (no subscription_applied_at),
            // so once an admin corrects the mismatch the event can re-run.
            Log::error('[subscriptions] activation refused: order underpays tier — flagged for review', [
                'order_id' => $order->id,
                'order_amount' => $order->amount,
                'order_currency' => $order->currency,
                'tier_id' => $tier->id,
                'expected_floor' => $expectedFloor,
                'expected_currency' => $expectedCurrency,
                'snapshot_present' => $snapshotValid,
                'source' => $source,
            ]);

            $meta = is_array($order->metadata) ? $order->metadata : [];
            $meta['activation_review'] = [
                'reason' => 'amount_below_expected_floor_or_currency_mismatch',
                'order_amount' => (string) $order->amount,
                'order_currency' => (string) $order->currency,
                'expected_floor' => (string) $expectedFloor,
                'expected_currency' => (string) $expectedCurrency,
                'flagged_at' => Carbon::now()->toIso8601String(),
            ];
            $order->forceFill(['metadata' => $meta])->save();

            return;
        }

        DB::transaction(function () use ($order, $tier, $source) {
            // Idempotency: atomically claim this order. The marker lives on
            // the ORDER (subscription_applied_at), not on the subscription's
            // payment_order_id — a same-tier renewal overwrites that FK,
            // which used to erase the marker and let a replayed
            // payment.completed grant a second free extension. A conditional
            // single-row update is race-safe even if a second dispatch
            // (queued listener, admin reconcile) fires concurrently: only
            // the first update touches a row, the rest see 0 and stop.
            $claimed = PaymentOrder::whereKey($order->id)
                ->whereNull('subscription_applied_at')
                ->update(['subscription_applied_at' => Carbon::now()]);
            if ($claimed === 0) {
                return;
            }

            $duration = $tier->durationInDays();
            $now = Carbon::now();

            $current = UserSubscription::where('user_id', $order->user_id)
                ->where('status', UserSubscription::STATUS_ACTIVE)
                ->where('ends_at', '>', $now)
                ->lockForUpdate()
                ->orderByDesc('ends_at')
                ->first();

            if ($current && $current->subscription_tier_id === $tier->id) {
                // Same-tier renewal — extend from the existing ends_at.
                $startsAt = $current->starts_at ?? $now;
                $endsAt = ($current->ends_at ?? $now)->copy()->addDays($duration);

                $current->update([
                    'ends_at' => $endsAt,
                    'payment_order_id' => $order->id,
                ]);

                Log::info('[subscriptions] renewed via payment', [
                    'user_subscription_id' => $current->id,
                    'tier_id' => $tier->id,
                    'user_id' => $order->user_id,
                    'new_ends_at' => $endsAt->toIso8601String(),
                    'source' => $source,
                ]);
                return;
            }

            if ($current) {
                // Different tier — cancel the old one so the user only has
                // a single active row at a time.
                $current->update([
                    'status' => UserSubscription::STATUS_CANCELLED,
                    'cancelled_at' => $now,
                ]);

                if ($current->user) {
                    event(new \Modules\Notifications\app\Events\SubscriptionCancelled($current->user));
                }
            }

            $sub = UserSubscription::create([
                'user_id' => $order->user_id,
                'subscription_tier_id' => $tier->id,
                'payment_order_id' => $order->id,
                'starts_at' => $now,
                'ends_at' => $now->copy()->addDays($duration),
                'status' => UserSubscription::STATUS_ACTIVE,
                'auto_renew' => false,
            ]);

            if ($sub->user) {
                event(new \Modules\Notifications\app\Events\SubscriptionActivated(
                    $sub->user,
                    $tier->name ?? 'Jambo premium',
                    $sub->ends_at->toDateString(),
                ));
            }

            Log::info('[subscriptions] activated via payment', [
                'user_subscription_id' => $sub->id,
                'tier_id' => $tier->id,
                'user_id' => $order->user_id,
                'ends_at' => $sub->ends_at->toIso8601String(),
                'source' => $source,
            ]);
        });
    }
}
