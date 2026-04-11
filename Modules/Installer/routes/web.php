<?php

use Illuminate\Support\Facades\Route;
use Modules\Installer\app\Http\Controllers\InstallerController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group([], function () {
    Route::resource('installer', InstallerController::class)->names('installer');
});
