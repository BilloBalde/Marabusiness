<?php

namespace Tests\Feature\Payment;

use App\Models\Currency;
use App\Models\Order;
use App\Models\Paiement;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * POST /orders/{id}/payment/session with payment_method=cod is what the mobile
 * payment modal actually calls to declare a cash-on-delivery payment
 * (payment_modal.dart:419-424) — not submitOfflinePayment, which exists in
 * api_service.dart but is never called from anywhere.
 */
class MobileCodSessionTest extends TestCase
{
    use RefreshDatabase;

    private function order(float $total = 290000): array
    {
        $currency = Currency::factory()->create(['code' => 'GNF', 'rate_to_usd' => 0.00012]);
        $vendor = Vendor::factory()->create(['currency_id' => $currency->id]);
        $buyer = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $buyer->id,
            'vendor_id' => $vendor->id,
            'grand_total' => $total,
            'total_remaining' => $total,
            'total_paid' => 0,
            'payment_status' => 'pending',
            'rate_to_usd' => 0.00012,
        ]);

        return [$order, $buyer, $vendor];
    }

    #[Test]
    public function declaring_cash_on_delivery_records_a_payment_awaiting_confirmation(): void
    {
        [$order, $buyer] = $this->order();
        Sanctum::actingAs($buyer);

        $this->postJson("/api/v1/orders/{$order->id}/payment/session", [
            'payment_method' => 'cod',
        ])->assertOk();

        $payment = Paiement::where('order_id', $order->id)->firstOrFail();
        $this->assertNull($payment->confirmed_at);

        // The declaration alone must not settle the order.
        $order->refresh();
        $this->assertSame(0.0, (float) $order->total_paid);
        $this->assertSame('pending', $order->payment_status);
    }

    #[Test]
    public function no_placeholder_image_is_stored_for_a_cash_payment(): void
    {
        // 'payments/default.png' was written for every COD declaration — a file
        // that does not exist on disk, leaving 12 real payments pointing at a
        // broken image that the back office then tries to render.
        [$order, $buyer] = $this->order();
        Sanctum::actingAs($buyer);

        $this->postJson("/api/v1/orders/{$order->id}/payment/session", [
            'payment_method' => 'cod',
        ])->assertOk();

        $this->assertNull(Paiement::where('order_id', $order->id)->value('image'));
    }

    #[Test]
    public function a_second_cash_declaration_is_refused_while_the_first_awaits_confirmation(): void
    {
        // Nothing stopped a buyer tapping "pay with cash" repeatedly, each tap
        // writing another payment row for the same money — the web has guarded
        // this since PaiementModal was fixed.
        [$order, $buyer] = $this->order();
        Sanctum::actingAs($buyer);

        $this->postJson("/api/v1/orders/{$order->id}/payment/session", [
            'payment_method' => 'cod',
        ])->assertOk();

        $this->postJson("/api/v1/orders/{$order->id}/payment/session", [
            'payment_method' => 'cod',
        ])->assertStatus(409);

        $this->assertDatabaseCount('paiements', 1);
    }
}
