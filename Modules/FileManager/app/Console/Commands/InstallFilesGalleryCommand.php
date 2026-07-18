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
            // Admin gate required at the top of index.php. Security policy,
            // so it's force-overwritten on every install (see below) — the
            // served drop-in must never run without the current guard.
            'fm-guard.php'      => "{$target}/fm-guard.php",
            'config.php'        => "{$target}/_files/config/config.php",
            'custom.js'         => "{$target}/_files/js/custom.js",
            'gallery-readme.md' => "{$galleryDir}/README.md",

            // Security drop-ins. media.htaccess cookie-gates the Files
            // Gallery drop-in so direct hits on /storage/media/*
            // can't bypass the admin role gate. gallery.htaccess
            // blocks PHP execution + directory listing on the public
            // gallery tree. Both are force-overwritten on every
            // install (see --force handling below) so security rules
            // can't drift behind the code.
            'media.htaccess'    => "{$target}/.htaccess",
            'gallery.htaccess'  => "{$galleryDir}/.htaccess",
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

            // Always refresh .htaccess rules and the admin gate — they're
            // security policy and must not drift from the repo. Everything
            // else honours the --force flag so admins don't lose custom edits.
            $forceOverwrite = $this->option('force')
                || str_ends_with($name, '.htaccess')
                || $name === 'fm-guard.php';

            if (File::exists($dest) && ! $forceOverwrite) {
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

        $indexPath = "{$target}/index.php";

        if (! File::exists($indexPath)) {
            $this->warn('index.php is not present — /admin/file-manager will show the "missing" state.');

            return self::SUCCESS;
        }

        // Guarantee the admin gate is wired in, even on an upgrade where a
        // pre-existing index.php was left untouched (no --force). A guard
        // file that isn't require()d protects nothing, so if the line is
        // missing we prepend it rather than leave the gallery exposed.
        $index = File::get($indexPath);
        if (! str_contains($index, "require __DIR__ . '/fm-guard.php';")) {
            $guardLine = "<?php\n\n"
                . "// Jambo admin gate. Validates the signed access token and 403s any\n"
                . "// unauthenticated request BEFORE any Files Gallery code runs.\n"
                . "require __DIR__ . '/fm-guard.php';\n";
            $index = preg_replace('/^<\?php\s*/', $guardLine, $index, 1);
            File::put($indexPath, $index);
            $this->info('  ✓ wired fm-guard.php into index.php');
        }

        return self::SUCCESS;
    }
}
