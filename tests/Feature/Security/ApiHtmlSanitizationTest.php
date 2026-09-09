<?php

namespace Tests\Feature\Security;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Product;
use App\Models\Vendor;
use App\Models\VendorProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The web sanitises a vendor's rich-text product description at render
 * (product-detail-page.blade.php), but the API handed the same field to the
 * mobile app raw. flutter_html runs no JavaScript, so a <script> is inert
 * there — but a tracking <img> still reports every viewer's IP to a third
 * party, and the app already uses launchUrl elsewhere, so wiring onLinkTap
 * later would make a crafted <a href> a phishing link. Cleaned at the source
 * now, so both surfaces agree.
 */
class ApiHtmlSanitizationTest extends TestCase
{
    use RefreshDatabase;

    private function productWithDescription(string $html): array
    {
        $currency = Currency::factory()->create(['code' => 'GNF', 'rate_to_usd' => 0.00012]);
        $vendor = Vendor::factory()->create(['currency_id' => $currency->id]);
        $category = Category::create(['name' => 'Cat', 'slug' => 'cat-' . uniqid(), 'is_active' => true]);
        $brand = Brand::create(['name' => 'Brand', 'slug' => 'brand-' . uniqid()]);

        $product = Product::create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Test Product',
            'slug' => 'test-product-' . uniqid(),
            'description' => $html,
            'is_active' => true,
        ]);

        $pivot = VendorProduct::create([
            'vendor_id' => $vendor->id,
            'product_id' => $product->id,
            'price' => 1000,
            'stock' => 5,
            'is_active' => true,
        ]);

        return [$product, $pivot];
    }

    #[Test]
    public function a_script_tag_never_reaches_the_mobile_app(): void
    {
        [$product, $pivot] = $this->productWithDescription(
            '<p>Bon produit</p><script>alert(1)</script>'
        );

        $response = $this->getJson("/api/v1/products/{$product->slug}/{$pivot->id}");

        $response->assertOk();
        $description = json_encode($response->json());
        $this->assertStringNotContainsString('<script', $description);
        $this->assertStringNotContainsString('alert(1)', $description);
    }

    #[Test]
    public function an_event_handler_attribute_is_stripped(): void
    {
        [$product, $pivot] = $this->productWithDescription(
            '<p onclick="steal()">Cliquez ici</p>'
        );

        $response = $this->getJson("/api/v1/products/{$product->slug}/{$pivot->id}");

        $this->assertStringNotContainsString('onclick', json_encode($response->json()));
    }

    #[Test]
    public function a_javascript_link_is_stripped(): void
    {
        [$product, $pivot] = $this->productWithDescription(
            '<a href="javascript:steal()">Promo</a>'
        );

        $this->assertStringNotContainsString(
            'javascript:',
            json_encode($this->getJson("/api/v1/products/{$product->slug}/{$pivot->id}")->json())
        );
    }

    #[Test]
    public function legitimate_formatting_survives(): void
    {
        // The point is to clean, not to gut: vendors write real descriptions.
        [$product, $pivot] = $this->productWithDescription(
            '<p><strong>Neuf</strong> et <em>garanti</em></p><ul><li>2 ans</li></ul>'
        );

        $body = json_encode($this->getJson("/api/v1/products/{$product->slug}/{$pivot->id}")->json());

        $this->assertStringContainsString('strong', $body);
        $this->assertStringContainsString('Neuf', $body);
        $this->assertStringContainsString('garanti', $body);
        $this->assertStringContainsString('2 ans', $body);
    }
}
