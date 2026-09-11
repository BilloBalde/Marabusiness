<?php

namespace Tests\Feature\Negotiation;

use App\Livewire\UserRfqsPage;
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
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Le vendeur qui cherche ses négociations au mauvais endroit.
 *
 * C'est ce qui s'est produit : le lien « Mes négociations » du menu mène à
 * /my-rfqs, qui ne liste que les négociations où l'on est CLIENT. Un vendeur y
 * arrivait et voyait une page vide, alors que des demandes attendaient sa
 * boutique dans son espace vendeur — exact, et parfaitement trompeur.
 */
class VendorOnBuyerPageTest extends TestCase
{
    use RefreshDatabase;

    private function shopWithANegotiation(): array
    {
        $currency = Currency::factory()->create(['code' => 'GNF', 'rate_to_usd' => 0.00012]);
        $vendorUser = User::factory()->create();
        $vendor = Vendor::factory()->create([
            'user_id' => $vendorUser->id,
            'currency_id' => $currency->id,
            'is_active' => true,
        ]);
        $buyer = User::factory()->create();

        $product = Product::create([
            'name' => 'Article',
            'slug' => 'article-' . uniqid(),
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

        return compact('vendor', 'vendorUser', 'buyer', 'order');
    }

    #[Test]
    public function the_page_tells_a_vendor_where_their_own_negotiations_live(): void
    {
        $s = $this->shopWithANegotiation();

        Livewire::actingAs($s['vendorUser'])
            ->test(UserRfqsPage::class)
            ->assertSee('Vous êtes aussi vendeur')
            ->assertSee('Ouvrir mon espace vendeur')
            ->assertSee('1 client(s) discutent le prix');
    }

    #[Test]
    public function the_link_lands_on_the_negotiations_tab(): void
    {
        $s = $this->shopWithANegotiation();

        Livewire::actingAs($s['vendorUser'])
            ->test(UserRfqsPage::class)
            ->assertSee('/vendor/orders?activeTab=negotiating', escape: false);
    }

    #[Test]
    public function an_ordinary_buyer_is_shown_none_of_it(): void
    {
        $s = $this->shopWithANegotiation();

        Livewire::actingAs($s['buyer'])
            ->test(UserRfqsPage::class)
            ->assertDontSee('Vous êtes aussi vendeur');
    }

    #[Test]
    public function a_shop_with_no_negotiation_is_still_pointed_at_the_right_place(): void
    {
        // Une boutique sans demande en cours doit tout de même comprendre que
        // cette page-ci ne concerne que ses achats.
        $s = $this->shopWithANegotiation();
        (new OrderNegotiation())->cancel($s['order'], $s['buyer']);

        Livewire::actingAs($s['vendorUser'])
            ->test(UserRfqsPage::class)
            ->assertSee('Vous êtes aussi vendeur')
            ->assertSee('se traitent depuis votre espace vendeur');
    }

    #[Test]
    public function opening_a_negotiation_does_not_log_a_missing_entries_warning(): void
    {
        // Ce que le journal a réellement enregistré à la création de la vraie
        // commande 204. Une commande en négociation n'a pas d'écritures et c'est
        // voulu — le prix n'est pas convenu. Un avertissement à chaque discussion
        // ouverte finit par noyer ceux qui comptent.
        Log::spy();

        $this->shopWithANegotiation();

        Log::shouldNotHaveReceived('warning', ['Order created without financial transactions', \Mockery::any()]);
    }
}
