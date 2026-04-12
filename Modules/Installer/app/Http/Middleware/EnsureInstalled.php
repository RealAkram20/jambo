<?php

namespace Modules\Installer\app\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Jambo install gate.
 *
 * Redirects every request to /install until the installer wizard finishes
 * and writes the `storage/installed` flag file. Once the flag exists, any
 * request that lands on /install* is redirected back to the site root,
 * locking the wizard so it can't be re-run from the browser.
 *
 * Because the installer has to run BEFORE any database exists, this
 * middleware also swaps the session driver to `file` for install routes
 * — otherwise Laravel's default `database` session driver would try to
 * hit the `sessions` table that hasn't been created yet.
 */
class EnsureInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        $flagPath = storage_path('installed');
        $isInstalled = file_exists($flagPath);
        $isInstallRoute = $request->is('install') || $request->is('install/*');

        // During the install wizard, force a file-based session so the
        // wizard can store form state before the sessions table exists.
        if (!$isInstalled && $isInstallRoute) {
            config([
                'session.driver' => 'file',
                'cache.default' => 'file',
            ]);
        }

        // Not installed and not on the installer → bounce to the installer.
        if (!$isInstalled && !$isInstallRoute) {
            return redirect('/install');
        }

        // Already installed and trying to reach the installer → bounce away.
        if ($isInstalled && $isInstallRoute) {
            return redirect('/');
        }

        return $next($request);
    }
}
