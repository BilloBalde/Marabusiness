// Create migration: create_shipping_zones_table
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->string('name'); // e.g., "Local City", "National", "International"
            $table->string('country_code')->nullable(); // GN, ML, SN, etc.
            $table->string('region')->nullable(); // Specific region within country
            $table->string('cities')->nullable(); // Comma-separated cities
            $table->integer('radius_km')->nullable(); // Radius from vendor location
            $table->decimal('base_price', 10, 2); // Base shipping price
            $table->decimal('price_per_kg', 10, 2)->default(0); // Price per kg
            $table->decimal('price_per_cbm', 10, 2)->default(0); // Price per CBM
            $table->decimal('price_per_item', 10, 2)->default(0); // Price per item
            $table->integer('min_days')->default(1); // Minimum delivery days
            $table->integer('max_days')->default(7); // Maximum delivery days
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('shipping_carrier_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->string('carrier'); // dhl, ups, fedex, local, etc.
            $table->string('zone_name'); // Links to shipping zone
            $table->decimal('base_rate', 10, 2);
            $table->decimal('rate_per_kg', 10, 2)->default(0);
            $table->decimal('rate_per_cbm', 10, 2)->default(0);
            $table->decimal('rate_per_item', 10, 2)->default(0);
            $table->integer('delivery_days')->default(3);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_carrier_rates');
        Schema::dropIfExists('shipping_zones');
    }
};