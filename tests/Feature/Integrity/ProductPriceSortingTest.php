<?php

namespace Tests\Feature\Integrity;

use App\Livewire\ProductsPage;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Product;
use App\Models\Vendor;
use App\Models\VendorProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * vendor_product.price is denominated in the vendor's own currency, and the
 * catalogue page sorted and filtered on it as a bare number:
 *
 *     $query->orderBy('price', 'ASC');
 *     $query->where('price', '<=', $this->price_range);   // labelled "USD"
 *
 * With GNF at 0.00012 to the dollar, a 50,000 GNF t-shirt ($6) sorted *after* an
 * 11,000 CNY phone ($1,540), because 50,000 is the larger number. "Cheapest
 * first" put the cheapest item fourth. The filter was worse: a shopper dragging
 * it to "2000 USD" lost every GNF product in the catalogue, since their prices
 * are six- and seven-figure numbers in their own currency.
 *
 * Both now compare a USD-normalised expression built from the joined currency
 * rate. These tests use two currencies whose ordering by raw number is the
 * reverse of their ordering by value, so a regression to the naive column cannot
 * pass them.
 */
class ProductPriceSortingTest extends TestCase
{
    use RefreshDatabase;

    private function vendorIn(string $code, float $rate): Vendor
    {
        $currency = Currency::factory()->create(['code' => $code, 'rate_to_usd' => $rate]);

        return Vendor::factory()->create([
            'store_name' => "Shop {$code}",
            'is_active' => true,
            'currency_id' => $currency->id,
        ]);
    }

    private function listing(Vendor $vendor, string $name, float $price): Product
    {
        $category = Category::create(['name' => 'Cat ' . uniqid(), 'slug' => 'cat-' . uniqid(), 'is_active' => true]);
        $brand = Brand::create(['name' => 'Brand ' . uniqid(), 'slug' => 'brand-' . uniqid()]);

        $product = Product::create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => $name,
            'slug' => Str()->slug($name) . '-' . uniqid(),
            'is_active' => true,
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

    /**
     * Cheap in value, huge as a number: 50,000 GNF is $6.
     * Expensive in value, small as a number: 1,000 USD is $1,000.
     */
    private function seedTwoCurrencies(): void
    {
        $gnf = $this->vendorIn('GNF', 0.00012);
        $usd = $this->vendorIn('USD', 1.0);

        $this->listing($gnf, 'Cheap Shirt', 50000);   // $6
        $this->listing($usd, 'Costly Laptop', 1000);  // $1,000
    }

    #[Test]
    public function sorting_by_price_orders_by_what_the_item_is_actually_worth(): void
    {
        $this->seedTwoCurrencies();

        $names = Livewire::test(ProductsPage::class)
            ->set('sort', 'price')
            ->viewData('vendorProducts')
            ->pluck('product.name')
            ->all();

        // Sorting the raw column would put Costly Laptop (1,000) before
        // Cheap Shirt (50,000) — the exact inversion this guards against.
        $this->assertSame(['Cheap Shirt', 'Costly Laptop'], $names);
    }

    #[Test]
    public function the_price_filter_threshold_is_read_as_dollars(): void
    {
        $this->seedTwoCurrencies();

        $names = Livewire::test(ProductsPage::class)
            ->set('price_range', 100)
            ->viewData('vendorProducts')
            ->pluck('product.name')
            ->all();

        // "Under $100" must keep the $6 shirt and drop the $1,000 laptop.
        // Comparing the raw column would do precisely the opposite.
        $this->assertSame(['Cheap Shirt'], $names);
    }

    #[Test]
    public function a_zero_threshold_means_no_filter_rather_than_no_products(): void
    {
        $this->seedTwoCurrencies();

        $names = Livewire::test(ProductsPage::class)
            ->set('price_range', 0)
            ->viewData('vendorProducts')
            ->pluck('product.name')
            ->all();

        $this->assertCount(2, $names);
    }

    #[Test]
    public function the_default_listing_still_loads_with_the_currency_join(): void
    {
        // The join added for the conversion brings vendors and currencies into a
        // query whose default sort is latest() — and all three tables carry
        // created_at, so an unqualified sort is ambiguous and throws.
        $this->seedTwoCurrencies();

        Livewire::test(ProductsPage::class)
            ->assertOk()
            ->assertSee('Cheap Shirt')
            ->assertSee('Costly Laptop');
    }
}
