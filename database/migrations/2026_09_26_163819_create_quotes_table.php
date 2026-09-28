<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * study_destination_id has no foreign key target yet — the Study Abroad
 * module (and its migration) comes in a later step, per the phased plan. The
 * column is here now so this table's shape doesn't need revisiting later,
 * but it stays a plain nullable unsignedBigInteger until that table exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['sourcing', 'study_abroad']);
            $table->foreignId('source_product_id')->nullable()->constrained('source_products')->nullOnDelete();
            $table->unsignedBigInteger('study_destination_id')->nullable();
            $table->unsignedInteger('moq')->nullable();
            $table->text('message');
            $table->json('attachments')->nullable();
            $table->enum('status', ['pending', 'processing', 'validated', 'cancelled'])->default('pending');
            $table->timestamps();

            $table->index(['client_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
