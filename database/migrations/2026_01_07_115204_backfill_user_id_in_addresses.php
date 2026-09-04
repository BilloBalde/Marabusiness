<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("
                UPDATE addresses
                SET user_id = (
                    SELECT o.user_id
                    FROM orders o
                    WHERE o.id = addresses.order_id
                )
                WHERE user_id IS NULL
                  AND order_id IS NOT NULL
            ");

            return;
        }

        DB::statement("
            UPDATE addresses a
            JOIN orders o ON o.id = a.order_id
            SET a.user_id = o.user_id
            WHERE a.user_id IS NULL
        ");
    }

    public function down(): void
    {
        // no-op
    }
};
