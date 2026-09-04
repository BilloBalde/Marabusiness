<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vendor_product_variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_product_id')->constrained('vendor_product')->onDelete('cascade');
            $table->json('attributes'); // e.g., {"color": "Black", "size": "XL"}
            $table->decimal('price', 10, 2)->nullable(); // Override price for this variation
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->integer('stock')->default(0);
            $table->string('sku')->nullable(); // Unique SKU for this variation
            $table->string('image')->nullable(); // Optional image for this variation
            $table->timestamps();
            $table->unique(['vendor_product_id', 'sku']);
        });

        // Add variation summary to vendor_product
        Schema::table('vendor_product', function (Blueprint $table) {
            $table->json('variation_matrix')->nullable()->after('variation_json'); // Stores all possible combos
            $table->boolean('has_variations')->default(false)->after('variation_json');
            $table->dropColumn('variation_json'); // Remove old field or keep as backup
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_product_variations');
        Schema::table('vendor_product', function (Blueprint $table) {
            $table->dropColumn(['variation_matrix', 'has_variations']);
            $table->json('variation_json')->nullable()->after('variation_matrix'); // Restore old field
        });
    }
};
