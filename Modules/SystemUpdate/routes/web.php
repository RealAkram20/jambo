<?php

use Illuminate\Support\Facades\Route;
use Modules\SystemUpdate\app\Http\Controllers\UpdateController;

/*
|--------------------------------------------------------------------------
| SystemUpdate Routes
|--------------------------------------------------------------------------
|
| All update routes are locked behind the stack defined in
| config('systemupdate.middleware') — by default web + auth + role:admin.
|
*/

$middleware = config('systemupdate.middleware', ['web', 'auth', 'role:admin']);

Route::middleware($middleware)
    ->prefix('admin/updates')
    ->name('admin.updates.')
    ->group(function () {
        Route::get('/', [UpdateController::class, 'index'])->name('index');
        Route::post('check', [UpdateController::class, 'check'])->name('check');
        Route::post('run', [UpdateController::class, 'run'])->name('run');

        // Manual rollback to a retained backup. Name is constrained at
        // the route level so a malicious value can't escape the
        // retained-backup root via path traversal; the controller
        // re-validates anyway.
        Route::post('backups/{name}/restore', [UpdateController::class, 'restoreBackup'])
            ->where('name', '[A-Za-z0-9._-]+')
            ->name('backups.restore');
    });
