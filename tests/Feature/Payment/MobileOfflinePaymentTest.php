<?php

namespace Tests\Feature\Payment;

use App\Mail\PaymentDeclared;
use App\Models\Currency;
use App\Models\Order;
use App\Models\Paiement;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * POST /api/v1/orders/{id}/payment/offline is the mobile equivalent of the web's
 * PaiementModal — the buyer declaring they have paid. It rejected cash on
 * delivery outright ('in:om') and always demanded a proof image, so a mobile
 * buyer handing cash to a courier had no way to declare it at all, while the
 * same buyer on the web could. Cash on delivery is the mobile app's *default*
 * payment method (checkout_provider.dart), which made this the common case.
 *
 * Two further contradictions in the same method: the payment was written with a
 * hardcoded 'om' method while its financial transaction was written with a
 * hardcoded 'cod' — neither reflecting what the buyer actually chose.
 */
class MobileOfflinePaymentTest extends TestCase
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
    public function a_buyer_can_declare_a_cash_on_delivery_payment_without_a_receipt(): void
    {
        [$order, $buyer] = $this->order();
        Sanctum::actingAs($buyer);

        $response = $this->postJson("/api/v1/orders/{$order->id}/payment/offline", [
            'payment_method' => 'cod',
            'amount' => 290000,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('paiements', [
            'order_id' => $order->id,
            'payment_method' => 'cod',
            'amount' => 290000,
            'confirmed_at' => null,
        ]);
    }

    #[Test]
    public function orange_money_still_requires_a_receipt(): void
    {
        [$order, $buyer] = $this->order();
        Sanctum::actingAs($buyer);

        $this->postJson("/api/v1/orders/{$order->id}/payment/offline", [
            'payment_method' => 'om',
            'amount' => 290000,
        ])->assertStatus(422);

        $this->assertDatabaseCount('paiements', 0);
    }

    #[Test]
    public function an_orange_money_declaration_with_a_receipt_is_accepted(): void
    {
        Storage::fake('public');
        [$order, $buyer] = $this->order();
        Sanctum::actingAs($buyer);

        $this->post("/api/v1/orders/{$order->id}/payment/offline", [
            'payment_method' => 'om',
            'amount' => 290000,
            'image' => UploadedFile::fake()->image('recu.jpg'),
        ], ['Accept' => 'application/json'])->assertOk();

        $payment = Paiement::first();
        $this->assertSame('om', $payment->payment_method);
        $this->assertNotNull($payment->image);
    }

    #[Test]
    public function the_declaration_does_not_settle_the_order_on_its_own(): void
    {
        [$order, $buyer] = $this->order();
        Sanctum::actingAs($buyer);

        $this->postJson("/api/v1/orders/{$order->id}/payment/offline", [
            'payment_method' => 'cod',
            'amount' => 290000,
        ])->assertOk();

        // Same rule as the web: only the vendor's confirmation moves the balance.
        $order->refresh();
        $this->assertSame(0.0, (float) $order->total_paid);
        $this->assertSame('pending', $order->payment_status);
    }

    #[Test]
    public function the_vendor_is_emailed_so_the_confirmation_flow_actually_starts(): void
    {
        // The whole point of the fix: a mobile declaration now reaches the same
        // vendor confirmation workflow (email + navigation badge) as a web one.
        Mail::fake();
        [$order, $buyer, $vendor] = $this->order();
        Sanctum::actingAs($buyer);

        $this->postJson("/api/v1/orders/{$order->id}/payment/offline", [
            'payment_method' => 'cod',
            'amount' => 290000,
        ])->assertOk();

        Mail::assertSent(
            PaymentDeclared::class,
            fn (PaymentDeclared $mail) => $mail->hasTo($vendor->user->email)
        );
    }

    #[Test]
    public function a_second_declaration_is_refused_while_the_first_awaits_confirmation(): void
    {
        [$order, $buyer] = $this->order();
        Sanctum::actingAs($buyer);

        $this->postJson("/api/v1/orders/{$order->id}/payment/offline", [
            'payment_method' => 'cod',
            'amount' => 290000,
        ])->assertOk();

        $this->postJson("/api/v1/orders/{$order->id}/payment/offline", [
            'payment_method' => 'cod',
            'amount' => 290000,
        ])->assertStatus(409);

        $this->assertDatabaseCount('paiements', 1);
    }

    // --- Ce que l'API montre ensuite de ce paiement --------------------------

    #[Test]
    public function a_declared_payment_is_not_reported_as_paid_by_the_api(): void
    {
        // The API used to send paiements[].status = paiements.payment_status,
        // which reads 'paid' on an unconfirmed declaration — contradicting the
        // order's own 'pending' status in the very same response.
        [$order, $buyer] = $this->order();
        Sanctum::actingAs($buyer);

        $this->postJson("/api/v1/orders/{$order->id}/payment/offline", [
            'payment_method' => 'cod',
            'amount' => 290000,
        ])->assertOk();

        $response = $this->getJson("/api/v1/orders/{$order->id}");

        $response->assertOk();
        $response->assertJsonPath('data.paiements.0.status', 'awaiting_confirmation');
        $response->assertJsonPath('data.paiements.0.confirmed', false);
        $response->assertJsonPath('data.paiements.0.paid_at', null);
        $response->assertJsonPath('data.payment_status', 'pending');
        // The declaration time is still available, under a name that means it.
        $this->assertNotNull($response->json('data.paiements.0.declared_at'));
    }

    #[Test]
    public function the_api_reports_the_payment_as_paid_once_the_vendor_confirms(): void
    {
        [$order, $buyer, $vendor] = $this->order();
        Sanctum::actingAs($buyer);

        $this->postJson("/api/v1/orders/{$order->id}/payment/offline", [
            'payment_method' => 'cod',
            'amount' => 290000,
        ])->assertOk();

        Paiement::where('order_id', $order->id)->first()->confirm($vendor->user);

        $response = $this->getJson("/api/v1/orders/{$order->id}");

        $response->assertJsonPath('data.paiements.0.status', 'confirmed');
        $response->assertJsonPath('data.paiements.0.confirmed', true);
        $this->assertNotNull($response->json('data.paiements.0.paid_at'));
        $response->assertJsonPath('data.payment_status', 'paid');
    }

    #[Test]
    public function the_api_tells_the_app_a_declaration_is_awaiting_confirmation(): void
    {
        // Without this the app shows the "pay now" button to a buyer who already
        // handed over cash, because payment_status reads 'pending' either way.
        [$order, $buyer, $vendor] = $this->order();
        Sanctum::actingAs($buyer);

        $this->getJson("/api/v1/orders/{$order->id}")
            ->assertJsonPath('data.declared_awaiting_confirmation', 0);

        $this->postJson("/api/v1/orders/{$order->id}/payment/offline", [
            'payment_method' => 'cod',
            'amount' => 290000,
        ])->assertOk();

        $this->getJson("/api/v1/orders/{$order->id}")
            ->assertJsonPath('data.declared_awaiting_confirmation', 290000);

        Paiement::where('order_id', $order->id)->first()->confirm($vendor->user);

        $this->getJson("/api/v1/orders/{$order->id}")
            ->assertJsonPath('data.declared_awaiting_confirmation', 0);
    }

    #[Test]
    public function a_buyer_cannot_declare_a_payment_on_someone_elses_order(): void
    {
        [$order] = $this->order();
        $stranger = User::factory()->create();
        Sanctum::actingAs($stranger);

        $this->postJson("/api/v1/orders/{$order->id}/payment/offline", [
            'payment_method' => 'cod',
            'amount' => 290000,
        ])->assertStatus(404);

        $this->assertDatabaseCount('paiements', 0);
    }
}
