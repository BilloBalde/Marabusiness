<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Fix existing data for SQLite
        // Note: SQLite uses single quotes, and double backslashes
        DB::update("
            UPDATE bulk_rfq_messages 
            SET sender_type = 'App\\Models\\User' 
            WHERE sender_type = 'App\\Livewire\\User'
        ");
    }

    public function down(): void
    {
        // Revert if needed
        DB::update("
            UPDATE bulk_rfq_messages 
            SET sender_type = 'App\\Livewire\\User' 
            WHERE sender_type = 'App\\Models\\User'
        ");
    }
};