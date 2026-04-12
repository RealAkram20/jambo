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
    });
