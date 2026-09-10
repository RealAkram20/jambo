<?php

/*
 * Referrals and referral earnings for the test account. Local database only.
 *
 * WHY THIS STEP EXISTS. The Refer & Earn screen was built from a mockup
 * showing three successful referrals and a reward figure. On a fresh database
 * the test account has none, so the screen renders every number as zero —
 * which is a real and correct state, and not the one that needed checking.
 * `screen`'s rule 6 asks for the populated case at realistic volume, and
 * nothing else in `dev-catalogue` creates a referral.
 *
 * A MIX ON PURPOSE. Some qualified and some still pending, because the screen
 * shows "Subscribed referrals" separately from the total — two figures that
 * are identical on every fixture would hide a mix-up between them.
 *
 * MONEY GOES THROUGH THE LEDGER, never through a raw insert. `Ledger::append`
 * is what the real reward path calls; it serialises the wallet, computes
 * `balance_after` itself and refuses to run outside a transaction. Writing
 * rows directly would produce a balance the ledger disagrees with, which is
 * the one thing a money fixture must not do.
 *
 * Re-running is safe: referrals are keyed on the referred account, and the
 * ledger's own reference check makes a replayed credit a no-op.
 * `JAMBO_REFERRALS_RESET=1` removes them instead.
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Referrals\app\Models\Referral;
use Modules\Referrals\app\Services\ReferralSettings;
use Modules\Wallet\app\Models\LedgerEntry;
use Modules\Wallet\app\Services\Ledger;

$referrer = App\Models\User::where('email', 'testuser@jambo.test')->first();

if ($referrer === null) {
    echo "no test user; run 6-test-user.php first\n";

    return;
}

$currency = config('payments.currency', 'UGX');

/** The five invented friends. Emails are on a .test domain, which is reserved
 *  by RFC 2606 and can never reach a real inbox. */
$friends = [
    ['name' => 'Aisha',  'email' => 'aisha.referral@jambo.test',  'qualified' => true,  'paid' => '30000'],
    ['name' => 'Brian',  'email' => 'brian.referral@jambo.test',  'qualified' => true,  'paid' => '30000'],
    ['name' => 'Carol',  'email' => 'carol.referral@jambo.test',  'qualified' => true,  'paid' => '150000'],
    ['name' => 'Daniel', 'email' => 'daniel.referral@jambo.test', 'qualified' => false, 'paid' => null],
    ['name' => 'Esther', 'email' => 'esther.referral@jambo.test', 'qualified' => false, 'paid' => null],
];

if (getenv('JAMBO_REFERRALS_RESET') === '1') {
    $emails = array_column($friends, 'email');
    $ids = App\Models\User::whereIn('email', $emails)->pluck('id');

    $removedReferrals = Referral::where('referrer_id', $referrer->id)
        ->whereIn('referred_user_id', $ids)->delete();
    $removedEntries = LedgerEntry::where('owner_type', $referrer->getMorphClass())
        ->where('owner_id', $referrer->id)
        ->where('memo', 'like', 'Referral reward%')->delete();
    App\Models\User::whereIn('id', $ids)->delete();

    echo "cleared {$removedReferrals} referrals, {$removedEntries} ledger entries, "
        . count($emails) . " fixture accounts\n";

    return;
}

$rewardPercent = (string) ReferralSettings::rewardPercent();
$discountPercent = (string) ReferralSettings::discountPercent();

foreach ($friends as $index => $friend) {
    $friendUser = App\Models\User::updateOrCreate(
        ['email' => $friend['email']],
        [
            'username' => 'ref' . ($index + 1) . 'testuser',
            'first_name' => $friend['name'],
            'last_name' => 'Referral',
            'password' => Hash::make('Jambo@2026'),
            'email_verified_at' => now(),
        ],
    );

    // The reward is a percentage of what the friend actually paid, which is
    // the programme's own definition — not a figure chosen to look plausible.
    $reward = $friend['qualified']
        ? bcdiv(bcmul($friend['paid'], $rewardPercent, 2), '100', 2)
        : null;

    $referral = Referral::updateOrCreate(
        ['referred_user_id' => $friendUser->id],
        [
            'referrer_id' => $referrer->id,
            'code_used' => $referrer->referral_code,
            'source' => Referral::SOURCE_CODE,
            'status' => $friend['qualified'] ? Referral::STATUS_QUALIFIED : Referral::STATUS_PENDING,
            'discount_percent' => $discountPercent,
            'reward_percent' => $rewardPercent,
            'paid_amount' => $friend['paid'],
            'reward_amount' => $reward,
            'currency' => $currency,
            'qualified_at' => $friend['qualified'] ? now()->subDays(5 - $index) : null,
            'created_at' => now()->subDays(20 - $index * 3),
        ],
    );

    $status = $friend['qualified'] ? 'qualified' : 'pending';
    echo str_pad($friend['name'], 8), ' ', str_pad($status, 10),
        ' reward: ', $reward ?? '—', "\n";

    if ($reward === null) {
        continue;
    }

    // Inside a transaction, and referenced to the referral row, so a re-run
    // credits nothing twice.
    DB::transaction(function () use ($referrer, $reward, $currency, $referral, $friend) {
        $entry = app(Ledger::class)->append(
            owner: $referrer,
            type: 'referral_reward',
            amount: $reward,
            currency: $currency,
            reference: $referral,
            memo: 'Referral reward — ' . $friend['name'],
        );

        echo $entry === null
            ? "  ledger: already credited, skipped\n"
            : "  ledger: credited {$reward}, balance now {$entry->balance_after}\n";
    });
}

echo "\nbalance: ", app(Ledger::class)->balanceFor($referrer), " {$currency}\n";
