<?php

namespace Tests\Feature\Negotiation;

use App\Models\Address;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorProduct;
use App\Services\OrderNegotiation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The invoice for an order whose price was negotiated.
 *
 * repriceItems() spreads the agreed total across the lines, so the invoice added
 * up correctly but said nothing about why the lines were below the catalogue
 * price. The concession is named at order level, which is where it was granted.
 *
 * The imageless-item test guards a crash rather than a wording: the thumbnail
 * was read with file_get_contents() before anything checked the file existed, so
 * one item without a picture returned a 500 for the whole invoice.
 */
class NegotiatedInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private function negotiatedOrder(bool $withImage = true): array
    {
        $currency = Currency::factory()->create([
            'code' => 'GNF',
            'rate_to_usd' => 0.00012,
            'symbol' => 'FG',
        ]);
        $vendor = Vendor::factory()->create(['currency_id' => $currency->id, 'is_active' => true]);
        $buyer = User::factory()->create();

        $product = Product::create([
            'name' => 'Article',
            'slug' => 'article-' . uniqid(),
            'category_id' => Category::create(['name' => 'C' . uniqid(), 'slug' => 'c-' . uniqid()])->id,
            'brand_id' => Brand::create(['name' => 'B' . uniqid(), 'slug' => 'b-' . uniqid()])->id,
            'is_active' => true,
            // A product with no images at all is the case that crashed.
            'images' => $withImage ? ['manquante.png'] : [],
        ]);

        VendorProduct::create([
            'vendor_id' => $vendor->id,
            'product_id' => $product->id,
            'price' => 100000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $order = (new OrderNegotiation())->open(
            buyer: $buyer,
            vendor: $vendor,
            items: [[
                'product_id' => $product->id,
                'vendor_id' => $vendor->id,
                'quantity' => 2,
                'unit_amount' => 100000,
                'total_amount' => 200000,
            ]],
            shippingLocal: 20000,
            shippingUsd: 2.4,
            targetPrice: 150000,
            message: 'Un geste ?',
            address: [
                'first_name' => 'A', 'last_name' => 'B', 'phone' => '600000000',
                'street_address' => 'Rue 1', 'city' => 'Conakry', 'state' => 'Conakry',
                'country' => 'Guinea',
            ],
        );

        return compact('vendor', 'buyer', 'order');
    }

    #[Test]
    public function the_invoice_names_the_discount_that_was_agreed(): void
    {
        $s = $this->negotiatedOrder();
        $negotiation = new OrderNegotiation();
        $negotiation->price($s['order']->fresh(), $s['vendor'], 180000, 3);
        $negotiation->accept($s['order']->fresh(), $s['buyer']);

        $response = $this->actingAs($s['buyer'])
            ->get(route('orders.invoice.preview', $s['order']));

        $response->assertOk()
            ->assertSee('Prix initial')
            ->assertSee('Remise négociée')
            // 220 000 de départ, 180 000 convenus.
            ->assertSee('220,000.00', false)
            ->assertSee('40,000.00', false);
    }

    #[Test]
    public function an_ordinary_order_shows_no_discount_line(): void
    {
        $s = $this->negotiatedOrder();
        $plain = Order::create([
            'user_id' => $s['buyer']->id,
            'vendor_id' => $s['vendor']->id,
            'order_number' => 'ORD-PLAIN-' . uniqid(),
            'grand_total' => 50000,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'status' => 'new',
            'shipping_amount' => 0,
            'rate_to_usd' => 0.00012,
        ]);
        Address::create([
            'order_id' => $plain->id,
            'user_id' => $s['buyer']->id,
            'first_name' => 'A', 'last_name' => 'B', 'phone' => '600000000',
            'street_address' => 'Rue 1', 'city' => 'Conakry', 'state' => 'Conakry',
            'country' => 'Guinea',
        ]);

        $this->actingAs($s['buyer'])
            ->get(route('orders.invoice.preview', $plain))
            ->assertOk()
            ->assertDontSee('Remise négociée');
    }

    #[Test]
    public function an_item_without_a_picture_does_not_break_the_invoice(): void
    {
        $s = $this->negotiatedOrder(withImage: false);
        $negotiation = new OrderNegotiation();
        $negotiation->price($s['order']->fresh(), $s['vendor'], 180000, 3);
        $negotiation->accept($s['order']->fresh(), $s['buyer']);

        $this->actingAs($s['buyer'])
            ->get(route('orders.invoice.preview', $s['order']))
            ->assertOk();
    }

    #[Test]
    public function a_picture_that_is_listed_but_missing_on_disk_does_not_break_it_either(): void
    {
        // 'manquante.png' is named on the product and is not in public/uploads.
        $s = $this->negotiatedOrder(withImage: true);

        $this->actingAs($s['buyer'])
            ->get(route('orders.invoice.preview', $s['order']))
            ->assertOk();
    }

    #[Test]
    public function a_stranger_still_cannot_read_the_invoice(): void
    {
        $s = $this->negotiatedOrder();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->get(route('orders.invoice.preview', $s['order']))
            ->assertForbidden();
    }
}
