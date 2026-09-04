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
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('grand_total_usd', 10, 2)->after('total_remaining')->nullable();
            $table->decimal('shipping_amount_usd', 10, 2)->after('grand_total_usd')->nullable();
            $table->decimal('rate_to_usd', 10, 4)->after('shipping_amount_usd')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['grand_total_usd', 'shipping_amount_usd', 'rate_to_usd']);
        });
    }
};
