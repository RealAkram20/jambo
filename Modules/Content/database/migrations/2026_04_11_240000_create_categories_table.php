<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Categories — editorial buckets used to surface content on the home page
 * (e.g. "Trending", "New Releases", "Editor's Picks"). A piece of content
 * can belong to many categories and categories can be reordered via
 * `sort_order` for the storefront.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->text('description')->nullable();
            $t->string('cover_url')->nullable();
            $t->integer('sort_order')->default(0);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
