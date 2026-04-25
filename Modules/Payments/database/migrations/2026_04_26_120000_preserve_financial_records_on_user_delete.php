<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Stop deleting payment_orders + user_subscriptions when their owning
 * user is deleted. Financial history is audit-grade and we'd lose
 * earnings totals, tax records, and reconciliation trails the moment
 * a customer's account vanished.
 *
 *   - payment_orders.user_id   becomes NULL on delete (was CASCADE)
 *   - user_subscriptions.user_id becomes NULL on delete (was CASCADE)
 *
 * Adds a denormalised customer snapshot to payment_orders so an
 * orphaned row still tells us who paid (email + name + username at
 * the time of order). Backfilled here from the current users row;
 * any future deletes will have the snapshot refreshed on the
 * User::deleting model hook just before the row is removed.
 */
return new class extends Migration {
    public function up(): void
    {
        // payment_orders ----------------------------------------------------
        if (Schema::hasTable('payment_orders')) {
            // Snapshot columns first — backfill needs them.
            if (!Schema::hasColumn('payment_orders', 'customer_email')) {
                Schema::table('payment_orders', function (Blueprint $t) {
                    $t->string('customer_email')->nullable()->after('user_id');
                    $t->string('customer_name')->nullable()->after('customer_email');
                    $t->string('customer_username')->nullable()->after('customer_name');
                });
            }

            // Backfill from the current users row so existing orders carry
            // identity even if we never see those users again.
            DB::statement("
                UPDATE payment_orders po
                INNER JOIN users u ON po.user_id = u.id
                SET po.customer_email = COALESCE(po.customer_email, u.email),
                    po.customer_name  = COALESCE(po.customer_name, TRIM(CONCAT(IFNULL(u.first_name, ''), ' ', IFNULL(u.last_name, '')))),
                    po.customer_username = COALESCE(po.customer_username, u.username)
            ");

            // Swap the FK from CASCADE to SET NULL.
            $this->dropFkIfExists('payment_orders', 'payment_orders_user_id_foreign');
            DB::statement("ALTER TABLE payment_orders MODIFY user_id BIGINT UNSIGNED NULL");
            DB::statement("
                ALTER TABLE payment_orders
                ADD CONSTRAINT payment_orders_user_id_foreign
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            ");
        }

        // user_subscriptions ------------------------------------------------
        if (Schema::hasTable('user_subscriptions')) {
            $this->dropFkIfExists('user_subscriptions', 'user_subscriptions_user_id_foreign');
            DB::statement("ALTER TABLE user_subscriptions MODIFY user_id BIGINT UNSIGNED NULL");
            DB::statement("
                ALTER TABLE user_subscriptions
                ADD CONSTRAINT user_subscriptions_user_id_foreign
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            ");
        }
    }

    public function down(): void
    {
        // Putting CASCADE back is destructive — refuse to roll back. If
        // the operator really wants the old behaviour they can drop and
        // re-add the FK by hand. The customer_* columns are safe to leave
        // in place since they're nullable.
        throw new \RuntimeException(
            'Refusing to roll back: restoring CASCADE delete on financial tables would let user deletes wipe earnings history again.'
        );
    }

    private function dropFkIfExists(string $table, string $constraint): void
    {
        $exists = DB::selectOne(
            "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = ?
             AND CONSTRAINT_NAME = ?
             AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [$table, $constraint]
        );
        if ($exists) {
            DB::statement("ALTER TABLE `$table` DROP FOREIGN KEY `$constraint`");
        }
    }
};
