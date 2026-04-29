<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

/**
 * Two read-mostly admin pages, both gated by web + auth + role:admin
 * (see routes/web.php). They give the operator a quick way to triage
 * a production issue without SSHing into the box.
 *
 *   - Error Log: tail the storage/logs/*.log files. Read-only with
 *     a one-click "clear" button per file. Capped at the last N KB so
 *     a multi-GB log doesn't blow PHP's memory_limit.
 *
 *   - System Status: the kind of `php artisan about` snapshot the
 *     operator wants when something on prod looks off — Laravel + PHP
 *     version, current commit/version, free disk, queue connection,
 *     storage symlink presence, mail driver, and which Jambo modules
 *     are enabled.
 */
class SystemDiagnosticsController extends Controller
{
    /** Max bytes of any single log file we'll read into memory. */
    private const LOG_TAIL_BYTES = 256 * 1024; // 256 KiB

    /* ------------------------------------------------------------------ */
    /* Error log                                                          */
    /* ------------------------------------------------------------------ */

    public function logsIndex(Request $request): View
    {
        $logsDir = storage_path('logs');
        $selected = $request->query('file');
        $files = $this->logFiles($logsDir);

        // Default to the most-recently-modified file when nothing is
        // explicitly chosen — that's what an operator actually wants
        // when they click in.
        if (!$selected && !empty($files)) {
            $selected = $files[0]['name'];
        }

        // Validate $selected against the listing so a query string
        // can't traverse out of storage/logs.
        $valid = collect($files)->contains(fn ($f) => $f['name'] === $selected);
        $tail = null;
        $truncated = false;
        $sizeBytes = 0;

        if ($valid && $selected) {
            $path = $logsDir . DIRECTORY_SEPARATOR . $selected;
            $sizeBytes = (int) @filesize($path);
            [$tail, $truncated] = $this->tailFile($path, self::LOG_TAIL_BYTES);
        }

        return view('admin.diagnostics.logs', [
            'files' => $files,
            'selected' => $valid ? $selected : null,
            'tail' => $tail,
            'truncated' => $truncated,
            'sizeBytes' => $sizeBytes,
            'tailLimitBytes' => self::LOG_TAIL_BYTES,
        ]);
    }

    public function logsClear(Request $request, string $file): RedirectResponse
    {
        $logsDir = storage_path('logs');
        $valid = collect($this->logFiles($logsDir))->contains(fn ($f) => $f['name'] === $file);

        if (!$valid) {
            return redirect()->route('admin.diagnostics.logs')
                ->with('error', 'Unknown log file.');
        }

        @file_put_contents($logsDir . DIRECTORY_SEPARATOR . $file, '');

        return redirect()->route('admin.diagnostics.logs', ['file' => $file])
            ->with('success', "Cleared $file.");
    }

    /* ------------------------------------------------------------------ */
    /* System status                                                      */
    /* ------------------------------------------------------------------ */

    public function statusIndex(): View
    {
        $base = base_path();
        $modulesPath = $base . DIRECTORY_SEPARATOR . 'modules_statuses.json';
        $moduleStates = [];
        if (File::exists($modulesPath)) {
            $decoded = json_decode((string) File::get($modulesPath), true);
            if (is_array($decoded)) {
                ksort($decoded);
                $moduleStates = $decoded;
            }
        }

        $publicStorageLink = public_path('storage');
        $storageLinkExists = is_dir($publicStorageLink) || is_link($publicStorageLink);

        $diskFree = @disk_free_space($base);
        $diskTotal = @disk_total_space($base);

        $dbDriver = config('database.default');
        $dbConnected = false;
        $dbName = config("database.connections.$dbDriver.database");
        try {
            DB::connection()->getPdo();
            $dbConnected = true;
        } catch (\Throwable $e) {
            $dbConnected = false;
        }

        return view('admin.diagnostics.status', [
            'app' => [
                'name'        => config('app.name'),
                'env'         => config('app.env'),
                'debug'       => (bool) config('app.debug'),
                'url'         => config('app.url'),
                'timezone'    => config('app.timezone'),
                'locale'      => config('app.locale'),
                'version'     => trim((string) @file_get_contents(base_path('version.txt'))) ?: 'unknown',
                'php'         => PHP_VERSION,
                'laravel'     => \Illuminate\Foundation\Application::VERSION,
            ],
            'runtime' => [
                'cache_driver'   => config('cache.default'),
                'queue_driver'   => config('queue.default'),
                'session_driver' => config('session.driver'),
                'mail_mailer'    => config('mail.default'),
                'broadcast'      => config('broadcasting.default'),
                'filesystem'     => config('filesystems.default'),
            ],
            'database' => [
                'driver'    => $dbDriver,
                'name'      => $dbName,
                'connected' => $dbConnected,
            ],
            'disk' => [
                'free_bytes'  => $diskFree !== false ? (int) $diskFree : null,
                'total_bytes' => $diskTotal !== false ? (int) $diskTotal : null,
            ],
            'storage' => [
                'symlink_present' => $storageLinkExists,
                'storage_path'    => storage_path(),
                'public_path'     => public_path(),
            ],
            'modules' => $moduleStates,
            'php_extensions' => $this->checkExtensions([
                'pdo_mysql', 'mbstring', 'openssl', 'gd', 'zip',
                'fileinfo', 'curl', 'tokenizer', 'xml', 'bcmath',
                'exif', 'intl',
            ]),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* helpers                                                            */
    /* ------------------------------------------------------------------ */

    /** @return list<array{name:string,size:int,mtime:int}> */
    private function logFiles(string $dir): array
    {
        if (!is_dir($dir)) {
            return [];
        }

        $out = [];
        foreach (scandir($dir) ?: [] as $name) {
            if ($name === '.' || $name === '..') continue;
            // Restrict to plain log files. A misuse of this listing
            // (e.g. some script depositing arbitrary files in logs/)
            // can't surface non-log content via the UI.
            if (!preg_match('/\.log$/i', $name)) continue;
            $path = $dir . DIRECTORY_SEPARATOR . $name;
            if (!is_file($path)) continue;
            $out[] = [
                'name' => $name,
                'size' => (int) @filesize($path),
                'mtime' => (int) @filemtime($path),
            ];
        }

        // Newest first.
        usort($out, fn ($a, $b) => $b['mtime'] <=> $a['mtime']);
        return $out;
    }

    /**
     * Read the last $bytes of a file. Avoids file_get_contents on a
     * potentially-huge log. Returns [content, truncated?].
     *
     * @return array{0:string,1:bool}
     */
    private function tailFile(string $path, int $bytes): array
    {
        $size = (int) @filesize($path);
        if ($size <= 0) {
            return ['', false];
        }

        $fh = @fopen($path, 'rb');
        if (!$fh) {
            return ['(could not open ' . basename($path) . ')', false];
        }

        $truncated = $size > $bytes;
        if ($truncated) {
            fseek($fh, -$bytes, SEEK_END);
            // Drop the (likely) partial first line so the output starts cleanly.
            fgets($fh);
        }

        $content = (string) stream_get_contents($fh);
        fclose($fh);
        return [$content, $truncated];
    }

    /** @param array<int,string> $exts @return array<string,bool> */
    private function checkExtensions(array $exts): array
    {
        $out = [];
        foreach ($exts as $e) {
            $out[$e] = extension_loaded($e);
        }
        ksort($out);
        return $out;
    }
}
