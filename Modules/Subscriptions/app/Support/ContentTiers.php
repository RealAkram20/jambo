<?php

namespace Modules\Subscriptions\app\Support;

use Illuminate\Support\Collection;
use Modules\Subscriptions\app\Models\SubscriptionTier;

/**
 * The bridge between "which plan did the admin pick" and the `tier_required`
 * slug we persist on a movie / show / episode.
 *
 * Worth knowing before touching this: several tiers deliberately share one
 * access_level. day-pass, weekly-basic, basic and basic-yearly are all
 * level 1 — they're different ways to *buy* the same access, not different
 * access. TierGate only ever compares numeric access_level, so tagging a
 * movie `basic-yearly` gates exactly like `basic`.
 *
 * Two invariants this class exists to protect:
 *
 *   1. Free means NULL, never the 'free' slug. Roughly a dozen views test
 *      `(bool) $item->tier_required` to decide whether to draw a PREMIUM
 *      ribbon. Persisting the level-0 slug would satisfy that truthiness
 *      check and badge free content as premium.
 *
 *   2. Badges show the access level, not the slug. Public cards render
 *      `strtoupper($item->tier_required)`, so a raw slug would surface as
 *      "BASIC-YEARLY" on a movie poster. label() maps any slug back to its
 *      level name ("Basic"), which is what the gate actually enforces.
 */
final class ContentTiers
{
    /**
     * Explicit "no plan / free" choice for the bulk picker.
     *
     * The picker can't use an empty string for Free: an empty value is also
     * what an untouched placeholder submits, and conflating the two means a
     * stray submit silently sets a whole page of titles free. So Free posts
     * this sentinel, the placeholder posts '', and the endpoint requires a
     * non-empty value — making "set these to Free" an unambiguous act.
     *
     * A sentinel rather than the 'free' tier's own slug so the option keeps
     * working even if an operator deactivates that tier row.
     */
    public const FREE = '__free';

    /**
     * access_level → human label. Mirrors SubscriptionTier::ACCESS_*.
     * Level 0 has no label because level 0 is stored as NULL.
     */
    private const LEVEL_LABELS = [
        SubscriptionTier::ACCESS_BASIC => 'Basic',
        SubscriptionTier::ACCESS_PREMIUM => 'Premium',
        SubscriptionTier::ACCESS_ULTRA => 'Ultra',
    ];

    /**
     * Per-request memo of the whole (small) tier table.
     *
     * label() is called once per rendered card, so hitting the database each
     * time turned a 15-row admin list into 15 extra queries and the home
     * page's rails into dozens. The table is a handful of rows that change
     * about never, so one read per request covers every lookup below.
     *
     * Deliberately holds INACTIVE tiers too: content can still reference a
     * tier an operator has since switched off, and such a title must keep
     * resolving to its real access level rather than silently reading free.
     */
    private static ?Collection $tiers = null;

    private static function tiers(): Collection
    {
        return static::$tiers ??= SubscriptionTier::query()
            ->orderBy('sort_order')
            ->orderBy('access_level')
            ->get(['id', 'name', 'slug', 'price', 'currency', 'billing_period', 'access_level', 'is_active']);
    }

    /**
     * Drop the memo. Called from SubscriptionTier's model events so an admin
     * editing a plan sees it immediately, and so long-lived processes
     * (queue workers, Octane) and tests don't serve a stale table.
     */
    public static function flush(): void
    {
        static::$tiers = null;
    }

    /**
     * Every active tier, cheapest access first, for rendering a picker.
     * Includes the level-0 tier(s) — the caller shows them as the "no plan
     * needed" choice and normalize() turns the selection into NULL.
     */
    public static function pickerOptions(): Collection
    {
        return static::tiers()->where('is_active', true)->values();
    }

    /**
     * Values the bulk endpoint will accept, for a validation `in:` rule —
     * every active tier slug plus the explicit Free sentinel.
     */
    public static function assignableSlugs(): array
    {
        return array_merge([self::FREE], static::pickerOptions()->pluck('slug')->all());
    }

    /**
     * Paid tiers only, for the single-item edit forms where Free is already
     * represented by the select's empty option.
     */
    public static function paidOptions(): Collection
    {
        return static::pickerOptions()
            ->filter(fn ($tier) => $tier->access_level > SubscriptionTier::ACCESS_FREE)
            ->values();
    }

    /**
     * Turn a submitted choice into the value to persist.
     *
     *   '' / null / a level-0 tier  → null  (free, ungated)
     *   any other active tier slug  → that slug
     *   anything unrecognised       → null
     *
     * Returning null for an unknown slug is the safe direction only because
     * callers validate first; it keeps a typo from writing a slug TierGate
     * can't resolve, which it would treat as free anyway.
     */
    public static function normalize(?string $slug): ?string
    {
        $slug = trim((string) $slug);

        if ($slug === '' || $slug === self::FREE) {
            return null;
        }

        $tier = static::pickerOptions()->firstWhere('slug', $slug);

        if (!$tier || $tier->access_level <= SubscriptionTier::ACCESS_FREE) {
            return null;
        }

        return $tier->slug;
    }

    /**
     * Display label for a stored `tier_required`, e.g. 'basic-yearly' →
     * "Basic". Null (or an unresolvable slug) means free — no badge.
     */
    public static function label(?string $slug): ?string
    {
        if (!$slug) {
            return null;
        }

        $level = static::accessLevelFor($slug);

        return self::LEVEL_LABELS[$level] ?? null;
    }

    /**
     * Numeric access level a stored slug gates at. Unknown slugs report 0
     * (free) to match how TierGate treats a slug it can't resolve.
     */
    public static function accessLevelFor(?string $slug): int
    {
        if (!$slug) {
            return SubscriptionTier::ACCESS_FREE;
        }

        $tier = static::tiers()->firstWhere('slug', $slug);

        return (int) ($tier->access_level ?? SubscriptionTier::ACCESS_FREE);
    }

    /**
     * Human summary of what a picker choice will do, for confirm dialogs and
     * flash messages: "Premium Yearly (Premium access)" or "Free".
     */
    public static function describe(?string $slug): string
    {
        $normalized = static::normalize($slug);

        if (!$normalized) {
            return 'Free';
        }

        $tier = static::tiers()->firstWhere('slug', $normalized);
        $label = static::label($normalized);

        // No tier row (a slug that vanished from the table) → fall back to
        // whatever we can name it, rather than reading a property off null.
        if (!$tier) {
            return $label ?? 'Free';
        }

        // "Basic Monthly (Basic access)" is worth saying; "Basic (Basic
        // access)" isn't, so collapse when the plan name IS the level name.
        return $label && $tier->name !== $label
            ? "{$tier->name} ({$label} access)"
            : $tier->name;
    }
}
