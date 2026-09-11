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

    /**
     * Where the gallery loads its own JS and CSS from.
     *
     * Files Gallery fetches its stylesheet, its 320 KB application bundle and
     * eleven libraries from `cdn.jsdelivr.net`, with **no integrity hashes**.
     * That is 680 KB of unverified third-party code executing inside an
     * authenticated admin session that can upload to and delete from the whole
     * media tree — a supply-chain exposure before you even count the latency
     * of thirteen requests to another continent.
     *
     * Rio, 2026-09-12: *"i thought we fully host the filemanager? so that we
     * don't get these request issues"*. He was right that we should be.
     *
     * `assets` is the gallery's own documented switch for this
     * (files.gallery/docs/self-hosted-assets), so nothing upstream is patched
     * and an upgrade stays a straight file replacement. Every asset URL in the
     * drop-in is built through one `U::assetspath()` call that reads this key.
     *
     * Enforced on every install rather than merely seeded, because the gallery
     * REWRITES its own config file whenever an admin saves its settings panel,
     * and doing so silently drops keys. That is how this and
     * `menu_max_depth` were lost on production before 2026-09-12.
     */
    private const ENFORCED_CONFIG = [
        // 🔴 `assets` is deliberately NOT here yet, and the vendored files in
        // resources/files-gallery/vendor are staged rather than in use.
        //
        // Setting it in 1.8.41 broke production. The page declares fourteen
        // assets, which is what was vendored and what the test checks — but
        // the 320 KB bundle lazy-loads TWELVE more packages the first time a
        // feature is used: uppy (drag-and-drop upload), plyr and hls.js
        // (playback), codemirror (editing, plus a syntax file per language
        // fetched on demand), pannellum, jsmediatags, headroom, marked,
        // intersection-observer, dayjs locales, flag icons, uppy locales.
        // Repointing the asset path without those means every one of them
        // 404s, and the feature dies with no visible error. Rio lost
        // drag-and-drop and the right-click menu on the live site.
        //
        // Finishing this needs the remaining packages vendored — roughly five
        // megabytes, much of it enumerated only inside the minified bundle —
        // and a check that FAILS when the gallery asks for something we do not
        // ship. Hand-listing is what failed: see the worklog for 2026-09-12.
        // Until that exists the gallery keeps using its CDN, which works.

        // Performance on large folders. Jambo's `movies` directory holds 1,761
        // title folders; with these at their defaults the gallery opens every
        // one of them twice before rendering — once hunting for a poster, once
        // checking for subfolders — which measured 1,583ms locally and timed
        // the request out entirely on the VPS.
        'folder_preview_image' => false,
        'menu_max_depth' => 1,
    ];

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

        File::ensureDirectoryExists("{$target}/_files/vendor");
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

        $selfHosted = $this->installVendorAssets($source, $target);
        $this->enforceConfig("{$target}/_files/config/config.php", $selfHosted);

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

    /**
     * Copy the vendored JS and CSS that index.php would otherwise fetch from
     * jsdelivr.
     *
     * Always refreshed, never honouring --force: these are ours, they are not
     * edited in place, and a stale copy against a rewritten index.php is a
     * blank admin screen. The npm-style layout (`pkg@version/dist/file.js`) is
     * preserved so the rewritten URLs are a pure prefix swap — which is what
     * keeps this a one-line change rather than a patch set.
     */
    private function installVendorAssets(string $source, string $target): bool
    {
        $vendorSource = "{$source}/vendor";

        if (! File::isDirectory($vendorSource)) {
            $this->warn('  · vendor/ assets missing — leaving the gallery on the CDN.');

            return false;
        }

        // Mirror, don't merge. Copying over the top leaves the previous
        // gallery version's assets sitting beside the new ones, which grows
        // without bound and — worse — makes the version check below pass on
        // files that are no longer the ones index.php asks for.
        //
        // Safe to delete outright: nothing but this method writes here, and if
        // the copy that follows fails, the version check withdraws `assets`
        // and the gallery falls back to the CDN rather than breaking.
        $vendorTarget = "{$target}/_files/vendor";
        File::deleteDirectory($vendorTarget);
        File::ensureDirectoryExists($vendorTarget);

        $expected = count(File::allFiles($vendorSource));
        $copied = 0;

        foreach (File::allFiles($vendorSource) as $file) {
            $relative = str_replace('\\', '/', $file->getRelativePathname());
            $dest = "{$target}/_files/vendor/{$relative}";

            File::ensureDirectoryExists(dirname($dest));

            // A copy that silently fails would leave index.php pointing at a
            // 404 and the file manager blank. Count what actually landed.
            if (File::copy($file->getPathname(), $dest) && File::size($dest) > 0) {
                $copied++;
            }
        }

        if ($copied !== $expected) {
            $this->warn("  · only {$copied}/{$expected} assets copied — leaving the gallery on the CDN.");

            return false;
        }

        // Every asset URL carries the gallery's own version number, so a
        // drop-in upgraded without re-vendoring would ask for a directory that
        // is not there and 404 all of it. Check the one file that must match
        // rather than trust the count above: forty-four files of the WRONG
        // version still count as forty-four.
        $version = $this->galleryVersion("{$target}/index.php");

        if ($version === null) {
            $this->warn('  · could not read the gallery version — leaving it on the CDN.');

            return false;
        }

        $bundle = "{$target}/_files/vendor/files.photo.gallery@{$version}/js/files.js";

        if (! File::exists($bundle)) {
            $this->warn("  · vendored assets are not for gallery {$version} — leaving it on the CDN.");
            $this->warn('    Re-vendor resources/files-gallery/vendor after upgrading the drop-in.');

            return false;
        }

        $this->info("  ✓ {$copied} self-hosted assets → _files/vendor (gallery {$version})");

        return true;
    }

    /** The Files Gallery version the installed drop-in reports, or null. */
    private function galleryVersion(string $indexPath): ?string
    {
        if (! File::exists($indexPath)) {
            return null;
        }

        return preg_match('/\$version = \'([0-9.]+)\'/', File::get($indexPath), $m)
            ? $m[1]
            : null;
    }

    /**
     * Put the keys in ENFORCED_CONFIG back into the live config file.
     *
     * The gallery regenerates `_files/config/config.php` from its settings
     * panel, keeping only what that panel knows about, so anything we ship
     * outside that set disappears the first time somebody saves a setting.
     * Seeding the file once is therefore not enough; this re-asserts the keys
     * on every install and leaves everything else exactly as the admin left
     * it.
     *
     * Edits the file as text rather than re-serialising it, so an admin's own
     * commented-out lines and ordering survive untouched — a rewritten config
     * that loses their notes would be the same failure in the other direction.
     */
    private function enforceConfig(string $path, bool $selfHosted): void
    {
        if (! File::exists($path)) {
            // Nothing to enforce yet; the copy above will have seeded it, or
            // the gallery will generate it on first run and the next install
            // will fix it up.
            return;
        }

        $config = File::get($path);
        $added = [];

        // Only claim the assets are local once they demonstrably are. Setting
        // this when the copy failed would point every script tag at a 404 and
        // leave an admin staring at a blank file manager — strictly worse than
        // the CDN this replaces.
        $enforced = self::ENFORCED_CONFIG;

        if (! $selfHosted) {
            unset($enforced['assets']);
        }

        // Withdraw any live `assets` line we are not actively enforcing.
        //
        // Two ways a config ends up holding one we do not want. The files went
        // missing on a box where an earlier install worked; or, as in 1.8.42,
        // we deliberately stopped enforcing it and every machine that took
        // 1.8.41 still has it written down. Both point the gallery at paths
        // that may not resolve, and both fail silently.
        //
        // Deleting a key nobody is asserting is the safe direction: worst
        // case the gallery falls back to its CDN, which works.
        if (! array_key_exists('assets', $enforced)) {
            $config = preg_replace('/^[ \t]*\'assets\'[ \t]*=>.*\R?/m', '', $config, 1);
        }

        foreach ($enforced as $key => $value) {
            // var_export rather than hand-rolled quoting: it is correct for
            // every type we might add later, and it escapes a quote inside a
            // value instead of writing a config file that will not parse.
            $line = '  ' . var_export((string) $key, true) . ' => ' . var_export($value, true) . ',';

            // Matches the key only when it is live — a leading `//` puts the
            // quote somewhere this pattern will not find it, so the gallery's
            // own commented sample lines are left alone.
            $quoted = preg_quote($key, '/');
            $pattern = "/^\\s*'{$quoted}'\\s*=>.*$/m";

            // Callbacks, not replacement strings: `preg_replace` reads `$` and
            // `\` in a replacement as back-references, so a value containing
            // either would be silently mangled into the config file.
            if (preg_match($pattern, $config)) {
                $config = preg_replace_callback($pattern, fn () => $line, $config, 1);

                continue;
            }

            $opened = preg_replace_callback(
                '/^return \[$/m',
                fn () => "return [\n" . $line,
                $config,
                1,
                $inserted
            );

            if ($inserted !== 1) {
                // The gallery wrote a shape we do not recognise. Leaving the
                // file alone and saying so beats corrupting the only config an
                // admin has.
                $this->warn("  · could not place '{$key}' — the config file has an unexpected shape.");

                continue;
            }

            $config = $opened;
            $added[] = $key;
        }

        File::put($path, $config);

        $this->info('  ✓ enforced ' . count($enforced) . ' gallery config keys'
            . ($added ? ' (added: ' . implode(', ', $added) . ')' : ''));
    }
}
