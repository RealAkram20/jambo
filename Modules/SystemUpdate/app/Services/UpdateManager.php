<?php

namespace Modules\SystemUpdate\app\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Orchestrates an in-app update:
 *
 *   1. Fetch the release manifest (local file first, remote URL fallback).
 *   2. Compare manifest.version with version.txt via version_compare().
 *   3. If newer, download the archive, extract with backup, run migrations,
 *      write the new version.txt, and clear caches.
 *   4. On any failure after extraction, restore the backup before bringing
 *      the site back up.
 *
 * Keeps its own tiny append-only log at storage/logs/updater.log so the
 * admin can see what happened after the fact.
 */
class UpdateManager
{
    public function __construct(private readonly ZipExtractor $extractor)
    {
    }

    /* -------------------------------------------------------------------- */
    /* Version + manifest                                                   */
    /* -------------------------------------------------------------------- */

    public function currentVersion(): string
    {
        $path = base_path(config('systemupdate.version_file', 'version.txt'));
        if (!File::exists($path)) {
            return '0.0.0';
        }
        return trim(File::get($path)) ?: '0.0.0';
    }

    /**
     * Try the local manifest first, then the remote URL. Returns null
     * if neither yields a parseable JSON object with a version field.
     *
     * @return array{version: string, archive: ?string, description: ?string}|null
     */
    public function fetchManifest(): ?array
    {
        $local = config('systemupdate.manifest.local');
        if ($local && File::exists($local)) {
            $decoded = $this->decodeManifest(File::get($local));
            if ($decoded !== null) {
                $decoded['source'] = 'local';
                return $decoded;
            }
        }

        $remote = config('systemupdate.manifest.remote');
        if ($remote) {
            try {
                $response = Http::timeout((int) config('systemupdate.http_timeout', 30))
                    ->acceptJson()
                    ->get(rtrim($remote, '/') . '/laraupdater.json');

                if ($response->ok()) {
                    $decoded = $this->decodeManifest($response->body());
                    if ($decoded !== null) {
                        $decoded['source'] = 'remote';
                        return $decoded;
                    }
                }
            } catch (Throwable $e) {
                $this->log("Remote manifest fetch failed: {$e->getMessage()}");
            }
        }

        return null;
    }

    /**
     * Convenience: summarise the update state for the admin UI.
     *
     * @return array{
     *     current: string,
     *     latest: ?string,
     *     has_update: bool,
     *     manifest: ?array,
     * }
     */
    public function status(): array
    {
        $current = $this->currentVersion();
        $manifest = $this->fetchManifest();
        $latest = $manifest['version'] ?? null;

        return [
            'current' => $current,
            'latest' => $latest,
            'has_update' => $latest !== null && version_compare($latest, $current, '>'),
            'manifest' => $manifest,
        ];
    }

    /* -------------------------------------------------------------------- */
    /* Run the update                                                       */
    /* -------------------------------------------------------------------- */

    /**
     * @return array{ok: bool, messages: string[], error: ?string}
     */
    public function runUpdate(): array
    {
        $log = [];
        $note = function (string $msg) use (&$log) {
            $log[] = $msg;
            $this->log($msg);
        };

        $status = $this->status();
        if (!$status['has_update']) {
            return ['ok' => false, 'messages' => $log, 'error' => 'No update available.'];
        }

        $manifest = $status['manifest'];
        $archiveUrl = $manifest['archive'] ?? '';
        if (!$archiveUrl) {
            return ['ok' => false, 'messages' => $log, 'error' => 'Manifest has no archive URL.'];
        }

        $required = (int) config('systemupdate.require_free_disk_bytes', 0);
        if ($required > 0 && disk_free_space(base_path()) < $required) {
            return ['ok' => false, 'messages' => $log, 'error' => 'Not enough free disk space.'];
        }

        $note("Starting update {$status['current']} → {$status['latest']}");

        $tmpDir = base_path(config('systemupdate.tmp_folder', 'tmp'));
        File::ensureDirectoryExists($tmpDir);

        $zipPath = $tmpDir . DIRECTORY_SEPARATOR . 'RELEASE-' . $status['latest'] . '.zip';
        $backupDir = null;

        try {
            // 1. Maintenance mode.
            Artisan::call('down');
            $note('Maintenance mode enabled.');

            // 2. Download.
            $note('Downloading update package…');
            Http::timeout((int) config('systemupdate.http_timeout', 300))
                ->sink($zipPath)
                ->get($archiveUrl);

            if (!file_exists($zipPath) || filesize($zipPath) === 0) {
                throw new RuntimeException('Downloaded package is empty or missing.');
            }
            $note('Downloaded ' . number_format(filesize($zipPath) / 1024, 1) . ' KiB.');

            // 3. Extract + backup.
            $note('Extracting package…');
            $backupDir = $this->extractor->extract($zipPath);
            $note('Extracted. Backup at ' . basename($backupDir));

            // 4. Migrations.
            $note('Running database migrations…');
            set_time_limit(300);
            Artisan::call('migrate', ['--force' => true]);

            // 5. Write new version.
            $versionFile = base_path(config('systemupdate.version_file', 'version.txt'));
            File::put($versionFile, $status['latest'] . "\n");
            $note("Wrote version.txt: {$status['latest']}");

            // 6. Clear caches.
            $this->clearCaches();
            $note('Cleared caches.');

            // 7. Cleanup.
            @unlink($zipPath);
            $this->extractor->deleteDirectory($backupDir);
            $note('Cleaned up backup and temp files.');

            // 8. Maintenance off.
            Artisan::call('up');
            $note('Update complete.');

            return ['ok' => true, 'messages' => $log, 'error' => null];
        } catch (Throwable $e) {
            $note('FAILED: ' . $e->getMessage());

            if ($backupDir && is_dir($backupDir)) {
                $note('Restoring from backup…');
                try {
                    $this->extractor->restore($backupDir);
                    $this->extractor->deleteDirectory($backupDir);
                    $note('Restored.');
                } catch (Throwable $restoreError) {
                    $note('Restore failed: ' . $restoreError->getMessage());
                }
            }

            @unlink($zipPath);

            try {
                Artisan::call('up');
            } catch (Throwable) {
                // best-effort; admin can run `php artisan up` manually
            }

            return ['ok' => false, 'messages' => $log, 'error' => $e->getMessage()];
        }
    }

    /* -------------------------------------------------------------------- */
    /* Internals                                                            */
    /* -------------------------------------------------------------------- */

    private function decodeManifest(string $json): ?array
    {
        $decoded = json_decode($json, true);
        if (!is_array($decoded) || empty($decoded['version'])) {
            return null;
        }
        return [
            'version' => (string) $decoded['version'],
            'archive' => $decoded['archive'] ?? null,
            'description' => $decoded['description'] ?? null,
            'released_at' => $decoded['released_at'] ?? null,
        ];
    }

    private function clearCaches(): void
    {
        try {
            Artisan::call('optimize:clear');
        } catch (Throwable) {
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
        }
    }

    private function log(string $message): void
    {
        $logPath = storage_path(config('systemupdate.log_file', 'logs/updater.log'));
        File::ensureDirectoryExists(dirname($logPath));
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
        @file_put_contents($logPath, $line, FILE_APPEND);

        Log::info('[updater] ' . $message);
    }
}
