<?php

use Illuminate\Support\Facades\Route;
use Modules\Content\app\Http\Controllers\Admin\EpisodeController;
use Modules\Content\app\Http\Controllers\Admin\MovieController;
use Modules\Content\app\Http\Controllers\Admin\PersonController;
use Modules\Content\app\Http\Controllers\Admin\SeasonController;
use Modules\Content\app\Http\Controllers\Admin\ShowController;

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
        Route::resource('shows', ShowController::class)->except(['show']);
        Route::resource('seasons', SeasonController::class)->except(['show']);
        Route::resource('episodes', EpisodeController::class)->except(['show']);
        Route::resource('persons', PersonController::class)->except(['show']);
    });
