<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The FCM registration token for an app install.
 *
 * It lives on `devices` rather than in its own table because the push standard
 * (`~/.claude/skills/background-push`) requires a token registry that is per
 * user, per install, idempotent, and — the rule that actually bites —
 * **deleted on sign-out**. A shared handset that keeps the previous user's
 * token delivers their notifications, on the lock screen, to whoever is
 * holding it.
 *
 * `devices` is already exactly one row per (account, install) and already has
 * a revoke path that logout, booting and password changes all run through.
 * Hanging the token there means every one of those paths clears it without
 * anybody having to remember to.
 *
 * `push_subscriptions` is left alone: it is web-push shaped (endpoint, keys)
 * and belongs to the browser.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            // Long: FCM tokens are ~163 characters today and Google has never
            // promised a bound.
            $table->string('fcm_token', 512)->nullable()->after('app_version');

            // The viewer's own switch for this install, separate from the
            // per-notification-type preferences. Turning push off on a phone
            // must not require unsubscribing every category.
            $table->boolean('push_enabled')->default(true)->after('fcm_token');

            // Finding every install to push to for one account.
            $table->index(['user_id', 'push_enabled']);
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'push_enabled']);
            $table->dropColumn(['fcm_token', 'push_enabled']);
        });
    }
};
