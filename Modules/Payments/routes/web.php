<?php

use Illuminate\Support\Facades\Route;
use Modules\Payments\app\Http\Controllers\Admin\PaymentSettingsController;
use Modules\Payments\app\Http\Controllers\PaymentController;

/*
|--------------------------------------------------------------------------
| User-facing payment flow
|--------------------------------------------------------------------------
|
| createOrder needs an authenticated user (we attach user_id to the
| PaymentOrder). callback and complete are NOT auth-gated because the
| user's session cookie is not guaranteed to survive a round trip
| through the gateway's hosted payment page, and `/payment/ipn` is a
| server-to-server webhook — the gateway's IP is the only identity.
|
*/

Route::post('payment/create-order', [PaymentController::class, 'createOrder'])
    ->middleware(['auth'])
    ->name('payment.create-order');

// Polled by the iframe modal on the pricing page. Scoped to the
// current user so one viewer can't observe another's payment status.
Route::get('payment/status/{ref}', [PaymentController::class, 'status'])
    ->middleware(['auth'])
    ->where('ref', '[A-Za-z0-9\-]+')
    ->name('payment.status');

Route::get('payment/callback', [PaymentController::class, 'callback'])
    ->name('payment.callback');

Route::match(['get', 'post'], 'payment/ipn', [PaymentController::class, 'ipn'])
    ->name('payment.ipn');

Route::get('payment/complete', [PaymentController::class, 'complete'])
    ->name('payment.complete');

/*
|--------------------------------------------------------------------------
| Admin: payment settings + reconciliation
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin/payments')
    ->name('admin.payments.')
    ->group(function () {
        Route::get('/', [PaymentSettingsController::class, 'index'])->name('index');
        Route::post('/', [PaymentSettingsController::class, 'update'])->name('update');
        Route::post('register-ipn', [PaymentSettingsController::class, 'registerIpn'])->name('register-ipn');

        // Orders: list + manual create + per-order view + editable
        // fields + reconcile (re-poll gateway) + delete with guards.
        //
        // `orders/create` must be registered before `orders/{order}`
        // so Laravel doesn't treat "create" as a model key.
        Route::get('orders', [PaymentSettingsController::class, 'orders'])->name('orders');
        Route::get('orders/create', [PaymentSettingsController::class, 'createOrderForm'])->name('orders.create');
        Route::post('orders', [PaymentSettingsController::class, 'storeOrder'])->name('orders.store');
        Route::get('orders/{order}', [PaymentSettingsController::class, 'showOrder'])->name('orders.show');
        Route::patch('orders/{order}', [PaymentSettingsController::class, 'updateOrder'])->name('orders.update');
        Route::delete('orders/{order}', [PaymentSettingsController::class, 'destroyOrder'])->name('orders.destroy');
        Route::post('orders/{order}/reconcile', [PaymentSettingsController::class, 'reconcileOrder'])->name('orders.reconcile');
    });
