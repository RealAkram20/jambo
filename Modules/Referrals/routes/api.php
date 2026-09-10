<?php

use Illuminate\Support\Facades\Route;
use Modules\Referrals\app\Http\Controllers\Api\V1\ReferralController;

/*
|--------------------------------------------------------------------------
| Referrals API routes
|--------------------------------------------------------------------------
|
| Refer and earn, and the wallet the earnings land in.
|
| Withdrawal was deliberately absent here until 2026-09-09, on the grounds
| that it is money leaving the business. Rio's call that day was to build it,
| so the app's wallet screen can finish the task rather than send a viewer to
| a browser. It writes no money logic of its own: the controller calls
| ReferralWalletService::requestWithdrawal, which is the same entry point the
| website's own form posts to. See docs/api/coverage.md.
|
*/

Route::prefix('v1')
    ->name('api.v1.')
    ->middleware(['auth:sanctum', 'device.active'])
    ->group(function () {
        Route::get('referrals', [ReferralController::class, 'show'])->name('referrals.show');
        Route::put('referrals/code', [ReferralController::class, 'updateCode'])->name('referrals.code');
        // Live availability while a code is being typed. POST rather than GET:
        // the code is user input in a body, and a GET would put every
        // keystroke in an access log.
        Route::post('referrals/code/check', [ReferralController::class, 'checkCode'])
            ->name('referrals.code.check');
        Route::get('wallet', [ReferralController::class, 'wallet'])->name('wallet');
        // Plural and POST-to-collection: a withdrawal is a resource being
        // created, and the one that comes back is the one that was made.
        Route::post('wallet/withdrawals', [ReferralController::class, 'requestWithdrawal'])
            ->name('wallet.withdrawals.store');
    });
