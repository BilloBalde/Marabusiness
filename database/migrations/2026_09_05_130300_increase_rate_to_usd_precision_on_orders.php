<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * orders.rate_to_usd was decimal(10,4). The GNF to USD rate is around 0.000116,
     * which rounds to 0.0001 at four decimals — a 14% error on every stored order, and
     * 0 for any weaker currency. Since shipping amounts are stored in vendor currency
     * and mirrored to USD through this rate, the locality pricing cannot be trusted
     * until it holds enough precision.
     *
     * The column stays nullable: 141 historical orders predate it, and back-filling
     * them with today's exchange rate would misstate what those orders were worth.
     * currencies.rate_to_usd is a double and already precise; only the per-order
     * snapshot was truncating.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('rate_to_usd', 18, 10)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('rate_to_usd', 10, 4)->nullable()->change();
        });
    }
};
