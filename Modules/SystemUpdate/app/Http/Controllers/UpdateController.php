<?php

namespace Modules\SystemUpdate\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\SystemUpdate\app\Services\UpdateManager;

/**
 * Admin-facing update UI and JSON endpoints.
 *
 *   GET  /admin/updates                            index page
 *   POST /admin/updates/check                      manifest refresh, JSON
 *   POST /admin/updates/run                        run the update flow, JSON log
 *   POST /admin/updates/backups/{name}/restore     roll back to a retained backup, JSON log
 *
 * Middleware is applied in the route file using the configured stack
 * (`web + auth + role:admin` by default), and the controller
 * double-checks the optional allow_users_id list as a second layer of
 * defence — belt and braces on a destructive endpoint.
 */
class UpdateController extends Controller
{
    public function __construct(private readonly UpdateManager $manager)
    {
    }

    public function index(): View
    {
        $status = $this->manager->status();
        $backups = $this->manager->listBackups();

        return view('systemupdate::updates.index', [
            'status' => $status,
            'backups' => $backups,
            'checkUrl' => route('admin.updates.check'),
            'runUrl' => route('admin.updates.run'),
        ]);
    }

    public function check(Request $request): JsonResponse
    {
        $this->guardAllowlist($request);

        return response()->json($this->manager->status());
    }

    public function run(Request $request): JsonResponse
    {
        $this->guardAllowlist($request);

        $result = $this->manager->runUpdate();

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    /**
     * Manual rollback: restore files + DB from a previously retained
     * backup. Just as destructive as run() — same allowlist gate.
     */
    public function restoreBackup(Request $request, string $name): JsonResponse
    {
        $this->guardAllowlist($request);

        $result = $this->manager->restoreBackup($name);

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    /**
     * Extra defence-in-depth check: if the config declares an explicit
     * list of user IDs allowed to trigger updates, reject anyone else
     * even if they have the admin role.
     */
    private function guardAllowlist(Request $request): void
    {
        $allow = config('systemupdate.allow_users_id');
        if (!is_array($allow) || empty($allow)) {
            return;
        }

        $userId = optional($request->user())->id;
        if (!in_array($userId, $allow, true)) {
            abort(403, 'Your account is not authorised to run updates on this server.');
        }
    }
}
