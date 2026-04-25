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

if (! function_exists('branded_logo')) {
    /**
     * Resolve the best available branding image for "show the brand":
     * uploaded logo first, then uploaded favicon, then a stock fallback.
     * Used by the install prompt, apple-touch-icon, manifest, and the
     * auth/maintenance shell so they all surface the operator's brand
     * even when only one of the two settings is filled in.
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

