<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateway_fees', function (Blueprint $table) {
            $table->id();
            $table->string('gateway_name'); // stripe, orange_money, etc.
            $table->enum('fee_type', ['percentage', 'fixed', 'percentage_plus_fixed'])->default('percentage');
            $table->decimal('percentage_fee', 5, 2)->default(0);
            $table->decimal('fixed_fee', 10, 2)->default(0);
            $table->string('currency')->default('USD');
            $table->decimal('minimum_fee', 10, 2)->nullable();
            $table->decimal('maximum_fee', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->unique(['gateway_name', 'currency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_fees');
    }
};