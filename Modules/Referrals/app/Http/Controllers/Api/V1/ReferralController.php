<?php

namespace Modules\Referrals\app\Http\Controllers\Api\V1;

use App\Http\Api\ApiErrorCode;
use App\Http\Api\ApiResponse;
use App\Support\PhoneNumber;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Notifications\app\Events\WithdrawalRequested;
use Modules\Referrals\app\Services\ReferralDashboardService;
use Modules\Referrals\app\Services\ReferralSettings;
use Modules\Referrals\app\Services\ReferralWalletService;
use Modules\Referrals\app\Support\ReferralCodeRules;
use Modules\Wallet\app\Models\LedgerEntry;
use Modules\Wallet\app\Models\WithdrawalRequest;
use Modules\Wallet\app\Services\Ledger;

/**
 * Refer and earn, and the wallet the earnings land in.
 *
 * **Withdrawal is here as of 2026-09-09, and it writes no money logic of its
 * own.** It was read-only until Rio asked for the app's wallet screen to be
 * able to complete the task rather than send a viewer to a browser. What that
 * means in practice is one method that validates a request shape and then
 * calls `ReferralWalletService::requestWithdrawal` — the same entry point the
 * website's own form posts to, which is the same `Payouts::request` behind
 * that, which is where the lock, the open-request guard and the ledger hold
 * all live. A second implementation of a money path is the one thing this file
 * must never grow.
 *
 * **A retry is safe without an idempotency key**, and it is worth saying why
 * rather than adding a second mechanism to guarantee what the first already
 * does: `Payouts::request` takes a row lock and refuses a second open request,
 * so a resubmit over a dropped connection cannot create two withdrawals. It
 * fails with a specific code instead, and the app refetches to show the one
 * that exists.
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
     * The rules are `ReferralCodeRules`, which the website's own save and its
     * live availability check now also use.
     *
     * 🔴 **This endpoint used to be weaker than the website's form.** It
     * checked the shape and both uniqueness columns but NOT the reserved-route
     * list, so the app could set a code of `login`, `movies` or `watch` — names
     * the router owns, because the profile hub lives at `/{username}` — and the
     * resulting referral link would resolve to a real page rather than to the
     * referrer. Fixed by sharing one rule set rather than by copying the
     * missing rule across, so the two cannot drift apart again.
     */
    public function updateCode(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->reachable($user)) {
            return ApiResponse::error(ApiErrorCode::NotFound, 'Refer and earn is not available.');
        }

        $data = $request->validate(['code' => ReferralCodeRules::rulesFor($user)]);

        $user->forceFill(['referral_code' => $data['code']])->save();

        return ApiResponse::ok(['code' => $user->fresh()->referral_code], 'Your referral code is updated.');
    }

    /**
     * Is this code free, while somebody is typing it?
     *
     * The website has had this since the Refer & Earn page was built — a
     * debounced check against `/referrals/check-code` — and the app had no
     * equivalent, so its only way to learn a code was taken was to submit and
     * read a 422. Same service, same answer, so "available" here can never
     * fail on save.
     *
     * Deliberately not a validation failure when the code is unusable: an
     * answer of `available: false` with a reason is the useful response to
     * somebody mid-word, and a 422 per keystroke is not.
     */
    public function checkCode(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->reachable($user)) {
            return ApiResponse::error(ApiErrorCode::NotFound, 'Refer and earn is not available.');
        }

        return ApiResponse::ok(
            ReferralCodeRules::availability($user, (string) $request->input('code', '')),
        );
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

        /*
         * The withdrawal history, which the website has shown all along and
         * this endpoint used to drop.
         *
         * Not decoration: it is the only place a viewer learns that the money
         * missing from their balance is on its way to them rather than gone.
         * The hold is taken the moment a request is made, so a wallet with an
         * open withdrawal reads lower with no explanation unless this is here.
         *
         * Capped at the most recent ten rather than paginated. The website
         * shows the lot, but it is a table beside a form on a desktop; a phone
         * needs the open one and the last few, and the ledger below carries
         * the full history anyway.
         */
        $withdrawals = WithdrawalRequest::query()
            ->where('owner_type', $user->getMorphClass())
            ->where('owner_id', $user->id)
            ->orderByDesc('id')
            ->take(10)
            ->get();

        return ApiResponse::ok([
            'currency' => config('payments.currency', 'UGX'),
            'balance' => $ledger->balanceFor($user),
            'min_withdrawal' => ReferralSettings::minWithdrawal(),
            /*
             * Computed from the same OPEN_STATUSES the service guards on, so
             * the button the app draws and the rule the server enforces cannot
             * disagree. Deriving it in the app from the list above would be a
             * second opinion about what "open" means.
             */
            'has_open_withdrawal' => $withdrawals->contains(fn (WithdrawalRequest $w) => $w->isOpen()),
            'withdrawals' => $withdrawals->map(fn (WithdrawalRequest $w) => [
                'id' => $w->id,
                'amount' => $w->amount,
                'currency' => $w->currency,
                'status' => $w->status,
                'reference' => $w->transaction_reference,
                // The reason a rejection was refused. Withheld on every other
                // status: it is an internal note, and only its own row's owner
                // has any business reading it.
                'rejection_reason' => $w->status === WithdrawalRequest::STATUS_REJECTED
                    ? $w->rejection_reason
                    : null,
                'requested_at' => optional($w->requested_at)->toIso8601String(),
            ])->values(),
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
     * Request a cash withdrawal to a mobile-money number.
     *
     * **Every rule this enforces is enforced somewhere else.** The minimum
     * lives in `ReferralWalletService`, and the row lock, the one-open-request
     * guard and the balance check live in `Payouts::request` inside a
     * transaction that rolls the request row back when the ledger refuses. The
     * only thing this method owns is the shape of the request and the shape of
     * the answer.
     *
     * The validation below is the website's form validation, character for
     * character, because the two forms must not accept different things. A
     * phone number the browser rejects and the app accepts is a payout that
     * fails at the till.
     *
     * **`RuntimeException` is the service's way of saying no**, and all three
     * of its messages are safe to show a viewer: the minimum, the withdrawal
     * already in progress, and the insufficient balance. They are returned as
     * a validation failure rather than a 500 because they are answers, not
     * faults.
     */
    public function requestWithdrawal(Request $request, ReferralWalletService $wallet): JsonResponse
    {
        $user = $request->user();

        if (! $this->reachable($user)) {
            return ApiResponse::error(ApiErrorCode::NotFound, 'The wallet is not available.');
        }

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'payee_name' => ['required', 'string', 'max:100'],
            'payee_msisdn' => ['required', 'string', 'max:30'],
        ]);

        /*
         * **The one field on this endpoint where a typo costs money.**
         *
         * The website's form checks the shape with a regular expression, which
         * accepts `+000 000 0000` — a string that looks like a phone number and
         * is not one. Mobile money sent to a plausible typo does not bounce; it
         * arrives somewhere else. So this asks whether the number is a real
         * mobile line, not whether it is punctuated like one.
         *
         * A landline is refused for the same reason and it is worth naming,
         * because it is the case a shape check can never catch: `0414230000`
         * is a perfectly valid Ugandan number that cannot receive money.
         *
         * Normalised to E.164 so the row a clerk actions carries one
         * unambiguous number rather than whatever shape it was typed in.
         */
        $msisdn = PhoneNumber::toE164($data['payee_msisdn']);

        if ($msisdn === null || ! PhoneNumber::isMobile($msisdn)) {
            return ApiResponse::error(
                ApiErrorCode::ValidationFailed,
                'That is not a mobile number that can receive money.',
                ['payee_msisdn' => ['Enter a mobile money number like 0742 078 673.']],
            );
        }

        try {
            $withdrawal = $wallet->requestWithdrawal(
                $user,
                (string) $data['amount'],
                trim($data['payee_name']),
                $msisdn,
            );
        } catch (\RuntimeException $e) {
            return ApiResponse::error(ApiErrorCode::ValidationFailed, $e->getMessage());
        }

        // The same event the website's form fires. The notification subscriber
        // fans it out to the clerks who action it, so a withdrawal requested
        // from a phone reaches the same desk as one requested in a browser.
        event(new WithdrawalRequested($withdrawal));

        return ApiResponse::ok([
            'withdrawal' => [
                'id' => $withdrawal->id,
                'amount' => $withdrawal->amount,
                'currency' => $withdrawal->currency,
                'status' => $withdrawal->status,
                'requested_at' => optional($withdrawal->requested_at)->toIso8601String(),
            ],
            // The balance after the hold, so the screen does not have to
            // subtract it and cannot disagree with the ledger about the
            // answer.
            'balance' => app(Ledger::class)->balanceFor($user),
        ], 'Withdrawal requested. You will be notified when it is reviewed.');
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
