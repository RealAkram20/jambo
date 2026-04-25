<?php

namespace Modules\FileManager\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class FileManagerController extends Controller
{
    public function index()
    {
        $mediaDir = storage_path('app/public/media');
        $installFile = $mediaDir . DIRECTORY_SEPARATOR . 'install.php';
        $indexFile = $mediaDir . DIRECTORY_SEPARATOR . 'index.php';

        $state = match (true) {
            file_exists($installFile) => 'install',
            file_exists($indexFile)   => 'ready',
            default                    => 'missing',
        };

        // The Files Gallery drop-in lives at /storage/media/index.php,
        // which Apache serves directly — bypassing Laravel's role:admin
        // gate. storage/app/public/media/.htaccess rejects any request
        // missing the JAMBO_FM_SESSION cookie, and this is the only
        // place Laravel issues it. Admin hits this page, cookie lands,
        // iframe carries it along (same-origin), Apache allows access.
        // TTL is 4 hours so a session of admin work doesn't blank out
        // mid-task; re-issue on every hit so the clock resets while the
        // admin is active.
        return response()
            ->view('filemanager::index', compact('state'))
            ->cookie(
                'JAMBO_FM_SESSION',
                Str::random(40),
                4 * 60,                         // 4 hours
                '/',                            // path — iframe loads at /storage/...
                null,                           // domain — default
                config('session.secure', false), // secure on HTTPS
                true,                           // httpOnly
                false,                          // raw
                'Lax',                          // sameSite
            );
    }
}
