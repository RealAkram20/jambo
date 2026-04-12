<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Persons — actors, directors, writers, producers, cinematographers.
 * Linked to movies and shows through the respective *_person pivot
 * tables, where the specific role is stored.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('persons', function (Blueprint $t) {
            $t->id();
            $t->string('first_name');
            $t->string('last_name');
            $t->string('slug')->unique();
            $t->text('bio')->nullable();
            $t->date('birth_date')->nullable();
            $t->date('death_date')->nullable();
            $t->string('photo_url')->nullable();
            $t->string('known_for')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('persons');
    }
};
