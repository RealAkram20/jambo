<?php

namespace Modules\Notifications\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * A single user's per-type opt-outs. Sparse: a row exists only when the
 * user has changed a default. Read as the strictest layer in
 * ChannelGatedNotification::via() — a false channel here drops that
 * channel for that type regardless of what the super-admin allows.
 *
 * Keyed cache per (user_id, key) so a user with many prefs doesn't turn
 * every dispatch into N queries; invalidated when the user saves prefs.
 */
class NotificationPreference extends Model
{
    protected $table = 'notification_preferences';

    protected $fillable = [
        'user_id',
        'notification_key',
        'in_app_enabled',
        'email_enabled',
        'push_enabled',
    ];

    protected $casts = [
        'in_app_enabled' => 'bool',
        'email_enabled'  => 'bool',
        'push_enabled'   => 'bool',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Does this user allow $channel for $key? True when there's no
     * override row (inherit = receive) or the row leaves that channel on.
     * $channel is one of 'in_app' | 'email' | 'push'.
     */
    public static function allows(object $notifiable, string $key, string $channel): bool
    {
        // Only real users carry per-type prefs. Guests / non-user
        // notifiables always inherit (nothing to opt out of).
        $userId = $notifiable->id ?? null;
        if (!$userId || !($notifiable instanceof User)) {
            return true;
        }

        $prefs = Cache::remember(
            'notif-prefs:' . $userId,
            now()->addMinute(),
            fn () => static::where('user_id', $userId)
                ->get()
                ->keyBy('notification_key'),
        );

        $row = $prefs->get($key);
        if (!$row) {
            return true; // no override → inherit (receive)
        }

        return match ($channel) {
            'in_app' => (bool) $row->in_app_enabled,
            'email'  => (bool) $row->email_enabled,
            'push'   => (bool) $row->push_enabled,
            default  => true,
        };
    }

    public static function forgetCache(int $userId): void
    {
        Cache::forget('notif-prefs:' . $userId);
    }
}
