<?php

namespace Modules\SystemUpdate\app\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Dumps and restores the active database connection so an update can
 * roll back schema-destructive migrations.
 *
 * Why this exists: ZipExtractor backs up every FILE the update overwrites,
 * but it can't undo a migration that drops a column or renames a table.
 * Without a DB dump in hand, a single bad release wipes user content
 * permanently. This service runs `mysqldump` (or sqlite copy) before
 * `php artisan migrate --force`, so a failed update can restore the
 * pre-migration state.
 *
 * Supported drivers: mysql / mariadb (mysqldump), sqlite (file copy).
 * Other drivers fall through with a logged warning — the updater will
 * still proceed but without DB rollback safety, so the operator should
 * dump manually before triggering an update.
 */
class DatabaseBackup
{
    public function __construct(
        private readonly string $backupRoot,
    ) {
    }

    /**
     * Dump the active DB connection to a timestamped, gzipped file.
     * Returns the absolute path to the dump on success, or null when
     * the driver isn't supported (caller should warn but continue).
     *
     * @throws RuntimeException on dump failure for a supported driver
     *                          (so the updater aborts before migration).
     */
    public function dump(string $tag = 'pre-update'): ?string
    {
        $connection = config('database.default');
        $config = config("database.connections.$connection");

        if (!is_array($config) || empty($config['driver'])) {
            Log::warning('[updater] DB backup skipped — no connection config.');
            return null;
        }

        File::ensureDirectoryExists($this->backupRoot);

        $stamp = date('Ymd_His');
        $safeTag = preg_replace('/[^A-Za-z0-9._-]/', '_', $tag) ?: 'backup';

        return match ($config['driver']) {
            'mysql', 'mariadb' => $this->dumpMysql($config, $safeTag, $stamp),
            'sqlite' => $this->dumpSqlite($config, $safeTag, $stamp),
            default => $this->skipUnsupported($config['driver']),
        };
    }

    /**
     * Restore a previously created dump. Same dispatch rules as dump().
     *
     * @throws RuntimeException on restore failure.
     */
    public function restore(string $path): void
    {
        if (!File::exists($path)) {
            throw new RuntimeException("DB backup not found: $path");
        }

        $connection = config('database.default');
        $config = config("database.connections.$connection");
        $driver = $config['driver'] ?? null;

        match ($driver) {
            'mysql', 'mariadb' => $this->restoreMysql($config, $path),
            'sqlite' => $this->restoreSqlite($config, $path),
            default => throw new RuntimeException("Unsupported DB driver for restore: " . ($driver ?? 'unknown')),
        };
    }

    /* ---------------------------------------------------------------- */
    /* MySQL / MariaDB                                                  */
    /* ---------------------------------------------------------------- */

    private function dumpMysql(array $cfg, string $tag, string $stamp): string
    {
        $dest = $this->backupRoot . DIRECTORY_SEPARATOR . "db-{$tag}-{$stamp}.sql.gz";

        // Build mysqldump argv. --single-transaction makes the dump
        // consistent on InnoDB without locking writes; --quick streams
        // rows row-by-row so memory stays bounded on big tables;
        // --routines + --triggers preserve stored procs and triggers
        // that schema-only dumps would silently drop.
        $args = [
            $this->mysqldumpBinary(),
            '--host=' . ($cfg['host'] ?? '127.0.0.1'),
            '--port=' . ($cfg['port'] ?? 3306),
            '--user=' . ($cfg['username'] ?? 'root'),
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            '--default-character-set=utf8mb4',
            $cfg['database'] ?? '',
        ];

        // Pass the password via env (MYSQL_PWD) instead of --password=
        // so it never appears in `ps` output.
        $env = [];
        if (!empty($cfg['password'])) {
            $env['MYSQL_PWD'] = (string) $cfg['password'];
        }

        $process = new Process($args, base_path(), $env);
        $process->setTimeout(1800); // 30 min for very large DBs

        $gz = @gzopen($dest, 'wb6');
        if ($gz === false) {
            throw new RuntimeException("Could not open backup file for writing: $dest");
        }

        try {
            $process->start();
            // Stream stdout straight into the gzip file so we never load
            // the whole dump into PHP memory.
            foreach ($process as $type => $data) {
                if ($type === Process::OUT) {
                    gzwrite($gz, $data);
                }
            }
            gzclose($gz);
        } catch (Throwable $e) {
            gzclose($gz);
            @unlink($dest);
            throw new RuntimeException("mysqldump failed: " . $e->getMessage(), 0, $e);
        }

        if (!$process->isSuccessful()) {
            @unlink($dest);
            throw new RuntimeException(
                'mysqldump exited non-zero: ' . trim($process->getErrorOutput() ?: $process->getOutput())
            );
        }

        if (filesize($dest) === 0) {
            @unlink($dest);
            throw new RuntimeException('mysqldump produced an empty file — refusing to use as a backup.');
        }

        return $dest;
    }

