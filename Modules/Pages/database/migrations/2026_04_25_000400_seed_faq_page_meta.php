<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the FAQ page's structured meta with the original 5 questions
 * + placeholder answers so the admin always starts with editable rows.
 * Drives the public accordion via pages.meta JSON.
 *
 * Clears the rich-text content column for faqs so admins aren't editing
 * the same data in two places.
 */
return new class extends Migration {
    public function up(): void
    {
        $body = 'It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters, as opposed to using "Content here, content here", making it look like readable English.';

        $defaults = [
            'questions' => [
                ['q' => 'What Is Jambo?', 'a' => $body],
                ['q' => 'Will my account work outside my country?', 'a' => $body],
                ['q' => 'I am facing video playback issues. What do I do?', 'a' => $body],
                ['q' => 'How can I manage notifications?', 'a' => $body],
                ['q' => 'What benefits do I get with the packs?', 'a' => $body],
            ],
        ];

        DB::table('pages')
            ->where('slug', 'faqs')
            ->whereNull('meta')
            ->update([
                'meta' => json_encode($defaults),
                'content' => null,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Non-destructive — leave admin-customised values alone.
    }
};
