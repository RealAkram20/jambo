<?php

namespace Modules\Referrals\app\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Payments\app\Models\PaymentOrder;
use Modules\Subscriptions\app\Models\SubscriptionTier;
use Modules\Wallet\app\Models\LedgerEntry;
use Modules\Wallet\app\Models\WithdrawalRequest;
use Modules\Wallet\app\Services\Ledger;
use Modules\Wallet\app\Services\Payouts;
use RuntimeException;

/**
 * Spending and cashing out a USER's universal wallet from the
 * Refer & Earn surface. (Partners' new referral rewards land on their
 * partner-profile wallet and ride the Creator Studio flow instead.)
 *
 * Wallet actions deliberately do NOT check ReferralSettings::active() —
 * earned money stays usable even when the program is switched off.
 */
class ReferralWalletService
{
    public function __construct(private Ledger $ledger, private Payouts $payouts)
    {
    }

    /**
     * Buy a subscription tier entirely from the wallet balance (full
     * cover only — no partial top-ups). Creates a completed 'wallet'
     * PaymentOrder and fires payment.completed so the normal activation
     * listener grants the tier. Throws RuntimeException with a
     * user-presentable message when the purchase can't proceed.
     */
    public function spendOnTier(User $user, SubscriptionTier $tier): PaymentOrder
    {
        $price = number_format((float) $tier->price, 2, '.', '');
        $currency = $tier->currency ?: config('payments.currency', 'UGX');

        if (!$tier->is_active || bccomp($price, '0', 2) <= 0) {
            throw new RuntimeException('This plan cannot be purchased with wallet balance.');
        }

        $order = DB::transaction(function () use ($user, $tier, $price, $currency) {
            $order = PaymentOrder::create([
                'user_id' => $user->id,
                'payable_type' => SubscriptionTier::class,
                'payable_id' => $tier->id,
                'merchant_reference' => 'JMB-WALLET-' . strtoupper(uniqid()),
                'amount' => $price,
                'currency' => $currency,
                'status' => PaymentOrder::STATUS_COMPLETED,
                'payment_gateway' => 'referral-wallet',
                'metadata' => [
                    'tier_slug' => $tier->slug,
                    'tier_name' => $tier->name,
                    'billing_period' => $tier->billing_period,
                    'paid_with' => 'referral_wallet',
                ],
            ]);

            // Debits the full price; throws (rolling the order back) when
            // the per-currency balance can't cover it.
            $this->ledger->append(
                owner: $user,
                type: LedgerEntry::TYPE_SPEND,
                amount: bcmul($price, '-1', 2),
                currency: $currency,
                reference: $order,
                memo: 'Subscription paid with wallet: ' . $tier->name,
            );

            return $order;
        });

        // Same signal a gateway payment sends — the Subscriptions
        // activation listener and receipts ride on it.
        event('payment.completed', [$order, 'referral-wallet']);

        return $order;
    }

    /**
     * Open a withdrawal request against the user's wallet. Enforces the
     * super-admin minimum; the universal Payouts service owns the money
     * mechanics (one open request, hold, overdraw). Throws
     * RuntimeException with a user-presentable message on refusal.
     */
    public function requestWithdrawal(User $user, string $amount, string $payeeName, string $payeeMsisdn): WithdrawalRequest
    {
        $amount = number_format((float) $amount, 2, '.', '');
        $currency = config('payments.currency', 'UGX');
        $min = ReferralSettings::minWithdrawal();

        if (bccomp($amount, $min, 2) < 0) {
            throw new RuntimeException(
                'Minimum withdrawal is ' . $currency . ' ' . number_format((float) $min, 0) . '.'
            );
        }

        return $this->payouts->request(
            owner: $user,
            amount: $amount,
            payeeName: $payeeName,
            payeeMsisdn: $payeeMsisdn,
            requestedBy: $user->id,
        );
    }
}
