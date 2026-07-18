<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->cascadeOnDelete();

            // One attribution per referred user; last-touch re-points the
            // row while it is still pending, qualified rows are immutable.
            $table->foreignId('referred_user_id')->unique()->constrained('users')->cascadeOnDelete();

            $table->string('code_used', 50);
            $table->string('source', 20); // cookie | code
            $table->string('status', 20)->default('pending'); // pending | qualified
            $table->foreignId('qualified_payment_order_id')->nullable()
                ->constrained('payment_orders')->nullOnDelete();

            // Snapshot of the terms honoured at qualification time.
            $table->decimal('discount_percent', 5, 2)->nullable();
            $table->decimal('reward_percent', 5, 2)->nullable();
            $table->decimal('original_amount', 10, 2)->nullable();
            $table->decimal('discount_amount', 10, 2)->nullable();
            $table->decimal('paid_amount', 10, 2)->nullable();
            $table->decimal('reward_amount', 10, 2)->nullable();
            $table->char('currency', 3)->nullable();

            $table->timestamp('qualified_at')->nullable();
            $table->timestamps();

            $table->index(['referrer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
