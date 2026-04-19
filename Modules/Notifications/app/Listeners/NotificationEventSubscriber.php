<?php

namespace Modules\Notifications\app\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Modules\Notifications\app\Contracts\NotificationDispatcher;
use Modules\Notifications\app\Events as NE;
use Modules\Notifications\app\Notifications as N;

/**
 * One place where events get turned into notifications. Registered in
 * NotificationsServiceProvider::boot() via Event::subscribe(). Adding a
 * new notification type is a three-step change:
 *
 *   1. Add a key to NotificationSetting::definitions() + seeder
 *   2. Add a ChannelGatedNotification subclass under app/Notifications/
 *   3. Add one line to the $events array and one handle* method here
 *
 * Event dispatch points (where `event(new XxxEvent(...))` lives) are
 * the caller's job — this class only routes.
 */
class NotificationEventSubscriber
{
    public function __construct(private readonly NotificationDispatcher $dispatcher)
    {
    }

    /* ── Account & Security ──────────────────────────────────────── */

    public function handleRegistered(Registered $event): void
    {
        $user = $event->user;

        // Admin broadcast: new signup
        $this->dispatcher->toAdmins(new N\UserSignupNotification($user));

        // User-facing welcome message
        if ($user instanceof User) {
            $this->dispatcher->toUser($user, new N\WelcomeUserNotification($user));
        }
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        $user = $event->user;
        if ($user instanceof User) {
            $this->dispatcher->toUser(
                $user,
                new N\PasswordResetNotification(request()?->ip()),
            );
        }
    }

    public function handleVerified(Verified $event): void
    {
        $user = $event->user;
        if ($user instanceof User) {
            $this->dispatcher->toUser($user, new N\EmailVerifiedNotification());
        }
    }

    public function handleNewDeviceLogin(NE\NewDeviceLogin $event): void
    {
        $this->dispatcher->toUser(
            $event->user,
            new N\NewDeviceLoginNotification($event->ipAddress, $event->userAgent),
        );
    }

    public function handleAccountDeactivated(NE\AccountDeactivated $event): void
    {
        $this->dispatcher->toUser(
            $event->user,
            new N\AccountDeactivatedNotification($event->reason),
        );
    }

    /* ── Billing & Subscriptions ─────────────────────────────────── */

    public function handleOrderPlaced(NE\OrderPlaced $event): void
    {
        if ($event->order->user) {
            $this->dispatcher->toUser($event->order->user, new N\OrderConfirmationNotification($event->order));
        }
    }

    public function handlePaymentFailed(NE\PaymentFailed $event): void
    {
        $notif = new N\PaymentFailedNotification($event->order, $event->reason);
        if ($event->order->user) {
            $this->dispatcher->toUser($event->order->user, $notif);
        }
        $this->dispatcher->toAdmins($notif);
    }

    public function handleSubscriptionActivated(NE\SubscriptionActivated $event): void
    {
        $this->dispatcher->toUser(
            $event->user,
            new N\SubscriptionActivatedNotification($event->planName, $event->expiresOn),
        );
    }

    public function handleSubscriptionExpiring(NE\SubscriptionExpiring $event): void
    {
        $this->dispatcher->toUser(
            $event->user,
            new N\SubscriptionExpiringNotification($event->planName, $event->daysRemaining),
        );
    }

    /**
     * The existing ExpireSubscriptionsCommand fires a string-keyed
     * event('subscription.expired', [...]), so we register BOTH the
     * typed event and the string one for forward-compatibility.
     */
    public function handleSubscriptionExpiredTyped(object $event): void
    {
        // Detect whether this is called with the typed or untyped form.
        if (isset($event->user) && $event->user instanceof User) {
            $notif = new N\SubscriptionExpiredNotification($event->user->id, $event->planName ?? null);
            $this->dispatcher->toUser($event->user, $notif);
            $this->dispatcher->toAdmins($notif);
        }
    }

    public function handleSubscriptionExpiredStringEvent(User $user, ?string $planName = null): void
    {
        // Invoked by the string-keyed event('subscription.expired', [$user, $planName])
        // dispatched by ExpireSubscriptionsCommand.
        $notif = new N\SubscriptionExpiredNotification($user->id, $planName);
        $this->dispatcher->toUser($user, $notif);
        $this->dispatcher->toAdmins($notif);
    }

