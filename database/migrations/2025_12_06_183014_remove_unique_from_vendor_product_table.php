<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step A — Create new table without UNIQUE constraint
        Schema::create('vendor_product_new', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');

            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->integer('discount_percent')->nullable();
            $table->integer('stock')->default(0);
            $table->boolean('is_active')->default(true);

            $table->json('variation_json')->nullable();

            $table->date('sale_start')->nullable();
            $table->date('sale_end')->nullable();

            $table->timestamps();
        });

        // Step B — Copy data from old table
        DB::statement("
            INSERT INTO vendor_product_new 
            (id, vendor_id, product_id, price, sale_price, purchase_price, discount_percent, stock, is_active, variation_json, sale_start, sale_end, created_at, updated_at)
            SELECT id, vendor_id, product_id, price, sale_price, purchase_price, discount_percent, stock, is_active, variation_json, sale_start, sale_end, created_at, updated_at
            FROM vendor_product
        ");

        // Step C — Drop old table
        Schema::drop('vendor_product');

        // Step D — Rename new table
        Schema::rename('vendor_product_new', 'vendor_product');
    }

    public function down(): void
    {
        // Not necessary for SQLite — but you can rebuild with the unique if needed
    }
};
