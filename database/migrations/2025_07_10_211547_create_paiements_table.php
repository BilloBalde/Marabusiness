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
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('payment_method')->nullable();
            $table->enum('payment_status', ['pending', 'partial', 'paid'])->default('pending'); // pending, completed, failed
            $table->string('currency')->default('gnf'); // Default currency
            $table->decimal('amount', 15, 2)->default(0.00); // Amount in the smallest unit of the currency
            $table->string('image')->nullable(); // Image of the payment receipt
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};
