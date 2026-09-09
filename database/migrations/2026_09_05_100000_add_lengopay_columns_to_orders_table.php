<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * PaymentController and CheckoutController both write these two keys when a
     * LengoPay session is created, and LengoPayWebhookController looks the order
     * up by lengopay_pay_id. Neither column existed, so the pay id was silently
     * dropped and the webhook could never match an order.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('lengopay_pay_id')->nullable()->after('stripe_session_id');
            $table->string('lengopay_payment_url')->nullable()->after('lengopay_pay_id');

            $table->index('lengopay_pay_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['lengopay_pay_id']);
            $table->dropColumn(['lengopay_pay_id', 'lengopay_payment_url']);
        });
    }
};
