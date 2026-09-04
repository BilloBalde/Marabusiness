<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            // link to user
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();

            // make order_id optional (because profile addresses are not tied to an order)
            $table->foreignId('order_id')->nullable()->change();

            // optional: make one default address per user
            $table->boolean('is_default')->default(false)->after('zone');

            $table->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'is_default']);
            $table->dropColumn(['user_id', 'is_default']);
        });
    }
};
