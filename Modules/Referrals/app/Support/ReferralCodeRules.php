<?php

namespace Modules\Referrals\app\Support;

use App\Models\User;
use App\Rules\ReservedUsername;
use Illuminate\Validation\Rule;

/**
 * What makes a referral code acceptable — in one place, because it was in
 * three and they did not agree.
 *
 * A referral code shares a namespace with usernames. The profile hub lives at
 * `/{username}`, so a code is also a URL segment, and that produces three
 * rules that all have to hold together: the shape, uniqueness against BOTH
 * `referral_code` and `username`, and the reserved-route list.
 *
 * 🔴 **The API was missing the third of those.** `ProfileHubController::updateReferralCode`
 * applied `ReservedUsername`; `Api\V1\ReferralController::updateCode` did not.
 * So the mobile app could set a code of `login`, `movies` or `watch` — names
 * the router already owns — and the resulting referral link would resolve to a
 * real page instead of the referrer. The website would have refused the same
 * code. `ReferralCodeController::check` even carries a comment promising it
 * "mirrors updateReferralCode's rules exactly so available here never fails on
 * save", which was true of the web save and not of the API's.
 *
 * Both save paths and both availability checks now come here, so the four
 * cannot drift again. Same reasoning as `PlaybackAuthorizer` in 1.8.22 and
 * `WatchlistPlayResolver` today.
 */
class ReferralCodeRules
{
    /** The shape, kept as one string so the rule and the check cannot differ. */
    public const PATTERN = '/^[a-zA-Z0-9_.\-]+$/';

    public const MIN = 3;
    public const MAX = 50;

    /**
     * Laravel validation rules for saving a code.
     *
     * `$user` is excluded from both uniqueness checks so re-saving the code
     * you already own is not an error.
     *
     * @return array<int, mixed>
     */
    public static function rulesFor(User $user): array
    {
        return [
            'required',
            'string',
            'min:' . self::MIN,
            'max:' . self::MAX,
            'regex:' . self::PATTERN,
            new ReservedUsername(),
            Rule::unique('users', 'referral_code')->ignore($user->id),
            // A code that is somebody ELSE's username would let this account
            // impersonate their referral link.
            function (string $attribute, mixed $value, callable $fail) use ($user): void {
                if (User::where('username', $value)->where('id', '!=', $user->id)->exists()) {
                    $fail('That referral code is already taken.');
                }
            },
        ];
    }

    /**
     * The same answer, phrased for a live availability check.
     *
     * Returns the pair both check endpoints send. It must never say available
     * for something `rulesFor()` would reject on save — that is the whole
     * point of the two living in one class, and it is what the website's own
     * comment promised before this existed.
     *
     * @return array{available: bool, message: string}
     */
    public static function availability(User $user, string $code): array
    {
        $code = trim($code);
        $length = strlen($code);

        if ($length < self::MIN || $length > self::MAX || preg_match(self::PATTERN, $code) !== 1) {
            return [
                'available' => false,
                'message' => __('Use 3–50 letters, numbers, dots, dashes or underscores.'),
            ];
        }

        // Re-checking the code you already own is available, not taken.
        if ($user->referral_code !== null && strcasecmp($user->referral_code, $code) === 0) {
            return ['available' => true, 'message' => __('This is your current code.')];
        }

        $reserved = false;
        (new ReservedUsername())->validate('code', $code, function () use (&$reserved): void {
            $reserved = true;
        });

        $taken = User::query()
            ->where('id', '!=', $user->id)
            ->where(function ($query) use ($code): void {
                $query->where('referral_code', $code)->orWhere('username', $code);
            })
            ->exists();

        if ($reserved || $taken) {
            return ['available' => false, 'message' => __('That referral code is already taken.')];
        }

        return ['available' => true, 'message' => __('Available')];
    }
}
