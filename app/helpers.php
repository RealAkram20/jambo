<?php

/*
 * Global helpers file with misc functions.
 */
if (! function_exists('app_name')) {
    /**
     * Helper to grab the application name.
     *
     * @return mixed
     */
    function app_name()
    {
        return setting('app_name') ?? config('app.name');
    }
}

/**
 * Avatar Find By Gender
 */
if (! function_exists('default_user_avatar')) {
    function default_user_avatar()
    {
        return asset(config('app.avatar_base_path').'avatar.png');
    }
    function default_user_name()
    {
        return __('messages.unknown_user');
    }
}
if (! function_exists('user_avatar')) {
    function user_avatar()
    {
        if (auth()->user()->profile_image ?? null) {
            return auth()->user()->profile_image;
        } else {
            return asset(config('app.avatar_base_path').'avatar.png');
        }
    }
}

if (! function_exists('default_feature_image')) {
    function default_feature_image()
    {
        return asset(config('app.image_path').'default.png');
    }
}

/*
 * Get or Set the Settings Values
 *
 * @var [type]
 */
if (! function_exists('setting')) {
    function setting($key, $default = null)
    {
        if (is_null($key)) {
            return new App\Models\Setting();
        }

        if (is_array($key)) {
            return App\Models\Setting::set($key[0], $key[1]);
        }

        $value = App\Models\Setting::get($key);

        return is_null($value) ? value($default) : $value;
    }
}

if (! function_exists('media_url')) {
    /**
     * Resolve a media URL for posters, backdrops, stills, thumbnails, etc.
     *
     * Handles three storage conventions Jambo now has to juggle:
     *
     *   1. Full URLs  (`https://picsum.photos/…`, `https://dropbox.com/…`)
     *      — return as-is.
     *   2. App-absolute paths (`/Jambo/storage/gallery/…` on XAMPP,
     *      `/storage/gallery/…` on a domain-root deploy) — return as-is.
     *      The browser resolves a leading `/` against the current origin,
     *      which is exactly what the picker produces. Wrapping with
     *      `asset()` or `url()` here would double-prefix `/Jambo` and
     *      break the URL.
     *   3. Legacy bare filenames (`media/gameofhero.webp` — from the
     *      Streamit template era when values were relative to
     *      `public/frontend/images/`) — fall back to asset().
     *
     * When the value is null/empty, optionally resolves a fallback the
     * same way so callers can pass `media_url($item->poster_url,
     * 'media/gameofhero.webp')` and get sensible behaviour across all
     * three conventions without repeating the check at every call site.
     */
    function media_url(?string $value, ?string $fallback = null, string $legacyDir = 'frontend/images'): string
    {
        if (!empty($value)) {
            // Already a resolvable URL — full (http://…) or app-absolute (/…).
            if (preg_match('#^(https?://|/)#i', $value)) {
                return $value;
            }
            // Legacy: filename relative to public/frontend/images.
            return asset(trim($legacyDir, '/') . '/' . ltrim($value, '/'));
        }
        if ($fallback !== null) {
            return media_url($fallback, null, $legacyDir);
        }
        return '';
    }
}

if (! function_exists('media_img')) {
    /**
     * Like media_url() but routes the result through /img/ so the
     * ImageProxyController resizes + transcodes to WebP at the
     * requested width on the fly. Pair with media_srcset() to ship
     * a real responsive image; on its own, returns a single-size URL
     * suitable for an <img src="..."> attribute.
     *
     * External URLs (https://…) are passed through unchanged because
     * Glide can't proxy remote sources without extra setup. App-
     * absolute paths (`/foo.jpg`) and legacy bare filenames (resolved
     * the same way as media_url) are routed through the proxy.
     *
     *   media_img($movie->poster_url, 320)
     *     -> https://jambofilms.com/img/frontend/images/media/foo.jpg?w=320&fm=webp
     */
    function media_img(?string $value, int $width, ?string $fallback = null, string $legacyDir = 'frontend/images'): string
    {
        if (empty($value)) {
            if ($fallback !== null) return media_img($fallback, $width, null, $legacyDir);
            return '';
        }

        // External — pass through. Glide can't fetch remote sources
        // and we don't want to silently break a URL the admin pasted.
        if (preg_match('#^https?://#i', $value)) {
            return $value;
        }

        // App-absolute path → already public-relative, just strip the
        // leading slash. Legacy bare filename → prepend legacyDir
        // (matches media_url's convention exactly).
        $path = str_starts_with($value, '/')
            ? ltrim($value, '/')
            : trim($legacyDir, '/') . '/' . ltrim($value, '/');

        // Build the proxy URL by hand — url('img/'.$path) collapses
        // forward slashes inside $path which we want preserved.
        return url('img/' . $path) . '?w=' . $width . '&fm=webp';
    }
}

