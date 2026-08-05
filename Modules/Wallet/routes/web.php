<?php

use Illuminate\Support\Facades\Route;
use Modules\Wallet\app\Http\Controllers\Admin\WithdrawalQueueController;

/*
|--------------------------------------------------------------------------
| Wallet — web routes
|--------------------------------------------------------------------------
| The ONE payout queue for every wallet owner (users and partner
| profiles). Clerking is finance-operable, same doctrine as the old
| Monetization queue it replaces.
*/

// The signed-in staff member's own wallet (performance + referral
// earnings on one balance). Every panel role gets one.
Route::middleware(['auth', 'role:admin|finance|super-admin'])
    ->get('admin/wallet', [\Modules\Wallet\app\Http\Controllers\Admin\MyWalletController::class, 'index'])
    ->name('admin.wallet.index');

// Central wallet oversight: every wallet (staff, members, partner
// profiles) with a per-wallet bank-statement view. Read-only — the
// only write paths into the ledger remain the domain flows.
Route::middleware(['auth', 'role:finance|super-admin'])
    ->prefix('admin/wallets')
    ->name('admin.wallet.wallets.')
    ->group(function () {
        Route::get('/', [\Modules\Wallet\app\Http\Controllers\Admin\WalletsController::class, 'index'])->name('index');
        Route::get('{ownerType}/{ownerId}', [\Modules\Wallet\app\Http\Controllers\Admin\WalletsController::class, 'show'])
            ->whereIn('ownerType', ['user', 'partner'])
            ->whereNumber('ownerId')
            ->name('show');
    });

Route::middleware(['auth', 'role:finance|super-admin'])
    ->prefix('admin/wallet/withdrawals')
    ->name('admin.wallet.withdrawals.')
    ->group(function () {
        Route::get('/', [WithdrawalQueueController::class, 'index'])->name('index');
        Route::post('{withdrawal}/approve', [WithdrawalQueueController::class, 'approve'])->name('approve');
        Route::post('{withdrawal}/mark-paid', [WithdrawalQueueController::class, 'markPaid'])->name('mark-paid');
        Route::post('{withdrawal}/reject', [WithdrawalQueueController::class, 'reject'])->name('reject');
    });
