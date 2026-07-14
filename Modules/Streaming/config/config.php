<?php

return [
    'name' => 'Streaming',

    /*
    |--------------------------------------------------------------------------
    | Video CDN — pull zones over origin storage
    |--------------------------------------------------------------------------
    | Every pasted video URL is run through CdnUrlResolver, which walks
    | the `zones` list below in order and lets the FIRST zone that
    | recognizes the URL rewrite it. A zone that doesn't recognize a URL
    | passes; a URL no zone claims is served exactly as pasted.
    |
    | A Bunny pull zone is one hostname in front of one origin, so there
    | is one zone per origin — you can't point a single zone at "wherever
    | this file happens to live". Adding another origin of a shape a
    | driver already understands is a new entry here, not new code.
    |
    | Per-zone keys:
    |   driver     — which matcher/rewriter handles the URL:
    |                  'backblaze' : the three Backblaze B2 URL shapes,
    |                                rewritten to a Bunny pull zone.
    |                  'dropbox'   : normalized for inline playback
    |                                (raw=1); additionally routed through
    |                                a pull zone when `hostname` is set.
    |                  'host'      : any origin whose host matches
    |                                `origin_host` — swap host for the
    |                                pull-zone hostname, keep path+query.
    |   hostname   — the pull-zone host (e.g. jambofilms.b-cdn.net). Empty
    |                disables CDN rewriting for that zone (the dropbox
    |                driver still normalizes; others pass the URL through).
    |   token_key  — Bunny Token Authentication key. Empty = unsigned URLs
    |                (only safe while Token Authentication is OFF in the
    |                Bunny dashboard for that zone).
    |   token_ttl  — signed-URL lifetime in seconds. Must comfortably
    |                exceed the longest title so an in-flight session never
    |                has its URL expire mid-movie.
    |   bucket     — (backblaze only) only URLs pointing at THIS bucket
    |                are rewritten, so a pasted third-party B2 link never
    |                gets pointed at our pull zone.
    |   origin_host— (host only) the origin hostname this zone fronts.
    |
    | To put another recurring source behind its own zone: create the
    | pull zone in Bunny, then add a `host` entry here with its origin
    | host and the new pull-zone hostname. No code change.
    */
    'cdn' => [
        'zones' => [
            // Backblaze B2 → Bunny (the live, configured zone).
            'backblaze' => [
                'driver'    => 'backblaze',
                'bucket'    => env('B2_BUCKET', 'JamboFilms'),
                'hostname'  => env('BUNNY_CDN_HOSTNAME'),
                'token_key' => env('BUNNY_CDN_TOKEN_KEY'),
                'token_ttl' => (int) env('BUNNY_CDN_TOKEN_TTL', 28800),
            ],

            // Dropbox. hostname empty by default → links are only
            // normalized for inline playback, exactly as before. Set
            // BUNNY_DROPBOX_HOSTNAME once a Dropbox pull zone exists and
            // Dropbox traffic starts flowing through the CDN with no
            // other change.
            'dropbox' => [
                'driver'    => 'dropbox',
                'hostname'  => env('BUNNY_DROPBOX_HOSTNAME'),
                'token_key' => env('BUNNY_DROPBOX_TOKEN_KEY'),
                'token_ttl' => (int) env('BUNNY_DROPBOX_TOKEN_TTL', 28800),
            ],
        ],
    ],
];
