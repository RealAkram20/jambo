<?php

namespace Modules\Subscriptions\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Subscriptions\app\Models\SubscriptionTier;

/**
 * Seeds seven starter tiers covering every billing cadence Jambo
 * supports (daily / weekly / monthly / yearly) across three access
 * levels. Prices in UGX, appropriate for the Ugandan market.
 *
 * Uses `updateOrCreate` keyed on slug so re-running the seeder
 * refreshes prices / features on existing rows rather than skipping
 * them. Admins who've edited a tier through the admin UI can still
 * re-run this safely — their changes will be overwritten to the
 * canonical values, which is the correct seeder semantics.
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
                'price' => 0,
                'currency' => 'UGX',
                'billing_period' => SubscriptionTier::PERIOD_MONTHLY,
                'access_level' => SubscriptionTier::ACCESS_FREE,
                'max_concurrent_streams' => null,
                'features' => [
                    'Limited catalog',
                    'Ads supported',
                    'SD quality',
                ],
                'is_active' => true,
                'sort_order' => 10,
            ],

            // Daily pass — quick try-before-you-buy. ~$0.40 USD.
            [
                'slug' => 'day-pass',
                'name' => 'Day Pass',
                'description' => '24-hour full access. No subscription, no renewal.',
                'price' => 1500,
                'currency' => 'UGX',
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

            // Weekly basic — ~$1.60 USD.
            [
                'slug' => 'weekly-basic',
                'name' => 'Weekly Basic',
                'description' => 'Full catalog, seven days at a time.',
                'price' => 6000,
                'currency' => 'UGX',
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

            // Basic monthly — ~$4 USD. Anchor price for the platform.
            [
                'slug' => 'basic',
                'name' => 'Basic Monthly',
                'description' => 'Full Jambo catalog without ads.',
                'price' => 15000,
                'currency' => 'UGX',
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

            // Premium monthly — ~$8 USD. 4K + more streams.
            [
                'slug' => 'premium',
                'name' => 'Premium Monthly',
                'description' => 'The complete Jambo experience in 4K.',
                'price' => 30000,
                'currency' => 'UGX',
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

            // Basic yearly — two months free vs monthly (15000 × 10 = 150000).
            [
                'slug' => 'basic-yearly',
                'name' => 'Basic Yearly',
                'description' => 'Save two months when you pay yearly.',
                'price' => 150000,
                'currency' => 'UGX',
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

            // Premium yearly — same saving math.
            [
                'slug' => 'premium-yearly',
                'name' => 'Premium Yearly',
                'description' => 'Save two months on the premium tier.',
                'price' => 300000,
                'currency' => 'UGX',
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
            SubscriptionTier::updateOrCreate(
                ['slug' => $tier['slug']],
                $tier
            );
        }
    }
}
