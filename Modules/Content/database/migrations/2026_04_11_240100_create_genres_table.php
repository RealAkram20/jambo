<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Genres — the traditional taxonomic classification of a title
 * (Action, Drama, Sci-Fi...). Kept separate from editorial categories
 * because they have different lifecycles: categories change weekly,
 * genres rarely change at all.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('genres', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('colour', 7)->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('genres');
    }
};
