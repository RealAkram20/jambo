<?php

use Illuminate\Support\Facades\Route;
use Modules\Referrals\app\Http\Controllers\Api\V1\ReferralController;

/*
|--------------------------------------------------------------------------
| Referrals API routes
|--------------------------------------------------------------------------
|
| Refer and earn, and the wallet the earnings land in. Read-only: withdrawal
| is money leaving the business and is deliberately not exposed here yet.
| See docs/api/coverage.md.
|
*/

Route::prefix('v1')
    ->name('api.v1.')
    ->middleware(['auth:sanctum', 'device.active'])
    ->group(function () {
        Route::get('referrals', [ReferralController::class, 'show'])->name('referrals.show');
        Route::put('referrals/code', [ReferralController::class, 'updateCode'])->name('referrals.code');
        Route::get('wallet', [ReferralController::class, 'wallet'])->name('wallet');
    });
