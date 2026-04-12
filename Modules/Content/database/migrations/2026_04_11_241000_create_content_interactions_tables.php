<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * User-generated interactions on content: star ratings, long-form
 * reviews, and threaded comments. All three are polymorphic so the
 * same tables cover movies, shows, and individual episodes.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->morphs('ratable');
            $t->unsignedTinyInteger('stars');     // 1..5
            $t->timestamps();

            $t->unique(['user_id', 'ratable_type', 'ratable_id'], 'ratings_user_target_unique');
        });

        Schema::create('reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->morphs('reviewable');
            $t->string('title')->nullable();
            $t->text('body');
            $t->unsignedTinyInteger('stars')->nullable();
            $t->boolean('is_published')->default(true);
            $t->timestamps();

            $t->index(['reviewable_type', 'reviewable_id', 'is_published'], 'reviews_target_published_idx');
        });

        Schema::create('comments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->morphs('commentable');
            $t->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
            $t->text('body');
            $t->boolean('is_approved')->default(true);
            $t->timestamps();

            $t->index(['commentable_type', 'commentable_id', 'is_approved'], 'comments_target_approved_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('ratings');
    }
};
