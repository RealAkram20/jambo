<?php

// Files Gallery config for Jambo admin file manager.
// Docs: https://www.files.gallery/docs/config/
// Picked up by storage/app/public/media/index.php on every request and merged
// on top of Files Gallery's built-in defaults.

return [

    // Dedicated gallery folder at storage/app/public/gallery/ — a clean
    // admin-curated asset space, separate from spatie/medialibrary uploads,
    // source videos and HLS streams. Served publicly at /storage/gallery/<path>.
    'root' => '../gallery',

    // Not needed: the gallery folder lives inside the public symlink so files
    // resolve to direct URLs. Keep this off for speed (no PHP-streaming overhead).
    'load_files_proxy_php' => false,

    // Shortcut flag: grants the 14 standard read/write ops (upload, delete,
    // rename, new_file, new_folder, duplicate, text_edit, zip, unzip, move,
    // copy, download, mass_download, mass_copy_links).
    'allow_all' => true,

    // Lock off the built-in Files Gallery diagnostics endpoint — it exposes
    // server config details that admins don't need day-to-day.
    'allow_tests' => false,

    // Cap per-file upload at 4 GB. PHP's upload_max_filesize / post_max_size
    // still apply — raise them in php.ini (upload_max_filesize=4G, post_max_size=4G,
    // max_execution_time=600, max_input_time=600, memory_limit=512M) or this
    // ceiling is silently lowered to whatever PHP permits.
    'upload_max_filesize' => 4294967296,

    // Let admins upload any file type (the module route is already role:admin
    // gated). Tighten to e.g. 'image/*, video/*, .pdf' for stricter envs.
    'upload_allowed_file_types' => '',

    // Use ImageMagick for JPEG/PNG resizing when available — better downscaling
    // for large movie posters than PHP GD's default imagecopyresampled.
    'image_resize_use_imagemagick' => true,

    // Sidebar folder tree for nested dirs (public/, source/, hls/, etc.).
    'menu_enabled' => true,
    'menu_max_depth' => 5,
    'layout' => 'rows',

    // Cache dir previews and resized images. Files live under
    // storage/app/public/media/_files/cache/. Auto-cleaned on interval.
    'cache' => true,
    'clean_cache_interval' => 7,

    // Skip Files Gallery's own login. The iframe sits inside /admin/file-manager
    // which is already gated by Jambo's web auth + role:admin middleware.
    // On a public host, set these so a direct hit on /storage/media/index.php
    // doesn't bypass the Jambo admin gate.
    'username' => '',
    'password' => '',
];
