<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Shows — episodic content. Runtime and the dropbox path live on each
 * episode, not here. A show is effectively a metadata shell that
 * groups seasons.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('shows', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('slug')->unique();
            $t->text('synopsis')->nullable();
            $t->unsignedSmallInteger('year')->nullable();
            $t->string('rating', 8)->nullable();
            $t->string('poster_url')->nullable();
            $t->string('backdrop_url')->nullable();
            $t->string('trailer_url')->nullable();
            $t->string('tier_required')->nullable();
            $t->string('status')->default('draft');
            $t->timestamp('published_at')->nullable();
            $t->unsignedBigInteger('views_count')->default(0);
            $t->timestamps();

            $t->index(['status', 'published_at']);
            $t->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shows');
    }
};
