<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Earning attribution: which partners earn from which title, and at
 * what percentage of that title's qualified watch-minutes.
 *
 * splittable is a Movie or a Show FQCN (no app-wide morph map, so the
 * class string is stored as-is — same convention as watch_history).
 * Episodes are NOT split directly: an episode's minutes resolve to its
 * parent Show's split set via qualified_views.show_id.
 *
 * Percentages per title may sum to LESS than 100 — the unassigned
 * remainder deliberately stays with the platform at month close.
 * Service-layer validation forbids sums above 100.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('title_splits', function (Blueprint $table) {
            $table->id();
            $table->string('splittable_type', 191);
            $table->unsignedBigInteger('splittable_id');
            $table->foreignId('partner_id')
                ->constrained('monetization_partners')->cascadeOnDelete();
            $table->decimal('percent', 5, 2); // 0.01 .. 100.00
            $table->timestamps();

            $table->unique(
                ['splittable_type', 'splittable_id', 'partner_id'],
                'title_splits_title_partner_unique'
            );
            $table->index('partner_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('title_splits');
    }
};
