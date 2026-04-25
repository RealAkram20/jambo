# System Updater — Reusable Pattern

An in-app updater that lets a site admin click a "check for updates" button
in a settings page and, if a newer version is available, pull the update
package from a remote URL, back up the current files, unpack the new files,
run pending migrations, clear caches, and bring the site back up — all
without SSH.

Based on the implementation at
[github.com/RealAkram20/Forever-Loved-updates](https://github.com/RealAkram20/Forever-Loved-updates)
(`app/Http/Controllers/Admin/LaraUpdaterController.php`, `build-update.ps1`,
`LARA_UPDATER.md`), which is itself a heavily-rewritten fork of the
`salarmorovati/laraupdater` package — the original package had broken
version comparison and deprecated ZIP handling, so we reuse the *flow*
and replace the *implementation*.

---

## Data-loss prevention features

The updater is designed around the assumption that a release WILL
eventually try to do something destructive (drop a column, ship a
careless zip with stray uploads, fail mid-migration). Four protections
make the worst case recoverable:

1. **DB dump before every migration.** The updater runs `mysqldump`
   (gzipped) into `storage/app/updates/db-backups/` before
   `migrate --force`. If a migration fails or the rest of the update
   throws, the dump is restored automatically before the site comes
   back up. Without this, a destructive migration is unrecoverable.
2. **Extraction deny list.** A regex allow-list filters every entry in
   the release zip — `.env`, `storage/`, `public/storage/`,
   `database/database.sqlite`, `vendor/`, `node_modules/`, and
   `modules_statuses.json` are silently dropped. A careless release
   author can't overwrite the operator's uploads, env, or local module
   toggles even if those files end up in the archive.
3. **Retained backups (last 3).** After a successful update, the
   per-update file backup + the pre-migrate DB dump are moved together
   into `storage/app/updates/file-backups/<timestamp>/` with a
   `meta.json` recording the version transition. Older backups beyond
   the retention limit get rotated out automatically. This gives the
   admin a window to notice regressions and restore long after the
   update completed.
4. **Manual rollback endpoint.** The admin Settings → Updates page
   lists all retained backups with one-click **Restore** buttons.
   Restoration runs files-then-DB, rolls `version.txt` back, clears
   caches, and brings the site up — all behind the same admin gate
   as the forward update.

---

## What the updater does

1. Admin visits **Settings → Updates**. Page calls a check endpoint.
2. The check endpoint reads a small JSON manifest hosted somewhere
   (`laraupdater.json`) and compares its `version` field with the local
   `version.txt` using `version_compare()`.
3. If a newer version exists, the UI shows a notification with the release
   description and an **Update Now** button.
4. Clicking Update Now:
   1. Puts the app into maintenance mode.
   2. **Dumps the database** to `storage/app/updates/db-backups/` (gzipped).
   3. Downloads the release zip to a temp folder.
   4. Extracts it, normalizing the wrapper folder name, applying the
      deny list, and backing up every file it's about to overwrite.
   5. Runs any pending migrations.
   6. Writes the new version string to `version.txt`.
   7. Clears all caches.
   8. **Moves the file backup + DB dump to the retained-backups tree**,
      rotates older backups out beyond the retention count.
   9. Deletes the temp zip.
   10. Brings the app back up.
5. If any step fails after the DB dump, the dump is restored, the file
   backup is restored, and the site is brought back up untouched.

---

## Version tracking

The installed version lives in **`version.txt` at the project root** — a
single line with a semver string like `1.1.7`. No DB row, no config entry.
Reasons:

- Survives database resets (useful for developers rebuilding locally).
- Readable from a plain PHP file without bootstrapping Laravel.
- Can be committed and diffed like any other file.
- The build script can overwrite it as part of packaging.

Read it like this:

```php
$current = trim(@file_get_contents(base_path('version.txt')) ?: '0.0.0');
```

---

## The remote manifest

A plain JSON file served over HTTPS:

```json
{
    "version": "1.2.0",
    "archive": "https://updates.example.com/releases/RELEASE-1.2.0.zip",
    "description": "Bug fixes and the new Payments module."
}
```

In the Forever project it lives at `public/updates/laraupdater.json` when
testing locally, and at a canonical remote URL (`LARA_UPDATER_URL`) in
production. The controller tries the local one first, falls back to the
remote one — useful for development.

**Compare versions properly.** Use PHP's built-in:

```php
if (version_compare($remote, $local, '>')) {
    // update available
}
```

The original `laraupdater` package used `<=` on raw strings — it thinks
`1.0.10` is older than `1.0.9`. Avoid.

---

## Architecture at a glance

```
┌─────────────────────────────────────────────┐
│ GET /settings/updates                       │
│   → controller reads version.txt            │
│   → fetches manifest (local or remote)      │
│   → renders page with "update available"    │
└─────────────────────────────────────────────┘
                    │
                    │ admin clicks "Update Now"
                    ▼
┌─────────────────────────────────────────────┐
│ POST /api/laraupdater/update                │
│                                             │
│ 1. Artisan::call('down')                    │
│ 2. Http::sink($tmp)->get($manifest.archive) │
│ 3. Extract zip, backup overwritten files    │
│ 4. Artisan::call('migrate', --force)        │
│ 5. file_put_contents(version.txt, new)      │
│ 6. Artisan::call('optimize:clear')          │
│ 7. Delete tmp zip, delete backup dir        │
│ 8. Artisan::call('up')                      │
│                                             │
│ on any failure between 3 and 6:             │
│   → restore from backup dir                 │
│   → Artisan::call('up')                     │
│   → return error to admin                   │
└─────────────────────────────────────────────┘
```

---

## Routes

| Method | URI | Controller method | Middleware | Purpose |
|---|---|---|---|---|
| GET | `/settings/updates` | `SettingsController@updates` | `auth`, `role:admin` | admin UI |
| POST | `/api/laraupdater/check` | `LaraUpdaterController@check` | `auth`, `role:admin` | JSON: manifest info |
| POST | `/api/laraupdater/update` | `LaraUpdaterController@update` | `auth`, `role:admin` | JSON: run the update |

The admin gate is enforced in two places: the middleware on the route
**and** inside the controller (`allow_users_id` check from config), so a
misconfigured middleware can't accidentally expose the endpoint.

---

## Config keys

```php
// config/system-update.php  (in Jambo, Modules/SystemUpdate/config/config.php)

return [
    // Where extracted/downloaded files land during an update
    'tmp_folder_name' => 'tmp',

    // Optional: per-release script that runs after extraction
    'script_filename' => 'upgrade.php',

    // Where to fetch the manifest from. Supports a fallback chain.
    'update_baseurl' => env('JAMBO_UPDATER_URL', env('APP_URL') . '/updates'),

    // Middleware applied to the check/update endpoints
    'middleware' => ['web', 'auth', 'role:admin|super-admin'],

    // Optional allowlist. Either `false` to allow any admin, or an array
    // of user IDs that may trigger updates.
    'allow_users_id' => false,
];
```

Matching `.env` entries:

```
JAMBO_UPDATER_URL=https://updates.jambo.co
```

---

## Update package format

A plain `.zip` with this shape:

```
RELEASE-1.2.0.zip
└── <wrapper-folder>/            # anything — the unpacker strips it
    ├── version.txt              # REQUIRED — used to verify + write new version
    ├── app/
    ├── config/
    ├── database/migrations/     # pending migrations
    ├── resources/
    ├── public/
    └── upgrade.php              # OPTIONAL — one-shot post-extract script
```

**Path normalization is critical.** Different zip tools produce different
wrapper layouts:

- `RELEASE-1.2.0.zip/RELEASE-1.2.0/app/...`
- `RELEASE-1.2.0.zip/Jambo_v1_2_0/app/...`
- `RELEASE-1.2.0.zip/app/...`  (no wrapper)
- `RELEASE-1.2.0.zip/__MACOSX/...`  (junk from macOS zip)

The unpacker has to strip the first meaningful directory component and
ignore `__MACOSX`. The Forever implementation uses a `normalizeZipEntryPath()`
helper — reproduce it:

```php
private function normalizeZipEntryPath(string $name): ?string
{
    if (str_starts_with($name, '__MACOSX/')) return null;
    $parts = explode('/', $name);
    // Drop the first component if it looks like a wrapper
    if (
        preg_match('/^(RELEASE-|v?\d+[._]\d+|[A-Za-z]+_?v?\d+)/', $parts[0])
        || $parts[0] === ''
    ) {
        array_shift($parts);
    }
    return implode('/', $parts) ?: null;
}
```

---

## Extraction with backup

Use `ZipArchive` (not the deprecated `zip_open()` from the original):

```php
$zip = new \ZipArchive();
if ($zip->open($zipPath) !== true) {
    throw new \RuntimeException('Could not open update package');
}

$backupDir = base_path('backup_' . date('Ymd_His'));
mkdir($backupDir, 0755, true);

try {
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $entry = $zip->getNameIndex($i);
        $target = $this->normalizeZipEntryPath($entry);
        if ($target === null) continue;

        $stream = $zip->getStream($entry);
        if ($stream === false) continue;

        $dest = base_path($target);

        // Back up the existing file before overwriting
        if (file_exists($dest) && !is_dir($dest)) {
            $bp = $backupDir . '/' . $target;
            @mkdir(dirname($bp), 0755, true);
            copy($dest, $bp);
        }

        @mkdir(dirname($dest), 0755, true);
        file_put_contents($dest, stream_get_contents($stream));
        fclose($stream);
    }
} catch (\Throwable $e) {
    $this->restoreFromBackup($backupDir);
    $zip->close();
    throw $e;
}

$zip->close();
```

Keep the backup until *migrations finish and the version file updates*.
Only delete it on full success. That way a failed migration can still be
rolled back by copying the backup files back.

---

## build-update.ps1 (package builder)

A PowerShell script in the project root that produces an update zip from
the current working copy. Key behaviours:

- Accepts a `-Version` argument (or reads `version.txt` for the new value
  and increments it).
- Default: ships only files changed since the last release (`git diff HEAD`).
- With `-All`: ships the entire app.
- **Always** includes: `version.txt`, all files under `database/migrations/`,
  and the optional `upgrade.php`.
- **Excludes**: `vendor/`, `node_modules/`, `storage/*`, `.env`, `.git/`,
  `public/storage` (the symlink), and any nested release zips.
- Stages the files under a temp directory with a *short* name like
  `JamboUpdate_v1_2_0` (avoid hitting Windows' 8.3 short-path bug — don't
  use spaces or `RELEASE-X.Y.Z` as the temp folder name).
- Writes the output zip to `public/updates/RELEASE-<version>.zip`.
- Updates `public/updates/laraupdater.json` with the new version, archive
  URL, and the commit subject as the description.
- Uses `Get-Item -LiteralPath` instead of `Resolve-Path` to avoid 8.3
  corruption on Windows paths with `.`, spaces, or long names.

The PowerShell script is optional — a plain bash equivalent works the same
way. The key invariant is: **the built zip must round-trip through the
unpacker's `normalizeZipEntryPath()`.**

---

## Optional `upgrade.php` per release

If present in the zip, the unpacker extracts it to the tmp directory,
`require`s it, and calls a single entry function:

```php
<?php
// upgrade.php — runs after file extraction, before migrations

function main(): void
{
    // e.g. delete an obsolete cache directory
    @array_map('unlink', glob(base_path('storage/framework/cache/data/old-*')) ?: []);
}
```

Use this for one-shot cleanup that plain migrations can't express (removing
files, fixing permissions, rewriting an env key).

