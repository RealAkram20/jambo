<?php

namespace Modules\SystemUpdate\Tests\Feature;

use Illuminate\Support\Facades\File;
use Modules\SystemUpdate\app\Services\DatabaseBackup;
use Modules\SystemUpdate\app\Services\UpdateManager;
use Modules\SystemUpdate\app\Services\ZipExtractor;
use Tests\TestCase;

/**
 * Locks in the orchestration guarantees: version comparison reads
 * version.txt correctly, manifest fetch finds the local file, retained
 * backups are listed newest-first, and restoreBackup() rejects names
 * that could escape the backup root.
 *
 * The full runUpdate() happy path needs a real release zip + HTTP
 * server, which is too heavy for a unit-style test. The smoke test
 * already covered the two dangerous primitives end-to-end (DB dump
 * and zip extraction); this suite covers the orchestration layer
 * that wires them together.
 *
 * Paths matter here: config('systemupdate.version_file') goes through
 * base_path() and config('systemupdate.file_backup.path') through
 * storage_path(), so passing absolute /tmp paths breaks both. We use
 * unique-id sub-directories under the real base/storage paths and
 * clean them up in tearDown to keep tests isolated.
 */
class UpdateManagerTest extends TestCase
{
    private string $id;
    private string $versionFileRel;     // relative to base_path
    private string $manifestPath;       // absolute, in storage/
    private string $backupRootRel;      // relative to storage_path
    private UpdateManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->id = uniqid();
        $this->versionFileRel = "storage/app/test-version-{$this->id}.txt";
        $this->manifestPath = storage_path("app/test-manifest-{$this->id}.json");
        $this->backupRootRel = "app/test-backups-{$this->id}";

        File::ensureDirectoryExists(storage_path($this->backupRootRel));

        config([
            'systemupdate.version_file' => $this->versionFileRel,
            'systemupdate.manifest.local' => $this->manifestPath,
            'systemupdate.manifest.remote' => null,
            'systemupdate.file_backup.path' => $this->backupRootRel,
            'systemupdate.file_backup.retain' => 3,
            'systemupdate.deny_patterns' => [],
        ]);

