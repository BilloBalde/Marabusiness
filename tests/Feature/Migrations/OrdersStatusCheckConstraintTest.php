<?php

namespace Tests\Feature\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * database/migrations/2025_04_19_051427_create_orders_table.php declares:
 *
 *   $table->enum('status', ['new', 'processing', 'shipped', 'delivered', 'canceled'])->default('new');
 *
 * On SQLite, Laravel's enum() compiles to `varchar check ("status" in (...))` — a real
 * CHECK constraint, not just documentation. The application, however, writes the status
 * 'cancelled' (double L) and 'negotiating' throughout (OrderResource, OrderNegotiation,
 * OrderStats, ...), neither of which is in that original allow-list.
 *
 * This only works because a later migration — 2026_09_05_130300_increase_rate_to_usd_precision_on_orders.php
 * — calls ->change() on the orders table for an unrelated column (rate_to_usd). On SQLite,
 * ANY ->change() rebuilds the whole table (SQLiteGrammar::compileAlter): every other column
 * is re-declared from Blueprint\BlueprintState, which is populated by introspecting the LIVE
 * schema via `pragma_table_xinfo` (SQLiteProcessor::processColumns / SQLiteGrammar::compileColumns).
 * SQLite's pragma_table_xinfo only ever reports a column's bare type name — it has no concept
 * of CHECK constraints, so the rebuilt column definition for `status` loses the check(...)
 * clause entirely. The enum's allow-list silently stops being enforced from that migration
 * onward, which is what let 'cancelled' and 'negotiating' start working, and is also how a
 * status could earlier only ever have been 'canceled' (matching the original enum) before
 * later becoming a free-form string.
 *
 * This test proves the constraint is gone after the full migration stack runs — i.e. against
 * a fresh install today, not just historically on the already-migrated production database.
 */
class OrdersStatusCheckConstraintTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function orders_status_check_constraint_does_not_survive_the_later_rebuild(): void
    {
        $createSql = DB::selectOne(
            "SELECT sql FROM sqlite_master WHERE type = 'table' AND name = 'orders'"
        )->sql;

        $this->assertStringNotContainsStringIgnoringCase(
            'check',
            $createSql,
            "Expected the orders.status CHECK constraint from the original enum() to have been ".
            "dropped by the later ->change() rebuild (2026_09_05_130300_increase_rate_to_usd_precision_on_orders.php). ".
            "If this assertion fails, the constraint survived and the finding is wrong: current CREATE TABLE was:\n".$createSql
        );
    }

    #[Test]
    public function an_arbitrary_status_string_can_be_written_with_no_enum_enforcement_left(): void
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Test Buyer',
            'email' => 'buyer@example.test',
            'password' => bcrypt('secret'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Neither spelling nor value is in the original enum('new','processing','shipped',
        // 'delivered','canceled'). If the CHECK constraint were still enforced, this insert
        // would throw a QueryException (SQLSTATE[23000]: CHECK constraint failed).
        $orderId = DB::table('orders')->insertGetId([
            'user_id' => $userId,
            'order_number' => 'TEST-'.uniqid(),
            'status' => 'not-an-enum-value-at-all',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'status' => 'not-an-enum-value-at-all',
        ]);
    }
}