---

## Gotchas and clever bits

- **Maintenance mode both sides of the update.** `Artisan::call('down')`
  before, `up` at the very end *and* in every failure path.
- **Backup directory named with timestamp**, not a fixed name — multiple
  failed updates don't clobber each other's backups.
- **`optimize:clear` with a fallback.** Some hosts disable it; fall back
  to running `config:clear`, `route:clear`, `view:clear` individually.
- **Fetch with `Http::timeout(300)->sink($path)->get(...)`**, not
  `file_get_contents` — the latter has no progress, no timeout, and
  silently truncates on network errors.
- **Check disk space before extracting.** Abort if `disk_free_space()`
  is less than twice the zip size.
- **Never auto-run `composer install` from the updater.** If a release
  needs new packages, ship the updated `vendor/` inside the zip or ship
  nothing and document a manual `composer install` step. Running composer
  from a web request is a reliability nightmare.
- **Version-compare with `version_compare`**, never with string
  comparison. Semver ordering is not lexicographic.
- **Don't trust the zip's filename.** Read the version from
  `version.txt` *inside* the zip after extraction and use that as the
  authoritative new version.
- **Log every step.** Write each phase start/finish to
  `storage/logs/updater.log` with timestamps. Without this you will have
  no idea which step failed on a customer's production site.

