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

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('subscription-tiers', SubscriptionTierController::class)
            ->except(['show']);
    });
