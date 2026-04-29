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

    // Force Files Gallery to emit file URLs relative to its own index.php
    // URL (/…/storage/media/index.php), so '../gallery/foo.jpg' resolves to
    // /…/storage/gallery/foo.jpg in the browser — which then hits Laravel's
    // public/storage symlink cleanly. Without this override, FG computes
    // URLs from the physical path (/…/storage/app/public/gallery/foo.jpg),
    // which our root .htaccess sends through Laravel's front controller →
    // 404 on every preview. Relative URLs are host-agnostic, so the same
    // value works on localhost, jambo.test, and the production VPS.
    'root_url_path' => '../gallery',

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

    // Allow-list of upload MIME types. Tightened from the previous
    // wildcard because the admin role gate is no longer the only line
    // of defense — a compromised admin account should not be able to
    // drop a PHP shell into /storage/gallery/. Defense in depth: the
    // gallery's own .htaccess also blocks PHP execution regardless of
    // what was uploaded.
    //
    // `image/svg+xml` is intentionally excluded from `image/*`: SVG can
    // carry inline <script> that runs in the app origin when the URL is
    // opened directly, turning a benign-looking logo upload into stored
    // XSS. List the safe raster types explicitly. Widen this list if a
    // legitimate asset type is being rejected — don't switch back to ''.
    'upload_allowed_file_types' => '.png, .jpg, .jpeg, .gif, .webp, .ico, video/*, audio/*, .pdf, .srt, .vtt, .webvtt, .zip',

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

    // Bump this string whenever you change `root`, `root_url_path`, include/
    // exclude patterns, or anything else that would rewrite file URLs. It's
    // hashed into Files Gallery's server-side cache keys AND into `dirs_hash`
    // which namespaces the browser's localStorage directory cache — so
    // changing this value forces every client to re-fetch fresh data instead
    // of serving stale URLs from the previous config.
    'cache_key' => 'jambo-gallery-v1',

    // Skip Files Gallery's own login. The iframe sits inside /admin/file-manager
    // which is already gated by Jambo's web auth + role:admin middleware.
    // On a public host, set these so a direct hit on /storage/media/index.php
    // doesn't bypass the Jambo admin gate.
    'username' => '',
    'password' => '',
];
