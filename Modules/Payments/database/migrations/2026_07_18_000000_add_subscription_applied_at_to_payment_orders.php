<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotency marker for subscription activation.
 *
 * ActivateSubscriptionFromPayment used to detect "already processed" by
 * looking for a UserSubscription whose payment_order_id equalled the order.
 * But a same-tier renewal overwrites that column with the newest order id,
 * erasing the earlier order's marker — so a replayed payment.completed for
 * that earlier order sailed past the check and granted a second free
 * extension. The claim now lives on the order itself, where nothing rewrites
 * it, and the listener sets it with a conditional single-row update so the
 * check is atomic under concurrent dispatch.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_orders', function (Blueprint $table) {
            $table->timestamp('subscription_applied_at')->nullable()->after('status');
        });

        // Backfill: any order already linked to a subscription has clearly
        // been applied. Mark it so an early re-fire of its event can't
        // re-run activation against the new marker.
        DB::table('payment_orders')
            ->whereExists(function ($q) {
                $q->selectRaw('1')
                    ->from('user_subscriptions')
                    ->whereColumn('user_subscriptions.payment_order_id', 'payment_orders.id');
            })
            ->update(['subscription_applied_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('payment_orders', function (Blueprint $table) {
            $table->dropColumn('subscription_applied_at');
        });
    }
};
