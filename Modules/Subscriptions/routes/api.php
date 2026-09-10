<?php

use Illuminate\Support\Facades\Route;
use Modules\Subscriptions\app\Http\Controllers\Api\V1\SubscriptionController;

/*
|--------------------------------------------------------------------------
| Subscriptions API routes
|--------------------------------------------------------------------------
|
| Plans, the viewer's current plan, and their payment history.
|
| Read-only, and that is ADR-0004: the Google Play build is consumption-only,
| so it shows a plan and a renewal date and sells nothing. In-app checkout is
| for the direct APK and is NOT here yet - PaymentController::createOrder
| holds real money logic (server-authored price, frozen snapshot, referral
| discount, and a guard against pairing an arbitrary amount with a
| SubscriptionTier payable) and reusing it safely means extracting it into a
| shared service. Money code earns its own slice.
|
| The scaffold stub that returned $request->user() was removed on 2026-09-08.
|
*/

Route::prefix('v1')->name('api.v1.')->group(function () {
    // Public: the website's pricing page is, and the Play build must be able
    // to show what a plan costs even though it cannot sell one.
    Route::get('plans', [SubscriptionController::class, 'plans'])->name('plans');

    Route::middleware(['auth:sanctum', 'device.active'])->group(function () {
        Route::get('subscription', [SubscriptionController::class, 'show'])->name('subscription');
        /*
         * Starting a payment. `throttle:checkout` is below the auth rate and
         * fails closed, because every call mints a PaymentOrder row and a
         * PesaPal order — a loop does not waste CPU, it fills the table
         * finance reconciles against.
         */
        Route::post('subscription/orders', [SubscriptionController::class, 'createOrder'])
            ->middleware('throttle:checkout')
            ->name('subscription.orders.store');
        Route::get('subscription/orders', [SubscriptionController::class, 'orders'])->name('subscription.orders');
        Route::get('subscription/orders/{reference}', [SubscriptionController::class, 'order'])
            ->name('subscription.order');
    });
});
