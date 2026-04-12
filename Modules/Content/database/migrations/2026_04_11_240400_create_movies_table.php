<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Movies — single-file long-form content. The actual media lives on
 * Dropbox (see `dropbox_path`); this table holds the metadata, tier
 * gating, and lifecycle flags used by the storefront and the player.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('movies', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('slug')->unique();
            $t->text('synopsis')->nullable();
            $t->unsignedSmallInteger('year')->nullable();
            $t->unsignedInteger('runtime_minutes')->nullable();
            $t->string('rating', 8)->nullable();           // G | PG | PG-13 | R | NC-17
            $t->string('poster_url')->nullable();
            $t->string('backdrop_url')->nullable();
            $t->string('trailer_url')->nullable();
            $t->string('dropbox_path')->nullable();
            $t->string('tier_required')->nullable();       // slug of subscription tier, null = free
            $t->string('status')->default('draft');        // draft | published
            $t->timestamp('published_at')->nullable();
            $t->unsignedBigInteger('views_count')->default(0);
            $t->timestamps();

            $t->index(['status', 'published_at']);
            $t->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};
