<?php

namespace Modules\Streaming\app\Playback;

/**
 * Why playback was refused.
 *
 * The values are the machine-readable error codes the /api/v1 mobile
 * endpoints return, and they are the enum the engineering standard asks for:
 * clients branch on the code, never on the message text. They are listed in
 * the mobile app plan §5.
 *
 * The web does NOT branch on all of these. SubscriptionRequired and
 * UpgradeRequired both render as the same 403 with the same sentence there,
 * because that is what the site did before this enum existed and the site is
 * live. The split exists so the app can say "subscribe" to someone with no
 * plan and "upgrade" to someone on Basic, which is a better screen than one
 * message for both.
 */
enum PlaybackDenial: string
{
    /** No user, and either the content is gated or the site requires sign-up. */
    case LoginRequired = 'LOGIN_REQUIRED';

    /** Signed in, but carries no active subscription at all. */
    case SubscriptionRequired = 'SUBSCRIPTION_REQUIRED';

    /** Signed in with an active plan that sits below the content's level. */
    case UpgradeRequired = 'UPGRADE_REQUIRED';

    /** Entitled, but already streaming on as many devices as the plan allows. */
    case StreamLimit = 'STREAM_LIMIT';

    /** Draft, scheduled, or belonging to a series that is not public yet. */
    case ContentUnavailable = 'CONTENT_UNAVAILABLE';
}
