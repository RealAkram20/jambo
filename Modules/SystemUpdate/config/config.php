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
];
