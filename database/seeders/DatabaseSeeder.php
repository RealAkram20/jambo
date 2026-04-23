<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Content\database\seeders\ContentDatabaseSeeder;
use Modules\Notifications\database\seeders\NotificationsDatabaseSeeder;
use Modules\Subscriptions\database\seeders\SubscriptionsDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        $this->call(AuthTableSeeder::class);
        $this->call(ContentDatabaseSeeder::class);
        $this->call(SubscriptionsDatabaseSeeder::class);
        $this->call(NotificationsDatabaseSeeder::class);
    }
}
