<?php

namespace Modules\Streaming\app\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;
use Modules\Streaming\app\Models\ActiveStream;
use Modules\Streaming\app\Playback\PlaybackDecision;
use Modules\Streaming\app\Playback\PlaybackDenial;
use Modules\Subscriptions\app\Models\SubscriptionTier;
use Modules\Subscriptions\app\Models\UserSubscription;

/**
 * The one place that decides whether a viewer may play a title.
 *
 * Before this class the same rules lived in three hand-kept copies:
 * TierGate (the middleware on /player/* and /watch/src/*),
 * FrontendController::userCanWatch() + concurrencyExceeded() +
 * contentReleased() (the rich watch pages), and
 * StreamProxyController::streamable(). TierGate's own docblock said the
 * copies must not "drift apart" — and they had. Extracting them was the
 * precondition for /api/v1, because the mobile app would have been a fourth
 * copy. See docs/plans/mobile-offline-app.md §4.2.
 *
 * The site is live, so the ordering below reproduces TierGate's exactly.
 * Two deliberate reconciliations are marked R1 and R2 in the code.
 *
 * Two questions, kept separate because the callers ask them separately:
 *   isReleased()  — is this title public yet? (404 territory)
 *   authorize()   — may this viewer play it? (login / plan / device cap)
 * authorizeStream() asks both, in the order the stream endpoints need.
 */
class PlaybackAuthorizer
{
    /**
     * May this viewer play this title on this device?
     *
     * $sessionKey identifies the viewer's device for the concurrency cap.
     * On the web it is the Laravel session id; for /api/v1 it is the
     * device UUID. Pass null to skip the cap entirely — that is what a
     * read-only "should the Play button be enabled" check wants, since it
     * is not itself starting a stream.
     *
     * Does NOT check whether the title is published; see isReleased().
     * TierGate never checked that either, and the release check 404s
     * rather than 403s, so folding them together would change what the
     * live site returns.
     */
    public function authorize(
        Movie|Episode $content,
        ?Authenticatable $user,
        ?string $sessionKey = null,
    ): PlaybackDecision {
        // Site-wide "require sign-up to watch" switch. Deliberately ahead
        // of the free-content allow below, so that when it is ON a guest
        // cannot reach even an ungated title.
        if (!$user && setting('require_signup_to_watch')) {
            return PlaybackDecision::deny(PlaybackDenial::LoginRequired);
        }

        $requiredSlug = $this->requiredTierSlug($content);

        if (!$requiredSlug) {
            return PlaybackDecision::allow();
        }

        $requiredTier = SubscriptionTier::where('slug', $requiredSlug)->first();

        // R1. A tier_required slug that matches no tier is a data error, and
        // both former copies said in a comment that it should be treated as
        // free — but only TierGate actually did, because userCanWatch()
        // refused guests one branch earlier. TierGate's behaviour wins: it
        // is the security boundary, and it already served these bytes, so
        // matching it here removes an inconsistency rather than opening
        // anything. Effect on the web: a guest on a mis-slugged title now
        // gets the watch page instead of a bounce to login.
        if (!$requiredTier) {
            return PlaybackDecision::allow();
        }

        if (!$user) {
            return PlaybackDecision::deny(PlaybackDenial::LoginRequired, $requiredTier);
        }

        // Admins curate the catalogue and must be able to verify playback of
        // every tier without keeping test subscriptions.
        if ($this->isAdmin($user)) {
            return PlaybackDecision::allow();
        }

        $activeSub = $this->activeSubscription($user);
        $userLevel = $activeSub?->tier?->access_level ?? SubscriptionTier::ACCESS_FREE;

        if ($userLevel < $requiredTier->access_level) {
            // The web renders both of these as the same 403 with the same
            // sentence, exactly as before. The split is for the app.
            return PlaybackDecision::deny(
                $activeSub ? PlaybackDenial::UpgradeRequired : PlaybackDenial::SubscriptionRequired,
                $requiredTier,
            );
        }

        // Concurrency. Premium-gated content only — we are past the free
        // return above. A null or non-positive cap means the tier has none.
        //
        // R2. FrontendController::concurrencyExceeded() read
        // $content->tier_required directly, with no fallback to the show, so
        // an episode of a Premium series — the normal shape, since the Shows
        // form sets the plan on the series — skipped the cap on /watch and
        // /episode. TierGate applied it, so the <video src> 302'd to the
        // picker and the viewer got a silently broken player instead of a
        // screen explaining why. Using the resolved slug here fixes that:
        // the same viewers are refused as before, they just get told.
        if ($sessionKey !== null) {
            $cap = $activeSub?->tier?->max_concurrent_streams;

            if ($cap !== null && $cap > 0 && ActiveStream::activeCount($user->id, $sessionKey) >= $cap) {
                return PlaybackDecision::deny(PlaybackDenial::StreamLimit, $requiredTier);
            }
        }

        return PlaybackDecision::allow();
    }

    /**
     * Is this title public yet?
     *
     * A movie must be publicly visible. An episode must itself be released
     * AND belong to a publicly-visible show, so an episode of a draft series
     * is unreachable. Admins bypass, so they can verify scheduled content.
     */
    public function isReleased(Movie|Episode $content, ?Authenticatable $user): bool
    {
        if ($user && $this->isAdmin($user)) {
            return true;
        }

        if ($content instanceof Movie) {
            return $content->isPubliclyVisible();
        }

        // Episode::show() is a HasOneThrough that already reaches the show
        // via the season, so the season?->show fallback the frontend copy
        // carried was redundant rather than different.
        $show = $content->show;

        return $content->isPubliclyVisible() && $show && $show->isPubliclyVisible();
    }

    /**
     * Release check and entitlement in one, for the endpoints that hand over
     * a stream URL. Release is asked first because an unreleased title is a
     * 404 — "this does not exist for you" — and must not leak its tier.
     */
    public function authorizeStream(
        Movie|Episode $content,
        ?Authenticatable $user,
        ?string $sessionKey = null,
    ): PlaybackDecision {
        if (!$this->isReleased($content, $user)) {
            return PlaybackDecision::deny(PlaybackDenial::ContentUnavailable);
        }

        return $this->authorize($content, $user, $sessionKey);
    }

    /**
     * The tier slug that actually governs this title.
     *
     * Episodes usually carry none of their own — the admin Shows form sets
     * the plan on the series — so a null episode value falls back to the
     * show's. Reading the episode alone treated every such episode as free.
     */
    public function requiredTierSlug(Movie|Episode $content): ?string
    {
        $slug = $content->tier_required ?? null;

        if ($slug || !$content instanceof Episode) {
            return $slug;
        }

        return ($content->season?->show ?? $content->show)?->tier_required;
    }

    /**
     * The subscription that governs access right now: the current one with
     * the furthest end date, so a stacked renewal does not lose to an older
     * row.
     */
    public function activeSubscription(Authenticatable $user): ?UserSubscription
    {
        return UserSubscription::with('tier')
            ->where('user_id', $user->id)
            ->current()
            ->orderByDesc('ends_at')
            ->first();
    }

    private function isAdmin(Authenticatable $user): bool
    {
        return method_exists($user, 'hasRole') && $user->hasRole('admin');
    }
}
