<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-vendor delivery pricing, one row per locality the vendor serves. The amount
     * is entered when the vendor adds the locality, and is expressed in the vendor's
     * own currency exactly like shipping_zones.base_price already is.
     *
     * Quantity brackets are optional and live in the companion table. They mirror
     * vendor_product_wholesale (min_qty / nullable max_qty) so the same resolution
     * rule and the same admin ergonomics apply.
     */
    public function up(): void
    {
        Schema::create('vendor_shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('locality_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->unsignedSmallInteger('delivery_days')->default(2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // A vendor prices a given locality once.
            $table->unique(['vendor_id', 'locality_id']);
        });

        Schema::create('vendor_shipping_rate_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_shipping_rate_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('min_qty');
            // NULL means "and above", same convention as vendor_product_wholesale.
            $table->unsignedInteger('max_qty')->nullable();
            $table->decimal('amount', 15, 2);
            $table->timestamps();

            $table->index(['vendor_shipping_rate_id', 'min_qty']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_shipping_rate_tiers');
        Schema::dropIfExists('vendor_shipping_rates');
    }
};
