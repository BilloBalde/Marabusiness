<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\OrderResource\Pages\EditOrder as AdminEditOrder;
use App\Filament\Resources\OrderResource\RelationManagers\PaiementsRelationManager;
use App\Filament\Vendor\Resources\OrderResource as VendorOrderResource;
use App\Filament\Vendor\Resources\OrderResource\Pages\EditOrder;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\FinancialTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Paiement;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorProduct;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The vendor panel's order page extends the admin's and inherited its whole
 * edit form. A vendor could, on an order the customer had already agreed to:
 *   - set payment_status to "paid" — Order's saved() hook then flips every
 *     financial transaction of the order to PROCESSED, i.e. commission and
 *     vendor revenue booked for money nobody paid;
 *   - move it to another customer or shop, change quantities, unit prices and
 *     the shipping cost (the select boxes even preloaded every customer);
 *   - record a "confirmed" payment on an order the payment gateway collects.
 * Moving an order along (status, carrier, notes) stays theirs to do.
 */
class VendorOrderTamperingTest extends TestCase
{
    use RefreshDatabase;

    private function scenario(string $paymentMethod = 'stripe'): array
    {
        Role::findOrCreate('vendor', 'web');
        Role::findOrCreate('customer', 'web');
        Role::findOrCreate('admin', 'web');

        $currency = Currency::factory()->create(['code' => 'GNF', 'rate_to_usd' => 0.00012]);

        $vendorUser = User::factory()->create();
        $vendorUser->assignRole('vendor');
        $vendor = Vendor::factory()->create([
            'user_id' => $vendorUser->id,
            'currency_id' => $currency->id,
            'is_active' => true,
            'approved_at' => now(),
        ]);

        $buyer = User::factory()->create();
        $buyer->assignRole('customer');

        $product = Product::create([
            'name' => 'Article ' . uniqid(),
            'slug' => 'article-' . uniqid(),
            'category_id' => Category::create(['name' => 'C' . uniqid(), 'slug' => 'c-' . uniqid()])->id,
            'brand_id' => Brand::create(['name' => 'B' . uniqid(), 'slug' => 'b-' . uniqid()])->id,
            'is_active' => true,
            'images' => [],
        ]);
        VendorProduct::create([
            'vendor_id' => $vendor->id, 'product_id' => $product->id,
            'price' => 100000, 'stock' => 10, 'is_active' => true,
        ]);

        $order = Order::create([
            'user_id' => $buyer->id,
            'vendor_id' => $vendor->id,
            'order_number' => 'TAMPER-' . uniqid(),
            'grand_total' => 205000,
            'shipping_amount' => 5000,
            'total_paid' => 0,
            'total_remaining' => 205000,
            'payment_method' => $paymentMethod,
            'payment_status' => 'pending',
            'status' => 'new',
            'rate_to_usd' => 0.00012,
        ]);
        OrderItem::create([
            'order_id' => $order->id, 'product_id' => $product->id,
            'quantity' => 2, 'unit_amount' => 100000, 'total_amount' => 200000,
        ]);

        return compact('vendorUser', 'vendor', 'buyer', 'order', 'product');
    }

    private function editAsVendor(array $s)
    {
        $this->actingAs($s['vendorUser']);
        Filament::setCurrentPanel(Filament::getPanel('vendor'));

        return Livewire::test(EditOrder::class, ['record' => $s['order']->getKey()]);
    }

    #[Test]
    public function a_vendor_cannot_mark_an_order_paid_by_editing_its_status(): void
    {
        $s = $this->scenario();
        FinancialTransaction::create([
            'order_id' => $s['order']->id, 'vendor_id' => $s['vendor']->id,
            'transaction_type' => FinancialTransaction::TYPE_COMMISSION,
            'amount' => 10000, 'net_amount' => 10000, 'description' => 'Commission',
            'status' => FinancialTransaction::STATUS_PENDING,
        ]);

        $this->editAsVendor($s)
            ->fillForm(['payment_status' => 'paid'])
            ->call('save');

        $this->assertSame('pending', $s['order']->fresh()->payment_status);
        $this->assertSame(0, FinancialTransaction::where('order_id', $s['order']->id)
            ->where('status', FinancialTransaction::STATUS_PROCESSED)->count());
    }

    #[Test]
    public function a_vendor_cannot_hand_the_order_to_another_customer(): void
    {
        $s = $this->scenario();
        $stranger = User::factory()->create();

        $this->editAsVendor($s)
            ->fillForm(['user_id' => $stranger->id])
            ->call('save');

        $this->assertSame($s['buyer']->id, $s['order']->fresh()->user_id);
    }

    #[Test]
    public function a_vendor_cannot_change_the_shipping_cost_the_customer_accepted(): void
    {
        $s = $this->scenario();

        $this->editAsVendor($s)
            ->fillForm(['shipping_amount' => 0])
            ->call('save');

        $this->assertEquals(5000, $s['order']->fresh()->shipping_amount);
    }

