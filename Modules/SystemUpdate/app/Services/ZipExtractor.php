<?php

namespace Modules\SystemUpdate\app\Services;

use RuntimeException;
use ZipArchive;

/**
 * Extracts an update zip into the project root with per-file backup.
 *
 * Handles the three annoying realities of update zips produced in the
 * wild:
 *
 *   1. Different zip tools produce different wrapper folder names
 *      ("RELEASE-1.2.0/", "JamboUpdate_v1_2_0/", sometimes nothing).
 *      We strip the first path component if it looks like a wrapper.
 *   2. macOS zip tools leave behind "__MACOSX/" junk.
 *   3. A file we're about to overwrite must be backed up BEFORE the
 *      overwrite, so a subsequent migration failure can roll back.
 *
 * The backup directory is returned from extract(); the caller is
 * responsible for either deleting it on success or calling restore() on
 * failure.
 */
class ZipExtractor
{
    /**
     * Forward-slash relative paths matched against a regex deny list.
     * Anything that matches is silently dropped from the extraction —
     * a release zip cannot overwrite a user's `.env`, their uploads
     * under `storage/`, the `public/storage` symlink, the live SQLite
     * file, or vendor / node_modules even if a careless build script
     * happened to bundle them.
     */
    private array $skipped = [];

    public function __construct(
        private readonly string $projectRoot,
        private readonly string $backupPrefix = 'backup_',
        private readonly array $denyPatterns = []
    ) {
    }

    /**
     * Extract $zipPath into the project root, creating a backup of any
     * file that gets overwritten. Entries that match the deny list are
     * skipped and recorded — fetch them with lastSkipped().
     *
     * @throws RuntimeException if the zip can't be opened or any write fails.
     */
    public function extract(string $zipPath): string
    {
        if (!file_exists($zipPath)) {
            throw new RuntimeException("Update package not found: $zipPath");
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException("Could not open update package: $zipPath");
        }

        $this->skipped = [];

        $backupDir = $this->projectRoot
            . DIRECTORY_SEPARATOR
            . $this->backupPrefix
            . date('Ymd_His');

        if (!mkdir($backupDir, 0755, true) && !is_dir($backupDir)) {
            throw new RuntimeException("Could not create backup directory: $backupDir");
        }

        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->getNameIndex($i);
                if ($entry === false) {
                    continue;
                }

                // Skip directory entries; we'll create directories as needed
                // when writing files.
                if (str_ends_with($entry, '/')) {
                    continue;
                }

                $target = $this->normalizeEntryPath($entry);
                if ($target === null) {
                    continue;
                }

                if ($this->isDenied($target)) {
                    $this->skipped[] = $target;
                    continue;
                }

                $dest = $this->projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $target);

                // Back up an existing file before overwriting it.
                if (file_exists($dest) && !is_dir($dest)) {
                    $backupPath = $backupDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $target);
                    $this->ensureDirectory(dirname($backupPath));
                    if (!@copy($dest, $backupPath)) {
                        throw new RuntimeException("Could not back up $dest");
                    }
                }

                $this->ensureDirectory(dirname($dest));

                $stream = $zip->getStream($entry);
                if ($stream === false) {
                    throw new RuntimeException("Could not read $entry from zip");
                }

                $bytes = stream_get_contents($stream);
                fclose($stream);

                if (file_put_contents($dest, $bytes) === false) {
                    throw new RuntimeException("Could not write $dest");
                }
            }
        } catch (\Throwable $e) {
            $zip->close();
            throw $e;
        }

        $zip->close();
        return $backupDir;
    }

    /**
     * Paths from the most recent extract() call that were skipped because
     * they matched the deny list. Empty after a clean release.
     *
     * @return string[]
     */
    public function lastSkipped(): array
    {
        return $this->skipped;
    }

    private function isDenied(string $relativePath): bool
    {
        foreach ($this->denyPatterns as $pattern) {
            if (@preg_match($pattern, $relativePath) === 1) {
                return true;
            }
        }
        return false;
    }

    /**
     * Restore everything that lived in $backupDir back into the project
     * root. Intended to be called when a migration or version write
     * fails mid-update.
     */
    public function restore(string $backupDir): void
    {
        if (!is_dir($backupDir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($backupDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                continue;
            }

            $relative = substr($item->getPathname(), strlen($backupDir) + 1);
            $dest = $this->projectRoot . DIRECTORY_SEPARATOR . $relative;
            $this->ensureDirectory(dirname($dest));
            @copy($item->getPathname(), $dest);
        }
    }

    /**
     * Recursively delete a directory (tmp, backup_, etc.).
     */
    public function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }

        @rmdir($dir);
    }

    /**
     * Strip wrapper directories and junk from a zip entry name.
     *
     *   "RELEASE-1.2.0/app/Foo.php"   → "app/Foo.php"
     *   "JamboUpdate_v1_2_0/app/..."  → "app/..."
     *   "__MACOSX/..."                → null (skip)
     *   "app/..."                     → "app/..."
     *
     * Returns null for entries that should be skipped.
     */
    public function normalizeEntryPath(string $name): ?string
    {
        $name = ltrim($name, '/');

        if ($name === '' || str_starts_with($name, '__MACOSX/')) {
            return null;
        }

        $parts = explode('/', $name);
        if (count($parts) < 2) {
            // No subdirectory — e.g. "version.txt" at root.
            return $name;
        }

        $first = $parts[0];
        $wrapperLike = (bool) preg_match(
            '/^(RELEASE[-_]|release[-_]|v?\d+[._]\d+|[A-Za-z]+[_-]?v\d+|[A-Za-z]+_\d+)/',
            $first
        );

        if ($wrapperLike) {
            array_shift($parts);
        }

        $result = implode('/', $parts);
        return $result !== '' ? $result : null;
    }

    private function ensureDirectory(string $dir): void
    {
        if (is_dir($dir)) {
            return;
        }
        if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException("Could not create directory: $dir");
        }
    }
}
