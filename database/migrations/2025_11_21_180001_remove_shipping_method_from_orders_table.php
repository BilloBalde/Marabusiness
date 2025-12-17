<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'shipping_method')) {
                $table->dropColumn('shipping_method');
            }

            if (Schema::hasColumn('orders', 'currency')) {
                $table->dropColumn('currency');
            }

            if (!Schema::hasColumn('orders', 'vendor_id')) {
                $table->foreignId('vendor_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('vendors')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'shipping_method')) {
                $table->string('shipping_method')->nullable();
            }

            if (!Schema::hasColumn('orders', 'currency')) {
                $table->string('currency')->nullable();
            }

            if (Schema::hasColumn('orders', 'vendor_id')) {
                $table->dropConstrainedForeignId('vendor_id');
            }
        });
    }
};