if (! function_exists('media_srcset')) {
    /**
     * Build a `srcset` value with multiple widths so the browser
     * can pick the right size for the viewport / device pixel ratio.
     * Pair with `<img sizes="...">` for true responsive selection.
     *
     *   <img src="{{ media_img($p, 640) }}"
     *        srcset="{{ media_srcset($p, [320, 640]) }}"
     *        sizes="(max-width: 768px) 320px, 640px"
     *        loading="lazy" decoding="async">
     *
     * For external URLs, every width returns the same URL so the
     * srcset is effectively a no-op — harmless, just doesn't save
     * bytes (we can't resize images we don't host).
     */
    function media_srcset(?string $value, array $widths, ?string $fallback = null, string $legacyDir = 'frontend/images'): string
    {
        if (empty($value) && $fallback === null) return '';

        $parts = [];
        foreach ($widths as $w) {
            $url = media_img($value, (int) $w, $fallback, $legacyDir);
            if ($url !== '') {
                $parts[] = $url . ' ' . ((int) $w) . 'w';
            }
        }
        return implode(', ', $parts);
    }
}

if (! function_exists('branding_asset')) {
    /**
     * Resolve a branding asset URL (logo, favicon, preloader).
     * Falls back to the bundled template asset when unset.
     */
    function branding_asset($key, $fallback = null)
    {
        $value = setting($key);
        if (! empty($value)) {
            return str_starts_with($value, 'http') || str_starts_with($value, '/')
                ? $value
                : asset($value);
        }
        return $fallback ? asset($fallback) : null;
    }
}

if (! function_exists('versioned_asset')) {
    /**
     * Like asset(), but appends a ?v=<mtime> query string so browser
     * caches invalidate automatically the moment a file changes on
     * disk. Use this for hand-written /public assets (JS, CSS) that
     * we expect to evolve and where stale caches would surface as
     * "user reports a bug we already fixed". Falls back to plain
     * asset() if the file isn't reachable on the local disk.
     */
    function versioned_asset(string $path): string
    {
        $absolute = public_path($path);
        if (file_exists($absolute)) {
            return asset($path) . '?v=' . filemtime($absolute);
        }
        return asset($path);
    }
}

if (! function_exists('branded_logo')) {
    /**
     * Resolve the best available branding image for places that want a
     * wide / wordmark-style logo: uploaded logo first, then favicon,
     * then a stock fallback. Used by the auth + maintenance header.
     */
    function branded_logo($fallback = 'icons/jambo-192.png')
    {
        foreach (['logo', 'favicon'] as $key) {
            $value = setting($key);
            if (empty($value)) {
                continue;
            }
            return str_starts_with($value, 'http') || str_starts_with($value, '/')
                ? $value
                : asset($value);
        }
        return asset($fallback);
    }
}

if (! function_exists('branded_icon')) {
    /**
     * Resolve the best square brand icon — favicon first since square
     * marks render cleanly in the install banner, apple-touch-icon,
     * manifest, and any other icon-shaped slot. Falls back to the
     * uploaded logo, then a stock fallback.
     */
    function branded_icon($fallback = 'icons/jambo-192.png')
    {
        foreach (['favicon', 'logo'] as $key) {
            $value = setting($key);
            if (empty($value)) {
                continue;
            }
            return str_starts_with($value, 'http') || str_starts_with($value, '/')
                ? $value
                : asset($value);
        }
        return asset($fallback);
    }
}

if (! function_exists('meta_description')) {
    function meta_description()
    {
        return setting('meta_description') ?? config('app.name') . ' streaming platform';
    }
}


if(!function_exists('activeRoute')) {
    function activeRoute($route, $isClass = false): string
    {
        $requestUrl = request()->fullUrl() === $route ? true : false;

        if($isClass) {
            return $requestUrl ? $isClass : '';
        } else {
            return $requestUrl ? 'active' : '';
        }
    }
}

