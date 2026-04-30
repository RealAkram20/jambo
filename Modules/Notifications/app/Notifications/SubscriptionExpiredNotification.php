<?php

namespace Modules\Notifications\app\Notifications;

use App\Models\User;

class SubscriptionExpiredNotification extends ChannelGatedNotification
{
    /**
     * Memoised display name resolved from $userId on first use. Lets
     * toDatabase / toMail / toWebPush share one User::find call per
     * notification instance instead of three.
     */
    private ?string $resolvedDisplayName = null;

    public function __construct(
        public readonly int $userId,
        public readonly ?string $planName = null,
    ) {
    }

    protected function settingKey(): string
    {
        return 'subscription_expired';
    }

    public function toDatabase($notifiable): array
    {
        $isOwner = $this->userId === ($notifiable->id ?? null);
        $plan = $this->planName ?: 'The';

        return [
            'title'        => $isOwner ? 'Subscription expired' : 'A subscription expired',
            'message'      => $isOwner
                ? "{$plan} subscription has ended. Renew to regain premium access."
                : $this->resolveDisplayName() . "'s subscription has expired.",
            'icon'         => 'ph-x-circle',
            'colour'       => 'danger',
            'image'        => null,
            'action_url'   => $isOwner
                ? route('profile.membership', ['username' => $notifiable->username])
                : route('dashboard.user-list'),
            'action_label' => $isOwner ? 'Renew' : 'Open user list',
        ];
    }

    /**
     * Best-effort display name for the user whose subscription expired.
     * Prefers first+last, then username, then email local-part. Falls
     * back to the legacy "User #<id>" label only if the row no longer
     * exists (e.g. account was deleted between event and dispatch).
     */
    private function resolveDisplayName(): string
    {
        if ($this->resolvedDisplayName !== null) {
            return $this->resolvedDisplayName;
        }

        $user = User::find($this->userId);
        if (!$user) {
            return $this->resolvedDisplayName = "User #{$this->userId}";
        }

        $first = trim((string) ($user->first_name ?? ''));
        $last = trim((string) ($user->last_name ?? ''));
        if ($first !== '' || $last !== '') {
            return $this->resolvedDisplayName = trim("$first $last");
        }
        if (!empty($user->username)) {
            return $this->resolvedDisplayName = (string) $user->username;
        }
        if (!empty($user->email)) {
            return $this->resolvedDisplayName = explode('@', (string) $user->email)[0];
        }
        return $this->resolvedDisplayName = "User #{$this->userId}";
    }
}
