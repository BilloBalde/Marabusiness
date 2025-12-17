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
            $table->string('service_code')->default('STANDARD')->after('carrier');
            $table->string('carrier_account_id')->default('01NJEK90')->after('service_code');
            $table->decimal('rate_per_carton', 10, 2)->default(0.00)->after('rate_per_item');
            $table->decimal('max_rate', 10, 2)->default(0.00)->after('min_rate');
            $table->decimal('free_shipping_threshold')->default(0.00)->after('max_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipping_carrier_rates', function (Blueprint $table) {
            //
        });
    }
};
