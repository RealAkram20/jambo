<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Soft deactivation — keeps the user row + all their data (watch
     * history, reviews, orders) so revenue + analytics stay intact,
     * but blocks login until reactivation. GDPR "right to erasure"
     * is a separate harder workflow and not handled here.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->timestamp('deactivated_at')->nullable()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn('deactivated_at');
        });
    }
};
