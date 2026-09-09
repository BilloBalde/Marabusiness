<?php

namespace Tests\Feature\Performance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * orders, order_items, vendor_product, messages and bulk_rfqs — the tables
 * every order, storefront listing and chat query filters by — had no index at
 * all beyond a couple of unrelated unique constraints, not even on their own
 * foreign keys. Every vendor-panel order list, every buyer's order history,
 * every product's vendor listing and every chat thread was a full table scan.
 * A guard against this quietly regressing (a migration rolled back, a fresh
 * schema built without it) rather than a performance benchmark.
 */
class DatabaseIndexesTest extends TestCase
{
    use RefreshDatabase;

    private function indexedColumns(string $table): array
    {
        $columns = [];

        foreach (DB::select("PRAGMA index_list(\"{$table}\")") as $index) {
            foreach (DB::select("PRAGMA index_info(\"{$index->name}\")") as $info) {
                $columns[] = $info->name;
            }
        }

        return $columns;
    }

    #[Test]
    public function the_hot_tables_are_indexed_on_the_columns_they_are_actually_filtered_by(): void
    {
        $expected = [
            'orders' => ['vendor_id', 'user_id', 'payment_status'],
            'order_items' => ['order_id', 'product_id'],
            'vendor_product' => ['vendor_id', 'product_id'],
            'messages' => ['sender_id', 'receiver_id'],
            'bulk_rfqs' => ['user_id', 'vendor_id', 'status'],
        ];

        foreach ($expected as $table => $columns) {
            $indexed = $this->indexedColumns($table);

            foreach ($columns as $column) {
                $this->assertContains(
                    $column,
                    $indexed,
                    "{$table}.{$column} has no index."
                );
            }
        }
    }
}
