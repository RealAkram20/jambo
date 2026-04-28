<?php

use Illuminate\Support\Facades\Route;
use Modules\Seo\app\Http\Controllers\Admin\SeoSettingsController;

/*
|--------------------------------------------------------------------------
| SEO Module — Admin Web Routes
|--------------------------------------------------------------------------
|
| Single section: Google Analytics + Search Console + per-page SEO
| defaults, all under /admin/seo, gated by the same role:admin chain
| the rest of admin uses.
|
*/

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin/seo')
    ->name('admin.seo.')
    ->group(function () {
        Route::get('/',         [SeoSettingsController::class, 'index'])->name('index');
        Route::post('/general', [SeoSettingsController::class, 'updateGeneral'])->name('general');
        Route::post('/social',  [SeoSettingsController::class, 'updateSocial'])->name('social');
    });
