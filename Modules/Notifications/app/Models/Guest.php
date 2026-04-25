<?php

namespace Modules\Notifications\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use NotificationChannels\WebPush\HasPushSubscriptions;

/**
 * Singleton notifiable representing every anonymous (logged-out)
 * browser that opted in to push. There is exactly one row in this
 * model's table — id=1, seeded by the migration. All guest
 * push_subscriptions rows point at it via the morph relation.
 *
 * Channel toggles are hard-coded so the gating logic in
 * ChannelGatedNotification::via() resolves correctly: guests can
 * receive push, but never database (no inbox) or mail (no address).
 */
class Guest extends Model
{
    use Notifiable;
    use HasPushSubscriptions;

    public const SINGLETON_ID = 1;

    protected $table = 'notification_guests';

    public $timestamps = false;

    protected $guarded = [];

    /**
     * Resolve the one-and-only guest row. Created lazily on first
     * call in case the seeding migration was skipped.
     */
    public static function singleton(): self
    {
        return static::firstOrCreate(['id' => self::SINGLETON_ID]);
    }

    public function getInAppNotificationsEnabledAttribute(): bool
    {
        return false;
    }

    public function getEmailNotificationsEnabledAttribute(): bool
    {
        return false;
    }

    public function getPushNotificationsEnabledAttribute(): bool
    {
        return true;
    }
}
