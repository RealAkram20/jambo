<?php

namespace Modules\Notifications\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Notifications\app\Models\NotificationAudienceSetting;
use Modules\Notifications\app\Models\NotificationSetting;

/**
 * Seeds one row per (role-targeted notification key, audience). Derives
 * the audience set from each type's definitions() 'audience' tag and the
 * default channel switches from NotificationSettingsSeeder's flat
 * defaults, so day-one behaviour is identical to before this table
 * existed — the super-admin can then diverge audiences from the UI.
 *
 * Re-runnable: insertOrIgnore on (notification_key, audience), so existing
 * super-admin edits are preserved and only missing rows are added.
 */
class NotificationAudienceSettingsSeeder extends Seeder
{
    /**
     * Flat per-channel defaults mirrored from NotificationSettingsSeeder.
     * Keyed by notification key → [in_app, email, push].
     */
    private const CHANNEL_DEFAULTS = [
        'user_signup'             => ['in_app' => true, 'email' => true,  'push' => false],
        'payment_received'        => ['in_app' => true, 'email' => true,  'push' => true],
        'payment_failed'          => ['in_app' => true, 'email' => true,  'push' => true],
        'subscription_expired'    => ['in_app' => true, 'email' => true,  'push' => true],
        'subscription_cancelled'  => ['in_app' => true, 'email' => true,  'push' => false],
        'withdrawal_requested'    => ['in_app' => true, 'email' => true,  'push' => false],
        'movie_added'             => ['in_app' => true, 'email' => false, 'push' => true],
        'show_added'              => ['in_app' => true, 'email' => false, 'push' => true],
        'season_added'            => ['in_app' => true, 'email' => false, 'push' => true],
        'episode_added'           => ['in_app' => true, 'email' => false, 'push' => true],
        'admin_broadcast'         => ['in_app' => true, 'email' => true,  'push' => true],
        'new_review_posted'       => ['in_app' => true, 'email' => false, 'push' => false],
        'new_comment_posted'      => ['in_app' => true, 'email' => false, 'push' => false],
        'content_reported'        => ['in_app' => true, 'email' => true,  'push' => true],
        'system_update_available' => ['in_app' => true, 'email' => true,  'push' => false],
    ];

    public function run(): void
    {
        $now = now();
        $rows = [];

        foreach (NotificationSetting::definitions() as $group) {
            foreach ($group['items'] as $item) {
                $audiences = NotificationAudienceSetting::audiencesForTag($item['audience']);
                if ($audiences === []) {
                    continue; // personal type — no audience dimension
                }

                $defaults = self::CHANNEL_DEFAULTS[$item['key']]
                    ?? ['in_app' => true, 'email' => true, 'push' => false];

                foreach ($audiences as $audience) {
                    $rows[] = [
                        'notification_key' => $item['key'],
                        'audience'         => $audience,
                        'in_app_enabled'   => $defaults['in_app'],
                        'email_enabled'    => $defaults['email'],
                        'push_enabled'     => $defaults['push'],
                        'updated_at'       => $now,
                    ];
                }
            }
        }

        // insertOrIgnore preserves existing super-admin edits — new rows only.
        // (upsert() with an empty update list compiles to a plain INSERT in
        // Laravel and violates the (key, audience) unique index on re-run.)
        DB::table('notification_audience_settings')->insertOrIgnore($rows);
    }
}
