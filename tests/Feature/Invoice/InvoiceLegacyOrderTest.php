<?php

namespace Tests\Feature\Invoice;

use App\Models\Address;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * 31 orders in the real database (May–Nov 2025, from before checkout split the
 * cart into one order per vendor) have no vendor_id at all. The invoice read
 * $order->vendor->store_name and ->currency->symbol straight off that null —
 * a warning in tinker, but Laravel turns warnings into exceptions on a real
 * request, so the customer got a 500. It only became reachable by customers
 * once "Voir le reçu" was added to their order page.
 */
class InvoiceLegacyOrderTest extends TestCase
{
    use RefreshDatabase;

    private function legacyOrder(): Order
    {
        $buyer = User::factory()->create();

        $order = Order::create([
            'user_id' => $buyer->id,
            'vendor_id' => null,
            'order_number' => 'INV2025050002',
            'grand_total' => 220,
            'total_paid' => 220,
            'total_remaining' => 0,
            'payment_method' => 'cod',
            'payment_status' => 'paid',
            'status' => 'new',
        ]);

        Address::create([
            'order_id' => $order->id,
            'user_id' => $buyer->id,
            'first_name' => 'A', 'last_name' => 'B', 'phone' => '600000000',
            'street_address' => 'Rue 1', 'city' => 'Conakry', 'state' => 'Conakry',
            'country' => 'Guinea',
        ]);

        return $order;
    }

    #[Test]
    public function an_order_with_no_vendor_still_renders_its_invoice(): void
    {
        $order = $this->legacyOrder();

        $this->actingAs($order->user)
            ->get(route('orders.invoice.preview', $order))
            ->assertOk()
            ->assertSee('INV2025050002');
    }

    #[Test]
    public function an_order_with_no_vendor_still_downloads_as_a_pdf(): void
    {
        $order = $this->legacyOrder();

        $response = $this->actingAs($order->user)
            ->get(route('orders.invoice.pdf', $order));

        $response->assertOk();
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }
}
