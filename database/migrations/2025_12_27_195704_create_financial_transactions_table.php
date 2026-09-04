<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('vendor_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('commission_setting_id')->nullable()->constrained('commission_settings')->onDelete('set null');
            $table->enum('transaction_type', ['order', 'commission', 'gateway_fee', 'wire_fee', 'payout', 'refund', 'adjustment']);
            $table->decimal('amount', 15, 2);
            $table->string('currency')->default('USD');
            $table->text('description');
            $table->string('reference_number')->nullable();
            $table->decimal('gateway_fee', 15, 2)->default(0);
            $table->decimal('commission_fee', 15, 2)->default(0);
            $table->decimal('wire_fee', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2);
            $table->enum('status', ['pending', 'processed', 'failed', 'reversed', 'cancelled'])->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            $table->index(['vendor_id', 'status']);
            $table->index(['reference_number', 'transaction_type']);
            $table->index('processed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
    }
};