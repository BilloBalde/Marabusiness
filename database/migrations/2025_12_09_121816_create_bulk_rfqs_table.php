<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bulk_rfqs', function (Blueprint $table) {
            $table->id();

            // Buyer
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            // Vendor
            $table->foreignId('vendor_id')
                ->constrained()
                ->onDelete('cascade');

            // Product references
            $table->foreignId('product_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('vendor_product_id')
                ->nullable()
                ->constrained('vendor_product')
                ->nullOnDelete();

            // Status
            $table->string('status')->default('pending');
            // pending, quoted, accepted, rejected, cancelled, expired

            // Basic RFQ info
            $table->unsignedInteger('quantity')->nullable();
            $table->decimal('target_price', 12, 2)->nullable();
            $table->string('currency', 10)->nullable();       // USD, EUR, etc.

            // Shipping
            $table->string('shipping_country')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_port')->nullable();

            // Customization
            $table->boolean('needs_customization')->default(false);
            $table->text('customization_notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_rfqs');
    }
};
