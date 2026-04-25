<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RolePermission;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\AdminProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/app', [DashboardController::class, 'index'])->middleware(['auth', 'role:admin'])->name('dashboard');

// JSON endpoint for the dashboard chart filter dropdowns
// (Year/Month/Week). Returns a single chart's series + labels for
// the requested period so the frontend can call updateSeries()
// without a full reload.
Route::get('/app/charts/{chart}', [DashboardController::class, 'chartData'])
    ->middleware(['auth', 'role:admin'])
    ->whereIn('chart', ['revenue', 'newSubs', 'mostWatched'])
    ->name('dashboard.chart-data');

// Breeze shipped /profile routes pointing at App\Http\Controllers\
// ProfileController — that class has been deleted; the profile hub
// now lives under /{username}. Removing these avoids shadowing
// `profile.update` and silences the route:list reflection error.

// Dashboard Routes — template-showcase pages (UI elements, icons,
// widgets, etc.). Admin-only because they live under the admin
// chrome; regular users never need them.
Route::group(['as' => 'dashboard.', 'middleware' => ['auth', 'role:admin']], function () {
    // Route::get('static-app', [DashboardController::class, 'index'])->name('home');
    Route::get('rating', [DashboardController::class, 'rating'])->name('rating');
    Route::get('comment', [DashboardController::class, 'comment'])->name('comment');
    // User admin — full CRUD. Kept under `/user-list` with the
    // `dashboard.user-list` name so the sidebar link already points
    // at the right place. The create / store / edit / update /
    // destroy siblings are explicit rather than Route::resource
    // because we want the reserved-word /user-list/create to stay
    // routable on top of the index URL.
    Route::get('user-list', [AdminUserController::class, 'index'])->name('user-list');
    Route::get('user-list/create', [AdminUserController::class, 'create'])->name('user-list.create');
    Route::post('user-list', [AdminUserController::class, 'store'])->name('user-list.store');
    Route::get('user-list/{user}/edit', [AdminUserController::class, 'edit'])->name('user-list.edit');
    Route::patch('user-list/{user}', [AdminUserController::class, 'update'])->name('user-list.update');
    Route::delete('user-list/{user}', [AdminUserController::class, 'destroy'])->name('user-list.destroy');
    Route::get('movie-list', [DashboardController::class, 'movieList'])->name('movie-list');
    Route::get('movie-genres', [DashboardController::class, 'movieGenres'])->name('movie-genres');
    Route::get('vjs', [DashboardController::class, 'vjs'])->name('vjs');
    Route::get('movie-tags', [DashboardController::class, 'movieTags'])->name('movie-tags');
    Route::get('movie-playlist', [DashboardController::class, 'moviePlaylist'])->name('movie-playlist');
    Route::get('show-list', [DashboardController::class, 'showList'])->name('show-list');
    Route::get('seasons', [DashboardController::class, 'seasons'])->name('seasons');
    Route::get('tvshow-genres', [DashboardController::class, 'showGenres'])->name('tvshow-genres');
    Route::get('tvshow-tags', [DashboardController::class, 'showTags'])->name('tvshow-tags');
    Route::get('tvshow-playlist', [DashboardController::class, 'showPlaylist'])->name('tvshow-playlist');

    Route::get('person', [DashboardController::class, 'person'])->name('person');
    Route::get('person-categories', [DashboardController::class, 'personCategories'])->name('person-categories');
    Route::get('person-tags', [DashboardController::class, 'personTags'])->name('person-tags');

    Route::get('review', [DashboardController::class, 'review'])->name('review');
    Route::get('pricing', [DashboardController::class, 'pricing'])->name('pricing');

    Route::group(['prefix' => 'auth'], function () {
        Route::get('login', [DashboardController::class, 'login'])->name('login');
        Route::get('register', [DashboardController::class, 'register'])->name('register');
        Route::get('reset-password', [DashboardController::class, 'reset_password'])->name('reset-password');
        Route::get('verify-email', [DashboardController::class, 'verify_email'])->name('verify-email');
        Route::get('lock-screen', [DashboardController::class, 'lock_screen'])->name('lock-screen');
        Route::get('TwoFactor', [DashboardController::class, 'TwoFactor'])->name('TwoFactor');
        Route::get('AccountDeactivated', [DashboardController::class, 'AccountDeactivated'])->name('AccountDeactivated');
    });

    Route::get('error-404', [DashboardController::class, 'error404'])->name('error-404');
    Route::get('error-500', [DashboardController::class, 'error500'])->name('error-500');
    Route::get('maintenance', [DashboardController::class, 'maintenance'])->name('maintenance');
    Route::get('coming-soon', [DashboardController::class, 'coming'])->name('coming-soon');

    Route::get('blank-page', [DashboardController::class, 'blank'])->name('blank-page');
    Route::get('terms-of-use', [DashboardController::class, 'termsOfUse'])->name('terms-of-use');
    Route::get('dashboard/privacy-policy', [DashboardController::class, 'dashboardPrivacy'])->name('privacy-policy');

    Route::get('alerts', [DashboardController::class, 'alert'])->name('alerts');
    Route::get('avatars', [DashboardController::class, 'avatar'])->name('avatars');
    Route::get('badge', [DashboardController::class, 'badge'])->name('badge');
    Route::get('breadcrumb', [DashboardController::class, 'breadcrumb'])->name('breadcrumb');
    Route::get('buttons', [DashboardController::class, 'buttons'])->name('buttons');
    Route::get('buttonsgroup', [DashboardController::class, 'buttonsGroup'])->name('buttonsgroup');
    Route::get('offcanvas', [DashboardController::class, 'offcanvas'])->name('offcanvas');
    Route::get('colors', [DashboardController::class, 'colors'])->name('colors');
    Route::get('cards', [DashboardController::class, 'cards'])->name('cards');
    Route::get('carousel', [DashboardController::class, 'carousel'])->name('carousel');
    Route::get('grid', [DashboardController::class, 'grid'])->name('grid');
    Route::get('images', [DashboardController::class, 'images'])->name('images');
    Route::get('listgroup', [DashboardController::class, 'listgroup'])->name('listgroup');
    Route::get('modal', [DashboardController::class, 'modal'])->name('modal');
    Route::get('notificationss', [DashboardController::class, 'notifications'])->name('notificationss');
    Route::get('pagination', [DashboardController::class, 'pagination'])->name('pagination');
    Route::get('popovers', [DashboardController::class, 'popovers'])->name('popovers');
    Route::get('typography', [DashboardController::class, 'typography'])->name('typography');
    Route::get('tooltips', [DashboardController::class, 'tooltips'])->name('tooltips');
    Route::get('tabs', [DashboardController::class, 'tabs'])->name('tabs');
    Route::get('widget-basic', [DashboardController::class, 'widgetBasic'])->name('widget-basic');
    Route::get('widget-chart', [DashboardController::class, 'widgetChart'])->name('widget-chart');
    Route::get('widget-card', [DashboardController::class, 'widgetCard'])->name('widget-card');

    Route::get('elements', [DashboardController::class, 'elements'])->name('elements');
    Route::get('wizard', [DashboardController::class, 'wizard'])->name('wizard');
    Route::get('validation', [DashboardController::class, 'validation'])->name('validation');

    Route::get('bootstrap', [DashboardController::class, 'bootstrap'])->name('bootstrap');
    Route::get('border', [DashboardController::class, 'border'])->name('border');
    Route::get('fixed-table', [DashboardController::class, 'fancy'])->name('fixed-table');
    Route::get('table-data', [DashboardController::class, 'fixed'])->name('table-data');

    Route::get('font-awesome', [DashboardController::class, 'fontawesome'])->name('font-awesome');
    Route::get('ph-regular', [DashboardController::class, 'phregular'])->name('ph-regular');
    Route::get('ph-bold', [DashboardController::class, 'phbold'])->name('ph-bold');
    Route::get('ph-fill', [DashboardController::class, 'phfill'])->name('ph-fill');

    // Admin's own profile — the avatar-dropdown "Profile" link lands
    // here. Full CRUD for login details + session management.
    Route::get('profile', [AdminProfileController::class, 'index'])->name('profile');
    Route::patch('profile', [AdminProfileController::class, 'updateProfile'])->name('profile.update');
    Route::put('profile/password', [AdminProfileController::class, 'updatePassword'])->name('profile.password');
    Route::post('profile/sessions/logout-others', [AdminProfileController::class, 'logoutOtherSessions'])->name('profile.sessions.logout-others');
    Route::delete('profile/sessions/{session_id}', [AdminProfileController::class, 'logoutSession'])->name('profile.sessions.destroy');
});
Route::group(['as' => 'backend.', 'middleware' => ['auth', 'role:admin']], function () {
    // The GET entry point + every mutating endpoint (store + reset)
    // gate behind password.confirm so a stolen admin session cookie
    // can't silently grant itself permissions or wipe a role's grants.
    // Previously only the GET was gated — the POST / reset slipped
    // through, which was the security finding from the audit.
    Route::get('permission-role', [RolePermission::class, 'index'])
        ->name('permission-role')
        ->middleware('password.confirm');
    Route::post('/permission-role/store/{role_id}', [RolePermission::class, 'store'])
        ->name('permission-role.store')
        ->middleware('password.confirm');
    Route::get('/permission-role/reset/{role_id}', [RolePermission::class, 'reset_permission'])
        ->name('permission-role.reset')
        ->middleware('password.confirm');
    // Role & Permissions Crud
    Route::resource('permission', PermissionController::class);
    Route::resource('role', RoleController::class);
});
// Admin: System Settings (per-section saves so errors in one card
// don't block saving another)
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('settings', [AdminSettingController::class, 'index'])->name('settings.index');
        Route::post('settings/general', [AdminSettingController::class, 'updateGeneral'])->name('settings.general');
        Route::post('settings/branding', [AdminSettingController::class, 'updateBranding'])->name('settings.branding');
        Route::post('settings/smtp', [AdminSettingController::class, 'updateSmtp'])->name('settings.smtp');
        Route::post('settings/smtp-test', [AdminSettingController::class, 'sendTestEmail'])->name('settings.smtp-test');
        Route::post('settings/vapid', [AdminSettingController::class, 'updateVapid'])->name('settings.vapid');
        Route::post('settings/vapid-generate', [AdminSettingController::class, 'generateVapid'])->name('settings.vapid-generate');
    });

