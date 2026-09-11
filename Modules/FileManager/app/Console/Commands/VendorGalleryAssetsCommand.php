<?php

namespace Modules\FileManager\app\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

/**
 * Download every asset Files Gallery can ask for into the repo, so the
 * file manager runs with no external requests.
 *
 * **This exists because hand-listing failed.** 1.8.41 vendored the fourteen
 * assets the gallery's PAGE declares and pointed the asset path at our own
 * server. The 320 KB bundle then lazy-loads twelve further packages the first
 * time a feature is used, none of which were vendored, so drag-and-drop and
 * the right-click actions died on production — silently, because a 404 on a
 * lazily-injected `<script>` raises nothing anybody sees.
 *
 * So the list is derived from the bundle rather than written down:
 *
 *   - the `<script>`/`<link>` tags in `index.php`
 *   - the plugin registry inside `files.js` (`name: "pkg@version"`) together
 *     with the `src:`/`css:` arrays that say which files each needs
 *   - the three per-language families, resolved against the gallery's own
 *     `langs` list rather than every locale on npm
 *
 * Re-run it after upgrading the drop-in. `--check` reports what is missing
 * without downloading, which is what the test uses.
 */
class VendorGalleryAssetsCommand extends Command
{
    protected $signature = 'filemanager:vendor
        {--check : Report what is missing and change nothing}
        {--force : Re-download files that are already present}';

    protected $description = 'Vendor every Files Gallery asset from jsdelivr into the repo';

    private const CDN = 'https://cdn.jsdelivr.net/npm/';

    /**
     * Files each plugin package needs, keyed by the registry name inside
     * files.js. Read off the `Y.plugins` object in the bundle; the versions
     * are NOT hard-coded here — they come from the registry at run time, so an
     * upgrade only needs this command re-run.
     */
    private const PLUGIN_FILES = [
        'codemirror' => [
            '/lib/codemirror.min.js',
            '/lib/codemirror.css',
            '/mode/meta.js',
            '/addon/mode/loadmode.js',
            '/addon/mode/overlay.js',
            '/addon/selection/active-line.js',
        ],
        'marked' => ['/marked.min.js'],
        'marked_base_url' => ['/lib/index.umd.min.js'],
        'headroom' => ['/dist/headroom.min.js'],
        'uppy' => ['/dist/uppy.min.js', '/dist/uppy.min.css'],
        'pannellum' => ['/build/pannellum.min.js'],
        'plyr' => ['/dist/plyr.min.js', '/dist/plyr.css'],
        'hls' => ['/dist/hls.min.js'],
        'jsmediatags' => ['/dist/jsmediatags.min.js'],
    ];

    public function handle(): int
    {
        $source = module_path('FileManager', 'resources/files-gallery');
        $index = "{$source}/index.php";

        if (! File::exists($index)) {
            $this->error("Gallery source not found: {$index}");

            return self::FAILURE;
        }

        $php = File::get($index);
        $version = $this->match('/\$version = \'([0-9.]+)\'/', $php);

        if ($version === null) {
            $this->error('Could not read the gallery version from index.php.');

            return self::FAILURE;
        }

        $bundleRelative = "files.photo.gallery@{$version}/js/files.js";
        $bundlePath = "{$source}/vendor/{$bundleRelative}";

        $paths = $this->declaredAssets($php, $version);

        // The bundle lists the lazy-loaded packages, so it must be present
        // before the rest can be derived. On a fresh vendor run it arrives
        // with the declared assets above.
        if (! File::exists($bundlePath) && ! $this->option('check')) {
            $this->line('Fetching the bundle first, so its plugin list can be read…');
            $this->download($bundleRelative, "{$source}/vendor/{$bundleRelative}");
        }

        if (File::exists($bundlePath)) {
            $paths = array_merge($paths, $this->lazyAssets(File::get($bundlePath), $php, $version));
        } else {
            $this->warn('Bundle not vendored yet — lazy-loaded packages cannot be derived.');
        }

        $paths = array_values(array_unique($paths));
        sort($paths);

        return $this->option('check')
            ? $this->report($paths, "{$source}/vendor")
            : $this->fetchAll($paths, "{$source}/vendor");
    }

