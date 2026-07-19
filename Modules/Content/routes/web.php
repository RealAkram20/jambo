<?php

use Illuminate\Support\Facades\Route;
use Modules\Content\app\Http\Controllers\Admin\EpisodeController;
use Modules\Content\app\Http\Controllers\Admin\MovieController;
use Modules\Content\app\Http\Controllers\Admin\PersonController;
use Modules\Content\app\Http\Controllers\Admin\SeasonController;
use Modules\Content\app\Http\Controllers\Admin\ShowController;
use Modules\Content\app\Http\Controllers\Admin\GenreController;
use Modules\Content\app\Http\Controllers\Admin\VjController;
use Modules\Content\app\Http\Controllers\Admin\TagController;
use Modules\Content\app\Http\Controllers\Admin\CategoryController;
use Modules\Content\app\Http\Controllers\Admin\RatingController;
use Modules\Content\app\Http\Controllers\Admin\ReviewController;
use Modules\Content\app\Http\Controllers\Admin\CommentController;
use Modules\Content\app\Http\Controllers\Admin\ContentPreviewController;

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
        // Bulk-delete endpoints. Registered BEFORE the resource() so
        // /admin/movies/bulk doesn't get matched as {movie} = "bulk".
        // Admin-only video preview (302 to the resolved CDN source).
        // No tier gate, no heartbeat, no view/accrual side effects — just
        // "play the file I uploaded so I can check it".
        Route::get('preview/movie/{movie}', [ContentPreviewController::class, 'movie'])
            ->name('content-preview.movie');
        Route::get('preview/episode/{episode}', [ContentPreviewController::class, 'episode'])
            ->name('content-preview.episode');

        Route::delete('movies/bulk', [MovieController::class, 'bulkDestroy'])
            ->name('movies.bulk-destroy');
        Route::delete('series/bulk', [ShowController::class, 'bulkDestroy'])
            ->name('series.bulk-destroy');

        Route::resource('movies', MovieController::class);

        // Series (Shows) — slug-based; Seasons scoped by number under each
        // series; Episodes scoped by number under each season. Produces
        // pretty URLs like /admin/series/my-show/seasons/1/episodes/3/edit.
        Route::resource('series', ShowController::class)
            ->parameters(['series' => 'show'])
            ->except(['show']);

        Route::resource('series.seasons', SeasonController::class)
            ->parameters(['series' => 'show'])
            ->scoped(['season' => 'number'])
            ->except(['show']);

        Route::resource('series.seasons.episodes', EpisodeController::class)
            ->parameters(['series' => 'show'])
            ->scoped(['season' => 'number', 'episode' => 'number'])
            ->except(['show']);

        // Cast-picker AJAX endpoints — registered BEFORE the
        // resource() below so they aren't shadowed by the wildcard
        // /persons/{person} routes the resource declares.
        Route::get('persons/search', [PersonController::class, 'search'])->name('persons.search');
        Route::post('persons/quick', [PersonController::class, 'quickStore'])->name('persons.quick');

        Route::resource('persons', PersonController::class)->except(['show']);

        // Taxonomy CRUD (store, update, destroy only — listing is via DashboardController template pages)
        Route::post('genres', [GenreController::class, 'store'])->name('genres.store');
        Route::put('genres/{genre}', [GenreController::class, 'update'])->name('genres.update');
        Route::delete('genres/{genre}', [GenreController::class, 'destroy'])->name('genres.destroy');

        Route::post('vjs', [VjController::class, 'store'])->name('vjs.store');
        Route::put('vjs/{vj}', [VjController::class, 'update'])->name('vjs.update');
        Route::delete('vjs/{vj}', [VjController::class, 'destroy'])->name('vjs.destroy');

        Route::post('tags', [TagController::class, 'store'])->name('tags.store');
        Route::put('tags/{tag}', [TagController::class, 'update'])->name('tags.update');
        Route::delete('tags/{tag}', [TagController::class, 'destroy'])->name('tags.destroy');

        Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
        // Homepage-rail switch for the admin table (flips visible_home).
        Route::patch('categories/{category}/toggle-home', [CategoryController::class, 'toggleHome'])->name('categories.toggle-home');
        // Drag-and-drop ordering from the admin table: the full id list
        // in display order; sort_order = position. Drives homepage rails.
        Route::patch('categories/reorder', [CategoryController::class, 'reorder'])->name('categories.reorder');

        // Moderation: ratings, reviews, comments (listing via DashboardController template pages)
        Route::patch('ratings/{rating}', [RatingController::class, 'update'])->name('ratings.update');
        Route::delete('ratings/{rating}', [RatingController::class, 'destroy'])->name('ratings.destroy');

        Route::patch('reviews/{review}/toggle-published', [ReviewController::class, 'togglePublished'])->name('reviews.toggle-published');
        Route::delete('reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

        Route::patch('comments/{comment}/toggle-approved', [CommentController::class, 'toggleApproved'])->name('comments.toggle-approved');
        Route::delete('comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
    });
