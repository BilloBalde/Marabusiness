<?php

namespace Tests\Feature\Integrity;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The homepage product card printed three numbers that were simply invented:
 *
 *   $rating = rand(40,49) / 10;              a star rating, always 4.0 to 4.9
 *   {{ rand(50, 8000) }} vendus              a sales figure
 *   {{ $vendor->products_count ?? rand(20, 200) }} products
 *
 * The third fired every time, because products_count was never loaded. All three
 * were regenerated on each render, so a customer who reloaded the page saw a
 * different rating and a different number of sales for the same product, and a
 * product with no reviews and no orders still advertised "4.7 ★ · 3,412 vendus".
 *
 * These are commercial claims made to customers. They now come from
 * vendor_product_reviews, order_items and a real count of the vendor's products,
 * and the card hides each figure when there is nothing behind it.
 */
class HomepageFiguresAreRealTest extends TestCase
{
    use RefreshDatabase;

    private function vendor(): Vendor
    {
        return Vendor::factory()->create(['store_name' => 'Test Store', 'is_active' => true]);
    }

    private function featuredProduct(Vendor $vendor): array
    {
        $category = Category::create(['name' => 'Cat ' . uniqid(), 'slug' => 'cat-' . uniqid(), 'is_active' => true]);
        $brand = Brand::create(['name' => 'Brand ' . uniqid(), 'slug' => 'brand-' . uniqid()]);

        $product = Product::create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Widget ' . uniqid(),
            'slug' => 'widget-' . uniqid(),
            'is_active' => true,
            'is_featured' => true,
        ]);

        $vendorProduct = VendorProduct::create([
            'vendor_id' => $vendor->id,
            'product_id' => $product->id,
            'price' => 100,
            'stock' => 10,
            'is_active' => true,
        ]);

        return [$product, $vendorProduct];
    }

    private function sell(Product $product, int $quantity): void
    {
        $order = Order::factory()->create(['user_id' => User::factory()->create()->id]);

        DB::table('order_items')->insert([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_amount' => 100,
            'total_amount' => 100 * $quantity,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function review(VendorProduct $vendorProduct, int $rating): void
    {
        DB::table('vendor_product_reviews')->insert([
            'vendor_product_id' => $vendorProduct->id,
            'user_id' => User::factory()->create()->id,
            'rating' => $rating,
            'comment' => 'Fine.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    #[Test]
    public function a_product_with_no_reviews_and_no_sales_advertises_neither(): void
    {
        $vendor = $this->vendor();
        $this->featuredProduct($vendor);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('vendus');
    }

    #[Test]
    public function the_star_rating_is_the_real_average_of_the_reviews(): void
    {
        $vendor = $this->vendor();
        [, $vendorProduct] = $this->featuredProduct($vendor);

        // 5 and 4 average to 4.5 — a value rand(40,49)/10 could also have produced,
        // so the second review is what makes this test meaningful: the old code
        // could not have arrived at it from two specific reviews.
        $this->review($vendorProduct, 5);
        $this->review($vendorProduct, 4);

        $this->get('/')
            ->assertOk()
            ->assertSee('4.5')
            ->assertSee('(2)');
    }

    #[Test]
    public function the_sales_figure_is_the_real_quantity_ordered(): void
    {
        $vendor = $this->vendor();
        [$product] = $this->featuredProduct($vendor);

        $this->sell($product, 3);
        $this->sell($product, 4);

        $this->get('/')
            ->assertOk()
            ->assertSee('7 vendus');
    }

    #[Test]
    public function the_figures_do_not_change_between_two_identical_page_loads(): void
    {
        // The single clearest symptom of the old code: reload the page, get
        // different numbers. Nothing about the data changes between these two
        // requests, so nothing on the page may either.
        $vendor = $this->vendor();
        [$product, $vendorProduct] = $this->featuredProduct($vendor);
        $this->review($vendorProduct, 5);
        $this->sell($product, 11);

        $first = $this->get('/')->assertOk()->getContent();
        $second = $this->get('/')->assertOk()->getContent();

        foreach (['11 vendus', '5.0', '(1)'] as $figure) {
            $this->assertStringContainsString($figure, $first);
            $this->assertStringContainsString($figure, $second);
        }
    }

    #[Test]
    public function a_vendors_catalogue_size_is_counted_not_invented(): void
    {
        // products_count was never loaded, so rand(20, 200) ran every time and a
        // vendor with one product claimed to have dozens.
        $vendor = $this->vendor();
        $this->featuredProduct($vendor);

        $this->get('/')
            ->assertOk()
            ->assertSee('1 products');
    }
}
