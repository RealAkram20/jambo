<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where a viewer is, as an ISO-3166-1 alpha-2 code.
 *
 * Added 2026-09-09 for the profile screen Rio designed, whose Country row had
 * nothing behind it — the mockup's "Uganda" was the only place a country had
 * ever existed in this system. He chose to make the field real rather than
 * drop the row.
 *
 * **Two characters, not a name.** A free-text country produces "Uganda",
 * "uganda", "UG" and "Ugnada" for one country, none of which can be counted,
 * filtered or reported on, and every one of which has to be shown back to the
 * viewer exactly as they mistyped it. The display name is derived — see
 * `App\Support\Countries`.
 *
 * Nullable, and it stays nullable. Every existing account has no country and
 * there is no honest way to infer one; the screen shows an em dash until
 * somebody sets it. Deriving it from the phone's dialling code was considered
 * and rejected: it is wrong for anyone on a SIM from a country they do not
 * live in, and it presents a guess as a fact.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->char('country', 2)->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn('country');
        });
    }
};
