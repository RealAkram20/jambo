<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Classification pivots — six many-to-many tables linking movies and
 * shows to categories, genres, and tags. All use composite primary
 * keys (no surrogate id) and cascade on delete in both directions.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('category_movie', function (Blueprint $t) {
            $t->foreignId('category_id')->constrained()->cascadeOnDelete();
            $t->foreignId('movie_id')->constrained()->cascadeOnDelete();
            $t->primary(['category_id', 'movie_id']);
        });

        Schema::create('category_show', function (Blueprint $t) {
            $t->foreignId('category_id')->constrained()->cascadeOnDelete();
            $t->foreignId('show_id')->constrained()->cascadeOnDelete();
            $t->primary(['category_id', 'show_id']);
        });

        Schema::create('genre_movie', function (Blueprint $t) {
            $t->foreignId('genre_id')->constrained()->cascadeOnDelete();
            $t->foreignId('movie_id')->constrained()->cascadeOnDelete();
            $t->primary(['genre_id', 'movie_id']);
        });

        Schema::create('genre_show', function (Blueprint $t) {
            $t->foreignId('genre_id')->constrained()->cascadeOnDelete();
            $t->foreignId('show_id')->constrained()->cascadeOnDelete();
            $t->primary(['genre_id', 'show_id']);
        });

        Schema::create('movie_tag', function (Blueprint $t) {
            $t->foreignId('movie_id')->constrained()->cascadeOnDelete();
            $t->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $t->primary(['movie_id', 'tag_id']);
        });

        Schema::create('show_tag', function (Blueprint $t) {
            $t->foreignId('show_id')->constrained()->cascadeOnDelete();
            $t->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $t->primary(['show_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('show_tag');
        Schema::dropIfExists('movie_tag');
        Schema::dropIfExists('genre_show');
        Schema::dropIfExists('genre_movie');
        Schema::dropIfExists('category_show');
        Schema::dropIfExists('category_movie');
    }
};
