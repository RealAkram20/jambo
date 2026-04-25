<?php

namespace Modules\SystemUpdate\app\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Orchestrates an in-app update with full rollback safety:
 *
 *   1. Fetch the release manifest (local file first, remote URL fallback).
 *   2. Compare manifest.version with version.txt via version_compare().
 *   3. Dump the database BEFORE running migrations (the file backup
 *      can't undo a destructive `migrate --force`).
 *   4. Download the archive, extract with per-file backup (denying
 *      anything in storage/ or .env so user state can't be clobbered),
 *      run migrations, write the new version.txt, clear caches.
 *   5. On success: move the file backup + DB dump into a retained
 *      `storage/app/updates/file-backups/<timestamp>/` slot, rotate so
 *      only the last N (default 3) survive.
 *   6. On failure: restore files from backup, restore the DB dump,
 *      bring the site back up.
 *
 * Also supports manual rollback via listBackups()/restoreBackup() so an
 * admin can roll back a prior successful update if a bug surfaces later.
 */
class UpdateManager
{
    public function __construct(
        private readonly ZipExtractor $extractor,
        private readonly DatabaseBackup $dbBackup,
    ) {
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
        $dbBackupPath = null;

        try {
            // 1. Maintenance mode.
            Artisan::call('down');
            $note('Maintenance mode enabled.');

            // 2. DB dump BEFORE migrate. If this fails for a supported
            //    driver we abort — proceeding without a DB backup is
            //    exactly the data-loss path this exists to prevent.
            if (config('systemupdate.db_backup.enabled', true)) {
                $note('Dumping database…');
                $dbBackupPath = $this->dbBackup->dump('pre-' . $status['latest']);
                if ($dbBackupPath === null) {
                    $note('  (driver not supported — proceeding without a DB rollback safety net)');
                } else {
                    $note('  → ' . basename($dbBackupPath) . ' (' .
                        $this->humanBytes((int) @filesize($dbBackupPath)) . ')');
                }
            } else {
                $note('DB backup disabled by config — skipping.');
            }

            // 3. Download.
            $note('Downloading update package…');
            Http::timeout((int) config('systemupdate.http_timeout', 300))
                ->sink($zipPath)
                ->get($archiveUrl);

            if (!file_exists($zipPath) || filesize($zipPath) === 0) {
                throw new RuntimeException('Downloaded package is empty or missing.');
            }
            $note('Downloaded ' . $this->humanBytes((int) filesize($zipPath)) . '.');

            // 4. Extract + backup. Anything in the deny list is silently
            //    skipped so a careless release can't overwrite uploads
            //    or .env.
            $note('Extracting package…');
            $backupDir = $this->extractor->extract($zipPath);
            $note('Extracted. File backup at ' . basename($backupDir));
            $skipped = $this->extractor->lastSkipped();
            if (!empty($skipped)) {
                $note('Denied ' . count($skipped) . ' protected path(s) from the zip:');
                foreach (array_slice($skipped, 0, 10) as $s) {
                    $note('  · ' . $s);
                }
                if (count($skipped) > 10) {
                    $note('  · …and ' . (count($skipped) - 10) . ' more.');
                }
            }

            // 5. Migrations.
            $note('Running database migrations…');
            set_time_limit(300);
            Artisan::call('migrate', ['--force' => true]);

            // 6. Write new version.
            $versionFile = base_path(config('systemupdate.version_file', 'version.txt'));
            File::put($versionFile, $status['latest'] . "\n");
            $note("Wrote version.txt: {$status['latest']}");

            // 7. Clear caches.
            $this->clearCaches();
            $note('Cleared caches.');

            // 8. Retain the file backup + DB dump together so admins
            //    can roll back later. Old retained backups beyond the
            //    keep-N limit get rotated out here.
            $retained = $this->retainBackup($backupDir, $dbBackupPath, $status['current'], $status['latest']);
            $note('Retained backup as ' . basename($retained));
            $rotated = $this->rotateBackups();
            if ($rotated > 0) {
                $note("Rotated $rotated old backup(s) past the retention limit.");
            }

            // 9. Cleanup.
            @unlink($zipPath);
            $note('Cleaned up temp files.');

            // 10. Maintenance off.
            Artisan::call('up');
            $note('Update complete.');

            return ['ok' => true, 'messages' => $log, 'error' => null];
        } catch (Throwable $e) {
            $note('FAILED: ' . $e->getMessage());

            if ($backupDir && is_dir($backupDir)) {
                $note('Restoring files from backup…');
                try {
                    $this->extractor->restore($backupDir);
                    $this->extractor->deleteDirectory($backupDir);
                    $note('Files restored.');
                } catch (Throwable $restoreError) {
                    $note('File restore failed: ' . $restoreError->getMessage());
                }
            }

            if ($dbBackupPath && File::exists($dbBackupPath)) {
                $note('Restoring database from dump…');
                try {
                    $this->dbBackup->restore($dbBackupPath);
                    $note('Database restored.');
                    // Keep the dump file on disk for forensic review;
                    // admin can delete manually after verifying.
                } catch (Throwable $dbRestoreError) {
                    $note('Database restore failed: ' . $dbRestoreError->getMessage());
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
    /* Retained backups (manual rollback)                                   */
    /* -------------------------------------------------------------------- */

    /**
     * Retained backups available for manual restore. Newest first.
     *
     * @return array<int, array{name: string, path: string, version_from: ?string, version_to: ?string, size_bytes: int, created_at: int, has_db: bool}>
     */
    public function listBackups(): array
    {
        $root = $this->retainedBackupRoot();
        if (!is_dir($root)) {
            return [];
        }

        $entries = [];
        foreach (scandir($root) ?: [] as $name) {
            if ($name === '.' || $name === '..') continue;
            $path = $root . DIRECTORY_SEPARATOR . $name;
            if (!is_dir($path)) continue;

            $meta = $this->readBackupMeta($path);
            $entries[] = [
                'name' => $name,
                'path' => $path,
                'version_from' => $meta['version_from'] ?? null,
                'version_to' => $meta['version_to'] ?? null,
                'size_bytes' => $this->dirSize($path),
                'created_at' => $meta['created_at'] ?? @filemtime($path) ?: 0,
                'has_db' => !empty($meta['db_dump']) && File::exists($path . DIRECTORY_SEPARATOR . $meta['db_dump']),
            ];
        }

        // Newest first
        usort($entries, fn ($a, $b) => $b['created_at'] <=> $a['created_at']);
        return $entries;
    }

    /**
     * Restore a retained backup by name. Files first, then DB. Returns
     * the same shape as runUpdate() so the controller can stream the log
     * back to the admin.
     *
     * @return array{ok: bool, messages: string[], error: ?string}
     */
    public function restoreBackup(string $name): array
    {
        $log = [];
        $note = function (string $msg) use (&$log) {
            $log[] = $msg;
            $this->log("[restore] $msg");
        };

        // Reject obviously bad names so a malicious POST can't escape
        // the retained-backup root via "..".
        if (!preg_match('/^[A-Za-z0-9._-]+$/', $name)) {
            return ['ok' => false, 'messages' => $log, 'error' => 'Invalid backup name.'];
        }

        $path = $this->retainedBackupRoot() . DIRECTORY_SEPARATOR . $name;
        if (!is_dir($path)) {
            return ['ok' => false, 'messages' => $log, 'error' => 'Backup not found.'];
        }

        $meta = $this->readBackupMeta($path);
        $note("Restoring backup: $name");
        if (!empty($meta['version_from'])) {
            $note("  → rolling back to version " . $meta['version_from']);
        }

        try {
            Artisan::call('down');
            $note('Maintenance mode enabled.');

            // Restore files. The retained dir's `files/` subdir mirrors
            // the project root layout — same shape ZipExtractor::restore
            // expects.
            $filesDir = $path . DIRECTORY_SEPARATOR . 'files';
            if (is_dir($filesDir)) {
                $this->extractor->restore($filesDir);
                $note('Files restored.');
            } else {
                $note('No files/ subdir in backup — skipping file restore.');
            }

            // Restore DB if a dump is present.
            if (!empty($meta['db_dump'])) {
                $dump = $path . DIRECTORY_SEPARATOR . $meta['db_dump'];
                if (File::exists($dump)) {
                    $this->dbBackup->restore($dump);
                    $note('Database restored.');
                }
            }

            // Roll the version back if the backup recorded a from-version.
            if (!empty($meta['version_from'])) {
                $versionFile = base_path(config('systemupdate.version_file', 'version.txt'));
                File::put($versionFile, $meta['version_from'] . "\n");
                $note("Wrote version.txt: " . $meta['version_from']);
            }

            $this->clearCaches();
            $note('Cleared caches.');

            Artisan::call('up');
            $note('Restore complete.');

            return ['ok' => true, 'messages' => $log, 'error' => null];
        } catch (Throwable $e) {
            $note('FAILED: ' . $e->getMessage());
            try {
                Artisan::call('up');
            } catch (Throwable) {
            }
            return ['ok' => false, 'messages' => $log, 'error' => $e->getMessage()];
        }
    }

    /* -------------------------------------------------------------------- */
    /* Internals                                                            */
    /* -------------------------------------------------------------------- */

    /**
     * Move the per-update file backup + DB dump into the retained-backup
     * tree, write a metadata file alongside it, and return the new path.
     */
    private function retainBackup(
        string $fileBackupDir,
        ?string $dbBackupPath,
        string $versionFrom,
        string $versionTo,
    ): string {
        $root = $this->retainedBackupRoot();
        File::ensureDirectoryExists($root);

        $stamp = date('Ymd_His');
        $name = $stamp . '_v' . $versionFrom . '_to_v' . $versionTo;
        $name = preg_replace('/[^A-Za-z0-9._-]/', '_', $name) ?: $stamp;
        $dest = $root . DIRECTORY_SEPARATOR . $name;

        File::ensureDirectoryExists($dest);

        // Move file backup tree under files/. The project root and
        // storage/ may live on different volumes on some VPS layouts,
        // and rename() returns false (EXDEV) across mount points —
        // moveTree() falls back to recursive copy + delete in that case.
        $this->moveTree($fileBackupDir, $dest . DIRECTORY_SEPARATOR . 'files');

        // Move DB dump alongside it
        $dbBasename = null;
        if ($dbBackupPath && File::exists($dbBackupPath)) {
            $dbBasename = basename($dbBackupPath);
            $this->moveFile($dbBackupPath, $dest . DIRECTORY_SEPARATOR . $dbBasename);
        }

        // Write meta.json
        $meta = [
            'version_from' => $versionFrom,
            'version_to' => $versionTo,
            'created_at' => time(),
            'db_dump' => $dbBasename,
        ];
        File::put(
            $dest . DIRECTORY_SEPARATOR . 'meta.json',
            json_encode($meta, JSON_PRETTY_PRINT) . "\n"
        );

        return $dest;
    }

    /**
     * Keep only the most recent N retained backups (config), delete the
     * rest. Returns the number deleted.
     */
    private function rotateBackups(): int
    {
        $keep = (int) config('systemupdate.file_backup.retain', 3);
        if ($keep <= 0) {
            return 0;
        }

        $entries = $this->listBackups();
        if (count($entries) <= $keep) {
            return 0;
        }

        $toDelete = array_slice($entries, $keep);
        $deleted = 0;
        foreach ($toDelete as $entry) {
            try {
                $this->extractor->deleteDirectory($entry['path']);
                $deleted++;
            } catch (Throwable $e) {
                $this->log('Could not delete old backup ' . $entry['name'] . ': ' . $e->getMessage());
            }
        }
        return $deleted;
    }

    private function retainedBackupRoot(): string
    {
        $relative = config('systemupdate.file_backup.path', 'app/updates/file-backups');
        return storage_path(ltrim($relative, '/'));
    }

    private function readBackupMeta(string $path): array
    {
        $metaFile = $path . DIRECTORY_SEPARATOR . 'meta.json';
        if (!File::exists($metaFile)) {
            return [];
        }
        $decoded = json_decode(File::get($metaFile), true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Move a single file. Falls back to copy+unlink across filesystems
     * (where rename() fails with EXDEV).
     */
    private function moveFile(string $src, string $dst): void
    {
        if (@rename($src, $dst)) {
            return;
        }
        if (!@copy($src, $dst)) {
            throw new RuntimeException("Could not move file $src → $dst");
        }
        @unlink($src);
    }

    /**
     * Move a directory tree. Same EXDEV fallback as moveFile().
     */
    private function moveTree(string $src, string $dst): void
    {
        if (@rename($src, $dst)) {
            return;
        }

        // Cross-filesystem: recursive copy then prune the source.
        File::ensureDirectoryExists($dst);
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($src, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $item) {
            $rel = substr($item->getPathname(), strlen($src) + 1);
            $target = $dst . DIRECTORY_SEPARATOR . $rel;
            if ($item->isDir()) {
                File::ensureDirectoryExists($target);
            } else {
                File::ensureDirectoryExists(dirname($target));
                if (!@copy($item->getPathname(), $target)) {
                    throw new RuntimeException("Could not copy $item → $target during cross-fs move");
                }
            }
        }
        $this->extractor->deleteDirectory($src);
    }

    private function dirSize(string $dir): int
    {
        if (!is_dir($dir)) return 0;

        $bytes = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $item) {
            if ($item->isFile()) {
                $bytes += $item->getSize();
            }
        }
        return $bytes;
    }

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

    private function humanBytes(int $bytes): string
    {
        $units = ['B', 'KiB', 'MiB', 'GiB'];
        $i = 0;
        $n = (float) $bytes;
        while ($n >= 1024 && $i < count($units) - 1) {
            $n /= 1024;
            $i++;
        }
        return number_format($n, $i === 0 ? 0 : 1) . ' ' . $units[$i];
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
