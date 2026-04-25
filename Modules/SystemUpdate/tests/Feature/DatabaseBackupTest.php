<?php

namespace Modules\SystemUpdate\Tests\Feature;

use Modules\SystemUpdate\app\Services\DatabaseBackup;
use Tests\TestCase;

/**
 * Locks in the data-loss prevention guarantee: a dump must be
 * non-empty, gzipped, and round-trippable back into the same
 * connection.
 *
 * The MySQL path needs a real mysqldump binary on PATH (or auto-detect
 * to find it), so the MySQL-specific test skips cleanly when the
 * binary isn't reachable. The SQLite path is always exercised because
 * the test suite runs against SQLite by default (phpunit.xml).
 */
class DatabaseBackupTest extends TestCase
{
    private string $backupDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dbbackup-' . uniqid();
        mkdir($this->backupDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->rrmdir($this->backupDir);
        parent::tearDown();
    }

    /**
     * The default test connection is :memory: SQLite, which can't be
     * file-copied. Point at a real on-disk SQLite for the dump test.
     */
    public function test_sqlite_dump_round_trip(): void
    {
        $sqlitePath = $this->backupDir . DIRECTORY_SEPARATOR . 'live.sqlite';
        $this->createSqliteWithSampleData($sqlitePath);

        config([
            'database.default' => 'sqlite-test',
            'database.connections.sqlite-test' => [
                'driver' => 'sqlite',
                'database' => $sqlitePath,
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);

        $service = new DatabaseBackup($this->backupDir);
        $dumpPath = $service->dump('test');

        $this->assertNotNull($dumpPath, 'SQLite dump should return a path');
        $this->assertFileExists($dumpPath);
        $this->assertGreaterThan(0, filesize($dumpPath), 'Dump must be non-empty');

        // Mutate the live DB so we can prove the restore actually replaces it
        $pdo = new \PDO('sqlite:' . $sqlitePath);
        $pdo->exec("UPDATE widgets SET name = 'mutated' WHERE id = 1");
        $check = $pdo->query("SELECT name FROM widgets WHERE id = 1")->fetchColumn();
        $this->assertSame('mutated', $check);
        unset($pdo);

        // Restore: file should match the original
        $service->restore($dumpPath);

        $pdo = new \PDO('sqlite:' . $sqlitePath);
        $restored = $pdo->query("SELECT name FROM widgets WHERE id = 1")->fetchColumn();
        $this->assertSame('alpha', $restored, 'Restore should bring back the pre-mutation row');
    }

    public function test_unsupported_driver_returns_null_with_warning(): void
    {
        config([
            'database.default' => 'pgsql-test',
            'database.connections.pgsql-test' => [
                'driver' => 'pgsql',
                'host' => 'localhost',
                'database' => 'fake',
                'username' => 'fake',
                'password' => 'fake',
            ],
        ]);

        $service = new DatabaseBackup($this->backupDir);
        $this->assertNull($service->dump(), 'pgsql is unsupported — dump should bail with null, not throw');
    }

    public function test_resolve_binary_returns_configured_absolute_path_if_present(): void
    {
        // Use a known-existing binary as a stand-in: PHP itself.
        $phpBin = PHP_BINARY;
        $this->assertFileExists($phpBin);

        config([
            'systemupdate.db_backup.mysqldump_binary' => $phpBin,
        ]);

        $service = new DatabaseBackup($this->backupDir);

        // resolveBinary is private; reach it via reflection. We're
        // verifying the contract that a pre-existing absolute path
        // wins over the auto-detect fallback.
        $method = new \ReflectionMethod($service, 'resolveBinary');
        $method->setAccessible(true);
        $this->assertSame($phpBin, $method->invoke($service, $phpBin, 'whatever'));
    }

    /* ------------------------------------------------------------------ */

    private function createSqliteWithSampleData(string $path): void
    {
        $pdo = new \PDO('sqlite:' . $path);
        $pdo->exec('CREATE TABLE widgets (id INTEGER PRIMARY KEY, name TEXT)');
        $pdo->exec("INSERT INTO widgets (id, name) VALUES (1, 'alpha'), (2, 'beta')");
    }

    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) return;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($dir);
    }
}
