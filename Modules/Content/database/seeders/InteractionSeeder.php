<?php

namespace Modules\Content\database\seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Content\app\Models\Comment;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Rating;
use Modules\Content\app\Models\Review;
use Modules\Content\app\Models\Show;

class InteractionSeeder extends Seeder
{
    /**
     * Seed sample ratings, reviews, and comments across movies, shows, and episodes.
     *
     * Idempotent: skips if ratings already exist.
     */
    public function run(): void
    {
        if (Rating::exists()) {
            $this->command->info('Interactions already seeded — skipping.');
            return;
        }

        $user = User::first();
        if (! $user) {
            $this->command->warn('No users found — skipping interaction seeder.');
            return;
        }

        $movies = Movie::take(10)->get();
        $shows = Show::take(5)->get();
        $episodes = Episode::take(10)->get();

        $reviewBodies = [
            'Absolutely loved this! The storytelling is top-notch and keeps you hooked from start to finish.',
            'A decent watch, but the pacing feels off in the middle. It picks up towards the end.',
            'Not what I expected from the trailer. Still enjoyable, but could have been better.',
            'One of the best I have seen this year. Highly recommended for anyone looking for quality content.',
            'The performances are outstanding. Every actor brings their A-game.',
            'Visually stunning but lacks depth in character development.',
            'A masterpiece of modern storytelling. Will definitely watch again.',
            'Good concept but poor execution. The potential was there but not fully realized.',
            'Entertaining and fun. Perfect for a weekend binge.',
            'The soundtrack alone makes this worth watching. Every scene is elevated by the music.',
        ];

        $commentBodies = [
            'This show had me from the first episode. The plot twists were unexpected and thrilling!',
            'Great performances, but the middle episodes dragged a bit.',
            'I finished the whole season in one night. Addictive and well-written!',
            'The trailer made it look action-packed, but the actual movie was kind of slow.',
            'Absolutely beautiful cinematography. Every frame looked like a painting.',
            'Episode 5 completely changed the game. That twist was insane!',
            'I need the next season right now! The finale left me with so many questions.',
            'Some scenes felt unnecessary and stretched out. Could have been tighter.',
            'The acting was top-notch, especially the lead. Brought the character to life.',
            'Loved the buildup, but the ending did not land for me. Felt rushed.',
            'Exactly the kind of show I wanted. Gripping, emotional, and well-produced.',
            'The soundtrack is amazing! Been replaying the background score ever since.',
        ];

        $reviewTitles = [
            'Unexpected Gem', 'Worth the Hype', 'Solid Entertainment', 'Must Watch',
            'Hidden Treasure', 'Mixed Feelings', 'Brilliant Work', 'Could Be Better',
            'Pure Fun', 'Soundtrack Heaven',
        ];

        // Ratings: one per movie and show
        foreach ($movies as $movie) {
            Rating::create([
                'user_id' => $user->id,
                'ratable_type' => Movie::class,
                'ratable_id' => $movie->id,
                'stars' => rand(3, 5),
            ]);
        }

        foreach ($shows as $show) {
            Rating::create([
                'user_id' => $user->id,
                'ratable_type' => Show::class,
                'ratable_id' => $show->id,
                'stars' => rand(3, 5),
            ]);
        }

        foreach ($episodes->take(5) as $episode) {
            Rating::create([
                'user_id' => $user->id,
                'ratable_type' => Episode::class,
                'ratable_id' => $episode->id,
                'stars' => rand(2, 5),
            ]);
        }

        // Reviews: one per movie, a few for shows
        foreach ($movies->take(8) as $i => $movie) {
            Review::create([
                'user_id' => $user->id,
                'reviewable_type' => Movie::class,
                'reviewable_id' => $movie->id,
                'title' => $reviewTitles[$i],
                'body' => $reviewBodies[$i],
                'stars' => rand(3, 5),
                'is_published' => $i < 6, // 6 published, 2 unpublished
            ]);
        }

        foreach ($shows->take(3) as $i => $show) {
            Review::create([
                'user_id' => $user->id,
                'reviewable_type' => Show::class,
                'reviewable_id' => $show->id,
                'title' => $reviewTitles[$i + 8] ?? 'Great Show',
                'body' => $reviewBodies[$i + 8] ?? $reviewBodies[0],
                'stars' => rand(3, 5),
                'is_published' => true,
            ]);
        }

        // Comments: spread across movies and episodes
        foreach ($movies->take(6) as $i => $movie) {
            Comment::create([
                'user_id' => $user->id,
                'commentable_type' => Movie::class,
                'commentable_id' => $movie->id,
                'body' => $commentBodies[$i],
                'is_approved' => $i < 4, // 4 approved, 2 pending
            ]);
        }

        foreach ($episodes->take(6) as $i => $episode) {
            Comment::create([
                'user_id' => $user->id,
                'commentable_type' => Episode::class,
                'commentable_id' => $episode->id,
                'body' => $commentBodies[$i + 6],
                'is_approved' => $i < 4,
            ]);
        }

        $this->command->info('Seeded 20 ratings, 11 reviews, 12 comments.');
    }
}
