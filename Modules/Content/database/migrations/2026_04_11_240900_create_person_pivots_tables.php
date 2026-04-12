<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cast & crew pivots — attach persons to movies and shows with a
 * role (actor, director, writer, producer, cinematographer). The
 * same person can appear multiple times against the same title
 * under different roles, so `role` is part of the composite PK.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('movie_person', function (Blueprint $t) {
            $t->foreignId('movie_id')->constrained()->cascadeOnDelete();
            $t->foreignId('person_id')->constrained('persons')->cascadeOnDelete();
            $t->string('role');                 // actor|director|writer|producer|cinematographer
            $t->string('character_name')->nullable();
            $t->integer('display_order')->default(0);

            $t->primary(['movie_id', 'person_id', 'role']);
        });

        Schema::create('show_person', function (Blueprint $t) {
            $t->foreignId('show_id')->constrained()->cascadeOnDelete();
            $t->foreignId('person_id')->constrained('persons')->cascadeOnDelete();
            $t->string('role');
            $t->string('character_name')->nullable();
            $t->integer('display_order')->default(0);

            $t->primary(['show_id', 'person_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('show_person');
        Schema::dropIfExists('movie_person');
    }
};