require __DIR__ . '/auth.php';

/*
|--------------------------------------------------------------------------
| Profile hub — /{username}/...
|--------------------------------------------------------------------------
|
| MUST be registered last. These routes use a catch-all `{username}`
| segment that would shadow any single-segment route defined after them.
| The `where('username', ...)` constraint blocks filenames / paths with
| dots + slashes; ReservedUsername rule at registration blocks pickups
| that would collide with real top-level routes like /login, /movie,
| etc.
|
*/
Route::middleware('auth')->group(function () {
    // Build the username constraint from the reserved list so that any
    // top-level route path (/notifications, /admin, /movies, etc.) is
    // excluded at route-match time, not just at signup validation.
    // Without this, /notifications gets interpreted as a profile hub
    // request with username="notifications" and admins get bounced to
    // /app by resolveOwn().
    $reserved = implode('|', array_map(
        fn ($n) => preg_quote($n, '/'),
        \App\Rules\ReservedUsername::RESERVED,
    ));
    $usernamePattern = '(?!(?:' . $reserved . ')$)[a-zA-Z0-9._\-]+';

    Route::get('/{username}',
        [\App\Http\Controllers\ProfileHubController::class, 'show'])
        ->where('username', $usernamePattern)
        ->name('profile.show');

    Route::put('/{username}',
        [\App\Http\Controllers\ProfileHubController::class, 'updateProfile'])
        ->where('username', $usernamePattern)
        ->name('profile.update');

    Route::get('/{username}/security',
        [\App\Http\Controllers\ProfileHubController::class, 'security'])
        ->where('username', $usernamePattern)
        ->name('profile.security');

    Route::get('/{username}/membership',
        [\App\Http\Controllers\ProfileHubController::class, 'membership'])
        ->where('username', $usernamePattern)
        ->name('profile.membership');

    Route::get('/{username}/billing',
        [\App\Http\Controllers\ProfileHubController::class, 'billing'])
        ->where('username', $usernamePattern)
        ->name('profile.billing');

    Route::get('/{username}/billing/{orderId}',
        [\App\Http\Controllers\ProfileHubController::class, 'invoice'])
        ->where('username', $usernamePattern)
        ->whereNumber('orderId')
        ->name('profile.invoice');

    Route::get('/{username}/watchlist',
        [\App\Http\Controllers\ProfileHubController::class, 'watchlist'])
        ->where('username', $usernamePattern)
        ->name('profile.watchlist');

    Route::get('/{username}/notifications',
        [\App\Http\Controllers\ProfileHubController::class, 'notifications'])
        ->where('username', $usernamePattern)
        ->name('profile.notifications');

    Route::put('/{username}/notifications/preferences',
        [\App\Http\Controllers\ProfileHubController::class, 'updateNotificationPrefs'])
        ->where('username', $usernamePattern)
        ->name('profile.notifications.prefs');

    // Devices — list + per-device logout + logout-all-others.
    Route::get('/{username}/devices',
        [\App\Http\Controllers\ProfileHubController::class, 'devices'])
        ->where('username', $usernamePattern)
        ->name('profile.devices');

    Route::delete('/{username}/devices/{session_id}',
        [\App\Http\Controllers\ProfileHubController::class, 'logoutDevice'])
        ->where('username', $usernamePattern)
        ->name('profile.devices.destroy');

    Route::post('/{username}/devices/logout-others',
        [\App\Http\Controllers\ProfileHubController::class, 'logoutOtherDevices'])
        ->where('username', $usernamePattern)
        ->name('profile.devices.logout-others');
});
