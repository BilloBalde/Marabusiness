<?php

namespace Tests\Feature\Mail;

use App\Mail\VendorOrderNotification;
use App\Models\Address;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Support\VendorOrderNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Both checkouts (web and mobile API) read `$order->vendor->email` to tell a shop about
 * a new order. The vendors table has no email column — the address is the owner's
 * login — so the condition was always false and no vendor was ever notified. The
 * template behind it also linked to a route name that does not exist, and assumed
 * the delivery address was always there.
 */
class VendorOrderNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function order(bool $withAddress = true, ?User $owner = null): Order
    {
        $owner ??= User::factory()->create(['email' => 'boutique@example.test']);
        $vendor = Vendor::factory()->create([
            'user_id' => $owner->id,
            'store_name' => 'Boutique Kaloum',
            'currency_id' => Currency::factory()->create(['code' => 'GNF', 'rate_to_usd' => 0.00012])->id,
        ]);
        $buyer = User::factory()->create();

        $order = Order::create([
            'user_id' => $buyer->id,
            'vendor_id' => $vendor->id,
            'order_number' => 'NOTIF-' . uniqid(),
            'grand_total' => 105000,
            'shipping_amount' => 5000,
            'total_paid' => 0,
            'total_remaining' => 105000,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'status' => 'new',
            'rate_to_usd' => 0.00012,
        ]);

        $product = Product::create([
            'name' => 'Sac en cuir',
            'slug' => 'sac-' . uniqid(),
            'category_id' => Category::create(['name' => 'C' . uniqid(), 'slug' => 'c-' . uniqid()])->id,
            'brand_id' => Brand::create(['name' => 'B' . uniqid(), 'slug' => 'b-' . uniqid()])->id,
            'is_active' => true,
            'images' => [],
        ]);
        OrderItem::create([
            'order_id' => $order->id, 'product_id' => $product->id,
            'quantity' => 1, 'unit_amount' => 100000, 'total_amount' => 100000,
        ]);

        if ($withAddress) {
            Address::create([
                'order_id' => $order->id, 'user_id' => $buyer->id,
                'first_name' => 'Mariama', 'last_name' => 'Diallo', 'phone' => '620000000',
                'street_address' => 'Rue KA-012', 'city' => 'Conakry', 'state' => 'Conakry',
                'country' => 'Guinée',
            ]);
        }

        return $order->fresh();
    }

    #[Test]
    public function the_shop_owner_is_told_about_the_new_order(): void
    {
        Mail::fake();
        $order = $this->order();

        VendorOrderNotifier::send([$order]);

        Mail::assertSent(VendorOrderNotification::class, fn ($mail) => $mail->hasTo('boutique@example.test'));
    }

    #[Test]
    public function the_message_renders_and_links_to_the_vendor_panel(): void
    {
        $order = $this->order();

        $html = (new VendorOrderNotification($order))->render();

        $this->assertStringContainsString('Boutique Kaloum', $html);
        $this->assertStringContainsString('Sac en cuir', $html);
        $this->assertStringContainsString('/vendor/orders/' . $order->id, $html);
    }

    #[Test]
    public function an_order_with_no_delivery_address_still_renders(): void
    {
        $order = $this->order(withAddress: false);

        $html = (new VendorOrderNotification($order))->render();

        $this->assertStringContainsString($order->order_number, $html);
    }

    #[Test]
    public function a_mail_server_failure_never_breaks_the_order_that_triggered_it(): void
    {
        $order = $this->order();
        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('smtp down'));

        VendorOrderNotifier::send([$order]);

        $this->addToAssertionCount(1);
    }
}
