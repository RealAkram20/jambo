<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Episodes — a single playable unit within a season. Carries its own
 * `dropbox_path`, runtime, and tier gating so that a single episode
 * can, for example, be free while the rest of the season requires a tier.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('episodes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('season_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('number');
            $t->string('title');
            $t->text('synopsis')->nullable();
            $t->unsignedInteger('runtime_minutes')->nullable();
            $t->string('still_url')->nullable();
            $t->string('dropbox_path')->nullable();
            $t->string('tier_required')->nullable();
            $t->timestamp('published_at')->nullable();
            $t->timestamps();

            $t->unique(['season_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};
