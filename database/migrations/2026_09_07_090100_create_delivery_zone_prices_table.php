<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replaces vendor_shipping_rates (0 rows, never adopted by a real vendor — verified
     * before writing this migration). That table priced a locality per vendor, privately.
     * This one prices a locality once, shared by every vendor: `locality_id` is unique,
     * not (vendor_id, locality_id).
     *
     * The price is stored in USD rather than a vendor's local currency — the only way a
     * single shared number stays meaningful across vendors billing in GNF, CNY or USD.
     * Each vendor converts it through their own currency.rate_to_usd at quote time,
     * exactly as the rest of the shipping calculation already does.
     */
    public function up(): void
    {
        Schema::create('delivery_zone_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('locality_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('price_usd', 12, 4);
            $table->unsignedSmallInteger('delivery_days')->default(2);
            $table->boolean('is_active')->default(true);
            // Whoever priced or last touched this zone — admin or any vendor. Kept for
            // audit only; it grants no special rights over the row.
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('delivery_zone_price_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_zone_price_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('min_qty');
            // NULL means "and above" — same convention as vendor_product_wholesale and
            // the tiers this replaces.
            $table->unsignedInteger('max_qty')->nullable();
            $table->decimal('price_usd', 12, 4);
            $table->timestamps();

            $table->index(['delivery_zone_price_id', 'min_qty']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_zone_price_tiers');
        Schema::dropIfExists('delivery_zone_prices');
    }
};
