# Files Gallery drop-in (vendored)

This directory holds the source files for Jambo's admin file manager. Files
Gallery is an MIT/source-available single-file PHP gallery that we drop into
`storage/app/public/media/` and iframe from `/admin/file-manager`.

Because `storage/app/public/media/` is gitignored (it's runtime-only — user
uploads, cache, etc. live there), we can't commit the installed files
directly. Instead we commit the sources **here** and copy them at install
time via `php artisan filemanager:install`.

## Files

| File        | Copied to                                                | Purpose                                                   |
|-------------|----------------------------------------------------------|-----------------------------------------------------------|
| `index.php` | `storage/app/public/media/index.php`                     | Vendored Files Gallery 0.15.3 source                      |
| `config.php`| `storage/app/public/media/_files/config/config.php`      | Jambo-specific Files Gallery config (root, allow_*, etc.) |
| `custom.js` | `storage/app/public/media/_files/js/custom.js`           | Local-dev license nag suppressor                          |

## Install flow

Automatic — `composer.json` runs `php artisan filemanager:install` from the
`post-autoload-dump` hook, so a fresh `composer install` on a new clone lays
everything down without manual steps.

To force-overwrite an existing install (e.g. after updating `config.php` in
this source directory):

    php artisan filemanager:install --force

## Upgrading Files Gallery

1. Download the new `index.php` from <https://cdn.jsdelivr.net/npm/files.photo.gallery@VERSION/index.php>
2. Replace the copy in this directory
3. Commit + push
4. On each dev machine, `php artisan filemanager:install --force`
