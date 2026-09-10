<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Social login (Socialite)
    |--------------------------------------------------------------------------
    |
    | Google OAuth. The login/register views surface the "Continue with
    | Google" button only when GOOGLE_CLIENT_ID is set in .env, so
    | deployments without credentials don't see a broken button.
    |
    | Local dev credentials: create a project at
    |   https://console.cloud.google.com/ → Credentials → OAuth 2.0 Client IDs
    | Authorized redirect URI: {APP_URL}/auth/google/callback
    |
    */
    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),

        /*
         * Every OAuth client of ours that may appear in a token's `aud`.
         *
         * The website signs in with the Web client above. The Android app
         * signs in with its own client, keyed to the package name and the
         * signing certificate, and Google puts THAT id in the token it
         * returns. Two clients, two audiences, one account system.
         *
         * `POST /api/v1/auth/google` accepts a token whose audience is any
         * entry here and refuses everything else. Leaving the Android id
         * unset simply means the app cannot sign in yet; it never means
         * "accept anything".
         */
        'client_ids' => array_values(array_filter([
            env('GOOGLE_CLIENT_ID'),
            env('GOOGLE_ANDROID_CLIENT_ID'),
            env('GOOGLE_IOS_CLIENT_ID'),
        ])),
    ],

    /*
    |--------------------------------------------------------------------------
    | GeoIP
    |--------------------------------------------------------------------------
    |
    | Turns a device's IP into the city shown on the app's devices screen, so a
    | viewer can spot a sign-in that is not theirs. Resolved locally against a
    | MaxMind GeoLite2 file: no per-request cost, no rate limit, and no
    | viewer's address leaves this server.
    |
    | Unset by default and it must stay that way in this repository. The file
    | is ~60 MB, free but licence-keyed, and updated weekly, so it is fetched
    | on the server rather than committed. With no file every device row shows
    | its IP address instead, which is what the website has always shown.
    |
    */
    'geoip' => [
        'database' => env('GEOIP_DATABASE'),
    ],

];
