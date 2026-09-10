<?php

namespace Tests\Feature\Integrity;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Stock is checked when an item goes into the cart and never again. The cart
 * cookie lives thirty days (CartManagement: cookie(..., 60 * 24 * 30)), so the
 * gap between that check and the order being placed is as long as a month, and
 * CheckoutController::updateStock() takes whatever quantity the cart holds:
 *
 *     $vp->stock = max(0, $vp->stock - $item['quantity']);
 *
 * The max(0, ...) is why nothing looks wrong afterwards. Selling three units of
 * an item with one in the warehouse writes 0, not -2, so the oversell leaves no
 * trace in the data at all — the shortfall is discovered when someone tries to
 * ship it.
 *
 * These tests pin what the code does today. Where the current behaviour is the
 * defect, the test says so in its name rather than asserting the bug is correct.
 */
class StockIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function listing(int $stock): VendorProduct
    {
        $currency = Currency::factory()->create(['code' => 'GNF', 'rate_to_usd' => 0.00012]);
        $vendor = Vendor::factory()->create(['is_active' => true, 'currency_id' => $currency->id]);

        $product = Product::create([
            'category_id' => Category::create(['name' => 'C' . uniqid(), 'slug' => 'c-' . uniqid(), 'is_active' => true])->id,
            'brand_id' => Brand::create(['name' => 'B' . uniqid(), 'slug' => 'b-' . uniqid()])->id,
            'name' => 'Scarce Item',
            'slug' => 'scarce-' . uniqid(),
            'is_active' => true,
        ]);

        return VendorProduct::create([
            'vendor_id' => $vendor->id,
            'product_id' => $product->id,
            'price' => 50000,
            'stock' => $stock,
            'is_active' => true,
        ]);
    }

    private function addToCart(VendorProduct $vendorProduct, int $quantity)
    {
        return $this->postJson('/api/v1/cart/add', [
            'vendor_product_id' => $vendorProduct->id,
            'quantity' => $quantity,
        ]);
    }

    #[Test]
    public function asking_for_more_than_exists_is_capped_to_what_exists(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $listing = $this->listing(3);

        $this->addToCart($listing, 10);

        $cart = \App\Helpers\CartManagement::getCartItemsFromCookie();
        $this->assertNotEmpty($cart, 'the item should be in the cart');
        $this->assertSame(3, (int) $cart[0]['quantity'], 'quantity should be capped at the 3 in stock');
    }

    #[Test]
    public function an_item_with_zero_stock_is_refused(): void
    {
        // Every cap was written `if ($availableStock > 0 && ...)`, so it was
        // skipped at exactly one value: zero. Five units of an item with none in
        // the warehouse went into the cart in full.
        Sanctum::actingAs(User::factory()->create());
        $listing = $this->listing(0);

        $this->addToCart($listing, 5)->assertStatus(400);

        $this->assertEmpty(
            \App\Helpers\CartManagement::getCartItemsFromCookie(),
            'an out-of-stock item must not reach the cart'
        );
    }

    #[Test]
    public function a_cart_filled_before_the_item_sold_out_keeps_its_old_quantity(): void
    {
        // The cart itself is not re-priced or re-counted when stock moves; that is
        // expected, and is why the order path has to check. This pins the premise
        // of the test below rather than asserting a defect.
        Sanctum::actingAs(User::factory()->create());
        $listing = $this->listing(5);

        $this->addToCart($listing, 5);
        $listing->update(['stock' => 0]);

        $cart = \App\Helpers\CartManagement::getCartItemsFromCookie();
        $this->assertSame(5, (int) $cart[0]['quantity']);
    }

    private function placeOrder(VendorProduct $listing)
    {
        return $this->postJson('/api/v1/checkout/place-order', [
            'selected_ids' => [$listing->id],
            'address' => [
                'first_name' => 'A', 'last_name' => 'B', 'city' => 'Conakry',
                'phone' => '600000000', 'street_address' => 'Rue 1',
                'state' => 'Conakry', 'country' => 'Guinea',
            ],
            'payment_method' => 'cod',
            'shipping_carrier' => 'standard',
        ]);
    }

    #[Test]
    public function an_order_for_more_than_is_left_is_refused_rather_than_silently_clamped(): void
    {
        // updateStock() wrote max(0, stock - quantity), so an order for five units
        // of an item with one left stored 0 and looked perfectly healthy
        // afterwards. The shortfall surfaced only when someone tried to ship it.
        Sanctum::actingAs(User::factory()->create());
        $listing = $this->listing(5);

        $this->addToCart($listing, 5);
        $listing->update(['stock' => 1]);

        $this->placeOrder($listing)->assertStatus(409);

        $this->assertSame(1, (int) $listing->fresh()->stock, 'stock must be untouched by a refused order');
        $this->assertDatabaseCount('orders', 0);
    }

    #[Test]
    public function an_order_within_the_available_stock_still_goes_through(): void
    {
        // The guard must refuse only genuine shortfalls; an ordinary purchase has
        // to keep working, and the stock has to come down by what was bought.
        Sanctum::actingAs(User::factory()->create());
        $listing = $this->listing(5);

        $this->addToCart($listing, 2);

        $response = $this->placeOrder($listing);

        $this->assertNotSame(409, $response->getStatusCode(), 'a purchase within stock must not be refused');
        $this->assertSame(3, (int) $listing->fresh()->stock, '5 in stock minus 2 bought');
    }
}
