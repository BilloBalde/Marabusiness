<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Makes a payment reference unique at the database level.
 *
 * Order::generateTransactionNumber() searched for references prefixed "TRANS" while
 * returning ones prefixed "INV", so it never found what it had produced and handed the
 * same reference to every payment of a given month. It went unnoticed for over a year —
 * 88 payments sharing 24 references, one used 19 times — precisely because nothing
 * stopped a duplicate from being written. This index is what would have caught it on
 * the second payment.
 *
 * The column stays nullable, so several NULLs remain possible (standard SQL); what the
 * index rules out is two payments carrying the same actual reference.
 */
return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('paiements')
            ->select('transaction_id')
            ->whereNotNull('transaction_id')
            ->groupBy('transaction_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        if ($duplicates > 0) {
            // Better to stop than to fail halfway through: the historical references
            // have to be regularised before the index can hold.
            throw new RuntimeException(
                "Impossible de poser l'index unique : {$duplicates} référence(s) de paiement sont encore en double."
            );
        }

        Schema::table('paiements', function (Blueprint $table) {
            $table->unique('transaction_id', 'paiements_transaction_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('paiements', function (Blueprint $table) {
            $table->dropUnique('paiements_transaction_id_unique');
        });
    }
};
