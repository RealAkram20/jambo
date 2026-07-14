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

        // AI Smart Shuffle — half familiar-affinity picks, half
        // collaborative-filtering discovery, rank-sampled rather than
        // sorted. Shorter TTL than Top Picks so refreshes feel alive.
        'smart_shuffle' => [
            'cache_ttl_user' => env('JAMBO_SMART_SHUFFLE_TTL_USER', 1800),
            'cache_ttl_guest' => env('JAMBO_SMART_SHUFFLE_TTL_GUEST', 900),
            'pool_size' => 40,             // shortlist size per side before sampling
            'top_genres_count' => 3,       // how many affinity genres define "familiar"
            'discovery_recency_days' => 60,

            // Rank-biased sampling: the candidate at rank r is drawn with
            // weight rank_decay^r. 1.0 degenerates to a uniform shuffle
            // (which throws the ranking away); 0.0 to a strict top-N
            // (which never churns). 0.88 makes rank 0 roughly 7x likelier
            // than rank 15 while still letting the tail surface.
            'rank_decay' => env('JAMBO_SMART_SHUFFLE_RANK_DECAY', 0.88),

            // Collaborative filtering — "viewers who finished what you
            // finished also finished X". Powers the discovery half; falls
            // back to cross-genre browsing when co-watch data is too thin.
            'collab_enabled' => env('JAMBO_SMART_SHUFFLE_COLLAB', true),
            'collab_seed_titles' => 20,    // most recent completions used as seeds
            'collab_peer_limit' => 400,    // cap on peers pulled per compute
            'collab_min_peers' => 2,       // ignore co-watch links this weak

            // Anti-repeat memory. Titles surfaced in previous windows are
            // penalised, not hard-excluded — a thin catalog would starve
            // the shelf. Session-backed (see SESSION_KEY_SHUFFLE_SEEN), so
            // it expires with the session rather than on a TTL of its own.
            'recent_memory_size' => 30,

            // "Trending" = completions inside the window, not all-time
            // views_count (which lets a years-old hit dominate forever).
            'trending_days' => 14,
            'trending_cache_ttl' => 900,

            // Per-card "Because you watched X" captions.
            'reasons_enabled' => env('JAMBO_SMART_SHUFFLE_REASONS', true),

            // Personalise guests from the genres they browse in-session,
            // so the shelf reacts on the first visit instead of staying
            // generic until they sign up.
            'guest_session_signal' => env('JAMBO_SMART_SHUFFLE_GUEST_SIGNAL', true),

            // Scoring blend for the candidate pools. Genre/cast affinity
            // and collab score are normalised to 0..1 before weighting, so
            // these numbers are comparable to each other.
            'weights' => [
                'genre_affinity' => 1.0,
                'cast_affinity'  => 0.6,
                'collab'         => 1.4,
                'trending'       => 0.5,
                'editor_boost'   => 0.8,
                'recently_shown' => -1.2,
            ],
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
