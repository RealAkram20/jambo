<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Seasons — group episodes under a show. `number` is the human
 * season number (1, 2, 3...) and is unique per show.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('seasons', function (Blueprint $t) {
            $t->id();
            $t->foreignId('show_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('number');
            $t->string('title')->nullable();
            $t->text('synopsis')->nullable();
            $t->string('poster_url')->nullable();
            $t->date('released_at')->nullable();
            $t->timestamps();

            $t->unique(['show_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seasons');
    }
};
