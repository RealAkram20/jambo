<?php

namespace Modules\Streaming\app\Playback;

use Modules\Subscriptions\app\Models\SubscriptionTier;

/**
 * The answer to "may this viewer play this title right now, on this device".
 *
 * One value object so the web middleware, the web controllers and the /api/v1
 * endpoints all read the same decision instead of each re-deriving it. The
 * caller decides how to express it: TierGate redirects or aborts, the
 * frontend controller picks a destination, the API returns `code`.
 *
 * Immutable, and constructed only through allow()/deny() so an "allowed"
 * decision can never also carry a denial.
 */
final class PlaybackDecision
{
    private function __construct(
        public readonly bool $allowed,
        public readonly ?PlaybackDenial $denial = null,
        public readonly ?SubscriptionTier $requiredTier = null,
    ) {
    }

    public static function allow(): self
    {
        return new self(true);
    }

    public static function deny(PlaybackDenial $denial, ?SubscriptionTier $requiredTier = null): self
    {
        return new self(false, $denial, $requiredTier);
    }

    public function denied(): bool
    {
        return !$this->allowed;
    }

    public function is(PlaybackDenial $denial): bool
    {
        return $this->denial === $denial;
    }

    /**
     * True when the refusal is about the viewer's plan rather than their
     * session or the content. Both halves render as one 403 on the web.
     */
    public function needsBetterPlan(): bool
    {
        return $this->denial === PlaybackDenial::SubscriptionRequired
            || $this->denial === PlaybackDenial::UpgradeRequired;
    }

    /** The API error code, or null when playback was allowed. */
    public function code(): ?string
    {
        return $this->denial?->value;
    }

    /**
     * A sentence for a human.
     *
     * The plan-related sentence is reproduced verbatim from the abort() call
     * TierGate used to make, because it is on the live site today.
     */
    public function message(): string
    {
        return match ($this->denial) {
            null => 'Playback allowed.',
            PlaybackDenial::LoginRequired => 'Please sign in to watch this.',
            PlaybackDenial::SubscriptionRequired,
            PlaybackDenial::UpgradeRequired => $this->requiredTier
                ? "This requires a {$this->requiredTier->name} subscription."
                : 'This requires a subscription.',
            PlaybackDenial::StreamLimit => 'You are watching on too many devices. Stop one to continue here.',
            PlaybackDenial::ContentUnavailable => 'This title is not available yet.',
        };
    }
}
