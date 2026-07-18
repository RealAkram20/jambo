<?php

namespace Modules\Referrals\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Payments\app\Models\PaymentOrder;
use Modules\Referrals\app\Models\Referral;
use Modules\Referrals\app\Services\ReferralAttributionService;
use Modules\Referrals\app\Services\ReferralSettings;

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
        $user = $request->user();
        $code = trim((string) $request->input('code', ''));

        if ($code === '' || strlen($code) < 3 || strlen($code) > 50
            || preg_match('/^[a-zA-Z0-9_.\-]+$/', $code) !== 1
        ) {
            return response()->json([
                'ok' => true,
                'available' => false,
                'message' => __('Use 3–50 letters, numbers, dots, dashes or underscores.'),
            ]);
        }

        $reservedFails = false;
        (new \App\Rules\ReservedUsername())->validate('code', $code, function () use (&$reservedFails) {
            $reservedFails = true;
        });

        $takenAsCode = \App\Models\User::where('referral_code', $code)
            ->where('id', '!=', $user->id)
            ->exists();
        $takenAsUsername = \App\Models\User::where('username', $code)
            ->where('id', '!=', $user->id)
            ->exists();

        if ($reservedFails || $takenAsCode || $takenAsUsername) {
            return response()->json([
                'ok' => true,
                'available' => false,
                'message' => __('That referral code is already taken.'),
            ]);
        }

        return response()->json([
            'ok' => true,
            'available' => true,
            'message' => __('Available'),
        ]);
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
