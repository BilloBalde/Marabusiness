<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Separates money a buyer says they have paid from money the shop has actually received.
 *
 * Until now a buyer choosing "cash on delivery" marked their own order paid the moment
 * they clicked, before the courier had touched a single note. Nothing in the schema
 * could tell a declaration from a receipt.
 *
 * confirmed_at is that distinction. A payment only counts towards an order's balance
 * once it is set. Payments recorded by staff (admin screens, point of sale) are
 * confirmed as they are created — whoever types them in has the money in hand; only a
 * buyer's own declaration waits for the vendor.
 *
 * payment_status is deliberately left alone: it already carries three values with
 * unclear provenance, and overloading it with a fourth meaning is what makes fields
 * like it untrustworthy in the first place.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paiements', function (Blueprint $table) {
            $table->timestamp('confirmed_at')->nullable()->after('payment_status');
            $table->foreignId('confirmed_by')->nullable()->after('confirmed_at')
                ->constrained('users')->nullOnDelete();
        });

        // Everything already recorded was counted as received under the old rule, and
        // several of those orders are settled and delivered. Marking them confirmed
        // keeps every existing balance exactly as it is.
        DB::table('paiements')->update(['confirmed_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('paiements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('confirmed_by');
            $table->dropColumn('confirmed_at');
        });
    }
};
