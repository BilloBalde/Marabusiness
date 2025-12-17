<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bulk_rfq_offers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bulk_rfq_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('vendor_id')
                ->constrained()
                ->onDelete('cascade');

            // Vendor offer
            $table->unsignedInteger('moq')->nullable();    // minimum qty
            $table->decimal('unit_price', 12, 2);
            $table->string('currency', 10)->nullable();

            // Logistics
            $table->unsignedInteger('lead_time_days')->nullable();
            $table->string('shipping_terms')->nullable();  // FOB, CIF, EXW
            $table->decimal('shipping_cost', 12, 2)->nullable();

            $table->text('vendor_notes')->nullable();

            $table->string('status')->default('pending');
            // pending, accepted_by_buyer, declined_by_buyer

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_rfq_offers');
    }
};
