<?php

use Illuminate\Support\Facades\Route;
use Modules\Referrals\app\Http\Controllers\Admin\ReferralAdminController;
use Modules\Referrals\app\Http\Controllers\Admin\ReferralSettingsController;
use Modules\Referrals\app\Http\Controllers\ReferralCodeController;
use Modules\Referrals\app\Http\Controllers\ReferralWalletController;

/*
|--------------------------------------------------------------------------
| Referrals — web routes
|--------------------------------------------------------------------------
| The user-facing "Refer & Earn" page lives in the profile hub
| (routes/web.php → profile.refer); this file carries the manual
| promo-code endpoint and the admin surface.
*/

Route::middleware(['auth', 'throttle:10,1'])->group(function () {
    Route::post('referrals/apply-code', [ReferralCodeController::class, 'apply'])
        ->name('referrals.apply-code');
});

// Wallet actions — deliberately usable even while the program is off,
// so earned balances are never stranded.
Route::middleware(['auth', 'throttle:5,60'])->group(function () {
    Route::post('referrals/wallet/subscribe', [ReferralWalletController::class, 'subscribe'])
        ->name('referrals.wallet.subscribe');
    Route::post('referrals/wallet/withdraw', [ReferralWalletController::class, 'withdraw'])
        ->name('referrals.wallet.withdraw');
});

// Live availability check while typing a custom code — debounced
// client-side, but rate-limited generously enough for real typing.
Route::middleware(['auth', 'throttle:60,1'])->group(function () {
    Route::post('referrals/check-code', [ReferralCodeController::class, 'check'])
        ->name('referrals.check-code');
});

// The ONE admin-panel Referrals page: every panel user opens it for
// their own Refer & Earn tab; the Payouts tab appears for
// finance|super-admin and the program-wide Overview tab for
// super-admin (enforced inside the controller/view). Only the money
// knobs stay on their own super-admin route.
Route::middleware(['auth', 'role:admin|finance|super-admin'])
    ->get('admin/referrals', [ReferralAdminController::class, 'index'])
    ->name('admin.referrals.index');

Route::middleware(['auth', 'role:super-admin'])
    ->prefix('admin/referrals')
    ->name('admin.referrals.')
    ->group(function () {
        Route::get('settings', [ReferralSettingsController::class, 'index'])->name('settings');
        Route::put('settings', [ReferralSettingsController::class, 'update'])->name('settings.update');
    });

// Payout clerking lives on the UNIVERSAL wallet queue now
// (admin/wallet/withdrawals) — this stub keeps old bookmarks and
// stored notification links working.
Route::middleware(['auth', 'role:finance|super-admin'])
    ->get('admin/referrals/withdrawals', fn () => redirect()->route('admin.wallet.withdrawals.index'))
    ->name('admin.referrals.withdrawals.index');
