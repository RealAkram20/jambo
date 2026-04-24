<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Blocks usernames that would collide with real top-level routes.
 *
 * Because the profile hub lives at /{username}, anything a user picks
 * has to be a name the router doesn't already own. We keep the list
 * right here (rather than `config/*`) so adding/removing routes can
 * trigger a code review of usernames that might now clash.
 *
 * Matching is case-insensitive; we compare the slug-lowered input
 * against the reserved list, since usernames are stored case-
 * preserved but resolved case-insensitively in the hub.
 */
class ReservedUsername implements ValidationRule
{
    public const RESERVED = [
        // Auth + account paths
        'login', 'logout', 'register', 'signup', 'signin',
        'forgot-password', 'reset-password', 'verify-email',
        'confirm-password', 'two-factor-challenge',
        'account', 'auth', 'api',

        // Homepage + content browse
        'home', 'ott', 'index', 'search',
        'movie', 'movies', 'series', 'show', 'shows', 'tv-show', 'tv-shows',
        'episode', 'episodes', 'watch', 'playlist', 'watchlist-detail',

        // Taxonomy pages
        'genres', 'geners', 'all-genres', 'tag', 'view-all-tags',
        'categories', 'cast-list', 'cast-details', 'all-personality',

        // Detail pages
        'movie-detail', 'tv-show-detail', 'vj',

        // Profile & membership (legacy redirect targets)
        'your-profile', 'change-password',
        'membership-account', 'membership-orders', 'membership-invoice',
        'membership-level', 'membership-comfirmation',

        // Extra pages
        'about-us', 'contact-us', 'faq_page', 'privacy-policy',
        'terms-and-policy', 'comming-soon', 'pricing', 'pricing-page',
        'error-page1', 'error-page2',

        // Notifications + system
        'notifications', 'view-all', 'upcoming',

        // Short reserved words worth blocking so we don't paint
        // ourselves into a corner later
        'admin', 'administrator', 'root', 'support', 'help',
        'settings', 'billing', 'security', 'membership',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || $value === '') return;

        if (in_array(strtolower($value), self::RESERVED, true)) {
            $fail('That username is reserved. Please pick a different one.');
        }
    }
}