    /** Assets named directly in index.php's script and link tags. */
    private function declaredAssets(string $php, string $version): array
    {
        preg_match_all("/'([a-z0-9._-]+@[0-9.]+\/[^']+\.(?:js|css))'/i", $php, $m);

        $paths = $m[1];

        // Two are built from the version rather than written out.
        $paths[] = "files.photo.gallery@{$version}/js/files.js";
        $paths[] = "files.photo.gallery@{$version}/css/files.css";

        // Translations, for every language the gallery offers. English is
        // built in and never fetched.
        foreach ($this->languages($php) as $lang) {
            $paths[] = "files.photo.gallery@{$version}/lang/{$lang}.json";
        }

        return $paths;
    }

    /**
     * Everything the bundle fetches at runtime: the plugin packages, plus the
     * three per-language families.
     */
    private function lazyAssets(string $bundle, string $php, string $version): array
    {
        $paths = [];
        $registry = $this->pluginRegistry($bundle);

        foreach (self::PLUGIN_FILES as $name => $files) {
            if (! isset($registry[$name])) {
                $this->warn("  · plugin '{$name}' is no longer in the bundle — skipping.");

                continue;
            }

            foreach ($files as $file) {
                $paths[] = $registry[$name] . $file;
            }
        }

        // CodeMirror loads a syntax file per language on demand, from
        // `mode/%N/%N.js`. Which ones are needed depends on what a viewer
        // opens, so all of them are vendored — 125 files, about a megabyte.
        if (isset($registry['codemirror'])) {
            foreach ($this->codemirrorModes($registry['codemirror']) as $mode) {
                $paths[] = $mode;
            }
        }

        // intersection-observer is loaded by path, not through the registry.
        if (preg_match('#"(intersection-observer@[0-9.]+/[^"]+\.js)"#', $bundle, $m)) {
            $paths[] = $m[1];
        }

        $languages = $this->languages($php);

        // A date locale per language the gallery offers.
        if (preg_match('#"(dayjs@[0-9.]+/locale/)"#', $bundle, $m)) {
            foreach ($languages as $lang) {
                $paths[] = $m[1] . $lang . '.js';
            }
        }

        // A flag for the language picker — but the picker maps some languages
        // to a different country: `ar` is drawn with Saudi Arabia's flag, not
        // Argentina's, and `en` with the United Kingdom's. Deriving these from
        // the language code alone downloads files that exist and are WRONG,
        // which is worse than a 404 because nothing reports it.
        if (preg_match('#"(flag-icons@[0-9.]+/flags/1x1/)"#', $bundle, $m)) {
            $flags = $this->flagOverrides($bundle);

            foreach ($languages as $lang) {
                $paths[] = $m[1] . ($flags[$lang] ?? $lang) . '.svg';
            }
        }

        // The uploader's own translations are keyed by full locale
        // (`ar_SA`, not `ar`) and the bundle carries the list it can ask for,
        // so take it verbatim rather than deriving it.
        if (preg_match('#"(@uppy/locales@[0-9.]+/dist/)"#', $bundle, $prefix)
            && preg_match('/\["ar_SA"(?:,"[a-zA-Z_]+")+\]/', $bundle, $list)) {
            preg_match_all('/"([a-zA-Z_]+)"/', $list[0], $locales);

            foreach ($locales[1] as $locale) {
                $paths[] = $prefix[1] . $locale . '.min.js';
            }
        }

        return $paths;
    }

