<?php

namespace Tests\Feature\Catalogue;

use App\Helpers\CartManagement;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * A shop that is switched off (banned, or still awaiting approval) had its public page,
 * its reviews and its products listed by ID on the web and in the API, and the API's
 * "stats" endpoint — open to anyone, and unused by the mobile app — published each
 * vendor's order count and total sales. The cart badge also kept counting lines the cart
 * page no longer showed.
 */
class InactiveVendorsAreNotPublicTest extends TestCase
{
    use RefreshDatabase;

    private function shop(bool $active): Vendor
    {
        return Vendor::factory()->create([
            'is_active' => $active,
            'currency_id' => Currency::factory()->create(['rate_to_usd' => 1])->id,
        ]);
    }

    private function offer(Vendor $vendor, array $state = []): VendorProduct
    {
        $product = Product::create([
            'name' => 'Article ' . uniqid(),
            'slug' => 'article-' . uniqid(),
            'category_id' => Category::create(['name' => 'C' . uniqid(), 'slug' => 'c-' . uniqid()])->id,
            'brand_id' => Brand::create(['name' => 'B' . uniqid(), 'slug' => 'b-' . uniqid()])->id,
            'is_active' => true,
            'images' => [],
        ]);

        return VendorProduct::create(array_merge(
            ['vendor_id' => $vendor->id, 'product_id' => $product->id, 'price' => 100, 'stock' => 5, 'is_active' => true],
            $state
        ));
    }

    #[Test]
    public function an_active_shops_page_opens(): void
    {
        $vendor = $this->shop(active: true);

        $this->get('/vendor/' . $vendor->slug)->assertOk();
    }

    #[Test]
    public function an_inactive_shops_page_is_not_found(): void
    {
        $vendor = $this->shop(active: false);

        $this->get('/vendor/' . $vendor->slug)->assertNotFound();
    }

    #[Test]
    public function the_api_hides_an_inactive_shop_everywhere(): void
    {
        $vendor = $this->shop(active: false);
        $this->offer($vendor);

        $this->getJson('/api/v1/vendors/' . $vendor->slug)->assertNotFound();
        $this->getJson('/api/v1/vendors/' . $vendor->id . '/products')->assertStatus(404);
        $this->getJson('/api/v1/vendors/' . $vendor->id . '/reviews')->assertStatus(404);
        $this->getJson('/api/v1/vendors/' . $vendor->id . '/stats')->assertStatus(404);
    }

    #[Test]
    public function the_public_stats_no_longer_publish_a_shops_sales(): void
    {
        $vendor = $this->shop(active: true);
        $this->offer($vendor);
        Order::create([
            'user_id' => User::factory()->create()->id, 'vendor_id' => $vendor->id,
            'order_number' => 'STATS-1', 'grand_total' => 987654, 'total_paid' => 0,
            'total_remaining' => 987654, 'payment_method' => 'cod', 'payment_status' => 'pending',
            'status' => 'new', 'rate_to_usd' => 1,
        ]);

        $response = $this->getJson('/api/v1/vendors/' . $vendor->id . '/stats')->assertOk();

        $response->assertJsonMissingPath('data.total_sales');
        $response->assertJsonMissingPath('data.total_orders');
        $this->assertStringNotContainsString('987654', $response->getContent());
        $response->assertJsonPath('data.total_products', 1);
    }

    #[Test]
    public function the_shop_product_count_ignores_offers_switched_off(): void
    {
        $vendor = $this->shop(active: true);
        $this->offer($vendor);
        $this->offer($vendor, ['is_active' => false]);

        $this->getJson('/api/v1/vendors/' . $vendor->id . '/stats')->assertJsonPath('data.total_products', 1);
    }

    #[Test]
    public function the_cart_badge_only_counts_lines_the_cart_can_still_show(): void
    {
        $buyer = User::factory()->create();
        $this->actingAs($buyer);

        $kept = $this->offer($this->shop(active: true));
        $withdrawn = $this->offer($this->shop(active: true));

        CartManagement::addItemToCart($kept->id, 2);
        CartManagement::addItemToCart($withdrawn->id, 3);
        $this->assertSame(5, CartManagement::getCartCount());

        $withdrawn->update(['is_active' => false]); // switched off after it went into the cart

        $this->assertSame(2, CartManagement::getCartCount());
        $this->assertCount(1, CartManagement::getCartItemsFromCookie());
    }
}
