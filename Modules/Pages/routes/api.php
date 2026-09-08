<?php

use Illuminate\Support\Facades\Route;
use Modules\Pages\app\Http\Controllers\Api\V1\PageController;

/*
|--------------------------------------------------------------------------
| Pages API routes
|--------------------------------------------------------------------------
|
| The CMS pages — About, FAQ, Privacy, Terms. Public, and generic on purpose:
| adding a page in the admin makes it available to the app with no release.
| Google Play requires a subscription app to show its terms and privacy
| policy, and an app that hard-codes them drifts the moment one is edited.
|
*/

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('pages', [PageController::class, 'index'])->name('pages.index');
    Route::get('pages/{slug}', [PageController::class, 'show'])->name('pages.show');
});