    private function restoreMysql(array $cfg, string $path): void
    {
        $args = [
            $this->mysqlBinary(),
            '--host=' . ($cfg['host'] ?? '127.0.0.1'),
            '--port=' . ($cfg['port'] ?? 3306),
            '--user=' . ($cfg['username'] ?? 'root'),
            '--default-character-set=utf8mb4',
            $cfg['database'] ?? '',
        ];

        $env = [];
        if (!empty($cfg['password'])) {
            $env['MYSQL_PWD'] = (string) $cfg['password'];
        }

        $process = new Process($args, base_path(), $env);
        $process->setTimeout(1800);

        $gz = @gzopen($path, 'rb');
        if ($gz === false) {
            throw new RuntimeException("Could not open backup file for reading: $path");
        }

        $process->setInput((function () use ($gz) {
            while (!gzeof($gz)) {
                yield gzread($gz, 65536);
            }
        })());

        try {
            $process->run();
        } finally {
            gzclose($gz);
        }

        if (!$process->isSuccessful()) {
            throw new RuntimeException(
                'mysql restore failed: ' . trim($process->getErrorOutput() ?: $process->getOutput())
            );
        }
    }

    private function mysqldumpBinary(): string
    {
        return $this->resolveBinary(
            config('systemupdate.db_backup.mysqldump_binary', 'mysqldump'),
            'mysqldump',
        );
    }

    private function mysqlBinary(): string
    {
        return $this->resolveBinary(
            config('systemupdate.db_backup.mysql_binary', 'mysql'),
            'mysql',
        );
    }

    /**
     * Resolve a binary name to an absolute path that actually exists.
     *
     * Tries in order: the configured value (which may be an absolute
     * path or just a name), then a list of platform-typical locations.
     * Returns the configured value untouched if none of the fallbacks
     * exist either — Symfony Process will then fail with a clear
     * "command not found" instead of an opaque error.
     *
     * Saves the operator a frustrating debug session on first deploy:
     * Hostinger KVM VPS has /usr/bin/mysqldump in PATH (just works);
     * XAMPP on Windows ships it at C:\xampp\mysql\bin\mysqldump.exe
     * which isn't in PATH by default. Either is auto-handled.
     */
    private function resolveBinary(string $configured, string $shortName): string
    {
        // Configured absolute path that exists wins.
        if (str_contains($configured, DIRECTORY_SEPARATOR) || str_contains($configured, '/')) {
            if (is_file($configured)) {
                return $configured;
            }
        }

        // If the name resolves on PATH, let the OS find it.
        $isWindows = PHP_OS_FAMILY === 'Windows';
        $exe = $isWindows ? "$shortName.exe" : $shortName;

        $candidates = $isWindows ? [
            "C:\\xampp\\mysql\\bin\\$exe",
            "C:\\wamp64\\bin\\mysql\\mysql8.0\\bin\\$exe",
            "C:\\wamp\\bin\\mysql\\mysql8.0\\bin\\$exe",
            "C:\\Program Files\\MariaDB 10.11\\bin\\$exe",
            "C:\\Program Files\\MariaDB 10.6\\bin\\$exe",
            "C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\$exe",
            "C:\\laragon\\bin\\mysql\\mysql-8.0\\bin\\$exe",
        ] : [
            "/usr/bin/$shortName",
            "/usr/local/bin/$shortName",
            "/opt/homebrew/bin/$shortName",
            "/opt/mysql/bin/$shortName",
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        // Nothing matched — return the configured value and let the
        // Process invocation fail loudly with the actual reason.
        return $configured;
    }

    /* ---------------------------------------------------------------- */
    /* SQLite                                                           */
    /* ---------------------------------------------------------------- */

    private function dumpSqlite(array $cfg, string $tag, string $stamp): string
    {
        $source = $cfg['database'] ?? null;
        if (!$source || !File::exists($source)) {
            throw new RuntimeException("SQLite database file not found: " . ($source ?? 'null'));
        }

        $dest = $this->backupRoot . DIRECTORY_SEPARATOR . "db-{$tag}-{$stamp}.sqlite.gz";

        $in = @fopen($source, 'rb');
        $gz = @gzopen($dest, 'wb6');
        if ($in === false || $gz === false) {
            if ($in) fclose($in);
            if ($gz) gzclose($gz);
            throw new RuntimeException('Could not open SQLite source or destination for backup.');
        }

        try {
            while (!feof($in)) {
                $chunk = fread($in, 65536);
                if ($chunk === false) {
                    throw new RuntimeException('SQLite read error during backup.');
                }
                gzwrite($gz, $chunk);
            }
        } finally {
            fclose($in);
            gzclose($gz);
        }

        return $dest;
    }

    private function restoreSqlite(array $cfg, string $path): void
    {
        $dest = $cfg['database'] ?? null;
        if (!$dest) {
            throw new RuntimeException('SQLite restore: no database path in config.');
        }

        $gz = @gzopen($path, 'rb');
        $out = @fopen($dest, 'wb');
        if ($gz === false || $out === false) {
            if ($gz) gzclose($gz);
            if ($out) fclose($out);
            throw new RuntimeException('Could not open SQLite backup or destination for restore.');
        }

        try {
            while (!gzeof($gz)) {
                fwrite($out, gzread($gz, 65536));
            }
        } finally {
            gzclose($gz);
            fclose($out);
        }
    }

    /* ---------------------------------------------------------------- */

    private function skipUnsupported(string $driver): ?string
    {
        Log::warning("[updater] DB backup skipped — driver '$driver' not supported. Dump manually before updating.");
        return null;
    }
}
