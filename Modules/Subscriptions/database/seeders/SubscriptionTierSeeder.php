<?php

namespace Modules\Subscriptions\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Subscriptions\app\Models\SubscriptionTier;

/**
 * Seeds seven starter tiers covering every billing cadence Jambo
 * supports (daily / weekly / monthly / yearly) across three access
 * levels. Re-runnable — uses firstOrCreate keyed on slug.
 */
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
                'billing_period' => SubscriptionTier::PERIOD_MONTHLY,
                'access_level' => SubscriptionTier::ACCESS_FREE,
                'max_concurrent_streams' => null, // free-tier content has no stream cap
                'features' => [
                    'Limited catalog',
                    'Ads supported',
                    'SD quality',
                ],
                'is_active' => true,
                'sort_order' => 10,
            ],

            // Daily pass — quick try-before-you-buy option
            [
                'slug' => 'day-pass',
                'name' => 'Day Pass',
                'description' => '24-hour full access. No subscription, no renewal.',
                'price' => 49.00,
                'currency' => 'KES',
                'billing_period' => SubscriptionTier::PERIOD_DAILY,
                'access_level' => SubscriptionTier::ACCESS_BASIC,
                'max_concurrent_streams' => 1,
                'features' => [
                    'Full catalog for 24 hours',
                    'HD quality',
                    '1 device',
                ],
                'is_active' => true,
                'sort_order' => 15,
            ],

            // Weekly
            [
                'slug' => 'weekly-basic',
                'name' => 'Weekly Basic',
                'description' => 'Full catalog, seven days at a time.',
                'price' => 199.00,
                'currency' => 'KES',
                'billing_period' => SubscriptionTier::PERIOD_WEEKLY,
                'access_level' => SubscriptionTier::ACCESS_BASIC,
                'max_concurrent_streams' => 2,
                'features' => [
                    'Full catalog',
                    'Ad-free',
                    'HD quality',
                    '2 devices',
                ],
                'is_active' => true,
                'sort_order' => 18,
            ],

            // Monthly tiers
            [
                'slug' => 'basic',
                'name' => 'Basic Monthly',
                'description' => 'Full Jambo catalog without ads.',
                'price' => 499.00,
                'currency' => 'KES',
                'billing_period' => SubscriptionTier::PERIOD_MONTHLY,
                'access_level' => SubscriptionTier::ACCESS_BASIC,
                'max_concurrent_streams' => 2,
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
                'name' => 'Premium Monthly',
                'description' => 'The complete Jambo experience in 4K.',
                'price' => 999.00,
                'currency' => 'KES',
                'billing_period' => SubscriptionTier::PERIOD_MONTHLY,
                'access_level' => SubscriptionTier::ACCESS_PREMIUM,
                'max_concurrent_streams' => 4,
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

            // Yearly tiers — save vs monthly
            [
                'slug' => 'basic-yearly',
                'name' => 'Basic Yearly',
                'description' => 'Save two months when you pay yearly.',
                'price' => 4990.00,
                'currency' => 'KES',
                'billing_period' => SubscriptionTier::PERIOD_YEARLY,
                'access_level' => SubscriptionTier::ACCESS_BASIC,
                'max_concurrent_streams' => 2,
                'features' => [
                    'Full catalog',
                    'Ad-free',
                    'HD quality',
                    '2 devices',
                    '2 months free vs monthly plan',
                ],
                'is_active' => true,
                'sort_order' => 22,
            ],
            [
                'slug' => 'premium-yearly',
                'name' => 'Premium Yearly',
                'description' => 'Save two months on the premium tier.',
                'price' => 9990.00,
                'currency' => 'KES',
                'billing_period' => SubscriptionTier::PERIOD_YEARLY,
                'access_level' => SubscriptionTier::ACCESS_PREMIUM,
                'max_concurrent_streams' => 4,
                'features' => [
                    'Everything in Premium Monthly',
                    '2 months free vs monthly plan',
                ],
                'is_active' => true,
                'sort_order' => 32,
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
