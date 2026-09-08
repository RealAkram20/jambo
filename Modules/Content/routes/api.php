<?php

use Illuminate\Support\Facades\Route;
use Modules\Content\app\Http\Controllers\Api\V1\CatalogueController;
use Modules\Content\app\Http\Controllers\Api\V1\TaxonomyController;

/*
|--------------------------------------------------------------------------
| Content API routes
|--------------------------------------------------------------------------
|
| Browsing for the mobile and TV app. Public, like the website's own
| catalogue pages: the gate is on playback, not on looking. The resources
| behind these are allow-listed, so no video URL can leave through a listing.
|
| The scaffold stub that returned $request->user() from /api/v1/content was
| removed on 2026-09-08.
|
*/

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('movies', [CatalogueController::class, 'movies'])->name('movies.index');
    Route::get('movies/{slug}', [CatalogueController::class, 'movie'])->name('movies.show');

    Route::get('series', [CatalogueController::class, 'series'])->name('series.index');
    Route::get('series/{slug}', [CatalogueController::class, 'show'])->name('series.show');

    Route::get('episodes/{id}', [CatalogueController::class, 'episode'])
        ->where('id', '[0-9]+')
        ->name('episodes.show');

    Route::get('search', [CatalogueController::class, 'search'])->name('search');

    // The screens the home screen's genre, VJ and personality rails tap
    // through to. Public, like the website's archive pages.
    Route::get('genres', [TaxonomyController::class, 'genres'])->name('genres.index');
    Route::get('genres/{slug}', [TaxonomyController::class, 'genre'])->name('genres.show');

    Route::get('categories', [TaxonomyController::class, 'categories'])->name('categories.index');
    Route::get('categories/{slug}', [TaxonomyController::class, 'category'])->name('categories.show');

    Route::get('vjs', [TaxonomyController::class, 'vjs'])->name('vjs.index');
    Route::get('vjs/{slug}', [TaxonomyController::class, 'vj'])->name('vjs.show');

    Route::get('cast/{slug}', [TaxonomyController::class, 'person'])->name('cast.show');
});
