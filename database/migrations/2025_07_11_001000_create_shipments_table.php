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
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('carrier');
            $table->string('tracking_number');
            $table->string('status')->default('pending');
            $table->string('current_location')->nullable();
            $table->timestamp('estimated_delivery_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['carrier', 'tracking_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
