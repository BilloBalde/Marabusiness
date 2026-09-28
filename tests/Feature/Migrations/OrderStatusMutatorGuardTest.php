<?php

namespace Tests\Feature\Migrations;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Companion to OrdersStatusCheckConstraintTest: the database itself no longer
 * enforces orders.status (that test proves it, at the raw DB::table() level,
 * and stays that way — this doesn't touch the schema). Order::setStatusAttribute()
 * is the replacement guard, at the one layer every write in this app actually
 * goes through: Eloquent.
 */
class OrderStatusMutatorGuardTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function every_status_actually_used_in_the_app_is_accepted(): void
    {
        $order = Order::factory()->create(['status' => 'new']);

        foreach (Order::STATUSES as $status) {
            $order->status = $status;
            $order->save();

            $this->assertSame($status, $order->fresh()->status);
        }
    }

    #[Test]
    public function an_invalid_status_is_rejected_before_it_ever_reaches_the_database(): void
    {
        $order = Order::factory()->create(['status' => 'new']);

        $this->expectException(\InvalidArgumentException::class);

        $order->update(['status' => 'not-an-enum-value-at-all']);
    }
}