    public function handleSubscriptionCancelled(NE\SubscriptionCancelled $event): void
    {
        $this->dispatcher->toAdmins(
            new N\SubscriptionCancelledNotification($event->user->id, $event->user->username),
        );
    }

    /* ── Content Updates ─────────────────────────────────────────── */

    public function handleMovieAdded(NE\MovieAdded $event): void
    {
        $this->dispatcher->broadcastToAll(new N\MovieAddedNotification(
            $event->movieId, $event->movieTitle, $event->movieSlug, $event->poster,
        ));
    }

    public function handleShowAdded(NE\ShowAdded $event): void
    {
        $this->dispatcher->broadcastToAll(new N\ShowAddedNotification(
            $event->showId, $event->showTitle, $event->showSlug, $event->poster,
        ));
    }

    public function handleSeasonAdded(NE\SeasonAdded $event): void
    {
        $this->dispatcher->broadcastToAll(new N\SeasonAddedNotification(
            $event->showTitle, $event->seasonNumber, $event->showSlug, $event->poster,
        ));
    }

    public function handleEpisodeAdded(NE\EpisodeAdded $event): void
    {
        $this->dispatcher->broadcastToAll(new N\EpisodeAddedNotification(
            $event->showTitle, $event->seasonNumber, $event->episodeNumber,
            $event->episodeTitle, $event->showSlug, $event->poster,
        ));
    }

    public function handleWatchlistAvailable(NE\WatchlistAvailable $event): void
    {
        // Only notify the users who actually watchlisted this item.
        $notif = new N\WatchlistAvailableNotification($event->title, $event->slug, $event->poster, $event->kind);
        User::whereIn('id', $event->userIds)->each(
            fn (User $u) => $this->dispatcher->toUser($u, $notif),
        );
    }

    /* ── Admin & Moderation ──────────────────────────────────────── */

    public function handleNewReviewPosted(NE\NewReviewPosted $event): void
    {
        $this->dispatcher->toAdmins(new N\NewReviewPostedNotification(
            $event->reviewId, $event->reviewerUsername, $event->contentTitle, $event->rating,
        ));
    }

    public function handleNewCommentPosted(NE\NewCommentPosted $event): void
    {
        $this->dispatcher->toAdmins(new N\NewCommentPostedNotification(
            $event->commentId, $event->commenterUsername, $event->contentTitle, $event->excerpt,
        ));
    }

    public function handleContentReported(NE\ContentReported $event): void
    {
        $this->dispatcher->toAdmins(new N\ContentReportedNotification(
            $event->contentType, $event->contentId, $event->contentTitle, $event->reporterUsername, $event->reasonText,
        ));
    }

    public function handleSystemUpdateAvailable(NE\SystemUpdateAvailable $event): void
    {
        $this->dispatcher->toAdmins(new N\SystemUpdateAvailableNotification(
            $event->version, $event->releaseNotesUrl,
        ));
    }

    /**
     * Called by the framework via Event::subscribe(). Returns a map of
     * event → handler method. One-liner per new notification type.
     *
     * @return array<class-string, string>
     */
    public function subscribe(): array
    {
        return [
            Registered::class                 => 'handleRegistered',
            PasswordReset::class              => 'handlePasswordReset',
            Verified::class                   => 'handleVerified',

            NE\NewDeviceLogin::class          => 'handleNewDeviceLogin',
            NE\AccountDeactivated::class      => 'handleAccountDeactivated',

            NE\OrderPlaced::class             => 'handleOrderPlaced',
            NE\PaymentFailed::class           => 'handlePaymentFailed',
            NE\SubscriptionActivated::class   => 'handleSubscriptionActivated',
            NE\SubscriptionExpiring::class    => 'handleSubscriptionExpiring',
            NE\SubscriptionCancelled::class   => 'handleSubscriptionCancelled',

            NE\MovieAdded::class              => 'handleMovieAdded',
            NE\ShowAdded::class               => 'handleShowAdded',
            NE\SeasonAdded::class             => 'handleSeasonAdded',
            NE\EpisodeAdded::class            => 'handleEpisodeAdded',
            NE\WatchlistAvailable::class      => 'handleWatchlistAvailable',

            NE\NewReviewPosted::class         => 'handleNewReviewPosted',
            NE\NewCommentPosted::class        => 'handleNewCommentPosted',
            NE\ContentReported::class         => 'handleContentReported',
            NE\SystemUpdateAvailable::class   => 'handleSystemUpdateAvailable',
        ];
    }
}
