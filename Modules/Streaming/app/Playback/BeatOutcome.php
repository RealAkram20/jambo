<?php

namespace Modules\Streaming\app\Playback;

/**
 * What happened to one playback heartbeat.
 *
 * Two shapes, because a beat has exactly two endings: it was recorded, or
 * this device had been booted from the account by the device picker and must
 * stop playing.
 *
 * The outcome deliberately carries no HTTP in it. The web controller answers
 * a terminated beat by also killing the Laravel session, which is right for a
 * browser and meaningless for the app, where the equivalent is revoking the
 * device's Sanctum token. Each caller does its own thing with the same fact.
 */
final class BeatOutcome
{
    private function __construct(
        public readonly bool $terminated,
        public readonly ?int $position = null,
        public readonly ?bool $completed = null,
    ) {
    }

    public static function recorded(int $position, bool $completed): self
    {
        return new self(false, $position, $completed);
    }

    /** This device was booted from the account; playback must stop. */
    public static function terminated(): self
    {
        return new self(true);
    }
}
