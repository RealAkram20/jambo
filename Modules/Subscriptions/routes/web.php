<?php

use Illuminate\Support\Facades\Route;
use Modules\Subscriptions\app\Http\Controllers\Admin\SubscriptionTierController;

/*
|--------------------------------------------------------------------------
| Subscriptions Module — Web Routes
|--------------------------------------------------------------------------
|
| Admin CRUD for subscription tiers. The public pricing page is served
| by the Frontend module (/pricing-page) which reads the same tiers.
|
*/

// Subscription tier CRUD = pricing controls. Same role gate as the
// rest of the payments / pricing surface so a content admin can't
// edit tier amounts behind the operator's back.
Route::middleware(['auth', 'role:admin', 'role:finance|super-admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('subscription-tiers', SubscriptionTierController::class)
            ->except(['show']);
    });
