<?php

use Illuminate\Support\Facades\Route;
use Modules\Content\app\Http\Controllers\Admin\MovieController;

/*
|--------------------------------------------------------------------------
| Content Module — Web Routes
|--------------------------------------------------------------------------
|
| All admin CRUD for movies, shows, episodes, persons, genres,
| categories, tags, and content interactions lives under /admin/* with
| the web + auth + role:admin stack.
|
| Future: a companion routes/public.php will expose the read-only
| frontend queries.
|
*/

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('movies', MovieController::class);
    });
