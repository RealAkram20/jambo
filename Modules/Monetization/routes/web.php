<?php

use Illuminate\Support\Facades\Route;
use Modules\Monetization\app\Http\Controllers\Admin\MonetizationSettingsController;
use Modules\Monetization\app\Http\Controllers\Admin\PartnerAdminController;
use Modules\Monetization\app\Http\Controllers\Admin\StatementAdminController;
use Modules\Monetization\app\Http\Controllers\Admin\TitleSplitController;
use Modules\Monetization\app\Http\Controllers\Admin\WithdrawalAdminController;
use Modules\Monetization\app\Http\Controllers\Partner\PartnerAccountController;
use Modules\Monetization\app\Http\Controllers\Partner\PartnerContentController;
use Modules\Monetization\app\Http\Controllers\Partner\PartnerDashboardController;
use Modules\Monetization\app\Http\Controllers\Partner\PartnerStatementController;
use Modules\Monetization\app\Http\Controllers\Partner\PartnerWalletController;
use Modules\Monetization\app\Http\Controllers\Partner\PartnerWithdrawalController;
use Modules\Monetization\app\Http\Controllers\Partner\PayoutProfileController;

/*
|--------------------------------------------------------------------------
| Monetization Module — Web Routes
|--------------------------------------------------------------------------
|
| Two surfaces:
|
|   /admin/monetization/*  — finance|super-admin back office (settings,
|       partner enrollment, title splits, monthly statements, withdrawal
|       queue). `monetization.admin` additionally honours the
|       monetization.finance_can_view setting so the platform owner can
|       restrict the whole area to super-admins only. Money-SHAPING
|       writes (settings, enrollment, multipliers, payout verification,
|       Close & Credit) are further locked to super-admin; withdrawal
|       operations stay finance-operable — finance is the payout clerk.
|
|   /partner/*  — the partner console (earnings, statements, wallet,
|       withdrawals, payout profile) for users holding the `partner`
|       role. Every controller resolves the partner row from
|       auth()->id() — a partner can never address another partner's
|       data by id.
|
*/

Route::middleware(['auth', 'role:admin', 'role:finance|super-admin', 'monetization.admin'])
    ->prefix('admin/monetization')
    ->name('admin.monetization.')
    ->group(function () {

        // ---- Super-admin-only: knobs that shape money ----
        Route::middleware('role:super-admin')->group(function () {
            Route::get('settings', [MonetizationSettingsController::class, 'index'])->name('settings');
            Route::put('settings', [MonetizationSettingsController::class, 'update'])->name('settings.update');

            Route::get('partners/create', [PartnerAdminController::class, 'create'])->name('partners.create');
            Route::post('partners', [PartnerAdminController::class, 'store'])->name('partners.store');
            Route::get('partners/{partner}/edit', [PartnerAdminController::class, 'edit'])->name('partners.edit');
            Route::put('partners/{partner}', [PartnerAdminController::class, 'update'])->name('partners.update');
            Route::post('partners/{partner}/verify-payout', [PartnerAdminController::class, 'verifyPayout'])->name('partners.verify-payout');

            Route::get('splits', [TitleSplitController::class, 'index'])->name('splits.index');
            Route::get('splits/{type}/{id}', [TitleSplitController::class, 'edit'])
                ->whereIn('type', ['movie', 'show'])->whereNumber('id')->name('splits.edit');
            Route::put('splits/{type}/{id}', [TitleSplitController::class, 'update'])
                ->whereIn('type', ['movie', 'show'])->whereNumber('id')->name('splits.update');

            Route::post('statements/{period}/recompute', [StatementAdminController::class, 'recompute'])->name('statements.recompute');
            Route::post('statements/{period}/close', [StatementAdminController::class, 'close'])->name('statements.close');
        });

        // ---- Finance-operable: read surfaces + payout clerking ----
        Route::get('partners', [PartnerAdminController::class, 'index'])->name('partners.index');
        Route::get('partners/{partner}', [PartnerAdminController::class, 'show'])->name('partners.show');

        Route::get('statements', [StatementAdminController::class, 'index'])->name('statements.index');
        Route::get('statements/{period}', [StatementAdminController::class, 'show'])->name('statements.show');

        Route::get('withdrawals', [WithdrawalAdminController::class, 'index'])->name('withdrawals.index');
        Route::get('withdrawals/{withdrawal}', [WithdrawalAdminController::class, 'show'])->name('withdrawals.show');
        Route::post('withdrawals/{withdrawal}/approve', [WithdrawalAdminController::class, 'approve'])->name('withdrawals.approve');
        Route::post('withdrawals/{withdrawal}/mark-paid', [WithdrawalAdminController::class, 'markPaid'])->name('withdrawals.mark-paid');
        Route::post('withdrawals/{withdrawal}/reject', [WithdrawalAdminController::class, 'reject'])->name('withdrawals.reject');
    });

Route::middleware(['auth', 'role:partner'])
    ->prefix('partner')
    ->name('partner.')
    ->group(function () {
        Route::get('/', [PartnerDashboardController::class, 'index'])->name('dashboard');
        Route::get('charts/{chart}', [PartnerDashboardController::class, 'chartData'])
            ->whereIn('chart', ['earnings', 'minutes'])->name('charts');

        Route::get('statements', [PartnerStatementController::class, 'index'])->name('statements.index');
        Route::get('statements/{period}', [PartnerStatementController::class, 'show'])->name('statements.show');
        Route::get('titles', [PartnerStatementController::class, 'titles'])->name('titles');

        // Content self-service — additionally gated inside the
        // controller by the super-admin-granted can_edit_content /
        // can_delete_content flags and split ownership.
        Route::get('content/{type}/{id}/edit', [PartnerContentController::class, 'edit'])
            ->whereIn('type', ['movie', 'show'])->whereNumber('id')->name('content.edit');
        Route::put('content/{type}/{id}', [PartnerContentController::class, 'update'])
            ->whereIn('type', ['movie', 'show'])->whereNumber('id')->name('content.update');
        Route::delete('content/{type}/{id}', [PartnerContentController::class, 'destroy'])
            ->whereIn('type', ['movie', 'show'])->whereNumber('id')->name('content.destroy');

        Route::get('wallet', [PartnerWalletController::class, 'index'])->name('wallet');

        Route::get('withdrawals', [PartnerWithdrawalController::class, 'index'])->name('withdrawals.index');
        Route::post('withdrawals', [PartnerWithdrawalController::class, 'store'])
            ->middleware('throttle:5,60')->name('withdrawals.store');

        Route::get('payout-profile', [PayoutProfileController::class, 'edit'])->name('payout-profile');
        Route::put('payout-profile', [PayoutProfileController::class, 'update'])
            ->middleware('throttle:5,60')->name('payout-profile.update');

        // Account tabs — the profile-hub pages addressed flat inside
        // the studio (/partner/profile, /partner/security, …) so
        // partners have ONE dashboard URL space. Thin delegates to
        // ProfileHubController (see PartnerAccountController); a
        // partner hitting the old /{username} GET URLs is redirected
        // here by resolveOwn(). Mutating account endpoints stay on
        // their /{username} routes.
        //
        // Membership / Watchlist / Billing are consumer features and
        // deliberately have NO studio surface — a partner's GETs on
        // those hub URLs land on the studio overview instead.
        Route::get('profile', [PartnerAccountController::class, 'profile'])->name('profile');
        Route::get('security', [PartnerAccountController::class, 'security'])->name('security');
        Route::get('devices', [PartnerAccountController::class, 'devices'])->name('devices');
        Route::get('notifications', [PartnerAccountController::class, 'notifications'])->name('notifications');
    });
