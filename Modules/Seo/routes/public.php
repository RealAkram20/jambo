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

// Explicit ->withoutMiddleware() on each route as belt-and-suspenders.
// The 1.5.3 attempt to use Route::middleware([]) and the 1.5.4 attempt
// to use Route::middleware('api') in the RouteServiceProvider both
// still produced responses with session cookies set + a /login
// redirect — meaning the web stack was being applied somewhere we
// can't see (maybe a global wrapper from nwidart/laravel-modules,
// maybe a controller-level concern, maybe LiteSpeed-level rewrite).
//
// Stripping every web-group middleware by class name forces the
// router to remove them from this route specifically, regardless of
// where they were inherited from. Each entry mirrors an item in
// app/Http/Kernel.php's $middlewareGroups['web'] — keep this list in
// sync if that group changes.
$stripWebStack = [
    \App\Http\Middleware\EncryptCookies::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \App\Http\Middleware\VerifyCsrfToken::class,
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
    \App\Http\Middleware\MaintenanceMode::class,
    \Modules\Streaming\app\Http\Middleware\EnforceDeviceLimit::class,
    \Modules\Streaming\app\Http\Middleware\EnsureVisitorId::class,
];

Route::get('/sitemap.xml', [SitemapController::class, 'index'])
    ->withoutMiddleware($stripWebStack)
    ->name('seo.sitemap');

Route::get('/robots.txt', [RobotsController::class, 'index'])
    ->withoutMiddleware($stripWebStack)
    ->name('seo.robots');
