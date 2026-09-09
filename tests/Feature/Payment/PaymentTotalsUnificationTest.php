<?php

namespace Tests\Feature\Payment;

use App\Filament\Resources\PaiementResource;
use App\Filament\Resources\PaiementResource\Pages\CreatePaiement;
use App\Mail\PaymentDeclared;
use App\Models\Currency;
use App\Models\Order;
use App\Models\Paiement;
use App\Models\User;
use App\Models\Vendor;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The audit found seven places computing an order's balance with an unfiltered
 * paiements()->sum('amount') — counting a buyer's own unconfirmed declaration as
 * money in hand. All of them now delegate to Order::syncPaymentTotals() (or its
 * PaiementResource::updateOrderTotals() wrapper), which counts confirmed money
 * only. These tests lock that in at the two admin entry points that had their own
 * copy of the broken math: the standalone Paiements resource, and its per-row
 * edit/delete actions.
 */
class PaymentTotalsUnificationTest extends TestCase
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
    public function an_unconfirmed_declaration_does_not_settle_the_order_when_staff_touch_it(): void
    {
        // Before the fix: an admin editing or deleting any payment on this order —
        // even one unrelated to the buyer's declaration — recomputed the balance by
        // summing every row, banking the undeclared money as received.
        [$order, $buyer] = $this->order();

        Paiement::create([
            'order_id' => $order->id,
            'amount' => 290000,
            'currency' => 'GNF',
            'payment_status' => 'paid',
            'payment_method' => 'cod',
            'transaction_id' => Order::generateTransactionNumber(),
            'confirmed_at' => null, // declared by the buyer, not confirmed
        ]);

        PaiementResource::updateOrderTotals($order);

        $order->refresh();
        $this->assertSame(0.0, (float) $order->total_paid);
        $this->assertSame('pending', $order->payment_status);
    }

    #[Test]
    public function a_confirmed_payment_is_correctly_banked_regardless_of_its_own_status_label(): void
    {
        // Root cause of the divergence between the two success pages: one summed
        // every payment, the other filtered on payment_status='paid' and silently
        // dropped a 'partial' payment that was in fact confirmed. Only confirmed_at
        // decides what counts.
        [$order] = $this->order(1055450);

        Paiement::create([
            'order_id' => $order->id, 'amount' => 1000000, 'currency' => 'GNF',
            'payment_status' => 'partial', 'payment_method' => 'cod',
            'transaction_id' => Order::generateTransactionNumber(), 'confirmed_at' => now(),
        ]);
        Paiement::create([
            'order_id' => $order->id, 'amount' => 55450, 'currency' => 'GNF',
            'payment_status' => 'paid', 'payment_method' => 'stripe',
            'transaction_id' => Order::generateTransactionNumber(), 'confirmed_at' => now(),
        ]);

        PaiementResource::updateOrderTotals($order);

        $order->refresh();
        $this->assertSame(1055450.0, (float) $order->total_paid);
        $this->assertSame('paid', $order->payment_status);
    }

    #[Test]
    public function a_payment_entered_by_staff_through_the_paiements_resource_is_auto_confirmed(): void
    {
        // Staff typing a payment directly into the back office have the money or the
        // proof in hand — same as CreateOrder's initial payment and the counter sale
        // in PointOfSale. Without confirmed_at set at creation, Paiement::booted()
        // would read it as an unconfirmed buyer declaration and email the vendor to
        // confirm a payment their own admin just recorded.
        Mail::fake();
        [$order] = $this->order();
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::actingAs($admin)
            ->test(CreatePaiement::class)
            ->fillForm([
                'order_id' => $order->id,
                'amount' => 290000,
                'payment_method' => 'cod',
                'currency' => 'gnf',
                'payment_status' => 'paid',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $payment = Paiement::where('order_id', $order->id)->first();
        $this->assertNotNull($payment->confirmed_at);
        $this->assertSame($admin->id, $payment->confirmed_by);

        $order->refresh();
        $this->assertSame(290000.0, (float) $order->total_paid);
        $this->assertSame('paid', $order->payment_status);

        Mail::assertNotSent(PaymentDeclared::class);
    }
}
