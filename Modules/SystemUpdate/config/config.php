<?php

return [
    'name' => 'SystemUpdate',

    /*
    |--------------------------------------------------------------------------
    | Version file
    |--------------------------------------------------------------------------
    |
    | Path (relative to base_path) to the plain-text file that stores the
    | currently installed version. A single semver string, trimmed. Created
    | manually once per project; subsequently overwritten by the updater.
    |
    */
    'version_file' => 'version.txt',

    /*
    |--------------------------------------------------------------------------
    | Manifest sources
    |--------------------------------------------------------------------------
    |
    | The updater checks these locations in order and uses the first one
    | that returns a parseable JSON manifest. `local` is served by the
    | project itself (useful for testing); `remote` is the canonical
    | production source.
    |
    */
    'manifest' => [
        'local' => public_path('updates/laraupdater.json'),
        'remote' => env('JAMBO_UPDATER_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Temp folder
    |--------------------------------------------------------------------------
    |
    | Where downloaded update zips are written before extraction. Relative
    | to base_path. The folder is created on demand and cleaned up after
    | a successful update.
    |
    */
    'tmp_folder' => 'tmp',

    /*
    |--------------------------------------------------------------------------
    | Backup prefix
    |--------------------------------------------------------------------------
    |
    | When the extractor overwrites a file, the old copy is written under
    | this directory (plus a timestamp) first. If migrations fail, we
    | restore from here before bringing the site back up.
    |
    */
    'backup_prefix' => 'backup_',

    /*
    |--------------------------------------------------------------------------
    | Log file
    |--------------------------------------------------------------------------
    |
    | Updater actions are appended to this file so you can see what
    | happened on a customer's server after the fact. Path is relative to
    | storage_path().
    |
    */
    'log_file' => 'logs/updater.log',

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Applied to every UpdateController route. The admin gate is enforced
    | both here AND in the controller so a misconfigured middleware can't
    | accidentally expose the endpoints.
    |
    */
    'middleware' => ['web', 'auth', 'role:admin'],

    /*
    |--------------------------------------------------------------------------
    | Allowlist (defence in depth)
    |--------------------------------------------------------------------------
    |
    | Either `false` (any admin may trigger updates) or an array of user
    | IDs. The controller rejects update requests from users not in this
    | list when it's an array.
    |
    */
    'allow_users_id' => false,

    /*
    |--------------------------------------------------------------------------
    | Safety guards
    |--------------------------------------------------------------------------
    */
    'require_free_disk_bytes' => 200 * 1024 * 1024, // 200 MiB
    'http_timeout' => 300,                          // seconds

    /*
    |--------------------------------------------------------------------------
    | Database backup (defence against destructive migrations)
    |--------------------------------------------------------------------------
    |
    | Before `migrate --force` runs, the updater dumps the active DB to
    | a gzipped file. If migrations or the rest of the update fails, the
    | dump is restored before bringing the site back up — protection a
    | per-file backup can't provide on its own.
    |
    | Driver support: mysql / mariadb (mysqldump) and sqlite (file copy).
    | Other drivers fall through with a warning; back up manually before
    | triggering an update if you're on one of those.
    |
    | `path` is relative to storage_path() and gets created on demand.
    | `mysqldump_binary` / `mysql_binary` let you point at a non-PATH
    | install — e.g. on Hostinger shared hosting where mysqldump may
    | live at /opt/.../bin/mysqldump.
    |
    */
    'db_backup' => [
        'enabled' => true,
        'path' => 'app/updates/db-backups',
        'mysqldump_binary' => env('JAMBO_MYSQLDUMP_BIN', 'mysqldump'),
        'mysql_binary'     => env('JAMBO_MYSQL_BIN', 'mysql'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retained file + DB backups (manual rollback)
    |--------------------------------------------------------------------------
    |
    | After a successful update, the per-update file backup and the
    | pre-migrate DB dump are moved together into this directory, with a
    | `meta.json` recording the version transition. The admin UI lists
    | them and lets the operator click "Restore" if a regression
    | surfaces hours or days later. Older backups beyond the `retain`
    | count get rotated out automatically.
    |
    | Set `retain` to 0 to disable retention (delete on success — the
    | pre-hardening behaviour). Don't.
    |
    */
    'file_backup' => [
        'path' => 'app/updates/file-backups',
        'retain' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Extraction deny list
    |--------------------------------------------------------------------------
    |
    | Regex patterns, evaluated against the FORWARD-SLASH relative path
    | of each entry in the release zip. Any match is silently dropped —
    | a release zip cannot overwrite the operator's `.env`, the
    | `storage/` tree (uploads, sessions, logs), the `public/storage`
    | symlink, the live SQLite DB file, or vendor / node_modules even
    | if a careless build script bundled them.
    |
    | The patterns are anchored explicitly; widen with care.
    |
    */
    'deny_patterns' => [
        '#^\.env$#',
        '#^\.env\.[A-Za-z0-9_.-]+$#',
        '#^storage/#',
        '#^public/storage(/|$)#',
        '#^database/database\.sqlite$#',
        '#^vendor/#',
        '#^node_modules/#',
        '#^modules_statuses\.json$#',
    ],
];
