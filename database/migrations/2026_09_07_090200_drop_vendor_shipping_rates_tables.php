<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Superseded by delivery_zone_prices / delivery_zone_price_tiers: pricing a locality
     * is now shared across vendors, not private to one. These two tables held 0 rows —
     * verified before dropping — so nothing is lost.
     */
    public function up(): void
    {
        Schema::dropIfExists('vendor_shipping_rate_tiers');
        Schema::dropIfExists('vendor_shipping_rates');
    }

    public function down(): void
    {
        Schema::create('vendor_shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('locality_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->unsignedSmallInteger('delivery_days')->default(2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['vendor_id', 'locality_id']);
        });

        Schema::create('vendor_shipping_rate_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_shipping_rate_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('min_qty');
            $table->unsignedInteger('max_qty')->nullable();
            $table->decimal('amount', 15, 2);
            $table->timestamps();
            $table->index(['vendor_shipping_rate_id', 'min_qty']);
        });
    }
};
