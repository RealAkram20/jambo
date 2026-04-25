<?php

namespace Modules\SystemUpdate\Tests\Feature;

use Modules\SystemUpdate\app\Services\ZipExtractor;
use PHPUnit\Framework\TestCase;
use ZipArchive;

/**
 * Locks in the two safety properties this class is responsible for:
 *
 *   1. The deny list filters out anything a careless release zip might
 *      bundle that would clobber operator state (.env, storage/, etc.).
 *   2. Wrapper-folder normalisation strips the inevitable
 *      "RELEASE-X.Y.Z/" prefix without dropping the actual file path.
 *
 * Plain PHPUnit\Framework\TestCase (not Laravel's) — these are pure
 * filesystem ops with no framework dependency, so we skip the
 * application boot to keep the suite fast.
 */
class ZipExtractorTest extends TestCase
{
    private string $sandbox;

    protected function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('zip')) {
            $this->markTestSkipped('ext-zip is not loaded; run via `php -d extension=zip vendor/bin/phpunit`.');
        }

        $this->sandbox = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'zipextractor-' . uniqid();
        mkdir($this->sandbox, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->rrmdir($this->sandbox);
        parent::tearDown();
    }

    public function test_deny_list_filters_protected_paths(): void
    {
        $zipPath = $this->buildZip([
            'RELEASE-9.9.9/version.txt'           => "9.9.9\n",
            'RELEASE-9.9.9/app/Hello.php'         => "<?php\n",
            'RELEASE-9.9.9/.env'                  => "EVIL=1\n",
            'RELEASE-9.9.9/storage/app/poison.txt'=> "x",
            'RELEASE-9.9.9/vendor/foo/bar.php'    => "x",
            'RELEASE-9.9.9/modules_statuses.json' => "{\"hijacked\":true}",
        ]);

        // Pre-existing files that must survive
        file_put_contents($this->sandbox . '/.env', "ORIGINAL=1\n");
        mkdir($this->sandbox . '/storage/app', 0755, true);
        file_put_contents($this->sandbox . '/storage/app/precious.txt', "USER UPLOAD\n");

        $extractor = new ZipExtractor(
            projectRoot: $this->sandbox,
            backupPrefix: 'backup_',
            denyPatterns: [
                '#^\.env$#',
                '#^storage/#',
                '#^vendor/#',
                '#^modules_statuses\.json$#',
            ],
        );

        $extractor->extract($zipPath);

        // Allowed paths landed
        $this->assertFileExists($this->sandbox . '/version.txt');
        $this->assertSame("9.9.9\n", file_get_contents($this->sandbox . '/version.txt'));
        $this->assertFileExists($this->sandbox . '/app/Hello.php');

        // Denied paths did NOT overwrite / didn't appear
        $this->assertSame("ORIGINAL=1\n", file_get_contents($this->sandbox . '/.env'));
        $this->assertFileDoesNotExist($this->sandbox . '/vendor/foo/bar.php');
        $this->assertFileDoesNotExist($this->sandbox . '/modules_statuses.json');
        $this->assertFileDoesNotExist($this->sandbox . '/storage/app/poison.txt');

        // User upload preserved
        $this->assertSame("USER UPLOAD\n", file_get_contents($this->sandbox . '/storage/app/precious.txt'));

        // lastSkipped() reports the correct count
        $this->assertCount(4, $extractor->lastSkipped());
    }

    public function test_wrapper_folder_normalisation(): void
    {
        $extractor = new ZipExtractor($this->sandbox);

        $cases = [
            // RELEASE-style wrappers stripped
            'RELEASE-1.2.0/app/Foo.php'   => 'app/Foo.php',
            'RELEASE_1.2.0/app/Foo.php'   => 'app/Foo.php',
            'release-2.0.0/version.txt'   => 'version.txt',
            // Project-name wrappers stripped
            'JamboUpdate_v1_2_0/app/X.php' => 'app/X.php',
            // Bare semver-ish wrapper stripped
            'v1.2.0/app/X.php'            => 'app/X.php',
            // No wrapper — pass through
            'app/Foo.php'                 => 'app/Foo.php',
            'version.txt'                 => 'version.txt',
            // Junk
            '__MACOSX/whatever'           => null,
            ''                            => null,
        ];

        foreach ($cases as $input => $expected) {
            $this->assertSame(
                $expected,
                $extractor->normalizeEntryPath($input),
                "Input '$input' should normalise to " . var_export($expected, true)
            );
        }
    }

    public function test_restore_round_trip(): void
    {
        // Pre-existing file that gets overwritten + then restored
        file_put_contents($this->sandbox . '/foo.txt', "ORIGINAL\n");

        $zipPath = $this->buildZip([
            'foo.txt' => "REPLACED\n",
        ]);

        $extractor = new ZipExtractor(projectRoot: $this->sandbox);
        $backupDir = $extractor->extract($zipPath);

        $this->assertSame("REPLACED\n", file_get_contents($this->sandbox . '/foo.txt'));

        $extractor->restore($backupDir);

        $this->assertSame("ORIGINAL\n", file_get_contents($this->sandbox . '/foo.txt'));
    }

    private function buildZip(array $entries): string
    {
        $path = $this->sandbox . DIRECTORY_SEPARATOR . 'release.zip';
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);
        foreach ($entries as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();
        return $path;
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
