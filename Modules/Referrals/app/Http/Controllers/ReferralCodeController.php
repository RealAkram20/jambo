<?php

namespace Modules\Referrals\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Payments\app\Models\PaymentOrder;
use Modules\Referrals\app\Models\Referral;
use Modules\Referrals\app\Services\ReferralAttributionService;
use Modules\Referrals\app\Services\ReferralSettings;
use Modules\Referrals\app\Support\ReferralCodeRules;

/**
 * Manual promo-code entry: lets a logged-in user who arrived without a
 * referral link claim an attribution before their first payment.
 */
class ReferralCodeController extends Controller
{
    /**
     * Live availability check while the user types a custom code on the
     * Refer & Earn page. Mirrors updateReferralCode's rules exactly so
     * "available" here never fails on save.
     */
    public function check(Request $request): JsonResponse
    {
        // The rules moved to ReferralCodeRules so this, the web save, and both
        // API endpoints give one answer. The comment this method used to carry
        // promised it "mirrors updateReferralCode's rules exactly"; that was
        // true here and false of the API, which is why they are shared now
        // rather than restated.
        $answer = ReferralCodeRules::availability($request->user(), (string) $request->input('code', ''));

        return response()->json(['ok' => true] + $answer);
    }

    public function apply(Request $request, ReferralAttributionService $attribution): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9_.\-]+$/'],
        ]);

        $user = $request->user();
        $code = trim($data['code']);

        if (!ReferralSettings::active()) {
            return response()->json(['ok' => false, 'message' => __('Referral program is not available right now.')], 422);
        }

        $owner = $attribution->findOwner($code);
        if (!$owner) {
            return response()->json(['ok' => false, 'message' => __('That referral code was not found.')], 422);
        }

        if ($owner->id === $user->id) {
            return response()->json(['ok' => false, 'message' => __('You cannot use your own referral code.')], 422);
        }

        $hasPaid = PaymentOrder::where('user_id', $user->id)
            ->where('status', PaymentOrder::STATUS_COMPLETED)
            ->exists();
        if ($hasPaid) {
            return response()->json(['ok' => false, 'message' => __('Referral discounts only apply to your first payment.')], 422);
        }

        $existing = Referral::where('referred_user_id', $user->id)->first();
        if ($existing && $existing->status === Referral::STATUS_QUALIFIED) {
            return response()->json(['ok' => false, 'message' => __('A referral has already been applied to your account.')], 422);
        }

        $referral = $attribution->attribute($user, $code, Referral::SOURCE_CODE);
        if (!$referral) {
            return response()->json(['ok' => false, 'message' => __('That referral code could not be applied.')], 422);
        }

        return response()->json([
            'ok' => true,
            'discount_percent' => ReferralSettings::discountPercent(),
        ]);
    }
}
