<?php

use Illuminate\Support\Facades\Route;
use Modules\Seo\app\Http\Controllers\Public\RobotsController;
use Modules\Seo\app\Http\Controllers\Public\SitemapController;

/*
|--------------------------------------------------------------------------
| SEO Module — Public crawler endpoints
|--------------------------------------------------------------------------
|
| /sitemap.xml is XML — Googlebot pulls it to discover content.
| /robots.txt is plain text — every well-behaved crawler reads it
| first to learn what's off-limits and where the sitemap lives.
|
| The static public/robots.txt has been removed so Laravel's router
| handles the request; otherwise nginx / php-fpm would serve the
| static file before Laravel saw it.
|
*/

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('seo.sitemap');
Route::get('/robots.txt',  [RobotsController::class, 'index'])->name('seo.robots');
