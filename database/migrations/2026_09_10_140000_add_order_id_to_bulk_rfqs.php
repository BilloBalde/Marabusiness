<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a price negotiation hang off an order.
 *
 * A bulk_rfqs row has always described one product a buyer wants quoted. The
 * checkout negotiation is a different shape: a buyer opens it on a whole vendor
 * basket, and the order exists from the first click. The request still carries
 * the conversation and the buyer's target price, but it now points at the order
 * those messages are about.
 *
 * Nullable on purpose — the original single-product RFQ flow still creates rows
 * with no order, and RfqOfferConverter still builds an order from an accepted
 * offer. Both shapes live in this table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bulk_rfqs', function (Blueprint $table) {
            if (!Schema::hasColumn('bulk_rfqs', 'order_id')) {
                $table->foreignId('order_id')
                    ->nullable()
                    ->after('vendor_product_id')
                    ->constrained()
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('bulk_rfqs', function (Blueprint $table) {
            if (Schema::hasColumn('bulk_rfqs', 'order_id')) {
                $table->dropConstrainedForeignId('order_id');
            }
        });
    }
};
