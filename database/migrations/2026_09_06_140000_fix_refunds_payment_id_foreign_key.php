<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * refunds.payment_id was created with `constrained()` and no explicit table name,
     * which derives the target from the column by convention: payment_id -> payments.
     * The payments table has always been named `paiements`, so the constraint pointed
     * at a table that never existed.
     *
     * With Laravel's sqlite connection enforcing PRAGMA foreign_keys = ON by default,
     * this made every statement that validates the schema against `refunds` fail —
     * including deleting an order, which cascades into refunds — with
     * "no such table: main.payments", regardless of whether any refund row existed.
     *
     * SQLite has no ALTER TABLE ... DROP CONSTRAINT, so the column is dropped and
     * re-added pointing at the real table. Laravel's schema builder does this as a
     * table rebuild under the hood.
     */
    public function up(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->dropForeign(['payment_id']);
            $table->dropColumn('payment_id');
        });

        Schema::table('refunds', function (Blueprint $table) {
            $table->foreignId('payment_id')->nullable()->after('order_id')
                ->constrained('paiements')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->dropForeign(['payment_id']);
            $table->dropColumn('payment_id');
        });

        Schema::table('refunds', function (Blueprint $table) {
            $table->foreignId('payment_id')->nullable()->after('order_id')
                ->constrained('payments')->nullOnDelete();
        });
    }
};
