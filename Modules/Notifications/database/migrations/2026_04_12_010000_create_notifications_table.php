<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Laravel's default notifications table shape. Every notification
 * dispatched via the `database` channel lands here as a row keyed by
 * the class name in `type` and the recipient in `notifiable_type` +
 * `notifiable_id`.
 *
 * Equivalent to `php artisan notifications:table`, scoped inside the
 * Notifications module so the whole feature disappears cleanly with
 * `module:disable Notifications`.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('type');
            $t->morphs('notifiable');
            $t->text('data');
            $t->timestamp('read_at')->nullable();
            $t->timestamps();

            $t->index(['notifiable_type', 'notifiable_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
