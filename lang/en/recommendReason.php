<?php

/**
 * Captions shown under an AI Smart Shuffle card explaining why the title
 * is on the shelf. Deliberately terse — this is a label, not a sales
 * pitch, and it sits inside a hover overlay with limited room.
 *
 * Every string maps to a signal the recommender actually computed. If a
 * new one is added here, something in TopPicksRecommender::attachReasons()
 * has to be able to prove it.
 */
return [
    'because_you_watched' => 'Because you watched :title',
    'cast_match' => 'You watch :name',
    'genre_match' => 'More :genre',
    'trending' => 'Trending now',
    'popular' => 'Popular now',
    'just_added' => 'Just added',
    'new_to_you' => 'New to you',
    'browsing' => 'Based on your browsing',
];
