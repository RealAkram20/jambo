<?php

namespace Modules\Notifications\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Global admin-controlled notification switches. One row per notification
 * key (e.g. "payment_received", "movie_added") with three booleans: one
 * per delivery channel. Results are cached per-key for a minute so every
 * dispatch doesn't hit the DB.
 *
 * Notification classes should call NotificationSetting::channelsFor($key)
 * at the top of via() and AND each returned flag with the notifiable's
 * own per-user boolean. Admin can fully disable a notification type by
 * turning all three switches off — via() then returns an empty array and
 * the notification is silently dropped.
 */
class NotificationSetting extends Model
{
    protected $table = 'notification_settings';
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'key',
        'system_enabled',
        'push_enabled',
        'email_enabled',
        'updated_at',
    ];

    protected $casts = [
        'system_enabled' => 'bool',
        'push_enabled'   => 'bool',
        'email_enabled'  => 'bool',
        'updated_at'     => 'datetime',
    ];

    /**
     * @return array{system: bool, push: bool, email: bool}
     */
    public static function channelsFor(string $key): array
    {
        return Cache::remember(
            'notif-settings:' . $key,
            now()->addMinute(),
            function () use ($key) {
                $row = static::find($key);

                // Missing row = feature is newly added and not yet seeded;
                // default to the conservative per-channel defaults so
                // dispatch still works. Admin can flip push on from the UI
                // once the row is created by the seeder.
                if (!$row) {
                    return ['system' => true, 'push' => false, 'email' => true];
                }

                return [
                    'system' => (bool) $row->system_enabled,
                    'push'   => (bool) $row->push_enabled,
                    'email'  => (bool) $row->email_enabled,
                ];
            }
        );
    }

    public static function forgetCache(string $key): void
    {
        Cache::forget('notif-settings:' . $key);
    }

    public static function forgetAllCache(): void
    {
        foreach (static::all() as $row) {
            Cache::forget('notif-settings:' . $row->key);
        }
    }

    /**
     * The canonical list of notification types, grouped into the four
     * categories that drive the admin settings page tab layout. Every
     * key here must also have a matching row seeded by
     * NotificationSettingsSeeder.
     *
     * Shape: [ categoryId => [ 'label'=>.., 'icon'=>.., 'items'=>[ ... ] ] ]
     *
     *   item: [
     *     'key'         => 'movie_added',
     *     'label'       => 'Movie added',
     *     'description' => 'A new movie appears in the catalogue.',
     *     'icon'        => 'ph-film-strip',
     *     'colour'      => 'primary',
     *     'audience'    => 'User'  or 'Admin'  or 'All',
     *   ]
     *
     * @return array<string, array{label:string, icon:string, items: list<array{key:string,label:string,description:string,icon:string,colour:string,audience:string}>}>
     */
    public static function definitions(): array
    {
        return [
            'account' => [
                'label' => 'Account & Security',
                'icon'  => 'ph-user-circle',
                'items' => [
                    ['key' => 'user_signup',         'label' => 'New user signup',       'description' => 'A new account is created on the platform.',    'icon' => 'ph-user-plus',        'colour' => 'primary', 'audience' => 'Admin'],
                    ['key' => 'welcome_user',        'label' => 'Welcome message',       'description' => 'Friendly welcome after signup completes.',     'icon' => 'ph-hand-waving',      'colour' => 'primary', 'audience' => 'User'],
                    ['key' => 'password_reset',      'label' => 'Password reset',        'description' => 'Password reset link and confirmation.',        'icon' => 'ph-key',              'colour' => 'warning', 'audience' => 'User'],
                    ['key' => 'email_verified',     'label' => 'Email verified',        'description' => 'User successfully verified their email.',      'icon' => 'ph-envelope-open',    'colour' => 'success', 'audience' => 'User'],
                    ['key' => 'new_device_login',   'label' => 'New device login',      'description' => 'Sign-in from an unrecognised browser or IP.',  'icon' => 'ph-device-mobile',    'colour' => 'warning', 'audience' => 'User'],
                    ['key' => 'account_deactivated','label' => 'Account deactivated',   'description' => 'Account was deactivated manually or by admin.','icon' => 'ph-user-minus',       'colour' => 'danger',  'audience' => 'User'],
                ],
            ],
            'billing' => [
                'label' => 'Billing & Subscriptions',
                'icon'  => 'ph-credit-card',
                'items' => [
                    ['key' => 'order_confirmation',     'label' => 'Order confirmation',    'description' => 'User placed a paid order (pre-capture).',      'icon' => 'ph-receipt',           'colour' => 'primary', 'audience' => 'User'],
                    ['key' => 'payment_received',       'label' => 'Payment received',      'description' => 'Payment was captured successfully.',           'icon' => 'ph-credit-card',       'colour' => 'success', 'audience' => 'All'],
                    ['key' => 'payment_failed',         'label' => 'Payment failed',        'description' => 'Payment attempt failed or was declined.',      'icon' => 'ph-warning-octagon',   'colour' => 'danger',  'audience' => 'All'],
                    ['key' => 'subscription_activated', 'label' => 'Subscription activated','description' => 'User\'s subscription is now active.',          'icon' => 'ph-crown-simple',      'colour' => 'success', 'audience' => 'User'],
                    ['key' => 'subscription_expiring',  'label' => 'Subscription expiring', 'description' => 'Renewal reminder a few days before expiry.',   'icon' => 'ph-hourglass-medium',  'colour' => 'warning', 'audience' => 'User'],
                    ['key' => 'subscription_expired',   'label' => 'Subscription expired',  'description' => 'Subscription lapsed without renewal.',         'icon' => 'ph-x-circle',          'colour' => 'danger',  'audience' => 'All'],
                    ['key' => 'subscription_cancelled', 'label' => 'Subscription cancelled','description' => 'User or admin cancelled the subscription.',    'icon' => 'ph-prohibit',          'colour' => 'danger',  'audience' => 'Admin'],
                ],
            ],
            'content' => [
                'label' => 'Content Updates',
                'icon'  => 'ph-film-slate',
                'items' => [
                    ['key' => 'movie_added',         'label' => 'Movie added',        'description' => 'New movie appears in the catalogue.',           'icon' => 'ph-film-strip',        'colour' => 'primary', 'audience' => 'User'],
                    ['key' => 'show_added',          'label' => 'TV show added',      'description' => 'New TV show appears in the catalogue.',         'icon' => 'ph-television',        'colour' => 'primary', 'audience' => 'User'],
                    ['key' => 'season_added',        'label' => 'Season added',       'description' => 'A new season was added to an existing show.',   'icon' => 'ph-stack',             'colour' => 'primary', 'audience' => 'User'],
                    ['key' => 'episode_added',       'label' => 'Episode added',      'description' => 'A new episode was added to a tracked season.',  'icon' => 'ph-play-circle',       'colour' => 'primary', 'audience' => 'User'],
                    ['key' => 'watchlist_available', 'label' => 'Watchlist available','description' => 'An item on the user\'s watchlist is now live.', 'icon' => 'ph-bookmark-simple',   'colour' => 'info',    'audience' => 'User'],
                ],
            ],
            'admin' => [
                'label' => 'Admin & Moderation',
                'icon'  => 'ph-shield-check',
                'items' => [
                    ['key' => 'admin_broadcast',        'label' => 'Admin broadcast',        'description' => 'One-to-many announcement sent from the admin.','icon' => 'ph-megaphone',         'colour' => 'primary', 'audience' => 'All'],
                    ['key' => 'new_review_posted',      'label' => 'New review posted',      'description' => 'A user posted a review that needs moderation.','icon' => 'ph-star',              'colour' => 'warning', 'audience' => 'Admin'],
                    ['key' => 'new_comment_posted',     'label' => 'New comment posted',     'description' => 'A user posted a comment that needs moderation.','icon' => 'ph-chat-circle-dots', 'colour' => 'warning', 'audience' => 'Admin'],
                    ['key' => 'content_reported',       'label' => 'Content reported',       'description' => 'A user flagged content as inappropriate.',     'icon' => 'ph-flag',              'colour' => 'danger',  'audience' => 'Admin'],
                    ['key' => 'system_update_available','label' => 'System update available','description' => 'A new Jambo release is available to install.', 'icon' => 'ph-download-simple',   'colour' => 'info',    'audience' => 'Admin'],
                ],
            ],
        ];
    }

    /**
     * All keys that should exist as rows. Used by the controller to
     * validate the incoming update payload and by the seeder indirectly
     * via its own hard-coded list.
     *
     * @return list<string>
     */
    public static function knownKeys(): array
    {
        $keys = [];
        foreach (static::definitions() as $group) {
            foreach ($group['items'] as $item) {
                $keys[] = $item['key'];
            }
        }
        return $keys;
    }
}
