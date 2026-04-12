<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * User subscriptions — the join between a user and a subscription tier
 * over a time window. A user can have many historical rows but typically
 * only one with status = 'active' at any given time.
 *
 * `payment_order_id` is intentionally NOT a foreign key. Payments is a
 * separate module and we want the two to remain independently-migratable
 * in any order. A plain index is enough for lookups.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_subscriptions', function (Blueprint $t) {
            $t->id();

            $t->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $t->foreignId('subscription_tier_id')
                ->constrained('subscription_tiers')
                ->restrictOnDelete();

            // Loose link to Payments module — no FK on purpose.
            $t->unsignedBigInteger('payment_order_id')->nullable();
            $t->index('payment_order_id');

            // Nullable because MariaDB strict mode rejects NOT NULL
            // timestamps without a default, and a subscription that has
            // not yet started/ended is a legitimate state (e.g. pending
            // payment).
            $t->timestamp('starts_at')->nullable();
            $t->timestamp('ends_at')->nullable();

            // active|expired|cancelled|pending
            $t->string('status');

            $t->boolean('auto_renew')->default(false);
            $t->timestamp('cancelled_at')->nullable();

            $t->timestamps();

            $t->index(['user_id', 'status']);
            $t->index('ends_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_subscriptions');
    }
};
