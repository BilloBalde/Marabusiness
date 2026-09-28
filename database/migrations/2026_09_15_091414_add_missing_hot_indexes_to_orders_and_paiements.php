<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Neither column was ever indexed, unlike orders.payment_status and
 * (vendor_id, payment_status) next to it:
 *
 *  - orders.status: every tab of the admin order list (all/negotiating/new/
 *    processing/shipped/delivered/cancelled — OrderResource/Pages/ListOrders.php)
 *    filters on it, on every page load, and the table only grows.
 *  - paiements.confirmed_at: the vendor navigation badge's whereNull('confirmed_at')
 *    (Vendor/Resources/OrderResource.php) runs on every admin-panel page a vendor
 *    is on, now cached per-request but still a full scan the first time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('paiements', function (Blueprint $table) {
            $table->index('confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('paiements', function (Blueprint $table) {
            $table->dropIndex(['confirmed_at']);
        });
    }
};
