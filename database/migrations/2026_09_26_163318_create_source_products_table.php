<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('source_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sourcing_id')->constrained('sourcings')->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->json('name');
            $table->json('short_description')->nullable();
            $table->string('price_range')->nullable();
            $table->unsignedInteger('quantity_minimum')->nullable();
            $table->string('city')->nullable();
            $table->unsignedInteger('interest_count')->default(0);
            $table->json('images')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['sourcing_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('source_products');
    }
};
