<?php

use Illuminate\Support\Facades\Route;
use Modules\Content\app\Http\Controllers\Api\V1\CatalogueController;
use Modules\Content\app\Http\Controllers\Api\V1\CommentController;
use Modules\Content\app\Http\Controllers\Api\V1\ReviewController;
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

    // Type-ahead. Thinner and shorter than search: a viewer sends this on
    // every keystroke over a mobile connection.
    Route::get('search/suggest', [CatalogueController::class, 'suggest'])->name('search.suggest');

    // The screens the home screen's genre, VJ and personality rails tap
    // through to. Public, like the website's archive pages.
    Route::get('genres', [TaxonomyController::class, 'genres'])->name('genres.index');
    Route::get('genres/{slug}', [TaxonomyController::class, 'genre'])->name('genres.show');

    Route::get('categories', [TaxonomyController::class, 'categories'])->name('categories.index');
    Route::get('categories/{slug}', [TaxonomyController::class, 'category'])->name('categories.show');

    Route::get('vjs', [TaxonomyController::class, 'vjs'])->name('vjs.index');
    Route::get('vjs/{slug}', [TaxonomyController::class, 'vj'])->name('vjs.show');

    Route::get('tags', [TaxonomyController::class, 'tags'])->name('tags.index');
    Route::get('tags/{slug}', [TaxonomyController::class, 'tag'])->name('tags.show');

    Route::get('cast', [TaxonomyController::class, 'people'])->name('cast.index');
    Route::get('cast/{slug}', [TaxonomyController::class, 'person'])->name('cast.show');

    // Reviews and comments: reading is public, exactly as on the website,
    // and writing needs a signed-in viewer.
    //
    // The path is the title's own catalogue path plus /reviews, so the app
    // does not learn a second vocabulary for the same thing. Reviews attach
    // to a movie or a series and never to an episode - that is the morph the
    // table was built with.
    // api.viewer, not auth:sanctum: reading is public, but a signed-in app
    // must still get `mine` on a review thread and `is_mine` on a comment.
    // A public route resolves no bearer token on its own, so without this
    // those fields are silently null for a viewer who IS signed in - the
    // same trap /home hit.
    Route::middleware('api.viewer')->group(function () {
        Route::get('{type}/{slug}/reviews', [ReviewController::class, 'index'])
            ->where('type', 'movies|series')
            ->name('reviews.index');

        Route::get('episodes/{id}/comments', [CommentController::class, 'index'])
            ->where('id', '[0-9]+')
            ->name('comments.index');
    });

    Route::middleware(['auth:sanctum', 'device.active'])->group(function () {
        // updateOrCreate, so posting twice edits rather than duplicating -
        // which also makes it safe to retry on a connection that drops.
        Route::post('{type}/{slug}/reviews', [ReviewController::class, 'store'])
            ->where('type', 'movies|series')
            ->name('reviews.store');
        Route::delete('{type}/{slug}/reviews', [ReviewController::class, 'destroy'])
            ->where('type', 'movies|series')
            ->name('reviews.destroy');

        Route::post('episodes/{id}/comments', [CommentController::class, 'store'])
            ->where('id', '[0-9]+')
            ->name('comments.store');
        Route::delete('comments/{id}', [CommentController::class, 'destroy'])
            ->where('id', '[0-9]+')
            ->name('comments.destroy');
    });
});