---

## What I'd change when rebuilding for Jambo

- **Drop `laraupdater` vendor package entirely.** Write a clean
  `UpdateManager` service that owns the flow. The original package was
  overridden so completely in the Forever project that the parent class
  was unused — dependency for no benefit.
- **Add a pre-check endpoint** that returns `{php_ok, writable_ok,
  disk_ok, maintenance_ok}` before accepting the update trigger.
- **Add a rollback endpoint** that takes a backup directory name and
  restores it. Current flow only rolls back within the same request.
- **Sign the manifest**, not with HMAC (key management is a headache),
  but with HTTPS + a pinned certificate fingerprint. Refuse to update if
  the cert doesn't match.
- **Support a "channel"** (stable / beta) in the manifest + config,
  so staging sites pull beta releases automatically.
- **Split check and update routes into the [SystemUpdate module](../../Modules/SystemUpdate/)**
  so the whole feature can be disabled with `php artisan module:disable
  SystemUpdate` on servers managed by a real CI/CD pipeline.

---

## How to rebuild in Jambo

Drop this into [Modules/SystemUpdate/](../../Modules/SystemUpdate/):

1. `Modules/SystemUpdate/app/Services/UpdateManager.php` — the flow
2. `Modules/SystemUpdate/app/Services/ZipExtractor.php` — path normalisation
3. `Modules/SystemUpdate/app/Http/Controllers/UpdateController.php` —
   `check()`, `run()`, `rollback()`
4. `Modules/SystemUpdate/routes/web.php` — admin-gated routes
5. `Modules/SystemUpdate/resources/views/updates/index.blade.php` — UI
6. `Modules/SystemUpdate/config/config.php` — tmp dir, manifest URL,
   middleware, allow list
7. Put `version.txt` at the project root, committed
8. Scripts: `bin/build-update.sh` (cross-platform) and
   `build-update.ps1` (Windows) at the project root
9. Add `Modules/SystemUpdate/docs/release-checklist.md` — the human steps
   to tag, build, upload, and publish the manifest
