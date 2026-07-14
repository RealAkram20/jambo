<?php

namespace Modules\Notifications\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Super-admin-controlled, per-audience notification switches. One row per
 * (notification_key, audience) with a boolean per channel. This is the
 * layer that lets the platform owner route a notification type to some
 * role-audiences but not others (e.g. system updates to super-admins only).
 *
 * Audience of a recipient is decided by their highest role — see
 * audienceFor(). Only role-targeted types (Admin/All in
 * NotificationSetting::definitions()) have rows here; personal types fall
 * back to the flat NotificationSetting row inside via().
 *
 * channelsFor() is cached a minute per (key, audience), mirroring
 * NotificationSetting so hot dispatch loops don't hit the DB.
 */
class NotificationAudienceSetting extends Model
{
    protected $table = 'notification_audience_settings';
    public $timestamps = false;

    protected $fillable = [
        'notification_key',
        'audience',
        'in_app_enabled',
        'email_enabled',
        'push_enabled',
        'updated_at',
    ];

    protected $casts = [
        'in_app_enabled' => 'bool',
        'email_enabled'  => 'bool',
        'push_enabled'   => 'bool',
        'updated_at'     => 'datetime',
    ];

    public const AUDIENCE_SUPER_ADMIN = 'super_admin';
    public const AUDIENCE_ADMIN        = 'admin';
    public const AUDIENCE_USER         = 'user';

    /**
     * The audiences a notification type can be routed to, derived from its
     * definitions() 'audience' tag:
     *   - 'Admin' → admins + super-admins (role-targeted staff alert)
     *   - 'All'   → users + admins + super-admins (broadcast)
     *   - 'User'  → [] (personal; no audience dimension, uses the flat row)
     *
     * @return list<string>
     */
    public static function audiencesForTag(string $tag): array
    {
        return match ($tag) {
            'Admin' => [self::AUDIENCE_SUPER_ADMIN, self::AUDIENCE_ADMIN],
            'All'   => [self::AUDIENCE_SUPER_ADMIN, self::AUDIENCE_ADMIN, self::AUDIENCE_USER],
            default => [],
        };
    }

    /**
     * Classify a notifiable into a single audience by highest role.
     * Super-admins always also hold the `admin` role, so check
     * super-admin first. Anything without an admin role (or that can't
     * carry roles, e.g. the guest push singleton) is a plain user.
     */
    public static function audienceFor(object $notifiable): string
    {
        if (method_exists($notifiable, 'hasRole')) {
            if ($notifiable->hasRole('super-admin')) {
                return self::AUDIENCE_SUPER_ADMIN;
            }
            if ($notifiable->hasRole('admin')) {
                return self::AUDIENCE_ADMIN;
            }
        }
        return self::AUDIENCE_USER;
    }

    /**
     * Channel switches for a (key, audience), or null when no row exists
     * (caller should then fall back to the flat NotificationSetting row).
     *
     * @return array{in_app: bool, email: bool, push: bool}|null
     */
    public static function channelsFor(string $key, string $audience): ?array
    {
        return Cache::remember(
            'notif-audience:' . $key . ':' . $audience,
            now()->addMinute(),
            function () use ($key, $audience) {
                $row = static::where('notification_key', $key)
                    ->where('audience', $audience)
                    ->first();

                if (!$row) {
                    return null;
                }

                return [
                    'in_app' => (bool) $row->in_app_enabled,
                    'email'  => (bool) $row->email_enabled,
                    'push'   => (bool) $row->push_enabled,
                ];
            }
        );
    }

    /**
     * True when this key is audience-controlled — i.e. its definitions()
     * tag is role-targeted (Admin/All). Lets via() tell apart two "no row
     * for this audience" cases: a role-targeted type where THIS audience
     * was deliberately excluded (→ deny), versus a personal type with no
     * audience concept (→ fall back to the flat NotificationSetting row).
     *
     * Derived from definitions(), NOT from which rows happen to exist in
     * the table. Row presence would fail OPEN for a role-targeted type
     * that was added to definitions() but never seeded: with no rows the
     * key would look "not audience-controlled", skip the matrix entirely
     * and fall through to the flat switch, whose own missing-row default
     * is permissive. Reading the tag makes that case deny instead, so the
     * super-admin's matrix is always the authority for staff-facing types.
     */
    public static function keyIsAudienceControlled(string $key): bool
    {
        $keys = Cache::remember(
            'notif-audience:controlled-keys',
            now()->addMinute(),
            function () {
                $controlled = [];
                foreach (NotificationSetting::definitions() as $group) {
                    foreach ($group['items'] as $item) {
                        if (self::audiencesForTag($item['audience']) !== []) {
                            $controlled[] = $item['key'];
                        }
                    }
                }
                return $controlled;
            },
        );

        return in_array($key, $keys, true);
    }

    public static function forgetAllCache(): void
    {
        Cache::forget('notif-audience:controlled-keys');
        foreach (static::all() as $row) {
            Cache::forget('notif-audience:' . $row->notification_key . ':' . $row->audience);
        }
    }
}