    /**
     * Languages whose flag is not their own code, as the picker maps them.
     *
     * Looks like `{ar:"sa",cs:"cz",…,en:"gb"}` in the bundle, sitting just
     * before the flag URL is built.
     */
    private function flagOverrides(string $bundle): array
    {
        if (! preg_match('/\{(?:[a-z]{2}:"[a-z]{2}",){5,}[a-z]{2}:"[a-z]{2}"\}/', $bundle, $m)) {
            return [];
        }

        preg_match_all('/([a-z]{2}):"([a-z]{2})"/', $m[0], $pairs, PREG_SET_ORDER);

        $map = [];

        foreach ($pairs as [, $lang, $country]) {
            $map[$lang] = $country;
        }

        return $map;
    }

    /** The `name: "pkg@version"` map the bundle keeps its plugins in. */
    private function pluginRegistry(string $bundle): array
    {
        if (! preg_match('/\{codemirror:"[^"]+",(?:[a-z_]+:"[^"]+",?)+\}/', $bundle, $m)) {
            return [];
        }

        preg_match_all('/([a-z_]+):"([^"]+)"/', $m[0], $pairs, PREG_SET_ORDER);

        $registry = [];

        foreach ($pairs as [, $name, $package]) {
            $registry[$name] = $package;
        }

        return $registry;
    }

    /**
     * Every `mode/NAME/NAME.js` CodeMirror publishes for this version.
     *
     * Cached on disk beside the assets, for two reasons. `--check` runs inside
     * the test suite and must not depend on a network call to a third party —
     * a check that fails when someone's wifi drops teaches people to ignore
     * it. And the list only changes when the package version does, which is
     * precisely when a real vendor run happens.
     */
    private function codemirrorModes(string $package): array
    {
        $cache = module_path('FileManager', "resources/files-gallery/vendor/{$package}/MODES.txt");

        if (File::exists($cache)) {
            return array_values(array_filter(array_map('trim', explode("\n", File::get($cache)))));
        }

        if ($this->option('check')) {
            // Nothing cached and we are not allowed to fetch. Say so rather
            // than silently reporting a short list as complete.
            $this->warn('  · CodeMirror mode list not cached — run `filemanager:vendor` to build it.');

            return [];
        }

        $listing = Http::timeout(60)->get('https://data.jsdelivr.com/v1/packages/npm/' . $package);

        if (! $listing->successful()) {
            $this->warn('  · could not list CodeMirror modes — syntax highlighting may fall back.');

            return [];
        }

        $modes = [];

        foreach ($listing->json('files') ?? [] as $node) {
            if (($node['type'] ?? '') !== 'directory' || ($node['name'] ?? '') !== 'mode') {
                continue;
            }

            foreach ($node['files'] ?? [] as $dir) {
                if (($dir['type'] ?? '') !== 'directory') {
                    continue;
                }

                foreach ($dir['files'] ?? [] as $file) {
                    if (($file['name'] ?? '') === $dir['name'] . '.js') {
                        $modes[] = "{$package}/mode/{$dir['name']}/{$file['name']}";
                    }
                }
            }
        }

        if ($modes !== []) {
            sort($modes);
            File::ensureDirectoryExists(dirname($cache));
            File::put($cache, implode("\n", $modes) . "\n");
        }

        return $modes;
    }

    /** The languages the gallery offers, minus English, which is built in. */
    private function languages(string $php): array
    {
        if (! preg_match('/langs = \[([^\]]+)\]/', $php, $m)) {
            return [];
        }

        $langs = array_map(
            static fn (string $l): string => trim($l, " \t\n\r'\""),
            explode(',', $m[1])
        );

        return array_values(array_filter(
            $langs,
            static fn (string $l): bool => $l !== '' && $l !== 'en'
        ));
    }

