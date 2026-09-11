<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The Orange Money fee row was never charged.
 *
 * FinanceCalculator::calculateGatewayFee() looks the fee up with
 * where('gateway_name', $paymentMethod), and orders store 'om' — the value in
 * Paiement::OFFLINE_METHODS, the API validation rules and the mobile app alike.
 * The fee row said 'orange_money', so the lookup found nothing and returned 0
 * for all 38 Orange Money orders.
 *
 * The admin form had already been corrected to offer 'om'; only the stored row
 * was left behind, which also meant opening that row in Filament showed a select
 * with no matching option.
 *
 * Renaming makes the configured 1.5% (minimum 1500 GNF) start applying. That is
 * the intent — it was always meant to be charged.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Guarded rather than a blind update: if an 'om' row already exists,
        // renaming would collide with the unique constraint the resource
        // enforces, and silently merging two fee configurations is not
        // something a migration should decide.
        $hasOm = DB::table('payment_gateway_fees')->where('gateway_name', 'om')->exists();

        if ($hasOm) {
            return;
        }

        DB::table('payment_gateway_fees')
            ->where('gateway_name', 'orange_money')
            ->update(['gateway_name' => 'om']);
    }

    public function down(): void
    {
        $hasOrangeMoney = DB::table('payment_gateway_fees')
            ->where('gateway_name', 'orange_money')
            ->exists();

        if ($hasOrangeMoney) {
            return;
        }

        DB::table('payment_gateway_fees')
            ->where('gateway_name', 'om')
            ->update(['gateway_name' => 'orange_money']);
    }
};
