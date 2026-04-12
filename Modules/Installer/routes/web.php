<?php

use Illuminate\Support\Facades\Route;
use Modules\Installer\app\Http\Controllers\InstallController;

/*
|--------------------------------------------------------------------------
| Installer Routes
|--------------------------------------------------------------------------
|
| These routes run BEFORE the app has a database, BEFORE any user exists,
| and BEFORE the auth system is usable. They must not be gated by `auth`
| or by anything else that touches the DB.
|
| EnsureInstalled middleware (registered in the `web` group via
| app/Http/Kernel.php) enforces two rules:
|   - any non-/install request redirects here until installation completes
|   - any /install request redirects to `/` once installation completes
|
*/

Route::prefix('install')->name('install.')->group(function () {
    Route::get('/', [InstallController::class, 'entry'])->name('entry');

    // Step 1 — requirements
    Route::get('requirements', [InstallController::class, 'requirements'])->name('requirements');

    // Step 2 — database
    Route::get('database', [InstallController::class, 'database'])->name('database');
    Route::post('database/validate', [InstallController::class, 'validateDatabase'])->name('database.validate');
    Route::post('database', [InstallController::class, 'storeDatabase'])->name('database.store');

    // Step 3 — app settings
    Route::get('settings', [InstallController::class, 'settings'])->name('settings');
    Route::post('settings', [InstallController::class, 'storeSettings'])->name('settings.store');

    // Step 4 — admin account
    Route::get('admin', [InstallController::class, 'admin'])->name('admin');
    Route::post('admin', [InstallController::class, 'storeAdmin'])->name('admin.store');

    // Step 5 — run (progress page + AJAX executor)
    Route::get('run', [InstallController::class, 'run'])->name('run');
    Route::post('execute/{step}', [InstallController::class, 'executeStep'])
        ->whereNumber('step')
        ->name('execute');

    // Step 6 — complete
    Route::get('complete', [InstallController::class, 'complete'])->name('complete');
});
