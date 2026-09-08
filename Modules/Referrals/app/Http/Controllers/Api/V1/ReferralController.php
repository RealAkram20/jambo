<?php

namespace Modules\Referrals\app\Http\Controllers\Api\V1;

use App\Http\Api\ApiErrorCode;
use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Referrals\app\Services\ReferralDashboardService;
use Modules\Referrals\app\Services\ReferralSettings;
use Modules\Wallet\app\Models\LedgerEntry;
use Modules\Wallet\app\Services\Ledger;

/**
 * Refer and earn, and the wallet the earnings land in.
 *
 * Both read-only here. Withdrawal is money leaving the business and it is not
 * something to add at the end of a long session; see the note at the bottom
 * and docs/api/coverage.md.
 *
 * The availability rule is the website's, and it is worth stating because it
 * looks like a bug otherwise: when the referral programme is switched off the
 * screen is a 404 — **except** for a viewer who already has wallet history,
 * whose earned money must never become unreachable because a setting changed.
 */
class ReferralController extends Controller
{
    public function show(Request $request, ReferralDashboardService $dashboard): JsonResponse
    {
        $user = $request->user();

        if (! $this->reachable($user)) {
            return ApiResponse::error(ApiErrorCode::NotFound, 'Refer and earn is not available.');
        }

        // Straight from the service the website's own screen uses, so the two
        // report the same numbers. Key names are re-shaped to the API's
        // snake_case, and nothing is recomputed here - referral money is the
        // service's arithmetic, not this controller's.
        $data = $dashboard->forUser($user);

        return ApiResponse::ok([
            'code' => $data['code'] ?? null,
            'share_url' => $data['link'] ?? null,
            'currency' => $data['currency'] ?? config('payments.currency', 'UGX'),
            'stats' => [
                'balance' => $data['balance'] ?? null,
                'total_earned' => $data['totalEarned'] ?? null,
                'total_referrals' => $data['totalReferrals'] ?? 0,
                'qualified' => $data['qualifiedCount'] ?? 0,
            ],
            'terms' => [
                // What the referrer earns, and what the new viewer is
                // discounted on their first payment.
                'reward_percent' => $data['rewardPercent'] ?? ReferralSettings::rewardPercent(),
                'discount_percent' => $data['discountPercent'] ?? ReferralSettings::discountPercent(),
                'min_withdrawal' => ReferralSettings::minWithdrawal(),
            ],
        ]);
    }

    /**
     * Set a custom referral code.
     *
     * The code doubles as a username-space value, so the same uniqueness rules
     * the profile form applies hold here: it must be free against both columns
     * or somebody's custom code collides with another account's username.
     */
    public function updateCode(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->reachable($user)) {
            return ApiResponse::error(ApiErrorCode::NotFound, 'Refer and earn is not available.');
        }

        $data = $request->validate([
            'code' => [
                'required', 'string', 'min:3', 'max:50',
                'regex:/^[a-zA-Z0-9_.\-]+$/',
                'unique:users,referral_code,' . $user->id,
                'unique:users,username,' . $user->id,
            ],
        ]);

        $user->forceFill(['referral_code' => $data['code']])->save();

        return ApiResponse::ok(['code' => $user->fresh()->referral_code], 'Your referral code is updated.');
    }

    /**
     * The wallet: balance and ledger.
     *
     * Amounts come back exactly as the ledger holds them. This endpoint does
     * not do arithmetic on money — the ledger is the only thing that should.
     */
    public function wallet(Request $request, Ledger $ledger): JsonResponse
    {
        $user = $request->user();

        // id appended as a tiebreaker after whatever order the ledger sets:
        // two entries in the same second must not lose one to the cursor.
        $entries = $ledger->entriesFor($user)->orderByDesc('id')->cursorPaginate(20);

        return ApiResponse::ok([
            'currency' => config('payments.currency', 'UGX'),
            'balance' => $ledger->balanceFor($user),
            'min_withdrawal' => ReferralSettings::minWithdrawal(),
            'entries' => $entries->getCollection()->map(fn (LedgerEntry $entry) => [
                'id' => $entry->id,
                'type' => $entry->type,
                'amount' => $entry->amount,
                // The ledger records the balance after each entry, so a client
                // never has to add money up itself.
                'balance_after' => $entry->balance_after,
                'currency' => $entry->currency,
                'memo' => $entry->memo,
                'created_at' => optional($entry->created_at)->toIso8601String(),
            ])->values(),
            'next_cursor' => $entries->nextCursor()?->encode(),
        ]);
    }

    /**
     * Whether this viewer can see the screen at all.
     *
     * Off means 404 — except for someone with wallet history. Money they
     * already earned must not disappear because an admin flipped a switch.
     */
    private function reachable($user): bool
    {
        if (ReferralSettings::active()) {
            return true;
        }

        return LedgerEntry::query()
            ->where('owner_type', $user->getMorphClass())
            ->where('owner_id', $user->id)
            ->exists();
    }
}
