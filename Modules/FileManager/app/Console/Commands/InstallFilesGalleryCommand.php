<?php

namespace Modules\FileManager\app\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Copies the vendored Files Gallery drop-in from the FileManager module's
 * resources/ dir into storage/app/public/media/, where the admin iframe at
 * /admin/file-manager loads it via the public storage symlink.
 *
 * Wired into composer.json's post-autoload-dump so a fresh clone installs
 * everything on the first `composer install`. Idempotent — safe to re-run;
 * pass --force to overwrite existing files if you updated the sources.
 */
class InstallFilesGalleryCommand extends Command
{
    protected $signature = 'filemanager:install {--force : Overwrite files that already exist in storage/app/public/media}';

    protected $description = 'Install the vendored Files Gallery drop-in into storage/app/public/media';

    public function handle(): int
    {
        $source = module_path('FileManager', 'resources/files-gallery');
        $target = storage_path('app/public/media');

        if (! File::exists($source)) {
            $this->error("Source directory not found: {$source}");

            return self::FAILURE;
        }

        $galleryDir = storage_path('app/public/gallery');

        $files = [
            'index.php'         => "{$target}/index.php",
            'config.php'        => "{$target}/_files/config/config.php",
            'custom.js'         => "{$target}/_files/js/custom.js",
            'gallery-readme.md' => "{$galleryDir}/README.md",
        ];

        File::ensureDirectoryExists("{$target}/_files/config");
        File::ensureDirectoryExists("{$target}/_files/js");
        File::ensureDirectoryExists($galleryDir);

        $installed = 0;
        $skipped = 0;

        foreach ($files as $name => $dest) {
            $src = "{$source}/{$name}";

            if (! File::exists($src)) {
                $this->warn("  · source missing: {$name}");

                continue;
            }

            if (File::exists($dest) && ! $this->option('force')) {
                $this->line("  · {$name} already present (use --force to overwrite)");
                $skipped++;

                continue;
            }

            File::copy($src, $dest);
            $rel = str_replace(base_path().DIRECTORY_SEPARATOR, '', $dest);
            $this->info("  ✓ {$name} → {$rel}");
            $installed++;
        }

        $this->newLine();
        $this->info("Installed {$installed}, skipped {$skipped}.");

        if (! File::exists("{$target}/index.php")) {
            $this->warn('index.php is not present — /admin/file-manager will show the "missing" state.');
        }

        return self::SUCCESS;
    }
}
