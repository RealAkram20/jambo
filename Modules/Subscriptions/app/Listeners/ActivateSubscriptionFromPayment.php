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

        DB::transaction(function () use ($order, $tier, $source) {
            // Idempotency: if we've already activated this order, stop.
            $existing = UserSubscription::where('payment_order_id', $order->id)->first();
            if ($existing) {
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
