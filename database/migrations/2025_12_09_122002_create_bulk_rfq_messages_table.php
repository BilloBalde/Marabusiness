<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bulk_rfq_messages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bulk_rfq_id')
                ->constrained()
                ->onDelete('cascade');

            // sender: user or vendor
            $table->morphs('sender'); 
            // sender_type, sender_id

            $table->text('message');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_rfq_messages');
    }
};

