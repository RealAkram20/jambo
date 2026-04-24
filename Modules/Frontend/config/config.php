<?php

return [
    'name' => 'Frontend',

    'recommendations' => [
        'enabled' => env('JAMBO_RECOMMENDATIONS_ENABLED', true),
        'cold_threshold' => env('JAMBO_COLD_THRESHOLD', 3),
        'cache_ttl_user' => env('JAMBO_TOP_PICKS_TTL_USER', 3600),
        'cache_ttl_guest' => env('JAMBO_TOP_PICKS_TTL_GUEST', 1800),
        'oversample_factor' => 3,
        'diversity_per_primary_genre' => 2,

        // Smart Shuffle — half familiar-affinity picks, half cross-genre
        // discovery, sampled then shuffled. Shorter TTL than Top Picks
        // so refreshes feel alive.
        'smart_shuffle' => [
            'cache_ttl_user' => env('JAMBO_SMART_SHUFFLE_TTL_USER', 1800),
            'cache_ttl_guest' => env('JAMBO_SMART_SHUFFLE_TTL_GUEST', 900),
            'pool_size' => 20,             // shortlist size per side before sampling
            'top_genres_count' => 3,       // how many affinity genres define "familiar"
            'discovery_recency_days' => 60,
        ],

        // Fresh Picks — affinity scoring on a recency-windowed pool.
        // Longer TTL than Smart Shuffle because the pool only changes
        // when new titles publish, not on every user interaction.
        'fresh_picks' => [
            'cache_ttl_user' => env('JAMBO_FRESH_PICKS_TTL_USER', 7200),
            'cache_ttl_guest' => env('JAMBO_FRESH_PICKS_TTL_GUEST', 7200),
            'recency_days' => 60,
            'pool_size' => 40,
        ],

        // Upcoming — pool is any title flagged STATUS_UPCOMING. Warm
        // users get affinity-scored ordering; cold/guest get soonest
        // release date first. Long TTL because the set changes only
        // when an admin toggles a title to/from upcoming.
        'upcoming' => [
            'cache_ttl_user' => env('JAMBO_UPCOMING_TTL_USER', 7200),
            'cache_ttl_guest' => env('JAMBO_UPCOMING_TTL_GUEST', 7200),
        ],

        'weights' => [
            'completion_genre' => 3.0,
            'rating_genre_per_star' => 2.0,
            'watchlist_genre' => 1.0,
            'abandoned_genre' => -1.0,
            'completion_cast' => 2.0,
            'rating_cast_per_star' => 1.5,
            'watchlist_cast' => 0.8,
            'popularity_log' => 0.2,
            'recency' => 0.5,
            'editor_boost' => 1.0,
            'in_progress_penalty' => -2.0,
        ],
    ],
];
