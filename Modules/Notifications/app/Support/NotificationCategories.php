<?php

namespace Modules\Notifications\app\Support;

use Modules\Notifications\app\Notifications\AccountDeactivatedNotification;
use Modules\Notifications\app\Notifications\AdminBroadcastNotification;
use Modules\Notifications\app\Notifications\EarningsCreditedNotification;
use Modules\Notifications\app\Notifications\EmailVerifiedNotification;
use Modules\Notifications\app\Notifications\EpisodeAddedNotification;
use Modules\Notifications\app\Notifications\MovieAddedNotification;
use Modules\Notifications\app\Notifications\NewDeviceLoginNotification;
use Modules\Notifications\app\Notifications\OrderConfirmationNotification;
use Modules\Notifications\app\Notifications\PasswordResetNotification;
use Modules\Notifications\app\Notifications\PaymentFailedNotification;
use Modules\Notifications\app\Notifications\PaymentReceivedNotification;
use Modules\Notifications\app\Notifications\PayoutProfileVerifiedNotification;
use Modules\Notifications\app\Notifications\ReferralRewardEarnedNotification;
use Modules\Notifications\app\Notifications\SeasonAddedNotification;
use Modules\Notifications\app\Notifications\ShowAddedNotification;
use Modules\Notifications\app\Notifications\SubscriptionActivatedNotification;
use Modules\Notifications\app\Notifications\SubscriptionCancelledNotification;
use Modules\Notifications\app\Notifications\SubscriptionExpiredNotification;
use Modules\Notifications\app\Notifications\SubscriptionExpiringNotification;
use Modules\Notifications\app\Notifications\UserSignupNotification;
use Modules\Notifications\app\Notifications\WelcomeUserNotification;
use Modules\Notifications\app\Notifications\WithdrawalApprovedNotification;
use Modules\Notifications\app\Notifications\WithdrawalPaidNotification;
use Modules\Notifications\app\Notifications\WithdrawalRejectedNotification;
use Modules\Notifications\app\Notifications\WithdrawalRequestedNotification;

/**
 * Which filter chip a notification belongs under.
 *
 * The app's inbox draws five chips — All, Movies, TV Shows, Account, Offers —
 * and every one of them has to be answerable from a row that already exists.
 * `notifications.type` holds the notification's class name, so that is what
 * this maps; no column was added and no payload was rewritten, which means the
 * chips work over notifications sent before this file existed.
 *
 * **Explicit, not derived.** Every class name here happens to snake_case into
 * its own `settingKey()`, so this could have been a string transformation. It
 * is a literal map instead because a transformation fails silently when
 * somebody names the thirty-fourth notification differently — the key would
 * simply stop matching and the row would quietly leave its chip.
 * `NotificationCategoryApiTest` walks the notification directory and fails if any
 * class is neither listed here nor named in UNCATEGORISED, so adding a
 * notification forces a decision about where it belongs.
 *
 * **Offers is admin broadcasts, by Rio's ruling of 2026-09-09.** Jambo has no
 * promotional notification type. A broadcast is how a promotion actually
 * reaches a viewer today, so the chip points at the real thing rather than at
 * an invented one, and it holds nothing until somebody sends a broadcast.
 */
final class NotificationCategories
{
    public const MOVIES = 'movies';
    public const SERIES = 'series';
    public const ACCOUNT = 'account';
    public const OFFERS = 'offers';

    /**
     * Category => the notification classes filed under it.
     *
     * @var array<string, array<int, class-string>>
     */
    private const MAP = [
        self::MOVIES => [
            MovieAddedNotification::class,
        ],

        self::SERIES => [
            ShowAddedNotification::class,
            SeasonAddedNotification::class,
            EpisodeAddedNotification::class,
        ],

        /*
         * Everything personal that is not catalogue news: security, billing
         * and partner money. One chip rather than three because a viewer with
         * four notifications does not need a taxonomy, and because the mockup
         * has one Account chip.
         */
        self::ACCOUNT => [
            WelcomeUserNotification::class,
            UserSignupNotification::class,
            EmailVerifiedNotification::class,
            PasswordResetNotification::class,
            NewDeviceLoginNotification::class,
            AccountDeactivatedNotification::class,

            OrderConfirmationNotification::class,
            PaymentReceivedNotification::class,
            PaymentFailedNotification::class,
            SubscriptionActivatedNotification::class,
            SubscriptionExpiringNotification::class,
            SubscriptionExpiredNotification::class,
            SubscriptionCancelledNotification::class,

            EarningsCreditedNotification::class,
            ReferralRewardEarnedNotification::class,
            PayoutProfileVerifiedNotification::class,
            WithdrawalRequestedNotification::class,
            WithdrawalApprovedNotification::class,
            WithdrawalPaidNotification::class,
            WithdrawalRejectedNotification::class,
        ],

        self::OFFERS => [
            AdminBroadcastNotification::class,
        ],
    ];

    /**
     * Notification classes that deliberately sit under no chip.
     *
     * They still appear under All, which is why this is a decision rather than
     * a gap. Named as strings because two of them are moderation types a
     * viewer never receives, and a `::class` reference would suggest the app
     * has something to do with them.
     *
     * `WatchlistAvailableNotification` is the interesting one. Its payload
     * carries `kind` — show or movie — so it *could* be filed under Movies or
     * TV Shows, but that value lives inside the `data` text column and routing
     * it would mean a JSON path query against an unindexed column on every
     * chip press. It is one notification type; it stays under All.
     *
     * @var array<int, string>
     */
    private const UNCATEGORISED = [
        'WatchlistAvailableNotification',
        'SystemUpdateAvailableNotification',
        'ContentReportedNotification',
        'NewCommentPostedNotification',
        'NewReviewPostedNotification',
        'TestNotification',
    ];

    /** The chips the app may ask for. */
    public static function all(): array
    {
        return array_keys(self::MAP);
    }

    public static function isCategory(string $category): bool
    {
        return array_key_exists($category, self::MAP);
    }

    /**
     * The class names to match `notifications.type` against.
     *
     * @return array<int, class-string>
     */
    public static function typesFor(string $category): array
    {
        return self::MAP[$category] ?? [];
    }

    /**
     * Which chip this row belongs under, or null when it belongs under none.
     *
     * Takes the stored `type` string rather than an instance: the row is all
     * the server has by the time the app asks, and the notification object
     * that wrote it is long gone.
     */
    public static function forType(?string $type): ?string
    {
        if ($type === null || $type === '') {
            return null;
        }

        foreach (self::MAP as $category => $classes) {
            if (in_array($type, $classes, true)) {
                return $category;
            }
        }

        return null;
    }

    /**
     * The classes this file deliberately leaves out of every chip.
     *
     * Exposed for the drift test, which is the only caller: it needs to tell
     * "nobody has filed this yet" apart from "somebody decided not to".
     *
     * @return array<int, string>
     */
    public static function uncategorised(): array
    {
        return self::UNCATEGORISED;
    }
}
