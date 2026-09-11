<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The negotiation state of an order.
 *
 * A buyer can now open a price discussion from checkout. The order is created
 * immediately so both sides have something concrete to talk about, but it is not
 * payable until a price is agreed.
 *
 * The agreed figure lives here rather than on bulk_rfq_offers because that table
 * models a single-product quote — unit_price is NOT NULL and pairs with moq, and
 * a basket has no single unit price. The order is also what actually gets paid,
 * so it is the honest place for the number.
 *
 * order_items are deliberately NOT repriced: the table carries no
 * vendor_product_id or variation_id, so a line cannot be traced back to the
 * listing it came from. The negotiated figure is an order-level total, which is
 * also what was asked for — the discussion is per vendor, not per line.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'negotiation_status')) {
                // open | priced | agreed. Null on every order that was never
                // negotiated, which is all of them until now.
                $table->string('negotiation_status')->nullable()->after('status');
            }

            if (!Schema::hasColumn('orders', 'negotiated_total')) {
                $table->decimal('negotiated_total', 12, 2)->nullable()->after('negotiation_status');
            }

            if (!Schema::hasColumn('orders', 'negotiated_expires_at')) {
                $table->timestamp('negotiated_expires_at')->nullable()->after('negotiated_total');
            }

            if (!Schema::hasColumn('orders', 'pre_negotiation_total')) {
                // What the basket cost before anyone haggled. Kept so the buyer
                // can refuse and still see what they would otherwise pay, and so
                // the invoice can show the difference as a discount.
                $table->decimal('pre_negotiation_total', 12, 2)->nullable()->after('negotiated_expires_at');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index('negotiation_status');
        });

        // One row spells it the American way. Two spellings of one state means
        // every filter, badge and revenue query silently misses that order.
        DB::table('orders')->where('status', 'canceled')->update(['status' => 'cancelled']);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['negotiation_status']);

            $table->dropColumn([
                'negotiation_status',
                'negotiated_total',
                'negotiated_expires_at',
                'pre_negotiation_total',
            ]);
        });
    }
};
