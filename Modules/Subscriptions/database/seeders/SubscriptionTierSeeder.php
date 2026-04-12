<?php

namespace Modules\Subscriptions\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Subscriptions\app\Models\SubscriptionTier;

class SubscriptionTierSeeder extends Seeder
{
    public function run(): void
    {
        $tiers = [
            [
                'slug' => 'free',
                'name' => 'Free',
                'description' => 'Get a taste of Jambo at no cost.',
                'price' => 0.00,
                'currency' => 'KES',
                'billing_period' => 'monthly',
                'access_level' => SubscriptionTier::ACCESS_FREE,
                'features' => [
                    'Limited catalog',
                    'Ads supported',
                    'SD quality',
                ],
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'slug' => 'basic',
                'name' => 'Basic',
                'description' => 'Full Jambo catalog without ads.',
                'price' => 499.00,
                'currency' => 'KES',
                'billing_period' => 'monthly',
                'access_level' => SubscriptionTier::ACCESS_BASIC,
                'features' => [
                    'Full catalog',
                    'Ad-free',
                    'HD quality',
                    '2 devices',
                ],
                'is_active' => true,
                'sort_order' => 20,
            ],
            [
                'slug' => 'premium',
                'name' => 'Premium',
                'description' => 'The complete Jambo experience in 4K.',
                'price' => 999.00,
                'currency' => 'KES',
                'billing_period' => 'monthly',
                'access_level' => SubscriptionTier::ACCESS_PREMIUM,
                'features' => [
                    'Everything in Basic',
                    '4K Ultra HD',
                    'Dolby Atmos',
                    '4 devices',
                    'Downloads',
                ],
                'is_active' => true,
                'sort_order' => 30,
            ],
        ];

        foreach ($tiers as $tier) {
            SubscriptionTier::firstOrCreate(
                ['slug' => $tier['slug']],
                $tier
            );
        }
    }
}
