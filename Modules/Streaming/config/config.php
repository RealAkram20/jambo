<?php

return [
    'name' => 'Streaming',

    /*
    |--------------------------------------------------------------------------
    | Video CDN (Bunny pull zone over Backblaze B2)
    |--------------------------------------------------------------------------
    | hostname   — the pull-zone host (jambofilms.b-cdn.net or a custom
    |              cdn.* CNAME later). Empty = Backblaze URLs are served
    |              as pasted (which fails on a private bucket).
    | token_key  — Bunny Token Authentication key. Empty = unsigned CDN
    |              URLs (only while Token Authentication is off in the
    |              Bunny dashboard).
    | token_ttl  — signed-URL lifetime in seconds. Must comfortably
    |              exceed the longest title so an in-flight playback
    |              session never has its URL expire mid-movie.
    | b2_bucket  — only pasted URLs pointing at THIS bucket are
    |              rewritten to the CDN.
    */
    'cdn' => [
        'hostname'  => env('BUNNY_CDN_HOSTNAME'),
        'token_key' => env('BUNNY_CDN_TOKEN_KEY'),
        'token_ttl' => (int) env('BUNNY_CDN_TOKEN_TTL', 28800),
        'b2_bucket' => env('B2_BUCKET', 'JamboFilms'),
    ],
];
