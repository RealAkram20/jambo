<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-audience refinement of the site-wide notification switches.
 *
 * The existing `notification_settings` table is one on/off-per-channel row
 * per notification key — it can't say "send payment_failed to super-admins
 * but not regular admins." This table adds that missing audience dimension:
 * one row per (key, audience) where audience ∈ {super_admin, admin, user}.
 *
 * Only role-targeted notification types (the ones tagged Admin/All in
 * NotificationSetting::definitions()) get rows here. Personal, single-
 * recipient types (password_reset, welcome_user, …) have no audience
 * choice — they go to the individual involved — so they keep using the
 * flat `notification_settings` row, and ChannelGatedNotification::via()
 * falls back to it when no audience row exists for a (key, audience).
 *
 * Seeded by NotificationAudienceSettingsSeeder. Super-admin edits it from
 * the notification settings page.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('notification_audience_settings', function (Blueprint $t) {
            $t->id();
            $t->string('notification_key');
            // 'super_admin' | 'admin' | 'user'
            $t->string('audience', 20);
            $t->boolean('in_app_enabled')->default(true);
            $t->boolean('email_enabled')->default(true);
            $t->boolean('push_enabled')->default(false);
            $t->timestamp('updated_at')->nullable();

            $t->unique(['notification_key', 'audience']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_audience_settings');
    }
};
