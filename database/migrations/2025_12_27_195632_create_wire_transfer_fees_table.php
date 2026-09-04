<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wire_transfer_fees', function (Blueprint $table) {
            $table->id();
            $table->string('country')->nullable(); // null for global
            $table->string('currency')->default('USD');
            $table->enum('fee_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('percentage_fee', 5, 2)->default(0);
            $table->decimal('fixed_fee', 10, 2)->default(0);
            $table->decimal('minimum_amount', 10, 2)->nullable();
            $table->decimal('maximum_amount', 10, 2)->nullable();
            $table->integer('processing_days')->default(3);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['country', 'currency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wire_transfer_fees');
    }
};