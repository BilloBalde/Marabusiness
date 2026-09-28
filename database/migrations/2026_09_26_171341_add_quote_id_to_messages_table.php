<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The existing marketplace chat (Chat.php, CustomerChat.php) reads/writes this
 * same table by (sender_id, receiver_id) pairs alone — this column stays null
 * for every one of those messages. It only gets set for the client<->admin
 * thread a Quote opens, so a conversation can be found by quote_id instead of
 * having to reconstruct it from a sender/receiver pair that could, in theory,
 * also be exchanging ordinary marketplace messages at the same time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('quote_id')->nullable()->after('id')->constrained('quotes')->nullOnDelete();
            $table->index('quote_id');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('quote_id');
        });
    }
};
