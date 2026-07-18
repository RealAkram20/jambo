<?php

use Illuminate\Support\Facades\Route;
use Modules\Pages\app\Http\Controllers\Admin\PageController;

/*
|--------------------------------------------------------------------------
| Pages Module — Web Routes
|--------------------------------------------------------------------------
|
| Admin CRUD for static pages (About, Contact, FAQ, Terms, Privacy, plus
| any custom additions). The public-facing rendering lives in the
| Frontend module — its existing about_us / contact_us / faq_page /
| privacy / terms_and_policy controller methods read from the `pages`
| table when a published row exists, otherwise fall back to the legacy
| template view.
|
*/

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // Delegatable system page: hidden + 403 until a super-admin grants
        // pages_access. Super-admins bypass via Gate::before.
        Route::resource('pages', PageController::class)->except(['show'])
            ->middleware('permission:pages_access');
    });
