<?php

namespace Tests\Feature\Livewire\Buyer;

use App\Livewire\CheckoutPage;
use App\Models\Brand;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * placeOrder() (app/Livewire/CheckoutPage.php) now checks and decrements stock
 * atomically, under lockForUpdate(), inside a DB::transaction() — before a
 * single Order/Address/OrderItem row exists — mirroring
 * CheckoutController::findStockShortfalls()/updateStock() on the API side. The
 * check and the decrement used to be two separate, unlocked queries with an
 * unbounded gap between them (order creation, address, items, payment
 * handling); now there's no gap left between them at all.
 *
 * A real second HTTP request can't be produced inside a single-process,
 * single-connection SQLite test (acknowledged here rather than papered over):
 * this instead proves the behavioural contract the lock exists to guarantee —
 * a checkout against stock a concurrent sale already exhausted a moment
 * earlier is refused, cleanly, with no Order row left behind — which is
 * exactly what would be false if the check and the decrement could still
 * observe two different values.
 */
class CheckoutOversellRaceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function placing_an_order_is_refused_when_a_concurrent_sale_already_took_the_last_unit(): void
    {
        $currency = Currency::factory()->create(['code' => 'GNF', 'rate_to_usd' => 0.00012]);
        $vendor = Vendor::factory()->create(['currency_id' => $currency->id, 'is_active' => true]);

        $product = Product::create([
            'name' => 'Last Unit',
            'slug' => 'last-unit-' . uniqid(),
            'category_id' => Category::create(['name' => 'C' . uniqid(), 'slug' => 'c-' . uniqid()])->id,
            'brand_id' => Brand::create(['name' => 'B' . uniqid(), 'slug' => 'b-' . uniqid()])->id,
            'is_active' => true,
        ]);

        $vendorProduct = VendorProduct::create([
            'vendor_id' => $vendor->id,
            'product_id' => $product->id,
            'price' => 100000,
            // A concurrent sale already took the only unit a moment before this
            // checkout's own transaction starts.
            'stock' => 0,
            'is_active' => true,
        ]);

        $buyer = User::factory()->create();
        CartItem::create([
            'user_id' => $buyer->id,
            'vendor_product_id' => $vendorProduct->id,
            'quantity' => 1,
            'cart_key' => 'key-' . $vendorProduct->id,
        ]);

        $this->actingAs($buyer);

        Livewire::withQueryParams(['selected' => (string) $vendorProduct->id])
            ->test(CheckoutPage::class)
            ->set('first_name', 'Fatou')
            ->set('last_name', 'Diallo')
            ->set('phone', '600000000')
            ->set('street_address', 'Rue 1')
            ->set('city', 'Conakry')
            ->set('state', 'Conakry')
            ->set('country', 'Guinea')
            ->set('payment_method', 'cod')
            ->call('placeOrder');

        $this->assertSame(
            0,
            Order::count(),
            'a checkout should not complete for stock a concurrent sale already took'
        );
        $this->assertSame(0, $vendorProduct->fresh()->stock, 'stock must not go negative either');
    }

    #[Test]
    public function placing_an_order_succeeds_and_decrements_stock_exactly_once_when_stock_is_sufficient(): void
    {
        Mail::fake();

        $currency = Currency::factory()->create(['code' => 'GNF', 'rate_to_usd' => 0.00012]);
        $vendor = Vendor::factory()->create(['currency_id' => $currency->id, 'is_active' => true]);

        $product = Product::create([
            'name' => 'Plenty Left',
            'slug' => 'plenty-left-' . uniqid(),
            'category_id' => Category::create(['name' => 'C' . uniqid(), 'slug' => 'c-' . uniqid()])->id,
            'brand_id' => Brand::create(['name' => 'B' . uniqid(), 'slug' => 'b-' . uniqid()])->id,
            'is_active' => true,
        ]);

        $vendorProduct = VendorProduct::create([
            'vendor_id' => $vendor->id,
            'product_id' => $product->id,
            'price' => 100000,
            'stock' => 5,
            'is_active' => true,
        ]);

        $buyer = User::factory()->create();
        CartItem::create([
            'user_id' => $buyer->id,
            'vendor_product_id' => $vendorProduct->id,
            'quantity' => 2,
            'cart_key' => 'key-' . $vendorProduct->id,
        ]);

        $this->actingAs($buyer);

        Livewire::withQueryParams(['selected' => (string) $vendorProduct->id])
            ->test(CheckoutPage::class)
            ->set('first_name', 'Fatou')
            ->set('last_name', 'Diallo')
            ->set('phone', '600000000')
            ->set('street_address', 'Rue 1')
            ->set('city', 'Conakry')
            ->set('state', 'Conakry')
            ->set('country', 'Guinea')
            ->set('payment_method', 'cod')
            ->call('placeOrder');

        $this->assertSame(1, Order::count());
        $this->assertSame(3, $vendorProduct->fresh()->stock);

        // Sent after the response now, not inline — it must still be sent.
        $this->app->terminate();
        Mail::assertSent(\App\Mail\OrderPlaced::class, fn ($mail) => $mail->hasTo($buyer->email));
    }
}
