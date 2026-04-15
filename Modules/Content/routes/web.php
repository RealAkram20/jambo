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

        // Moderation: ratings, reviews, comments (listing via DashboardController template pages)
        Route::delete('ratings/{rating}', [RatingController::class, 'destroy'])->name('ratings.destroy');

        Route::patch('reviews/{review}/toggle-published', [ReviewController::class, 'togglePublished'])->name('reviews.toggle-published');
        Route::delete('reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

        Route::patch('comments/{comment}/toggle-approved', [CommentController::class, 'toggleApproved'])->name('comments.toggle-approved');
        Route::delete('comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
    });
