<?php

namespace Modules\Referrals\app\Services;

use App\Models\User;
use Illuminate\Database\QueryException;
use Modules\Referrals\app\Models\Referral;

/**
 * Records "who referred whom". One row per referred user; last-touch
 * wins while the row is still pending, a qualified row never moves.
 */
class ReferralAttributionService
{
    public function findOwner(string $code): ?User
    {
        return User::where('referral_code', $code)->first();
    }

    public function attribute(User $referred, string $code, string $source): ?Referral
    {
        $owner = $this->findOwner($code);

        if (!$owner || $owner->id === $referred->id) {
            return null;
        }

        $existing = Referral::where('referred_user_id', $referred->id)->first();
        if ($existing && $existing->status === Referral::STATUS_QUALIFIED) {
            return null;
        }

        try {
            return Referral::updateOrCreate(
                ['referred_user_id' => $referred->id],
                [
                    'referrer_id' => $owner->id,
                    'code_used' => $code,
                    'source' => $source,
                    'status' => Referral::STATUS_PENDING,
                ],
            );
        } catch (QueryException $e) {
            // Concurrent request created the row between our read and
            // write — the other attribution won, which is fine.
            return Referral::where('referred_user_id', $referred->id)->first();
        }
    }
}
