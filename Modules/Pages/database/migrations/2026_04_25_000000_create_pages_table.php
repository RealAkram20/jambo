<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pages — admin-managed static pages (About Us, Contact, FAQ, Terms,
 * Privacy, plus any custom additions). The frontend hits these by slug;
 * `is_system` flags the seeded set so they can be edited but not deleted.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('title');
            $t->longText('content')->nullable();
            $t->string('featured_image_url')->nullable();
            $t->string('meta_description', 500)->nullable();
            $t->enum('status', ['draft', 'published'])->default('draft')->index();
            $t->boolean('is_system')->default(false);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
