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

