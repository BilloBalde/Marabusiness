<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A flat cross-border freight cost, in the vendor's own currency, added on top of
     * the shared destination zone price (or its fallback) for every order — unlike
     * that shared price, this one belongs to the vendor alone, so it is never averaged
     * or overwritten by another vendor's own shipping settings.
     *
     * Deliberately NOT conditioned on comparing the vendor's country against the
     * buyer's: that free-text comparison is exactly what caused the "Zone
     * International" bug in the legacy carrier calculator (see
     * app/Services/ShippingCalculator.php's determineShippingZone()) — inconsistent
     * values such as "USA", "GN", "Guinée" never reliably matched. A vendor based
     * abroad sets this once to their real freight cost; a Guinea-based vendor leaves
     * it at zero. No country string is compared anywhere in this new field's logic.
     */
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->decimal('international_shipping_surcharge', 15, 2)
                ->default(0)
                ->after('default_shipping_amount');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn('international_shipping_surcharge');
        });
    }
};
