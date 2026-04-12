<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Subscription tiers — the catalog of plans a user can subscribe to.
 * Seeded with Free/Basic/Premium by SubscriptionTierSeeder but fully
 * editable at runtime (price changes, new tiers, etc.).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('subscription_tiers', function (Blueprint $t) {
            $t->id();

            $t->string('name');
            $t->string('slug')->unique();
            $t->text('description')->nullable();

            // Money.
            $t->decimal('price', 10, 2);
            $t->string('currency', 8)->default('KES');

            // 'monthly' | 'yearly'
            $t->string('billing_period');

            // 0=free, 1=basic, 2=premium, 3=ultra. Higher = more access.
            $t->unsignedTinyInteger('access_level');

            // Freeform human-readable feature list.
            $t->json('features')->nullable();

            $t->boolean('is_active')->default(true);
            $t->unsignedInteger('sort_order')->default(0);

            $t->timestamps();

            $t->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_tiers');
    }
};
