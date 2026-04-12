<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payment orders — every user-initiated payment creates one row here,
 * regardless of gateway. A single source of truth for reconciliation.
 *
 * `payable_*` is polymorphic so the same table can track subscription
 * payments, one-off rental payments, merchandise purchases, etc. The
 * Subscriptions module will be the first to use it.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('payment_orders', function (Blueprint $t) {
            $t->id();

            $t->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Polymorphic target (subscription, rental, merch, ...).
            $t->string('payable_type')->nullable();
            $t->unsignedBigInteger('payable_id')->nullable();
            $t->index(['payable_type', 'payable_id']);

            // Our own identifier. Must be unique and gateway-safe.
            $t->string('merchant_reference', 64)->unique();

            // Gateway-assigned ids.
            $t->string('order_tracking_id')->nullable();
            $t->string('confirmation_code')->nullable();

            // Money.
            $t->decimal('amount', 10, 2);
            $t->string('currency', 8)->default('KES');

            // Lifecycle.
            $t->string('status')->default('pending');            // pending|completed|failed|cancelled
            $t->string('payment_gateway')->default('pesapal');    // pesapal|stripe|...
            $t->string('payment_method')->nullable();             // card|mpesa|airtel|...

            // Freeform extras: return URL, signup context, plan slug, etc.
            $t->json('metadata')->nullable();

            // Full last-seen gateway response for debugging.
            $t->json('raw_response')->nullable();

            $t->timestamps();

            $t->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_orders');
    }
};
