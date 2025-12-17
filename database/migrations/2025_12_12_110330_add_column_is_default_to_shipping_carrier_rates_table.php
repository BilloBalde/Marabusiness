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
        Schema::table('shipping_carrier_rates', function (Blueprint $table) {
            $table->boolean('is_default')->default(false); // ADD THIS
            $table->decimal('min_rate', 10, 2)->default(0); // ADD THIS
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipping_carrier_rates', function (Blueprint $table) {
            $table->dropColumn(['is_default', 'min_rate']);
        });
    }
};
