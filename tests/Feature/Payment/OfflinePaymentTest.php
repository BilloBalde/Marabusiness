<?php

namespace Tests\Feature\Payment;

use App\Filament\Vendor\Resources\OrderResource as VendorOrderResource;
use App\Livewire\PaiementModal;
use App\Livewire\SuccessPage;
use App\Mail\PaymentDeclared;
use App\Models\Currency;
use App\Models\Order;
use App\Models\Paiement;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Cash-on-delivery and Orange Money, reported as simply "not working", plus the
 * currency label on the order confirmation screen.
 */
class OfflinePaymentTest extends TestCase
{
    use RefreshDatabase;

    private function order(float $total = 290000, string $currencyCode = 'GNF', float $rate = 0.00012): array
    {
        $currency = Currency::factory()->create(['code' => $currencyCode, 'rate_to_usd' => $rate]);
        $vendor = Vendor::factory()->create(['currency_id' => $currency->id]);
        $buyer = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $buyer->id,
            'vendor_id' => $vendor->id,
            'grand_total' => $total,
            'total_remaining' => $total,
            'total_paid' => 0,
            'payment_status' => 'pending',
            // Left empty on purpose: only 38 of the 179 real orders carry a USD value,
            // so the conversion fallback is the path that actually matters.
            'grand_total_usd' => null,
            'rate_to_usd' => $rate,
        ]);

        return [$order, $buyer, $vendor];
    }

    /**
     * A buyer declaring a payment. Orange Money now requires a receipt, so one is
     * attached unless a test is specifically checking that rule.
     */
    private function pay(Order $order, User $buyer, string $method, $amount = null, $proof = null)
    {
        Storage::fake('public');

        $component = Livewire::actingAs($buyer)
            ->test(PaiementModal::class)
            ->call('openModal', $order->id)
            ->set('payment_method', $method)
            ->set('amount', $amount);

        if ($proof === null && in_array($method, Paiement::METHODS_REQUIRING_PROOF, true)) {
            $proof = File::image('recu-orange-money.jpg');
        }

        if ($proof) {
            $component->set('image', $proof);
        }

        return $component->call('save');
    }

    /**
     * The vendor saying the money arrived — what now settles an order.
     */
    private function confirmAll(Order $order, ?User $by = null): void
    {
        $order->paiements()->whereNull('confirmed_at')->get()
            ->each(fn (Paiement $p) => $p->confirm($by));
    }

    /**
     * The badge is read from whoever is signed in, and `pay()` signs the buyer in to
     * drive the modal. Reading it therefore has to say, at that moment, whose menu we
     * are looking at — otherwise a test can pass simply because nobody is a vendor.
     */
    private function badgeSeenBy(User $user): ?string
    {
        $this->actingAs($user);

        return VendorOrderResource::getNavigationBadge();
    }

    // --- Le blocage signalé -------------------------------------------------

    #[Test]
    public function cash_on_delivery_works_without_typing_an_amount(): void
    {
        // paiements.amount is NOT NULL while the form allowed it to be empty, so this
        // exact case — the natural one, since you pay the courier — died on a database
        // constraint. An unstated amount now means "settle the balance".
        [$order, $buyer] = $this->order();

        $this->pay($order, $buyer, 'cod');

        $this->assertDatabaseHas('paiements', [
            'order_id' => $order->id,
            'payment_method' => 'cod',
            'amount' => 290000,
        ]);
    }

    #[Test]
    public function orange_money_works_without_typing_an_amount(): void
    {
        [$order, $buyer] = $this->order();

        $this->pay($order, $buyer, 'om'); // le justificatif est joint par le helper

        $this->assertDatabaseHas('paiements', ['order_id' => $order->id, 'payment_method' => 'om']);
    }

    // --- Déclaré n'est pas encaissé -----------------------------------------

    #[Test]
    public function a_buyers_declaration_does_not_settle_their_own_order(): void
    {
        // A buyer used to mark their own cash-on-delivery order paid the moment they
        // clicked — before the courier had collected a single note.
        [$order, $buyer] = $this->order();

        $this->pay($order, $buyer, 'cod', 290000);

        $order->refresh();
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame(0.0, (float) $order->total_paid);
        $this->assertSame(290000.0, $order->declaredAwaitingConfirmation());
    }

    #[Test]
    public function the_vendors_confirmation_is_what_marks_the_order_paid(): void
    {
        [$order, $buyer, $vendor] = $this->order();
        $this->pay($order, $buyer, 'cod', 290000);

        $this->confirmAll($order, $vendor->user);

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame(290000.0, (float) $order->total_paid);
        $this->assertSame(0.0, (float) $order->total_remaining);
    }

    #[Test]
    public function the_confirmation_records_who_vouched_for_the_money(): void
    {
        [$order, $buyer, $vendor] = $this->order();
        $this->pay($order, $buyer, 'cod', 290000);

        $this->confirmAll($order, $vendor->user);

        $payment = $order->paiements()->first();
        $this->assertNotNull($payment->confirmed_at);
        $this->assertSame($vendor->user->id, $payment->confirmed_by);
    }

    #[Test]
    public function a_confirmed_part_payment_leaves_the_order_partial(): void
    {
        [$order, $buyer, $vendor] = $this->order();

        $this->pay($order, $buyer, 'om', 100000);
        $this->confirmAll($order, $vendor->user);

        $order->refresh();
        $this->assertSame('partial', $order->payment_status);
        $this->assertSame(190000.0, (float) $order->total_remaining);
    }

    #[Test]
    public function settling_the_balance_afterwards_completes_the_order(): void
    {
        [$order, $buyer, $vendor] = $this->order();

        $this->pay($order, $buyer, 'om', 100000);
        $this->confirmAll($order, $vendor->user);

        $this->pay($order->refresh(), $buyer, 'om'); // solde, sans montant
        $this->confirmAll($order, $vendor->user);

        $order->refresh();
        $this->assertSame(290000.0, (float) $order->total_paid);
        $this->assertSame('paid', $order->payment_status);
    }

    #[Test]
    public function confirming_twice_does_not_double_the_balance(): void
    {
        [$order, $buyer, $vendor] = $this->order();
        $this->pay($order, $buyer, 'cod', 290000);

        $payment = $order->paiements()->first();
        $payment->confirm($vendor->user);
        $payment->confirm($vendor->user); // deuxième clic

        $this->assertSame(290000.0, (float) $order->refresh()->total_paid);
    }

    // --- Justificatif -------------------------------------------------------

    #[Test]
    public function orange_money_requires_a_receipt(): void
    {
        // Money really moves on an Orange Money transfer and the buyer has a
        // confirmation screen for it; without one the vendor has nothing to check.
        [$order, $buyer] = $this->order();

        Livewire::actingAs($buyer)
            ->test(PaiementModal::class)
            ->call('openModal', $order->id)
            ->set('payment_method', 'om')
            ->set('amount', 100000)
            ->call('save')
            ->assertHasErrors('image');

        $this->assertDatabaseCount('paiements', 0);
    }

    #[Test]
    public function cash_on_delivery_does_not_require_a_receipt(): void
    {
        // There is nothing to photograph when you hand notes to a courier.
        [$order, $buyer] = $this->order();

        $this->pay($order, $buyer, 'cod', 290000)->assertHasNoErrors();

        $this->assertDatabaseCount('paiements', 1);
    }

    #[Test]
    public function an_orange_money_receipt_is_stored_with_the_payment(): void
    {
        [$order, $buyer] = $this->order();

        $this->pay($order, $buyer, 'om', 100000);

        $this->assertNotNull($order->paiements()->first()->image);
    }

    // --- Sur-paiement -------------------------------------------------------

    #[Test]
    public function an_already_settled_order_refuses_a_further_payment(): void
    {
        [$order, $buyer, $vendor] = $this->order();
        $this->pay($order, $buyer, 'cod', 290000);
        $this->confirmAll($order, $vendor->user);

        $this->pay($order->refresh(), $buyer, 'om', 50000)
            ->assertHasErrors('amount');

        $this->assertSame(290000.0, (float) $order->refresh()->total_paid);
    }

    #[Test]
    public function a_declaration_still_pending_blocks_a_second_one(): void
    {
        // Otherwise a buyer could declare the same amount again while the first is
        // still being checked, and the vendor would see two claims for one payment.
        [$order, $buyer] = $this->order();
        $this->pay($order, $buyer, 'cod', 290000);

        $this->pay($order->refresh(), $buyer, 'cod', 290000)
            ->assertHasErrors('amount');

        $this->assertDatabaseCount('paiements', 1);
    }

    #[Test]
    public function a_payment_larger_than_the_balance_is_capped(): void
    {
        [$order, $buyer, $vendor] = $this->order();

        $this->pay($order, $buyer, 'om', 500000);
        $this->confirmAll($order, $vendor->user);

        // Banking more than the order is worth would leave the vendor's books wrong.
        $this->assertSame(290000.0, (float) $order->refresh()->total_paid);
        $this->assertSame(290000.0, (float) Paiement::where('order_id', $order->id)->sum('amount'));
    }

    // --- Alerter le vendeur -------------------------------------------------

    #[Test]
    public function the_vendor_is_emailed_when_a_buyer_declares_a_payment(): void
    {
        // Declarations no longer settle an order, so without this the money would sit
        // unconfirmed until the vendor happened to open the order.
        Mail::fake();
        [$order, $buyer, $vendor] = $this->order();

        $this->pay($order, $buyer, 'cod', 290000);

        Mail::assertSent(
            PaymentDeclared::class,
            fn (PaymentDeclared $mail) => $mail->hasTo($vendor->user->email)
        );
    }

    #[Test]
    public function a_payment_that_needs_no_confirmation_raises_no_alert(): void
    {
        // Card payments are vouched for by the gateway; there is nothing to check.
        Mail::fake();
        [$order] = $this->order();

        Paiement::create([
            'order_id' => $order->id,
            'amount' => 290000,
            'currency' => 'GNF',
            'payment_status' => 'paid',
            'payment_method' => 'stripe',
            'transaction_id' => Order::generateTransactionNumber(),
            'confirmed_at' => now(),
        ]);

        Mail::assertNotSent(PaymentDeclared::class);
    }

    #[Test]
    public function the_alert_template_renders(): void
    {
        [$order, $buyer] = $this->order();
        $this->pay($order, $buyer, 'cod', 290000);

        $html = (new PaymentDeclared($order->paiements()->first()))->render();

        $this->assertStringContainsString($order->order_number, $html);
        $this->assertStringContainsString('/vendor/orders/' . $order->id, $html);
    }

    #[Test]
    public function a_failing_mail_server_does_not_lose_the_payment(): void
    {
        [$order, $buyer] = $this->order();
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP indisponible'));

        $this->pay($order, $buyer, 'cod', 290000);

        // The declaration is what matters; the vendor also sees it in the back office.
        $this->assertDatabaseCount('paiements', 1);
    }

    // --- Pastille du menu vendeur -------------------------------------------

    #[Test]
    public function the_vendor_menu_counts_payments_awaiting_confirmation(): void
    {
        [$order, $buyer, $vendor] = $this->order();

        $this->assertNull($this->badgeSeenBy($vendor->user));

        $this->pay($order, $buyer, 'cod', 290000);

        $this->assertSame('1', $this->badgeSeenBy($vendor->user));
        $this->assertSame('warning', VendorOrderResource::getNavigationBadgeColor());
    }

    #[Test]
    public function the_badge_disappears_once_everything_is_confirmed(): void
    {
        // A badge that never clears stops being read.
        [$order, $buyer, $vendor] = $this->order();
        $this->pay($order, $buyer, 'cod', 290000);
        $this->assertSame('1', $this->badgeSeenBy($vendor->user));

        $this->confirmAll($order, $vendor->user);

        $this->assertNull($this->badgeSeenBy($vendor->user));
    }

    #[Test]
    public function a_vendor_is_not_shown_another_vendors_pending_payments(): void
    {
        [$order, $buyer, $vendor] = $this->order();
        $this->pay($order, $buyer, 'cod', 290000);

        $otherVendor = Vendor::factory()->create();

        $this->assertSame('1', $this->badgeSeenBy($vendor->user));
        $this->assertNull($this->badgeSeenBy($otherVendor->user));
    }

    // --- Référence de transaction -------------------------------------------

    #[Test]
    public function each_payment_gets_its_own_reference(): void
    {
        // Order::generateTransactionNumber() searched for references prefixed "TRANS"
        // but returned one prefixed "INV", so it never found what it had produced: the
        // increment stayed at 1 and every payment of a month got the same reference.
        // 87 real payments ended up sharing 23 references, one used 19 times.
        [$order, $buyer] = $this->order();

        $references = collect(range(1, 3))->map(function () use ($order) {
            $reference = Order::generateTransactionNumber();

            Paiement::create([
                'order_id' => $order->id,
                'amount' => 1,
                'currency' => 'GNF',
                'payment_status' => 'partial',
                'payment_method' => 'cod',
                'transaction_id' => $reference,
            ]);

            return $reference;
        });

        $this->assertCount(3, $references->unique());
    }

    #[Test]
    public function a_payment_reference_cannot_be_mistaken_for_an_order_number(): void
    {
        // Order numbers use the INV prefix. Payment references returning INV too made
        // the two series indistinguishable in the books.
        $this->assertStringStartsWith('TRANS', Order::generateTransactionNumber());
    }

    #[Test]
    public function an_existing_reference_is_never_handed_out_again(): void
    {
        [$order] = $this->order();

        $taken = Order::generateTransactionNumber();
        Paiement::create([
            'order_id' => $order->id,
            'amount' => 1,
            'currency' => 'GNF',
            'payment_status' => 'partial',
            'payment_method' => 'cod',
            'transaction_id' => $taken,
        ]);

        $this->assertNotSame($taken, Order::generateTransactionNumber());
    }

    // --- L'étiquette de devise ----------------------------------------------

    #[Test]
    public function the_day_total_is_shown_in_the_vendors_currency(): void
    {
        // The footer hardcoded 'USD', so a 290 000 GNF order read "$290,000.00" — the
        // same figure with a dollar sign, about 8 000 times its real value.
        [$order, $buyer] = $this->order();

        $component = new SuccessPage();
        $component->orders = Order::with('vendor.currency')->whereKey($order->id)->get();

        $method = new \ReflectionMethod($component, 'todayTotal');
        $method->setAccessible(true);
        [$total, $currency] = $method->invoke($component);

        $this->assertSame(290000.0, $total);
        $this->assertSame('GNF', $currency);
    }

    #[Test]
    public function orders_in_different_currencies_are_totalled_in_usd(): void
    {
        // Several buyers already have same-day orders from vendors billing in different
        // currencies; adding the raw totals together would be meaningless.
        [$gnfOrder, $buyer] = $this->order(290000, 'GNF', 0.00012);

        $usd = Currency::factory()->create(['code' => 'USD', 'rate_to_usd' => 1]);
        $usdVendor = Vendor::factory()->create(['currency_id' => $usd->id]);
        Order::factory()->create([
            'user_id' => $buyer->id,
            'vendor_id' => $usdVendor->id,
            'grand_total' => 100,
            'grand_total_usd' => 100,
        ]);

        $component = new SuccessPage();
        $component->orders = Order::with('vendor.currency')->where('user_id', $buyer->id)->get();

        $method = new \ReflectionMethod($component, 'todayTotal');
        $method->setAccessible(true);
        [$total, $currency] = $method->invoke($component);

        $this->assertSame('USD', $currency);
        // 290 000 GNF ≈ 34,80 $ + 100 $
        $this->assertEqualsWithDelta(134.8, $total, 0.01);
    }
}
