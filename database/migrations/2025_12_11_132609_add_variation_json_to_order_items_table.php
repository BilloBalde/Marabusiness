<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->json('variation_json')->nullable()->after('total_amount');
            
            // Optional: Add an index if you'll be searching in variations
            // $table->index(['variation_json'], 'variation_json_index');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('variation_json');
            // $table->dropIndex('variation_json_index');
        });
    }
};