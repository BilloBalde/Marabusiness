<?php

namespace Tests\Feature\Livewire\Content;

use App\Livewire\ProductDetailPage;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorProduct;
use App\Models\VendorProductReview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * ProductDetailPage::submitReview() (app/Livewire/ProductDetailPage.php:422-441) always
 * inserts a new vendor_product_reviews row. It never consults $this->hasReviewed (correctly
 * computed in mount(), ProductDetailPage.php:105-112) and never looks for an existing
 * (user_id, vendor_product_id) row first.
 *
 * Compare VendorPage::submitReview() (app/Livewire/VendorPage.php:110-152), which checks
 * $this->userVendorReview and branches between update() and create() -- the pattern
 * ProductDetailPage's own version should follow but doesn't.
 *
 * vendor_product_reviews also carries no unique constraint on (vendor_product_id, user_id)
 * (database/migrations/2025_12_04_152524_create_vendor_product_reviews_table.php), so
 * nothing at the DB layer backstops the missing application check either. A double click
 * before the DOM re-renders (Livewire disables the button client-side, not server-side), or
 * simply replaying the request, inserts a second review from the same buyer and skews that
 * listing's average rating.
 */
class ProductDetailPageDuplicateReviewTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_buyer_cannot_submit_two_reviews_for_the_same_listing(): void
    {
        $category = Category::create(['name' => 'Cat', 'slug' => 'cat-' . uniqid()]);
        $brand = Brand::create(['name' => 'Brand', 'slug' => 'brand-' . uniqid()]);
        $product = Product::create([
            'name' => 'Produit',
            'slug' => 'produit-' . uniqid(),
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'is_active' => true,
        ]);
        $vendorProduct = VendorProduct::create([
            'vendor_id' => Vendor::factory()->create()->id,
            'product_id' => $product->id,
            'price' => 20,
            'stock' => 10,
            'is_active' => true,
        ]);

        $buyer = User::factory()->create();

        $component = Livewire::actingAs($buyer)->test(ProductDetailPage::class, [
            'slug' => $product->slug,
            'vendor_product_id' => $vendorProduct->id,
        ]);

        $component->set('rating', 5)
            ->set('comment', 'Tres bon produit, je recommande.')
            ->call('submitReview');

        // Simulates a double submit / replayed request: only server-side state can stop
        // this, and submitReview() keeps none.
        $component->set('rating', 1)
            ->set('comment', 'En fait non, mauvaise experience finalement.')
            ->call('submitReview');

        $this->assertSame(
            1,
            VendorProductReview::where('vendor_product_id', $vendorProduct->id)
                ->where('user_id', $buyer->id)
                ->count(),
            'submitReview() inserted a second review row for the same buyer/listing instead of updating the first.'
        );
    }
}
