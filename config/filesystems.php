<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been set up for each driver as an example of the required values.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        /*
         * Profile photos.
         *
         * A disk of its own so avatars land in one predictable folder instead
         * of media-library's numbered directories scattered under the public
         * disk, and — the reason Rio asked for it — so a viewer's photo
         * resolves from any device in any environment.
         *
         * **Rooted at `public_path()`, deliberately, not `storage_path()`.**
         * The conventional Laravel location is `storage/app/public` served
         * through the `public/storage` symlink, and that is exactly the link
         * that is missing here: on this machine `public/storage` is a real
         * directory holding 126 poster files, so anything media-library wrote
         * to `storage/app/public` was never served and an avatar upload had
         * never once displayed locally.
         *
         * Writing through `public/storage/profiles` works in every
         * arrangement, which is the whole point:
         *   - symlinked install  → resolves to storage/app/public/profiles
         *   - real directory     → written and served directly
         *   - no link at all     → the directory is created and still served
         *
         * The url is a root-relative path, not `APP_URL`-prefixed. `APP_URL`
         * is the website's address and need not be the host a phone is
         * talking to; a path lets every client resolve against its own origin.
         * See App\Support\MediaUrl for the same reasoning applied on the way
         * out of the API.
         */
        'profiles' => [
            'driver' => 'local',
            'root' => public_path('storage/profiles'),
            'url' => '/storage/profiles',
            'visibility' => 'public',
            'throw' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
