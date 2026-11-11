<?php

namespace Tests\Feature\Catalogue;

use App\Helpers\CartManagement;
use App\Livewire\HomePage;
use App\Livewire\ProductsPage;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * An offer a vendor (or an admin) has switched off, or one that belongs to a
 * vendor who is not active, must not be sellable. The catalogue's
 * cheapestPerProduct() scope only hides an offer when a cheaper ACTIVE rival
 * exists — it never checks the offer's own is_active, nor its vendor's.
 */
class InactiveOffersAreNotSoldTest extends TestCase
{
    use RefreshDatabase;

    private function offer(array $vendorState, array $offerState): array
    {
        $vendor = Vendor::factory()->create(array_merge(
            ['currency_id' => Currency::factory()->create(['rate_to_usd' => 1])->id],
            $vendorState
        ));

        $product = Product::create([
            'name' => 'Sellable ' . uniqid(),
            'slug' => 'sellable-' . uniqid(),
            'category_id' => Category::create(['name' => 'C' . uniqid(), 'slug' => 'c-' . uniqid(), 'is_active' => true])->id,
            'brand_id' => Brand::create(['name' => 'B' . uniqid(), 'slug' => 'b-' . uniqid()])->id,
            'is_active' => true,
            'is_featured' => true,
            'images' => [],
        ]);

        $vp = VendorProduct::create(array_merge(
            ['vendor_id' => $vendor->id, 'product_id' => $product->id, 'price' => 100, 'stock' => 10, 'is_active' => true],
            $offerState
        ));

        return [$product, $vp, $vendor];
    }

    #[Test]
    public function an_active_offer_from_an_active_vendor_is_listed_the_baseline(): void
    {
        [$product] = $this->offer(['is_active' => true], ['is_active' => true]);

        Livewire::test(ProductsPage::class)->assertSee($product->name);
    }

    #[Test]
    public function an_offer_switched_off_is_not_listed(): void
    {
        [$product] = $this->offer(['is_active' => true], ['is_active' => false]);

        Livewire::test(ProductsPage::class)->assertDontSee($product->name);
    }

    #[Test]
    public function an_offer_from_an_inactive_vendor_is_not_listed(): void
    {
        [$product] = $this->offer(['is_active' => false], ['is_active' => true]);

        Livewire::test(ProductsPage::class)->assertDontSee($product->name);
    }

    #[Test]
    public function an_offer_switched_off_cannot_be_opened(): void
    {
        [$product, $vp] = $this->offer(['is_active' => true], ['is_active' => false]);

        $this->get('/products/' . $product->slug . '/' . $vp->id)->assertNotFound();
    }

    #[Test]
    public function an_offer_from_an_inactive_vendor_cannot_be_opened(): void
    {
        [$product, $vp] = $this->offer(['is_active' => false], ['is_active' => true]);

        $this->get('/products/' . $product->slug . '/' . $vp->id)->assertNotFound();
    }

    #[Test]
    public function an_offer_from_an_inactive_vendor_is_not_on_the_home_page(): void
    {
        [$product] = $this->offer(['is_active' => false], ['is_active' => true]);

        Livewire::test(HomePage::class)->assertDontSee($product->name);
    }

    #[Test]
    public function an_offer_switched_off_cannot_be_added_to_the_cart(): void
    {
        [, $vp] = $this->offer(['is_active' => true], ['is_active' => false]);
        $buyer = User::factory()->create();
        $this->actingAs($buyer);

        try {
            CartManagement::addItemToCart($vp->id, 1);
        } catch (\Throwable $e) {
            // refusing by exception is also a refusal
        }

        $this->assertDatabaseMissing('cart_items', ['user_id' => $buyer->id, 'vendor_product_id' => $vp->id]);
    }

    #[Test]
    public function an_offer_from_an_inactive_vendor_cannot_be_added_to_the_cart(): void
    {
        [, $vp] = $this->offer(['is_active' => false], ['is_active' => true]);
        $buyer = User::factory()->create();
        $this->actingAs($buyer);

        try {
            CartManagement::addItemToCart($vp->id, 1);
        } catch (\Throwable $e) {
        }

        $this->assertDatabaseMissing('cart_items', ['user_id' => $buyer->id, 'vendor_product_id' => $vp->id]);
    }
}
