<?php

use Illuminate\Support\Facades\Route;
use Modules\FileManager\app\Http\Controllers\FileManagerController;

/*
|--------------------------------------------------------------------------
| FileManager Module — Web Routes
|--------------------------------------------------------------------------
| Files Gallery (free tier) handles all file operations natively via its
| own UI once the license cache is pre-warmed by _files/js/custom.js.
*/

Route::middleware(['web', 'auth', 'role:admin'])
    ->prefix('admin')
    ->group(function () {
        Route::get('file-manager', [FileManagerController::class, 'index'])
            ->name('admin.file-manager.index');
    });
