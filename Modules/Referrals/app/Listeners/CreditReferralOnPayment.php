<?php

namespace Modules\Referrals\app\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Payments\app\Models\PaymentOrder;
use Modules\Referrals\app\Models\Referral;

/**
 * Credits the referrer when a referred buyer's first payment completes.
 *
 * Listens on the string event `payment.completed` ([$order, $source])
 * alongside the subscription-activation listener. The reward terms come
 * from the metadata snapshot createOrder froze at purchase time — NOT
 * current settings — so a toggled-off program still honours orders that
 * were sold with the offer showing.
 *
 * A referral failure must never break payment processing; the whole
 * handler is fenced.
 */
class CreditReferralOnPayment
{
    public function __construct()
    {
    }

    public function handle(PaymentOrder $order, string $source = 'unknown'): void
    {
        try {
            $meta = is_array($order->metadata ?? null) ? ($order->metadata['referral'] ?? null) : null;

            if (!is_array($meta) || $order->status !== PaymentOrder::STATUS_COMPLETED) {
                return;
            }

            $referrerId = (int) ($meta['referrer_id'] ?? 0);
            $rewardPercent = (string) ($meta['reward_percent'] ?? '');

            if ($referrerId <= 0 || $referrerId === (int) $order->user_id
                || !is_numeric($rewardPercent) || (float) $rewardPercent <= 0 || (float) $rewardPercent > 100
            ) {
                return;
            }

            DB::transaction(function () use ($order, $meta, $referrerId, $rewardPercent) {
                $referral = Referral::where('referred_user_id', $order->user_id)
                    ->lockForUpdate()
                    ->first();

                // First payment only: once qualified by some order, later
                // orders never earn again.
                if ($referral
                    && $referral->status === Referral::STATUS_QUALIFIED
                    && (int) $referral->qualified_payment_order_id !== (int) $order->id
                ) {
                    return;
                }

                $reward = bcdiv(bcmul((string) $order->amount, $rewardPercent, 4), '100', 2);
                if (bccomp($reward, '0', 2) <= 0) {
                    return;
                }

                $credited = $this->creditToDestination($order, $referrerId, $reward, $referral);
                if (!$credited) {
                    // Replay (callback + IPN) — the first pass already
                    // credited and qualified.
                    return;
                }

                if ($referral) {
                    $referral->update([
                        // The order-time snapshot is who got paid. If the
                        // pending row was re-pointed (last-touch) after
                        // this order was created, pin it back so the
                        // qualified row never shows earnings on a
                        // referrer who wasn't credited.
                        'referrer_id' => $referrerId,
                        'code_used' => $meta['code'] ?? $referral->code_used,
                        'source' => $meta['source'] ?? $referral->source,
                        'status' => Referral::STATUS_QUALIFIED,
                        'qualified_payment_order_id' => $order->id,
                        'discount_percent' => $meta['discount_percent'] ?? null,
                        'reward_percent' => $rewardPercent,
                        'original_amount' => $meta['original_amount'] ?? null,
                        'discount_amount' => $meta['discount_amount'] ?? null,
                        'paid_amount' => $order->amount,
                        'reward_amount' => $reward,
                        'currency' => $order->currency,
                        'qualified_at' => now(),
                    ]);
                }

                Log::info('[referrals] reward credited', [
                    'referrer_id' => $referrerId,
                    'referred_user_id' => $order->user_id,
                    'order_id' => $order->id,
                    'reward' => $reward,
                ]);

                DB::afterCommit(function () use ($referrerId, $reward, $order) {
                    try {
                        $referrer = \App\Models\User::find($referrerId);
                        $referrer?->notify(new \Modules\Notifications\app\Notifications\ReferralRewardEarnedNotification(
                            $reward,
                            (string) $order->currency,
                        ));
                    } catch (\Throwable $e) {
                        Log::warning('[referrals] reward notification failed', ['error' => $e->getMessage()]);
                    }
                });
            });
        } catch (\Throwable $e) {
            Log::error('[referrals] credit on payment failed', [
                'order_id' => $order->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Route the reward onto the ONE universal ledger: enrolled partners
     * earn on their partner-profile wallet (Creator Studio flow),
     * everyone else on their user wallet. One table + one entry type
     * means the (order, referral_reward) idempotency key holds even if
     * the referrer enrolls as a partner between callback and IPN.
     */
    private function creditToDestination(PaymentOrder $order, int $referrerId, string $reward, ?Referral $referral): bool
    {
        $alreadyCredited = \Modules\Wallet\app\Models\LedgerEntry::query()
            ->where('reference_type', $order->getMorphClass())
            ->where('reference_id', $order->id)
            ->where('type', \Modules\Wallet\app\Models\LedgerEntry::TYPE_REFERRAL_REWARD)
            ->exists();
        if ($alreadyCredited) {
            return false;
        }

        $owner = $this->enrolledPartnerFor($referrerId)
            ?? \App\Models\User::findOrFail($referrerId);

        $entry = app(\Modules\Wallet\app\Services\Ledger::class)->append(
            owner: $owner,
            type: \Modules\Wallet\app\Models\LedgerEntry::TYPE_REFERRAL_REWARD,
            amount: $reward,
            currency: (string) $order->currency,
            reference: $order,
            memo: 'Referral reward for order '.$order->merchant_reference,
            meta: $referral ? ['referral_id' => $referral->id] : null,
        );

        return $entry !== null;
    }

    private function enrolledPartnerFor(int $userId): ?\Modules\Monetization\app\Models\MonetizationPartner
    {
        if (!class_exists(\Modules\Monetization\app\Models\MonetizationPartner::class)) {
            return null;
        }

        return \Modules\Monetization\app\Models\MonetizationPartner::query()
            ->where('user_id', $userId)
            ->where('status', \Modules\Monetization\app\Models\MonetizationPartner::STATUS_ENROLLED)
            ->first();
    }
}
