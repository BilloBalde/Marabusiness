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
        Schema::table('vendor_product', function (Blueprint $table) {
            $table->decimal('sale_price', 10, 2)->nullable()->after('price');
            $table->unsignedInteger('discount_percent')->nullable()->after('sale_price');
            $table->timestamp('sale_start')->nullable();
            $table->timestamp('sale_end')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_product', function (Blueprint $table) {
            $table->dropColumn([
                'sale_price', 'discount_percent', 'sale_start', 'sale_end'
            ]);
        });
    }
};
