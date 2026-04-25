<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tiny anchor table for guest push subscriptions.
 *
 * The `push_subscriptions` table is morph-keyed on subscribable_*. To
 * support anonymous (logged-out) browsers without making those columns
 * nullable, we share a single row in this table — id=1 — that every
 * guest endpoint points at. Dispatching to "all guests" then becomes
 * Guest::singleton()->notify($n), which the WebPushChannel fans out
 * across every guest endpoint exactly the same way it does for users.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('notification_guests', function (Blueprint $table) {
            $table->bigIncrements('id');
        });

        DB::table('notification_guests')->insertOrIgnore(['id' => 1]);
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_guests');
    }
};
