<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Minimal audit trail for every money-shaping mutation in the module
 * (no activity-log package is installed; this avoids a new dependency).
 *
 * Actions: settings.updated, partner.enrolled, partner.updated,
 * partner.suspended, payout_profile.submitted / .verified,
 * split.updated, period.computed / .closed, withdrawal.requested /
 * .approved / .paid / .rejected, adjustment.created.
 *
 * actor_name is a snapshot so the trail stays readable after the
 * actor's account is deleted.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('monetization_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->string('actor_name')->nullable();
            $table->string('action', 64);
            $table->string('subject_type', 191)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('changes')->nullable(); // {before: {}, after: {}}
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monetization_audit_logs');
    }
};
