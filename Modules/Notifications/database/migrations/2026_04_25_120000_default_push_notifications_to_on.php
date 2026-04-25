<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Flip the column-level default for push_notifications_enabled from 0
 * to 1 so that any future direct INSERT (seeders, raw SQL, etc.) gets
 * an opted-in user. Existing rows are deliberately left alone — users
 * who previously toggled push off should stay off.
 *
 * Eloquent's User::$attributes already covers the create() path; this
 * migration is the belt-and-suspenders for the DB layer.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'push_notifications_enabled')) {
            return;
        }

        DB::statement("ALTER TABLE `users` ALTER COLUMN `push_notifications_enabled` SET DEFAULT 1");
    }

    public function down(): void
    {
        if (!Schema::hasColumn('users', 'push_notifications_enabled')) {
            return;
        }

        DB::statement("ALTER TABLE `users` ALTER COLUMN `push_notifications_enabled` SET DEFAULT 0");
    }
};
