<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->string('currency')->default('USD');
            $table->decimal('commission_amount', 15, 2)->default(0);
            $table->decimal('gateway_fees', 15, 2)->default(0);
            $table->decimal('wire_fees', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2);
            $table->enum('payout_method', ['bank_wire', 'orange_money', 'stripe_transfer', 'other']);
            $table->json('payout_details')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->string('reference_number')->unique();
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['vendor_id', 'status']);
            $table->index('reference_number');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_payouts');
    }
};