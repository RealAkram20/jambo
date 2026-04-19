<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Site-wide on/off switches for every notification type × channel. Each
 * notification class reads its row inside via() and AND-s it against the
 * per-user preference booleans on the users table. Admin-off wins,
 * user-off also wins — whichever is stricter.
 *
 * Seeded by NotificationSettingsSeeder with the 23 known keys.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('notification_settings', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->boolean('system_enabled')->default(true);
            $t->boolean('push_enabled')->default(false);
            $t->boolean('email_enabled')->default(true);
            $t->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