    private function report(array $paths, string $vendor): int
    {
        $upstream = self::upstreamGaps($vendor);

        $missing = array_values(array_filter(
            $paths,
            static fn (string $p): bool => ! File::exists("{$vendor}/{$p}") && ! in_array($p, $upstream, true)
        ));

        $this->info(count($paths) . ' assets derived from the gallery.');

        if ($upstream !== []) {
            $this->line(count($upstream) . ' not published upstream (see UPSTREAM-MISSING.txt).');
        }

        if ($missing === []) {
            $this->info('All present.');

            return self::SUCCESS;
        }

        $this->warn(count($missing) . ' missing:');

        foreach (array_slice($missing, 0, 25) as $p) {
            $this->line("  · {$p}");
        }

        if (count($missing) > 25) {
            $this->line('  · …and ' . (count($missing) - 25) . ' more.');
        }

        return self::FAILURE;
    }

    private function fetchAll(array $paths, string $vendor): int
    {
        $this->info(count($paths) . ' assets derived from the gallery.');

        $bar = $this->output->createProgressBar(count($paths));
        $bar->start();

        $fetched = 0;
        $kept = 0;
        $failed = [];
        $absent = [];

        foreach ($paths as $path) {
            $dest = "{$vendor}/{$path}";

            if (File::exists($dest) && ! $this->option('force')) {
                $kept++;
                $bar->advance();

                continue;
            }

            $status = $this->download($path, $dest);

            match ($status) {
                200 => $fetched++,
                404 => $absent[] = $path,
                default => $failed[] = $path,
            };

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Downloaded {$fetched}, already present {$kept}.");

        $this->recordUpstreamGaps($vendor, $absent);

        if ($failed !== []) {
            $this->error(count($failed) . ' failed:');

            foreach (array_slice($failed, 0, 15) as $p) {
                $this->line("  · {$p}");
            }

            // A partial set is the exact failure this command exists to
            // prevent, so it is not a success.
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Record assets the gallery asks for that npm does not publish.
     *
     * `dayjs` is the live example: the gallery offers Norwegian as `no`, dayjs
     * ships `nb` and `nn`, so that request 404s — on the CDN exactly as it
     * would here. Self-hosting neither causes nor cures it, and dayjs falls
     * back cleanly.
     *
     * Writing them down matters because "missing" otherwise has two meanings.
     * One is harmless and upstream's; the other is the bug that broke
     * production. The completeness test reads this file so it can hold the
     * second to account without tripping over the first.
     */
    private function recordUpstreamGaps(string $vendor, array $absent): void
    {
        $path = "{$vendor}/UPSTREAM-MISSING.txt";

        if ($absent === []) {
            File::delete($path);

            return;
        }

        sort($absent);

        File::put($path, implode("\n", array_merge([
            '# Assets Files Gallery requests that npm does not publish.',
            '# Generated by `php artisan filemanager:vendor` — do not edit by hand.',
            '#',
            '# These 404 on the CDN too, so self-hosting changes nothing for them',
            '# and the feature degrades the way it always has.',
            '',
        ], $absent)) . "\n");

        $this->warn(count($absent) . ' asset(s) are not published upstream; recorded in UPSTREAM-MISSING.txt.');
    }

    /**
     * Paths a previous run found are not published on npm.
     *
     * Public so the completeness test can read the same list rather than keep
     * its own copy of it.
     *
     * @return array<int, string>
     */
    public static function upstreamGaps(string $vendor): array
    {
        $path = "{$vendor}/UPSTREAM-MISSING.txt";

        if (! File::exists($path)) {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', explode("\n", File::get($path))),
            static fn (string $line): bool => $line !== '' && ! str_starts_with($line, '#')
        ));
    }

    /** @return int the HTTP status, so 404 can be told from a real failure */
    private function download(string $path, string $dest): int
    {
        $response = Http::timeout(90)->get(self::CDN . $path);

        if (! $response->successful() || $response->body() === '') {
            return $response->status() ?: 0;
        }

        File::ensureDirectoryExists(dirname($dest));
        File::put($dest, $response->body());

        return 200;
    }

    private function match(string $pattern, string $subject): ?string
    {
        return preg_match($pattern, $subject, $m) ? $m[1] : null;
    }
}