        // Real services with real (sandboxed) state. Easier to reason
        // about than mocking, and the underlying primitives are
        // already covered by their own tests.
        $extractor = new ZipExtractor(base_path());
        $dbBackup = new DatabaseBackup(storage_path("app/test-db-{$this->id}"));
        $this->manager = new UpdateManager($extractor, $dbBackup);
    }

    protected function tearDown(): void
    {
        @unlink(base_path($this->versionFileRel));
        @unlink($this->manifestPath);
        $this->rrmdir(storage_path($this->backupRootRel));
        $this->rrmdir(storage_path("app/test-db-{$this->id}"));
        parent::tearDown();
    }

    /* ------------------------------------------------------------------ */
    /* Version + manifest                                                 */
    /* ------------------------------------------------------------------ */

    public function test_current_version_falls_back_to_zero_when_file_missing(): void
    {
        $this->assertSame('0.0.0', $this->manager->currentVersion());
    }

    public function test_current_version_reads_version_file(): void
    {
        $this->writeVersion('1.2.3');
        $this->assertSame('1.2.3', $this->manager->currentVersion());
    }

    public function test_status_reports_no_update_when_manifest_unreachable(): void
    {
        $this->writeVersion('1.0.0');
        $status = $this->manager->status();

        $this->assertSame('1.0.0', $status['current']);
        $this->assertNull($status['latest']);
        $this->assertFalse($status['has_update']);
    }

    public function test_status_reports_update_when_manifest_is_newer(): void
    {
        $this->writeVersion('1.0.0');
        $this->writeManifest(['version' => '1.2.0', 'archive' => 'https://example.com/r.zip']);

        $status = $this->manager->status();

        $this->assertSame('1.0.0', $status['current']);
        $this->assertSame('1.2.0', $status['latest']);
        $this->assertTrue($status['has_update']);
        $this->assertSame('https://example.com/r.zip', $status['manifest']['archive']);
    }

    public function test_status_uses_proper_version_compare(): void
    {
        // String comparison would say "1.0.10" < "1.0.9" (lexicographic).
        // version_compare() must be used instead.
        $this->writeVersion('1.0.9');
        $this->writeManifest(['version' => '1.0.10', 'archive' => 'https://example.com/r.zip']);

        $this->assertTrue($this->manager->status()['has_update']);
    }

    public function test_status_ignores_older_manifests(): void
    {
        $this->writeVersion('2.0.0');
        $this->writeManifest(['version' => '1.5.0', 'archive' => 'https://example.com/r.zip']);

        $this->assertFalse($this->manager->status()['has_update']);
    }

    /* ------------------------------------------------------------------ */
    /* Backup retention                                                   */
    /* ------------------------------------------------------------------ */

    public function test_list_backups_returns_newest_first(): void
    {
        $this->seedBackup('20260101_000000_v1_to_v2', 1700000000);
        $this->seedBackup('20260201_000000_v2_to_v3', 1700100000);
        $this->seedBackup('20260301_000000_v3_to_v4', 1700200000);

        $listed = $this->manager->listBackups();

        $this->assertCount(3, $listed);
        $this->assertSame('20260301_000000_v3_to_v4', $listed[0]['name']);
        $this->assertSame('20260201_000000_v2_to_v3', $listed[1]['name']);
        $this->assertSame('20260101_000000_v1_to_v2', $listed[2]['name']);
    }

    public function test_list_backups_includes_meta_and_size(): void
    {
        $this->seedBackup('20260301_000000_v3_to_v4', 1700200000);

        $listed = $this->manager->listBackups();
        $this->assertCount(1, $listed);
        $this->assertSame('3', $listed[0]['version_from']);
        $this->assertSame('4', $listed[0]['version_to']);
        $this->assertTrue($listed[0]['has_db']);
        $this->assertGreaterThan(0, $listed[0]['size_bytes']);
    }

    public function test_restore_backup_rejects_path_traversal(): void
    {
        $cases = [
            '..',           // bare parent
            '.',            // bare current
            '../etc',       // contains slash (regex blocks)
            'foo/bar',      // contains slash
            '',             // empty
            'has space',    // space not in regex
            '..hidden',     // starts with .. — defense in depth
        ];

        foreach ($cases as $bad) {
            $result = $this->manager->restoreBackup($bad);
            $this->assertFalse($result['ok'], "Bad name '$bad' should be rejected");
            $this->assertNotNull($result['error']);
        }
    }

    public function test_restore_backup_returns_not_found_for_missing_name(): void
    {
        $result = $this->manager->restoreBackup('20260101_000000_does_not_exist');
        $this->assertFalse($result['ok']);
        $this->assertSame('Backup not found.', $result['error']);
    }

    /* ------------------------------------------------------------------ */
    /* Helpers                                                            */
    /* ------------------------------------------------------------------ */

    private function writeVersion(string $v): void
    {
        File::put(base_path($this->versionFileRel), "$v\n");
    }

    private function writeManifest(array $data): void
    {
        File::put($this->manifestPath, json_encode($data));
    }

    private function seedBackup(string $name, int $createdAt): void
    {
        $dir = storage_path($this->backupRootRel . '/' . $name);
        File::ensureDirectoryExists($dir . '/files');
        File::put($dir . '/files/dummy.txt', 'x');
        File::put($dir . '/db-dump.sql.gz', str_repeat('x', 256));
        preg_match('/_v([^_]+)_to_v(.+)$/', $name, $m);
        File::put($dir . '/meta.json', json_encode([
            'version_from' => $m[1] ?? null,
            'version_to'   => $m[2] ?? null,
            'created_at'   => $createdAt,
            'db_dump'      => 'db-dump.sql.gz',
        ]));
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
