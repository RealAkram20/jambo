<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gives a VJ an actual identity.
 *
 * Until now a Vj row was a name, a slug and a colour swatch — the admin list
 * rendered a coloured square with a microphone glyph because there was no image
 * to render, and the public VJ page illustrated itself with a still lifted from
 * whichever film the VJ most recently narrated.
 *
 * `photo_url` fixes that. The social columns matter more than they look: they
 * feed schema.org `sameAs` on the Person node, which is what tells Google that
 * the "VJ Junior" on our page is the same entity as the "VJ Junior" with an
 * existing audience on YouTube and TikTok. That link is the strongest
 * entity-disambiguation signal available to us, and "vj junior" is the site's
 * highest-volume query.
 *
 * All nullable: 36 existing VJs must keep working with every field empty, and
 * StructuredData omits any key it has no value for rather than emitting a null.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vjs', function (Blueprint $table) {
            $table->string('photo_url')->nullable()->after('colour');

            $table->string('youtube_url')->nullable()->after('description');
            $table->string('tiktok_url')->nullable()->after('youtube_url');
            $table->string('facebook_url')->nullable()->after('tiktok_url');
            $table->string('instagram_url')->nullable()->after('facebook_url');
            $table->string('website_url')->nullable()->after('instagram_url');
        });
    }

    public function down(): void
    {
        Schema::table('vjs', function (Blueprint $table) {
            $table->dropColumn([
                'photo_url',
                'youtube_url',
                'tiktok_url',
                'facebook_url',
                'instagram_url',
                'website_url',
            ]);
        });
    }
};
