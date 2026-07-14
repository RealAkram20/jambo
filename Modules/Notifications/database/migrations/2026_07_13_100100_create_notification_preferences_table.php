<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user, per-notification-type opt-outs.
 *
 * The three booleans already on the users table (in_app/email/push
 * _notifications_enabled) are global — they mute a whole channel across
 * every notification type. This table adds the missing per-type dimension
 * so an individual admin can say "don't email me new-signup alerts" while
 * still receiving everything else.
 *
 * Sparse by design: a row exists ONLY when the user has overridden the
 * default for that type. No row = inherit (receive it). A present row's
 * false channel = that user opted out of that channel for that type.
 * ChannelGatedNotification::via() AND-s this in as the strictest layer.
 *
 * Exposed to admins via the admin notification-preferences page; regular
 * users keep the simpler global per-channel toggles for now.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('notification_key');
            $t->boolean('in_app_enabled')->default(true);
            $t->boolean('email_enabled')->default(true);
            $t->boolean('push_enabled')->default(true);
            $t->timestamps();

            $t->unique(['user_id', 'notification_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
