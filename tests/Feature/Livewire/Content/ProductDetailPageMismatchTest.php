<?php

namespace Tests\Feature\Livewire\Content;

use App\Livewire\ProductDetailPage;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorProduct;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The product page route is /products/{slug}/{vendor_product_id} (routes/web.php:58) and
 * ProductDetailPage::mount() (app/Livewire/ProductDetailPage.php:53-68) fetches the two
 * route segments independently:
 *
 *   $this->product = Product::where('slug', $slug)->firstOrFail();
 *   $this->vendorProduct = VendorProduct::with([...])->findOrFail($vendor_product_id);
 *
 * Nothing ever checks that $vendorProduct->product_id === $product->id. Both segments are
 * plain, independently-editable route parameters, so a URL can pair one product's slug with
 * a totally different product's vendor listing. The page then mixes the two: $product
 * supplies the title/images/description shown to the shopper, while $vendorProduct supplies
 * the price/stock/vendor and — critically — the id that addToCart() actually adds to the
 * cart. Any vendor can build such a link to their own vendor_product_id under a trusted
 * product's slug.
 */
class ProductDetailPageMismatchTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(string $tag): Product
    {
        $category = Category::create(['name' => "Categorie $tag", 'slug' => 'cat-' . strtolower($tag) . '-' . uniqid()]);
        $brand = Brand::create(['name' => "Marque $tag", 'slug' => 'marque-' . strtolower($tag) . '-' . uniqid()]);

        return Product::create([
            'name' => "Produit $tag",
            'slug' => 'produit-' . strtolower($tag) . '-' . uniqid(),
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'is_active' => true,
        ]);
    }

    private function makeListing(Product $product, float $price, int $stock = 5): VendorProduct
    {
        return VendorProduct::create([
            'vendor_id' => Vendor::factory()->create()->id,
            'product_id' => $product->id,
            'price' => $price,
            'stock' => $stock,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function mounting_rejects_a_vendor_listing_that_does_not_belong_to_the_urls_product(): void
    {
        $productA = $this->makeProduct('A'); // the product named in the URL slug
        $productB = $this->makeProduct('B'); // an unrelated catalogue item
        $listingForB = $this->makeListing($productB, 999.00);

        // A correctly-scoped lookup 404s when the two route segments disagree, the same way
        // firstOrFail()/findOrFail() already 404 on an unknown slug or id individually.
        $this->expectException(ModelNotFoundException::class);

        Livewire::test(ProductDetailPage::class, [
            'slug' => $productA->slug,
            'vendor_product_id' => $listingForB->id,
        ]);
    }

    #[Test]
    public function add_to_cart_never_adds_a_listing_for_a_different_product_than_the_page_shows(): void
    {
        $productA = $this->makeProduct('A');
        $productB = $this->makeProduct('B');
        $listingForB = $this->makeListing($productB, 999.00);

        $buyer = User::factory()->create();

        // Shopper is on what looks like product A's page (A's slug in the URL, A's title and
        // images on screen) but the listing id in that same URL belongs to product B.
        try {
            Livewire::actingAs($buyer)
                ->test(ProductDetailPage::class, [
                    'slug' => $productA->slug,
                    'vendor_product_id' => $listingForB->id,
                ])
                ->set('quantity', 1)
                ->call('addToCart');
        } catch (ModelNotFoundException) {
            // A fix that 404s at mount() (see previous test) also satisfies this one:
            // nothing reaches the cart either way.
        }

        // Whatever happens, the buyer must never end up with product B's listing in their
        // cart while believing they ordered product A.
        $this->assertDatabaseMissing('cart_items', [
            'user_id' => $buyer->id,
            'vendor_product_id' => $listingForB->id,
        ]);
    }
}
