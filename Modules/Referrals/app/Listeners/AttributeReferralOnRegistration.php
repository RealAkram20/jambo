<?php

namespace Modules\Referrals\app\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Log;
use Modules\Referrals\app\Http\Middleware\CaptureReferralCode;
use Modules\Referrals\app\Services\ReferralAttributionService;
use Modules\Referrals\app\Services\ReferralSettings;

/**
 * Runs synchronously on signup (Registered is fired in-request from
 * RegisteredUserController::store), so the referral cookie that rode
 * along on the request is still readable here.
 *
 * A referral failure must never break registration — the whole body is
 * fenced.
 */
class AttributeReferralOnRegistration
{
    public function __construct(private ReferralAttributionService $attribution)
    {
    }

    public function handle(Registered $event): void
    {
        $user = $event->user;

        if (!$user instanceof User) {
            return;
        }

        // Every new account starts with their username as their code.
        // Fenced separately from the attribution below: a collision with
        // someone's custom code (unique constraint) must not also cost
        // this user the referral cookie they arrived with.
        try {
            if (empty($user->referral_code) && !empty($user->username)) {
                $user->referral_code = $user->username;
                $user->save();
            }
        } catch (\Throwable $e) {
            Log::warning('[referrals] default referral code failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            if (!ReferralSettings::active()) {
                return;
            }

            $code = trim((string) request()->cookie(CaptureReferralCode::COOKIE_NAME, ''));
            if ($code !== '') {
                $this->attribution->attribute($user, $code, \Modules\Referrals\app\Models\Referral::SOURCE_COOKIE);
            }
        } catch (\Throwable $e) {
            Log::warning('[referrals] attribution on registration failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
