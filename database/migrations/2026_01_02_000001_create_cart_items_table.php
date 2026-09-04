<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('vendor_product_id');
            $table->unsignedBigInteger('variation_id')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->json('selected_variations')->nullable();
            $table->string('custom_note')->nullable();
            $table->string('cart_key');
            $table->timestamps();

            $table->unique(['user_id', 'cart_key']);
            $table->index(['user_id', 'vendor_product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
