<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vjs', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('colour', 7)->nullable();
            $t->text('description')->nullable();
            $t->timestamps();
        });

        Schema::create('movie_vj', function (Blueprint $t) {
            $t->foreignId('movie_id')->constrained()->cascadeOnDelete();
            $t->foreignId('vj_id')->constrained()->cascadeOnDelete();
            $t->primary(['movie_id', 'vj_id']);
        });

        Schema::create('show_vj', function (Blueprint $t) {
            $t->foreignId('show_id')->constrained()->cascadeOnDelete();
            $t->foreignId('vj_id')->constrained()->cascadeOnDelete();
            $t->primary(['show_id', 'vj_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('show_vj');
        Schema::dropIfExists('movie_vj');
        Schema::dropIfExists('vjs');
    }
};
