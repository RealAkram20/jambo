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

        // Verification-file upload + delete. Lets the admin drop a
        // googleXXX.html / BingSiteAuth.xml / yandex_XXX.html file
        // straight into public/ so Google / Bing / Yandex can fetch
        // it at the root for ownership verification.
        Route::post('/verification-file',                  [SeoSettingsController::class, 'uploadVerificationFile'])->name('verification.upload');
        Route::delete('/verification-file/{filename}',     [SeoSettingsController::class, 'deleteVerificationFile'])
            ->where('filename', '[A-Za-z0-9._-]+')
            ->name('verification.delete');
    });
