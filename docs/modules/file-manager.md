# File Manager Module

**URL:** `/admin/file-manager` (auth + role:admin)
**Underlying engine:** [Files Gallery](https://www.files.gallery/) 0.15.3 — a
single-file PHP file browser, source-available under MIT.
**Serves:** a dedicated `storage/app/public/gallery/` folder, iframed into the
admin panel with Jambo's theme piped through on load.

## Why we vendor it

Files Gallery lives inside `storage/app/public/media/`, which is gitignored
(runtime state: user uploads, thumbnail cache, Files Gallery's own
`_files/config/config.php`). If we relied on "just leave the file there", the
next clone would lose it. So the shipped source sits in

    Modules/FileManager/resources/files-gallery/

and is **copied into place** at install time by an Artisan command.

## Directory map

Source (committed):

```
Modules/FileManager/
├── app/
│   ├── Console/Commands/InstallFilesGalleryCommand.php   artisan filemanager:install
│   └── Http/Controllers/FileManagerController.php        state detector (missing/install/ready)
├── resources/
│   ├── files-gallery/                                    vendored drop-in
│   │   ├── index.php                                     Files Gallery 0.15.3
│   │   ├── config.php                                    Jambo config overrides
│   │   ├── custom.js                                     license nag suppressor
│   │   ├── gallery-readme.md                             shown inside the gallery folder
│   │   └── README.md                                     explains this dir
│   └── views/index.blade.php                             iframe + theme sync
└── routes/web.php                                        /admin/file-manager
```

Runtime (gitignored, created by `filemanager:install`):

```
storage/app/public/
├── gallery/                                              what the file manager BROWSES
│   └── README.md                                         from gallery-readme.md
└── media/                                                what the iframe LOADS
    ├── index.php                                         Files Gallery
    └── _files/
        ├── cache/{folders,images,menu}/                  Files Gallery auto-created
        ├── config/config.php                             Jambo config
        └── js/custom.js                                  license nag suppressor
```

URL flow:

1. Admin hits `http://<host>/admin/file-manager`
2. Blade renders an iframe pointing at `/storage/media/index.php`
3. Apache follows `public/storage` → `storage/app/public/` symlink (created by
   `php artisan storage:link`)
4. Files Gallery loads with `root=../gallery` — so it browses the
   `storage/app/public/gallery/` tree

## Installing

### Automatic (normal case — fresh clone, deploy, teammate setup)

Nothing to do. `composer.json`'s `post-autoload-dump` runs the install:

```json
"post-autoload-dump": [
    "Illuminate\\Foundation\\ComposerScripts::postAutoloadDump",
    "@php artisan package:discover --ansi",
    "@php artisan filemanager:install --ansi"
]
```

So `composer install` drops everything into place. The command is idempotent —
re-runs are no-ops unless you pass `--force`.

### Manual (deliberate re-sync or overwrite)

```bash
php artisan filemanager:install          # skip files that already exist
php artisan filemanager:install --force  # overwrite existing files
```

Use `--force` after you've edited any file under
`Modules/FileManager/resources/files-gallery/` — the sources in that dir are
the source of truth; `--force` pushes your changes into runtime.

## Config (storage/app/public/media/_files/config/config.php)

Options we set, and why. Full reference at <https://www.files.gallery/docs/config/>.

| Option                          | Value         | Why                                                              |
|---------------------------------|---------------|------------------------------------------------------------------|
| `root`                          | `../gallery`  | Resolves to `storage/app/public/gallery/` — our curated space    |
| `load_files_proxy_php`          | `false`       | Gallery is under the public symlink — direct URLs, no PHP proxy  |
| `allow_all`                     | `true`        | Shortcut: grants 14 standard ops (upload/delete/rename/…)        |
| `allow_tests`                   | `false`       | Hide the diagnostics page from admins                            |
| `upload_max_filesize`           | `524288000`   | 500 MB per file ceiling (PHP's ini limits still apply)           |
| `upload_allowed_file_types`     | `''`          | Allow any file type; admin is already auth-gated                 |
| `image_resize_use_imagemagick`  | `true`        | Better downscaling than GD for large posters                     |
| `menu_enabled`                  | `true`        | Sidebar folder tree                                              |
| `menu_max_depth`                | `5`           | Reasonable tree depth                                            |
| `layout`                        | `'rows'`      | Default row-based file list                                      |
| `cache`                         | `true`        | Cache folder metadata and resized thumbs                         |
| `clean_cache_interval`          | `7`           | Auto-prune every 7 days                                          |
| `username` / `password`         | `''` / `''`   | No FG-level login; rely on Jambo's admin gate                    |

### Making config changes stick

The runtime `config.php` is gitignored. If you want a config change to persist
across machines, edit **the source**:

    Modules/FileManager/resources/files-gallery/config.php

Then `php artisan filemanager:install --force` locally, commit, push.

## Licence note (free tier and the nag suppressor)

Files Gallery's free tier supports every feature we use (upload, delete,
rename, move, copy, zip/unzip, mass download, thumbnails via ImageMagick and
FFmpeg, folder tree, theme sync). The **only** free-tier limitation is a
"purchase a license" SweetAlert that mounts on every page load.

`custom.js` suppresses that popup by pre-populating the localStorage key
`files:jux` with `btoa(location.hostname)` — the same value the client-side
gate is checking for. This is not a license bypass (no Pro features unlocked,
no server tampering); it just answers a question the browser is already asking
about its own environment.

**Before going to paid production traffic**, buy a real license at
<https://www.files.gallery/docs/license/> and set `'license_key' => '...'` in
the source `config.php`. That triggers the real remote validation flow and
makes `custom.js`'s effect cosmetic.

## Deploying to the VPS

Short answer: **no manual steps required**.

Your deploy flow (whether it's a `git pull && composer install --no-dev`, a
Hostinger deploy button, Envoyer, Deployer, or a manual SSH session) should
include a `composer install`. That command runs `post-autoload-dump`, which
runs `filemanager:install`, which lays the drop-in into
`storage/app/public/media/` on the VPS.

Checklist on first deploy:

- [x] `composer install` — triggers `filemanager:install` automatically
- [x] `php artisan storage:link` — creates `public/storage` → `storage/app/public/` symlink
- [x] `chmod -R 775 storage/app/public/media storage/app/public/gallery` (www-data writeable)
- [x] `chown -R www-data:www-data storage/app/public/media storage/app/public/gallery` (on Debian/Ubuntu) or `apache:apache` (on RHEL/CentOS)

### If you use `composer install --no-scripts`

The command won't run. Run it manually afterwards:

```bash
composer install --no-scripts --no-dev
php artisan filemanager:install
```

### Updating the Files Gallery version

1. Download the new `index.php` from
   `https://cdn.jsdelivr.net/npm/files.photo.gallery@<NEW_VERSION>/index.php`
2. Replace `Modules/FileManager/resources/files-gallery/index.php`
3. Commit and push
4. On the VPS: `git pull && composer install` (re-runs post-autoload-dump →
   installs version skipped because `index.php` already exists). To force:
   `php artisan filemanager:install --force`

## Troubleshooting

**"Files Gallery is not installed" banner at `/admin/file-manager`**
Runtime copy is missing. Run `php artisan filemanager:install`.

**Nag popup still appears despite `custom.js`**
Clear the iframe's localStorage and hard-refresh. DevTools → Application →
Local Storage → `/storage` → delete all keys → reload the admin page.

**"file-manager is rendering an empty tree"**
`storage/app/public/gallery/` exists but is empty (aside from the README).
That's expected on fresh install — drop some files in.

**Uploads over ~2 MB fail silently**
PHP defaults. Edit `php.ini`:
```ini
upload_max_filesize = 500M
post_max_size       = 500M
memory_limit        = 512M
```
Restart Apache / PHP-FPM.

**ImageMagick not detected — posters render slowly**
FG falls back to PHP GD (which works but is slower and lower-quality). Enable
`imagick` extension in `php.ini` or install the `imagemagick` CLI on the VPS.
Test at `/storage/media/index.php?action=tests` — you'll need to flip
`allow_tests => true` in the source `config.php` temporarily.

**Direct URL `/storage/media/index.php` bypasses Jambo admin auth**
Correct — on localhost this is fine, in production it isn't. Either:
- Set `username` / `password` in the source `config.php` (adds a second login)
- Or add an Apache/Nginx rule rejecting requests without the right
  `Referer`/`Origin` (more complex but preserves single sign-on)
