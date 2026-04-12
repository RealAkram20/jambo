<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Three booleans on users for per-channel opt in. Notification classes
 * read these in their via() method and drop channels the user has
 * muted.
 *
 * Defaults:
 *   - in_app  → true   (always useful; free to deliver)
 *   - email   → true   (onboarding assumes users want payment receipts)
 *   - push    → false  (requires explicit browser-permission opt-in)
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->boolean('in_app_notifications_enabled')->default(true)->after('remember_token');
            $t->boolean('email_notifications_enabled')->default(true)->after('in_app_notifications_enabled');
            $t->boolean('push_notifications_enabled')->default(false)->after('email_notifications_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn([
                'in_app_notifications_enabled',
                'email_notifications_enabled',
                'push_notifications_enabled',
            ]);
        });
    }
};
