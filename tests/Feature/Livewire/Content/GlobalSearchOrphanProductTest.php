<?php

namespace Tests\Feature\Livewire\Content;

use App\Livewire\GlobalSearch;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Vendor;
use App\Models\VendorProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * GlobalSearch::updatedQuery() (app/Livewire/GlobalSearch.php:13-24) only filters on
 * Product::is_active, unlike every other listing surface in the app: ProductsPage scopes to
 * VendorProduct rows via whereHas('product', ...) (ProductsPage.php:107-108) and HomePage
 * drops any product whose $product->vendors is empty (HomePage.php:102-103,
 * ->filter(fn($p) => $p->has_vendor) at 217/225). GlobalSearch has no equivalent, so a
 * catalogue product with zero vendor listings (never listed, or delisted by every vendor)
 * still surfaces in the search dropdown.
 *
 * The dropdown then breaks on exactly that result: resources/views/livewire/global-search.blade.php:29
 * builds the link as $product->vendorProducts[0]->id with no existence check and no eager
 * load (an N+1 on top of it, one query per result). Confirmed by running this test: it is
 * not a silently-dead link, it's a fatal "Undefined array key 0" thrown out of the blade
 * view -- the whole page hosting the search bar 500s the moment a shopper's query matches a
 * catalogue product with no vendor listing.
 */
class GlobalSearchOrphanProductTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function search_results_do_not_include_a_product_with_no_vendor_listing(): void
    {
        $category = Category::create(['name' => 'Cat', 'slug' => 'cat-' . uniqid()]);
        $brand = Brand::create(['name' => 'Brand', 'slug' => 'brand-' . uniqid()]);

        // Catalogue-active, but no vendor has ever listed it -- nothing to actually buy.
        $orphan = Product::create([
            'name' => 'Gadget Orphelin Introuvable',
            'slug' => 'gadget-orphelin-' . uniqid(),
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'is_active' => true,
        ]);

        // A normal, purchasable product with the same search term, for contrast.
        $sellable = Product::create([
            'name' => 'Gadget Orphelin Vendu',
            'slug' => 'gadget-vendu-' . uniqid(),
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'is_active' => true,
        ]);
        VendorProduct::create([
            'vendor_id' => Vendor::factory()->create()->id,
            'product_id' => $sellable->id,
            'price' => 15,
            'stock' => 5,
            'is_active' => true,
        ]);

        $results = Livewire::test(GlobalSearch::class)
            ->set('query', 'Gadget Orphelin')
            ->get('results');

        $resultIds = collect($results)->pluck('id')->all();

        $this->assertNotContains(
            $orphan->id,
            $resultIds,
            'GlobalSearch returned a product with no vendor listing at all -- the search '
            . 'dropdown link for it (vendorProducts[0]) has nothing to point to.'
        );
        $this->assertContains($sellable->id, $resultIds);
    }
}
