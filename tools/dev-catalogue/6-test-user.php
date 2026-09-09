<?php

/*
 * The development test account, as Rio named it on 2026-09-09.
 *
 * Local database only. It replaces the `app-slice-check@jambo.local` throwaway
 * slice 2a left behind — one named account with a known password beats a
 * different one per session, because every worklog entry after this can say
 * "signed in as the test user" and mean the same thing.
 *
 * The subscription matters as much as the login: most of this catalogue is
 * `tier_required`, so an account with no plan exercises the refusal path and
 * nothing else.
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

$user = App\Models\User::updateOrCreate(
    ['email' => 'testuser@jambo.test'],
    [
        'username' => 'testuser',
        'first_name' => 'Test',
        'last_name' => 'User',
        'password' => Hash::make('Jambo@2026'),
        // Verified, so the app is not permanently showing a "verify your
        // email" state that has nothing to do with what is being tested.
        'email_verified_at' => now(),
        'referral_code' => 'testuser',
    ],
);

echo "user {$user->id} {$user->email}\n";

// The highest tier available, so nothing in the catalogue is out of reach.
$tier = DB::table('subscription_tiers')->orderByDesc('access_level')->first();
if ($tier === null) {
    echo "no subscription tiers exist; left without a plan\n";
    return;
}

DB::table('user_subscriptions')->updateOrInsert(
    ['user_id' => $user->id, 'subscription_tier_id' => $tier->id],
    [
        'starts_at' => now()->subDay(),
        // A year out, so the account does not quietly expire mid-slice and
        // send somebody hunting for a bug that is really a lapsed plan.
        'ends_at' => now()->addYear(),
        'status' => 'active',
        'auto_renew' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ],
);

echo "subscribed to {$tier->name} (access_level {$tier->access_level}) until " . now()->addYear()->toDateString() . "\n";
