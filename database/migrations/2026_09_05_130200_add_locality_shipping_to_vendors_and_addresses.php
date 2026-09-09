<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Switches shipping calculation on per vendor. 'carrier' is the default so every
     * existing vendor keeps the current zone + carrier pricing untouched; only a
     * vendor explicitly moved to 'locality' uses the new calculator.
     *
     * default_shipping_amount is the "default value" of the specification: what a
     * vendor charges when the buyer's locality is not one it has priced. Without it a
     * multi-vendor cart would silently ship for free from any shop that does not
     * cover the destination.
     */
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('shipping_mode')->default('carrier')->after('carrier_rates');
            $table->decimal('default_shipping_amount', 15, 2)->nullable()->after('shipping_mode');
        });

        Schema::table('addresses', function (Blueprint $table) {
            $table->foreignId('locality_id')->nullable()->after('zip_code')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('locality_id');
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['shipping_mode', 'default_shipping_amount']);
        });
    }
};
