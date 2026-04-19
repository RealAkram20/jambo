<?php

namespace Modules\Notifications\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds one row per known notification key. Re-runnable: uses upsert so
 * existing admin choices are preserved when a new key is added.
 *
 * To add a new notification type site-wide:
 *   1. Append to the $keys array below with sensible channel defaults
 *   2. Run `php artisan db:seed --class=NotificationSettingsSeeder`
 *   3. Build the notification class and gate via() on
 *      NotificationSetting::channelsFor('your_key')
 */
class NotificationSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $keys = [
            // ─── Account & Security ────────────────────────────────
            ['key' => 'user_signup',          'system_enabled' => true,  'push_enabled' => false, 'email_enabled' => true],
            ['key' => 'welcome_user',         'system_enabled' => true,  'push_enabled' => false, 'email_enabled' => true],
            ['key' => 'password_reset',       'system_enabled' => true,  'push_enabled' => false, 'email_enabled' => true],
            ['key' => 'email_verified',       'system_enabled' => true,  'push_enabled' => false, 'email_enabled' => true],
            ['key' => 'new_device_login',     'system_enabled' => true,  'push_enabled' => true,  'email_enabled' => true],
            ['key' => 'account_deactivated',  'system_enabled' => true,  'push_enabled' => false, 'email_enabled' => true],

            // ─── Billing & Subscriptions ───────────────────────────
            ['key' => 'order_confirmation',   'system_enabled' => true,  'push_enabled' => false, 'email_enabled' => true],
            ['key' => 'payment_received',     'system_enabled' => true,  'push_enabled' => true,  'email_enabled' => true],
            ['key' => 'payment_failed',       'system_enabled' => true,  'push_enabled' => true,  'email_enabled' => true],
            ['key' => 'subscription_activated','system_enabled'=> true,  'push_enabled' => false, 'email_enabled' => true],
            ['key' => 'subscription_expiring','system_enabled' => true,  'push_enabled' => true,  'email_enabled' => true],
            ['key' => 'subscription_expired', 'system_enabled' => true,  'push_enabled' => true,  'email_enabled' => true],
            ['key' => 'subscription_cancelled','system_enabled'=> true,  'push_enabled' => false, 'email_enabled' => true],

            // ─── Content Updates ───────────────────────────────────
            ['key' => 'movie_added',          'system_enabled' => true,  'push_enabled' => true,  'email_enabled' => false],
            ['key' => 'show_added',           'system_enabled' => true,  'push_enabled' => true,  'email_enabled' => false],
            ['key' => 'season_added',         'system_enabled' => true,  'push_enabled' => true,  'email_enabled' => false],
            ['key' => 'episode_added',        'system_enabled' => true,  'push_enabled' => true,  'email_enabled' => false],
            ['key' => 'watchlist_available',  'system_enabled' => true,  'push_enabled' => true,  'email_enabled' => false],

            // ─── Admin & Moderation ────────────────────────────────
            ['key' => 'admin_broadcast',      'system_enabled' => true,  'push_enabled' => true,  'email_enabled' => true],
            ['key' => 'new_review_posted',    'system_enabled' => true,  'push_enabled' => false, 'email_enabled' => false],
            ['key' => 'new_comment_posted',   'system_enabled' => true,  'push_enabled' => false, 'email_enabled' => false],
            ['key' => 'content_reported',     'system_enabled' => true,  'push_enabled' => true,  'email_enabled' => true],
            ['key' => 'system_update_available','system_enabled' => true,'push_enabled' => false, 'email_enabled' => true],
        ];

        $rows = array_map(fn ($row) => $row + ['updated_at' => $now], $keys);

        // upsert keeps existing admin tweaks intact — new rows only.
        DB::table('notification_settings')->upsert(
            $rows,
            ['key'],
            [] // no columns to overwrite on conflict
        );
    }
}
