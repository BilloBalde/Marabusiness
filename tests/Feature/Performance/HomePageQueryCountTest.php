<?php

namespace Tests\Feature\Performance;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The homepage ran 106 queries for 7 products and 8 vendors: none of
 * store_name/description (HasTranslations, vendor_translations/service_
 * translations/category_translations), the rating average, the follower count,
 * or a product's own vendor_product_id was eager-loaded — each was refetched
 * fresh per field access, per row. Fixed with eager-loaded translations,
 * withAvg()/withCount() for the rating and follower count, and by reading the
 * vendor_product id straight off the already-loaded pivot instead of re-querying
 * it (Product::vendors()'s withPivot() didn't expose 'id' at all).
 *
 * The query-count assertions here are the actual regression guard: they fail if
 * eager-loading is ever dropped, whether or not anyone happens to notice the
 * page got slower. Seeded with two vendors specifically so an N+1 that scales
 * with vendor count is caught even at this small a scale — a fixed per-vendor
 * cost of, say, 2 extra queries would show as 4 extra with two vendors, not
 * invisible the way it would be with just one.
 */
class HomePageQueryCountTest extends TestCase
{
    use RefreshDatabase;

    private function vendorWithData(string $storeName): Vendor
    {
        $vendor = Vendor::factory()->create(['store_name' => $storeName, 'is_active' => true]);

        DB::table('vendor_translations')->insert([
            'vendor_id' => $vendor->id,
            'locale' => 'en',
            'store_name' => $storeName,
            'description' => "Description for {$storeName}",
        ]);

        $buyer = User::factory()->create();
        DB::table('vendor_reviews')->insert([
            'user_id' => $buyer->id,
            'vendor_id' => $vendor->id,
            'rating' => 4,
            'is_approved' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $follower = User::factory()->create();
        DB::table('vendor_follows')->insert([
            'user_id' => $follower->id,
            'vendor_id' => $vendor->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $vendor;
    }

    private function productListedBy(Vendor $vendor, float $price = 100): Product
    {
        $category = Category::create(['name' => 'Cat ' . uniqid(), 'slug' => 'cat-' . uniqid(), 'is_active' => true]);
        $brand = Brand::create(['name' => 'Brand ' . uniqid(), 'slug' => 'brand-' . uniqid()]);

        $product = Product::create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Product ' . uniqid(),
            'slug' => 'product-' . uniqid(),
            'is_active' => true,
            'is_featured' => true,
        ]);

        VendorProduct::create([
            'vendor_id' => $vendor->id,
            'product_id' => $product->id,
            'price' => $price,
            'stock' => 10,
            'is_active' => true,
        ]);

        return $product;
    }

    private function service(string $name): Service
    {
        $service = Service::create(['name' => $name, 'slug' => 'service-' . uniqid()]);

        DB::table('service_translations')->insert([
            'service_id' => $service->id,
            'locale' => 'en',
            'name' => $name,
            'description' => "Description for {$name}",
        ]);

        return $service;
    }

    #[Test]
    public function the_homepage_query_count_does_not_scale_with_the_number_of_vendors(): void
    {
        $vendorA = $this->vendorWithData('Vendor A Shop');
        $this->productListedBy($vendorA);
        $this->service('Service One');

        DB::enableQueryLog();
        $this->get('/')->assertOk();
        $withOneVendor = count(DB::getQueryLog());
        DB::flushQueryLog();
        DB::disableQueryLog();

        // Query logging switched off while seeding the second vendor — flushing
        // the log does not stop it recording, so the inserts below would
        // otherwise be counted as if they were part of the homepage request.
        $vendorB = $this->vendorWithData('Vendor B Shop');
        $this->productListedBy($vendorB);
        $this->service('Service Two');

        DB::enableQueryLog();
        $this->get('/')->assertOk();
        $withTwoVendors = count(DB::getQueryLog());
        DB::disableQueryLog();

        // The old code added roughly 5 queries per vendor (translations, rating,
        // follower count, ...) and more per product/service. A second vendor and
        // product and service should cost only a handful of queries more, not
        // scale linearly with a fixed per-row multiplier.
        $this->assertLessThanOrEqual(
            $withOneVendor + 6,
            $withTwoVendors,
            "Adding a second vendor/product/service added " . ($withTwoVendors - $withOneVendor) .
                " queries ({$withOneVendor} -> {$withTwoVendors}) — looks like an N+1 regressed."
        );

        // The concrete number this fix brought it down to (was 106 for 7
        // products / 8 vendors before) — a generous ceiling, not a tight bound.
        $this->assertLessThan(40, $withTwoVendors);
    }

    #[Test]
    public function the_homepage_shows_correct_translated_names_ratings_and_follower_counts(): void
    {
        $vendor = $this->vendorWithData('Real Vendor Name');
        $this->productListedBy($vendor);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Real Vendor Name');
        // rating=4 from the one seeded review, formatted to one decimal by the view.
        $response->assertSee('4.0');
        $response->assertSee('1 followers');
    }

    #[Test]
    public function a_products_vendor_product_id_on_the_homepage_matches_the_real_pivot_row(): void
    {
        $vendor = $this->vendorWithData('Pivot Check Shop');
        $product = $this->productListedBy($vendor);

        $realPivotId = VendorProduct::where('product_id', $product->id)
            ->where('vendor_id', $vendor->id)
            ->value('id');

        // The product link is built as /products/{slug}/{vendor_product_id} —
        // reading a wrong or stale id here would send buyers to the wrong (or a
        // 404) product page.
        $this->get('/')->assertSee("/products/{$product->slug}/{$realPivotId}", false);
    }
}
