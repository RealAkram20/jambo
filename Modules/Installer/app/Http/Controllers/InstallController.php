<?php

namespace Modules\Installer\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Installer\app\Services\InstallerService;

/**
 * Web installer wizard. Each step persists its form input into the
 * session; after the "admin account" step we freeze everything into a
 * JSON state file and run the remaining work via AJAX through
 * `executeStep($n)`.
 *
 * The matching EnsureInstalled middleware makes sure every request
 * redirects here until `storage/installed` exists, and locks the wizard
 * once installation is complete.
 */
class InstallController extends Controller
{
    public function __construct(private readonly InstallerService $installer)
    {
    }

    /* -------------------------------------------------------------------- */
    /* Step 1 — Requirements                                                */
    /* -------------------------------------------------------------------- */

    public function entry(): RedirectResponse
    {
        return redirect()->route('install.requirements');
    }

    public function requirements(): View
    {
        $report = $this->installer->checkRequirements();
        return view('installer::steps.requirements', $report);
    }

    /* -------------------------------------------------------------------- */
    /* Step 2 — Database                                                    */
    /* -------------------------------------------------------------------- */

    public function database(Request $request): View
    {
        return view('installer::steps.database', [
            'values' => session('install.database', [
                'host' => '127.0.0.1',
                'port' => '3306',
                'database' => 'jambo',
                'username' => 'root',
                'password' => '',
            ]),
        ]);
    }

    public function validateDatabase(Request $request): JsonResponse
    {
        $creds = $request->validate([
            'host' => 'required|string',
            'port' => 'required|string',
            'database' => 'required|string',
            'username' => 'required|string',
            'password' => 'nullable|string',
        ]);

        $result = $this->installer->testDatabaseConnection($creds);
        return response()->json($result);
    }

    public function storeDatabase(Request $request): RedirectResponse
    {
        $creds = $request->validate([
            'host' => 'required|string',
            'port' => 'required|string',
            'database' => 'required|string',
            'username' => 'required|string',
            'password' => 'nullable|string',
        ]);

        $result = $this->installer->testDatabaseConnection($creds);
        if (!$result['ok']) {
            return back()->withInput()->with('error', $result['message']);
        }

        session(['install.database' => $creds]);
        return redirect()->route('install.settings');
    }

    /* -------------------------------------------------------------------- */
    /* Step 3 — App settings                                                */
    /* -------------------------------------------------------------------- */

    public function settings(): View
    {
        return view('installer::steps.settings', [
            'values' => session('install.settings', [
                'app_name' => 'Jambo',
                'app_url' => rtrim(url('/'), '/'),
                'app_env' => 'local',
            ]),
        ]);
    }

    public function storeSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'app_name' => 'required|string|max:100',
            'app_url' => 'required|url|max:255',
            'app_env' => 'required|in:local,production',
        ]);

        session(['install.settings' => $data]);
        return redirect()->route('install.admin');
    }

    /* -------------------------------------------------------------------- */
    /* Step 4 — Admin account                                               */
    /* -------------------------------------------------------------------- */

    public function admin(): View
    {
        return view('installer::steps.admin', [
            'values' => session('install.admin', [
                'first_name' => '',
                'last_name' => '',
                'email' => '',
            ]),
        ]);
    }

    public function storeAdmin(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:60',
            'last_name' => 'required|string|max:60',
            'email' => 'required|email|max:120',
            'password' => 'required|string|min:8|confirmed',
        ]);

        session(['install.admin' => $data]);
        return redirect()->route('install.run');
    }

    /* -------------------------------------------------------------------- */
    /* Step 5 — Run (progress page + AJAX executor)                         */
    /* -------------------------------------------------------------------- */

    public function run(Request $request): View|RedirectResponse
    {
        $db = session('install.database');
        $settings = session('install.settings');
        $admin = session('install.admin');

        if (!$db || !$settings || !$admin) {
            return redirect()->route('install.requirements')
                ->with('error', 'Some wizard steps are missing. Please start over.');
        }

        // Freeze wizard input into a JSON state file. After this point the
        // session may become unusable (we're about to rewrite .env and
        // possibly swap the session backend), so everything the executor
        // needs has to live on disk.
        $state = [
            'database' => $db,
            'settings' => $settings,
            'admin' => $admin,
            'last_completed_step' => 0,
        ];
        $this->installer->saveState($state);

        // Clear wizard session keys now that they're persisted.
        session()->forget(['install.database', 'install.settings', 'install.admin']);

        return view('installer::steps.run', [
            'stepCount' => InstallerService::STEP_COUNT,
            'stepLabels' => $this->stepLabels(),
        ]);
    }

    public function executeStep(int $step): JsonResponse
    {
        if ($step < 1 || $step > InstallerService::STEP_COUNT) {
            return response()->json(['ok' => false, 'error' => 'Invalid step number.'], 422);
        }

        $state = $this->installer->loadState();

        // Enforce strict sequential execution.
        if ($step > ($state['last_completed_step'] ?? 0) + 1) {
            return response()->json([
                'ok' => false,
                'error' => 'Cannot skip ahead; previous steps must finish first.',
            ], 422);
        }

        $result = $this->installer->runStep($step, $state);
        return response()->json($result);
    }

    /* -------------------------------------------------------------------- */
    /* Step 6 — Complete                                                    */
    /* -------------------------------------------------------------------- */

    public function complete(): View|RedirectResponse
    {
        if (!$this->installer->isInstalled()) {
            return redirect()->route('install.requirements')
                ->with('error', 'Installation did not finish. Please retry.');
        }

        return view('installer::steps.complete', [
            'loginUrl' => url('/login'),
            'homeUrl' => url('/'),
        ]);
    }

    /* -------------------------------------------------------------------- */
    /* Labels                                                               */
    /* -------------------------------------------------------------------- */

    private function stepLabels(): array
    {
        return [
            1 => 'Writing .env configuration',
            2 => 'Generating application key',
            3 => 'Running database migrations',
            4 => 'Seeding roles and permissions',
            5 => 'Creating admin account',
            6 => 'Linking storage directory',
            7 => 'Clearing caches',
            8 => 'Finalizing installation',
        ];
    }
}
