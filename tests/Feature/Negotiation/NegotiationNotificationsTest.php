<?php

namespace Tests\Feature\Negotiation;

use App\Mail\NegotiationAccepted;
use App\Mail\NegotiationCancelled;
use App\Mail\NegotiationOpened;
use App\Mail\NegotiationPriced;
use App\Mail\NegotiationRefused;
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
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NegotiationNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private function scenario(float $rateToUsd = 0.00012): array
    {
        $currency = Currency::factory()->create(['code' => 'GNF', 'rate_to_usd' => $rateToUsd]);
        $vendor = Vendor::factory()->create(['currency_id' => $currency->id, 'is_active' => true]);
        $buyer = User::factory()->create();

        $product = Product::create([
            'name' => 'Article négocié',
            'slug' => 'article-negocie-' . uniqid(),
            'category_id' => Category::create(['name' => 'C' . uniqid(), 'slug' => 'c-' . uniqid()])->id,
            'brand_id' => Brand::create(['name' => 'B' . uniqid(), 'slug' => 'b-' . uniqid()])->id,
            'is_active' => true,
        ]);

        VendorProduct::create([
            'vendor_id' => $vendor->id,
            'product_id' => $product->id,
            'price' => 100000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $items = [[
            'product_id' => $product->id,
            'vendor_id' => $vendor->id,
            'quantity' => 2,
            'unit_amount' => 100000,
            'total_amount' => 200000,
        ]];

        return compact('vendor', 'buyer', 'product', 'items', 'currency');
    }

    private function open(array $s, ?float $target = 150000): Order
    {
        return (new OrderNegotiation())->open(
            buyer: $s['buyer'],
            vendor: $s['vendor'],
            items: $s['items'],
            shippingLocal: 20000,
            shippingUsd: 2.4,
            targetPrice: $target,
            message: 'Bonjour, pouvez-vous faire un geste sur ce panier ?',
            address: [
                'first_name' => 'A',
                'last_name' => 'B',
                'city' => 'Conakry',
                'country' => 'Guinea',
                'phone' => '600000000',
                'street_address' => 'Rue 1',
                'state' => 'Conakry',
            ],
        );
    }

    #[Test]
    public function the_vendor_is_emailed_when_a_buyer_opens_a_negotiation(): void
    {
        Mail::fake();
        $s = $this->scenario();

        $order = $this->open($s);

        Mail::assertSent(
            NegotiationOpened::class,
            fn (NegotiationOpened $mail) => $mail->hasTo($s['vendor']->user->email)
                && $mail->order->id === $order->id
        );
    }

    #[Test]
    public function the_buyer_is_emailed_when_the_vendor_names_a_price(): void
    {
        Mail::fake();
        $s = $this->scenario();
        $order = $this->open($s);

        (new OrderNegotiation())->price($order, $s['vendor'], 180000, 3, 'Meilleur prix possible.');

        Mail::assertSent(
            NegotiationPriced::class,
            fn (NegotiationPriced $mail) => $mail->hasTo($s['buyer']->email)
        );
    }

    #[Test]
    public function the_vendor_is_emailed_when_the_buyer_refuses_a_price(): void
    {
        Mail::fake();
        $s = $this->scenario();
        $order = $this->open($s);
        (new OrderNegotiation())->price($order, $s['vendor'], 180000, 3);

        (new OrderNegotiation())->refuse($order, $s['buyer'], 'Trop cher.');

        Mail::assertSent(NegotiationRefused::class, function (NegotiationRefused $mail) use ($s) {
            return $mail->hasTo($s['vendor']->user->email) && $mail->reason === 'Trop cher.';
        });
    }

    #[Test]
    public function the_vendor_is_emailed_when_the_buyer_accepts(): void
    {
        Mail::fake();
        $s = $this->scenario();
        $order = $this->open($s);
        (new OrderNegotiation())->price($order, $s['vendor'], 180000, 3);

        (new OrderNegotiation())->accept($order, $s['buyer']);

        Mail::assertSent(
            NegotiationAccepted::class,
            fn (NegotiationAccepted $mail) => $mail->hasTo($s['vendor']->user->email)
        );
    }

    #[Test]
    public function the_vendor_is_emailed_when_the_buyer_cancels(): void
    {
        Mail::fake();
        $s = $this->scenario();
        $order = $this->open($s);

        (new OrderNegotiation())->cancel($order, $s['buyer'], 'Plus besoin.');

        Mail::assertSent(NegotiationCancelled::class, function (NegotiationCancelled $mail) use ($s) {
            return $mail->hasTo($s['vendor']->user->email) && $mail->reason === 'Plus besoin.';
        });
    }

    #[Test]
    public function all_five_negotiation_templates_actually_render(): void
    {
        // Mail::fake() never renders the view, so a broken template would only
        // surface in production.
        $s = $this->scenario();
        $order = $this->open($s)->load('vendor.currency', 'user', 'negotiation.messages');

        $opened = (new NegotiationOpened($order))->render();
        $this->assertStringContainsString($order->order_number, $opened);

        $priced = (new OrderNegotiation())->price($order, $s['vendor'], 180000, 3)
            ->load('vendor.currency', 'user');
        $this->assertStringContainsString('180,000.00', (new NegotiationPriced($priced))->render());

        $refused = (new NegotiationRefused($priced, 'Trop cher.'))->render();
        $this->assertStringContainsString('Trop cher.', $refused);

        $accepted = (new OrderNegotiation())->accept($priced, $s['buyer'])
            ->load('vendor.currency', 'user');
        $this->assertStringContainsString($accepted->order_number, (new NegotiationAccepted($accepted))->render());

        $cancelled = (new NegotiationCancelled($accepted, 'Changement d\'avis.'))->render();
        $this->assertStringContainsString('Changement d\'avis.', $cancelled);
    }
}
