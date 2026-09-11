<?php

namespace Modules\FileManager\Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * `filemanager:install` must be able to put the gallery's settings back.
 *
 * Files Gallery regenerates `_files/config/config.php` from its own settings
 * panel and keeps only what that panel knows about, so every key we ship
 * disappears the first time an admin saves anything there. That is not a
 * theory: it is how `menu_max_depth` was lost on production, which is why the
 * `movies` folder — 1,761 title directories — stopped loading at all.
 *
 * So the install command re-asserts three keys on every run, and these tests
 * pin the behaviour that makes that safe rather than merely present:
 *
 *   - it survives the settings panel wiping the file
 *   - it never duplicates a key, however many times it runs
 *   - it leaves the admin's own lines alone
 *   - and it REFUSES to claim self-hosted assets when the files are not there,
 *     because a wrong `assets` path is a blank file manager, which is worse
 *     than the CDN it replaces
 */
class GalleryConfigEnforcementTest extends TestCase
{
    private string $configPath;

    private string $vendorSource;

    private ?string $originalConfig = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configPath = storage_path('app/public/media/_files/config/config.php');
        $this->vendorSource = module_path('FileManager', 'resources/files-gallery/vendor');

        if (File::exists($this->configPath)) {
            $this->originalConfig = File::get($this->configPath);
        }
    }

    protected function tearDown(): void
    {
        // Put the developer's own gallery back exactly as it was. This test
        // writes to storage/, not to a fixture, because the command's whole
        // job is editing that real file.
        if ($this->originalConfig !== null) {
            File::put($this->configPath, $this->originalConfig);
        }

        parent::tearDown();
    }

    /** @return array<string, mixed> */
    private function readConfig(): array
    {
        $this->assertFileExists($this->configPath);

        // `require` caches, so read and evaluate the current bytes instead —
        // otherwise the second assertion in a test sees the first one's file.
        $config = eval('?>' . File::get($this->configPath));

        $this->assertIsArray($config, 'The gallery config must still be valid PHP returning an array.');

        return $config;
    }

    public function test_the_keys_come_back_after_the_settings_panel_wipes_them(): void
    {
        $this->artisan('filemanager:install')->assertSuccessful();

        // Exactly what the panel leaves behind: a valid file, our keys gone.
        $wiped = preg_replace(
            '/^[ \t]*\'(assets|menu_max_depth|folder_preview_image)\'[ \t]*=>.*\R?/m',
            '',
            File::get($this->configPath)
        );
        File::put($this->configPath, $wiped);

        $this->assertArrayNotHasKey('menu_max_depth', $this->readConfig(), 'Precondition: the wipe must remove the key.');

        $this->artisan('filemanager:install')->assertSuccessful();

        $config = $this->readConfig();
        $this->assertSame(1, $config['menu_max_depth'] ?? null);
        $this->assertFalse($config['folder_preview_image'] ?? null);
    }

    public function test_running_it_repeatedly_never_duplicates_a_key(): void
    {
        foreach (range(1, 3) as $ignored) {
            $this->artisan('filemanager:install')->assertSuccessful();
        }

        $contents = File::get($this->configPath);

        foreach (['assets', 'menu_max_depth', 'folder_preview_image'] as $key) {
            $this->assertSame(
                1,
                preg_match_all('/^[ \t]*\'' . preg_quote($key, '/') . '\'[ \t]*=>/m', $contents),
                "'{$key}' must appear exactly once — a duplicate means the file grows on every deploy."
            );
        }
    }

    public function test_an_admins_own_lines_are_left_alone(): void
    {
        $this->artisan('filemanager:install')->assertSuccessful();

        $marker = "  'image_resize_quality' => 71,";
        File::put($this->configPath, str_replace('return [', "return [\n" . $marker, File::get($this->configPath)));

        $this->artisan('filemanager:install')->assertSuccessful();

        $this->assertStringContainsString(
            $marker,
            File::get($this->configPath),
            'Enforcing our keys must not rewrite the rest of the file.'
        );
        $this->assertSame(71, $this->readConfig()['image_resize_quality'] ?? null);
    }

    public function test_it_refuses_to_claim_self_hosting_when_the_assets_are_missing(): void
    {
        $this->artisan('filemanager:install')->assertSuccessful();
        $this->assertSame('_files/vendor/', $this->readConfig()['assets'] ?? null);

        $hidden = $this->vendorSource . '-hidden-by-test';
        File::moveDirectory($this->vendorSource, $hidden);

        try {
            $this->artisan('filemanager:install')->assertSuccessful();

            $config = $this->readConfig();

            // Falling back to the CDN is correct here. Pointing at a directory
            // that is not there would render every script tag as a 404.
            $this->assertArrayNotHasKey(
                'assets',
                $config,
                'With no vendored assets the key must be withdrawn, not left pointing at nothing.'
            );

            // The performance keys are unrelated to self-hosting and must stay.
            $this->assertSame(1, $config['menu_max_depth'] ?? null);
        } finally {
            File::moveDirectory($hidden, $this->vendorSource);
        }

        $this->artisan('filemanager:install')->assertSuccessful();
        $this->assertSame('_files/vendor/', $this->readConfig()['assets'] ?? null, 'It must recover once the assets are back.');
    }

    public function test_it_refuses_to_self_host_assets_from_a_different_gallery_version(): void
    {
        $this->artisan('filemanager:install')->assertSuccessful();
        $this->assertSame('_files/vendor/', $this->readConfig()['assets'] ?? null);

        $source = File::get(module_path('FileManager', 'resources/files-gallery/index.php'));
        preg_match('/\$version = \'([0-9.]+)\'/', $source, $version);
        $this->assertNotEmpty($version[1] ?? null);

        // Exactly what upgrading the drop-in without re-vendoring looks like:
        // the right NUMBER of files, under the wrong version directory.
        $current = "{$this->vendorSource}/files.photo.gallery@{$version[1]}";
        $stale = "{$this->vendorSource}/files.photo.gallery@0.0.1-stale";
        File::moveDirectory($current, $stale);

        try {
            $this->artisan('filemanager:install')->assertSuccessful();

            $this->assertArrayNotHasKey(
                'assets',
                $this->readConfig(),
                'Assets for another version must not be claimed as self-hosted — every URL would 404.'
            );
        } finally {
            File::moveDirectory($stale, $current);
        }

        $this->artisan('filemanager:install')->assertSuccessful();
        $this->assertSame('_files/vendor/', $this->readConfig()['assets'] ?? null);
    }

    public function test_every_asset_the_gallery_asks_for_is_vendored(): void
    {
        $index = module_path('FileManager', 'resources/files-gallery/index.php');
        $this->assertFileExists($index);

        preg_match_all(
            "/'([a-z0-9._-]+@[0-9.]+\/[^']+\.(?:js|css))'/i",
            File::get($index),
            $matches
        );

        $this->assertNotEmpty($matches[1], 'Expected to find the gallery\'s asset list in index.php.');

        foreach ($matches[1] as $asset) {
            $this->assertFileExists(
                $this->vendorSource . '/' . $asset,
                "index.php loads {$asset}; it must exist under resources/files-gallery/vendor or self-hosting 404s."
            );
        }
    }

    public function test_every_language_the_gallery_offers_is_vendored(): void
    {
        $source = File::get(module_path('FileManager', 'resources/files-gallery/index.php'));

        preg_match('/langs = \[([^\]]+)\]/', $source, $langs);
        $this->assertNotEmpty($langs[1] ?? null, 'Expected the language list in index.php.');

        preg_match('/\$version = \'([0-9.]+)\'/', $source, $version);
        $this->assertNotEmpty($version[1] ?? null, 'Expected the gallery version in index.php.');

        foreach (explode(',', $langs[1]) as $lang) {
            $code = trim($lang, " \t\n\r'\"");

            // English is built in and never fetched, so it has no file.
            if ($code === '' || $code === 'en') {
                continue;
            }

            $this->assertFileExists(
                "{$this->vendorSource}/files.photo.gallery@{$version[1]}/lang/{$code}.json",
                "The gallery offers '{$code}'. Self-hosted, a missing file means that admin silently "
                . 'falls back to English — which is how a language regression hides after a version bump.'
            );
        }
    }
}
