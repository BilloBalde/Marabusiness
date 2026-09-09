<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * orders, order_items, vendor_product, messages and bulk_rfqs are the tables every
 * order, storefront listing and chat query filters by — and none of them carried an
 * index on the columns that filtering actually happens on, not even their own foreign
 * keys. Every vendor-panel order list (OrderResource::getEloquentQuery()), every
 * buyer's "My Orders" page, every product's vendor listing, every chat thread and
 * every RFQ list was a full table scan. Invisible at 182 orders; not at 50,000.
 *
 * Column choices come from what this session actually saw queried, not a blanket
 * "index everything": orders.vendor_id/user_id/payment_status (OrderResource,
 * MyOrdersPage, the finance dashboards), order_items.order_id/product_id
 * (Order::items(), reporting), vendor_product.vendor_id/product_id (queried both
 * separately and, constantly, together — HomePage, EditProduct, the storefront),
 * messages.sender_id/receiver_id (ChatController, CustomerChat), bulk_rfqs.user_id/
 * vendor_id/status (UserRfqsPage, the vendor RFQ resource).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index('vendor_id');
            $table->index('user_id');
            $table->index('payment_status');
            $table->index(['vendor_id', 'payment_status']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->index('order_id');
            $table->index('product_id');
        });

        Schema::table('vendor_product', function (Blueprint $table) {
            $table->index('vendor_id');
            $table->index('product_id');
            $table->index(['vendor_id', 'product_id']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->index('sender_id');
            $table->index('receiver_id');
        });

        Schema::table('bulk_rfqs', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('vendor_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['vendor_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['payment_status']);
            $table->dropIndex(['vendor_id', 'payment_status']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['order_id']);
            $table->dropIndex(['product_id']);
        });

        Schema::table('vendor_product', function (Blueprint $table) {
            $table->dropIndex(['vendor_id']);
            $table->dropIndex(['product_id']);
            $table->dropIndex(['vendor_id', 'product_id']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['sender_id']);
            $table->dropIndex(['receiver_id']);
        });

        Schema::table('bulk_rfqs', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['vendor_id']);
            $table->dropIndex(['status']);
        });
    }
};