    #[Test]
    public function a_vendor_cannot_reprice_or_requantify_the_items(): void
    {
        $s = $this->scenario();

        $component = $this->editAsVendor($s);
        $items = $component->get('data.items');
        foreach ($items as $key => $item) {
            $items[$key]['unit_amount'] = 1;
            $items[$key]['quantity'] = 1;
            $items[$key]['total_amount'] = 1;
        }
        $component->fillForm(['items' => $items])->call('save');

        $item = OrderItem::where('order_id', $s['order']->id)->first();
        $this->assertEquals(100000, $item->unit_amount);
        $this->assertEquals(2, $item->quantity);
    }

    #[Test]
    public function a_vendor_can_still_move_the_order_along(): void
    {
        $s = $this->scenario();

        $this->editAsVendor($s)
            ->fillForm(['status' => 'processing'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('processing', $s['order']->fresh()->status);
    }

    #[Test]
    public function an_admin_can_still_correct_any_field(): void
    {
        $s = $this->scenario();
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(AdminEditOrder::class, ['record' => $s['order']->getKey()])
            ->fillForm(['shipping_amount' => 7000, 'user_id' => $s['buyer']->id])
            ->call('save');

        // Shipping is the platform's to correct; the grand total follows it.
        $order = $s['order']->fresh();
        $this->assertEquals(7000, $order->shipping_amount);
        $this->assertEquals(207000, $order->grand_total);
    }

    #[Test]
    public function a_vendor_cannot_record_a_payment_on_an_order_the_gateway_collects(): void
    {
        $s = $this->scenario(paymentMethod: 'stripe');
        $this->actingAs($s['vendorUser']);
        Filament::setCurrentPanel(Filament::getPanel('vendor'));

        Livewire::test(PaiementsRelationManager::class, [
            'ownerRecord' => $s['order'],
            'pageClass' => EditOrder::class,
        ])->assertTableActionHidden('create');
    }

    #[Test]
    public function a_vendor_can_still_record_cash_on_delivery_money_they_handled(): void
    {
        $s = $this->scenario(paymentMethod: 'cod');
        $this->actingAs($s['vendorUser']);
        Filament::setCurrentPanel(Filament::getPanel('vendor'));

        Livewire::test(PaiementsRelationManager::class, [
            'ownerRecord' => $s['order'],
            'pageClass' => EditOrder::class,
        ])->assertTableActionVisible('create');
    }

    #[Test]
    public function a_vendor_cannot_edit_or_delete_a_payment_the_gateway_verified(): void
    {
        $s = $this->scenario(paymentMethod: 'stripe');
        $stripe = Paiement::create([
            'order_id' => $s['order']->id, 'payment_method' => 'stripe', 'payment_status' => 'paid',
            'currency' => 'GNF', 'amount' => 205000, 'transaction_id' => 'stripe_cs_x',
            'confirmed_at' => now(),
        ]);
        $this->actingAs($s['vendorUser']);
        Filament::setCurrentPanel(Filament::getPanel('vendor'));

        Livewire::test(PaiementsRelationManager::class, [
            'ownerRecord' => $s['order'],
            'pageClass' => EditOrder::class,
        ])
            ->assertTableActionHidden('edit', $stripe)
            ->assertTableActionHidden('delete', $stripe);
    }

    #[Test]
    public function a_vendor_can_still_edit_and_confirm_an_offline_payment(): void
    {
        $s = $this->scenario(paymentMethod: 'om');
        $declared = Paiement::create([
            'order_id' => $s['order']->id, 'payment_method' => 'om', 'payment_status' => 'paid',
            'currency' => 'GNF', 'amount' => 205000, 'transaction_id' => 'OM-1',
        ]);
        $this->actingAs($s['vendorUser']);
        Filament::setCurrentPanel(Filament::getPanel('vendor'));

        Livewire::test(PaiementsRelationManager::class, [
            'ownerRecord' => $s['order'],
            'pageClass' => EditOrder::class,
        ])
            ->assertTableActionVisible('edit', $declared)
            ->assertTableActionVisible('confirmerReception', $declared);
    }

    #[Test]
    public function a_vendor_cannot_delete_an_order(): void
    {
        $s = $this->scenario();
        $this->actingAs($s['vendorUser']);
        Filament::setCurrentPanel(Filament::getPanel('vendor'));

        $this->assertFalse(VendorOrderResource::canDelete($s['order']));
        $this->assertFalse(VendorOrderResource::canDeleteAny());
    }

    #[Test]
    public function the_vendor_form_does_not_list_every_customer(): void
    {
        $s = $this->scenario();
        $stranger = User::factory()->create(['name' => 'Cliente Sans Rapport']);

        $this->editAsVendor($s)->assertDontSee($stranger->name);
    }
}
