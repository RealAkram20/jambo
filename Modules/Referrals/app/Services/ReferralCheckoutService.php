<?php

namespace Modules\Referrals\app\Services;

use App\Models\User;
use Modules\Payments\app\Models\PaymentOrder;
use Modules\Referrals\app\Models\Referral;

/**
 * Computes the referral discount for a buyer's FIRST completed payment.
 * The block this returns is written into payment_orders.metadata by
 * createOrder() (and only by createOrder — client-supplied copies are
 * stripped), then trusted by the activation backstop and the completion
 * listener as the terms shown at purchase time.
 */
class ReferralCheckoutService
{
    public function __construct(private ReferralAttributionService $attribution)
    {
    }

    /**
     * The buyer's pending attribution: an existing row, or one created
     * lazily from the referral cookie (covers users who registered
     * before the program went live, then clicked a link).
     */
    public function resolveAttribution(User $user, ?string $cookieCode): ?Referral
    {
        $referral = Referral::where('referred_user_id', $user->id)->first();

        if (!$referral && $cookieCode) {
            $code = trim($cookieCode);
            if ($code !== '' && preg_match('/^[a-zA-Z0-9_.\-]+$/', $code) === 1) {
                $referral = $this->attribution->attribute($user, $code, Referral::SOURCE_COOKIE);
            }
        }

        if (!$referral || $referral->status !== Referral::STATUS_PENDING) {
            return null;
        }

        return $referral->referrer_id === $user->id ? null : $referral;
    }

    private function hasCompletedPayment(User $user): bool
    {
        return PaymentOrder::where('user_id', $user->id)
            ->where('status', PaymentOrder::STATUS_COMPLETED)
            ->exists();
    }

    /**
     * Discount an order amount. Null when the buyer doesn't qualify
     * (program off, no attribution, or not their first payment) or the
     * discount rounds to nothing.
     *
     * @return array|null the metadata block for payment_orders.metadata['referral']
     */
    public function apply(User $user, string $amount, string $currency, ?string $cookieCode): ?array
    {
        if (!ReferralSettings::active() || $this->hasCompletedPayment($user)) {
            return null;
        }

        $referral = $this->resolveAttribution($user, $cookieCode);
        if (!$referral) {
            return null;
        }

        $snapshot = ReferralSettings::snapshotForOrder();
        $discount = bcdiv(bcmul($amount, $snapshot['discount_percent'], 4), '100', 2);
        $final = bcsub($amount, $discount, 2);

        // A zero (or negative) total can't be charged by the gateway —
        // settings cap the percent at 99, this guards degenerate amounts.
        if (bccomp($discount, '0', 2) <= 0 || bccomp($final, '0', 2) <= 0) {
            return null;
        }

        // One live discounted order at a time: a buyer who abandoned an
        // earlier discounted checkout gets the discount on THIS order and
        // the stale pending one is cancelled locally, so both can't be
        // paid at the discounted price. (processPaymentResult would still
        // honour a cancelled row the gateway reports as paid — this
        // closes the normal double-checkout window, not that race.)
        PaymentOrder::where('user_id', $user->id)
            ->where('status', PaymentOrder::STATUS_PENDING)
            ->whereNotNull('metadata->referral')
            ->update(['status' => PaymentOrder::STATUS_CANCELLED]);

        return [
            'referrer_id' => $referral->referrer_id,
            'referral_id' => $referral->id,
            'code' => $referral->code_used,
            'source' => $referral->source,
            'discount_percent' => $snapshot['discount_percent'],
            'reward_percent' => $snapshot['reward_percent'],
            'original_amount' => $amount,
            'discount_amount' => $discount,
            'final_amount' => $final,
            'currency' => $currency,
        ];
    }

    /**
     * What the pricing page needs to render the offer state for a
     * logged-in viewer.
     */
    public function previewContext(User $user, ?string $cookieCode): array
    {
        if (!ReferralSettings::active()) {
            return ['active' => false, 'eligible' => false, 'can_apply_code' => false, 'discount_percent' => '0'];
        }

        $hasPaid = $this->hasCompletedPayment($user);
        $referral = $hasPaid ? null : $this->resolveAttribution($user, $cookieCode);

        return [
            'active' => true,
            'eligible' => $referral !== null,
            'can_apply_code' => !$hasPaid && $referral === null,
            'discount_percent' => ReferralSettings::discountPercent(),
        ];
    }
}
