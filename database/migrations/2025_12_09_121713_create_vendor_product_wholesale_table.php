<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vendor_product_wholesale', function (Blueprint $table) {
            $table->id();

            $table->foreignId('vendor_product_id')
                ->constrained('vendor_product')
                ->onDelete('cascade');

            $table->unsignedInteger('min_qty');          // e.g. 10
            $table->unsignedInteger('max_qty')->nullable(); // null = ∞
            $table->decimal('price', 12, 2);             // vendor currency

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_product_wholesale');
    }
};
