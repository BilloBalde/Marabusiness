<?php

namespace Tests\Feature\Invoice;

use App\Models\Order;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The invoice's QR code used to encode the bare order number — scanning it
 * with a phone just showed that string, nothing opened. It now encodes a
 * signed URL to /orders/{order}/invoice/scan, a route deliberately outside
 * the 'auth' group (a phone scanning it is almost never logged into the
 * site) and protected instead by Laravel's 'signed' middleware: it 403s
 * unless the URL carries the exact signature minted for that one order.
 */
class InvoiceScanTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrderWithVendor(): Order
    {
        $vendor = Vendor::factory()->create();

        return Order::factory()->create(['vendor_id' => $vendor->id]);
    }

    #[Test]
    public function a_signed_link_opens_the_invoice_with_no_login_at_all(): void
    {
        $order = $this->makeOrderWithVendor();

        $url = URL::signedRoute('orders.invoice.scan', ['order' => $order->id]);

        $this->get($url)
            ->assertOk()
            ->assertSee($order->order_number);
    }

    #[Test]
    public function the_scan_route_rejects_a_request_with_no_signature(): void
    {
        $order = $this->makeOrderWithVendor();

        $this->get("/orders/{$order->id}/invoice/scan")
            ->assertForbidden();
    }

    #[Test]
    public function a_valid_signature_cannot_be_replayed_against_a_different_order(): void
    {
        $order = $this->makeOrderWithVendor();
        $otherOrder = $this->makeOrderWithVendor();

        $url = URL::signedRoute('orders.invoice.scan', ['order' => $order->id]);
        $swapped = str_replace("/orders/{$order->id}/invoice/scan", "/orders/{$otherOrder->id}/invoice/scan", $url);

        $this->get($swapped)->assertForbidden();
    }
}
