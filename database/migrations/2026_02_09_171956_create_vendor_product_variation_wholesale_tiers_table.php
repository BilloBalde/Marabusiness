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
        Schema::create('vendor_product_variation_wholesale_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_product_variation_id')->constrained()->cascadeOnDelete();

            $table->unsignedInteger('min_qty');
            $table->unsignedInteger('max_qty')->nullable();
            $table->decimal('price', 12, 2);

            $table->timestamps();

            $table->index(['vendor_product_variation_id', 'min_qty']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_product_variation_wholesale_tiers');
    }
};
